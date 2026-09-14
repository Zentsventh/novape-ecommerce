<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\Variante;
use App\Models\Cupon;
use App\Models\Proveedor;
use App\Models\Almacen;
use App\Models\Gasto;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\GeminiService;

/**
 * JarvisAgentService — Cerebro del Agente estilo Alexa + Hermes.
 *
 * Arquitectura:
 * 1. Recibe mensaje del usuario + historial de sesión
 * 2. Envía al LLM con catálogo de tools (patrón ReAct)
 * 3. LLM responde con JSON indicando qué tool ejecutar
 * 4. Ejecuta la tool, recoge resultado
 * 5. Genera respuesta de voz + UI
 */
class JarvisAgentService
{
    private GeminiService $llm;
    private JarvisSessionManager $session;

    public function __construct(GeminiService $llm, JarvisSessionManager $session)
    {
        $this->llm     = $llm;
        $this->session = $session;
    }

    /**
     * Procesar un mensaje del usuario — entry point principal.
     */
    public function process(string $message, ?int $userId): array
    {
        $start = microtime(true);

        // 1. Limpiar wake word
        $clean = preg_replace('/^(jarvis[,.\s]*)/i', '', trim($message));
        $clean = trim($clean);

        if ($clean === '') {
            return $this->response('No escuché nada. ¿Podrías repetir?', null, 'empty_input');
        }

        // 2. Guardar en historial
        $this->session->push($userId, 'user', $clean);

        // 3. Pedir al LLM que clasifique intent + extraiga slots
        $toolDecision = $this->resolveIntent($clean, $userId);

        // 4. Ejecutar la tool decidida
        $result = $this->executeTool($toolDecision);

        // 5. Guardar respuesta en historial
        $this->session->push($userId, 'assistant', $result['voice']);

        $result['response_time_ms'] = (int)((microtime(true) - $start) * 1000);
        return $result;
    }

    /**
     * Resolver intent usando el LLM como NLU engine (patrón Alexa).
     * El LLM analiza el mensaje y decide qué tool usar.
     */
    private function resolveIntent(string $message, ?int $userId): array
    {
        $historyContext = $this->session->formatForLLM($userId);

        $systemPrompt = <<<PROMPT
Eres el motor NLU de JARVIS, un asistente de voz para el panel de control de Novape (e-commerce peruano).

Tu trabajo es analizar el mensaje del usuario y devolver un JSON con la herramienta (tool) que debe ejecutarse.

## HERRAMIENTAS DISPONIBLES:

1. **navigate** — Navegar a una sección del panel.
   Slots: { "section": "dashboard|pos|pedidos|inventario|productos|compras|gastos|almacenes|banners|clientes|proveedores|cupones|trabajadores|roles|zonas|categorias|marcas|ajustes|metodos_pago|notificaciones|tienda|crear_producto|crear_cupon" }

2. **query_count** — Contar registros de una entidad.
   Slots: { "entity": "productos|pedidos|cupones|clientes|proveedores|almacenes|categorias|marcas|gastos|variantes" }

3. **query_sales** — Consultar ventas por período.
   Slots: { "period": "hoy|semana|mes|año" }

4. **query_stock** — Consultar stock crítico o de un producto.
   Slots: { "type": "critico|producto", "product_name": "nombre del producto (si aplica)" }

5. **query_orders** — Estado de pedidos.
   Slots: { "status": "pendiente|procesando|enviado|entregado|cancelado|todos" }

6. **query_top** — Rankings: más vendidos, etc.
   Slots: { "type": "vendidos|recientes|caros", "limit": 5 }

7. **search_product** — Buscar producto por nombre.
   Slots: { "query": "texto de búsqueda" }

8. **query_expenses** — Consultar gastos.
   Slots: { "period": "hoy|semana|mes" }

9. **system_status** — Diagnóstico del sistema.
   Slots: {}

10. **help** — Mostrar capacidades de Jarvis.
    Slots: {}

11. **greeting** — Saludos, presentación, preguntas sobre identidad.
    Slots: { "type": "hola|identidad|despedida" }

12. **conversation** — Cualquier otra cosa que no encaje en las anteriores. Conversación libre.
    Slots: { "topic": "tema de la pregunta" }

## REGLAS:
- Responde SOLO con JSON válido.
- El JSON DEBE tener: { "tool": "nombre_tool", "slots": { ... }, "confidence": 0.0-1.0 }
- Usa el historial para resolver referencias como "cuántos hay" (refiriéndose a la última entidad mencionada).
- Si el usuario dice "llévame", "abre", "ve a", "mándame", "derívame", etc. → siempre es **navigate**.
- Si dice "cuántos", "cuántas", "total de" → siempre es **query_count**.
- Si dice "busca", "encuentra", "dónde está" → siempre es **search_product**.
- Si no estás seguro, usa "conversation" con confidence baja.

{$historyContext}
PROMPT;

        $result = $this->llm->generateJSON($systemPrompt, $message, 15);

        if ($result && isset($result['tool'])) {
            Log::info('Jarvis NLU resolved', $result);
            return $result;
        }

        // Fallback: clasificación rápida con regex si el LLM falla
        Log::warning('Jarvis NLU fallback to regex', ['message' => $message]);
        return $this->regexFallback($message);
    }

