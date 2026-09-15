class VadProcessor extends AudioWorkletProcessor {
  constructor() {
    super();
    this.threshold = 0.015; // Umbral de energía para detectar voz
    this.isSpeaking = false;
    this.silenceFrames = 0;
    this.speechFrames = 0;
    this.SILENCE_LIMIT = 50; // frames de silencio para considerar fin de habla
    this.SPEECH_LIMIT = 5;  // frames de sonido para considerar inicio de habla (barge-in rápido)
  }

  process(inputs, outputs, parameters) {
    const input = inputs[0];
    if (!input || !input[0]) return true;

    const channel = input[0];
    let sum = 0;
    for (let i = 0; i < channel.length; i++) {
      sum += channel[i] * channel[i];
    }
    const rms = Math.sqrt(sum / channel.length);

    if (rms > this.threshold) {
      this.speechFrames++;
      this.silenceFrames = 0;
      if (this.speechFrames > this.SPEECH_LIMIT && !this.isSpeaking) {
        this.isSpeaking = true;
        this.port.postMessage({ event: 'speech_start', rms });
      }
    } else {
      this.silenceFrames++;
      this.speechFrames = 0;
      if (this.silenceFrames > this.SILENCE_LIMIT && this.isSpeaking) {
        this.isSpeaking = false;
        this.port.postMessage({ event: 'speech_end' });
      }
    }

    return true; // Mantener vivo el procesador
  }
}

registerProcessor('vad-processor', VadProcessor);
