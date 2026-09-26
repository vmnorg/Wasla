# Live Darija transcription with Gemini

The PHP dashboard microphone streams through FastAPI to Google Gemini Live.
The standalone page at **http://localhost:8001** needs no PHP or database.
No OpenAI key, connection, billing or fallback is used by this feature.

## Setup (PowerShell, Python 3.11+)

### Start the two services on different ports

From the project directory, keep these running in **two separate terminals**:

```powershell
# Terminal 1: FastAPI, http://127.0.0.1:8001
.\start-voice.ps1

# Terminal 2: PHP dashboard, http://localhost:8000
.\start-dashboard.ps1
```

Open **http://localhost:8000** for the dashboard, or **http://127.0.0.1:8001**
for the standalone voice page. The dashboard launcher also finds XAMPP PHP at
`C:/xampp/php/php.exe` when PHP is absent from PATH. The voice launcher creates
its virtual environment and installs dependencies on first use. Existing
`transcription/.env` credentials are preserved. Keep both terminals open.

**Do not start PHP on port 8001.** PHP cannot handle `/ws/transcribe`. In the
reported failure PHP was listening on IPv6 `localhost:8001`, while the voice
service uses IPv4 loopback. A `localhost` WebSocket could therefore reach PHP.
The browser now uses `ws://127.0.0.1:8001/ws/transcribe` on local HTTP pages.

For manual installation or non-Windows systems, use the steps below.

From the repository root:

```powershell
python -m venv transcription/.venv
transcription/.venv/Scripts/python -m pip install -r transcription/requirements.txt
# Only if transcription/.env does not already exist:
Copy-Item transcription/.env.example transcription/.env
```