    /**
     * Fallback de regex ultrarrápido si el LLM no responde.
     * Garantiza que Jarvis SIEMPRE responde algo.
     */
    private function regexFallback(string $msg): array
    {
        $m = mb_strtolower($msg);

        // Navegación
        $navVerbs = '(mandame|mándame|derivame|derívame|llevame|llévame|ir\s*a|abre|abrir|muestra|muéstrame|entra|pasa|navega|ve\s*a|ve\s*al|dame|quiero\s*ver|vamos)';
        if (preg_match("/{$navVerbs}/i", $m)) {
            $sectionMap = [
                'dashboard' => '/dashboard|panel|inicio|home/',
                'pos' => '/pos|punto\s*de\s*venta|caja/',
                'pedidos' => '/venta|pedido|orden/',
                'inventario' => '/inventario|stock/',
                'productos' => '/producto/',
                'compras' => '/compra/',
                'gastos' => '/gasto/',
                'almacenes' => '/almac[eé]n|bodega/',
                'banners' => '/banner|cms|slider/',
                'clientes' => '/cliente/',
                'proveedores' => '/proveedor/',
                'cupones' => '/cup[oó]n|descuento/',
                'trabajadores' => '/usuario|trabajador|empleado/',
                'roles' => '/rol|permiso/',
                'zonas' => '/zona|env[ií]o/',
                'categorias' => '/categor[ií]a/',
                'marcas' => '/marca/',
                'ajustes' => '/ajuste|configuraci[oó]n/',
                'metodos_pago' => '/m[eé]todo.*pago|pago/',
                'notificaciones' => '/notificaci[oó]n|alerta/',
                'tienda' => '/tienda|frontend|web/',
            ];

            foreach ($sectionMap as $section => $pattern) {
                if (preg_match($pattern . 'i', $m)) {
                    return ['tool' => 'navigate', 'slots' => ['section' => $section], 'confidence' => 0.8];
                }
            }
            return ['tool' => 'navigate', 'slots' => ['section' => 'dashboard'], 'confidence' => 0.5];
        }

        // Conteos
        if (preg_match('/(cuantos|cuántos|cuantas|cuántas|total\s*de)/i', $m)) {
            $entityMap = [
                'productos'   => '/producto/',
                'cupones'     => '/cup[oó]n/',
                'clientes'    => '/cliente/',
                'proveedores' => '/proveedor/',
                'pedidos'     => '/pedido|orden/',
                'categorias'  => '/categor[ií]a/',
                'marcas'      => '/marca/',
                'almacenes'   => '/almac[eé]n/',
                'gastos'      => '/gasto/',
            ];
            foreach ($entityMap as $entity => $pattern) {
                if (preg_match($pattern . 'i', $m)) {
                    return ['tool' => 'query_count', 'slots' => ['entity' => $entity], 'confidence' => 0.8];
                }
            }
        }

        // Ventas
        if (preg_match('/venta/i', $m)) {
            $period = 'hoy';
            if (str_contains($m, 'semana')) $period = 'semana';
            elseif (str_contains($m, 'mes')) $period = 'mes';
            elseif (str_contains($m, 'año')) $period = 'año';
            return ['tool' => 'query_sales', 'slots' => ['period' => $period], 'confidence' => 0.8];
        }

        // Stock
        if (preg_match('/stock\s*(bajo|crit|crítico)/i', $m)) {
            return ['tool' => 'query_stock', 'slots' => ['type' => 'critico'], 'confidence' => 0.9];
        }

        // Saludos
        if (preg_match('/^(hola|hey|buenas?|buenos|saludos)/i', $m)) {
            return ['tool' => 'greeting', 'slots' => ['type' => 'hola'], 'confidence' => 0.9];
        }

        if (preg_match('/(como te llamas|cómo te llamas|quien eres|quién eres|tu nombre)/i', $m)) {
            return ['tool' => 'greeting', 'slots' => ['type' => 'identidad'], 'confidence' => 0.9];
        }

        // Ayuda
        if (preg_match('/(ayuda|que puedes|qué puedes|comandos)/i', $m)) {
            return ['tool' => 'help', 'slots' => [], 'confidence' => 0.9];
        }

        // Sistema
        if (preg_match('/(estado.*sistema|status|diagn[oó]stico)/i', $m)) {
            return ['tool' => 'system_status', 'slots' => [], 'confidence' => 0.9];
        }

        // Top productos
        if (preg_match('/(top|más vendido|mas vendido|populares)/i', $m)) {
            return ['tool' => 'query_top', 'slots' => ['type' => 'vendidos', 'limit' => 5], 'confidence' => 0.8];
        }

        // Buscar producto
        if (preg_match('/(busca|encuentra|búsqueda|dónde está|donde esta)/i', $m)) {
            $query = preg_replace('/(busca|encuentra|búsqueda|buscar|dónde está|donde esta)\s*/i', '', $m);
            return ['tool' => 'search_product', 'slots' => ['query' => trim($query)], 'confidence' => 0.7];
        }

        // Pedidos pendientes
        if (preg_match('/pedidos?\s*(pendiente|sin\s*procesar)/i', $m)) {
            return ['tool' => 'query_orders', 'slots' => ['status' => 'pendiente'], 'confidence' => 0.9];
        }

        // Gastos
        if (preg_match('/gasto/i', $m)) {
            $period = 'mes';
            if (str_contains($m, 'hoy')) $period = 'hoy';
            elseif (str_contains($m, 'semana')) $period = 'semana';
            return ['tool' => 'query_expenses', 'slots' => ['period' => $period], 'confidence' => 0.8];
        }

        // Fallback: conversación libre
        return ['tool' => 'conversation', 'slots' => ['topic' => $m], 'confidence' => 0.3];
    }

