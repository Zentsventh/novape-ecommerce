<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Variante;
use App\Models\JarvisAuditLog;
use App\Services\OllamaService;

class JarvisAdminController extends Controller
{
    protected OllamaService $ollama;

    public function __construct(OllamaService $ollama)
    {
        $this->ollama = $ollama;
    }

    /**
     * Handle an admin Jarvis message (text or voice transcript).
     */
    public function handle(Request $request)
    {
        $start = microtime(true);
        $msg   = $request->input('message', '');
        $intent = mb_strtolower(trim($msg));

        $response = ['voice' => '', 'ui' => null, 'action' => null];

        // ── Intent-based fast responses ─────────────────────────
        if (str_contains($intent, 'como te llamas') || str_contains($intent, 'cómo te llamas') || str_contains($intent, 'quien eres') || str_contains($intent, 'quién eres') || str_contains($intent, 'tu nombre')) {
            $response['voice']  = "Soy Jarvis, tu asistente de inteligencia artificial, diseñado exclusivamente para el panel de control de Novape.";
            $response['action'] = 'greeting_identity';
        }
        elseif (str_contains($intent, 'hola') || str_contains($intent, 'buenos dias') || str_contains($intent, 'buenas tardes') || str_contains($intent, 'buenas noches')) {
            $response['voice']  = "Hola, Administrador. Sistemas en línea y a tu disposición. ¿Qué datos necesitas revisar hoy?";
            $response['action'] = 'greeting_hello';
        }
        elseif (str_contains($intent, 'ventas hoy') || str_contains($intent, 'ventas de hoy')) {
            $sales = Pedido::whereDate('created_at', now()->toDateString())->sum('total');
            $count = Pedido::whereDate('created_at', now()->toDateString())->count();
            $response['voice']  = "Hoy hemos registrado {$count} pedidos por un total de S/ " . number_format($sales, 2) . ".";
            $response['ui']     = ['type' => 'card', 'title' => 'Ventas de hoy', 'value' => "S/ " . number_format($sales, 2), 'subtitle' => "{$count} pedidos"];
            $response['action'] = 'query_sales_today';
        }
        elseif (str_contains($intent, 'ventas esta semana') || str_contains($intent, 'ventas semana')) {
            $sales = Pedido::whereBetween('created_at', [now()->startOfWeek(), now()])->sum('total');
            $count = Pedido::whereBetween('created_at', [now()->startOfWeek(), now()])->count();
            $response['voice']  = "Esta semana llevamos {$count} pedidos por S/ " . number_format($sales, 2) . ".";
            $response['ui']     = ['type' => 'card', 'title' => 'Ventas esta semana', 'value' => "S/ " . number_format($sales, 2), 'subtitle' => "{$count} pedidos"];
            $response['action'] = 'query_sales_week';
        }
        elseif (str_contains($intent, 'ventas este mes') || str_contains($intent, 'ventas mes')) {
            $sales = Pedido::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
            $count = Pedido::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
            $response['voice']  = "Este mes llevamos {$count} pedidos por un total de S/ " . number_format($sales, 2) . ".";
            $response['ui']     = ['type' => 'card', 'title' => 'Ventas del mes', 'value' => "S/ " . number_format($sales, 2), 'subtitle' => "{$count} pedidos"];
            $response['action'] = 'query_sales_month';
        }
        elseif (str_contains($intent, 'stock bajo') || str_contains($intent, 'stock critico') || str_contains($intent, 'stock crítico')) {
            // Stock is stored in the 'variante' table, not 'producto'
            $variantes = Variante::where('stock', '<', 5)
                ->whereNull('deleted_at')
                ->orderBy('stock', 'asc')
                ->take(10)
                ->with('producto')
                ->get();

            $rows = $variantes->map(fn($v) => [
                'id'       => $v->producto->id ?? $v->producto_id,
                'nombre'   => ($v->producto->nombre ?? 'Desconocido') . " (SKU: {$v->sku})",
                'stock'    => $v->stock,
            ])->toArray();

            $total = Variante::where('stock', '<', 5)->whereNull('deleted_at')->count();
            $response['voice']  = "Hay {$total} variantes con stock crítico. Te muestro las primeras " . count($rows) . ".";
            $response['ui']     = ['type' => 'table', 'title' => 'Stock Crítico', 'columns' => ['ID', 'Producto (SKU)', 'Stock'], 'rows' => $rows];
            $response['action'] = 'query_low_stock';
        }
        elseif (str_contains($intent, 'pedidos pendientes') || str_contains($intent, 'pedidos sin procesar')) {
            $count = Pedido::where('estado', 'pendiente')->count();
            $response['voice']  = "Tienes {$count} pedidos pendientes de procesar.";
            $response['ui']     = ['type' => 'card', 'title' => 'Pedidos Pendientes', 'value' => $count, 'color' => $count > 10 ? 'red' : 'green'];
            $response['action'] = 'query_pending_orders';
        }
        elseif (str_contains($intent, 'productos totales') || str_contains($intent, 'cuantos productos') || str_contains($intent, 'total productos')) {
            $total = Producto::count();
            $active = Producto::where('activo', true)->count();
            $response['voice']  = "Tienes {$total} productos registrados, de los cuales {$active} están activos.";
            $response['ui']     = ['type' => 'card', 'title' => 'Productos', 'value' => $total, 'subtitle' => "{$active} activos"];
            $response['action'] = 'query_product_count';
        }
        elseif (str_contains($intent, 'top productos') || str_contains($intent, 'más vendidos') || str_contains($intent, 'mas vendidos')) {
            $topProducts = \App\Models\PedidoItem::select('producto_id', \DB::raw('SUM(cantidad) as total_vendido'))
                ->groupBy('producto_id')
                ->orderByDesc('total_vendido')
                ->take(5)
                ->get();

            $rows = $topProducts->map(function ($item) {
                $producto = Producto::find($item->producto_id);
                return [
                    'nombre'   => $producto->nombre ?? 'Desconocido',
                    'vendidos' => $item->total_vendido,
                ];
            })->toArray();

            $response['voice']  = "Los productos más vendidos son: " . collect($rows)->pluck('nombre')->join(', ', ' y ') . ".";
            $response['ui']     = ['type' => 'table', 'title' => 'Top Productos Vendidos', 'columns' => ['Producto', 'Unidades Vendidas'], 'rows' => $rows];
            $response['action'] = 'query_top_products';
        }
        elseif (str_contains($intent, 'estado del sistema') || str_contains($intent, 'status') || str_contains($intent, 'diagnostico') || str_contains($intent, 'diagnóstico')) {
            $ollamaOk = $this->ollama->isAvailable();
            $response['voice']  = "Sistema operativo. " . ($ollamaOk ? "Inteligencia artificial conectada." : "Advertencia: modelo de IA no disponible.");
            $response['ui']     = [
                'type' => 'status',
                'items' => [
                    ['label' => 'Base de datos', 'status' => 'ok'],
                    ['label' => 'Ollama LLM', 'status' => $ollamaOk ? 'ok' : 'error'],
                    ['label' => 'Productos', 'value' => Producto::count()],
                    ['label' => 'Pedidos hoy', 'value' => Pedido::whereDate('created_at', now()->toDateString())->count()],
                ]
            ];
            $response['action'] = 'system_diagnostics';
        }
        elseif (str_contains($intent, 'ayuda') || str_contains($intent, 'que puedes hacer') || str_contains($intent, 'qué puedes hacer') || str_contains($intent, 'comandos')) {
            $response['voice'] = "Puedo ayudarte con: ventas de hoy, esta semana o este mes. Stock crítico. Pedidos pendientes. Total de productos. Productos más vendidos. Estado del sistema. O puedes hacerme cualquier pregunta libre.";
            $response['ui']    = [
                'type' => 'list',
                'title' => 'Comandos Disponibles',
                'items' => [
                    ['title' => 'ventas hoy', 'subtitle' => 'Resumen de ventas del día'],
                    ['title' => 'ventas esta semana', 'subtitle' => 'Resumen semanal'],
                    ['title' => 'ventas este mes', 'subtitle' => 'Resumen mensual'],
                    ['title' => 'stock bajo', 'subtitle' => 'Productos con stock crítico'],
                    ['title' => 'pedidos pendientes', 'subtitle' => 'Pedidos sin procesar'],
                    ['title' => 'total productos', 'subtitle' => 'Conteo de productos'],
                    ['title' => 'top productos', 'subtitle' => 'Más vendidos'],
                    ['title' => 'estado del sistema', 'subtitle' => 'Diagnóstico general'],
                ],
            ];
            $response['action'] = 'help';
        }
        else {
            // ── Fallback: ask the local LLM ──────────────────────
            $systemPrompt = "Eres JARVIS, el asistente de inteligencia artificial del panel de control de Novape, una tienda e-commerce peruana. "
                . "Responde de forma profesional, concisa y directa en español. "
                . "Si te preguntan sobre datos que no tienes, indica que necesitas más contexto. "
                . "Nunca inventes datos de ventas o inventario.";

            $ai = $this->ollama->generate($msg, $systemPrompt, 120);
            $response['voice']  = $ai ?? "No pude procesar tu solicitud. Verifica que el servidor de IA esté activo.";
            $response['action'] = 'llm_fallback';
        }

        // ── Audit log ────────────────────────────────────────────
        $elapsed = (int) ((microtime(true) - $start) * 1000);

        JarvisAuditLog::create([
            'user_id'          => $request->user()?->id,
            'role'             => 'admin',
            'intent'           => $msg,
            'payload'          => $response,
            'action_taken'     => $response['action'] ?? null,
            'response_time_ms' => $elapsed,
        ]);

        return response()->json($response);
    }
}
