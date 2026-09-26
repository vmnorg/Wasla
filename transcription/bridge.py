"""Gemini Live PCM bridge. Audio and transcripts are never persisted."""
import asyncio
import base64
import json
import time

RATE = 24000
MAX_FRAME = RATE  # 500 ms of PCM16 mono.
GEMINI_URL = "wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1beta.GenerativeService.BidiGenerateContent"


def session_config():
    return {"setup": {
        "model": "models/gemini-3.5-transcribe-live",
        "generationConfig": {"responseModalities": ["TEXT"]},
        "realtimeInputConfig": {"automaticActivityDetection": {"disabled": True}},
        # ar-MA isn't documented by this model. No system prompt is supported.
        "inputAudioTranscription": {
            "languageCodes": ["ar-EG"], "mode": "VERBATIM",
            "customVocabulary": ["مطيشة", "خيزو", "جوج طن", "بلفاع", "ماسة",
                                 "تارودانت", "إنزكان", "ولاد تايمة"],
        },
    }}


class ProtocolError(Exception):
    pass


def provider_error(event):
    """Never expose upstream messages, URLs or credentials."""
    code = (event.get("error") or {}).get("code")
    messages = {
        400: "Configuration Gemini refusée. Vérifiez le modèle et les paramètres de transcription.",
        401: "Clé Gemini refusée. Vérifiez GEMINI_API_KEY dans transcription/.env.",
        403: "Accès Gemini refusé. Vérifiez la clé API et les autorisations du projet.",
        404: "Le modèle Gemini de transcription n’est pas accessible avec ce projet.",
        429: "Quota Gemini atteint. Attendez son renouvellement ou vérifiez les limites dans Google AI Studio.",
    }
    return ProtocolError(messages.get(code, "Gemini a refusé la transcription. Vérifiez la clé, le modèle et le quota du projet."))


async def bridge(client, upstream):
    """Serialize activities so a new turn cannot interrupt pending final text."""
    buffered = 0
    ended = asyncio.Event()
    ended.set()
    started = time.monotonic()
    total_bytes = 0
    segment = 0
    partial = ""
    silent = True

    async def send(event):
        await asyncio.wait_for(upstream.send(json.dumps(event)), 5)

    async def commit():
        nonlocal buffered
        if not buffered:
            return
        buffered = 0
        await send({"realtimeInput": {"activityEnd": {}}})
        await asyncio.wait_for(ended.wait(), 15)

    async def upload():
        nonlocal buffered, total_bytes, silent
        while True:
            message = await client.receive()
            if message["type"] == "websocket.disconnect":
                return
            audio = message.get("bytes")
            if audio is not None:
                if not audio or len(audio) > MAX_FRAME or len(audio) % 2:
                    raise ProtocolError("Expected 1–500 ms of mono PCM16 at 24 kHz.")
                total_bytes += len(audio)
                if total_bytes > (time.monotonic() - started + 2) * RATE * 2:
                    raise ProtocolError("Audio exceeds real-time upload rate.")
                if not buffered:
                    ended.clear()
                    silent = True
                    await send({"realtimeInput": {"activityStart": {}}})
                silent = silent and not any(audio)
                buffered += len(audio)
                await send({"realtimeInput": {"audio": {
                    "mimeType": "audio/pcm;rate=24000",
                    "data": base64.b64encode(audio).decode(),
                }}})
                if buffered >= RATE * 2 * 20:
                    await commit()
                continue
            raw = message.get("text", "")
            if len(raw) > 100:
                raise ProtocolError("Control message is too large.")
            try:
                event = json.loads(raw)
            except (ValueError, TypeError):
                raise ProtocolError("Invalid control JSON.") from None
            if not isinstance(event, dict) or event.get("type") not in ("commit", "stop"):
                raise ProtocolError("Unknown control message.")
            await commit()
            if event["type"] == "stop":
                await client.send_json({"type": "stopped"})
                return

    async def download():
        nonlocal segment, partial
        async for raw in upstream:
            event = json.loads(raw)
            if "error" in event:
                raise provider_error(event)
            if "goAway" in event:
                raise ConnectionError("Gemini session is ending.")
            content = event.get("serverContent", {})
            # Interim text is a revised hypothesis, NOT an append delta.
            interim = content.get("interimInputTranscription")
            if interim and "text" in interim:
                partial = interim["text"]
                await client.send_json({"type": "partial", "item_id": str(segment), "text": partial})
            final = content.get("inputTranscription")
            if final and "text" in final:
                await client.send_json({"type": "final", "item_id": str(segment), "text": final["text"]})
                segment += 1
                partial = ""
            if content.get("interrupted"):
                raise ProtocolError("Transcription Gemini interrompue. Le texte peut être incomplet.")
            # Transcribe Live emits generationComplete for finalized speech;
            # unlike conversational models it may never emit turnComplete.
            if content.get("generationComplete") or content.get("turnComplete"):
                if partial:
                    raise ProtocolError("Gemini n’a pas finalisé le dernier texte. Réessayez l’enregistrement.")
                ended.set()
            # Digital silence yields only an activity acknowledgement, no
            # generationComplete. Never use this shortcut for nonzero audio.
            if silent and not partial and event.get("voiceActivity", {}).get("type") == "ACTIVITY_END":
                ended.set()
        raise ConnectionError("Gemini disconnected.")

    tasks = [asyncio.create_task(upload()), asyncio.create_task(download())]
    try:
        done, _ = await asyncio.wait(tasks, return_when=asyncio.FIRST_COMPLETED)
        for task in done:
            task.result()
    finally:
        for task in tasks:
            task.cancel()
        await asyncio.gather(*tasks, return_exceptions=True)
