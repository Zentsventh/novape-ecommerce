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

/**
 * JarvisAgentService — Motor Alexa-Style 100% Local.
 *
 * Arquitectura (ingeniería inversa de Amazon Alexa):
 * 1. Recibe texto del usuario (transcripción de voz o texto escrito)
 * 2. classifyIntent() — Clasifica la intención con pattern matching (NLU local)
 * 3. extractSlots() — Extrae parámetros/slots del texto con regex
 * 4. executeTool() — Ejecuta la skill/herramienta PHP correspondiente
 * 5. Devuelve respuesta de voz + UI (el frontend usa Web Speech API para hablar)
 *
 * SIN dependencias externas: no Gemini, no ElevenLabs, no APIs de terceros.
 * Respuesta instantánea (< 50ms).
 */
class JarvisAgentService
{
    /**
     * Procesar un mensaje del usuario — entry point principal.
     */
    public function process(string $message, ?int $userId): array
    {
        $start = microtime(true);

        // Limpiar input: quitar "jarvis" del inicio y espacios extra
        $clean = preg_replace('/^(jarvis[,.!?\s]*)/i', '', trim($message));
        $clean = trim($clean);

        if ($clean === '') {
            return $this->response('No escuché nada. ¿Podrías repetir?', null, 'empty_input');
        }

        // ── MOTOR NLU LOCAL (tipo Alexa) ──────────────────────────
        $intent = $this->classifyIntent($clean);
        $slots  = $this->extractSlots($clean, $intent);

        Log::info('Jarvis NLU', [
            'input'  => $clean,
            'intent' => $intent,
            'slots'  => $slots,
        ]);

        // ── EJECUTAR SKILL ────────────────────────────────────────
        $result = $this->executeTool($intent, $slots);

        $result['response_time_ms'] = (int)((microtime(true) - $start) * 1000);
        return $result;
    }

    // ════════════════════════════════════════════════════════════════
    // NLU ENGINE — Clasificación de intenciones por patrones
    // ════════════════════════════════════════════════════════════════

