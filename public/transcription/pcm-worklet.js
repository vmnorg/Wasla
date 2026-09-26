// AudioContext performs device-rate conversion to 24 kHz before this worklet.
class PcmCapture extends AudioWorkletProcessor {
  constructor() {
    super();
    this.samples = new Float32Array(6000); // 250 ms, bounded allocation.
    this.count = 0;
    this.enabled = false;
    this.turnFrames = 0;
    this.silentFrames = 0;
    this.hasSpeech = false;
    this.inFlight = 0;
    this.port.onmessage = ({ data }) => {
      if (data === "ack") {
        this.inFlight = Math.max(0, this.inFlight - 1);
      } else if (data === "start" || data === "reset") {
        this.count = this.turnFrames = this.silentFrames = 0;
        this.inFlight = 0;
        this.hasSpeech = false;
        this.enabled = data === "start";
      } else if (data === "stop") {
        this.enabled = false;
        this.flush();
        this.port.postMessage({ type: "flushed" });
      }
    };
  }

  flush() {
    if (!this.count) return;
    if (this.inFlight >= 4) {
      this.enabled = false;
      this.count = 0;
      this.port.postMessage({ type: "overflow" });
      return;
    }
    const buffer = new ArrayBuffer(this.count * 2);
    const view = new DataView(buffer);
    let energy = 0;
    for (let i = 0; i < this.count; i++) {
      const value = Math.max(-1, Math.min(1, this.samples[i]));
      view.setInt16(i * 2, Math.round(value * (value < 0 ? 32768 : 32767)), true);
      energy += value * value;
    }
    const rms = Math.sqrt(energy / this.count);
    this.count = 0;
    this.inFlight++;
    this.port.postMessage({ type: "audio", buffer }, [buffer]);
    this.turnFrames++;
    if (rms > 0.012) {
      this.hasSpeech = true;
      this.silentFrames = 0;
    } else {
      this.silentFrames++;
    }
    // Simple energy VAD: tune against actual microphones and background noise.
    if ((this.hasSpeech && this.silentFrames >= 2) || this.turnFrames >= 60) {
      this.port.postMessage({ type: "commit" });
      this.turnFrames = this.silentFrames = 0;
      this.hasSpeech = false;
    }
  }

  process(inputs) {
    const input = inputs[0]?.[0];
    if (this.enabled && input) {
      for (const sample of input) {
        if (!this.enabled) break;
        this.samples[this.count++] = sample;
        if (this.count === this.samples.length) this.flush();
      }
    }
    // Output stays silent, avoiding microphone feedback.
    return true;
  }
}
registerProcessor("pcm-capture", PcmCapture);
