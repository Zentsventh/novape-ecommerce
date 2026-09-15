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

        $clean = preg_replace('/^(jarvis[,.\s]*)/i', '', trim($message));
        $clean = trim($clean);

        if ($clean === '') {
            return $this->response('No escuché nada. ¿Podrías repetir?', null, 'empty_input');
        }

        // Add user message to history
        $this->session->push($userId, 'user', $clean);

        // AGENTIC LOOP (Max 3 turns to prevent infinite loops)
        $maxTurns = 3;
        $turn = 0;
        $result = null;
        $lastToolUI = null;
        $lastToolAction = null;
        $lastToolCommand = null;

        while ($turn < $maxTurns) {
            $turn++;
            $history = $this->session->formatForLLM($userId);
            
            $systemPrompt = "Eres JARVIS, el asistente de IA del panel de control de Novape. 
Tu trabajo es ayudar al administrador con sus tareas de forma eficiente.
REGLAS VITALES: 
- SIEMPRE utiliza las herramientas (tools) disponibles para buscar información en la base de datos o ejecutar acciones.
- Si te piden modificar algo y falta un parámetro crítico, NO adivines, pregúntale al usuario para confirmar.
- Nunca inventes datos de ventas o inventario.
- Tus respuestas de texto (cuando le hablas al usuario) deben ser conversacionales, muy cortas (1-2 oraciones) y al grano.
- Eres amable, leal y sumamente eficiente. Refiérete al usuario como 'Señor' o 'Administrador'.";

            $response = $this->llm->chatWithTools($history, $this->getToolsSchema(), $systemPrompt, 30);
            
            if (!$response || !isset($response['parts'])) {
                $result = $this->response(
                    "Hubo un problema de conexión con mi procesador neural, pero ejecuté la orden parcial.", 
                    $lastToolUI ?? null, 
                    'error',
                    $lastToolCommand ?? null
                );
                break;
            }

            $part = $response['parts'][0];

            if (isset($part['functionCall'])) {
                $funcName = $part['functionCall']['name'];
                $args = $part['functionCall']['args'] ?? [];
                
                Log::info("Jarvis Agentic Tool Call: {$funcName}", $args);
                
                // Guardar la decisión del modelo en el historial
                $this->session->push($userId, 'model', [['functionCall' => $part['functionCall']]]);

                // Ejecutar la herramienta PHP local
                $toolResult = $this->executeTool($funcName, $args);
                
                // Devolver el resultado a Gemini (role function o user dependiendo de la spec, usamos function)
                $this->session->push($userId, 'function', [
                    [
                        'functionResponse' => [
                            'name' => $funcName,
                            'response' => ['result' => $toolResult['data']]
                        ]
                    ]
                ]);

                // Guardar UI y actions para acoplarlas a la respuesta final de voz
                if (isset($toolResult['ui'])) $lastToolUI = $toolResult['ui'];
                if (isset($toolResult['action'])) $lastToolAction = $toolResult['action'];
                if (isset($toolResult['command'])) $lastToolCommand = $toolResult['command'];
                
                continue;
            } 
            
            if (isset($part['text'])) {
                $voice = $part['text'];
                
                $result = $this->response($voice, $lastToolUI, $lastToolAction ?? 'chat', $lastToolCommand);
                $this->session->push($userId, 'model', $voice);
                break;
            }
        }

        if (!$result) {
            $result = $this->response("Llegué a mi límite de razonamiento.", null, 'error');
        }

        // --- ELEVENLABS NEURAL TTS INTEGRATION ---
        if (!empty($result['voice'])) {
            $elevenLabs = new \App\Services\ElevenLabsService();
            $audioBase64 = $elevenLabs->textToSpeechBase64($result['voice']);
            if ($audioBase64) {
                $result['audio_base64'] = $audioBase64;
            }
        }

        $result['response_time_ms'] = (int)((microtime(true) - $start) * 1000);
        return $result;
    }

    /**
     * Define the tools available for Gemini to call natively.
     */
    private function getToolsSchema(): array
    {
        return [
            [
                'name' => 'update_coupon_discount',
                'description' => 'Actualiza el porcentaje de descuento de un cupón existente en la base de datos.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'codigo' => [
                            'type' => 'STRING',
                            'description' => 'El código exacto del cupón (ej: SUMMER, DESC10).'
                        ],
                        'porcentaje' => [
                            'type' => 'INTEGER',
                            'description' => 'El nuevo porcentaje de descuento (ej: 12, 15, 20).'
                        ]
                    ],
                    'required' => ['codigo', 'porcentaje']
                ]
            ],
            [
                'name' => 'navigate',
                'description' => 'Navega a una sección específica del panel de control.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'section' => [
                            'type' => 'STRING',
                            'description' => 'La sección a la que navegar: dashboard, pedidos, inventario, productos, cupones, etc.'
                        ]
                    ],
                    'required' => ['section']
                ]
            ],
            [
                'name' => 'query_count',
                'description' => 'Cuenta cuántos registros existen de una entidad en la base de datos.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'entity' => [
                            'type' => 'STRING',
                            'description' => 'La entidad a contar: productos, pedidos, cupones, clientes, etc.'
                        ]
                    ],
                    'required' => ['entity']
                ]
            ],
            [
                'name' => 'query_stock',
                'description' => 'Consulta el stock de un producto o lista productos con stock crítico.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'type' => [
                            'type' => 'STRING',
                            'description' => 'El tipo de consulta de stock: "critico" o "producto"'
                        ],
                        'product_name' => [
                            'type' => 'STRING',
                            'description' => 'El nombre del producto a buscar, si type es "producto"'
                        ]
                    ],
                    'required' => ['type']
                ]
            ]
        ];
    }

    /**
     * Enrutador de herramientas locales invocadas por el LLM.
     */
    private function executeTool(string $tool, array $args): array
    {
        return match ($tool) {
            'update_coupon_discount' => $this->toolUpdateCouponDiscount($args),
            'navigate'       => $this->adaptLegacyTool($this->toolNavigate($args)),
            'query_count'    => $this->adaptLegacyTool($this->toolQueryCount($args)),
            'query_stock'    => $this->adaptLegacyTool($this->toolQueryStock($args)),
            default          => ['data' => "Herramienta desconocida o no implementada."],
        };
    }

    /**
     * Adapta la respuesta de los métodos antiguos para que encajen en el flujo agéntico.
     */
    private function adaptLegacyTool(array $legacyResult): array
    {
        return [
            'data'    => $legacyResult['voice'] ?? 'Acción ejecutada en base de datos.',
            'ui'      => $legacyResult['ui'] ?? null,
            'action'  => $legacyResult['action'] ?? null,
            'command' => $legacyResult['command'] ?? null,
        ];
    }

    /**
     * Herramienta nivel "Santo Grial": Modificación de base de datos de cupones.
     */
    private function toolUpdateCouponDiscount(array $args): array
    {
        $codigo = $args['codigo'] ?? '';
        $porcentaje = $args['porcentaje'] ?? 0;

        $cupon = Cupon::where('codigo', $codigo)->first();
        if (!$cupon) {
            return ['data' => "Error: No encontré ningún cupón con el código '{$codigo}' en la base de datos."];
        }

        $viejo = $cupon->porcentaje;
        $cupon->porcentaje = $porcentaje;
        $cupon->save();

        return [
            'data' => "Éxito: El cupón {$codigo} ha sido actualizado del {$viejo}% al {$porcentaje}%.",
            'action' => 'coupon_updated'
        ];
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
