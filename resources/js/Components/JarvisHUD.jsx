import { useState, useEffect, useRef } from 'react';
import jarvisVoice from '../lib/jarvisVoice';
import { parseIntent } from '../lib/intentParser';
import { executeIntent } from '../lib/commandRouter';
import { router } from '@inertiajs/react';
import '../../css/jarvis.css';

export default function JarvisHUD() {
  const [status, setStatus] = useState('idle');
  const mountedRef = useRef(true);

  // ─── Cleanup on unmount ──────────────────────────────────────
  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      jarvisVoice.stopListening();
    };
  }, []);

  // ─── Voice Engine: start continuous listening ─────────────────
  useEffect(() => {
    jarvisVoice.startContinuous(
      // onInterim — solo actualizar el estado visual
      (text) => {
        if (mountedRef.current && status !== 'processing' && status !== 'speaking') {
          setStatus('listening');
        }
      },
      // onFinal — parsear y ejecutar comando
      (text) => {
        if (!mountedRef.current) return;

        const intent = parseIntent(text);

        // Si es ignore (sin wake word), no hacer nada
        if (intent.type === 'ignore') return;

        // Marcar como procesando
        setStatus('processing');

        // Ejecutar el intent
        const handled = executeIntent(intent);

        // Si no se manejó (no debería pasar), volver a idle
        if (!handled && mountedRef.current) {
          setStatus('idle');
        }
      },
      // onError
      (err) => {
        if (mountedRef.current && status === 'listening') {
          setStatus('idle');
        }
      }
    );

    // Saludo inicial
    setTimeout(() => {
      jarvisVoice.speak('Jarvis en línea. Di Jarvis seguido de un comando.', () => {
        if (mountedRef.current) setStatus('idle');
      });
    }, 1500);

    // Cleanup: no re-ejecutar
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // ─── Resume listening after Inertia navigation ────────────────
  useEffect(() => {
    const handleFinish = () => {
      if (mountedRef.current) {
        setTimeout(() => {
          jarvisVoice.cancelSpeechAndReset();
          setStatus('idle');
        }, 100);
      }
    };
    const removeListener = router.on('finish', handleFinish);
    return () => {
      if (removeListener) removeListener();
    };
  }, []);

  // ─── Sync status when speech ends ─────────────────────────────
  // Observar cuando jarvisVoice deja de hablar para volver a idle
  useEffect(() => {
    const interval = setInterval(() => {
      if (mountedRef.current && status === 'processing' && !jarvisVoice._isSpeaking) {
        setStatus('idle');
      }
    }, 500);
    return () => clearInterval(interval);
  }, [status]);

  // ─── Render: solo la esfera flotante ──────────────────────────
  return (
    <div className={`jarvis-orb active status-${status}`} title="Jarvis - Asistente de Voz">
      <span className={`pulse active${status === 'listening' ? ' listening' : ''}`} />
      <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" className="jarvis-svg">
        <circle cx="50" cy="50" r="40" stroke="#10b981" strokeWidth="4" fill="none" opacity="0.8" />
        <circle cx="50" cy="50" r="20" fill="#10b981" opacity="0.6" />
      </svg>
      {status === 'processing' && <span className="processing-dot" />}
    </div>
  );
}
