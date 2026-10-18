#!/usr/bin/env node
// engine.js — TRUE CONTINUOUS BROWSER VIDEO ENGINE (V4)
//
// PIPELINE B (VIDEO): the ONLY source of video frames.
//   * The real application runs in Chromium. Every output frame k is a fresh
//     capture of the live browser compositor at virtual time vt = k * 1000/60 ms.
//   * Time is virtualised so capture latency never leaks into the video:
//       - Playwright fake clock  -> Date, setTimeout/Interval, rAF, performance.now
//       - overlay.js              -> every CSS animation/transition/WAAPI animation
//     The page therefore advances exactly 16.667 ms between consecutive frames,
//     giving constant 60 FPS pacing that does not depend on laptop speed.
//   * Interactions are REAL browser input dispatched between frames:
//     pointer moves (desktop hover), touch taps, per-character key events,
//     per-frame scroll offsets, real <select> commits, real navigations/fetches.
//   * No still image is ever repeated, transformed, zoomed, panned or blended
//     to fake motion. Frames are piped straight into H.264 (libx264, CFR 60).
//
// PIPELINE A (EVIDENCE): separate clean screenshots (overlay hidden) written to
//   <run>/evidence/. They are never read back by the video pipeline.
//
// Usage:
//   node engine.js --plan plan.json --narration narration.json --run-dir DIR --ffmpeg PATH [--scenes a,b] [--headed]

'use strict';
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawn } = require('child_process');

function arg(name, fb = null) {
  const i = process.argv.indexOf('--' + name);
  return i >= 0 && i + 1 < process.argv.length ? process.argv[i + 1] : fb;
}
const planPath = arg('plan');
const narrPath = arg('narration');
const runDir = arg('run-dir');
const ffmpegPath = arg('ffmpeg', 'ffmpeg');
const sceneFilter = arg('scenes') ? arg('scenes').split(',').map(s => s.trim()).filter(Boolean) : null;
const headed = process.argv.includes('--headed');
if (!planPath || !narrPath || !runDir) {
  console.error('usage: engine.js --plan P --narration N --run-dir D --ffmpeg F');
  process.exit(2);
}

function resolvePlaywright() {
  const custom = arg('playwright') || process.env.PLAYWRIGHT_PATH;
  if (custom && fs.existsSync(custom)) return require(custom);
  const candidates = [
    path.join(process.cwd(), 'node_modules', 'playwright'),
    arg('workspace-root') ? path.join(arg('workspace-root'), 'node_modules', 'playwright') : null,
    path.resolve(__dirname, '../../../../..', 'node_modules', 'playwright'),
  ].filter(Boolean);
  for (const c of candidates) {
    try { if (fs.existsSync(c)) return require(c); } catch (_) {}
  }
  try { return require('playwright'); }
  catch (e) {
    console.error('[V4] Could not resolve playwright. Checked candidates and NODE_PATH:', e.message);
    process.exit(1);
  }
}
const playwright = resolvePlaywright();

const plan = JSON.parse(fs.readFileSync(planPath, 'utf8').replace(/^\uFEFF/, ''));
const narration = JSON.parse(fs.readFileSync(narrPath, 'utf8').replace(/^\uFEFF/, ''));
const FPS = plan.fps || 60;
const FRAME_MS = 1000 / FPS;

const modeArg = arg('mode');
const MODE = modeArg || plan.mode || 'mobile';
const MOBILE = MODE === 'mobile';

const VW = MOBILE ? (plan.viewport?.width || 414) : (plan.desktop_viewport?.width || (plan.viewport && plan.viewport.width >= 1024 ? plan.viewport.width : 1920));
const VH = MOBILE ? (plan.viewport?.height || 736) : (plan.desktop_viewport?.height || (plan.viewport && plan.viewport.width >= 1024 ? plan.viewport.height : 1080));
const OUT_W = MOBILE ? (plan.target_resolution ? plan.target_resolution[0] : 1080) : (plan.desktop_target_resolution ? plan.desktop_target_resolution[0] : 1920);
const DSF = OUT_W / VW;
const OUT_H = Math.round(VH * DSF);
const REACTION_MS = plan.reaction_delay_ms ?? 300;
const SETTLE_MS = 180;

const recDir = path.join(runDir, 'recording');
const evDirArg = arg('evidence-dir');
const evDir = evDirArg ? evDirArg : path.join(runDir, 'evidence', MODE);
fs.mkdirSync(recDir, { recursive: true });
fs.mkdirSync(evDir, { recursive: true });

