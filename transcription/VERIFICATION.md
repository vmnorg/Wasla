# Verification

Implemented: standalone FastAPI demo, PHP dashboard microphone integration,
bounded PCM capture and bridge, Darija prompting, partial/final reconciliation,
cancel/stop cleanup, bounded retries, environment configuration and deployment guide.

Offline checks: Python unit/ASGI WebSocket tests; Node worklet, transcript and
client lifecycle tests; JS syntax checks; `pip check`; `git diff --check`.
The fake Gemini provider verifies setupComplete, activity boundaries, PCM,
interim/final messages, quota errors and Stop without sending audio externally.
After migration: 15 Python tests and 7 Node tests pass.

Review covered correctness, structure, security and memory/backpressure limits.
Fixed late microphone permission cleanup and bounded worklet message backlog.
The provider key is read only on the server and no raw provider error is exposed.

Not verified here: live Gemini connection/model access (no Gemini key configured), physical microphone capture,
Darija accuracy, end-to-end latency, visual/browser accessibility checks, or PHP
runtime lint (PHP is unavailable in this environment). No production deployment.
The installed Starlette version emits an httpx TestClient deprecation warning;
the integration tests still pass. Graphify is unavailable, so its generated graph
was not refreshed.
