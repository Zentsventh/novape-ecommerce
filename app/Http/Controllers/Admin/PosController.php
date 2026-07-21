<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\ConfiguracionSitio;

class PosController extends Controller
{
    public function index()
    {
        $cajaAbierta = DB::table('cajas_sesiones')
            ->where('cajero_id', auth()->id())
            ->where('estado', 'abierta')
            ->first();

        $almacenId = 1;
        if ($cajaAbierta) {
            try {
                $cajaFisica = DB::table('cajas')->where('id', $cajaAbierta->caja_id)->first();
                if ($cajaFisica && isset($cajaFisica->sucursal_id)) {
                    $sucursal = DB::table('sucursales')->where('id', $cajaFisica->sucursal_id)->first();
                    if ($sucursal && isset($sucursal->almacen_id)) {
                        $almacenId = $sucursal->almacen_id;
                    }
                }
            } catch (\Exception $e) {}
        }

        $productos = DB::table('producto')
            ->join('variante', 'variante.producto_id', '=', 'producto.id')
            ->join('stock_almacen', function($join) use ($almacenId) {
                $join->on('stock_almacen.variante_id', '=', 'variante.id')
                     ->where('stock_almacen.almacen_id', '=', $almacenId);
            })
            ->leftJoin('producto_imagen', function ($join) {
                $join->on('producto_imagen.producto_id', '=', 'producto.id')
                     ->where('producto_imagen.orden', '=', 0);
            })
            ->leftJoin('marca', 'marca.id', '=', 'producto.marca_id')
            ->leftJoin('producto_categoria', 'producto_categoria.producto_id', '=', 'producto.id')
            ->leftJoin('categoria', 'categoria.id', '=', 'producto_categoria.categoria_id')
            ->whereNull('producto.deleted_at')
            ->whereNull('variante.deleted_at')
            ->where('stock_almacen.cantidad', '>', 0)
            ->where('producto.activo', true)
            ->select(
                'producto.id as producto_id',
                'producto.nombre',
                'variante.id as variante_id',
                'variante.sku',
                'variante.precio',
                'variante.stock',
                'variante.atributos',
                'producto_imagen.url as imagen',
                'marca.nombre as marca_nombre',
                'categoria.nombre as categoria_nombre'
            )
            ->orderBy('producto.nombre')
            ->limit(50)
            ->get();

        $metodosPago = DB::table('metodos_pago')->where('activo', true)->get();

        $categorias = DB::table('categoria')
            ->where('activa', true)
            ->whereNull('categoria_padre_id') // Solo principales, opcional
            ->orderBy('nombre')
            ->pluck('nombre');

        $ventasHoy = DB::table('ventas_pos')
            ->whereDate('created_at', now()->toDateString())
            ->sum('total');

        $ticketsHoy = DB::table('ventas_pos')
            ->whereDate('created_at', now()->toDateString())
            ->count();

        // $cajaAbierta ya fue calculado arriba

        $ventasCajaTotal = 0;
        $ventasCajaEfectivo = 0;
        $cajaIngresos = 0;
        $cajaEgresos = 0;
        
        if ($cajaAbierta) {
            $ventasCajaTotal = DB::table('ventas_pos')->where('caja_sesion_id', $cajaAbierta->id)->sum('total');
            $ventasCajaEfectivo = DB::table('venta_pos_pagos')
                ->join('ventas_pos', 'venta_pos_pagos.venta_pos_id', '=', 'ventas_pos.id')
                ->join('metodos_pago', 'venta_pos_pagos.metodo_pago_id', '=', 'metodos_pago.id')
                ->where('ventas_pos.caja_sesion_id', $cajaAbierta->id)
                ->where('metodos_pago.tipo', 'fisico')
                ->sum('venta_pos_pagos.monto');
                
            $cajaIngresos = DB::table('caja_movimientos')->where('caja_sesion_id', $cajaAbierta->id)->where('tipo', 'ingreso')->sum('monto');
            $cajaEgresos = DB::table('caja_movimientos')->where('caja_sesion_id', $cajaAbierta->id)->where('tipo', 'egreso')->sum('monto');
        }

        $logoUrl = ConfiguracionSitio::obtener('logo_url');
        $igvPorcentaje = (float)(ConfiguracionSitio::obtener('igv_porcentaje', '18'));

        return Inertia::render('Admin/Pos/Index', [
            'productos' => $productos,
            'categorias' => $categorias,
            'metodosPago' => $metodosPago,
            'ventasHoy' => (float) $ventasHoy,
            'ticketsHoy' => $ticketsHoy,
            'cajaAbierta' => $cajaAbierta,
            'cajaIngresos' => $cajaIngresos,
            'cajaEgresos' => $cajaEgresos,
            'ventasCajaTotal' => (float) $ventasCajaTotal,
            'ventasCajaEfectivo' => (float) $ventasCajaEfectivo,
            'logoUrl' => $logoUrl,
            'igv_porcentaje' => $igvPorcentaje
        ]);
    }