const videoOutArg = arg('video-out');
const videoOut = videoOutArg ? videoOutArg : path.join(recDir, `continuous-${MODE}-60fps.mp4`);
const eventsOut = arg('events-out', path.join(recDir, `events-${MODE}.json`));
const manifestOut = arg('manifest-out', path.join(recDir, `recording-manifest-${MODE}.json`));
const framesOut = arg('frames-out', path.join(recDir, `frames-${MODE}.jsonl`));


// ── deterministic helpers ──────────────────────────────────────────────
function seedOf(str) { let h = 2166136261; for (const c of str) { h ^= c.charCodeAt(0); h = Math.imul(h, 16777619); } return h >>> 0; }
function rng(seed) { let a = seed >>> 0; return () => { a |= 0; a = (a + 0x6D2B79F5) | 0; let t = Math.imul(a ^ (a >>> 15), 1 | a); t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t; return ((t ^ (t >>> 14)) >>> 0) / 4294967296; }; }
const minJerk = u => u <= 0 ? 0 : u >= 1 ? 1 : u * u * u * (10 - 15 * u + 6 * u * u);
const easeOutCubic = u => 1 - Math.pow(1 - Math.max(0, Math.min(1, u)), 3);
const easeInOutSine = u => -(Math.cos(Math.PI * Math.max(0, Math.min(1, u))) - 1) / 2;
const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

// ── state ──────────────────────────────────────────────────────────────
let page, cdp, ff, ffDone;
let frame = 0;
let clockMs = 0;
const framesLog = [];
const events = [];
const sceneWindows = [];
const warnings = [];
const st = {
  vt: 0, mobile: MOBILE,
  pointer: { x: VW * 0.62, y: VH * 0.62, a: MOBILE ? 0 : 1, s: 1, press: 0 },
  rings: [], sub: { text: '', a: 0, pos: 'bottom' }, picker: null, scrollY: undefined, hide: false,
};
let ringDefs = [];        // {x,y,t0}
let subTrack = null;      // {start, end, chunks:[{t,start}], pos}
let lastInfo = { scrollY: 0, optRects: null };
let phase = 'idle';
let lastPointerShownVt = -1e9;
const vt = () => frame * FRAME_MS;

function log(...a) { console.log('[V4]', ...a); }
function ev(type, extra = {}) { const e = Object.assign({ type, vt_ms: +vt().toFixed(3), frame }, extra); events.push(e); return e; }

// ── encoder ────────────────────────────────────────────────────────────
function startEncoder() {
  const args = [
    '-y', '-hide_banner', '-loglevel', 'error',
    '-f', 'image2pipe', '-framerate', String(FPS), '-c:v', 'png', '-i', 'pipe:0',
    '-vf', `scale=${OUT_W}:${OUT_H}:flags=lanczos:out_color_matrix=bt709:out_range=tv,format=yuv420p`,
    '-c:v', 'libx264', '-preset', 'slow', '-crf', '14', '-profile:v', 'high', '-tune', 'animation',
    '-g', String(FPS * 2), '-bf', '2',
    '-colorspace', 'bt709', '-color_primaries', 'bt709', '-color_trc', 'bt709', '-color_range', 'tv',
    '-r', String(FPS), '-fps_mode', 'cfr', '-movflags', '+faststart', videoOut,
  ];
  ff = spawn(ffmpegPath, args, { stdio: ['pipe', 'inherit', 'inherit'] });
  ffDone = new Promise((res, rej) => ff.on('close', c => c === 0 ? res() : rej(new Error('ffmpeg exit ' + c))));
}
function writeFrame(buf) {
  return new Promise(res => { if (ff.stdin.write(buf)) res(); else ff.stdin.once('drain', res); });
}

// ── per-frame core ─────────────────────────────────────────────────────
function updateRings(t) {
  const DUR = 480;
  ringDefs = ringDefs.filter(r => t - r.t0 <= DUR);
  st.rings = ringDefs.map(r => {
    const u = (t - r.t0) / DUR;
    return { x: r.x, y: r.y, r: (MOBILE ? 9 : 7) + (MOBILE ? 24 : 20) * easeOutCubic(u), a: 0.9 * (1 - easeOutCubic(u)) };
  });
}
function updateSubtitle(t) {
  if (!subTrack) { st.sub.a = 0; return; }
  const { start, end, chunks, pos } = subTrack;
  st.sub.pos = pos;
  if (t < start - 120 || t > end + 450) { st.sub.a = 0; if (t > end + 450) subTrack = null; return; }
  let cur = chunks[0];
  for (const c of chunks) if (t >= c.start) cur = c;
  st.sub.text = cur.t;
  const fin = clamp((t - (start - 120)) / 150, 0, 1);
  const fout = clamp((end + 450 - t) / 180, 0, 1);
  st.sub.a = Math.min(fin, fout);
}

