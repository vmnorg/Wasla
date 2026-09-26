import { SpeechClient } from "./client.js";
import { Transcript } from "./transcript.js";

const button = document.getElementById("mainMicBtn");
const output = document.getElementById("darija-text");
const status = document.getElementById("mic-status");
const badge = document.getElementById("mic-rec-badge");
const preview = document.createElement("p");
preview.dir = "rtl";
preview.lang = "ar-MA";
preview.setAttribute("aria-live", "polite");
preview.setAttribute("aria-label", "Transcription en direct");
output.after(preview);
output.setAttribute("aria-label", "Transcription en Darija, modifiable après l’enregistrement");
status.setAttribute("role", "status");
// Only the endpoint is public. API keys must stay in transcription/.env.
const local = ["localhost", "127.0.0.1"].includes(location.hostname);
const endpoint = button.dataset.wsUrl || (local && location.port !== "8001"
  ? `ws://${location.hostname}:8001/ws/transcribe`
  : `${location.protocol === "https:" ? "wss" : "ws"}://${location.host}/ws/transcribe`);
let transcript = new Transcript(), previous = "", initial = "", received = false;
const client = new SpeechClient(endpoint, event => {
  if (event.type === "session") {
    previous = [previous, transcript.text()].filter(Boolean).join(" ");
    transcript = new Transcript();
    return;
  }
  transcript.apply(event);
  const text = [previous, transcript.text()].filter(Boolean).join(" ");
  if (text) {
    received = true;
    output.value = text;
    preview.textContent = text;
    output.dispatchEvent(new Event("input", { bubbles: true }));
  }
}, (state, message) => {
  const active = state !== "idle";
  button.disabled = state === "stopping";
  button.setAttribute("aria-pressed", String(active));
  button.setAttribute("aria-label", active ? "Arrêter l’enregistrement" : "Enregistrer en Darija");
  button.title = button.getAttribute("aria-label");
  const icon = button.querySelector(".material-symbols-outlined");
  if (icon) icon.textContent = active ? "stop" : "mic";
  else button.textContent = active ? "Arrêter" : "Enregistrer";
  status.textContent = message;
  if (badge) badge.textContent = state === "recording" ? "REC" : state === "idle" ? "PRÊT" : "…";
  document.querySelectorAll(".wave-bar").forEach(bar => {
    bar.style.animationPlayState = state === "recording" ? "running" : "paused";
  });
  output.readOnly = active;
  document.querySelectorAll(".preset-pill, #nlu-form button[type=submit]").forEach(el => { el.disabled = active; });
  if (!active && !received) output.value = initial || output.value;
});
button.addEventListener("click", () => {
  if (client.state !== "idle") { void client.stop(); return; }
  initial = output.value;
  previous = "";
  received = false;
  transcript = new Transcript();
  preview.textContent = "";
  const translation = document.getElementById("darija-translation");
  if (translation) translation.textContent = "La transcription vocale ne génère pas de traduction.";
  void client.start();
});
window.addEventListener("pagehide", () => client.finish());
client.setState("idle", "Micro prêt. L’audio sera envoyé à Google Gemini pendant l’enregistrement.");