    public function historial(Request $request)
    {
        $query = DB::table('ventas_pos')
            ->leftJoin('usuario', 'usuario.id', '=', 'ventas_pos.cajero_id')
            ->leftJoin('clientes', 'clientes.id', '=', 'ventas_pos.cliente_id')
            ->leftJoin('metodos_pago', 'metodos_pago.id', '=', 'ventas_pos.metodo_pago_id')
            ->select(
                'ventas_pos.id',
                'ventas_pos.codigo_ticket',
                'ventas_pos.total',
                'ventas_pos.created_at',
                'ventas_pos.tipo_comprobante',
                'usuario.nombres as cajero_nombre',
                'clientes.nombre_razon_social as cliente_nombre',
                'metodos_pago.nombre as metodo_pago'
            );

        if (!auth()->user()->esAdmin()) {
            $query->where('ventas_pos.cajero_id', auth()->id());
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function($q) use ($search) {
                $q->where('ventas_pos.codigo_ticket', 'like', "%{$search}%")
                  ->orWhere('clientes.nombre_razon_social', 'like', "%{$search}%");
            });
        }
        
        $historial = $query->orderBy('ventas_pos.created_at', 'desc')->paginate(15);
        $logoUrl = ConfiguracionSitio::obtener('logo_url');

        return Inertia::render('Admin/Pos/Historial', [
            'historial' => $historial,
            'filters' => $request->only(['search']),
            'logoUrl' => $logoUrl
        ]);
    }

    public function registrarVenta(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variante_id' => 'required|exists:variante,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'items.*.producto_nombre' => 'required|string',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'tipo_comprobante' => 'in:ticket,boleta,factura',
            'cliente' => 'nullable|array',
        ]);

        // Calcular el subtotal bruto real para las validaciones de SUNAT
        $subtotalBrutoValidation = 0;
        foreach ($request->items as $item) {
            $subtotalBrutoValidation += $item['precio_unitario'] * $item['cantidad'];
        }
        $descuentoValidation = $request->input('descuento', 0);
        $subtotalNetoValidation = max(0, $subtotalBrutoValidation - $descuentoValidation);
        $totalValidation = $subtotalNetoValidation * 1.18; // IGV 18%

        // REGLAS SUNAT PERÚ
        if ($request->tipo_comprobante === 'factura') {
            if (empty($request->cliente['numero_documento']) || strlen($request->cliente['numero_documento']) !== 11) {
                return redirect()->back()->withErrors(['cliente' => 'La Factura exige un RUC válido de 11 dígitos.']);
            }
            if (empty($request->cliente['nombre_razon_social'])) {
                return redirect()->back()->withErrors(['cliente' => 'La Factura exige una Razón Social.']);
            }
        } elseif ($request->tipo_comprobante === 'boleta' && $totalValidation >= 700) {
            if (empty($request->cliente['numero_documento'])) {
                return redirect()->back()->withErrors(['cliente' => 'Toda Boleta mayor o igual a S/ 700 exige identificar al cliente (DNI/CE).']);
            }
        }

        $caja = DB::table('cajas_sesiones')
            ->where('cajero_id', auth()->id())
            ->where('estado', 'abierta')
            ->first();
        
        if (!$caja) {
            return redirect()->back()->with('error', 'Debes aperturar caja antes de realizar ventas.');
        }

        $cliente_id = null;
        if ($request->tipo_comprobante !== 'ticket' && $request->has('cliente')) {
            $clienteData = $request->cliente;
            if (!empty($clienteData['numero_documento'])) {
                $clienteRow = DB::table('clientes')->where('numero_documento', $clienteData['numero_documento'])->first();
                if ($clienteRow) {
                    $cliente_id = $clienteRow->id;
                    DB::table('clientes')->where('id', $cliente_id)->update([
                        'nombre_razon_social' => $clienteData['nombre_razon_social'] ?? $clienteRow->nombre_razon_social,
                        'direccion' => $clienteData['direccion'] ?? $clienteRow->direccion,
                        'updated_at' => now()
                    ]);
                } else {
                    $cliente_id = DB::table('clientes')->insertGetId([
                        'tipo_documento' => $clienteData['tipo_documento'] ?? 'DNI',
                        'numero_documento' => $clienteData['numero_documento'],
                        'nombre_razon_social' => $clienteData['nombre_razon_social'] ?? 'Sin Nombre',
                        'direccion' => $clienteData['direccion'] ?? '',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        DB::beginTransaction();
        try {
            $almacenId = 1; // Default to main warehouse
            try {
                $cajaFisica = DB::table('cajas')->where('id', $caja->caja_id)->first();
                if ($cajaFisica && isset($cajaFisica->sucursal_id)) {
                    $sucursal = DB::table('sucursales')->where('id', $cajaFisica->sucursal_id)->first();
                    if ($sucursal && isset($sucursal->almacen_id)) {
                        $almacenId = $sucursal->almacen_id;
                    }
                }
            } catch (\Exception $e) {}

            $subtotalBruto = 0;
            $itemsValidados = [];
            
            foreach ($request->items as $item) {
                $variante = DB::table('variante')
                    ->where('id', $item['variante_id'])
                    ->lockForUpdate()
                    ->first();
                
                if (!$variante) {
                    throw new \Exception("Variante no encontrada.");
                }
                
                $stockAlmacen = DB::table('stock_almacen')
                    ->where('almacen_id', $almacenId)
                    ->where('variante_id', $item['variante_id'])
                    ->lockForUpdate()
                    ->first();
                
                $cantidadLocal = $stockAlmacen ? $stockAlmacen->cantidad : 0;
                
                if ($cantidadLocal < $item['cantidad']) {
                    throw new \Exception("Stock local insuficiente para el producto: " . $item['producto_nombre']);
                }

                $precioReal = $variante->precio; 
                $subtotalBruto += $precioReal * $item['cantidad'];
                
                $itemsValidados[] = [
                    'variante_id' => $item['variante_id'],
                    'producto_nombre' => $item['producto_nombre'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $precioReal,
                ];
            }

            $descuento = floatval($request->descuento ?? 0);
            $total = max(0, $subtotalBruto - $descuento);
            
            if ($request->has('pagos') && is_array($request->pagos) && count($request->pagos) > 0) {
                $sumaPagos = array_sum(array_column($request->pagos, 'monto'));
                if (round($sumaPagos, 2) !== round($total, 2)) {
                    throw new \Exception("La suma de los pagos múltiples no coincide con el total de la venta.");
                }
            }
            
            $igvPorcentaje = (float)(ConfiguracionSitio::obtener('igv_porcentaje', '18'));
            $factor = 1 + ($igvPorcentaje / 100);
            
            $subtotal = round($total / $factor, 2);
            $igv = round($total - $subtotal, 2);

            $tipoComprobante = $request->tipo_comprobante ?? 'ticket';
            
            // Obtener la serie activa para el tipo de comprobante
            $serie = DB::table('comprobantes_series')
                ->where('tipo_comprobante', $tipoComprobante)
                ->where('activo', true)
                ->lockForUpdate() // Para evitar correlativos duplicados en concurrencia
                ->first();

            if (!$serie) {
                throw new \Exception('No hay una serie activa configurada para este comprobante.');
            }

            $nuevoCorrelativo = $serie->correlativo_actual + 1;
            $codigoTicket = $serie->serie . '-' . str_pad($nuevoCorrelativo, 6, '0', STR_PAD_LEFT);
            
            // Actualizar la serie atómicamente
            DB::table('comprobantes_series')
                ->where('id', $serie->id)
                ->increment('correlativo_actual');
            $ventaId = DB::table('ventas_pos')->insertGetId([
                'codigo_ticket' => $codigoTicket,
                'cajero_id' => auth()->id(),
                'caja_sesion_id' => $caja->id,
                'cliente_id' => $cliente_id,
                'metodo_pago_id' => $request->metodo_pago_id, // Primary method
                'subtotal' => $subtotalBruto,
                'descuento' => $descuento,
                'igv' => $igv,
                'total' => $total,
                'tipo_comprobante' => $request->tipo_comprobante ?? 'ticket',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Save Split Payments if provided
            if ($request->has('pagos') && is_array($request->pagos) && count($request->pagos) > 0) {
                foreach ($request->pagos as $pago) {
                    if ($pago['monto'] > 0) {
                        DB::table('venta_pos_pagos')->insert([
                            'venta_pos_id' => $ventaId,
                            'metodo_pago_id' => $pago['metodo_pago_id'],
                            'monto' => $pago['monto'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            } else {
                DB::table('venta_pos_pagos')->insert([
                    'venta_pos_id' => $ventaId,
                    'metodo_pago_id' => $request->metodo_pago_id,
                    'monto' => $total,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // (AlmacenId logic moved above)

            foreach ($itemsValidados as $item) {
                DB::table('venta_pos_items')->insert([
                    'venta_pos_id' => $ventaId,
                    'variante_id' => $item['variante_id'],
                    'producto_nombre' => $item['producto_nombre'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['precio_unitario'] * $item['cantidad'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Descontar stock en almacén específico (Tienda Principal)
                $stockAlmacen = DB::table('stock_almacen')
                    ->where('almacen_id', $almacenId)
                    ->where('variante_id', $item['variante_id'])
                    ->first();
                
                if ($stockAlmacen) {
                    DB::table('stock_almacen')
                        ->where('id', $stockAlmacen->id)
                        ->decrement('cantidad', $item['cantidad']);
                } else {
                    DB::table('stock_almacen')->insert([
                        'almacen_id' => $almacenId,
                        'variante_id' => $item['variante_id'],
                        'cantidad' => 0 - $item['cantidad'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Recalcular stock global como caché de la sumatoria de almacenes (Arquitectura Fix)
                DB::statement("UPDATE variante SET stock = (SELECT COALESCE(SUM(cantidad), 0) FROM stock_almacen WHERE variante_id = ?) WHERE id = ?", [$item['variante_id'], $item['variante_id']]);

                // Registrar en Kardex (movimientos_almacen)
                DB::table('movimientos_almacen')->insert([
                    'almacen_id' => $almacenId,
                    'variante_id' => $item['variante_id'],
                    'tipo' => 'salida',
                    'cantidad' => -$item['cantidad'], // Salida = negativo
                    'referencia' => 'Venta POS - ' . $codigoTicket,
                    'usuario_id' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', "Venta {$codigoTicket} registrada. Total: S/ {$total}")->with('venta_id', $ventaId);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("POS Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error al procesar la venta: ' . $e->getMessage());
        }
    }

    public function buscarCliente(Request $request)
    {
        $tipo_documento = $request->query('tipo_documento');
        $numero_documento = $request->query('numero_documento');

        if (!$numero_documento) {
            return response()->json(['error' => 'Documento requerido'], 400);
        }

        // Buscar localmente
        $cliente = DB::table('clientes')->where('numero_documento', $numero_documento)->first();
        if ($cliente) {
            return response()->json([
                'success' => true,
                'origen' => 'local',
                'data' => [
                    'nombre_razon_social' => $cliente->nombre_razon_social,
                    'direccion' => $cliente->direccion,
                    'tipo_documento' => $cliente->tipo_documento,
                ]
            ]);
        }

        // Consultar API Externa (apis.net.pe)
        try {
            if ($tipo_documento === 'DNI') {
                $response = \Illuminate\Support\Facades\Http::timeout(5)->get("https://api.apis.net.pe/v1/dni?numero={$numero_documento}");
                if ($response->successful()) {
                    $data = $response->json();
                    return response()->json([
                        'success' => true,
                        'origen' => 'api',
                        'data' => [
                            'nombre_razon_social' => $data['nombre'] ?? '',
                            'direccion' => '',
                            'tipo_documento' => 'DNI'
                        ]
                    ]);
                }
            } else if ($tipo_documento === 'RUC') {
                $response = \Illuminate\Support\Facades\Http::timeout(5)->get("https://api.apis.net.pe/v1/ruc?numero={$numero_documento}");
                if ($response->successful()) {
                    $data = $response->json();
                    return response()->json([
                        'success' => true,
                        'origen' => 'api',
                        'data' => [
                            'nombre_razon_social' => $data['nombre'] ?? '',
                            'direccion' => $data['direccion'] ?? '',
                            'tipo_documento' => 'RUC'
                        ]
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Silencioso, pasamos a fallback manual
        }

        return response()->json(['success' => false, 'error' => 'No encontrado en API, ingrese los datos manualmente.']);
    }

    public function imprimirTicket($id)
    {
        $venta = DB::table('ventas_pos')
            ->leftJoin('clientes', 'ventas_pos.cliente_id', '=', 'clientes.id')
            ->leftJoin('usuario', 'ventas_pos.cajero_id', '=', 'usuario.id')
            ->select('ventas_pos.*', 'clientes.nombre_razon_social as cliente_nombre', 'clientes.numero_documento as cliente_doc', 'clientes.direccion as cliente_direccion', 'usuario.nombres as cajero_nombre')
            ->where('ventas_pos.id', $id)
            ->first();

        if (!$venta) {
            abort(404, 'Ticket no encontrado');
        }

        $items = DB::table('venta_pos_items')
            ->where('venta_pos_id', $id)
            ->get();

        $logoUrl = \App\Models\ConfiguracionSitio::obtener('logo_url');
        $nombreTienda = \App\Models\ConfiguracionSitio::obtener('nombre_tienda') ?: 'NOVAPE STORE';

        return view('admin.pos.ticket', compact('venta', 'items', 'logoUrl', 'nombreTienda'));
    }

    public function buscarProductos(Request $request)
    {
        $cajaAbierta = DB::table('cajas_sesiones')
            ->where('cajero_id', auth()->id())
            ->where('estado', 'abierta')
            ->first();

        $almacenId = 1;
        if ($cajaAbierta) {
            try {
                $cajaFisica = DB::table('cajas')->where('id', $cajaAbierta->caja_id)->first();
                if ($cajaFisica && isset($cajaFisica->sucursal_id)) {
                    $sucursal = DB::table('sucursales')->where('id', $cajaFisica->sucursal_id)->first();
                    if ($sucursal && isset($sucursal->almacen_id)) {
                        $almacenId = $sucursal->almacen_id;
                    }
                }
            } catch (\Exception $e) {}
        }

        $query = DB::table('producto')
            ->join('variante', 'variante.producto_id', '=', 'producto.id')
            ->join('stock_almacen', function($join) use ($almacenId) {
                $join->on('stock_almacen.variante_id', '=', 'variante.id')
                     ->where('stock_almacen.almacen_id', '=', $almacenId);
            })
            ->leftJoin('producto_imagen', function ($join) {
                $join->on('producto_imagen.producto_id', '=', 'producto.id')
                     ->where('producto_imagen.orden', '=', 0);
            })
            ->leftJoin('marca', 'marca.id', '=', 'producto.marca_id')
            ->leftJoin('producto_categoria', 'producto_categoria.producto_id', '=', 'producto.id')
            ->leftJoin('categoria', 'categoria.id', '=', 'producto_categoria.categoria_id')
            ->whereNull('producto.deleted_at')
            ->whereNull('variante.deleted_at')
            ->where('stock_almacen.cantidad', '>', 0)
            ->where('producto.activo', true);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('producto.nombre', 'like', "%{$search}%")
                  ->orWhere('variante.sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('categoria') && $request->input('categoria') !== 'Todas') {
            $query->where('categoria.nombre', $request->input('categoria'));
        }

        $productos = $query->select(
                'producto.id as producto_id',
                'producto.nombre',
                'variante.id as variante_id',
                'variante.sku',
                'variante.precio',
                'variante.stock',
                'variante.atributos',
                'producto_imagen.url as imagen',
                'marca.nombre as marca_nombre',
                'categoria.nombre as categoria_nombre'
            )
            ->orderBy('producto.nombre')
            ->limit(50)
            ->get();

        return response()->json(['success' => true, 'data' => $productos]);
    }
}
