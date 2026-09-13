<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\JarvisAuditLog;
use App\Services\OllamaService;

class JarvisPublicController extends Controller
{
    protected OllamaService $ollama;

    public function __construct(OllamaService $ollama)
    {
        $this->ollama = $ollama;
    }

    /**
     * Handle a public Jarvis message (chatbot / voice from the storefront).
     */
    public function handle(Request $request)
    {
        $start = microtime(true);
        $msg   = $request->input('message', '');
        $intent = mb_strtolower(trim($msg));

        $response = ['voice' => '', 'ui' => null, 'action' => null];

        // ── Recommendations ──────────────────────────────────────
        if (preg_match('/recomienda|sugerencia|sugiere|que me recomiendas|productos populares/', $intent)) {
            $products = Producto::where('activo', true)
                ->with(['variantes', 'imagenes'])
                ->inRandomOrder()
                ->take(3)
                ->get();

            $items = $products->map(function ($p) {
                $precio = $p->variantes->first()?->precio ?? 0;
                $imagen = $p->imagenes->first()?->url ?? null;
                return [
                    'id'       => $p->id,
                    'title'    => $p->nombre,
                    'subtitle' => "S/ " . number_format($precio, 2),
                    'image'    => $imagen,
                    'action'   => ['type' => 'view_product', 'product_id' => $p->id, 'slug' => $p->slug],
                ];
            })->toArray();

            $names = $products->pluck('nombre')->join(', ', ' y ');
            $response['voice']  = "Te recomiendo estos productos: {$names}.";
            $response['ui']     = ['type' => 'product_list', 'items' => $items];
            $response['action'] = 'recommend_products';
        }
        // ── Add to cart ──────────────────────────────────────────
        elseif (preg_match('/agrega (.+?) al carrito|añade (.+?) al carrito|pon (.+?) en el carrito/i', $intent, $m)) {
            $search  = $m[1] ?? $m[2] ?? $m[3] ?? '';
            $product = Producto::where('activo', true)
                ->where('nombre', 'like', "%{$search}%")
                ->with('variantes')
                ->first();

            if ($product) {
                $precio = $product->variantes->first()?->precio ?? 0;
                $response['voice']  = "He añadido {$product->nombre} al carrito por S/ " . number_format($precio, 2) . ".";
                $response['ui']     = [
                    'type'    => 'toast',
                    'message' => "Añadido: {$product->nombre}",
                    'action'  => ['type' => 'add_to_cart', 'product_id' => $product->id, 'variant_id' => $product->variantes->first()?->id],
                ];
                $response['action'] = 'add_to_cart';
            } else {
                $response['voice'] = "No encontré un producto que coincida con \"{$search}\". ¿Podrías ser más específico?";
                $response['action'] = 'product_not_found';
            }
        }
        // ── Search product ───────────────────────────────────────
        elseif (preg_match('/busca|buscar|encuentra|tiene[ns]? (.+)/i', $intent, $m)) {
            $search = $m[1] ?? $msg;
            $products = Producto::where('activo', true)
                ->where('nombre', 'like', "%{$search}%")
                ->with('variantes')
                ->take(5)
                ->get();

            if ($products->isNotEmpty()) {
                $items = $products->map(function ($p) {
                    $precio = $p->variantes->first()?->precio ?? 0;
                    return [
                        'id'       => $p->id,
                        'title'    => $p->nombre,
                        'subtitle' => "S/ " . number_format($precio, 2),
                        'action'   => ['type' => 'view_product', 'product_id' => $p->id, 'slug' => $p->slug],
                    ];
                })->toArray();
                $response['voice']  = "Encontré " . count($items) . " productos relacionados con \"{$search}\".";
                $response['ui']     = ['type' => 'product_list', 'items' => $items];
                $response['action'] = 'search_products';
            } else {
                $response['voice']  = "No encontré productos que coincidan con \"{$search}\".";
                $response['action'] = 'search_no_results';
            }
        }
        // ── Shipping / policies ──────────────────────────────────
        elseif (preg_match('/envio|envío|costo de envio|cuanto cuesta el envio|delivery/', $intent)) {
            $response['voice']  = "Realizamos envíos a todo el Perú. El costo depende de tu ubicación. En Lima Metropolitana el envío es desde S/ 10. Para provincias, el costo se calcula al ingresar tu dirección.";
            $response['action'] = 'shipping_info';
        }
        elseif (preg_match('/devolucion|devolución|cambio|garantia|garantía/', $intent)) {
            $response['voice']  = "Ofrecemos devoluciones y cambios dentro de los 7 días hábiles posteriores a la entrega. El producto debe estar en su empaque original y sin uso. Contáctanos para gestionar tu caso.";
            $response['action'] = 'return_policy';
        }
        elseif (preg_match('/horario|atencion|atención|contacto|telefono|teléfono|whatsapp/', $intent)) {
            $response['voice']  = "Nuestro horario de atención es de lunes a sábado, de 9 AM a 6 PM. Puedes contactarnos por WhatsApp o por el chat de la tienda.";
            $response['action'] = 'contact_info';
        }
        elseif (preg_match('/hola|buenos dias|buenas tardes|buenas noches|hey|saludos/i', $intent)) {
            $response['voice']  = "¡Hola! Soy JARVIS, tu asistente virtual de Novape. ¿En qué puedo ayudarte hoy? Puedo recomendarte productos, buscar artículos, o responder tus dudas sobre envíos y devoluciones.";
            $response['action'] = 'greeting';
        }
        elseif (preg_match('/como te llamas|cómo te llamas|quien eres|quién eres|tu nombre/i', $intent)) {
            $response['voice']  = "Soy Jarvis, un asistente de inteligencia artificial creado para ayudarte a encontrar lo que buscas en Novape. ¿Qué necesitas comprar?";
            $response['action'] = 'identity';
        }
        // ── Fallback: ask the LLM ────────────────────────────────
        else {
            $systemPrompt = "Eres JARVIS, el asistente virtual de Novape, una tienda e-commerce peruana. "
                . "Ayudas a los clientes a encontrar productos, resolver dudas sobre envíos, pagos y devoluciones. "
                . "Responde siempre en español, de forma amable y profesional. "
                . "Si no sabes la respuesta, sugiere que el cliente se comunique con soporte.";

            $ai = $this->ollama->generate($msg, $systemPrompt, 120);
            $response['voice']  = $ai ?? "Disculpa, no pude procesar tu consulta en este momento. Te recomiendo contactar a nuestro equipo de soporte.";
            $response['action'] = 'llm_fallback';
        }

        // ── Audit log ────────────────────────────────────────────
        $elapsed = (int) ((microtime(true) - $start) * 1000);

        JarvisAuditLog::create([
            'user_id'          => $request->user()?->id,
            'role'             => 'public',
            'intent'           => $msg,
            'payload'          => $response,
            'action_taken'     => $response['action'] ?? null,
            'response_time_ms' => $elapsed,
        ]);

        return response()->json($response);
    }
}
