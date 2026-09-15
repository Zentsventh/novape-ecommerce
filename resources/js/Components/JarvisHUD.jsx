import { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import jarvisVoice from '../lib/jarvisVoice';
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
    // Inicializar Picovoice si está disponible
    jarvisVoice.initPorcupine().then(() => {
      jarvisVoice.startContinuous(
        // onWakeWord — Despierta visualmente
        () => {
          if (mountedRef.current && status !== 'processing' && status !== 'speaking') {
            setStatus('listening');
          }
        },
        // onInterim — solo actualizar el estado visual
        (text) => {
          if (mountedRef.current && status !== 'processing' && status !== 'speaking') {
            setStatus('listening');
          }
        },
        // onFinal — Llamar a la API agéntica (Gemini)
        async (text) => {
          if (!mountedRef.current) return;

          const cleanText = text.toLowerCase().trim();
          
          // Marcar como procesando
          setStatus('processing');
          console.log('[JarvisHUD] Enviando mensaje a Gemini:', cleanText);

          try {
              // Llamada al Santo Grial
              const res = await axios.post('/api/jarvis/admin/message', { message: text });
              console.log('[JarvisHUD] Respuesta de Gemini:', res.data);
              
              // Si hay redirección
              if (res.data.command && res.data.command.type === 'redirect') {
                  router.visit(res.data.command.url);
              }

              // Hablar la respuesta (Neural)
              jarvisVoice.speakNeural(res.data.audio_base64, res.data.voice, () => {
                  if (mountedRef.current) setStatus('idle');
              });
              
          } catch (error) {
              console.error('Error de conexión con procesador neural:', error);
              jarvisVoice.speak('Señor, perdí conexión temporal con mi procesador.', () => {
                  if (mountedRef.current) setStatus('idle');
              });
          }
        },
        // onError
        (err) => {
          if (mountedRef.current && status === 'listening') {
            setStatus('idle');
          }
        }
      );
    });

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