async function advanceClockTo(ms) {
  const target = Math.round(ms);
  const d = target - clockMs;
  if (d <= 0) return;
  for (let tries = 0; tries < 3; tries++) {
    try { await page.clock.runFor(d); clockMs = target; return; }
    catch (e) { await new Promise(r => setTimeout(r, 30)); }
  }
  // Document in transit: fake clock will resume from its persisted state.
  clockMs = target;
}

async function stepPage(extra = {}) {
  const payload = Object.assign({}, st, extra);
  for (let tries = 0; tries < 4; tries++) {
    try {
      const r = await page.evaluate(s => (window.__tve ? window.__tve.step(s) : null), payload);
      if (r) return r;
      return null;
    } catch (e) { await new Promise(r => setTimeout(r, 25)); }
  }
  return null;
}

async function tick(mutate) {
  const t = vt();
  await advanceClockTo(t);
  if (mutate) await mutate(t);
  updateRings(t);
  updateSubtitle(t);
  st.vt = t;
  const info = await stepPage();
  if (info) lastInfo = info;
  st.scrollY = undefined;
  const shot = await cdp.send('Page.captureScreenshot', { format: 'png', optimizeForSpeed: true });
  const buf = Buffer.from(shot.data, 'base64');
  await writeFrame(buf);
  framesLog.push({
    f: frame, vt: +t.toFixed(3), src: 'cdp_live_capture', ph: phase,
    px: +(st.pointer.x * DSF).toFixed(3), py: +(st.pointer.y * DSF).toFixed(3), pa: +st.pointer.a.toFixed(3),
    sy: info ? info.scrollY : null, overlay: !!(info && info.ok), anim: info ? info.nAnim : null,
    sub: st.sub.a > 0.01 ? st.sub.pos : null,
  });
  if (st.pointer.a > 0.01) lastPointerShownVt = t;
  frame++;
  if (frame % 300 === 0) log(`frame ${frame} (${(t / 1000).toFixed(2)}s)`);
  return info;
}

async function holdUntil(vtEnd, mutate) { while (vt() < vtEnd - 1e-6) await tick(mutate); }
async function hold(ms, mutate) { await holdUntil(vt() + ms, mutate); }

// ── evidence pipeline (A) ──────────────────────────────────────────────
async function evidence(name) {
  try {
    await stepPage({ hide: true });
    const shot = await cdp.send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(evDir, name + '.png'), Buffer.from(shot.data, 'base64'));
    await stepPage({ hide: false });
  } catch (e) { warnings.push('evidence ' + name + ': ' + e.message); }
}

// ── geometry ───────────────────────────────────────────────────────────
async function bbox(selector, timeout = 8000) {
  const t0 = Date.now();
  while (Date.now() - t0 < timeout) {
    const loc = page.locator(selector);
    const count = await loc.count().catch(() => 0);
    for (let i = 0; i < count; i++) {
      const nth = loc.nth(i);
      const vis = await nth.isVisible().catch(() => false);
      if (vis) {
        const b = await nth.boundingBox().catch(() => null);
        if (b && b.width > 0 && b.height > 0) return b;
      }
    }
    await new Promise(r => setTimeout(r, 60));
  }
  throw new Error('no visible bbox for ' + selector);
}
function subtitleReserve() { return subTrack && subTrack.pos === 'bottom' ? 120 : 0; }

// ── pointer motion ─────────────────────────────────────────────────────
async function fadePointer(to, ms) {
  const from = st.pointer.a; const t0 = vt();
  await holdUntil(t0 + ms, t => { st.pointer.a = from + (to - from) * easeInOutSine((t - t0) / ms); });
  st.pointer.a = to;
}

