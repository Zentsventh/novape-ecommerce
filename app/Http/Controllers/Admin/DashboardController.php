<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;
use App\Models\ConfiguracionSitio;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        $sortOrder = $request->query('sort_order', 'desc'); // 'desc' or 'asc'
        $sortBy = $request->query('sort_by', 'created_at'); // 'created_at' or 'total'

        // Convert dates to Carbon instances if provided
        $dateFilterQuery = function ($query) use ($startDate, $endDate) {
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
            return $query;
        };

        // For some models, we might not want to filter by date (like total products).
        // Let's apply date filters primarily to Orders (Pedidos) and Users.
        $totalProductos = Producto::count();
        $totalCategorias = Categoria::count();
        $totalMarcas = Marca::count();

        // Pedidos stats
        $totalPedidos = 0;
        $pedidosPendientes = 0;
        $pedidosEnviados = 0;
        $pedidosCompletados = 0;
        $pedidosCancelados = 0;
        $ventasTotal = 0;
        $pedidosRecientes = [];
        $ventasSemana = [0, 0, 0, 0, 0, 0, 0];
        $ventasMes = 0;

        try {
            $totalPedidosQuery = Pedido::query();
            $pedidosPendientesQuery = Pedido::whereRaw('LOWER(estado) = ?', ['pendiente']);
            $pedidosEnviadosQuery = Pedido::whereRaw('LOWER(estado) = ?', ['enviado']);
            $pedidosCompletadosQuery = Pedido::whereRaw('LOWER(estado) = ?', ['completado']);
            $pedidosCanceladosQuery = Pedido::whereRaw('LOWER(estado) = ?', ['cancelado']);
            
            $totalPedidos = $dateFilterQuery($totalPedidosQuery)->count();
            $pedidosPendientes = $dateFilterQuery($pedidosPendientesQuery)->count();
            $pedidosEnviados = $dateFilterQuery($pedidosEnviadosQuery)->count();
            $pedidosCompletados = $dateFilterQuery($pedidosCompletadosQuery)->count();
            $pedidosCancelados = $dateFilterQuery($pedidosCanceladosQuery)->count();

            $ventasTotalQuery = Pedido::whereRaw('LOWER(estado) IN (?, ?, ?)', ['pagado', 'enviado', 'completado']);
            $ventasTotal = (float) $dateFilterQuery($ventasTotalQuery)->sum('total');

            $ventasPosQuery = DB::table('ventas_pos');
            $ventasPosTotal = (float) $dateFilterQuery($ventasPosQuery)->sum('total');
            $ventasTotal += $ventasPosTotal;

            // Gastos operativos
            $costosGastosQuery = DB::table('gastos');
            $costosGastos = (float) $dateFilterQuery($costosGastosQuery)->sum('monto');
            
            // Compras de abastecimiento a proveedores
            $costosComprasQuery = DB::table('compras');
            $costosCompras = (float) $dateFilterQuery($costosComprasQuery)->sum('total');

            $costosTotal = $costosGastos + $costosCompras;
            $gananciaNeta = $ventasTotal - $costosTotal;

            // Ventas mes stays relative to current month, SIEMPRE.
            $ventasMesQuery = Pedido::whereRaw('LOWER(estado) IN (?, ?, ?)', ['pagado', 'enviado', 'completado'])
                ->where('created_at', '>=', now()->startOfMonth());
            $ventasMes = (float) $ventasMesQuery->sum('total');

            // Add POS sales this month
            $ventasPosMesQuery = DB::table('ventas_pos')
                ->where('created_at', '>=', now()->startOfMonth());
            $ventasMes += (float) $ventasPosMesQuery->sum('total');

            $pedidosRecientesQuery = Pedido::with('usuario');
            $pedidosRecientesQuery = $dateFilterQuery($pedidosRecientesQuery);
            $pedidosRecientes = $pedidosRecientesQuery
                ->orderBy($sortBy, $sortOrder)
                ->limit(10)
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'codigo' => $p->codigo,
                        'total' => $p->total,
                        'estado' => $p->estado,
                        'created_at' => $p->created_at,
                        'usuario_nombre' => $p->usuario ? $p->usuario->nombres . ' ' . $p->usuario->apellidos : 'Cliente',
                    ];
                });

            // Ventas de los últimos 7 días terminando en la fecha de fin del filtro (o hoy)
            $endDateCarbon = $endDate ? \Carbon\Carbon::parse($endDate) : now();
            $ventasSemana = [];
            $nombresDias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            
            for ($i = 6; $i >= 0; $i--) {
                $day = $endDateCarbon->copy()->subDays($i);
                
                $webSales = (float) Pedido::whereRaw('LOWER(estado) IN (?, ?, ?)', ['pagado', 'enviado', 'completado'])
                    ->whereDate('created_at', $day)
                    ->sum('total');
                    
                $posSales = (float) DB::table('ventas_pos')
                    ->whereDate('created_at', $day)
                    ->sum('total');
                    
                $ventasSemana[] = [
                    'dia' => $nombresDias[$day->dayOfWeek],
                    'total' => $webSales + $posSales
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Dashboard Error: ' . $e->getMessage());
        }

        // Stock: productos con variantes y su stock
        $stockBajo = [];
        $totalStock = 0;
        try {
            // Todos los productos ordenados por stock para la lista scrollable
            $allLowStock = DB::select("
                SELECT p.id, p.nombre, COALESCE(SUM(v.stock), 0) as stock_total
                FROM producto p
                LEFT JOIN variante v ON v.producto_id = p.id
                GROUP BY p.id, p.nombre
                ORDER BY stock_total ASC
                LIMIT 150
            ");
            
            $productIds = array_column($allLowStock, 'id');
            $productosData = Producto::with(['imagenes', 'marca'])->whereIn('id', $productIds)->get()->keyBy('id');
            
            $stockBajo = collect($allLowStock)->map(function ($r) use ($productosData) {
                $p = $productosData->get($r->id);
                return [
                    'id' => $r->id,
                    'nombre' => $r->nombre,
                    'marca' => $p && $p->marca ? $p->marca->nombre : null,
                    'imagen' => $p && $p->imagenes->first() ? $p->imagenes->first()->url : null,
                    'stock' => (int) $r->stock_total,
                    'activo' => $p ? $p->activo : false,
                ];
            })->toArray();
            
        } catch (\Exception $e) {}

        // Top Productos Vendidos
        $topProductosVendidos = [];
        try {
            // Combinar POS y E-commerce (agrupando por variante)
            $queryTop = DB::query()
                ->fromSub(function($query) {
                    $query->select('variante_id', 'cantidad')
                          ->from('venta_pos_items')
                          ->unionAll(
                              DB::table('pedido_item')
                                ->join('pedido', 'pedido.id', '=', 'pedido_item.pedido_id')
                                ->where('pedido.estado', 'completado')
                                ->select('variante_id', 'cantidad')
                          );
                }, 'ventas_combinadas')
                ->select('variante_id', DB::raw('SUM(cantidad) as total_vendido'))
                ->groupBy('variante_id')
                ->orderBy('total_vendido', 'desc')
                ->limit(15)
                ->get();
                
            foreach ($queryTop as $item) {
                $variante = \App\Models\Variante::with('producto')->find($item->variante_id);
                $nombre = $variante && $variante->producto ? $variante->producto->nombre : 'Desconocido';
                
                $encontrado = false;
                foreach ($topProductosVendidos as &$tv) {
                    if ($tv['nombre'] === $nombre) {
                        $tv['cantidad'] += (int) $item->total_vendido;
                        $encontrado = true;
                        break;
                    }
                }
                
                if (!$encontrado) {
                    $topProductosVendidos[] = [
                        'nombre' => $nombre,
                        'cantidad' => (int) $item->total_vendido,
                    ];
                }
            }
            
            usort($topProductosVendidos, function($a, $b) {
                return $b['cantidad'] <=> $a['cantidad'];
            });
            $topProductosVendidos = array_slice($topProductosVendidos, 0, 6);
            
        } catch (\Exception $e) {
            \Log::error('Error Top Productos: ' . $e->getMessage());
        }

        // Productos recientes
        $productosRecientes = Producto::with(['marca', 'imagenes', 'categorias', 'variantes'])
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'marca' => $p->marca ? $p->marca->nombre : null,
                    'imagen' => $p->imagenes->first() ? $p->imagenes->first()->url : null,
                    'categoria' => $p->categorias->first() ? $p->categorias->first()->nombre : null,
                    'activo' => $p->activo,
                    'stock' => (function() use ($p) {
                        $s = 0;
                        foreach ($p->variantes as $v) {
                            $s += $v->stock;
                        }
                        return $s;
                    })(),
                    'precio' => $p->variantes->first() ? $p->variantes->first()->precio : 0,
                ];
            });

        $totalUsuarios = 0;
        try {
            $usuariosQuery = DB::table('usuario');
            $totalUsuarios = $dateFilterQuery($usuariosQuery)->count();
        } catch (\Exception $e) {}

        $logoUrl = ConfiguracionSitio::obtener('logo_url');

        return Inertia::render('Admin/Dashboard', [
            'totalProductos' => $totalProductos,
            'totalPedidos' => $totalPedidos,
            'pedidosPendientes' => $pedidosPendientes,
            'pedidosEnviados' => $pedidosEnviados,
            'pedidosCompletados' => $pedidosCompletados,
            'pedidosCancelados' => $pedidosCancelados,
            'ventasTotal' => (float) $ventasTotal,
            'costosTotal' => $costosTotal,
            'gananciaNeta' => $gananciaNeta,
            'ventasMes' => (float) $ventasMes,
            'pedidosRecientes' => $pedidosRecientes,
            'ventasSemana' => $ventasSemana,
            'stockBajo' => $stockBajo,
            'topProductosVendidos' => $topProductosVendidos,
            'totalCategorias' => $totalCategorias,
            'logoUrl' => $logoUrl,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'sort_order' => $sortOrder,
                'sort_by' => $sortBy
            ]
        ]);
    }



    public function globalSearch(Request $request)
    {
        $q = $request->query('q');
        if (!$q) return response()->json(['productos' => [], 'pedidos' => [], 'usuarios' => []]);

        $productos = [];
        $pedidos = [];
        $usuarios = [];

        $user = auth()->user();

        if ($user->tienePermiso('ver_productos')) {
            $productosDb = \App\Models\Producto::with('variantes')
                ->where('nombre', 'like', "%$q%")
                ->orWhere('id', 'like', "$q%")
                ->limit(5)->get();
            $productos = $productosDb->map(function($p) {
                $stock = 0;
                foreach($p->variantes as $v) $stock += $v->stock;
                $precio = $p->variantes->first() ? $p->variantes->first()->precio : 0;
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'precio' => $precio,
                    'stock' => $stock
                ];
            });
        }

        if ($user->tienePermiso('ver_pedidos')) {
            $pedidosQuery = \App\Models\Pedido::with('usuario:id,nombres,apellidos')
                ->where('id', 'like', "$q%");
            
            $pedidosQuery->orWhereHas('usuario', function($query) use ($q) {
                $query->where('nombres', 'like', "%$q%")
                      ->orWhere('email', 'like', "%$q%");
            });
            
            $pedidos = $pedidosQuery->limit(5)->get(['id', 'estado', 'total', 'usuario_id']);
        }

        if ($user->tienePermiso('ver_usuarios')) {
            $usuarios = \App\Models\Usuario::where('nombres', 'like', "%$q%")
                ->orWhere('apellidos', 'like', "%$q%")
                ->orWhere('email', 'like', "%$q%")
                ->orWhere('dni', 'like', "%$q%")
                ->limit(5)->get(['id', 'nombres', 'apellidos', 'email', 'dni']);
        }

        return response()->json([
            'productos' => $productos,
            'pedidos' => $pedidos,
            'usuarios' => $usuarios
        ]);
    }

    public function exportarPdf(Request $request)
    {
        $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $dateFilterQuery = function ($query) use ($startDate, $endDate) {
            if ($startDate) $query->whereDate('created_at', '>=', $startDate);
            if ($endDate) $query->whereDate('created_at', '<=', $endDate);
            return $query;
        };

        // Stats Globales
        $ventasWebQuery = Pedido::where('estado', 'completado');
        $ventasWeb = (float) $dateFilterQuery($ventasWebQuery)->sum('total');

        $ventasPosQuery = DB::table('ventas_pos');
        $ventasPos = (float) $dateFilterQuery($ventasPosQuery)->sum('total');

        $ventasTotal = $ventasWeb + $ventasPos;

        $costosGastosQuery = DB::table('gastos');
        $costosGastos = (float) $dateFilterQuery($costosGastosQuery)->sum('monto');
        
        $costosComprasQuery = DB::table('compras');
        $costosCompras = (float) $dateFilterQuery($costosComprasQuery)->sum('total');

        $costosTotal = $costosGastos + $costosCompras;
        $gananciaNeta = $ventasTotal - $costosTotal;

        $pedidosCount = $dateFilterQuery(Pedido::query())->count();

        // Pedidos Recientes
        $pedidosRecientes = $dateFilterQuery(Pedido::with('usuario'))
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        // Convert logo to base64 to avoid Puppeteer deadlock on php artisan serve
        $logoUrl = ConfiguracionSitio::obtener('logo_url');
        $logoBase64 = null;
        if ($logoUrl) {
            $logoPath = storage_path('app/public/' . str_replace('public/', '', $logoUrl));
            if (!file_exists($logoPath)) {
                // Try from public path directly as fallback
                $logoPath = public_path('images/logofactura.png');
            }
            if (file_exists($logoPath)) {
                $logoMime = mime_content_type($logoPath);
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoBase64 = 'data:' . $logoMime . ';base64,' . $logoData;
            }
        }

        // Stock bajo
        $stockBajo = [];
        try {
            $allLowStock = DB::select("
                SELECT p.id, p.nombre, COALESCE(SUM(v.stock), 0) as stock_total
                FROM producto p
                LEFT JOIN variante v ON v.producto_id = p.id
                GROUP BY p.id, p.nombre
                HAVING stock_total <= 5
                ORDER BY stock_total ASC
                LIMIT 8
            ");
            $stockBajo = collect($allLowStock)->map(function ($r) {
                return [
                    'nombre' => $r->nombre,
                    'stock' => $r->stock_total,
                ];
            })->toArray();
        } catch (\Exception $e) {}

        // Top Productos Vendidos en el periodo seleccionado
        $topProductosVendidos = [];
        try {
            // Combinar POS y E-commerce (agrupando por variante)
            $queryTop = DB::query()
                ->fromSub(function($query) use ($startDate, $endDate) {
                    $q1 = DB::table('venta_pos_items')
                          ->join('ventas_pos', 'ventas_pos.id', '=', 'venta_pos_items.venta_id')
                          ->select('variante_id', 'cantidad');
                    if ($startDate) $q1->whereDate('ventas_pos.created_at', '>=', $startDate);
                    if ($endDate) $q1->whereDate('ventas_pos.created_at', '<=', $endDate);

                    $q2 = DB::table('pedido_item')
                          ->join('pedido', 'pedido.id', '=', 'pedido_item.pedido_id')
                          ->where('pedido.estado', 'completado')
                          ->select('variante_id', 'cantidad');
                    if ($startDate) $q2->whereDate('pedido.created_at', '>=', $startDate);
                    if ($endDate) $q2->whereDate('pedido.created_at', '<=', $endDate);

                    $query->from($q1->unionAll($q2), 'ventas_combinadas');
                }, 'ventas_combinadas')
                ->select('variante_id', DB::raw('SUM(cantidad) as total_vendido'))
                ->groupBy('variante_id')
                ->orderBy('total_vendido', 'desc')
                ->limit(15)
                ->get();
                
            foreach ($queryTop as $item) {
                $variante = \App\Models\Variante::with('producto')->find($item->variante_id);
                $nombre = $variante && $variante->producto ? $variante->producto->nombre : 'Desconocido';
                
                $encontrado = false;
                foreach ($topProductosVendidos as &$tv) {
                    if ($tv['nombre'] === $nombre) {
                        $tv['cantidad'] += (int) $item->total_vendido;
                        $encontrado = true;
                        break;
                    }
                }
                
                if (!$encontrado) {
                    $topProductosVendidos[] = [
                        'nombre' => $nombre,
                        'cantidad' => (int) $item->total_vendido,
                    ];
                }
            }
            
            usort($topProductosVendidos, function($a, $b) {
                return $b['cantidad'] <=> $a['cantidad'];
            });
            $topProductosVendidos = array_slice($topProductosVendidos, 0, 6);
            
        } catch (\Exception $e) {
            \Log::error('Error Top Productos Export: ' . $e->getMessage());
        }

        $data = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'ventasWeb' => $ventasWeb,
            'ventasPos' => $ventasPos,
            'ventasTotal' => $ventasTotal,
            'costosTotal' => $costosTotal,
            'gananciaNeta' => $gananciaNeta,
            'pedidosCount' => $pedidosCount,
            'pedidos' => $pedidosRecientes,
            'logoBase64' => $logoBase64,
            'stockBajo' => $stockBajo,
            'topProductosVendidos' => $topProductosVendidos
        ];

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.dashboard', $data)
            ->setPaper('A4', 'portrait')
            ->download('reporte_dashboard_' . date('Y-m-d') . '.pdf');
    }
}
