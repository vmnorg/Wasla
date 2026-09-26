# WebSocket fix verification ? 2026-09-26

## Root causes

- PHP was running as `C:/xampp/php/php.exe -S localhost:8001 -t public/`
  on IPv6 `::1:8001`. The frontend WebSocket therefore reached PHP rather
  than FastAPI. The sandboxed port query initially hid this listener; an
  unrestricted process/listener inspection identified it.
- The local frontend URL branch used `ws://` even on HTTPS pages.
- Live Gemini speech emitted final text and `generationComplete`, while the
  bridge waited exclusively for `turnComplete`, causing Stop to time out.

## Changes

- Separate Windows launchers: PHP on localhost:8000; FastAPI on 127.0.0.1:8001.
  The PHP launcher discovers XAMPP and routes existing /api/*.php endpoints
  without serving the repository root or environment files.
- Frontend resolves configurable WASLA_WS_URL from the existing PHP environment
  loader; retains data-ws-url override. HTTP loopback uses explicit IPv4;
  HTTPS defaults to same-origin WSS. Invalid URLs fail before microphone capture.
- Exact backend origin allowlist trims whitespace. Examples cover documented
  PHP and standalone origins, IPv6 loopback, and local Apache. No wildcard CORS.
- Added /healthz and sanitized connection/exception logging.
- Bridge accepts generationComplete, retaining turnComplete support. Digital
  silence can complete on the provider activity acknowledgement. Nonzero audio
  without a completion event still reports a timeout, preserving uncertainty.
- Removed credential values from both tracked .env.example files; actual ignored
  credentials were preserved. Previously committed credentials need rotation.

## Checks

```powershell
transcription/.venv/Scripts/python -m unittest discover -s transcription/tests
node --test transcription/tests/audio.test.mjs transcription/tests/client.test.mjs transcription/tests/endpoint.test.mjs
C:/xampp/php/php.exe -l public/index.php
C:/xampp/php/php.exe -l public/router.php
transcription/.venv/Scripts/python -m pip check
git diff --check
transcription/.venv/Scripts/python -m transcription.diagnose
transcription/.venv/Scripts/python transcription/tests/browser_smoke.py
```

The optional browser check needs installed Chrome and Playwright. On Windows it
creates temporary synthetic speech with built-in speech synthesis. Alternatively
set WASLA_TEST_AUDIO to a spoken PCM WAV. It never records a physical microphone.
Both local servers and a working Gemini API key must be available.

## Results and limits

21 Python tests and 11 JavaScript tests pass. Offline checks cover PCM/VAD/backpressure, lifecycle cancellation, transcript
reconciliation, origin rejection, URL resolution, health response, secret-safe
logging, and both completion formats. PHP syntax and dependency checks pass.

Live testing verified FastAPI startup, reachable port 8001, provider setup,
WebSocket acceptance and foreign-origin HTTP 403. Chrome tested both the PHP
UI and standalone UI through microphone capture, AudioWorklet PCM, FastAPI,
Gemini partial/final transcript delivery, and successful Stop. No JavaScript
exceptions or Service vocal inaccessible error occurred with the corrected setup.

Physical microphone hardware, native Darija accuracy, production TLS/proxy,
and database-backed logistics operations are not covered by this voice test.
The installed Starlette/httpx test integration emits a deprecation warning.

## Files changed

- `start-voice.ps1`, `start-dashboard.ps1`: reproducible Windows startup.
- `public/index.php`, `public/router.php`: public endpoint configuration and PHP API routing.
- `public/transcription/endpoint.js`, `client.js`, `ui.js`: URL resolution and connection diagnostics.
- `transcription/server.py`, `bridge.py`: origin parsing, health, logging and completion handling.
- `.env.example`, `transcription/.env.example`: public URL setting, safe credential placeholders and local origins.
- `transcription/tests/test_server.py`, `test_bridge.py`, `endpoint.test.mjs`, `browser_smoke.py`: regression and live tests.
- `README.md`, `transcription/README.md`, `SPEC-transcription.md`, `transcription/VERIFICATION.md`: startup and verification documentation.

Provider completion semantics were checked against the
[Gemini Live API reference](https://ai.google.dev/api/live) and actual live events.
The pre-existing local `.gitignore` change was preserved.
