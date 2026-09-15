<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeminiService — Integración ultrarrápida con Google Gemini API.
 * Reemplaza a Ollama para lograr respuestas de voz en tiempo real.
 */
class GeminiService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct()
    {
        // Se recomienda mover esto a .env en el futuro (GEMINI_API_KEY)
        $this->apiKey = env('GEMINI_API_KEY', '');
        // gemini-2.5-flash es el modelo más rápido y optimizado para tool calling / respuestas cortas
        $this->model  = 'gemini-2.5-flash'; 
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}";
    }

    /**
     * Generar una respuesta simple de texto.
     */
    public function generate(string $prompt, string $systemPrompt = '', int $timeout = 10): ?string
    {
        try {
            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $prompt]]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 150, // Respuestas cortas para voz
                ]
            ];

            if ($systemPrompt !== '') {
                $payload['systemInstruction'] = [
                    'parts' => [['text' => $systemPrompt]]
                ];
            }

            $response = Http::timeout($timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}:generateContent?key={$this->apiKey}", $payload);

            if ($response->successful()) {
                return $response->json('candidates.0.content.parts.0.text');
            }

            Log::error('GeminiService: request failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('GeminiService: exception', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Generar respuesta estructurada JSON usando la capacidad nativa de Gemini.
     */
    public function generateJSON(string $systemPrompt, string $userMessage, int $timeout = 10): ?array
    {
        try {
            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $userMessage]]
                    ]
                ],
                'systemInstruction' => [
                    'parts' => [['text' => $systemPrompt]]
                ],
                'generationConfig' => [
                    'temperature' => 0.0, // Totalmente determinista para NLU
                    'responseMimeType' => 'application/json', // Nativo en Gemini 1.5
                ]
            ];

            $response = Http::timeout($timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}:generateContent?key={$this->apiKey}", $payload);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                
                // Limpiar posibles bloques de markdown en el JSON
                $content = preg_replace('/```json\s*/', '', $content);
                $content = preg_replace('/```/', '', $content);
                
                $parsed = json_decode(trim($content), true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                    return $parsed;
                }

                Log::warning('GeminiService::generateJSON invalid JSON', [
                    'raw' => substr($content ?? '', 0, 500),
                ]);
            } else {
                 Log::error('GeminiService JSON request failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('GeminiService::generateJSON exception', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }
    
    /**
     * Comunicación agéntica con Function Calling.
     * $history es un array con el formato nativo de Gemini:
     * [['role' => 'user'|'model', 'parts' => [...]]]
     */
    public function chatWithTools(array $history, array $tools, string $systemPrompt = '', int $timeout = 30): ?array
    {
        try {
            $payload = [
                'contents' => $history,
                'tools' => [
                    ['functionDeclarations' => $tools]
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                ]
            ];

            if ($systemPrompt !== '') {
                $payload['systemInstruction'] = [
                    'parts' => [['text' => $systemPrompt]]
                ];
            }

            $response = Http::timeout($timeout)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}:generateContent?key={$this->apiKey}", $payload);

            if ($response->successful()) {
                return $response->json('candidates.0.content');
            }

            Log::error('GeminiService::chatWithTools failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('GeminiService::chatWithTools exception', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Verificar que el API key funciona y hay conexión.
     */
    public function isAvailable(): bool
    {
        // Una petición simple para comprobar la conexión
        $response = Http::timeout(5)->get("{$this->baseUrl}?key={$this->apiKey}");
        return $response->successful();
    }
}
