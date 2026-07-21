<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\ConfiguracionSitio;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('compras')
            ->leftJoin('proveedor', 'compras.proveedor_id', '=', 'proveedor.id')
            ->select('compras.id', 'compras.numero_orden', 'compras.total', 'compras.estado', 'compras.fecha_compra', 'proveedor.nombre as proveedor_nombre');

        if ($request->filled('proveedor_id')) {
            $query->where('compras.proveedor_id', $request->proveedor_id);
        }
        if ($request->filled('estado')) {
            $query->where('compras.estado', $request->estado);
        }
        if ($request->filled('marca_id')) {
            $query->whereExists(function($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('compra_items')
                  ->join('producto', 'producto.id', '=', 'compra_items.producto_id')
                  ->whereColumn('compra_items.compra_id', 'compras.id')
                  ->where('producto.marca_id', $request->marca_id);
            });
        }
        if ($request->filled('categoria_id')) {
            // Filtramos ordenes que contengan productos de la categoria o sus subcategorias
            $query->whereExists(function($q) use ($request) {
                $q->select(DB::raw(1))
                  ->from('compra_items')
                  ->join('producto', 'producto.id', '=', 'compra_items.producto_id')
                  ->join('producto_categoria', 'producto_categoria.producto_id', '=', 'producto.id')
                  ->leftJoin('categoria', 'categoria.id', '=', 'producto_categoria.categoria_id')
                  ->whereColumn('compra_items.compra_id', 'compras.id')
                  ->where(function($q2) use ($request) {
                      $q2->where('categoria.id', $request->categoria_id)
                         ->orWhere('categoria.categoria_padre_id', $request->categoria_id);
                  });
            });
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('compras.numero_orden', 'like', "%{$search}%")
                  ->orWhereExists(function($q2) use ($search) {
                      $q2->select(DB::raw(1))
                         ->from('compra_items')
                         ->join('producto', 'producto.id', '=', 'compra_items.producto_id')
                         ->join('variante', 'variante.id', '=', 'compra_items.variante_id')
                         ->whereColumn('compra_items.compra_id', 'compras.id')
                         ->where(function($q3) use ($search) {
                             $q3->where('producto.nombre', 'like', "%{$search}%")
                                ->orWhere('variante.sku', 'like', "%{$search}%");
                         });
                  });
            });
        }
        if ($request->filled('producto_id')) {
            $query->join('compra_items', 'compras.id', '=', 'compra_items.compra_id')
                  ->where('compra_items.producto_id', $request->producto_id)
                  ->distinct();
        }

        $compras = $query->orderBy('compras.fecha_compra', 'desc')->get();

        $totalGastado = $compras->where('estado', 'completado')->sum('total');
        $comprasPendientes = $compras->where('estado', 'pendiente')->count();
        $proveedores = DB::table('proveedor')->orderBy('nombre')->get(['id', 'nombre']);
        $productos = DB::table('producto')
            ->join('variante', 'variante.producto_id', '=', 'producto.id')
            ->leftJoin('producto_categoria', 'producto_categoria.producto_id', '=', 'producto.id')
            ->leftJoin('categoria', 'categoria.id', '=', 'producto_categoria.categoria_id')
            ->leftJoin('categoria as padre', 'categoria.categoria_padre_id', '=', 'padre.id')
            ->whereNull('producto.deleted_at')
            ->whereNull('variante.deleted_at')
            ->select(
                'producto.id as producto_id', 
                'producto.nombre', 
                'producto.marca_id',
                'variante.id as variante_id', 
                'variante.sku', 
                'variante.precio', 
                DB::raw('COALESCE(padre.id, categoria.id) as parent_category_id'),
                DB::raw('COALESCE(padre.nombre, categoria.nombre) as parent_category_nombre')
            )
            ->orderBy('parent_category_nombre')
            ->orderBy('producto.nombre')
            ->get();
            
        $categorias = DB::table('categoria')
            ->where('activa', true)
            ->whereNull('categoria_padre_id')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $marcas = DB::table('marca')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $historialProducto = null;
        if ($request->filled('producto_id')) {
            $historialProducto = DB::table('compra_items')
                ->join('compras', 'compra_items.compra_id', '=', 'compras.id')
                ->leftJoin('proveedor', 'compras.proveedor_id', '=', 'proveedor.id')
                ->where('compra_items.producto_id', $request->producto_id)
                ->select(
                    'proveedor.nombre as proveedor_nombre',
                    'compras.numero_orden',
                    'compras.fecha_compra',
                    'compras.estado',
                    'compra_items.cantidad',
                    'compra_items.costo_unitario',
                    'compra_items.subtotal'
                )
                ->orderBy('compras.fecha_compra', 'desc')
                ->get();
        }

        $logoUrl = ConfiguracionSitio::obtener('logo_url');

        return Inertia::render('Admin/Compras/Index', [
            'compras' => $compras,
            'totalGastado' => $totalGastado,
            'comprasPendientes' => $comprasPendientes,
            'proveedores' => $proveedores,
            'productos' => $productos,
            'categorias' => $categorias,
            'marcas' => $marcas,
            'historialProducto' => $historialProducto,
            'filters' => $request->only(['proveedor_id', 'estado', 'categoria_id', 'marca_id', 'producto_id', 'search']),
            'logoUrl' => $logoUrl
        ]);
    }

    public function show($id)
    {
        $compra = DB::table('compras')
            ->leftJoin('proveedor', 'compras.proveedor_id', '=', 'proveedor.id')
            ->select('compras.*', 'proveedor.nombre as proveedor_nombre', 'proveedor.email as proveedor_email', 'proveedor.telefono as proveedor_telefono')
            ->where('compras.id', $id)
            ->first();

        if (!$compra) abort(404);

        $items = DB::table('compra_items')
            ->leftJoin('producto', 'compra_items.producto_id', '=', 'producto.id')
            ->leftJoin('variante', 'compra_items.variante_id', '=', 'variante.id')
            ->select('compra_items.*', 'producto.nombre as producto_nombre', 'variante.sku')
            ->where('compra_items.compra_id', $id)
            ->get();

        $logoUrl = ConfiguracionSitio::obtener('logo_url');

        return Inertia::render('Admin/Compras/Show', [
            'compra' => $compra,
            'items' => $items,
            'logoUrl' => $logoUrl,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedor,id',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required',
            'items.*.variante_id' => 'required',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.costo_unitario' => 'required|numeric|min:0',
            'notas' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $total = 0;
            foreach ($request->items as $item) {
                $total += $item['costo_unitario'] * $item['cantidad'];
            }

            $count = DB::table('compras')->count() + 1;
            $numeroOrden = 'OC-' . date('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $compraId = DB::table('compras')->insertGetId([
                'numero_orden' => $numeroOrden,
                'proveedor_id' => $request->proveedor_id,
                'total' => $total,
                'estado' => 'pendiente',
                'notas' => $request->notas,
                'fecha_compra' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($request->items as $item) {
                DB::table('compra_items')->insert([
                    'compra_id' => $compraId,
                    'producto_id' => $item['producto_id'],
                    'variante_id' => $item['variante_id'],
                    'cantidad' => $item['cantidad'],
                    'costo_unitario' => $item['costo_unitario'],
                    'subtotal' => $item['costo_unitario'] * $item['cantidad'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', "Orden {$numeroOrden} creada por S/ " . number_format($total, 2));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al crear la orden: ' . $e->getMessage());
        }
    }

    public function completar($id)
    {
        $compra = DB::table('compras')->where('id', $id)->first();
        if (!$compra || $compra->estado === 'completado') {
            return redirect()->back()->with('error', 'La compra no existe o ya está completada.');
        }

        DB::beginTransaction();
        try {
            DB::table('compras')->where('id', $id)->update([
                'estado' => 'completado',
                'updated_at' => now(),
            ]);

            $items = DB::table('compra_items')->where('compra_id', $id)->get();
            $almacenId = \App\Models\ConfiguracionSitio::obtener('almacen_ecommerce_id', 1);

            foreach ($items as $item) {
                // Cálculo de Precio Promedio Ponderado (PPP)
                $variante = DB::table('variante')
                    ->where('id', $item->variante_id)
                    ->lockForUpdate()
                    ->first();
                
                if ($variante) {
                    $stockActual = $variante->stock;
                    $costoActual = $variante->precio_compra ?? 0;
                    
                    $nuevoStock = $stockActual + $item->cantidad;
                    $nuevoPPP = $costoActual;
                    
                    if ($nuevoStock > 0) {
                        $nuevoPPP = (($stockActual * $costoActual) + ($item->cantidad * $item->costo_unitario)) / $nuevoStock;
                    }
                    
                    // Actualizar stock global y PPP
                    DB::table('variante')
                        ->where('id', $item->variante_id)
                        ->update([
                            'precio_compra' => $nuevoPPP,
                            'updated_at' => now(),
                        ]);
                }

                // Actualizar stock por almacén (Sincronización con POS)
                $stockAlmacen = DB::table('stock_almacen')
                    ->where('almacen_id', $almacenId)
                    ->where('variante_id', $item->variante_id)
                    ->lockForUpdate()
                    ->first();
                
                if ($stockAlmacen) {
                    DB::table('stock_almacen')
                        ->where('id', $stockAlmacen->id)
                        ->increment('cantidad', $item->cantidad);
                } else {
                    DB::table('stock_almacen')->insert([
                        'almacen_id' => $almacenId,
                        'variante_id' => $item->variante_id,
                        'cantidad' => $item->cantidad,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
                // Recalcular stock global como caché de la sumatoria de almacenes (Arquitectura Fix)
                DB::statement("UPDATE variante SET stock = (SELECT COALESCE(SUM(cantidad), 0) FROM stock_almacen WHERE variante_id = ?) WHERE id = ?", [$item->variante_id, $item->variante_id]);

                // Registrar en Kardex (movimientos_almacen)
                DB::table('movimientos_almacen')->insert([
                    'almacen_id' => $almacenId,
                    'variante_id' => $item->variante_id,
                    'tipo' => 'entrada',
                    'cantidad' => $item->cantidad,
                    'referencia' => 'Compra Proveedor - Orden ' . $compra->numero_orden,
                    'usuario_id' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Compra completada. Inventario actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al completar la compra: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $compra = DB::table('compras')->where('id', $id)->first();
        if ($compra && $compra->estado === 'completado') {
            return redirect()->back()->with('error', 'No se puede eliminar una compra completada por motivos de auditoría de inventario.');
        }

        DB::table('compra_items')->where('compra_id', $id)->delete();
        DB::table('compras')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Orden de compra eliminada.');
    }
}
