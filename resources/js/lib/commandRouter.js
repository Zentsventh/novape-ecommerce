/**
 * Command Router para Jarvis — Ejecuta intenciones 100% local.
 * Maneja navegación, acciones del sistema y respuestas de voz.
 */
import { router } from '@inertiajs/react';
import jarvisVoice from './jarvisVoice';

/**
 * Ejecuta la intención recibida.
 * @param {{ type: string, target?: string, voice?: string }} intent
 * @returns {boolean} true si fue manejada.
 */
export function executeIntent(intent) {
  if (!intent || intent.type === 'ignore') return false;

  // ── Navegación ──────────────────────────────────────────────
  if (intent.type === 'navigation' && intent.target) {
    jarvisVoice.speak(intent.voice || 'Navegando.', () => {
      router.visit(intent.target);
    });
    return true;
  }

  // ── Solo hablar (conversación, hora, fecha, ayuda, error) ───
  if (intent.type === 'speak' && intent.voice) {
    jarvisVoice.speak(intent.voice);
    return true;
  }

  // ── Acciones del sistema ────────────────────────────────────
  if (intent.type === 'action') {
    if (intent.target === 'reload') {
      jarvisVoice.speak(intent.voice || 'Recargando.', () => {
        window.location.reload();
      });
      return true;
    }

    if (intent.target === 'logout') {
      jarvisVoice.speak(intent.voice || 'Cerrando sesión.', () => {
        const form = document.getElementById('logout-form');
        if (form) {
          form.submit();
        } else {
          router.visit('/admin/logout', { method: 'post' });
        }
      });
      return true;
    }

    if (intent.target === 'back') {
      jarvisVoice.speak(intent.voice || 'Volviendo.', () => {
        window.history.back();
      });
      return true;
    }
  }

  return false;
}