async function moveTo(x, y, opts = {}) {
  phase = 'move';
  let fx = st.pointer.x, fy = st.pointer.y;
  const appear = MOBILE && st.pointer.a < 0.05;
  if (appear) {
    // Thumb enters from its natural rest region (below-right of the target).
    fx = clamp(x + 34, 24, VW - 24); fy = clamp(y + 96, 40, VH - 30);
    st.pointer.x = fx; st.pointer.y = fy;
  } else if (MOBILE && vt() - lastPointerShownVt > 1500) {
    fx = clamp(x + 34, 24, VW - 24); fy = clamp(y + 96, 40, VH - 30);
  }
  const dx = x - fx, dy = y - fy, d = Math.hypot(dx, dy);
  const e = ev('move_start', { from: [fx * DSF, fy * DSF], to: [x * DSF, y * DSF], dist_px: +(d * DSF).toFixed(1) });
  if (d < 1.5) { st.pointer.x = x; st.pointer.y = y; if (appear) await fadePointer(1, 160); return; }
  const dur = clamp(300 + 165 * Math.log2(1 + d / 24), 380, 1150);
  const ux = dx / d, uy = dy / d, nx = -uy, ny = ux;
  const arc = Math.min(MOBILE ? 12 : 18, d * 0.07) * (dx >= 0 ? 1 : -1);
  const ov = Math.min(2.4, d * 0.012);
  const tx = x + ux * ov, ty = y + uy * ov;
  const t0 = vt();
  const a0 = st.pointer.a;
  await holdUntil(t0 + dur, async t => {
    const u = (t - t0) / dur; const s = minJerk(u);
    const bow = Math.sin(Math.PI * s) * arc;
    st.pointer.x = fx + (tx - fx) * s + nx * bow;
    st.pointer.y = fy + (ty - fy) * s + ny * bow;
    if (appear) st.pointer.a = a0 + (1 - a0) * clamp(u / 0.22, 0, 1);
    if (!MOBILE) await page.mouse.move(st.pointer.x, st.pointer.y);
  });
  // micro-settle (150–220 ms): bleed off the tiny overshoot with an ease-out.
  phase = 'settle';
  const sx = st.pointer.x, sy = st.pointer.y, t1 = vt();
  await holdUntil(t1 + SETTLE_MS, async t => {
    const s = easeOutCubic((t - t1) / SETTLE_MS);
    st.pointer.x = sx + (x - sx) * s; st.pointer.y = sy + (y - sy) * s;
    if (!MOBILE) await page.mouse.move(st.pointer.x, st.pointer.y);
  });
  st.pointer.x = x; st.pointer.y = y; st.pointer.a = 1;
  e.move_ms = +dur.toFixed(1); e.settle_ms = SETTLE_MS;
  ev('move_end', { at: [x * DSF, y * DSF] });
}

async function tapAt(x, y, opts = {}) {
  phase = 'tap';
  const PRESS = 80, RELEASE = 130;
  const t0 = vt();
  ringDefs.push({ x, y, t0: vt() });
  await holdUntil(t0 + PRESS, t => { const u = easeOutCubic((t - t0) / PRESS); st.pointer.s = 1 - 0.20 * u; st.pointer.press = u; });
  // REAL input — dispatched between frames, response captured continuously.
  const e = ev('tap', { at: [x * DSF, y * DSF], label: opts.label || null, bbox: opts.bbox || null });
  if (opts.dispatch !== false) {
    if (MOBILE) await page.touchscreen.tap(x, y);
    else { await page.mouse.move(x, y); await page.mouse.down(); await page.mouse.up(); }
  }
  if (opts.after) await opts.after();
  const t1 = vt();
  await holdUntil(t1 + RELEASE, t => { const u = easeOutCubic((t - t1) / RELEASE); st.pointer.s = 0.80 + 0.20 * u; st.pointer.press = 1 - u; });
  st.pointer.s = 1; st.pointer.press = 0;
  phase = 'hold';
  return e;
}

// ── scrolling (real browser scroll offset, per frame) ──────────────────
async function scrollBy(delta, opts = {}) {
  const maxY = await page.evaluate(() => Math.max(0, document.scrollingElement.scrollHeight - innerHeight));
  const from = lastInfo.scrollY || 0;
  const to = clamp(from + delta, 0, maxY);
  const d = to - from;
  if (Math.abs(d) < 2) return;
  phase = 'scroll';
  const dur = clamp(460 + Math.abs(d) * 0.9, 520, 1150);
  const e = ev('scroll_start', { from_y: from, to_y: to, dur_ms: +dur.toFixed(1) });
  let fingerX = VW * 0.66, y0 = d > 0 ? Math.min(VH * 0.74, VH * 0.42 + d) : Math.max(VH * 0.26, VH * 0.58 + d);
  if (MOBILE) {
    // finger lands, then drags the content (content follows the finger exactly)
    st.pointer.x = fingerX; st.pointer.y = y0;
    if (st.pointer.a < 0.9) await fadePointer(1, 140);
    const tp = vt(); await holdUntil(tp + 70, t => { const u = easeOutCubic((t - tp) / 70); st.pointer.s = 1 - 0.12 * u; st.pointer.press = u; });
  }
  const t0 = vt();
  await holdUntil(t0 + dur, t => {
    const s = minJerk((t - t0) / dur);
    st.scrollY = from + d * s;
    if (MOBILE) { st.pointer.y = y0 - d * s; st.pointer.x = fingerX + Math.sin(Math.PI * s) * 4; }
  });
  st.scrollY = to;
  await tick();
  if (MOBILE) {
    const tr = vt(); await holdUntil(tr + 110, t => { const u = easeOutCubic((t - tr) / 110); st.pointer.s = 0.88 + 0.12 * u; st.pointer.press = 1 - u; });
    await fadePointer(0, 200);
  }
  phase = 'hold';
  ev('scroll_end', { y: lastInfo.scrollY });
}

