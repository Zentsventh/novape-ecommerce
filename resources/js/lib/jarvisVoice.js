/**
 * Jarvis Voice Engine 2.0 — Speech Recognition & Synthesis
 * Arquitectura Alexa/Hermes: escucha continua, resultados interim, auto-recovery.
 */
const jarvisVoice = {
  /** @type {SpeechRecognition|null} */
  _recognition: null,
  _shouldBeListening: false,
  _isSpeaking: false,
  _onInterimCallback: null,
  _onFinalCallback: null,
  _onErrorCallback: null,
  /** @type {SpeechSynthesisVoice|null} */
  _cachedVoice: null,
  _voiceLoaded: false,
  _safetyTimer: null,

  /**
   * Busca y cachea la mejor voz española (se ejecuta una sola vez).
   */
  _getSpanishVoice() {
    if (this._voiceLoaded) return this._cachedVoice;
    const voices = speechSynthesis.getVoices();
    if (!voices || voices.length === 0) return null;
    this._cachedVoice =
      voices.find(v => v.lang.startsWith('es') && (v.name.includes('Pablo') || v.name.includes('Raul') || v.name.includes('Diego'))) ||
      voices.find(v => v.lang.startsWith('es')) ||
      null;
    this._voiceLoaded = true;
    return this._cachedVoice;
  },

  /**
   * Habla un texto usando SpeechSynthesis.
   * Pausa la escucha mientras habla para evitar eco.
   */
  speak(text, onEndCallback) {
    // Limpiar cualquier síntesis anterior
    if (speechSynthesis.speaking || speechSynthesis.pending) {
      speechSynthesis.cancel();
    }
    // Limpiar safety timer anterior
    if (this._safetyTimer) {
      clearTimeout(this._safetyTimer);
      this._safetyTimer = null;
    }

    if (!text || text.trim() === '') {
      if (onEndCallback) onEndCallback();
      return;
    }

    this._isSpeaking = true;
    this._pauseListening();

    const utter = new SpeechSynthesisUtterance(text);
    utter.lang = 'es-ES';
    utter.rate = 1.05;

    const voice = this._getSpanishVoice();
    if (voice) utter.voice = voice;

    // Callback de finalización unificado
    const done = () => {
      if (this._safetyTimer) {
        clearTimeout(this._safetyTimer);
        this._safetyTimer = null;
      }
      this._isSpeaking = false;
      this._resumeListening();
      if (onEndCallback) onEndCallback();
    };

    // Safety: si onend nunca se dispara (ej: navegación lo mata), reset tras 12s
    this._safetyTimer = setTimeout(() => {
      if (this._isSpeaking) {
        console.warn('[JarvisVoice] Safety timeout — forzando reset');
        done();
      }
    }, 12000);

    utter.onend = done;
    utter.onerror = done;

    speechSynthesis.speak(utter);
  },

  /**
   * Inicia escucha continua estilo Alexa.
   */
  startContinuous(onInterim, onFinal, onError) {
    this._shouldBeListening = true;
    this._onInterimCallback = onInterim;
    this._onFinalCallback = onFinal;
    this._onErrorCallback = onError;
    this._startEngine();
  },

  /**
   * Detiene la escucha por completo.
   */
  stopListening() {
    this._shouldBeListening = false;
    if (this._recognition) {
      try { this._recognition.abort(); } catch (_) {}
      this._recognition = null;
    }
  },

  /**
   * Cancela síntesis y fuerza reset de flags — usar después de navegación.
   */
  cancelSpeechAndReset() {
    if (speechSynthesis.speaking || speechSynthesis.pending) {
      speechSynthesis.cancel();
    }
    if (this._safetyTimer) {
      clearTimeout(this._safetyTimer);
      this._safetyTimer = null;
    }
    this._isSpeaking = false;
    // Forzar destrucción del recognition actual para empezar limpio
    if (this._recognition) {
      try { this._recognition.abort(); } catch (_) {}
      this._recognition = null;
    }
    // Re-arrancar el motor con un pequeño delay
    if (this._shouldBeListening) {
      setTimeout(() => this._startEngine(), 200);
    }
  },

  _pauseListening() {
    if (this._recognition) {
      try { this._recognition.abort(); } catch (_) {}
    }
  },

  _resumeListening() {
    if (this._shouldBeListening && !this._isSpeaking) {
      setTimeout(() => this._startEngine(), 150);
    }
  },

  _startEngine() {
    // No arrancar si ya hay uno activo, no debería estar escuchando, o está hablando
    if (this._recognition || !this._shouldBeListening || this._isSpeaking) return;

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      console.error('[JarvisVoice] SpeechRecognition no soportado');
      if (this._onErrorCallback) this._onErrorCallback('not_supported');
      return;
    }

    const rec = new SpeechRecognition();
    rec.lang = 'es-ES';
    rec.interimResults = true;
    rec.maxAlternatives = 1;
    rec.continuous = true;

    rec.onresult = (ev) => {
      let interimTranscript = '';
      let finalTranscript = '';

      for (let i = ev.resultIndex; i < ev.results.length; ++i) {
        if (ev.results[i].isFinal) {
          finalTranscript += ev.results[i][0].transcript;
        } else {
          interimTranscript += ev.results[i][0].transcript;
        }
      }

      if (interimTranscript && this._onInterimCallback) {
        this._onInterimCallback(interimTranscript);
      }

      if (finalTranscript && this._onFinalCallback) {
        this._onFinalCallback(finalTranscript);
        // Abort para limpiar y reiniciar después de un resultado final
        try { rec.abort(); } catch (_) {}
      }
    };

    rec.onerror = (ev) => {
      // 'no-speech' es normal en modo continuo, no reportar
      if (ev.error !== 'no-speech' && ev.error !== 'aborted' && this._onErrorCallback) {
        this._onErrorCallback(ev.error);
      }
    };

    rec.onend = () => {
      this._recognition = null;
      // Auto-recovery: si murió pero debería estar escuchando, reiniciar
      if (this._shouldBeListening && !this._isSpeaking) {
        setTimeout(() => this._startEngine(), 300);
      }
    };

    this._recognition = rec;

    try {
      rec.start();
    } catch (e) {
      console.warn('[JarvisVoice] Error al arrancar recognition:', e);
      this._recognition = null;
      setTimeout(() => this._startEngine(), 1000);
    }
  }
};

export default jarvisVoice;