Create a Gemini API key in [Google AI Studio](https://aistudio.google.com/apikey).
Edit `transcription/.env` locally:

```env
GEMINI_API_KEY=your_gemini_api_key
ALLOWED_ORIGINS=http://localhost:8000,http://127.0.0.1:8000,http://localhost:8001,http://127.0.0.1:8001
MAX_SESSIONS=4
MAX_SESSION_SECONDS=600
```

Keep the key in this ignored file, never in `.env.example`, JavaScript or URLs.
Process environment settings take precedence. When configuring the key for the
first time while the server is running, the next connection reads it automatically.
Restart after replacing an existing key or changing origins/limits.

```powershell
transcription/.venv/Scripts/python -m uvicorn transcription.server:app --host localhost --port 8001 --ws-max-size 24000 --ws-max-queue 8
```

Open http://localhost:8001, click **Enregistrer**, allow the microphone, speak and
click **Arrêter**. After finalization, correct the Arabic transcript as needed.
For the PHP dashboard, also run `php -S localhost:8000 -t public/ public/router.php` and open
http://localhost:8000. Both pages use the same browser code and speech service.

Linux/macOS: substitute `transcription/.venv/bin/python` and use `cp` to copy files.
`requirements.lock` records the dependency set tested on Windows/Python 3.14.

## Provider, cost and language

The dedicated `gemini-3.5-transcribe-live` model supports streaming text-only
transcription. Google's pricing page lists a free tier, subject to project quotas
and availability. A paid-tier project may incur charges; this code does not
change billing settings or automatically switch models/providers on quota errors.
Google lists free-tier inputs/outputs as used to improve its products; review its
terms before sending sensitive audio. Audio is transmitted only while recording.

This model uses language hints and custom vocabulary, not a system prompt.
Configuration uses its documented Arabic hint `ar-EG`, VERBATIM mode and Moroccan
vocabulary (مطيشة، خيزو، جوج طن، بلفاع، ماسة، تارودانت، إنزكان، ولاد تايمة).
`ar-MA` is not listed as a supported hint. This is an Arabic/Darija transcription
attempt, not a guarantee of Moroccan dialect fidelity or Arabic-only output.
Evaluate native speakers and mixed French vocabulary before relying on quantities.

## Streaming protocol

The browser requests mono audio with echo cancellation and noise suppression.
AudioContext resamples to 24 kHz; AudioWorklet sends PCM16 little-endian binary
frames every 250 ms. Gemini receives that actual sample rate in
`audio/pcm;rate=24000` and resamples internally. WebM is not accepted by this bridge.

FastAPI connects to Google's BidiGenerateContent WebSocket with the key in the
`x-goog-api-key` header, sends `setup`, and waits for `setupComplete` before `ready`.
Client-side VAD sends `commit` after 500 ms of silence or 15 seconds; the backend
uses manual `activityStart` / `activityEnd` events. Each activity is finalized
before opening the next, avoiding interruption. A server limit forces a boundary
after 20 seconds. Bounded socket queues apply backpressure during finalization.
Stop flushes the last PCM samples and waits up to 15 seconds for
`generationComplete` (or `turnComplete`). Digital silence completes on the
provider activity-end acknowledgement. Nonzero audio without a completion event
still reports a timeout rather than claiming that uncertain speech is finalized.
Empty Stop sends no activity; silence can complete with no transcript.

`interimInputTranscription` becomes a browser `partial` event which replaces the
current hypothesis. Each `inputTranscription` becomes a `final` segment. Segment
IDs preserve order. Generated assistant text/audio is never shown as recognition.
Unfinalized hypotheses and interrupted turns produce explicit errors.

Browser messages: binary PCM, `{"type":"commit"}`, `{"type":"stop"}`.
Server messages: `ready`, `partial`/`final` (`item_id`, `text`), `stopped`,
`error` (`message`, `retryable`). Existing delta handling remains supported by UI.

The browser retries transient disconnects three times with backoff and jitter.
There is no uncertain audio replay: repeat words spoken during an outage.
Previous text is retained but unfinished speech may be incomplete.

## Limits and deployment

The PHP dashboard reads **`WASLA_WS_URL`** from the process environment or the
root `.env`, using the existing PHP environment loader. Only that public URL
is HTML-escaped into a meta tag; keys are never exposed. Leave it empty for the
local default, or set it to `/ws/transcribe` for a same-origin reverse proxy,
or an absolute `wss://voice.example/ws/transcribe` for a separate voice server.
The microphone's existing `data-ws-url` attribute takes precedence, including
on the standalone page. Do not put credentials in an endpoint URL.

HTTPS pages default to same-origin **WSS**, including localhost. An explicit
`ws://` override is upgraded to `wss://`; the destination must actually provide
TLS. Plain Uvicorn on port 8001 does not provide TLS by itself.

`ALLOWED_ORIGINS` in `transcription/.env` is an exact comma-separated allowlist
of **page origins**, not WebSocket URLs or page paths. Whitespace is trimmed.
The supplied examples cover localhost/127.0.0.1 on PHP port 8000 and standalone
port 8001, local Apache port 80, and IPv6 loopback on 8000/8001. Add your exact
HTTPS/custom-port origin when needed and restart FastAPI. WebSocket validation
uses the Origin header; adding HTTP CORS middleware does not fix a WebSocket
handshake. No wildcard or unrestricted origin policy is used.

There is no Docker, Vite, Next.js, or reverse proxy configuration in this
repository. If deploying behind Nginx, route `/ws/transcribe` to the FastAPI
service with HTTP/1.1, `Upgrade $http_upgrade`, and `Connection "upgrade"`.
In Docker, the proxy upstream uses the backend service name; the browser URL
must remain a browser-reachable public address. See the authentication limits below.

- Sub-500 ms first partial is a target, not a guarantee. Capture alone takes up to
  250 ms; model/network/browser latency adds to it. Final text waits for turn end.
- Worklet and browser outbound queues are each capped near one second of audio.
  Uvicorn queues eight frames, upstream queues are bounded, session time is limited
  to ten minutes, and concurrency defaults to four per worker.
- The local service binds to loopback and checks exact Origins. Origin checks are
  not authentication. Before public use, authenticate WebSocket handshakes through
  your app/gateway and enforce user/IP quotas; keep FastAPI behind that gateway.
- Production requires HTTPS/WSS, WebSocket Upgrade proxying and suitable timeouts.
  Proxy `/ws/transcribe` to port 8001 and set `ALLOWED_ORIGINS` to the exact public
  origin. Use `data-ws-url` on the microphone button for a custom endpoint.
- Microphone access needs localhost or HTTPS. Never serve the repository root,
  `.env`, or the backend directory as public static files.

## Diagnostics and tests

`GET http://127.0.0.1:8001/healthz` returns service liveness and the WebSocket
path, with no secrets. It does not assert Gemini availability. FastAPI logs
accepted/rejected handshakes, missing configuration, provider readiness and
exception class names without raw provider errors, audio, or credentials.
The browser console logs connection state, target origin, page origin and
close code, omitting query strings and provider close reasons.

```powershell
# Handshake only: no microphone audio and no key printed.
transcription/.venv/Scripts/python -m transcription.diagnose
transcription/.venv/Scripts/python -m unittest discover -s transcription/tests
node --test transcription/tests/audio.test.mjs transcription/tests/client.test.mjs
node --test transcription/tests/endpoint.test.mjs

# Optional real Chrome check, with both services running and a valid key.
# Uses Windows speech synthesis and installed Chrome; never the physical microphone.
# On other platforms, supply WASLA_TEST_AUDIO pointing to a spoken PCM WAV.
transcription/.venv/Scripts/python -m pip install playwright
transcription/.venv/Scripts/python transcription/tests/browser_smoke.py
```

Missing key: configure `GEMINI_API_KEY`. A 429/quota error needs a quota reset or
project-limit check in AI Studio. Model/access refusal needs a valid permitted
key and model availability. PermissionError means the server's network access is
blocked; run it from an Internet-enabled terminal outside a restricted sandbox.
Raw upstream errors and keys are never sent to the browser or printed by diagnostics.

Offline tests use mocked Gemini messages. Live acceptance still requires an API
key and browser: test Darija, Stop mid-word, silence, denied permission, device
removal and reconnect. Measure latency and recognition errors separately.

## Official references

- [Gemini live transcription](https://ai.google.dev/gemini-api/docs/live-api/live-transcribe)
- [Live API reference](https://ai.google.dev/api/live)
- [Pricing and free-tier data use](https://ai.google.dev/gemini-api/docs/pricing)