async function ensureVisible(selector) {
  const b = await bbox(selector);
  const top = 70, bottom = VH - 28 - subtitleReserve();
  if (b.y >= top && b.y + b.height <= bottom) return;
  const want = VH * 0.42 - b.height / 2;
  await scrollBy(b.y - want);
}

// ── semantic steps ─────────────────────────────────────────────────────
async function stepTap(s) {
  await ensureVisible(s.target);
  const b = await bbox(s.target);
  const x = b.x + b.width * (s.ax ?? 0.5), y = b.y + b.height * (s.ay ?? 0.5);
  await moveTo(x, y);
  await tapAt(x, y, { label: s.target, bbox: [b.x * DSF, b.y * DSF, b.width * DSF, b.height * DSF] });
}

async function stepMove(s) {
  if (s.target) {
    await ensureVisible(s.target);
    const b = await bbox(s.target);
    const x = b.x + b.width * (s.ax ?? 0.5), y = b.y + b.height * (s.ay ?? 0.5);
    await moveTo(x, y);
  } else if (typeof s.x === 'number' && typeof s.y === 'number') {
    await moveTo(s.x, s.y);
  }
  if (s.hold_ms) {
    phase = 'hold';
    await hold(s.hold_ms);
  }
}

async function stepType(s) {
  const loc = page.locator(s.target).first();
  const focused = await loc.evaluate(el => document.activeElement === el).catch(() => false);
  if (!focused) await stepTap({ target: s.target, ax: s.ax ?? 0.3 });
  phase = 'hold';
  await hold(MOBILE ? 220 : 160);
  const isSensitive = /password|secret|pin|token|key/i.test(s.target || '') || s.mask === true;
  const r = rng(seedOf((s.target || '') + s.text));
  const tb = await page.locator(s.target).first().boundingBox();
  const e = ev('type_start', { target: s.target, text_len: s.text.length, bbox: tb ? [tb.x * DSF, tb.y * DSF, tb.width * DSF, tb.height * DSF] : null });
  let tNext = vt();
  const fadeFrom = vt();
  for (let i = 0; i < s.text.length; i++) {
    const ch = s.text[i];
    await holdUntil(tNext, t => { if (MOBILE && st.pointer.a > 0) st.pointer.a = Math.max(0, 1 - (t - fadeFrom) / 260); });
    await page.keyboard.type(ch);
    ev('key', { ch: isSensitive ? '*' : ch, i });
    let dt = 78 + r() * 70;
    if (ch === ' ') dt += 55;
    if (/[@.]/.test(ch)) dt += 45;
    if (r() < 0.08) dt += 140;
    tNext = vt() + dt;
  }
  await holdUntil(tNext, t => { if (MOBILE && st.pointer.a > 0) st.pointer.a = Math.max(0, 1 - (t - fadeFrom) / 260); });
  e.end_vt_ms = +vt().toFixed(3);
  ev('type_end', { target: s.target });
}

