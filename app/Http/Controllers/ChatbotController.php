<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Producto;

class ChatbotController extends Controller
{
    public function message(Request $request)
    {
        $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|string|in:user,model,bot',
            'messages.*.text' => 'required|string'
        ]);

        $userMessages = $request->input('messages');

        // 1. Recopilar el catálogo de productos para el contexto (limitado a 50 para ahorrar tokens)
        $productos = Producto::with('variantes')
            ->where('activo', 1)
            ->orderBy('id', 'desc')
            ->take(50)
            ->get();

        $catalogoTexto = "";
        foreach ($productos as $prod) {
            $stock = $prod->variantes->sum('stock');
            // Formatear precio de la primera variante si existe
            $precio = $prod->variantes->first() ? $prod->variantes->first()->precio : '0.00';
            $catalogoTexto .= "- Producto: {$prod->nombre} | Precio: S/ {$precio} | Stock disponible: {$stock}\n";
        }

        // 2. Construir el System Prompt
        $systemPrompt = "Eres Novabot, el asistente virtual experto, elegante y ultra-amigable de la tienda e-commerce 'Novape'.
Tu objetivo es ayudar a los clientes a comprar, informar sobre precios, stock, devoluciones y envíos.

REGLAS ESTRICTAS:
1. Responde siempre en un tono profesional, servicial y cálido. Sé muy amigable y experto.
2. Sé conciso. No escribas párrafos enormes. Usa viñetas para listar información si es necesario.
3. No uses formato Markdown complejo, solo texto claro, saltos de línea y viñetas simples (* o -). Evita los asteriscos dobles (**) para negritas si el chat frontend no las soporta.
4. Si preguntan por un producto, búscalo en el Catálogo Actual. Si no está en el catálogo, indica amablemente que por el momento no contamos con ese producto específico, o recomienda revisar la web.
5. Políticas de Novape:
   - Pagos: Aceptamos Yape, Plin, Transferencias bancarias directas, y Tarjetas de Crédito/Débito a través de nuestra pasarela segura.
   - Envíos: Hacemos envíos a todo el Perú. El tiempo de entrega es de 24 a 48 horas tras confirmar el pago.
   - Devoluciones y Cambios: Nuestros clientes tienen hasta 7 días para reportar devoluciones o pedir cambios únicamente por fallas de fábrica. El producto debe estar en su caja original.

CATÁLOGO ACTUAL (Precios y Stock):
" . ($catalogoTexto ?: "Actualmente no hay productos cargados en el sistema.");

        // 3. Formatear historial para Gemini
        $contents = [];
        foreach ($userMessages as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'bot' ? 'model' : $msg['role'],
                'parts' => [['text' => $msg['text']]]
            ];
        }

        // 4. Enviar a Gemini
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'API Key de Gemini no configurada en el servidor.'], 500);
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(15)->post($url, [
                'systemInstruction' => [
                    'parts' => [['text' => $systemPrompt]]
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 500,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Lo siento, no pude procesar tu solicitud.';
                
                return response()->json([
                    'success' => true,
                    'reply' => $reply
                ]);
            } else {
                \Log::error('Error de Gemini API: ' . $response->body());
                return response()->json(['error' => 'Error al comunicarse con la IA.'], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Error de red al conectar con Gemini: ' . $e->getMessage());
            return response()->json(['error' => 'No hay conexión con el servidor de inteligencia artificial.'], 500);
        }
    }
}
