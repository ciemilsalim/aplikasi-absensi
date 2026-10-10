// overlay.js — TRUE CONTINUOUS BROWSER VIDEO ENGINE (V4) in-page runtime.
//
// Injected with page.addInitScript() at document_start into the MAIN world.
// Responsibilities (all driven per-frame by engine.js, never by wall-clock):
//   1. Virtual animation clock: every CSS animation / CSS transition / WAAPI
//      animation in the document is paused and its currentTime is set from the
//      engine's virtual time (vt). Combined with Playwright's fake clock
//      (Date / setTimeout / rAF / performance.now) this makes the live page
//      evolve in exact 1/60 s steps regardless of capture latency.
//   2. Pointer layer: desktop vector cursor or mobile touch indicator, ripple.
//   3. Subtitle layer: translucent glass caption (no truncation).
//   4. Native <select> picker emulation: headless Chromium does not paint OS
//      popups, so an Android-style picker sheet is drawn from the REAL option
//      list; the real value is then committed via the real <select>.
// Nothing here generates video frames — frames are captured from the live
// browser compositor by engine.js.
(() => {
  if (window.__tve) return;
  const animStart = new WeakMap();
  let host = null, root = null, els = null;

  const CSS = `
    :host { all: initial; }
    .layer { position: fixed; inset: 0; pointer-events: none; overflow: hidden; }
    .sub {
      position: absolute; left: 12px; right: 12px; display: flex; justify-content: center;
      opacity: 0; will-change: opacity, transform;
    }
    .sub.bottom { bottom: 14px; }
    .sub.top { top: 14px; }
    .sub > div {
      max-width: 100%; box-sizing: border-box; padding: 9px 14px 10px;
      background: rgba(15, 23, 42, 0.58);
      -webkit-backdrop-filter: blur(10px) saturate(140%); backdrop-filter: blur(10px) saturate(140%);
      border: 1px solid rgba(255,255,255,0.14); border-radius: 14px;
      box-shadow: 0 6px 22px rgba(2, 6, 23, 0.28);
      color: #fff; font: 600 15px/1.42 "Segoe UI", "Inter", system-ui, Roboto, sans-serif;
      text-align: center; letter-spacing: 0.1px; text-shadow: 0 1px 2px rgba(0,0,0,0.35);
      white-space: normal; overflow-wrap: anywhere;
    }
    .touch { position: absolute; left: 0; top: 0; width: 0; height: 0; will-change: transform, opacity; }
    .touch .dot {
      position: absolute; left: -15px; top: -15px; width: 30px; height: 30px; border-radius: 50%;
      background: radial-gradient(circle at 50% 45%, rgba(255,255,255,0.55), rgba(226,232,240,0.30));
      border: 2px solid rgba(255,255,255,0.95);
      box-shadow: 0 2px 10px rgba(15,23,42,0.35), inset 0 0 0 1px rgba(15,23,42,0.10);
      box-sizing: border-box;
    }
    .rings { position: absolute; inset: 0; pointer-events: none; }
    .ring {
      position: absolute; border-radius: 50%; border: 2.5px solid rgba(255,255,255,0.95);
      background: rgba(59, 130, 246, 0.18);
      box-shadow: 0 0 12px rgba(59, 130, 246, 0.45); box-sizing: border-box;
    }
    .cursor { position: absolute; left: 0; top: 0; width: 0; height: 0; will-change: transform; }
    .cursor svg { position: absolute; left: -2px; top: -1px; filter: drop-shadow(0 2px 3px rgba(0,0,0,0.35)); }
    .picker { position: absolute; inset: 0; opacity: 0; }
    .picker .scrim { position: absolute; inset: 0; background: rgba(2, 6, 23, 0.45); }
    .picker .sheet {
      position: absolute; left: 22px; right: 22px; top: 50%; background: #fff; border-radius: 18px;
      box-shadow: 0 18px 48px rgba(2,6,23,0.35); overflow: hidden;
      font: 15px/1.3 "Segoe UI", "Inter", system-ui, Roboto, sans-serif; color: #0f172a;
    }
    .picker .title { padding: 18px 20px 10px; font-weight: 700; font-size: 16px; color: #0f172a; }
    .picker .opt { display: flex; align-items: center; gap: 14px; padding: 0 20px; height: 48px; position: relative; }
    .picker .opt .rad { width: 18px; height: 18px; border-radius: 50%; border: 2px solid #94a3b8; box-sizing: border-box; flex: none; position: relative; }
    .picker .opt.sel .rad { border-color: #2563eb; }
    .picker .opt.sel .rad::after { content: ""; position: absolute; inset: 3px; border-radius: 50%; background: #2563eb; }
    .picker .opt .hl { position: absolute; inset: 0; background: rgba(37,99,235,0.10); opacity: 0; }
    .picker .foot { height: 10px; }
  `;

  function ensure() {
    if (host && host.isConnected) return true;
    const docEl = document.documentElement;
    if (!docEl) return false;
    host = document.createElement('tve-overlay');
    host.setAttribute('data-tve', '1');
    host.style.cssText = 'position:fixed!important;inset:0!important;z-index:2147483647!important;pointer-events:none!important;display:block!important;';
    root = host.attachShadow({ mode: 'open' });
    root.innerHTML = `<style>${CSS}</style>
      <div class="layer">
        <div class="picker"><div class="scrim"></div><div class="sheet"></div></div>
        <div class="rings"></div>
        <div class="touch"><div class="dot"></div></div>
        <div class="cursor"><svg width="28" height="34" viewBox="0 0 28 34"><path d="M2 1.5 L2 27 L8.6 21 L13 31.5 L17.6 29.6 L13.3 19.4 L22.4 19.4 Z" fill="#111827" stroke="#ffffff" stroke-width="2" stroke-linejoin="round"/></svg></div>
        <div class="sub bottom"><div></div></div>
      </div>`;
    docEl.appendChild(host);
    els = {
      touch: root.querySelector('.touch'), dot: root.querySelector('.touch .dot'),
      cursor: root.querySelector('.cursor'), rings: root.querySelector('.rings'),
      sub: root.querySelector('.sub'), subText: root.querySelector('.sub > div'),
      picker: root.querySelector('.picker'), sheet: root.querySelector('.picker .sheet'),
    };
    return true;
  }

  function syncAnimations(vt) {
    let n = 0;
    for (const a of document.getAnimations()) {
      // Ignore animations inside our own overlay (none are used, defensive).
      let t0 = animStart.get(a);
      if (t0 === undefined) {
        t0 = vt;
        animStart.set(a, t0);
        try { a.pause(); } catch (e) {}
      }
      try { a.currentTime = Math.max(0, (vt - t0)) * (a.playbackRate || 1); } catch (e) {}
      n++;
    }
    return n;
  }

  let lastPickerKey = '';
  function renderPicker(pk) {
    if (!pk || pk.p <= 0) { els.picker.style.opacity = '0'; return null; }
    const key = JSON.stringify([pk.title, pk.options.map(o => o.t), pk.sel]);
    if (key !== lastPickerKey) {
      lastPickerKey = key;
      els.sheet.innerHTML = `<div class="title"></div>` + pk.options.map((o, i) =>
        `<div class="opt${i === pk.sel ? ' sel' : ''}" data-i="${i}"><div class="hl"></div><div class="rad"></div><div class="lbl"></div></div>`).join('') + `<div class="foot"></div>`;
      els.sheet.querySelector('.title').textContent = pk.title || '';
      els.sheet.querySelectorAll('.opt .lbl').forEach((n, i) => { n.textContent = pk.options[i].t; });
    }
    els.sheet.querySelectorAll('.opt').forEach((n, i) => {
      n.classList.toggle('sel', i === pk.sel);
      n.querySelector('.hl').style.opacity = (i === pk.hi ? String(pk.hiA || 0) : '0');
    });
    const p = pk.p; // 0..1 eased by engine
    els.picker.style.opacity = String(Math.min(1, p * 1.25));
    const h = els.sheet.offsetHeight;
    els.sheet.style.transform = `translateY(${(-h / 2 + (1 - p) * 26).toFixed(2)}px) scale(${(0.96 + 0.04 * p).toFixed(4)})`;
    // Rects of options in CSS px (after transform), for pointer targeting.
    return Array.from(els.sheet.querySelectorAll('.opt')).map(n => {
      const r = n.getBoundingClientRect();
      return { x: r.x, y: r.y, w: r.width, h: r.height };
    });
  }

  function renderRings(rings) {
    const want = rings || [];
    while (els.rings.children.length < want.length) {
      const d = document.createElement('div'); d.className = 'ring'; els.rings.appendChild(d);
    }
    Array.from(els.rings.children).forEach((d, i) => {
      const r = want[i];
      if (!r) { d.style.opacity = '0'; return; }
      const rad = r.r;
      d.style.left = (r.x - rad).toFixed(2) + 'px';
      d.style.top = (r.y - rad).toFixed(2) + 'px';
      d.style.width = d.style.height = (rad * 2).toFixed(2) + 'px';
      d.style.opacity = r.a.toFixed(3);
    });
  }

  window.__tve = {
    // st: { vt, mobile, pointer:{x,y,a,s,press}, rings:[{x,y,r,a}], sub:{text,a,pos}, picker, scrollY, hide }
    step(st) {
      const nAnim = syncAnimations(st.vt);
      if (typeof st.scrollY === 'number') {
        window.scrollTo({ top: st.scrollY, left: 0, behavior: 'instant' });
      }
      if (!ensure()) return { ok: false, nAnim, scrollY: window.scrollY };
      host.style.visibility = st.hide ? 'hidden' : 'visible';
      const p = st.pointer || { a: 0 };
      if (st.mobile) {
        els.cursor.style.display = 'none';
        els.touch.style.display = 'block';
        els.touch.style.opacity = String(p.a || 0);
        els.touch.style.transform = `translate(${(p.x || 0).toFixed(3)}px, ${(p.y || 0).toFixed(3)}px)`;
        const s = p.s || 1;
        els.dot.style.transform = `scale(${s.toFixed(4)})`;
        const press = p.press || 0;
        els.dot.style.background = `radial-gradient(circle at 50% 45%, rgba(255,255,255,${(0.55 + 0.25 * press).toFixed(3)}), rgba(203,213,225,${(0.30 + 0.35 * press).toFixed(3)}))`;
      } else {
        els.touch.style.display = 'none';
        els.cursor.style.display = 'block';
        els.cursor.style.opacity = String(p.a == null ? 1 : p.a);
        const s = p.s || 1;
        els.cursor.style.transform = `translate(${(p.x || 0).toFixed(3)}px, ${(p.y || 0).toFixed(3)}px) scale(${s.toFixed(4)})`;
      }
      renderRings(st.rings);
      const sub = st.sub || { a: 0 };
      if (els.subText.textContent !== (sub.text || '')) els.subText.textContent = sub.text || '';
      els.sub.className = 'sub ' + (sub.pos === 'top' ? 'top' : 'bottom');
      els.sub.style.opacity = String(sub.text ? (sub.a || 0) : 0);
      const optRects = renderPicker(st.picker);
      const subRect = els.subText.getBoundingClientRect();
      return { ok: true, nAnim, scrollY: window.scrollY, optRects, subRect: { y: subRect.y, h: subRect.height }, url: location.href };
    },
  };
})();
