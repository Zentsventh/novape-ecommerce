<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * JarvisSessionManager — Memoria conversacional estilo Alexa/Hermes.
 *
 * Mantiene los últimos N mensajes por usuario en cache para que el LLM
 * tenga contexto multi-turn (ej: "cuántos hay" después de hablar de cupones).
 */
class JarvisSessionManager
{
    private const MAX_MESSAGES = 10;
    private const TTL_MINUTES  = 30;

    /**
     * Generar la cache key para un usuario.
     */
    private function key(?int $userId): string
    {
        return 'jarvis_session_' . ($userId ?? 'guest');
    }

    /**
     * Obtener el historial de mensajes de la sesión.
     *
     * @return array<array{role: string, content: string}>
     */
    public function getHistory(?int $userId): array
    {
        return Cache::get($this->key($userId), []);
    }

    /**
     * Agregar un mensaje al historial.
     *
     * @param string $role   'user' | 'assistant'
     * @param string $content El texto del mensaje
     */
    public function push(?int $userId, string $role, string $content): void
    {
        $history = $this->getHistory($userId);

        $history[] = [
            'role'    => $role,
            'content' => $content,
            'time'    => now()->toIso8601String(),
        ];

        // Mantener solo los últimos N mensajes
        if (count($history) > self::MAX_MESSAGES) {
            $history = array_slice($history, -self::MAX_MESSAGES);
        }

        Cache::put($this->key($userId), $history, now()->addMinutes(self::TTL_MINUTES));
    }

    /**
     * Formatear historial para el system prompt del LLM.
     */
    public function formatForLLM(?int $userId): string
    {
        $history = $this->getHistory($userId);
        if (empty($history)) {
            return '';
        }

        $lines = ["--- Historial reciente de conversación ---"];
        foreach ($history as $msg) {
            $prefix = $msg['role'] === 'user' ? 'USUARIO' : 'JARVIS';
            $lines[] = "{$prefix}: {$msg['content']}";
        }
        $lines[] = "--- Fin del historial ---";

        return implode("\n", $lines);
    }

    /**
     * Limpiar la sesión.
     */
    public function clear(?int $userId): void
    {
        Cache::forget($this->key($userId));
    }
}
