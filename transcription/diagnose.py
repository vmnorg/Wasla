"""Check the provider handshake without transmitting microphone audio or secrets."""
import asyncio
import json
import os
import re
from pathlib import Path

from dotenv import dotenv_values
from websockets.asyncio.client import connect

from .bridge import GEMINI_URL, session_config


async def main():
    key = os.getenv("GEMINI_API_KEY") or dotenv_values(Path(__file__).with_name(".env")).get("GEMINI_API_KEY")
    print("API key configured:", bool(key))
    if not key:
        return
    try:
        async with connect(
            GEMINI_URL,
            additional_headers={"x-goog-api-key": key.strip()},
            open_timeout=15, close_timeout=2,
        ) as ws:
            print("Provider WebSocket connected")
            await ws.send(json.dumps(session_config()))
            async with asyncio.timeout(15):
                while True:
                    event = json.loads(await ws.recv())
                    if "error" in event:
                        code = str(event.get("error", {}).get("code", ""))
                        print("Error code:", code if re.fullmatch(r"[0-9]+", code) else "unknown")
                        return
                    if "setupComplete" in event:
                        print("Transcription session ready (no audio sent)")
                        return
    except Exception as exc:
        print("Exception type:", type(exc).__name__)
        print("OS error:", getattr(exc, "winerror", None) or getattr(exc, "errno", None))
        print("HTTP status:", getattr(getattr(exc, "response", None), "status_code", None))


if __name__ == "__main__":
    asyncio.run(main())
