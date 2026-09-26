import assert from 'node:assert/strict';
import test from 'node:test';
import { resolveEndpoint } from '../../public/transcription/endpoint.js';

test('local PHP and standalone pages use the IPv4 backend', () => {
  for (const origin of ['http://localhost:8000', 'http://127.0.0.1:8000', 'http://localhost:8001', 'http://localhost', 'http://[::1]:8000']) {
    assert.equal(resolveEndpoint(new URL(origin)), 'ws://127.0.0.1:8001/ws/transcribe');
  }
});
test('HTTPS always uses TLS and defaults to the same-origin proxy', () => {
  assert.equal(resolveEndpoint(new URL('https://localhost:8443')), 'wss://localhost:8443/ws/transcribe');
  assert.equal(resolveEndpoint(new URL('https://wasla.example')), 'wss://wasla.example/ws/transcribe');
  assert.equal(resolveEndpoint(new URL('https://wasla.example'), 'ws://voice.example/ws/transcribe'), 'wss://voice.example/ws/transcribe');
});
test('public configuration supports absolute and relative proxy endpoints', () => {
  assert.equal(resolveEndpoint(new URL('http://localhost:8000'), ' ws://127.0.0.1:9000/ws/transcribe '), 'ws://127.0.0.1:9000/ws/transcribe');
  assert.equal(resolveEndpoint(new URL('https://wasla.example/public/'), '/voice'), 'wss://wasla.example/voice');
  assert.equal(resolveEndpoint(new URL('https://wasla.example'), 'https://voice.example/ws/transcribe'), 'wss://voice.example/ws/transcribe');
});
test('invalid protocols and embedded credentials are rejected', () => {
  for (const value of ['javascript:alert(1)', 'ws://user:secret@localhost/ws', 'ws://localhost/ws#fragment']) {
    assert.throws(() => resolveEndpoint(new URL('http://localhost:8000'), value));
  }
});
