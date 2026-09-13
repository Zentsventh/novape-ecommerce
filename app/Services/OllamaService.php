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
     * Generate a response from the local Ollama LLM.
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
