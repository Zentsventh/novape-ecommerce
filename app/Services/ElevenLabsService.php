<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElevenLabsService
{
    private string $apiKey;
    private string $voiceId;

    public function __construct()
    {
        $this->apiKey = env('ELEVENLABS_API_KEY', '');
        // Voice ID de Jarvis o voz default (pNInz6obbfDQGcgMyIGC es Adam, buena voz profunda por defecto)
        $this->voiceId = env('ELEVENLABS_VOICE_ID', 'pNInz6obbfDQGcgMyIGC');
    }

    /**
     * Convierte texto a audio neural y devuelve el string base64 del MP3.
     */
    public function textToSpeechBase64(string $text): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('ElevenLabsService: API Key is empty.');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'audio/mpeg'
            ])->withoutVerifying()->timeout(15)->post("https://api.elevenlabs.io/v1/text-to-speech/{$this->voiceId}?optimize_streaming_latency=2", [
                'text' => $text,
                'model_id' => 'eleven_multilingual_v2',
                'voice_settings' => [
                    'stability' => 0.5,
                    'similarity_boost' => 0.75,
                    'style' => 0.0,
                    'use_speaker_boost' => true
                ]
            ]);

            if ($response->successful()) {
                // Devolver el binario de audio en base64
                return base64_encode($response->body());
            } else {
                Log::error('ElevenLabsService API Error', [
                    'status' => $response->status(),
                    'body' => $response->json()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ElevenLabsService Exception', [
                'message' => $e->getMessage()
            ]);
        }

        return null;
    }
}
