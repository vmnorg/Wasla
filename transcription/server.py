"""Run from repo root with python -m uvicorn transcription.server:app."""
import asyncio
import json
import logging
import os
from contextlib import suppress
from pathlib import Path

from dotenv import dotenv_values, load_dotenv
from fastapi import FastAPI, WebSocket, WebSocketDisconnect
from fastapi.responses import FileResponse
from fastapi.staticfiles import StaticFiles
from websockets.asyncio.client import connect
from websockets.exceptions import InvalidStatus, ConnectionClosed

from .bridge import GEMINI_URL, ProtocolError, bridge, provider_error, session_config

# Explicit path: credentials are never served by StaticFiles.
load_dotenv(Path(__file__).with_name(".env"))
def parse_origins(value):
    return {origin.strip().rstrip('/') for origin in value.split(',') if origin.strip()}


# Exact origins only: PHP dev server (8000), standalone (8001), local Apache (80).
ORIGINS = parse_origins(os.getenv("ALLOWED_ORIGINS", "http://localhost:8001,http://127.0.0.1:8001,http://localhost:8000,http://127.0.0.1:8000,http://localhost,http://127.0.0.1,http://[::1]:8000,http://[::1]:8001"))
logger = logging.getLogger("uvicorn.error")
MAX_SESSIONS = int(os.getenv("MAX_SESSIONS", "4"))
SESSION_SECONDS = int(os.getenv("MAX_SESSION_SECONDS", "600"))
ASSETS = Path(__file__).resolve().parents[1] / "public" / "transcription"
app = FastAPI()
active = 0  # Per worker; use one worker or enforce global quotas at the gateway.


@app.get("/")
async def index():
    return FileResponse(ASSETS / "index.html")


@app.get("/healthz")
async def health():
    # Liveness only; this does not claim that the upstream provider is ready.
    return {"status": "ok", "websocket_path": "/ws/transcribe"}


@app.websocket("/ws/transcribe")
async def transcribe(ws: WebSocket):
    global active
    if ws.headers.get("origin") not in ORIGINS:
        logger.warning("Voice WebSocket rejected: origin is not in ALLOWED_ORIGINS")
        await ws.close(code=1008)
        return
    await ws.accept()
    logger.info("Voice WebSocket accepted")
    # Re-read a newly configured local key without requiring a service restart.
    key = (os.getenv("GEMINI_API_KEY") or
           dotenv_values(Path(__file__).with_name(".env")).get("GEMINI_API_KEY") or "").strip()
    if not key:
        logger.warning("Voice setup failed: GEMINI_API_KEY is missing")
        await ws.send_json({"type": "error", "retryable": False,
                            "message": "Clé Gemini absente : configurez GEMINI_API_KEY dans transcription/.env."})
        await ws.close(code=1011)
        return
    if active >= MAX_SESSIONS:
        logger.warning("Voice setup rejected: session limit reached")
        await ws.send_json({"type": "error", "retryable": False,
                            "message": "Le service vocal est occupé. Réessayez dans quelques instants."})
        await ws.close(code=1013)
        return
    active += 1
    try:
        async with asyncio.timeout(SESSION_SECONDS):
            async with connect(
                GEMINI_URL,
                additional_headers={"x-goog-api-key": key},
                open_timeout=10, close_timeout=3, max_size=1024 * 1024,
                max_queue=8, write_limit=32768, ping_interval=20, ping_timeout=20,
            ) as upstream:
                await upstream.send(json.dumps(session_config()))
                async with asyncio.timeout(10):
                    while True:
                        event = json.loads(await upstream.recv())
                        if "setupComplete" in event:
                            break
                        if "error" in event:
                            raise provider_error(event)
                await ws.send_json({"type": "ready", "sample_rate": 24000})
                logger.info("Voice provider ready")
                await bridge(ws, upstream)
    except WebSocketDisconnect:
        pass
    except Exception as exc:
        # Exception text, headers and close reasons can contain credentials.
        logger.warning("Voice session failed (%s)", type(exc).__name__)
        # Never forward raw upstream errors: they can contain private request data.
        retryable = not isinstance(exc, (ProtocolError, InvalidStatus))
        message = str(exc) if isinstance(exc, ProtocolError) else (
            "Connexion Gemini interrompue ou délai dépassé. Le texte peut être incomplet."
        )
        if isinstance(exc, PermissionError):
            message = "Accès réseau du serveur bloqué. Relancez FastAPI dans un terminal autorisé à accéder à Internet."
            retryable = False
        elif isinstance(exc, InvalidStatus):
            status = exc.response.status_code
            message = str(provider_error({"error": {"code": status}}))
        elif isinstance(exc, ConnectionClosed) and exc.rcvd and exc.rcvd.code in (1007, 1008):
            message = "Gemini a refusé la session. Vérifiez la clé, l’accès au modèle et les quotas dans Google AI Studio."
            retryable = False
        with suppress(Exception):
            await ws.send_json({"type": "error", "message": message, "retryable": retryable})
    finally:
        active -= 1
        logger.info("Voice WebSocket session ended")
        with suppress(Exception):
            await ws.close()


app.mount("/transcription", StaticFiles(directory=ASSETS), name="transcription")
