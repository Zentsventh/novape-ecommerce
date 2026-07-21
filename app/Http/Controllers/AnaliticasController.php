<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\Pedido;
use App\Models\Producto;
use Carbon\Carbon;

class AnaliticasController extends Controller
{
    public function index()
    {
        // 1. Ventas por mes (últimos 6 meses)
        $ventasMes = [];
        $meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = Carbon::now()->subMonths($i);
            $meses[] = $mes->translatedFormat('M Y');
            
            $totalMes = Pedido::where('estado', 'completado')
                ->whereYear('created_at', $mes->year)
                ->whereMonth('created_at', $mes->month)
                ->sum('total');
            $ventasMes[] = (float) $totalMes;
        }

        $chartVentas = array_map(function($mes, $total) {
            return ['mes' => ucfirst($mes), 'total' => $total];
        }, $meses, $ventasMes);

        // 2. Distribución de Estados de Pedidos (Pie chart)
        $estadosRaw = Pedido::select('estado', DB::raw('count(*) as count'))
            ->groupBy('estado')
            ->get();
        
        $chartEstados = $estadosRaw->map(function($item) {
            $colores = [
                'pendiente' => '#F59E0B',
                'procesando' => '#3B82F6',
                'enviado' => '#8B5CF6',
                'completado' => '#10B981',
                'cancelado' => '#EF4444'
            ];
            return [
                'name' => ucfirst($item->estado),
                'value' => $item->count,
                'color' => $colores[$item->estado] ?? '#6B7280'
            ];
        });

        // 3. Top 5 Productos más vendidos
        $topProductosRaw = DB::table('pedido_item')
            ->join('pedido', 'pedido.id', '=', 'pedido_item.pedido_id')
            ->join('variante', 'variante.id', '=', 'pedido_item.variante_id')
            ->join('producto', 'producto.id', '=', 'variante.producto_id')
            ->select('producto.nombre', DB::raw('SUM(pedido_item.cantidad) as total_vendido'), DB::raw('SUM(pedido_item.cantidad * pedido_item.precio_unitario) as ingresos'))
            ->where('pedido.estado', 'completado')
            ->groupBy('producto.id', 'producto.nombre')
            ->orderBy('total_vendido', 'desc')
            ->limit(5)
            ->get();
        
        $topProductos = $topProductosRaw->map(function($p) {
            return [
                'nombre' => mb_strimwidth($p->nombre, 0, 25, '...'),
                'ventas' => (int) $p->total_vendido,
                'ingresos' => (float) $p->ingresos
            ];
        });

        // 4. Métricas Clave
        $totalIngresosHistorico = Pedido::where('estado', 'completado')->sum('total');
        $totalPedidosMesActual = Pedido::whereMonth('created_at', Carbon::now()->month)->count();
        $ticketPromedio = $totalPedidosMesActual > 0 
            ? Pedido::where('estado', 'completado')->whereMonth('created_at', Carbon::now()->month)->sum('total') / Pedido::where('estado', 'completado')->whereMonth('created_at', Carbon::now()->month)->count()
            : 0;

        return Inertia::render('Admin/Analiticas/Index', [
            'chartVentas' => $chartVentas,
            'chartEstados' => $chartEstados,
            'topProductos' => $topProductos,
            'kpis' => [
                'ingresosHistorico' => $totalIngresosHistorico,
                'pedidosMes' => $totalPedidosMesActual,
                'ticketPromedio' => $ticketPromedio
            ]
        ]);
    }
}