    /**
     * Ejecutar la tool decidida por el NLU.
     * Cada tool es un método independiente — patrón Alexa Skills.
     */
    private function executeTool(array $decision): array
    {
        $tool  = $decision['tool'] ?? 'conversation';
        $slots = $decision['slots'] ?? [];

        return match ($tool) {
            'navigate'       => $this->toolNavigate($slots),
            'query_count'    => $this->toolQueryCount($slots),
            'query_sales'    => $this->toolQuerySales($slots),
            'query_stock'    => $this->toolQueryStock($slots),
            'query_orders'   => $this->toolQueryOrders($slots),
            'query_top'      => $this->toolQueryTop($slots),
            'search_product' => $this->toolSearchProduct($slots),
            'query_expenses' => $this->toolQueryExpenses($slots),
            'system_status'  => $this->toolSystemStatus(),
            'help'           => $this->toolHelp(),
            'greeting'       => $this->toolGreeting($slots),
            'conversation'   => $this->toolConversation($slots),
            default          => $this->toolConversation($slots),
        };
    }

    // ════════════════════════════════════════════════════════════════
    // TOOLS — Cada una devuelve ['voice' => ..., 'ui' => ..., ...]
    // ════════════════════════════════════════════════════════════════

    private function toolNavigate(array $slots): array
    {
        $section = $slots['section'] ?? 'dashboard';

        $navMap = [
            'dashboard'      => ['/admin',                    'Abriendo el panel de control principal.'],
            'pos'            => ['/admin/pos',                'Abriendo el punto de venta.'],
            'pedidos'        => ['/admin/pedidos',            'Mostrando los pedidos y ventas.'],
            'inventario'     => ['/admin/inventario',         'Abriendo el dashboard de inventario.'],
            'productos'      => ['/admin/products',           'Mostrando la lista de productos.'],
            'compras'        => ['/admin/compras',            'Abriendo la sección de compras.'],
            'gastos'         => ['/admin/gastos',             'Mostrando el registro de gastos.'],
            'almacenes'      => ['/admin/almacenes',          'Abriendo los almacenes.'],
            'banners'        => ['/admin/banners',            'Mostrando los banners del CMS.'],
            'clientes'       => ['/admin/clientes',           'Mostrando el registro de clientes.'],
            'proveedores'    => ['/admin/proveedores',        'Abriendo la sección de proveedores.'],
            'cupones'        => ['/admin/cupones',            'Mostrando los cupones de descuento.'],
            'trabajadores'   => ['/admin/trabajadores',       'Mostrando los trabajadores del sistema.'],
            'roles'          => ['/admin/roles',              'Abriendo roles y permisos.'],
            'zonas'          => ['/admin/zonas',              'Mostrando las zonas de envío.'],
            'categorias'     => ['/admin/categorias',         'Abriendo las categorías.'],
            'marcas'         => ['/admin/marcas',             'Mostrando las marcas.'],
            'ajustes'        => ['/admin/ajustes',            'Abriendo la configuración del sistema.'],
            'metodos_pago'   => ['/admin/metodos-pago',       'Mostrando los métodos de pago.'],
            'notificaciones' => ['/admin/notificaciones',     'Abriendo las notificaciones.'],
            'tienda'         => ['/',                          'Abriendo la tienda pública.'],
            'crear_producto' => ['/admin/products/create',    'Abriendo el formulario para crear un producto.'],
            'crear_cupon'    => ['/admin/cupones/create',     'Abriendo el formulario para crear un cupón.'],
        ];

        if (isset($navMap[$section])) {
            return $this->response(
                $navMap[$section][1],
                null,
                'navigate_' . $section,
                ['type' => 'redirect', 'url' => $navMap[$section][0]]
            );
        }

        return $this->response(
            "No encontré la sección '{$section}'. Dime a dónde quieres ir: productos, pedidos, cupones, inventario, clientes, almacenes...",
            null,
            'navigate_unknown'
        );
    }

