// PHP supplies WASLA_WS_URL through a meta tag; data-ws-url remains an override.
export function resolveEndpoint(page, configured = "") {
  const local = ["localhost", "127.0.0.1", "[::1]"].includes(page.hostname);
  const fallback = local && page.protocol === "http:"
    ? "ws://127.0.0.1:8001/ws/transcribe"
    : `${page.protocol === "https:" ? "wss:" : "ws:"}//${page.host}/ws/transcribe`;
  const url = new URL(configured.trim() || fallback, page.href);
  if (url.protocol === "http:") url.protocol = "ws:";
  if (url.protocol === "https:") url.protocol = "wss:";
  if (!["ws:", "wss:"].includes(url.protocol) || url.username || url.password || url.hash) {
    throw Error("Adresse du service vocal invalide. Vérifiez WASLA_WS_URL.");
  }
  if (page.protocol === "https:" && url.protocol === "ws:") url.protocol = "wss:";
  return url.href;
}