    /**
     * Clasificar la intención del usuario usando pattern matching.
     * Orden de prioridad: más específico → más genérico.
     */
    private function classifyIntent(string $text): string
    {
        $t = mb_strtolower($text);

        // ── Navegación ────────────────────────────────────────────
        $navVerbs = 'abr[ie]|ir a|ve a|vamos a|mostrar|muestra|muéstrame|muestrame|llévame|llevame|navegar|entra|quiero ver|necesito ver';
        $navTargets = 'dashboard|inicio|panel|gastos|productos|categorías|categorias|marcas|pedidos|clientes|trabajadores|proveedores|cupones|pos|punto de venta|ventas|caja|compras|inventario|almacenes|zonas|métodos de pago|metodos de pago|roles|permisos|ajustes|configuraci[oó]n|banners|tienda|crear producto|crear cup[oó]n|notificaciones';
        
        if (preg_match("/($navVerbs)\s+.{0,10}($navTargets)/u", $t)) {
            return 'navigate';
        }
        // Comando corto: solo el destino
        if (preg_match("/^($navTargets)$/u", $t)) {
            return 'navigate';
        }

        // ── Actualizar cupón ──────────────────────────────────────
        if (preg_match('/(cambiar?|actualizar?|modificar?|poner?|pon)\s.*(cup[oó]n|descuento)/u', $t) ||
            preg_match('/cup[oó]n\s+\w+\s+(al?\s+)?\d+/u', $t)) {
            return 'update_coupon';
        }

        // ── Consultas de ventas ───────────────────────────────────
        if (preg_match('/(ventas?|facturación|facturacion|ingresos?)\s*(de|del)?\s*(hoy|semana|mes|año|a[ñn]o)/u', $t) ||
            preg_match('/cu[aá]nto\s+(se\s+)?vendi[oó]|cu[aá]nto\s+(hemos?\s+)?vendido/u', $t)) {
            return 'query_sales';
        }

        // ── Consultas de stock ────────────────────────────────────
        if (preg_match('/(stock|inventario)\s*(cr[ií]tico|bajo|m[ií]nimo)/u', $t) ||
            preg_match('/productos?\s+(con\s+)?(poco\s+)?stock\s*(bajo|cr[ií]tico)?/u', $t)) {
            return 'query_stock_critical';
        }
        if (preg_match('/(stock|existencias?|unidades?)\s*(de[l]?\s+)/u', $t) ||
            preg_match('/cu[aá]nto\s+(stock|hay)\s*(de[l]?\s+)/u', $t)) {
            return 'query_stock_product';
        }

        // ── Consultas de conteo ───────────────────────────────────
        if (preg_match('/cu[aá]ntos?\s+(productos?|pedidos?|cupones?|clientes?|proveedores?|almacenes?|categor[ií]as?|marcas?|gastos?|variantes?|trabajadores?)/u', $t)) {
            return 'query_count';
        }

        // ── Consultas de pedidos ──────────────────────────────────
        if (preg_match('/pedidos?\s+(pendientes?|procesando|enviados?|entregados?|cancelados?|todos)/u', $t) ||
            preg_match('/(cu[aá]ntos?\s+)?pedidos?\s+(hay|tengo|tenemos)/u', $t)) {
            return 'query_orders';
        }

        // ── Top productos ─────────────────────────────────────────
        if (preg_match('/(top|m[aá]s\s+vendidos?|mejores?\s+productos?|ranking)/u', $t)) {
            return 'query_top';
        }

        // ── Consultas de gastos ───────────────────────────────────
        if (preg_match('/(gastos?)\s*(de|del)?\s*(hoy|semana|mes|año|a[ñn]o)/u', $t) ||
            preg_match('/cu[aá]nto\s+(se\s+)?(gast[oó]|gastamos|gastado)/u', $t)) {
            return 'query_expenses';
        }

        // ── Búsqueda de producto ──────────────────────────────────
        if (preg_match('/(buscar?|encontrar?|busca|encuentra)\s+/u', $t)) {
            return 'search_product';
        }

        // ── Estado del sistema ────────────────────────────────────
        if (preg_match('/(estado|diagnóstico|diagnostico|status)\s*(del)?\s*(sistema)?/u', $t) ||
            preg_match('/c[oó]mo\s+est[aá]\s+(el\s+)?(sistema|todo|la\s+tienda)/u', $t)) {
            return 'system_status';
        }

        // ── Hora y fecha ─────────────────────────────────────────
        if (preg_match('/qu[eé]\s+hora\s+es|dime\s+la\s+hora/u', $t)) {
            return 'time';
        }
        if (preg_match('/qu[eé]\s+(fecha|d[ií]a)\s+es|dime\s+la\s+fecha/u', $t)) {
            return 'date';
        }

        // ── Acciones del sistema ──────────────────────────────────
        if (preg_match('/(recargar?|recarga|actualizar?|actualiza|refresh)/u', $t)) {
            return 'action_reload';
        }
        if (preg_match('/(cerrar\s+sesi[oó]n|logout|salir\s+del\s+sistema)/u', $t)) {
            return 'action_logout';
        }
        if (preg_match('/(retroceder|atr[aá]s|volver|regresar)/u', $t)) {
            return 'action_back';
        }

        // ── Conversación ─────────────────────────────────────────
        if (preg_match('/^(hola|hey|buenos?\s+d[ií]as?|buenas?\s+(tardes?|noches?))/u', $t)) {
            return 'greeting';
        }
        if (preg_match('/(qui[eé]n\s+eres|c[oó]mo\s+te\s+llamas|cu[aá]l\s+es\s+tu\s+nombre)/u', $t)) {
            return 'identity';
        }
        if (preg_match('/(c[oó]mo\s+est[aá]s|qu[eé]\s+tal\s+est[aá]s|todo\s+bien)/u', $t)) {
            return 'how_are_you';
        }
        if (preg_match('/(adi[oó]s|chao|hasta\s+luego|nos\s+vemos)/u', $t)) {
            return 'farewell';
        }
        if (preg_match('/(gracias|muchas\s+gracias|te\s+agradezco)/u', $t)) {
            return 'thanks';
        }
        if (preg_match('/(ayuda|qu[eé]\s+puedes\s+hacer|comandos|instrucciones)/u', $t)) {
            return 'help';
        }

        // ── Fallback ─────────────────────────────────────────────
        return 'unknown';
    }

    // ════════════════════════════════════════════════════════════════
    // SLOT EXTRACTION — Extraer parámetros del texto
    // ════════════════════════════════════════════════════════════════