    private function toolQueryCount(array $slots): array
    {
        $entity = $slots['entity'] ?? 'productos';

        $counts = [
            'productos'   => fn() => ['total' => Producto::count(), 'extra' => Producto::where('activo', true)->count() . ' activos'],
            'pedidos'     => fn() => ['total' => Pedido::count(), 'extra' => Pedido::where('estado', 'pendiente')->count() . ' pendientes'],
            'cupones'     => fn() => ['total' => Cupon::count(), 'extra' => Cupon::where('activo', true)->count() . ' activos'],
            'clientes'    => fn() => ['total' => User::count(), 'extra' => null],
            'proveedores' => fn() => ['total' => Proveedor::count(), 'extra' => null],
            'almacenes'   => fn() => ['total' => Almacen::count(), 'extra' => Almacen::where('activo', true)->count() . ' activos'],
            'categorias'  => fn() => ['total' => Categoria::count(), 'extra' => null],
            'marcas'      => fn() => ['total' => Marca::count(), 'extra' => null],
            'gastos'      => fn() => ['total' => Gasto::count(), 'extra' => 'S/ ' . number_format(Gasto::sum('monto'), 2) . ' total'],
            'variantes'   => fn() => ['total' => Variante::whereNull('deleted_at')->count(), 'extra' => Variante::where('stock', '<', 5)->whereNull('deleted_at')->count() . ' con stock bajo'],
        ];

        $entityLabel = str_replace('_', ' ', $entity);

        if (!isset($counts[$entity])) {
            return $this->response("No tengo información sobre '{$entityLabel}'.", null, 'count_unknown');
        }

        $data = $counts[$entity]();
        $voice = "Hay {$data['total']} {$entityLabel} registrados" . ($data['extra'] ? ", de los cuales {$data['extra']}." : ".");

        return $this->response($voice, [
            'type'     => 'card',
            'title'    => ucfirst($entityLabel),
            'value'    => $data['total'],
            'subtitle' => $data['extra'],
        ], 'count_' . $entity);
    }

