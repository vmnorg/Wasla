# Darija streaming transcription — Gemini

## Objective
Stream browser microphone audio through a FastAPI WebSocket to Gemini and show
live Arabic transcription in the PHP dashboard and standalone demo page.
Provider migration to Gemini was explicitly requested. No OpenAI fallback.

## Stack and structure
Python 3.11+, FastAPI, websockets, dotenv. Vanilla JS AudioWorklet and WebSocket.
`transcription/`: bridge, server, environment example, diagnostics and tests.
`public/transcription/`: capture worklet, client, transcript state, UI and demo.

## Contract
24 kHz mono PCM16 little-endian, 250 ms frames, bounded audio queues.
Gemini `gemini-3.5-transcribe-live`, TEXT modality, VERBATIM transcription,
documented Arabic hint ar-EG and Moroccan vocabulary. ar-MA recognition is not
guaranteed. The dedicated model accepts vocabulary hints, not system instructions.
Server authenticates via GEMINI_API_KEY, sends setup, waits for setupComplete.
Manual activityStart/activityEnd; wait for generationComplete/turnComplete before the next activity.
Browser controls: commit/stop. Server: ready, partial, final, stopped, error.
Interim hypotheses replace rather than append; final segments remain ordered.
Stop flushes audio and waits for completion; digital silence may complete on an activity-end acknowledgement. Disconnect never silently replays
uncertain audio. Errors preserve visible text and release microphone resources.

## Commands
Install: `python -m pip install -r transcription/requirements.txt`
Run: `python -m uvicorn transcription.server:app --host 127.0.0.1 --port 8001 --ws-max-size 24000 --ws-max-queue 8`
Test: `python -m unittest discover -s transcription/tests`
Test JS: `node --test transcription/tests/audio.test.mjs transcription/tests/client.test.mjs`

## Style and validation
Small async functions, explicit state, bounded buffers, DOM text APIs.
Example: `await send({"realtimeInput": {"activityEnd": {}}})`.
Test PCM encoding, stop flush, empty/silent turns, interim revisions, multiple
final segments, quota errors, origin validation and cancellation with provider mocks.
Manual: microphone permission, physical capture, Darija accuracy, browser UI and
end-to-end latency. Under 500 ms is a measured target, not a service guarantee.

## Boundaries
Always keep keys server-side and out of example files. Do not log raw provider
errors or audio. Explicitly report quota/access problems. Google's free tier has
quotas and data-use terms; no assumption that a paid project becomes free.
Ask before production deployment or existing auth/storage changes. Authenticate
and rate-limit at the gateway before exposing the loopback demo publicly.

## Implementation order
1. Replace provider setup, audio relay and transcript normalization.
2. Update UI provider disclosure and interim replacement.
3. Migrate environment examples, diagnostics and documentation.
4. Run mocked backend/client tests and reload the local service.
