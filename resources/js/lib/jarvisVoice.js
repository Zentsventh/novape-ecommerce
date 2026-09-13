export default {
  speak(text, onEndCallback = null) {
    if (!text) {
        if (onEndCallback) onEndCallback();
        return;
    }
    const utter = new SpeechSynthesisUtterance(text);
    utter.lang = 'es-ES';
    utter.rate = 1.0;
    
    // Buscar voz de hombre (Pablo, Raul, Diego o male)
    const voices = speechSynthesis.getVoices();
    const maleVoice = voices.find(v => 
        (v.lang.startsWith('es') && (v.name.includes('Pablo') || v.name.includes('Raul') || v.name.includes('Diego')))
    ) || voices.find(v => v.lang.startsWith('es')); // Fallback a cualquier voz en español

    if (maleVoice) {
        utter.voice = maleVoice;
    }

    if (onEndCallback) {
        utter.onend = onEndCallback;
    }
    speechSynthesis.speak(utter);
  },

  listen(callback, onEnd) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      alert('Tu navegador no soporta reconocimiento de voz. Usa Chrome o Edge.');
      return;
    }
    const rec = new SpeechRecognition();
    rec.lang = 'es-ES';
    rec.interimResults = false;
    
    rec.onresult = ev => {
      const transcript = ev.results[0][0].transcript.trim();
      callback(transcript);
    };

    rec.onend = () => {
        if(onEnd) onEnd();
    };

    rec.start();
  }
};
