import assert from 'node:assert/strict';
import test from 'node:test';
import { SpeechClient } from '../../public/transcription/client.js';

test('cancel during microphone permission stops a late stream', async () => {
  let grant, stops = 0, closes = 0;
  globalThis.AudioContext = class {
    state = 'running';
    async resume() {}
    async close() { closes++; this.state = 'closed'; }
  };
  Object.defineProperty(globalThis, 'navigator', { configurable: true, value: {
    mediaDevices: { getUserMedia: () => new Promise(resolve => { grant = resolve; }) },
  }});
  const client = new SpeechClient('ws://localhost', () => {}, () => {});
  const pending = client.start();
  await Promise.resolve();
  await client.stop();
  grant({ getTracks: () => [{ stop: () => stops++ }] });
  await pending;
  assert.equal(client.state, 'idle');
  assert.equal(stops, 1);
  assert.equal(closes, 1);
});

test('slow websocket stops rather than accumulating or dropping silent gaps', () => {
  globalThis.WebSocket = { OPEN: 1 };
  const client = new SpeechClient('ws://localhost', () => {}, () => {});
  let closed = false;
  client.state = 'recording';
  client.socket = { readyState: 1, bufferedAmount: 50000, close: () => { closed = true; } };
  client.audioMessage({ type: 'audio', buffer: new ArrayBuffer(12000) });
  assert.equal(client.state, 'idle');
  assert.equal(closed, true);
});
