<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OllamaService
{
    protected string $endpoint;
    protected string $model;

    public function __construct()
    {
        $this->endpoint = config('ollama.endpoint', 'http://127.0.0.1:11434');
        $this->model    = config('ollama.model', 'llama3.2');
    }

    /**
     * Generate a response from the local Ollama LLM (single-shot).
     *
     * @param string $prompt  The user's message / prompt.
     * @param string $systemPrompt  Optional system-level instructions.
     * @param int    $timeout  HTTP timeout in seconds.
     * @return string|null  The generated text, or null on failure.
     */
    public function generate(string $prompt, string $systemPrompt = '', int $timeout = 60): ?string
    {
        try {
            $payload = [
                'model'      => $this->model,
                'prompt'     => $prompt,
                'stream'     => false,
                'keep_alive' => '1h', // Keep model in RAM for 1 hour for fast responses
                'options'    => [
                    'num_predict' => 200, // Short responses are faster
                    'temperature' => 0.5,
                ]
            ];

            if ($systemPrompt !== '') {
                $payload['system'] = $systemPrompt;
            }

            $response = Http::timeout($timeout)
                ->post("{$this->endpoint}/api/generate", $payload);

            if ($response->successful()) {
                return $response->json('response');
            }

            Log::error('OllamaService: request failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('OllamaService: exception', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Chat with message history — estilo Alexa multi-turn.
     *
     * @param array $messages  Array of {role, content} objects.
     * @param int   $timeout   HTTP timeout in seconds.
     * @return string|null
     */
    public function chat(array $messages, int $timeout = 20): ?string
    {
        try {
            $payload = [
                'model'      => $this->model,
                'messages'   => $messages,
                'stream'     => false,
                'keep_alive' => '1h',
                'options'    => [
                    'num_predict' => 300,
                    'temperature' => 0.3,
                ]
            ];

            $response = Http::timeout($timeout)
                ->post("{$this->endpoint}/api/chat", $payload);

            if ($response->successful()) {
                $content = $response->json('message.content');
                return $content;
            }

            Log::error('OllamaService::chat failed', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);
        } catch (\Exception $e) {
            Log::error('OllamaService::chat exception', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Generar respuesta JSON estructurada — el cerebro del Agente Hermes.
     * Fuerza al LLM a responder con un JSON válido.
     *
     * @param string $systemPrompt  Instrucciones del sistema + definiciones de tools.
     * @param string $userMessage   Mensaje del usuario.
     * @param int    $timeout       HTTP timeout.
     * @return array|null  Parsed JSON array or null on failure.
     */
    public function generateJSON(string $systemPrompt, string $userMessage, int $timeout = 15): ?array
    {
        try {
            $payload = [
                'model'      => $this->model,
                'messages'   => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userMessage],
                ],
                'stream'     => false,
                'format'     => 'json', // Force JSON output
                'keep_alive' => '1h',
                'options'    => [
                    'num_predict' => 100, // Reduced to speed up JSON generation
                    'temperature' => 0.0, // Fully deterministic
                ]
            ];

            $response = Http::timeout($timeout)
                ->post("{$this->endpoint}/api/chat", $payload);

            if ($response->successful()) {
                $content = $response->json('message.content');
                $parsed  = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                    return $parsed;
                }

                Log::warning('OllamaService::generateJSON invalid JSON', [
                    'raw' => substr($content ?? '', 0, 500),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('OllamaService::generateJSON exception', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Check if the Ollama server is reachable.
     */
    public function isAvailable(): bool
    {
        return Cache::remember('ollama_available', 30, function () {
            try {
                $response = Http::timeout(5)->get("{$this->endpoint}/api/tags");
                return $response->successful();
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    /**
     * List available models on the local Ollama server.
     */
    public function listModels(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->endpoint}/api/tags");
            if ($response->successful()) {
                return $response->json('models', []);
            }
        } catch (\Exception $e) {
            Log::error('OllamaService: could not list models', ['message' => $e->getMessage()]);
        }
        return [];
    }
}
