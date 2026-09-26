import json
import os
import unittest
from unittest.mock import patch

from fastapi.testclient import TestClient
from starlette.websockets import WebSocketDisconnect
from transcription import server
from test_bridge import Upstream


class Connection(Upstream):
    async def __aenter__(self):
        return self

    async def __aexit__(self, *args):
        pass

    async def recv(self):
        return json.dumps({'setupComplete': {}})


class ServerTests(unittest.TestCase):
    def setUp(self):
        self.client = TestClient(server.app)

    def test_page_and_worklet_are_served_but_env_is_not(self):
        self.assertEqual(self.client.get('/').status_code, 200)
        self.assertEqual(self.client.get('/transcription/pcm-worklet.js').status_code, 200)
        self.assertEqual(self.client.get('/.env').status_code, 404)

    def test_health_reports_route_without_secrets(self):
        self.assertEqual(self.client.get('/healthz').json(), {
            'status': 'ok', 'websocket_path': '/ws/transcribe'})

    def test_origin_config_trims_whitespace_and_trailing_slashes(self):
        self.assertEqual(server.parse_origins(' http://localhost:8000/ , ,http://127.0.0.1:8000 '),
                         {'http://localhost:8000', 'http://127.0.0.1:8000'})

    def test_both_documented_php_origins_connect(self):
        for origin in ('http://localhost:8000', 'http://127.0.0.1:8000'):
            with self.subTest(origin=origin), patch.dict(os.environ, {'GEMINI_API_KEY': 'test'}), patch.object(server, 'connect', return_value=Connection()):
                with self.client.websocket_connect('/ws/transcribe', headers={'origin': origin}) as ws:
                    self.assertEqual(ws.receive_json()['type'], 'ready')
                    ws.send_json({'type': 'stop'})
                    self.assertEqual(ws.receive_json()['type'], 'stopped')

    def test_origin_failure_logs_no_request_secrets(self):
        with self.assertLogs('uvicorn.error', level='WARNING') as logs:
            with self.assertRaises(WebSocketDisconnect):
                with self.client.websocket_connect('/ws/transcribe', headers={'origin': 'https://foreign.example/private-secret'}):
                    pass
        self.assertNotIn('private-secret', ''.join(logs.output))

    def test_foreign_origin_denied(self):
        with self.assertRaises(WebSocketDisconnect):
            with self.client.websocket_connect('/ws/transcribe', headers={'origin': 'https://foreign.example'}):
                pass

    def test_missing_key_is_terminal(self):
        with patch.dict(os.environ, {'GEMINI_API_KEY': ''}), patch.object(server, 'dotenv_values', return_value={}):
            with self.client.websocket_connect('/ws/transcribe', headers={'origin': 'http://localhost:8001'}) as ws:
                event = ws.receive_json()
                self.assertEqual(event['type'], 'error')
                self.assertFalse(event['retryable'])
                self.assertIn('GEMINI_API_KEY', event['message'])

    def test_full_websocket_protocol_with_fake_provider(self):
        upstream = Connection()
        with patch.dict(os.environ, {'GEMINI_API_KEY': 'test-not-a-real-key'}), patch.object(server, 'connect', return_value=upstream):
            with self.client.websocket_connect('/ws/transcribe', headers={'origin': 'http://localhost:8001'}) as ws:
                self.assertEqual(ws.receive_json()['type'], 'ready')
                ws.send_bytes(bytes(12000))
                ws.send_json({'type': 'stop'})
                events = [ws.receive_json() for _ in range(3)]
                self.assertEqual([e['type'] for e in events], ['partial', 'final', 'stopped'])
        self.assertEqual(server.active, 0)

    def test_quota_error_is_terminal_during_session_setup(self):
        class CreditConnection(Connection):
            async def recv(self):
                return json.dumps({'error': {'code': 429}})
        with patch.dict(os.environ, {'GEMINI_API_KEY': 'test-not-a-real-key'}), patch.object(server, 'connect', return_value=CreditConnection()):
            with self.client.websocket_connect('/ws/transcribe', headers={'origin': 'http://localhost:8001'}) as ws:
                event = ws.receive_json()
                self.assertEqual(event['type'], 'error')
                self.assertFalse(event['retryable'])
                self.assertIn('Quota Gemini', event['message'])
        self.assertEqual(server.active, 0)
