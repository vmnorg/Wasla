export class SpeechClient {
  constructor(url, onEvent, onState) {
    this.url = url;
    this.onEvent = onEvent;
    this.onState = onState;
    this.state = "idle";
    this.generation = 0;
  }
  setState(state, message) {
    this.state = state;
    this.onState(state, message);
  }
  async start() {
    if (this.state !== "idle") return;
    const generation = ++this.generation;
    this.retries = 0;
    this.lastError = "";
    this.setState("connecting", "Autorisez le micro…");
    try {
      if (!navigator.mediaDevices?.getUserMedia) throw Error("Utilisez HTTPS ou localhost pour accéder au micro.");
      this.context = new AudioContext({ sampleRate: 24000 });
      await this.context.resume();
      if (generation !== this.generation) return;
      const stream = await navigator.mediaDevices.getUserMedia({ audio: {
        channelCount: 1, echoCancellation: true, noiseSuppression: true,
        autoGainControl: true,
      }});
      if (generation !== this.generation) { stream.getTracks().forEach(t => t.stop()); return; }
      this.stream = stream;
      stream.getAudioTracks()[0].onended = () => this.finish("Microphone déconnecté. Redémarrez l’enregistrement.");
      if (this.context.sampleRate !== 24000) throw Error("Ce navigateur ne prend pas en charge l’audio à 24 kHz.");
      await this.context.audioWorklet.addModule(new URL("./pcm-worklet.js", import.meta.url));
      if (generation !== this.generation) return;
      this.node = new AudioWorkletNode(this.context, "pcm-capture", { channelCount: 1 });
      this.source = this.context.createMediaStreamSource(stream);
      this.source.connect(this.node);
      this.node.connect(this.context.destination);
      this.node.port.onmessage = ({ data }) => this.audioMessage(data);
      this.open(generation);
    } catch (error) {
      if (generation !== this.generation) return;
      this.finish(error.name === "NotAllowedError"
        ? "Accès au micro refusé. Autorisez-le dans les paramètres du navigateur."
        : error.message);
    }
  }
  open(generation) {
    if (generation !== this.generation) return;
    this.setState("connecting", "Connexion au service vocal…");
    let socket;
    try { socket = this.socket = new WebSocket(this.url); }
    catch { this.finish("Adresse du service vocal invalide ou bloquée par le navigateur."); return; }
    let terminal = false;
    let connected = false;
    // Omit URL paths, query parameters and close reasons from logs.
    const targetOrigin = new URL(this.url).origin;
    socket.onopen = () => {
      connected = true;
      this.lastError = "";
      console.info("[voice] WebSocket connected", { target: targetOrigin });
    };
    this.connectTimer = setTimeout(() => socket.close(), 20000);
    socket.onmessage = ({ data }) => {
      if (socket !== this.socket) return;
      let event;
      try { event = JSON.parse(data); } catch { this.finish("Réponse du serveur invalide."); return; }
      if (event.type === "ready") {
        clearTimeout(this.connectTimer);
        this.onEvent({ type: "session" });
        this.node.port.postMessage("start");
        this.setState("recording", "Enregistrement en Darija…");
      } else if (event.type === "error") {
        terminal = !event.retryable;
        this.lastError = event.message;
        if (terminal || this.state === "stopping") this.finish(event.message);
        else socket.close();
      } else if (event.type === "stopped") {
        this.finish("Transcription terminée. Vous pouvez corriger le texte.");
      } else {
        this.onEvent(event);
      }
    };
    socket.onerror = () => {
      if (socket !== this.socket) return;
      console.warn("[voice] WebSocket connection failed", { target: targetOrigin, pageOrigin: globalThis.location?.origin });
      this.lastError ||= connected
        ? "Connexion au service vocal interrompue. Vérifiez les journaux FastAPI."
        : `Service vocal inaccessible (${targetOrigin}). Démarrez le backend avec .\\start-voice.ps1 et vérifiez ALLOWED_ORIGINS (origine : ${globalThis.location?.origin || "inconnue"}).`;
    };
    socket.onclose = (event) => {
      if (socket !== this.socket || generation !== this.generation) return;
      console.info("[voice] WebSocket closed", { target: targetOrigin, code: event?.code, connected });
      clearTimeout(this.connectTimer);
      this.node?.port.postMessage("reset");
      if (terminal || this.state === "stopping" || this.retries >= 3) {
        this.finish(this.lastError || "Connexion interrompue. Le texte peut être incomplet.");
        return;
      }
      const delay = Math.min(4000, 500 * 2 ** this.retries++) + Math.random() * 250;
      this.setState("reconnecting", "Connexion perdue. Reconnexion… Répétez les mots prononcés pendant la coupure.");
      this.retryTimer = setTimeout(() => this.open(generation), delay);
    };
  }
  audioMessage(data) {
    if (data.type === "audio") this.node?.port.postMessage("ack");
    if (data.type === "overflow") {
      this.finish("Le navigateur est trop occupé. Enregistrement arrêté ; le texte peut être incomplet.");
      return;
    }
    if (data.type === "flushed") { this.flushed?.(); return; }
    if (!["recording", "stopping"].includes(this.state) || this.socket?.readyState !== WebSocket.OPEN) return;
    if (this.socket.bufferedAmount > 48000) {
      this.finish("Réseau trop lent : enregistrement arrêté. Le texte peut être incomplet.");
      return;
    }
    if (data.type === "audio") this.socket.send(data.buffer);
    if (data.type === "commit") this.socket.send(JSON.stringify({ type: "commit" }));
  }
  async stop() {
    if (this.state === "idle" || this.state === "stopping") return;
    if (this.state !== "recording") { this.finish("Enregistrement annulé."); return; }
    this.setState("stopping", "Finalisation de la transcription…");
    const generation = this.generation;
    try {
      await new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(Error("Le micro ne répond plus.")), 2000);
        this.flushed = () => { clearTimeout(timer); resolve(); };
        this.node.port.postMessage("stop");
      });
      if (generation !== this.generation) return;
      this.releaseAudio();
      if (this.state !== "stopping") return;
      this.socket.send(JSON.stringify({ type: "stop" }));
      this.stopTimer = setTimeout(() => this.finish("Finalisation expirée. Le texte peut être incomplet."), 18000);
    } catch (error) {
      if (generation === this.generation) this.finish(error.message);
    }
  }
  releaseAudio() {
    this.source?.disconnect();
    this.node?.disconnect();
    this.stream?.getTracks().forEach(track => { track.onended = null; track.stop(); });
    if (this.context && this.context.state !== "closed") void this.context.close().catch(() => {});
    this.source = this.node = this.stream = this.context = null;
  }
  finish(message = "Enregistrement arrêté.") {
    ++this.generation;
    clearTimeout(this.retryTimer);
    clearTimeout(this.connectTimer);
    clearTimeout(this.stopTimer);
    this.socket?.close();
    this.socket = null;
    this.releaseAudio();
    this.setState("idle", message);
  }
}
