import assert from 'node:assert/strict';
import test from 'node:test';
import vm from 'node:vm';
import fs from 'node:fs';
import { Transcript } from '../../public/transcription/transcript.js';

function worklet() {
  let Processor;
  const events = [];
  const context = {
    AudioWorkletProcessor: class { constructor() { this.port = { postMessage: e => events.push(e) }; } },
    registerProcessor: (_, cls) => { Processor = cls; },
    Float32Array, ArrayBuffer, DataView, Math,
  };
  vm.runInNewContext(fs.readFileSync(new URL('../../public/transcription/pcm-worklet.js', import.meta.url), 'utf8'), context);
  const processor = new Processor();
  processor.port.onmessage({ data: 'start' });
  return { processor, events };
}

test('PCM is 250 ms, signed little endian, with a flushed tail', () => {
  const { processor, events } = worklet();
  processor.process([[new Float32Array(6000).fill(-1)]]);
  assert.equal(events[0].buffer.byteLength, 12000);
  assert.equal(new DataView(events[0].buffer).getInt16(0, true), -32768);
  processor.process([[new Float32Array(128).fill(1)]]);
  processor.port.onmessage({ data: 'stop' });
  assert.equal(events[1].buffer.byteLength, 256);
  assert.equal(new DataView(events[1].buffer).getInt16(0, true), 32767);
  assert.equal(events.at(-1).type, 'flushed');
  processor.process([[new Float32Array(6000)]]);
  assert.equal(events.length, 3);
});

test('VAD commits after speech and 500 ms silence; reset drops stale audio', () => {
  const { processor, events } = worklet();
  processor.process([[new Float32Array(6000).fill(.2)]]);
  processor.process([[new Float32Array(12000)]]);
  assert.equal(events.at(-1).type, 'commit');
  processor.process([[new Float32Array(128)]]);
  processor.port.onmessage({ data: 'reset' });
  processor.port.onmessage({ data: 'stop' });
  assert.equal(events.at(-1).type, 'flushed');
  assert.equal(events.filter(e => e.type === 'audio').length, 3);
});

test('out-of-order finals replace deltas and follow committed turn order', () => {
  const text = new Transcript();
  text.apply({ type: 'final', item_id: 'b', text: 'جوج طن' });
  text.apply({ type: 'committed', item_id: 'b', previous_item_id: 'a' });
  text.apply({ type: 'delta', item_id: 'a', text: 'عن' });
  text.apply({ type: 'final', item_id: 'a', text: 'عندي' });
  text.apply({ type: 'delta', item_id: 'a', text: 'ignored' });
  assert.equal(text.text(), 'عندي جوج طن');
});

test('worklet bounds queued audio when the main thread stalls', () => {
  const { processor, events } = worklet();
  processor.process([[new Float32Array(6000 * 10).fill(.2)]]);
  assert.equal(events.filter(e => e.type === 'audio').length, 4);
  assert.equal(events.at(-1).type, 'overflow');
  assert.equal(processor.enabled, false);
});

test('Gemini interim revisions replace text and final segments append once', () => {
  const text = new Transcript();
  text.apply({ type: 'partial', item_id: '0', text: 'عند' });
  text.apply({ type: 'partial', item_id: '0', text: 'عندي جوج' });
  assert.equal(text.text(), 'عندي جوج');
  text.apply({ type: 'final', item_id: '0', text: 'عندي جوج طن' });
  text.apply({ type: 'partial', item_id: '0', text: 'stale' });
  text.apply({ type: 'final', item_id: '1', text: 'ديال مطيشة' });
  assert.equal(text.text(), 'عندي جوج طن ديال مطيشة');
});