    private function extractSlots(string $text, string $intent): array
    {
        $t = mb_strtolower($text);
        $slots = [];

        switch ($intent) {
            case 'navigate':
                $slots['section'] = $this->extractNavigationTarget($t);
                break;

            case 'query_sales':
            case 'query_expenses':
                $slots['period'] = $this->extractPeriod($t);
                break;

            case 'query_count':
                if (preg_match('/cu[aá]ntos?\s+(\w+)/u', $t, $m)) {
                    $slots['entity'] = $this->normalizeEntity($m[1]);
                }
                break;

            case 'query_orders':
                $slots['status'] = $this->extractOrderStatus($t);
                break;

            case 'query_top':
                $slots['limit'] = 5;
                if (preg_match('/top\s+(\d+)/u', $t, $m)) {
                    $slots['limit'] = min((int)$m[1], 10);
                }
                break;

            case 'query_stock_product':
                if (preg_match('/(stock|existencias?|unidades?)\s*(de[l]?\s+)(.+)/u', $t, $m)) {
                    $slots['product_name'] = trim($m[3]);
                }
                if (preg_match('/cu[aá]nto\s+(stock|hay)\s*(de[l]?\s+)(.+)/u', $t, $m)) {
                    $slots['product_name'] = trim($m[3]);
                }
                break;

            case 'search_product':
                if (preg_match('/(buscar?|encontrar?|busca|encuentra)\s+(.+)/u', $t, $m)) {
                    $slots['query'] = trim($m[2]);
                }
                break;

            case 'update_coupon':
                if (preg_match('/cup[oó]n\s+(\w+)/u', $t, $m)) {
                    $slots['codigo'] = mb_strtoupper($m[1]);
                }
                if (preg_match('/(\d+)\s*%?/u', $t, $m)) {
                    $slots['porcentaje'] = (int)$m[1];
                }
                break;
        }

        return $slots;
    }

    private function extractNavigationTarget(string $t): string
    {
        $map = [
            'panel de control' => 'dashboard', 'punto de venta' => 'pos', 'métodos de pago' => 'metodos_pago',
            'metodos de pago' => 'metodos_pago', 'crear producto' => 'crear_producto', 'crear cupón' => 'crear_cupon',
            'crear cupon' => 'crear_cupon', 'configuración' => 'ajustes', 'configuracion' => 'ajustes',
            'dashboard' => 'dashboard', 'inicio' => 'dashboard', 'panel' => 'dashboard',
            'gastos' => 'gastos', 'productos' => 'productos', 'categorías' => 'categorias', 'categorias' => 'categorias',
            'marcas' => 'marcas', 'pedidos' => 'pedidos', 'clientes' => 'clientes', 'trabajadores' => 'trabajadores',
            'proveedores' => 'proveedores', 'cupones' => 'cupones', 'pos' => 'pos',
            'ventas' => 'pos', 'caja' => 'pos', 'compras' => 'compras', 'inventario' => 'inventario',
            'almacenes' => 'almacenes', 'zonas' => 'zonas', 'roles' => 'roles', 'permisos' => 'permisos',
            'ajustes' => 'ajustes', 'banners' => 'banners', 'tienda' => 'tienda', 'notificaciones' => 'notificaciones',
        ];

        // Check multi-word targets first (longer matches)
        foreach ($map as $keyword => $section) {
            if (str_contains($t, $keyword)) {
                return $section;
            }
        }

        return 'dashboard';
    }

    private function extractPeriod(string $t): string
    {
        if (str_contains($t, 'hoy')) return 'hoy';
        if (str_contains($t, 'semana')) return 'semana';
        if (preg_match('/a[ñn]o/u', $t)) return 'año';
        return 'mes';
    }

    private function extractOrderStatus(string $t): string
    {
        if (str_contains($t, 'pendiente')) return 'pendiente';
        if (str_contains($t, 'procesando')) return 'procesando';
        if (str_contains($t, 'enviado')) return 'enviado';
        if (str_contains($t, 'entregado')) return 'entregado';
        if (str_contains($t, 'cancelado')) return 'cancelado';
        if (str_contains($t, 'todos') || str_contains($t, 'todas')) return 'todos';
        return 'pendiente';
    }

    private function normalizeEntity(string $word): string
    {
        $map = [
            'producto' => 'productos', 'productos' => 'productos',
            'pedido' => 'pedidos', 'pedidos' => 'pedidos',
            'cupón' => 'cupones', 'cupon' => 'cupones', 'cupones' => 'cupones',
            'cliente' => 'clientes', 'clientes' => 'clientes',
            'proveedor' => 'proveedores', 'proveedores' => 'proveedores',
            'almacén' => 'almacenes', 'almacen' => 'almacenes', 'almacenes' => 'almacenes',
            'categoría' => 'categorias', 'categoria' => 'categorias', 'categorias' => 'categorias',
            'marca' => 'marcas', 'marcas' => 'marcas',
            'gasto' => 'gastos', 'gastos' => 'gastos',
            'variante' => 'variantes', 'variantes' => 'variantes',
            'trabajador' => 'trabajadores', 'trabajadores' => 'trabajadores',
        ];

        return $map[$word] ?? $word;
    }

