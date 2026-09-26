"""Opt-in live smoke test: running PHP/FastAPI + configured Gemini key required.

Uses synthetic browser microphone audio, never the physical microphone.
Install playwright separately; it isn't a backend runtime dependency.
"""
import asyncio
import json
import os
from pathlib import Path
import subprocess
import tempfile
from urllib.request import urlopen

from playwright.sync_api import sync_playwright
from websockets.asyncio.client import connect
from websockets.exceptions import InvalidStatus


async def check_websocket():
    async with connect('ws://127.0.0.1:8001/ws/transcribe', origin='http://localhost:8000') as ws:
        event = json.loads(await asyncio.wait_for(ws.recv(), 20))
        assert event['type'] == 'ready', event.get('message', event['type'])
        await ws.send(json.dumps({'type': 'stop'}))
        assert json.loads(await ws.recv())['type'] == 'stopped'
    async with connect('ws://127.0.0.1:8001/ws/transcribe', origin='http://localhost:8000') as ws:
        assert json.loads(await asyncio.wait_for(ws.recv(), 20))['type'] == 'ready'
        await ws.send(bytes(12000))
        await ws.send(json.dumps({'type': 'stop'}))
        assert json.loads(await asyncio.wait_for(ws.recv(), 5))['type'] == 'stopped'
    try:
        async with connect('ws://127.0.0.1:8001/ws/transcribe', origin='https://foreign.example'):
            raise AssertionError('Foreign origin was accepted')
    except InvalidStatus as exc:
        assert exc.response.status_code == 403
    print('PASS real WebSocket: ready, empty stop, silent stop, foreign origin denied')


def main():
    with urlopen('http://127.0.0.1:8001/healthz', timeout=5) as response:
        assert json.load(response)['status'] == 'ok'
    print('PASS port 8001 and FastAPI health')
    asyncio.run(check_websocket())
    with sync_playwright() as playwright:
        browser = playwright.chromium.launch(channel='chrome', headless=True, args=[
            '--use-fake-ui-for-media-stream', '--use-fake-device-for-media-stream',
            *([f"--use-file-for-fake-audio-capture={os.environ['WASLA_TEST_AUDIO']}"]
              if os.getenv('WASLA_TEST_AUDIO') else []),
        ])
        try:
            for url in ('http://localhost:8000', 'http://127.0.0.1:8001'):
                context = browser.new_context(permissions=['microphone'])
                page = context.new_page()
                errors = []
                page.on('pageerror', lambda error: errors.append(str(error)))
                sockets = []
                events = []
                def socket_opened(socket):
                    sockets.append(socket.url)
                    socket.on('framereceived', lambda payload: events.append(json.loads(payload)['type']))
                page.on('websocket', socket_opened)
                page.goto(url, wait_until='domcontentloaded')
                page.wait_for_function("document.querySelector('#mainMicBtn').hasAttribute('aria-pressed')")
                page.locator('#mainMicBtn').click()
                page.wait_for_function("document.querySelector('#mic-rec-badge').textContent === 'REC'", timeout=30000)
                page.wait_for_timeout(7000 if os.getenv('WASLA_TEST_AUDIO') else 1500)
                assert page.locator('#darija-text').get_attribute('readonly') is not None
                page.locator('#mainMicBtn').click()
                page.wait_for_function("document.querySelector('#mainMicBtn').getAttribute('aria-pressed') === 'false'", timeout=25000)
                status = page.locator('#mic-status').inner_text()
                assert 'Transcription terminée' in status, status
                assert sockets == ['ws://127.0.0.1:8001/ws/transcribe'], sockets
                assert 'final' in events, events
                assert not errors, errors
                assert page.locator('#darija-text').get_attribute('readonly') is None
                print(f'PASS browser {url}: microphone -> PCM -> FastAPI -> Gemini -> stop; no JS errors')
                context.close()
        finally:
            browser.close()


if __name__ == '__main__':
    if os.getenv('WASLA_TEST_AUDIO'):
        main()
    else:
        # Windows built-in TTS provides reproducible speech instead of a test tone.
        with tempfile.TemporaryDirectory(prefix='wasla-speech-') as directory:
            os.environ['WASLA_TEST_AUDIO'] = str(Path(directory) / 'speech.wav')
            subprocess.run(['powershell.exe', '-NoProfile', '-Command',
                "$ErrorActionPreference = 'Stop'; Add-Type -AssemblyName System.Speech; "
                "$synth = New-Object System.Speech.Synthesis.SpeechSynthesizer; "
                "$synth.SetOutputToWaveFile($env:WASLA_TEST_AUDIO); "
                "$synth.Speak('I have two tons of tomatoes ready for transport tomorrow morning.'); "
                "$synth.Dispose()"], check=True, capture_output=True)
            main()