    private function toolQuerySales(array $slots): array
    {
        $period = $slots['period'] ?? 'hoy';

        $query = Pedido::query();
        $label = '';

        switch ($period) {
            case 'hoy':
                $query->whereDate('created_at', now()->toDateString());
                $label = 'Hoy';
                break;
            case 'semana':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()]);
                $label = 'Esta semana';
                break;
            case 'mes':
                $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                $label = 'Este mes';
                break;
            case 'año':
                $query->whereYear('created_at', now()->year);
                $label = 'Este año';
                break;
        }

        $total = $query->sum('total');
        $count = $query->count();

        return $this->response(
            "{$label} hemos registrado {$count} pedidos por un total de S/ " . number_format($total, 2) . ".",
            ['type' => 'card', 'title' => "Ventas — {$label}", 'value' => 'S/ ' . number_format($total, 2), 'subtitle' => "{$count} pedidos"],
            'sales_' . $period
        );
    }

    private function toolQueryStock(array $slots): array
    {
        $type = $slots['type'] ?? 'critico';

        if ($type === 'critico') {
            $variantes = Variante::where('stock', '<', 5)
                ->whereNull('deleted_at')
                ->orderBy('stock', 'asc')
                ->take(10)
                ->with('producto')
                ->get();

            $rows = $variantes->map(fn($v) => [
                'producto' => ($v->producto->nombre ?? 'Desconocido') . " ({$v->sku})",
                'stock'    => $v->stock,
            ])->toArray();

            $total = Variante::where('stock', '<', 5)->whereNull('deleted_at')->count();

            return $this->response(
                "Hay {$total} variantes con stock crítico, menos de 5 unidades.",
                ['type' => 'table', 'title' => 'Stock Crítico', 'columns' => ['Producto (SKU)', 'Stock'], 'rows' => $rows],
                'stock_critical'
            );
        }

        // Buscar stock de un producto específico
        $name = $slots['product_name'] ?? '';
        $producto = Producto::where('nombre', 'like', "%{$name}%")->first();

        if ($producto) {
            $variantes = $producto->variantes()->whereNull('deleted_at')->get();
            $totalStock = $variantes->sum('stock');
            return $this->response(
                "El producto '{$producto->nombre}' tiene {$totalStock} unidades en stock total.",
                ['type' => 'card', 'title' => $producto->nombre, 'value' => $totalStock . ' uds.', 'subtitle' => $variantes->count() . ' variantes'],
                'stock_product'
            );
        }

        return $this->response("No encontré un producto con ese nombre.", null, 'stock_not_found');
    }

    private function toolQueryOrders(array $slots): array
    {
        $status = $slots['status'] ?? 'pendiente';

        if ($status === 'todos') {
            $total = Pedido::count();
            $byStatus = Pedido::select('estado', DB::raw('count(*) as total'))
                ->groupBy('estado')
                ->pluck('total', 'estado')
                ->toArray();

            $rows = collect($byStatus)->map(fn($count, $st) => ['estado' => ucfirst($st), 'cantidad' => $count])->values()->toArray();

            return $this->response(
                "Hay {$total} pedidos en total.",
                ['type' => 'table', 'title' => 'Pedidos por Estado', 'columns' => ['Estado', 'Cantidad'], 'rows' => $rows],
                'orders_all'
            );
        }

        $count = Pedido::where('estado', $status)->count();
        $color = $count > 10 ? '#f87171' : '#10b981';

        return $this->response(
            "Tienes {$count} pedidos en estado '{$status}'.",
            ['type' => 'card', 'title' => "Pedidos " . ucfirst($status), 'value' => $count, 'color' => $color],
            'orders_' . $status
        );
    }

    private function toolQueryTop(array $slots): array
    {
        $limit = min($slots['limit'] ?? 5, 10);

        $topProducts = PedidoItem::select('producto_id', DB::raw('SUM(cantidad) as total_vendido'))
            ->groupBy('producto_id')
            ->orderByDesc('total_vendido')
            ->take($limit)
            ->get();

        $rows = $topProducts->map(function ($item) {
            $producto = Producto::find($item->producto_id);
            return [
                'nombre'   => $producto->nombre ?? 'Desconocido',
                'vendidos' => $item->total_vendido,
            ];
        })->toArray();

        $names = collect($rows)->pluck('nombre')->join(', ', ' y ');

        return $this->response(
            "Los {$limit} productos más vendidos son: {$names}.",
            ['type' => 'table', 'title' => "Top {$limit} Productos", 'columns' => ['Producto', 'Unidades'], 'rows' => $rows],
            'top_products'
        );
    }

    private function toolSearchProduct(array $slots): array
    {
        $query = $slots['query'] ?? '';
        if (strlen($query) < 2) {
            return $this->response("Dime el nombre del producto que buscas.", null, 'search_empty');
        }

        $productos = Producto::where('nombre', 'like', "%{$query}%")
            ->where('activo', true)
            ->take(5)
            ->with('variantes')
            ->get();

        if ($productos->isEmpty()) {
            return $this->response("No encontré productos que coincidan con '{$query}'.", null, 'search_empty');
        }

        $rows = $productos->map(fn($p) => [
            'nombre' => $p->nombre,
            'stock'  => $p->variantes->sum('stock'),
            'activo' => $p->activo ? 'Sí' : 'No',
        ])->toArray();

        return $this->response(
            "Encontré {$productos->count()} productos. El primero es: {$productos->first()->nombre}.",
            ['type' => 'table', 'title' => "Búsqueda: {$query}", 'columns' => ['Producto', 'Stock', 'Activo'], 'rows' => $rows],
            'search_results'
        );
    }

    private function toolQueryExpenses(array $slots): array
    {
        $period = $slots['period'] ?? 'mes';
        $query  = Gasto::query();
        $label  = '';

        switch ($period) {
            case 'hoy':
                $query->whereDate('fecha_gasto', now()->toDateString());
                $label = 'de hoy';
                break;
            case 'semana':
                $query->whereBetween('fecha_gasto', [now()->startOfWeek()->toDateString(), now()->toDateString()]);
                $label = 'de esta semana';
                break;
            default:
                $query->whereMonth('fecha_gasto', now()->month)->whereYear('fecha_gasto', now()->year);
                $label = 'de este mes';
                break;
        }

        $total = $query->sum('monto');
        $count = $query->count();

        return $this->response(
            "Los gastos {$label} suman S/ " . number_format($total, 2) . " en {$count} registros.",
            ['type' => 'card', 'title' => "Gastos {$label}", 'value' => 'S/ ' . number_format($total, 2), 'subtitle' => "{$count} gastos"],
            'expenses_' . $period
        );
    }

    private function toolSystemStatus(): array
    {
        $llmOk = $this->llm->isAvailable();

        return $this->response(
            "Sistema operativo. " . ($llmOk ? "Inteligencia artificial conectada y funcionando." : "Advertencia: modelo de IA no disponible."),
            [
                'type'  => 'status',
                'items' => [
                    ['label' => 'Base de datos', 'status' => 'ok'],
                    ['label' => 'Gemini LLM', 'status' => $llmOk ? 'ok' : 'error'],
                    ['label' => 'Productos', 'value' => Producto::count()],
                    ['label' => 'Pedidos hoy', 'value' => Pedido::whereDate('created_at', now()->toDateString())->count()],
                    ['label' => 'Stock crítico', 'value' => Variante::where('stock', '<', 5)->whereNull('deleted_at')->count()],
                ]
            ],
            'system_status'
        );
    }

    private function toolHelp(): array
    {
        return $this->response(
            "Puedo navegar a cualquier sección, consultar ventas, stock, pedidos, gastos, cupones, clientes, buscar productos, ver diagnósticos, o responder cualquier pregunta. Dime qué necesitas.",
            [
                'type'  => 'list',
                'title' => '🧠 Capacidades de Jarvis',
                'items' => [
                    ['title' => '🗺️ Navegación', 'subtitle' => '"Llévame a cupones", "abre inventario", "ve al POS"'],
                    ['title' => '📊 Consultas', 'subtitle' => '"Ventas de hoy", "cuántos productos hay", "gastos del mes"'],
                    ['title' => '📦 Inventario', 'subtitle' => '"Stock bajo", "busca vape de fresa"'],
                    ['title' => '📋 Pedidos', 'subtitle' => '"Pedidos pendientes", "top productos vendidos"'],
                    ['title' => '🔧 Sistema', 'subtitle' => '"Estado del sistema", "diagnóstico"'],
                    ['title' => '💬 Conversación', 'subtitle' => '"Hola Jarvis", "cómo te llamas", cualquier pregunta libre'],
                ],
            ],
            'help'
        );
    }

    private function toolGreeting(array $slots): array
    {
        $type = $slots['type'] ?? 'hola';

        return match ($type) {
            'identidad' => $this->response(
                "Soy Jarvis, tu asistente de inteligencia artificial, diseñado exclusivamente para el panel de control de Novape. Estoy aquí para ayudarte a gestionar tu negocio con comandos de voz.",
                null,
                'greeting_identity'
            ),
            'despedida' => $this->response(
                "Hasta luego, Administrador. Estaré aquí cuando me necesites.",
                null,
                'greeting_bye'
            ),
            default => $this->response(
                "Hola, Administrador. Todos los sistemas están en línea. ¿En qué puedo ayudarte?",
                null,
                'greeting_hello'
            ),
        };
    }

    private function toolConversation(array $slots): array
    {
        $topic = $slots['topic'] ?? '';

        $systemPrompt = "Eres JARVIS, el asistente de inteligencia artificial del panel de control de Novape, una tienda e-commerce peruana. "
            . "Responde de forma profesional, concisa y directa en español. Máximo 2-3 oraciones. "
            . "Si te preguntan algo sobre datos de la tienda (ventas, inventario, etc.), indica que pueden pedirte esos datos con comandos como 'ventas de hoy', 'stock bajo', etc. "
            . "Nunca inventes datos de ventas o inventario.";

        $ai = $this->llm->generate($topic, $systemPrompt, 15);

        return $this->response(
            $ai ?? "Entendido. Puedo ayudarte con navegación, consultas de ventas, inventario, pedidos y más. Di 'ayuda' para ver todo.",
            null,
            'conversation'
        );
    }

    // ════════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════════

    private function response(string $voice, ?array $ui, string $action, ?array $command = null): array
    {
        $result = [
            'voice'  => $voice,
            'ui'     => $ui,
            'action' => $action,
        ];

        if ($command) {
            $result['command'] = $command;
        }

        return $result;
    }
}