async function stepSelect(s) {
  await ensureVisible(s.target);
  const loc = page.locator(s.target).first();
  const b = await bbox(s.target);
  const x = b.x + b.width * 0.5, y = b.y + b.height * 0.5;
  await moveTo(x, y);
  await tapAt(x, y, { label: s.target, dispatch: false, after: async () => { await loc.focus(); } });
  const all = await loc.evaluate(el => Array.from(el.options).map(o => ({ v: o.value, t: o.text.trim(), dis: o.disabled || o.value === '' })));
  const usable = all.filter(o => !o.dis);
  let ti = usable.findIndex(o => o.v === String(s.value));
  if (ti < 0) throw new Error('select value not found: ' + s.value);
  const MAXV = 7; let w0 = clamp(ti - 3, 0, Math.max(0, usable.length - MAXV));
  const win = usable.slice(w0, w0 + MAXV); const wi = ti - w0;
  const curVal = await loc.inputValue();
  const curIdx = win.findIndex(o => o.v === curVal);
  st.picker = { title: s.picker_title || 'Pilih', options: win.map(o => ({ t: o.t })), sel: curIdx, hi: -1, hiA: 0, p: 0 };
  phase = 'dropdown';
  ev('dropdown_open', { options: win.length });
  const t0 = vt();
  await holdUntil(t0 + 240, t => { st.picker.p = easeOutCubic((t - t0) / 240); });
  st.picker.p = 1;
  await hold(380);
  const rects = lastInfo.optRects;
  const rc = rects && rects[wi] ? rects[wi] : { x: VW * 0.2, y: VH * 0.5, w: VW * 0.6, h: 48 };
  const ox = rc.x + Math.min(rc.w * 0.42, 150), oy = rc.y + rc.h / 2;
  await moveTo(ox, oy);
  st.picker.hi = wi;
  const th = vt();
  await tapAt(ox, oy, {
    label: 'option:' + s.value, dispatch: false,
    after: async () => { await loc.selectOption(String(s.value)); st.picker.sel = wi; ev('select_commit', { value: String(s.value) }); },
  });
  await holdUntil(th + 320, t => { st.picker.hiA = clamp(1 - (t - th - 120) / 200, 0, 1); });
  const t1 = vt();
  await holdUntil(t1 + 200, t => { st.picker.p = 1 - easeOutCubic((t - t1) / 200); });
  st.picker = null;
  ev('dropdown_close');
}

async function stepWaitFor(s) {
  phase = 'ui_response';
  const maxTimeout = s.budget_ms ?? 8000;
  const minHold = s.min_hold_ms ?? 200;
  const settleMs = s.settle_ms ?? 350;
  const t0 = vt();
  const e = ev('wait_for', { selector: s.selector, budget_ms: maxTimeout });

  // Brief initial hold so click/tap animation frames register cleanly
  if (minHold > 0) {
    await hold(minHold);
  }

  // Poll in virtual time until target is visible or maxTimeout exceeded
  const deadline = Date.now() + 15000;
  let ok = false;
  while (Date.now() < deadline && (vt() - t0) < maxTimeout) {
    const loc = page.locator(s.selector);
    const count = await loc.count().catch(() => 0);
    for (let i = 0; i < count; i++) {
      if (await loc.nth(i).isVisible().catch(() => false)) {
        ok = true;
        break;
      }
    }
    if (ok) break;
    await tick();
  }

  if (!ok) {
    const loc = page.locator(s.selector);
    const count = await loc.count().catch(() => 0);
    for (let i = 0; i < count; i++) {
      if (await loc.nth(i).isVisible().catch(() => false)) { ok = true; break; }
    }
  }

  e.satisfied = ok;
  if (!ok) throw new Error('wait_for not satisfied: ' + s.selector);

  // Natural settle time once the target element is visible
  if (settleMs > 0) {
    await hold(settleMs);
  }
  e.extended_ms = +(Math.max(0, vt() - t0 - maxTimeout)).toFixed(1);
  e.duration_ms = +(vt() - t0).toFixed(1);
}

function resolveStepForMode(s, isMobile) {
  const stp = Object.assign({}, s);
  if (isMobile) {
    if (s.mobile_do) stp.do = s.mobile_do;
    if (s.mobile_target) stp.target = s.mobile_target;
    if (s.mobile_selector) stp.selector = s.mobile_selector;
    if (s.mobile_text) stp.text = s.mobile_text;
  } else {
    if (s.desktop_do) stp.do = s.desktop_do;
    if (s.desktop_target) stp.target = s.desktop_target;
    if (s.desktop_selector) stp.selector = s.desktop_selector;
    if (s.desktop_text) stp.text = s.desktop_text;
  }
  return stp;
}

async function runStep(rawS) {
  const s = resolveStepForMode(rawS, MOBILE);
  if (s.do === 'skip' || s.skip === true) return;
  switch (s.do) {
    case 'tap': return stepTap(s);
    case 'click': return stepTap(s);
    case 'move':
    case 'hover':
    case 'move_pointer': return stepMove(s);
    case 'type': return stepType(s);
    case 'select': return stepSelect(s);
    case 'scroll': return scrollBy(s.by ?? s.dy ?? 0);
    case 'scroll_to': return ensureVisible(s.target);
    case 'wait_for': return stepWaitFor(s);
    case 'hold': phase = 'hold'; return hold(s.ms);
    case 'pointer_hide': return fadePointer(0, s.ms || 200);
    default: throw new Error('unknown step ' + s.do);
  }
}

