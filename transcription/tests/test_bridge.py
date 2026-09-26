import asyncio
import base64
import json
import unittest

from transcription.bridge import ProtocolError, bridge, provider_error, session_config


class Client:
    def __init__(self, messages):
        self.messages = asyncio.Queue()
        for message in messages:
            self.messages.put_nowait(message)
        self.events = []

    async def receive(self):
        return await self.messages.get()

    async def send_json(self, event):
        self.events.append(event)


class Upstream:
    def __init__(self):
        self.events = asyncio.Queue()
        self.sent = []
        self.turn = 0

    async def send(self, raw):
        event = json.loads(raw)
        self.sent.append(event)
        if 'activityEnd' in event.get('realtimeInput', {}):
            self.turn += 1
            self.events.put_nowait({'serverContent': {'interimInputTranscription': {'text': 'عندي '}}})
            self.events.put_nowait({'serverContent': {'inputTranscription': {'text': 'عندي جوج طن'}}})
            self.events.put_nowait({'serverContent': {'turnComplete': True}})

    def __aiter__(self):
        return self

    async def __anext__(self):
        return json.dumps(await self.events.get())


def binary(data):
    return {'type': 'websocket.receive', 'bytes': data}


def control(kind):
    return {'type': 'websocket.receive', 'text': json.dumps({'type': kind})}


class BridgeTests(unittest.IsolatedAsyncioTestCase):
    async def test_digital_silence_stops_on_activity_ack_without_generation(self):
        class SilentUpstream(Upstream):
            async def send(self, raw):
                if 'activityEnd' in json.loads(raw).get('realtimeInput', {}):
                    self.events.put_nowait({'voiceActivity': {'type': 'ACTIVITY_END'}})
        client = Client([binary(bytes(12000)), control('stop')])
        await asyncio.wait_for(bridge(client, SilentUpstream()), 2)
        self.assertEqual(client.events, [{'type': 'stopped'}])

    async def test_transcription_generation_complete_finalizes_without_turn_complete(self):
        class TranscribeUpstream(Upstream):
            async def send(self, raw):
                self.sent.append(json.loads(raw))
                if 'activityEnd' in self.sent[-1].get('realtimeInput', {}):
                    self.events.put_nowait({'serverContent': {'inputTranscription': {'text': 'test'}}})
                    self.events.put_nowait({'serverContent': {'generationComplete': True}})
        client = Client([binary(bytes(12000)), control('stop')])
        await asyncio.wait_for(bridge(client, TranscribeUpstream()), 2)
        self.assertEqual([event['type'] for event in client.events], ['final', 'stopped'])

    def test_quota_error_is_actionable_without_exposing_provider_message(self):
        error = provider_error({'error': {'code': 429,
                                          'message': 'private request data'}})
        self.assertIn('Quota Gemini', str(error))
        self.assertNotIn('private', str(error))

    async def test_stop_flushes_tail_and_waits_for_final(self):
        client = Client([binary(bytes(12000)), control('commit'), binary(bytes(480)), control('stop')])
        upstream = Upstream()
        await asyncio.wait_for(bridge(client, upstream), 2)
        self.assertEqual([e['type'] for e in client.events],
                         ['partial', 'final', 'partial', 'final', 'stopped'])
        appends = [base64.b64decode(e['realtimeInput']['audio']['data'])
                   for e in upstream.sent if 'audio' in e.get('realtimeInput', {})]
        self.assertEqual(list(map(len, appends)), [12000, 480])
        self.assertEqual(client.events[-2]['text'], 'عندي جوج طن')

    async def test_empty_stop_never_commits(self):
        client, upstream = Client([control('stop')]), Upstream()
        await bridge(client, upstream)
        self.assertEqual(upstream.sent, [])
        self.assertEqual(client.events, [{'type': 'stopped'}])

    async def test_silent_turn_completes_without_fabricating_text(self):
        class Silent(Upstream):
            async def send(self, raw):
                if 'activityEnd' in json.loads(raw).get('realtimeInput', {}):
                    self.events.put_nowait({'serverContent': {'turnComplete': True}})
        client = Client([binary(bytes(12000)), control('stop')])
        await asyncio.wait_for(bridge(client, Silent()), 2)
        self.assertEqual(client.events, [{'type': 'stopped'}])

    async def test_multiple_final_segments_and_interim_revisions(self):
        class Segments(Upstream):
            async def send(self, raw):
                if 'activityEnd' not in json.loads(raw).get('realtimeInput', {}):
                    return
                for content in [
                    {'interimInputTranscription': {'text': 'one'}},
                    {'interimInputTranscription': {'text': 'one revised'}},
                    {'inputTranscription': {'text': 'first final'}},
                    {'inputTranscription': {'text': 'second final'}},
                    {'turnComplete': True},
                ]:
                    self.events.put_nowait({'serverContent': content})
        client = Client([binary(bytes(12000)), control('stop')])
        await asyncio.wait_for(bridge(client, Segments()), 2)
        self.assertEqual([e.get('item_id') for e in client.events], ['0', '0', '0', '1', None])

    async def test_unfinalized_partial_fails_instead_of_claiming_success(self):
        class Unfinished(Upstream):
            async def send(self, raw):
                if 'activityEnd' in json.loads(raw).get('realtimeInput', {}):
                    self.events.put_nowait({'serverContent': {
                        'interimInputTranscription': {'text': 'unfinished'}, 'turnComplete': True}})
        client = Client([binary(bytes(12000)), control('stop')])
        with self.assertRaises(ProtocolError):
            await bridge(client, Unfinished())
        self.assertNotIn({'type': 'stopped'}, client.events)

    async def test_bad_frames_and_controls(self):
        for message in [binary(b'x'), binary(bytes(24002)), binary(b''),
                        control('unknown'), {'type': 'websocket.receive', 'text': '[]'},
                        {'type': 'websocket.receive', 'text': 'broken'}]:
            with self.subTest(message=str(message)[:50]):
                with self.assertRaises(ProtocolError):
                    await bridge(Client([message]), Upstream())

    async def test_disconnect_cancels_upstream_reader(self):
        await asyncio.wait_for(bridge(Client([{'type': 'websocket.disconnect'}]), Upstream()), 1)

    async def test_rejects_bulk_upload(self):
        with self.assertRaises(ProtocolError):
            await bridge(Client([binary(bytes(24000)) for _ in range(8)]), Upstream())

    def test_config_uses_live_schema_and_arabic(self):
        config = session_config()['setup']
        self.assertEqual(config['inputAudioTranscription']['languageCodes'], ['ar-EG'])
        self.assertEqual(config['generationConfig']['responseModalities'], ['TEXT'])
        self.assertTrue(config['realtimeInputConfig']['automaticActivityDetection']['disabled'])
        self.assertNotIn('systemInstruction', config)
