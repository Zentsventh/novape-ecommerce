import { PorcupineWorker, BuiltInKeyword } from '@picovoice/porcupine-web';

/**
 * Jarvis Voice Engine 3.0 — Neural & Edge AI Edition
 * Arquitectura Alexa/Echo: Porcupine Wake-Word + ElevenLabs Neural TTS
 */
const jarvisVoice = {
  _recognition: null,
  _porcupineWorker: null,
  _isListeningForWakeWord: false,
  _isListeningForCommand: false,
  _isSpeaking: false,
  _onInterimCallback: null,
  _onFinalCallback: null,
  _onErrorCallback: null,
  _onWakeWordCallback: null,
  _currentAudio: null, // HTMLAudioElement for Neural TTS
  _safetyTimer: null,

  async initPorcupine() {
    const accessKey = import.meta.env.VITE_PICOVOICE_ACCESS_KEY;
    if (!accessKey) {
      console.warn('[JarvisVoice] PICOVOICE_ACCESS_KEY no encontrada. Porcupine deshabilitado. Fallback a SpeechRecognition puro.');
      return false;
    }
    
    try {
      this._porcupineWorker = await PorcupineWorker.create(
        accessKey,
        BuiltInKeyword.Jarvis,
        (keywordLabel) => {
          console.log(`[JarvisVoice] Wake word detectado: ${keywordLabel}`);
          this._handleWakeWord();
        }
      );
      console.log('[JarvisVoice] Motor Porcupine (Wake-Word) inicializado exitosamente.');
      return true;
    } catch (e) {
      console.error('[JarvisVoice] Error inicializando Porcupine:', e);
      return false;
    }
  },

  _handleWakeWord() {
    if (this._isSpeaking) {
      this.cancelSpeechAndReset(); // Barge-in interrupción
    }
    if (this._onWakeWordCallback) {
      this._onWakeWordCallback(); // Para brillar la esfera
    }
    
    // Reproducir un pitido de confirmación (opcional)
    // const beep = new Audio('/beep.mp3'); beep.play();
    
    // Activar reconocimiento de voz inmediatamente para escuchar el comando
    this._startCommandRecognition();
  },

  /**
   * Reproduce el audio neural devuelto por ElevenLabs.
   */
  speakNeural(base64Audio, fallbackText, onEndCallback) {
    this.cancelSpeechAndReset(); // Corta cualquier audio previo
    
    if (!base64Audio) {
      // Fallback a TTS robótico si no hay neural audio
      console.warn('[JarvisVoice] No neural audio provided, falling back to Web Speech API');
      return this.speak(fallbackText, onEndCallback);
    }

    this._isSpeaking = true;
    
    try {
      this._currentAudio = new Audio("data:audio/mpeg;base64," + base64Audio);
      
      const done = () => {
        this._isSpeaking = false;
        this._currentAudio = null;
        if (this._safetyTimer) {
          clearTimeout(this._safetyTimer);
          this._safetyTimer = null;
        }
        if (onEndCallback) onEndCallback();
      };

      this._currentAudio.onended = done;
      this._currentAudio.onerror = done;

      // Safety timeout de 15 segundos
      this._safetyTimer = setTimeout(() => {
        if (this._isSpeaking) {
            console.warn('[JarvisVoice] Neural audio safety timeout');
            done();
        }
      }, 15000);

      this._currentAudio.play().catch(e => {
        console.error('[JarvisVoice] Error reproduciendo Neural Audio:', e);
        done();
      });

    } catch (e) {
      console.error('[JarvisVoice] Error cargando Neural Audio:', e);
      this._isSpeaking = false;
      if (onEndCallback) onEndCallback();
    }
  },

  /**
   * TTS Robótico antiguo (Fallback)
   */
  speak(text, onEndCallback) {
    this.cancelSpeechAndReset();
    if (!text) {
      if (onEndCallback) onEndCallback();
      return;
    }
    this._isSpeaking = true;

    const utter = new SpeechSynthesisUtterance(text);
    utter.lang = 'es-ES';
    utter.rate = 1.05;

    const done = () => {
      this._isSpeaking = false;
      if (this._safetyTimer) {
        clearTimeout(this._safetyTimer);
        this._safetyTimer = null;
      }
      if (onEndCallback) onEndCallback();
    };

    this._safetyTimer = setTimeout(done, 12000);
    utter.onend = done;
    utter.onerror = done;
    speechSynthesis.speak(utter);
  },

  async startContinuous(onWakeWord, onInterim, onFinal, onError) {
    this._onWakeWordCallback = onWakeWord;
    this._onInterimCallback = onInterim;
    this._onFinalCallback = onFinal;
    this._onErrorCallback = onError;

    // Si ya inicializamos porcupine, arrancar WebVoiceProcessor (micrófono real continuo)
    if (this._porcupineWorker) {
        // En una implementación real requerimos @picovoice/web-voice-processor para alimentar a PorcupineWorker
        // import { WebVoiceProcessor } from '@picovoice/web-voice-processor';
        // await WebVoiceProcessor.subscribe(this._porcupineWorker);
        // NOTA: Para no romper el sistema si falla picovoice, usaremos el fallback por ahora
        console.warn('[JarvisVoice] Integración profunda de WebVoiceProcessor pendiente. Usando fallback.');
    }
    
    // Fallback: usar SpeechRecognition continuo simulando Wake Word
    this._startFallbackEngine();
  },

  cancelSpeechAndReset() {
    if (this._currentAudio) {
      this._currentAudio.pause();
      this._currentAudio.currentTime = 0;
      this._currentAudio = null;
    }
    if (speechSynthesis.speaking || speechSynthesis.pending) {
      speechSynthesis.cancel();
    }
    if (this._safetyTimer) {
      clearTimeout(this._safetyTimer);
      this._safetyTimer = null;
    }
    this._isSpeaking = false;
    this._isListeningForCommand = false;
    if (this._recognition) {
      try { this._recognition.abort(); } catch (_) {}
      this._recognition = null;
    }
    setTimeout(() => this._startFallbackEngine(), 200);
  },

  stopListening() {
    this._isListeningForWakeWord = false;
    this._isListeningForCommand = false;
    if (this._recognition) {
      try { this._recognition.abort(); } catch (_) {}
      this._recognition = null;
    }
  },

  // ---------------- FALLBACK ENGINE ----------------
  _startFallbackEngine() {
    if (this._recognition || this._isSpeaking) return;

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) return;

    this._isListeningForWakeWord = true;
    const rec = new SpeechRecognition();
    rec.lang = 'es-ES';
    rec.interimResults = true;
    rec.continuous = true;

    rec.onresult = (ev) => {
      let finalTranscript = '';
      let interimTranscript = '';
      for (let i = ev.resultIndex; i < ev.results.length; ++i) {
        if (ev.results[i].isFinal) finalTranscript += ev.results[i][0].transcript;
        else interimTranscript += ev.results[i][0].transcript;
      }
      
      const transcript = (interimTranscript || finalTranscript).trim().toLowerCase();
      
      // Simular interrupción (Barge-in falso)
      if (this._isSpeaking && transcript.length > 3) {
          this.cancelSpeechAndReset();
      }

      if (this._isListeningForWakeWord) {
        if (transcript.includes('jarvis')) {
          this._handleWakeWord();
        }
      } else if (this._isListeningForCommand) {
        if (interimTranscript && this._onInterimCallback) this._onInterimCallback(interimTranscript);
        if (finalTranscript && this._onFinalCallback) {
            this._isListeningForCommand = false;
            this._isListeningForWakeWord = true;
            this._onFinalCallback(finalTranscript);
            try { rec.abort(); } catch (_) {}
        }
      }
    };

    rec.onend = () => {
      this._recognition = null;
      if (!this._isSpeaking) {
        setTimeout(() => this._startFallbackEngine(), 300);
      }
    };

    try {
      rec.start();
      this._recognition = rec;
    } catch (e) {
      this._recognition = null;
    }
  },

  _startCommandRecognition() {
    // Cambia al estado de escuchar el comando después de decir Jarvis
    this._isListeningForWakeWord = false;
    this._isListeningForCommand = true;
    console.log('[JarvisVoice] Escuchando comando activamente...');
  }
};

export default jarvisVoice;