    // ════════════════════════════════════════════════════════════════
    // SKILL EXECUTION — Ejecutar herramientas por intent
    // ════════════════════════════════════════════════════════════════

    private function executeTool(string $intent, array $slots): array
    {
        return match ($intent) {
            'navigate'             => $this->toolNavigate($slots),
            'query_count'          => $this->toolQueryCount($slots),
            'query_sales'          => $this->toolQuerySales($slots),
            'query_stock_critical' => $this->toolQueryStock(['type' => 'critico']),
            'query_stock_product'  => $this->toolQueryStock(['type' => 'producto', 'product_name' => $slots['product_name'] ?? '']),
            'query_orders'         => $this->toolQueryOrders($slots),
            'query_top'            => $this->toolQueryTop($slots),
            'query_expenses'       => $this->toolQueryExpenses($slots),
            'search_product'       => $this->toolSearchProduct($slots),
            'update_coupon'        => $this->toolUpdateCouponDiscount($slots),
            'system_status'        => $this->toolSystemStatus(),
            'time'                 => $this->response(now()->format('g:i A') . '. ' . $this->getTimeGreeting(), null, 'time'),
            'date'                 => $this->response('Hoy es ' . now()->translatedFormat('l j \d\e F \d\e\l Y') . '.', null, 'date'),
            'action_reload'        => $this->response('Recargando la página.', null, 'action_reload', ['type' => 'reload']),
            'action_logout'        => $this->response('Cerrando sesión. Hasta pronto, Señor.', null, 'action_logout', ['type' => 'logout']),
            'action_back'          => $this->response('Volviendo atrás.', null, 'action_back', ['type' => 'back']),
            'greeting'             => $this->toolGreeting(),
            'identity'             => $this->response('Soy Jarvis, su asistente de inteligencia artificial, diseñado exclusivamente para el panel de control de Novape. Estoy aquí para ayudarlo a gestionar su negocio con comandos de voz.', null, 'identity'),
            'how_are_you'          => $this->response('Todos los sistemas operativos, Señor. Funcionando al cien por cien. ¿En qué le ayudo?', null, 'how_are_you'),
            'farewell'             => $this->response('Hasta luego, Señor. Estaré aquí cuando me necesite.', null, 'farewell'),
            'thanks'               => $this->response('Con gusto, Señor. Para eso estoy. ¿Algo más?', null, 'thanks'),
            'help'                 => $this->toolHelp(),
            'unknown'              => $this->toolUnknown(),
            default                => $this->response('No entendí ese comando. Diga "ayuda" para ver lo que puedo hacer.', null, 'unknown'),
        };
    }

    private function getTimeGreeting(): string
    {
        $hour = (int)now()->format('G');
        if ($hour < 12) return 'Buenos días, Señor.';
        if ($hour < 19) return 'Buenas tardes, Señor.';
        return 'Buenas noches, Señor.';
    }