// ── subtitles from real TTS word boundaries ────────────────────────────
function buildChunks(words, text, maxChars) {
  if (!words || !words.length) return [{ t: text, start: 0 }];
  const chunks = []; let cur = [], curStart = 0;
  const flush = () => { if (cur.length) chunks.push({ t: cur.join(' '), start: curStart }); cur = []; };
  for (const w of words) {
    const cand = cur.concat([w.w]).join(' ');
    if (cur.length && cand.length > maxChars) flush();
    if (!cur.length) curStart = w.s;
    cur.push(w.w);
    if (/[.!?]$/.test(w.w) && cur.join(' ').length > maxChars * 0.45) flush();
  }
  flush();
  return chunks;
}

// ── main ───────────────────────────────────────────────────────────────
(async () => {
  const browser = await playwright.chromium.launch({ headless: !headed, args: ['--hide-scrollbars', '--force-color-profile=srgb', '--font-render-hinting=none'] });
  const context = await browser.newContext({
    viewport: { width: VW, height: VH }, deviceScaleFactor: DSF, isMobile: MOBILE, hasTouch: MOBILE,
    locale: 'id-ID', timezoneId: 'Asia/Makassar', colorScheme: plan.color_scheme || 'light', reducedMotion: 'no-preference',
  });
  await context.addInitScript({ path: path.join(__dirname, 'overlay.js') });
  page = await context.newPage();
  cdp = await context.newCDPSession(page);
  try { await cdp.send('Emulation.setFocusEmulationEnabled', { enabled: true }); } catch (e) {}

  // Setup (not recorded): navigate & optional pre-steps
  const T0 = Date.now();
  await page.clock.install({ time: T0 });
  await page.clock.pauseAt(T0 + 1000);
  for (const rawS of (plan.setup || [])) {
    const s = resolveStepForMode(rawS, MOBILE);
    if (s.do === 'skip' || s.skip === true) continue;
    if (s.do === 'goto') await page.goto(s.url, { waitUntil: 'load', timeout: 30000 });
    else if (s.do === 'fill') await page.locator(s.target).first().fill(s.value);
    else if (s.do === 'click' || s.do === 'tap') await page.locator(s.target).first().click();
    else if (s.do === 'wait_for') {
      const deadline = Date.now() + 20000;
      let ok = false;
      while (Date.now() < deadline) {
        const loc = page.locator(s.selector);
        const count = await loc.count().catch(() => 0);
        for (let i = 0; i < count; i++) {
          if (await loc.nth(i).isVisible().catch(() => false)) { ok = true; break; }
        }
        if (ok) break;
        await page.clock.runFor(100);
      }
      if (!ok) throw new Error('setup wait_for not satisfied: ' + s.selector);
    }
    await page.clock.runFor(400);
  }
  await page.clock.runFor(800); // let entrance animations finish before frame 0

  startEncoder();
  log(`recording ${OUT_W}x${OUT_H} @ ${FPS}fps | viewport ${VW}x${VH} dsf ${DSF.toFixed(6)} | mobile=${MOBILE}`);
  const wall0 = Date.now();

  const scenes = plan.scenes.filter(s => !sceneFilter || sceneFilter.includes(s.scene_id));
  for (let si = 0; si < scenes.length; si++) {
    const sc = scenes[si];
    const nr = narration.scenes[sc.scene_id];
    if (!nr) throw new Error('missing narration for ' + sc.scene_id);
    const sStart = vt();
    phase = 'narration';
    ev('scene_start', { scene_id: sc.scene_id });
    // subtitle position: avoid covering the scene's interaction targets
    let pos = 'bottom';
    for (const stp of (sc.steps || [])) {
      if (!stp.target) continue;
      const vis = await page.locator(stp.target).first().isVisible().catch(() => false);
      if (!vis) continue;
      const b = await page.locator(stp.target).first().boundingBox().catch(() => null);
      if (b && b.y + b.height > VH - 130 && b.y + b.height <= VH) pos = 'top';
    }
    if (sc.subtitle_position) pos = sc.subtitle_position;
    const nStart = vt() + (sc.narration_lead_ms ?? 200);
    const nEnd = nStart + nr.duration_ms;
    const chunks = buildChunks(nr.words, nr.text, MOBILE ? 64 : 90).map(c => ({ t: c.t, start: nStart + c.start }));
    subTrack = { start: nStart, end: nEnd, chunks, pos };
    await evidence(`${String(si + 1).padStart(2, '0')}_${sc.scene_id}_pre`);
    ev('narration_start', { scene_id: sc.scene_id, audio: nr.audio, duration_ms: nr.duration_ms, at_ms: +nStart.toFixed(3) });
    await holdUntil(nEnd);
    ev('narration_end', { scene_id: sc.scene_id });
    await hold(REACTION_MS);
    const aStart = vt();
    ev('action_start', { scene_id: sc.scene_id });
    for (const stp of (sc.steps || [])) await runStep(stp);
    if (MOBILE && st.pointer.a > 0.05) {
      const tf = vt() + 380; await holdUntil(tf); await fadePointer(0, 220);
    }
    phase = 'result_hold';
    ev('action_end', { scene_id: sc.scene_id });
    const aEnd = vt();
    await hold(sc.result_hold_ms ?? (si === scenes.length - 1 ? 2200 : 900));
    await evidence(`${String(si + 1).padStart(2, '0')}_${sc.scene_id}_result`);
    sceneWindows.push({ scene_id: sc.scene_id, start_ms: +sStart.toFixed(3), narration_start_ms: +nStart.toFixed(3), narration_end_ms: +nEnd.toFixed(3), action_start_ms: +aStart.toFixed(3), action_end_ms: +aEnd.toFixed(3), end_ms: +vt().toFixed(3) });
    ev('scene_end', { scene_id: sc.scene_id });
  }

  ff.stdin.end();
  await ffDone;
  await browser.close();
  const wallS = (Date.now() - wall0) / 1000;
  const sha = crypto.createHash('sha256').update(fs.readFileSync(videoOut)).digest('hex');
  const framesJsonl = framesLog.map(f => JSON.stringify(f)).join('\n') + '\n';
  const eventsJson = JSON.stringify({ fps: FPS, frame_ms: FRAME_MS, dsf: DSF, viewport: [VW, VH], output: [OUT_W, OUT_H], mode: MODE, scenes: sceneWindows, events }, null, 2);

  fs.writeFileSync(framesOut, framesJsonl);
  fs.writeFileSync(eventsOut, eventsJson);
  // Also write legacy default names if different
  if (path.resolve(framesOut) !== path.resolve(path.join(recDir, 'frames.jsonl'))) {
    fs.writeFileSync(path.join(recDir, 'frames.jsonl'), framesJsonl);
  }
  if (path.resolve(eventsOut) !== path.resolve(path.join(recDir, 'events.json'))) {
    fs.writeFileSync(path.join(recDir, 'events.json'), eventsJson);
  }

  const manifest = {
    engine: 'TRUE_CONTINUOUS_BROWSER_VIDEO_V4',
    mode: MODE,
    capture_method: 'Per-virtual-frame CDP Page.captureScreenshot of the live Chromium compositor; fake clock + virtual animation timeline',
    video_pipeline: 'B (continuous browser capture) -> libx264 CFR',
    evidence_pipeline: 'A (clean screenshots in evidence/, never used as video frames)',
    fps: FPS, frames: frame, duration_ms: +(frame * FRAME_MS).toFixed(3),
    frame_sources: { cdp_live_capture: framesLog.filter(f => f.src === 'cdp_live_capture').length, still_image: 0, repeated: 0, interpolated: 0 },
    mobile: MOBILE, viewport: [VW, VH], dsf: DSF, output: [OUT_W, OUT_H],
    video: path.relative(runDir, videoOut).replace(/\\/g, '/'), video_sha256: sha,
    wall_clock_capture_s: +wallS.toFixed(1), capture_speed_x_realtime: +((frame * FRAME_MS / 1000) / wallS).toFixed(3),
    scenes: sceneWindows.map(s => s.scene_id), warnings,
    created_at: new Date().toISOString(),
  };

  fs.writeFileSync(manifestOut, JSON.stringify(manifest, null, 2));
  if (path.resolve(manifestOut) !== path.resolve(path.join(recDir, 'recording-manifest.json'))) {
    fs.writeFileSync(path.join(recDir, 'recording-manifest.json'), JSON.stringify(manifest, null, 2));
  }
  log(`[${MODE}] done: ${frame} frames (${(frame / FPS).toFixed(2)}s) in ${wallS.toFixed(1)}s wall; warnings=${warnings.length}`);
})().catch(async e => {
  console.error('[V4] FATAL', e && e.stack || e);
  try { if (ff) ff.stdin.end(); } catch (_) {}
  process.exit(1);
});