    // ════════════════════════════════════════════════════════════════
    // SKILLS (Tools) — Cada una devuelve ['voice' => ..., 'ui' => ...]
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
            'permisos'       => ['/admin/ajustes/permisos',   'Abriendo permisos del sistema.'],
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
            "No encontré la sección '{$section}'. Dígame a dónde quiere ir.",
            null,
            'navigate_unknown'
        );
    }

    private function toolQueryCount(array $slots): array
    {
        $entity = $slots['entity'] ?? 'productos';

        $counts = [
            'productos'    => fn() => ['total' => Producto::count(), 'extra' => Producto::where('activo', true)->count() . ' activos'],
            'pedidos'      => fn() => ['total' => Pedido::count(), 'extra' => Pedido::where('estado', 'pendiente')->count() . ' pendientes'],
            'cupones'      => fn() => ['total' => Cupon::count(), 'extra' => Cupon::where('activo', true)->count() . ' activos'],
            'clientes'     => fn() => ['total' => User::count(), 'extra' => null],
            'proveedores'  => fn() => ['total' => Proveedor::count(), 'extra' => null],
            'almacenes'    => fn() => ['total' => Almacen::count(), 'extra' => Almacen::where('activo', true)->count() . ' activos'],
            'categorias'   => fn() => ['total' => Categoria::count(), 'extra' => null],
            'marcas'       => fn() => ['total' => Marca::count(), 'extra' => null],
            'gastos'       => fn() => ['total' => Gasto::count(), 'extra' => 'S/ ' . number_format(Gasto::sum('monto'), 2) . ' total'],
            'variantes'    => fn() => ['total' => Variante::whereNull('deleted_at')->count(), 'extra' => Variante::where('stock', '<', 5)->whereNull('deleted_at')->count() . ' con stock bajo'],
            'trabajadores' => fn() => ['total' => User::count(), 'extra' => null],
        ];

        $entityLabel = str_replace('_', ' ', $entity);

        if (!isset($counts[$entity])) {
            return $this->response("No tengo información sobre '{$entityLabel}'.", null, 'count_unknown');
        }

        try {
            $data = $counts[$entity]();
            $voice = "Hay {$data['total']} {$entityLabel} registrados" . ($data['extra'] ? ", de los cuales {$data['extra']}." : ".");

            return $this->response($voice, [
                'type'     => 'card',
                'title'    => ucfirst($entityLabel),
                'value'    => $data['total'],
                'subtitle' => $data['extra'],
            ], 'count_' . $entity);
        } catch (\Exception $e) {
            Log::error('Jarvis toolQueryCount error', ['message' => $e->getMessage()]);
            return $this->response("Error al consultar {$entityLabel}.", null, 'count_error');
        }
    }

    private function toolQuerySales(array $slots): array
    {
        $period = $slots['period'] ?? 'hoy';

        try {
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
        } catch (\Exception $e) {
            Log::error('Jarvis toolQuerySales error', ['message' => $e->getMessage()]);
            return $this->response("Error al consultar ventas.", null, 'sales_error');
        }
    }

    private function toolQueryStock(array $slots): array
    {
        $type = $slots['type'] ?? 'critico';

        try {
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
        } catch (\Exception $e) {
            Log::error('Jarvis toolQueryStock error', ['message' => $e->getMessage()]);
            return $this->response("Error al consultar stock.", null, 'stock_error');
        }
    }

    private function toolQueryOrders(array $slots): array
    {
        $status = $slots['status'] ?? 'pendiente';

        try {
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
                "Tiene {$count} pedidos en estado '{$status}'.",
                ['type' => 'card', 'title' => "Pedidos " . ucfirst($status), 'value' => $count, 'color' => $color],
                'orders_' . $status
            );
        } catch (\Exception $e) {
            Log::error('Jarvis toolQueryOrders error', ['message' => $e->getMessage()]);
            return $this->response("Error al consultar pedidos.", null, 'orders_error');
        }
    }

    private function toolQueryTop(array $slots): array
    {
        $limit = min($slots['limit'] ?? 5, 10);

        try {
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
        } catch (\Exception $e) {
            Log::error('Jarvis toolQueryTop error', ['message' => $e->getMessage()]);
            return $this->response("Error al consultar productos más vendidos.", null, 'top_error');
        }
    }

    private function toolSearchProduct(array $slots): array
    {
        $query = $slots['query'] ?? '';
        if (strlen($query) < 2) {
            return $this->response("Dígame el nombre del producto que busca.", null, 'search_empty');
        }

        try {
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
        } catch (\Exception $e) {
            Log::error('Jarvis toolSearchProduct error', ['message' => $e->getMessage()]);
            return $this->response("Error al buscar productos.", null, 'search_error');
        }
    }

    private function toolQueryExpenses(array $slots): array
    {
        $period = $slots['period'] ?? 'mes';

        try {
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
        } catch (\Exception $e) {
            Log::error('Jarvis toolQueryExpenses error', ['message' => $e->getMessage()]);
            return $this->response("Error al consultar gastos.", null, 'expenses_error');
        }
    }

    private function toolUpdateCouponDiscount(array $slots): array
    {
        $codigo = $slots['codigo'] ?? '';
        $porcentaje = $slots['porcentaje'] ?? 0;

        if (!$codigo) {
            return $this->response("Necesito el código del cupón. Diga por ejemplo: cambia el cupón SUMMER al 15 porciento.", null, 'coupon_missing_code');
        }
        if (!$porcentaje) {
            return $this->response("Necesito el porcentaje de descuento. Diga por ejemplo: cupón {$codigo} al 20 porciento.", null, 'coupon_missing_percent');
        }

        try {
            $cupon = Cupon::where('codigo', $codigo)->first();
            if (!$cupon) {
                return $this->response("No encontré ningún cupón con el código '{$codigo}'.", null, 'coupon_not_found');
            }

            $viejo = $cupon->porcentaje;
            $cupon->porcentaje = $porcentaje;
            $cupon->save();

            return $this->response(
                "Listo. El cupón {$codigo} ha sido actualizado del {$viejo}% al {$porcentaje}%.",
                ['type' => 'card', 'title' => "Cupón {$codigo}", 'value' => "{$porcentaje}%", 'subtitle' => "Anterior: {$viejo}%"],
                'coupon_updated'
            );
        } catch (\Exception $e) {
            Log::error('Jarvis toolUpdateCouponDiscount error', ['message' => $e->getMessage()]);
            return $this->response("Error al actualizar el cupón.", null, 'coupon_error');
        }
    }

    private function toolSystemStatus(): array
    {
        try {
            $dbOk = true;
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                $dbOk = false;
            }

            $productCount = Producto::count();
            $ordersToday = Pedido::whereDate('created_at', now()->toDateString())->count();
            $criticalStock = Variante::where('stock', '<', 5)->whereNull('deleted_at')->count();

            $statusText = $dbOk
                ? "Sistema operativo. Base de datos conectada. {$productCount} productos, {$ordersToday} pedidos hoy, {$criticalStock} con stock crítico."
                : "Advertencia: la base de datos no está respondiendo.";

            return $this->response(
                $statusText,
                [
                    'type'  => 'status',
                    'items' => [
                        ['label' => 'Base de datos', 'status' => $dbOk ? 'ok' : 'error'],
                        ['label' => 'Motor NLU', 'status' => 'ok'],
                        ['label' => 'Productos', 'value' => $productCount],
                        ['label' => 'Pedidos hoy', 'value' => $ordersToday],
                        ['label' => 'Stock crítico', 'value' => $criticalStock],
                    ]
                ],
                'system_status'
            );
        } catch (\Exception $e) {
            Log::error('Jarvis toolSystemStatus error', ['message' => $e->getMessage()]);
            return $this->response("Error al consultar el estado del sistema.", null, 'status_error');
        }
    }

    private function toolGreeting(): array
    {
        $hour = (int)now()->format('G');
        if ($hour < 12) {
            $greeting = 'Buenos días, Señor.';
        } elseif ($hour < 19) {
            $greeting = 'Buenas tardes, Señor.';
        } else {
            $greeting = 'Buenas noches, Señor.';
        }

        return $this->response(
            "{$greeting} Todos los sistemas están en línea. ¿En qué puedo ayudarle?",
            null,
            'greeting'
        );
    }

    private function toolHelp(): array
    {
        return $this->response(
            "Puedo navegar a cualquier sección, consultar ventas, stock, pedidos, gastos, cupones, buscar productos, ver diagnósticos del sistema, o responder preguntas. Dígame qué necesita.",
            [
                'type'  => 'list',
                'title' => '🧠 Capacidades de Jarvis',
                'items' => [
                    ['title' => '🗺️ Navegación', 'subtitle' => '"Abre productos", "llévame a gastos", "ve al POS"'],
                    ['title' => '📊 Consultas', 'subtitle' => '"Ventas de hoy", "cuántos productos hay", "gastos del mes"'],
                    ['title' => '📦 Inventario', 'subtitle' => '"Stock crítico", "busca vape de fresa"'],
                    ['title' => '📋 Pedidos', 'subtitle' => '"Pedidos pendientes", "top productos vendidos"'],
                    ['title' => '🎟️ Cupones', 'subtitle' => '"Cambia el cupón SUMMER al 15%"'],
                    ['title' => '🔧 Sistema', 'subtitle' => '"Estado del sistema", "qué hora es"'],
                    ['title' => '💬 Conversación', 'subtitle' => '"Hola Jarvis", "ayuda", "quién eres"'],
                ],
            ],
            'help'
        );
    }

    private function toolUnknown(): array
    {
        return $this->response(
            "No entendí ese comando, Señor. Puede decirme cosas como: abre productos, ventas de hoy, stock crítico, o pedidos pendientes. Diga 'ayuda' para ver todo lo que puedo hacer.",
            null,
            'unknown'
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
