#!/usr/bin/env node
// record.js — V3.3 (Smart Focus, Zoom, Pan & Tutorial Cursor Engine)
// V2.2/V2.3 RETAINED: run isolation, clean dir, newest-mtime webm, recording-manifest.json, provenance,
//                    scene-evidence/ directory with DOM evidence, evidence_hash, scene-timeline.json
// V3.3 NEW:
//   [PHASE 6-8] High-contrast Tutorial Cursor (scale 1.8x, drop shadow, smooth movement, hover pause, click ripple)
//   [PHASE 9] Smart Highlight with rounded outline & smooth pulse
//   [PHASE 2-5, 10, 13] Bounding box extraction, adaptive zoom calculation & smart center
//   [PHASE 11] scene_focus_timeline.json and focus-manifest.json generation
import fs from 'fs';
import path from 'path';
import crypto from 'crypto';
import { fileURLToPath } from 'url';
import playwright from 'playwright';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

function help() {
  console.log(`Usage: node scripts/record.js --plan <path> --out <dir> [--headed] [--run-id <id>]

Environment:
  TUTORIAL_EMAIL / TUTORIAL_PASSWORD can supply values referenced by valueFromEnv.

V3.3 Changes (adds on top of V2.3):
  Tutorial Cursor with 1.8x scale, high contrast outline, drop shadow, smooth motion, and hover pauses
  Smart Highlight with rounded outline and soft pulse
  Per-scene bounding box extraction & adaptive zoom/pan calculation
  scene_focus_timeline.json & focus-manifest.json for coordinated render & Focus QA
`);
}

function arg(name, fallback = null) {
  const i = process.argv.indexOf(name);
  return i >= 0 ? process.argv[i + 1] : fallback;
}

if (process.argv.includes('--help')) { help(); process.exit(0); }

const planPath = arg('--plan');
const outDir = arg('--out') || arg('--out-dir') || '.tutorial-video/recording';
const runId = arg('--run-id') || null;
const timelineArg = arg('--timeline') || null;
const variantArg = arg('--variant') || null;
const headed = process.argv.includes('--headed');
if (!planPath) { help(); process.exit(2); }

// ──────────────────────────────────────────────────────────────────────
// UTILITY: compute file SHA256 hash (hex, first 16 chars)
// ──────────────────────────────────────────────────────────────────────
function fileHash(filePath) {
  if (!fs.existsSync(filePath)) return null;
  const hash = crypto.createHash('sha256');
  hash.update(fs.readFileSync(filePath));
  return hash.digest('hex').slice(0, 16);
}

// ──────────────────────────────────────────────────────────────────────
// V3.3/V3.5 SMART ZOOM CALCULATION (Adaptive Zoom based on element size & device mode)
// ──────────────────────────────────────────────────────────────────────
function calculateAdaptiveZoom(box, sceneFocus, focusDefaults, actionType, isMobile = false) {
  if (sceneFocus && sceneFocus.zoom_in !== undefined) {
    const val = parseFloat(sceneFocus.zoom_in);
    return isMobile ? Math.min(1.40, val) : val;
  }
  if (!box || box.width <= 0 || box.height <= 0) {
    return 1.0;
  }
  const minZ = (focusDefaults && focusDefaults.min_zoom) || (isMobile ? 1.10 : 1.20);
  const maxZ = isMobile ? 1.40 : ((focusDefaults && focusDefaults.max_zoom) || 1.65);
  const w = box.width;
  const h = box.height;

  let zoom = isMobile ? 1.25 : 1.35;
  if (w < 120 || h < 45) {
    zoom = isMobile ? 1.38 : 1.60;
  } else if (w < 350 || h < 80) {
    zoom = isMobile ? 1.30 : 1.48;
  } else if (w < 700 || h < 250) {
    zoom = isMobile ? 1.20 : 1.35;
  } else if (w < 1100 || h < 500) {
    zoom = isMobile ? 1.12 : 1.20;
  } else {
    zoom = 1.0; // Very large elements (tables/dashboard containers) do not need zoom
  }

  if (actionType === 'submit') {
    zoom = Math.min(maxZ, zoom + 0.08);
  }

  if (zoom > 1.0) {
    zoom = Math.max(minZ, Math.min(maxZ, zoom));
  }
  return Math.round(zoom * 100) / 100;
}

// ──────────────────────────────────────────────────────────────────────
// V3.3/V3.5 SMART CENTER CALCULATION (Avoid bottom subtitle collision)
// ──────────────────────────────────────────────────────────────────────
function calculateSmartCenter(box, zoomLevel, vWidth = 1920, vHeight = 1080, isMobile = false) {
  if (!box) {
    return { cx: Math.round(vWidth / 2), cy: Math.round(vHeight / 2), subtitle_safe: true };
  }
  let cx = box.x + box.width / 2;
  let cy = box.y + box.height / 2;

  // Clamp boundaries so camera never shows outside viewport area
  if (zoomLevel > 1.0) {
    const halfW = vWidth / (2 * zoomLevel);
    const halfH = vHeight / (2 * zoomLevel);
    cx = Math.max(halfW, Math.min(vWidth - halfW, cx));
    cy = Math.max(halfH, Math.min(vHeight - halfH, cy));
  }

  let subtitleSafe = true;
  // Subtitle safety zone: Subtitle sits at bottom
  const bottomThreshold = isMobile ? (vHeight - 140) : 850;
  if (cy > bottomThreshold && zoomLevel > 1.05) {
    cy = Math.max(vHeight / 2, cy - (isMobile ? 60 : 80));
    subtitleSafe = true;
  }

  return {
    cx: Math.round(cx),
    cy: Math.round(cy),
    subtitle_safe: subtitleSafe
  };
}

async function main() {
  const plan = JSON.parse(fs.readFileSync(planPath, 'utf8'));
  const tutorialId = plan.tutorial_id || plan.title || 'unknown-tutorial';
  const targetUser = plan.target_user || 'umum';
  const mode = plan.mode || 'feature';

  // V3.5.2 Canonical Event Timeline loading
  let canonicalTimeline = null;
  if (timelineArg && fs.existsSync(timelineArg)) {
    try {
      canonicalTimeline = JSON.parse(fs.readFileSync(timelineArg, 'utf8'));
      console.log(`[V3.5.2] Loaded canonical timeline: ${timelineArg} (hash: ${canonicalTimeline.timeline_hash || 'none'})`);
    } catch (e) {
      console.warn(`[V3.5.2] Warning: could not parse timelineArg ${timelineArg}: ${e.message}`);
    }
  }

  // Global defaults for Focus & Cursor (backward compatible, upgraded for V3.4 Cinematic Focus)
  const focusDefaults = Object.assign({
    enabled: true,
    min_zoom: 1.20,
    max_zoom: 1.65,
    zoom_in_duration: 0.9,
    hold_duration: 1.4,
    zoom_out_duration: 1.2,
    center_target: true
  }, plan.focus_defaults || {});

  const cursorDefaults = Object.assign({
    enabled: true,
    scale: 1.8,
    click_ripple: true,
    trail: false,
    hover_pause: 0.4,
    move_duration: 0.7
  }, plan.cursor_defaults || {});

  const globalFocusMode = plan.focus_mode || 'auto'; // 'auto' | 'manual' | 'off'

  // ── V2.2: ALWAYS clear the recording directory before starting ──
  if (fs.existsSync(outDir)) {
    console.log(`[V2.2] Clearing stale recording directory: ${outDir}`);
    fs.rmSync(outDir, { recursive: true, force: true });
  }
  fs.mkdirSync(outDir, { recursive: true });

  // ── V2.3: Create scene-evidence directory ──
  const sceneEvidenceDir = path.join(path.dirname(outDir), 'scene-evidence');
  if (fs.existsSync(sceneEvidenceDir)) {
    fs.rmSync(sceneEvidenceDir, { recursive: true, force: true });
  }
  fs.mkdirSync(sceneEvidenceDir, { recursive: true });

  // ── V3.3: Create focus-evidence directory ──
  const focusEvidenceDir = path.join(path.dirname(outDir), 'focus-evidence');
  if (fs.existsSync(focusEvidenceDir)) {
    fs.rmSync(focusEvidenceDir, { recursive: true, force: true });
  }
  fs.mkdirSync(focusEvidenceDir, { recursive: true });

  console.log(`[V2.3] Fresh recording directory: ${outDir}`);
  console.log(`[V2.3] Scene evidence directory: ${sceneEvidenceDir}`);
  console.log(`[V3.3] Focus evidence directory: ${focusEvidenceDir}`);
  console.log(`[V3.3] Focus mode: ${globalFocusMode} | Cursor scale: ${cursorDefaults.scale}x`);

  const recordingStartTimestamp = new Date().toISOString();

  const delays = Object.assign({
    actionDelay: 800,
    sceneDelay: 1500,
    typingDelay: 40,
    postActionDelay: 1200
  }, plan.delays || {});

  const isMobile = (plan.tutorial_mode === 'mobile') || (mode === 'mobile');
  let viewportWidth = 1920;
  let viewportHeight = 1080;
  if (isMobile) {
    const preset = plan.viewport_preset || 'iphone-standard';
    if (preset === 'iphone-small') {
      viewportWidth = 375; viewportHeight = 667;
    } else if (preset === 'android-standard') {
      viewportWidth = 412; viewportHeight = 915;
    } else { // iphone-standard or custom
      viewportWidth = plan.mobile_viewport?.width || 390;
      viewportHeight = plan.mobile_viewport?.height || 844;
    }
  }

  const browser = await playwright.chromium.launch({
    headless: !headed,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });

  const context = await browser.newContext({
    viewport: { width: viewportWidth, height: viewportHeight },
    isMobile: isMobile,
    hasTouch: isMobile,
    deviceScaleFactor: isMobile ? 2 : 1,
    recordVideo: { dir: outDir, size: { width: viewportWidth, height: viewportHeight } }
  });

  const page = await context.newPage();

  // Inject helper scripts for custom tutorial cursor, click ripple, and element highlight
  await page.addInitScript(({ cScale, cTrail, isMobileDevice, vW, vH }) => {
    window.__injectTutorialOverlay = () => {
      if (document.getElementById('__tutorial_overlay_style')) return;
      const style = document.createElement('style');
      style.id = '__tutorial_overlay_style';
      style.innerHTML = `
        @keyframes ripple {
          0% { width: 0px; height: 0px; opacity: 0.95; }
          100% { width: ${isMobileDevice ? '56px' : '70px'}; height: ${isMobileDevice ? '56px' : '70px'}; opacity: 0; }
        }
        @keyframes focusPulse {
          0% { box-shadow: 0 0 10px rgba(59, 130, 246, 0.4); }
          50% { box-shadow: 0 0 22px rgba(59, 130, 246, 0.85); }
          100% { box-shadow: 0 0 10px rgba(59, 130, 246, 0.4); }
        }
        .__click_ripple {
          position: fixed;
          border: 3.5px solid #2563eb;
          background: rgba(37, 99, 235, 0.35);
          border-radius: 50%;
          pointer-events: none;
          transform: translate(-50%, -50%);
          z-index: 9999998;
          animation: ripple 0.65s cubic-bezier(0.1, 0.8, 0.2, 1) forwards;
        }
        .__element_highlight {
          outline: 3.5px solid #2563eb !important;
          outline-offset: 4px !important;
          border-radius: 8px !important;
          animation: focusPulse 1.8s infinite ease-in-out !important;
          transition: all 0.3s ease !important;
        }
        .__cursor_trail_dot {
          position: fixed;
          width: 7px;
          height: 7px;
          border-radius: 50%;
          background: #3b82f6;
          pointer-events: none;
          z-index: 9999997;
          opacity: 0.7;
          transition: opacity 0.35s ease, transform 0.35s ease;
        }
      `;
      document.head.appendChild(style);

      // Inject Tutorial Cursor or Mobile Finger Pointer with session persistence
      if (!document.getElementById('__tutorial_cursor')) {
        const cursorDiv = document.createElement('div');
        cursorDiv.id = '__tutorial_cursor';
        let initX = Math.round(vW / 2);
        let initY = Math.round(vH / 2);
        try {
          if (window.sessionStorage) {
            const sx = window.sessionStorage.getItem('__tut_cur_x');
            const sy = window.sessionStorage.getItem('__tut_cur_y');
            if (sx !== null && sy !== null) {
              const px = parseInt(sx, 10);
              const py = parseInt(sy, 10);
              if (!isNaN(px) && !isNaN(py) && px >= 0 && py >= 0 && px <= vW && py <= vH) {
                initX = px;
                initY = py;
              }
            }
          }
        } catch (e) {}

        cursorDiv.style.cssText = `
          position: fixed;
          left: ${initX}px;
          top: ${initY}px;
          pointer-events: none;
          z-index: 9999999;
          transform: scale(${isMobileDevice ? 1.0 : cScale}) translate(${isMobileDevice ? '-16px, -16px' : '-2px, -2px'});
          filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.65));
          transition: transform 0.12s ease;
          display: block;
        `;
        if (isMobileDevice) {
          cursorDiv.innerHTML = `
            <svg width="34" height="34" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="16" cy="16" r="14" fill="#3b82f6" fill-opacity="0.25" stroke="#ffffff" stroke-width="1.5"/>
              <circle cx="16" cy="16" r="6" fill="#ffffff"/>
              <circle cx="16" cy="16" r="3.5" fill="#2563eb"/>
              <path d="M16 16 L25 33 L29 27 L37 25 Z" fill="#2563eb" stroke="#ffffff" stroke-width="2" stroke-linejoin="round"/>
            </svg>
          `;
        } else {
          cursorDiv.innerHTML = `
            <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 2L23 12L13 14L10 24L3 2Z" fill="#1d4ed8" stroke="#ffffff" stroke-width="2.2" stroke-linejoin="round"/>
            </svg>
          `;
        }
        document.body.appendChild(cursorDiv);
      }
    };

    window.__moveTutorialCursor = (x, y) => {
      window.__injectTutorialOverlay();
      const cursor = document.getElementById('__tutorial_cursor');
      if (cursor) {
        cursor.style.left = `${x}px`;
        cursor.style.top = `${y}px`;
        cursor.style.display = 'block';
      }
      try {
        if (window.sessionStorage) {
          window.sessionStorage.setItem('__tut_cur_x', Math.round(x));
          window.sessionStorage.setItem('__tut_cur_y', Math.round(y));
        }
      } catch (e) {}
      if (cTrail) {
        const dot = document.createElement('div');
        dot.className = '__cursor_trail_dot';
        dot.style.left = `${x}px`;
        dot.style.top = `${y}px`;
        document.body.appendChild(dot);
        setTimeout(() => {
          dot.style.opacity = '0';
          dot.style.transform = 'scale(0.3)';
          setTimeout(() => dot.remove(), 350);
        }, 80);
      }
    };

    window.__showRipple = (x, y) => {
      window.__injectTutorialOverlay();
      const cursor = document.getElementById('__tutorial_cursor');
      let rx = x;
      let ry = y;
      if (cursor && cursor.style.left && cursor.style.top) {
        rx = parseInt(cursor.style.left, 10);
        ry = parseInt(cursor.style.top, 10);
      }
      const div = document.createElement('div');
      div.className = '__click_ripple';
      div.style.left = `${rx}px`;
      div.style.top = `${ry}px`;
      document.body.appendChild(div);
      setTimeout(() => div.remove(), 700);

      // Subtle click feedback on cursor
      if (cursor) {
        cursor.style.transform = `scale(${cScale * 0.88}) translate(${isMobileDevice ? '-16px, -16px' : '-2px, -2px'})`;
        setTimeout(() => {
          if (cursor) cursor.style.transform = `scale(${isMobileDevice ? 1.0 : cScale}) translate(${isMobileDevice ? '-16px, -16px' : '-2px, -2px'})`;
        }, 150);
      }
    };

    window.__highlightElement = (selector) => {
      window.__injectTutorialOverlay();
      document.querySelectorAll('.__element_highlight').forEach(el => el.classList.remove('__element_highlight'));
      if (!selector) return;
      try {
        const el = document.querySelector(selector);
        if (el) el.classList.add('__element_highlight');
      } catch (e) {}
    };

    window.__clearHighlight = () => {
      document.querySelectorAll('.__element_highlight').forEach(el => el.classList.remove('__element_highlight'));
    };

    // V2.3: DOM evidence extractor
    window.__extractDOMEvidence = (expectedTexts, expectedSelectors) => {
      const headings = Array.from(document.querySelectorAll('h1,h2,h3,h4,[class*="title"],[class*="heading"]'))
        .map(el => el.textContent.trim()).filter(t => t && t.length < 200 && t.length > 2).slice(0, 8);

      const buttons = Array.from(document.querySelectorAll('button,[role="button"],a.btn,[class*="btn"]'))
        .map(el => el.textContent.trim()).filter(t => t && t.length < 80).slice(0, 12);

      const alerts = Array.from(document.querySelectorAll('[class*="alert"],[class*="success"],[class*="error"],[role="alert"],[class*="notification"],[class*="flash"]'))
        .map(el => el.textContent.trim()).filter(t => t && t.length < 300).slice(0, 5);

      const navItems = Array.from(document.querySelectorAll('nav a,[class*="sidebar"] a,[class*="menu"] a'))
        .map(el => el.textContent.trim()).filter(t => t && t.length < 60).slice(0, 15);

      const formLabels = Array.from(document.querySelectorAll('label,legend,th,[class*="label"]'))
        .map(el => el.textContent.trim()).filter(t => t && t.length < 100).slice(0, 15);

      const pageText = (document.body.innerText || '').toLowerCase();
      const textFound = (expectedTexts || []).filter(t => pageText.includes(t.toLowerCase()));
      const textMissing = (expectedTexts || []).filter(t => !pageText.includes(t.toLowerCase()));

      const selectorStates = (expectedSelectors || []).map(sel => {
        try {
          const el = document.querySelector(sel);
          return { selector: sel, found: !!el, visible: el ? el.offsetParent !== null : false };
        } catch(e) { return { selector: sel, found: false, visible: false }; }
      });

      return { headings, buttons, alerts, navItems, formLabels, textFound, textMissing, selectorStates, pageTitle: document.title };
    };

    window.__getForbiddenText = (forbiddenWords) => {
      const pageText = (document.body.innerText || '').toLowerCase();
      return (forbiddenWords || []).filter(w => pageText.includes(w.toLowerCase()));
    };
  }, { cScale: cursorDefaults.scale, cTrail: cursorDefaults.trail, isMobileDevice: isMobile, vW: viewportWidth, vH: viewportHeight });

  let mousePos = { x: Math.round(viewportWidth / 2), y: Math.round(viewportHeight / 2) };

  async function smoothMouseMove(targetX, targetY, durationMs = 600, hoverPauseMs = 300) {
    const startX = mousePos.x;
    const startY = mousePos.y;
    const dist = Math.sqrt((targetX - startX) ** 2 + (targetY - startY) ** 2);
    // Dynamic physics-based steps (~60fps, 16ms per step)
    const steps = Math.max(14, Math.round(durationMs / 16.0));
    const stepInterval = Math.max(12, Math.round(durationMs / steps));

    for (let i = 1; i <= steps; i++) {
      const rawT = i / steps;
      // Smoothstep physics: acceleration (0-15%), cruising (15-80%), deceleration (80-100%)
      const ease = rawT * rawT * (3 - 2 * rawT);
      const x = Math.round(startX + (targetX - startX) * ease);
      const y = Math.round(startY + (targetY - startY) * ease);
      await page.mouse.move(x, y);
      await page.evaluate(([cx, cy]) => window.__moveTutorialCursor(cx, cy), [x, y]).catch(() => {});
      await page.waitForTimeout(stepInterval);
    }
    mousePos = { x: targetX, y: targetY };
    if (hoverPauseMs > 0) {
      await page.waitForTimeout(hoverPauseMs);
    }
  }

  // Active scene focus timeline entries & V3.4/V3.5 Interactive Audio Effects & Typing Events Timeline
  const sceneFocusTimeline = {};
  const focusManifestList = [];
  const audioEffectsEvents = [];
  const typingEventsList = [];

  // V3.5.1: Real Mouse Click Policy & Audio Authenticity Metadata
  const audioMetaPath = path.join(__dirname, '..', 'resources', 'audio', 'metadata.json');
  let audioMeta = {};
  if (fs.existsSync(audioMetaPath)) {
    try { audioMeta = JSON.parse(fs.readFileSync(audioMetaPath, 'utf8')); } catch (e) {}
  }
  const mouseClickAsset = (audioMeta.assets && audioMeta.assets['mouse-click.wav']) || { source_type: 'synthetic' };

  let configuredClickSource = plan.click_source || plan.click_sound_mode;
  if (!configuredClickSource) {
    configuredClickSource = isMobile ? 'mobile_tap' : 'recorded_mouse';
  }

  let effectiveClickSource = configuredClickSource;
  let clickSourceWarning = null;
  let clickSourceType = 'synthetic';

  if (configuredClickSource === 'recorded_mouse' || configuredClickSource === 'physical_mouse') {
    if (mouseClickAsset.source_type === 'recorded') {
      effectiveClickSource = 'recorded_mouse';
      clickSourceType = 'recorded';
    } else {
      effectiveClickSource = 'synthetic_ui';
      clickSourceType = 'synthetic_fallback';
      clickSourceWarning = 'REAL MOUSE AUDIO NOT AVAILABLE - USING SYNTHETIC FALLBACK';
      console.warn(`\n[WARN] ==================================================`);
      console.warn(`[WARN] REAL MOUSE AUDIO NOT AVAILABLE`);
      console.warn(`[WARN] USING SYNTHETIC FALLBACK`);
      console.warn(`[WARN] (mouse-click.wav source_type is '${mouseClickAsset.source_type}')`);
      console.warn(`[WARN] ==================================================\n`);
    }
  } else if (configuredClickSource === 'synthetic_ui' || configuredClickSource === 'ui_click') {
    effectiveClickSource = 'synthetic_ui';
    clickSourceType = 'synthetic';
  } else if (configuredClickSource === 'off') {
    effectiveClickSource = 'off';
    clickSourceType = 'off';
  } else if (configuredClickSource === 'mobile_tap') {
    effectiveClickSource = 'mobile_tap';
    clickSourceType = ((audioMeta.assets && audioMeta.assets['tap.wav']) || {}).source_type || 'synthetic';
  }

  const clickSoundMode = effectiveClickSource;

  async function performAction(act, sceneInfo, sceneId, sceneStartSec, startTime) {
    const actionType = act.type || sceneInfo.action || sceneInfo.cursor_action;
    const selector = act.selector || sceneInfo.locator;
    const url = act.url || sceneInfo.target_url;
    const highlight = act.highlight !== undefined ? act.highlight : (sceneInfo.highlight !== undefined ? sceneInfo.highlight : true);

    const sceneFocus = sceneInfo.focus || {};
    const sceneCursor = Object.assign({}, cursorDefaults, sceneInfo.cursor || {});
    const isFocusDisabled = globalFocusMode === 'off' || sceneFocus.enabled === false;

    await page.evaluate(() => window.__injectTutorialOverlay && window.__injectTutorialOverlay()).catch(() => {});

    let targetBox = null;
    let targetSelector = selector || sceneFocus.target;

    // Focus Region support
    if (sceneFocus.mode === 'region' && sceneFocus.x !== undefined && sceneFocus.width !== undefined) {
      targetBox = {
        x: sceneFocus.x,
        y: sceneFocus.y,
        width: sceneFocus.width,
        height: sceneFocus.height
      };
    } else if (targetSelector) {
      try {
        const loc = page.locator(targetSelector).first();
        if (await loc.count() > 0) {
          await loc.waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});
          targetBox = await loc.boundingBox().catch(() => null);
        }
      } catch (e) {}
    }

    if (highlight && targetSelector && !isFocusDisabled) {
      await page.evaluate((sel) => window.__highlightElement(sel), targetSelector).catch(() => {});
      await page.waitForTimeout(300);
    }

    const preActionMs = Date.now() - startTime;

    switch (actionType) {
      case 'goto': {
        console.log(`  -> Navigate: ${url}`);
        await page.goto(url, { waitUntil: 'networkidle' });
        await page.waitForTimeout(delays.actionDelay);
        break;
      }
      case 'click':
      case 'tap':
      case 'move-and-click': {
        if (!targetSelector) break;
        console.log(`  -> ${isMobile ? 'Tap' : 'Click'}: ${targetSelector}`);
        const loc = page.locator(targetSelector).first();
        await loc.waitFor({ state: 'visible', timeout: 10000 });
        const box = targetBox || await loc.boundingBox();
        const targetX = box ? Math.round(box.x + box.width / 2) : Math.round(viewportWidth / 2);
        const targetY = box ? Math.round(box.y + box.height / 2) : Math.round(viewportHeight / 2);

        await smoothMouseMove(targetX, targetY, isMobile ? 14 : 20, (sceneCursor.hover_pause || 0.35) * 1000);
        // Synchronous visual ripple and audio timestamp (±50ms)
        await page.evaluate(({ x, y }) => window.__showRipple(x, y), { x: targetX, y: targetY });
        const clickSec = (Date.now() - startTime) / 1000.0;
        audioEffectsEvents.push({
          scene_id: sceneId,
          type: isMobile ? 'tap' : 'click',
          mode: clickSoundMode,
          time: Math.round(clickSec * 100) / 100,
          volume: isMobile ? 0.16 : 0.18
        });

        await loc.click();

        // Enforce result hold after click/action
        const resultWait = (actionType === 'submit' || sceneInfo.id?.includes('submit') || sceneInfo.name?.toLowerCase().includes('simpan')) ? 1600 : 900;
        await page.waitForTimeout(resultWait);
        break;
      }
      case 'double_tap': {
        if (!targetSelector) break;
        console.log(`  -> Double Tap: ${targetSelector}`);
        const loc = page.locator(targetSelector).first();
        await loc.waitFor({ state: 'visible', timeout: 10000 });
        const box = targetBox || await loc.boundingBox();
        const targetX = box ? Math.round(box.x + box.width / 2) : Math.round(viewportWidth / 2);
        const targetY = box ? Math.round(box.y + box.height / 2) : Math.round(viewportHeight / 2);

        await smoothMouseMove(targetX, targetY, 14, 200);
        // First tap
        await page.evaluate(({ x, y }) => window.__showRipple(x, y), { x: targetX, y: targetY });
        let clickSec = (Date.now() - startTime) / 1000.0;
        audioEffectsEvents.push({
          scene_id: sceneId,
          type: 'tap',
          mode: clickSoundMode,
          time: Math.round(clickSec * 100) / 100,
          volume: 0.16
        });
        await page.waitForTimeout(120);
        // Second tap
        await page.evaluate(({ x, y }) => window.__showRipple(x, y), { x: targetX, y: targetY });
        clickSec = (Date.now() - startTime) / 1000.0;
        audioEffectsEvents.push({
          scene_id: sceneId,
          type: 'tap',
          mode: clickSoundMode,
          time: Math.round(clickSec * 100) / 100,
          volume: 0.16
        });
        await loc.dblclick().catch(() => {});
        await page.waitForTimeout(delays.postActionDelay);
        break;
      }
      case 'swipe':
      case 'swipe_up':
      case 'swipe_down': {
        const dir = act.direction || (actionType === 'swipe_down' ? 'down' : 'up');
        console.log(`  -> Gesture: Swipe ${dir}`);
        const startX = targetBox ? Math.round(targetBox.x + targetBox.width / 2) : Math.round(viewportWidth / 2);
        const startY = targetBox ? Math.round(targetBox.y + targetBox.height / 2) : Math.round(viewportHeight * 0.7);
        const dist = act.distance || 280;
        const endY = dir === 'up' ? Math.max(80, startY - dist) : Math.min(viewportHeight - 80, startY + dist);

        await smoothMouseMove(startX, startY, 12, 150);
        await page.mouse.down();
        await smoothMouseMove(startX, endY, 16, 50);
        await page.mouse.up();
        await page.waitForTimeout(delays.postActionDelay);
        break;
      }
      case 'scroll': {
        const scrollDelta = act.delta || 350;
        console.log(`  -> Gesture: Scroll ${scrollDelta}px`);
        await page.evaluate((d) => window.scrollBy({ top: d, behavior: 'smooth' }), scrollDelta);
        await page.waitForTimeout(delays.postActionDelay);
        break;
      }
      case 'long_press': {
        if (!targetSelector) break;
        console.log(`  -> Gesture: Long Press ${targetSelector}`);
        const loc = page.locator(targetSelector).first();
        await loc.waitFor({ state: 'visible', timeout: 10000 });
        const box = targetBox || await loc.boundingBox();
        const targetX = box ? Math.round(box.x + box.width / 2) : Math.round(viewportWidth / 2);
        const targetY = box ? Math.round(box.y + box.height / 2) : Math.round(viewportHeight / 2);

        await smoothMouseMove(targetX, targetY, 14, 250);
        await page.evaluate(({ x, y }) => window.__showRipple(x, y), { x: targetX, y: targetY });
        const pressSec = (Date.now() - startTime) / 1000.0;
        audioEffectsEvents.push({
          scene_id: sceneId,
          type: isMobile ? 'tap' : 'click',
          mode: clickSoundMode,
          time: Math.round(pressSec * 100) / 100,
          volume: 0.18
        });
        await page.mouse.down();
        await page.waitForTimeout(800);
        await page.mouse.up();
        await page.waitForTimeout(delays.postActionDelay);
        break;
      }
      case 'fill':
      case 'type': {
        if (!targetSelector) break;
        let value = act.value;
        if (act.valueFromEnv) value = process.env[act.valueFromEnv];
        if (value === undefined) value = act.value || '';
        console.log(`  -> Type into ${targetSelector} (${value.length} chars)`);
        const loc = page.locator(targetSelector).first();
        await loc.waitFor({ state: 'visible', timeout: 10000 });
        const box = targetBox || await loc.boundingBox();
        if (box) {
          await smoothMouseMove(box.x + box.width / 2, box.y + box.height / 2, 16, 250);
        }
        await loc.focus();
        await loc.clear();

        // Actual typing interval capture
        const typeStartSec = (Date.now() - startTime) / 1000.0;
        for (const char of value) {
          if (loc.pressSequentially) {
            await loc.pressSequentially(char, { delay: delays.typingDelay });
          } else {
            await loc.type(char, { delay: delays.typingDelay });
          }
        }
        const typeEndSec = (Date.now() - startTime) / 1000.0;

        audioEffectsEvents.push({
          scene_id: sceneId,
          type: 'typing',
          start: Math.round(typeStartSec * 100) / 100,
          end: Math.round(typeEndSec * 100) / 100,
          volume: 0.08
        });

        // Dedicated typing events list for Gate 8 acoustic verification
        typingEventsList.push({
          scene_id: sceneId,
          start: Math.round(typeStartSec * 100) / 100,
          end: Math.round(typeEndSec * 100) / 100,
          target: targetSelector,
          characters: value.length
        });

        await page.waitForTimeout(delays.postActionDelay);
        break;
      }
      case 'select': {
        if (!targetSelector) break;
        console.log(`  -> Select ${targetSelector} = ${act.value}`);
        const loc = page.locator(targetSelector).first();
        await loc.waitFor({ state: 'visible', timeout: 10000 });
        const box = targetBox || await loc.boundingBox();
        if (box) {
          const targetX = Math.round(box.x + box.width / 2);
          const targetY = Math.round(box.y + box.height / 2);
          await smoothMouseMove(targetX, targetY, 16, (sceneCursor.hover_pause || 0.35) * 1000);
          await page.evaluate(({ x, y }) => window.__showRipple(x, y), { x: targetX, y: targetY });
        }
        const selectSec = (Date.now() - startTime) / 1000.0;
        audioEffectsEvents.push({
          scene_id: sceneId,
          type: isMobile ? 'tap' : 'click',
          mode: clickSoundMode,
          time: Math.round(selectSec * 100) / 100,
          volume: 0.16
        });
        await loc.selectOption(act.value);
        await page.waitForTimeout(delays.postActionDelay);
        break;
      }
      case 'press': {
        if (!targetSelector) break;
        console.log(`  -> Press key ${act.key} on ${targetSelector}`);
        await page.locator(targetSelector).first().press(act.key);
        await page.waitForTimeout(delays.postActionDelay);
        break;
      }
      case 'wait': {
        const ms = act.ms || sceneInfo.wait_after_action || 1000;
        console.log(`  -> Pause: ${ms}ms`);
        await page.waitForTimeout(ms);
        break;
      }
      default:
        console.log(`  -> Notice: Action ${actionType} handled by scene timing.`);
        break;
    }

    const postActionMs = Date.now() - startTime;
    const actionSec = (preActionMs + (postActionMs - preActionMs) / 2) / 1000.0;

    // Calculate Smart Adaptive Zoom & Smart Center
    const zoomLevel = (!isFocusDisabled && targetBox)
      ? calculateAdaptiveZoom(targetBox, sceneFocus, focusDefaults, actionType, isMobile)
      : 1.0;

    const smartCenter = (!isFocusDisabled && targetBox)
      ? calculateSmartCenter(targetBox, zoomLevel, viewportWidth, viewportHeight, isMobile)
      : { cx: Math.round(viewportWidth / 2), cy: Math.round(viewportHeight / 2), subtitle_safe: true };

    const zoomInDur = sceneFocus.zoom_in_duration || focusDefaults.zoom_in_duration || 0.9;
    const holdDur = sceneFocus.hold_duration || focusDefaults.hold_duration || 1.4;
    const zoomOutDur = sceneFocus.zoom_out_duration || focusDefaults.zoom_out_duration || 1.2;
    const resultHoldSec = (actionType === 'submit' || actionType === 'save') ? 1.6 : 0.9;
    const effectiveHold = Math.max(holdDur, resultHoldSec);

    const focusEntry = {
      scene_id: sceneId,
      focus_enabled: !isFocusDisabled && zoomLevel > 1.0,
      mode: sceneFocus.mode || (sceneFocus.x !== undefined ? 'region' : 'element'),
      target: targetSelector || null,
      target_box: targetBox ? {
        x: Math.round(targetBox.x),
        y: Math.round(targetBox.y),
        width: Math.round(targetBox.width),
        height: Math.round(targetBox.height)
      } : null,
      action_type: actionType,
      zoom_level: zoomLevel,
      timing: {
        scene_start_sec: sceneStartSec,
        zoom_in_start: Math.max(sceneStartSec, actionSec - zoomInDur - 0.2),
        zoom_in_end: Math.max(sceneStartSec + 0.1, actionSec - 0.2),
        hold_start: Math.max(sceneStartSec + 0.1, actionSec - 0.2),
        action_time: actionSec,
        hold_end: actionSec + effectiveHold,
        zoom_out_end: actionSec + effectiveHold + zoomOutDur
      },
      center: {
        cx: smartCenter.cx,
        cy: smartCenter.cy
      },
      subtitle_safe: smartCenter.subtitle_safe,
      cursor: {
        scale: sceneCursor.scale || cursorDefaults.scale,
        position: targetBox ? { x: Math.round(targetBox.x + targetBox.width / 2), y: Math.round(targetBox.y + targetBox.height / 2) } : mousePos,
        ripple: sceneCursor.click_ripple !== false
      },
      status: targetBox ? 'RESOLVED' : 'FALLBACK'
    };

    sceneFocusTimeline[sceneId] = focusEntry;
    focusManifestList.push(focusEntry);

    await page.evaluate(() => window.__clearHighlight && window.__clearHighlight()).catch(() => {});
  }

  const manifestTiming = [];
  const sceneScreenshots = [];
  const sceneEvidenceList = [];   // V2.3: per-scene evidence data
  const sceneTimeline = {};       // V2.3: scene-timeline.json
  const startTime = Date.now();

  try {
    for (const scene of plan.scenes) {
      const sceneId = scene.scene_id || scene.id;
      const sceneTitle = scene.title || scene.name;
      console.log(`Recording Scene [${sceneId}]: ${sceneTitle}`);
      const sceneStartMs = Date.now() - startTime;
      const sceneStartSec = sceneStartMs / 1000.0;

      if (scene.target_url && (!scene.actions || scene.actions.length === 0)) {
        await performAction({ type: 'goto', url: scene.target_url }, scene, sceneId, sceneStartSec, startTime);
      }

      if (scene.actions && scene.actions.length > 0) {
        for (const act of scene.actions) {
          await performAction(act, scene, sceneId, sceneStartSec, startTime);
        }
      } else if (scene.action || scene.cursor_action) {
        await performAction({}, scene, sceneId, sceneStartSec, startTime);
      } else {
        await page.waitForTimeout(scene.wait_after_action || 1500);
      }

      // ── V3.5.2 DETERMINISTIC PACING LOCK ──
      // Ensure visual recording scene duration strictly matches canonical timeline
      let canonicalScene = null;
      if (canonicalTimeline && Array.isArray(canonicalTimeline.scenes)) {
        canonicalScene = canonicalTimeline.scenes.find(s => s.scene_id === sceneId);
      }
      const plannedSceneDurSec = canonicalScene ? (canonicalScene.duration_ms / 1000.0) : (scene.duration_target || 10);
      const sceneElapsedSoFarSec = (Date.now() - startTime - sceneStartMs) / 1000.0;
      const pacingRemainingSec = plannedSceneDurSec - sceneElapsedSoFarSec;
      if (pacingRemainingSec > 0.05) {
        console.log(`  [V3.5.2 Pacing] Holding scene for ${pacingRemainingSec.toFixed(2)}s to lock deterministic timeline`);
        await page.waitForTimeout(Math.round(pacingRemainingSec * 1000));
      } else {
        await page.waitForTimeout(delays.sceneDelay || 800);
      }

      // ── SCREENSHOT ──
      const screenshotPath = path.join(outDir, `${sceneId}.png`);
      await page.screenshot({ path: screenshotPath });
      const evidenceTimestampMs = Date.now() - startTime;

      // ── V2.3 PHASE 4: DOM Evidence Capture ──
      const currentUrl = page.url();
      const expectedTexts = scene.expected_text || [];
      const expectedSelectors = (scene.expected_dom_state || {}).selectors || [];
      const forbiddenTexts = scene.forbidden_text || plan.forbidden_visual_keywords || [];

      let domEvidence = { headings: [], buttons: [], alerts: [], navItems: [], formLabels: [], textFound: [], textMissing: expectedTexts, selectorStates: [], pageTitle: '' };
      let foundForbiddenText = [];
      try {
        domEvidence = await page.evaluate(
          ([et, es]) => window.__extractDOMEvidence(et, es),
          [expectedTexts, expectedSelectors]
        );
        foundForbiddenText = await page.evaluate(
          (fw) => window.__getForbiddenText(fw),
          forbiddenTexts
        );
      } catch (e) {
        console.log(`  [V2.3] DOM evidence collection warning: ${e.message}`);
      }

      // ── V2.3: Copy screenshot to scene-evidence dir with standardized name ──
      const evidenceScreenshotPath = path.join(sceneEvidenceDir, `${sceneId}.png`);
      try {
        fs.copyFileSync(screenshotPath, evidenceScreenshotPath);
      } catch (e) {
        console.log(`  [V2.3] Warning: could not copy screenshot to scene-evidence: ${e.message}`);
      }

      const evidenceHash = fileHash(screenshotPath);

      const sceneEndMs = Date.now() - startTime;
      const sceneEndSec = sceneEndMs / 1000.0;

      const timingEntry = {
        scene_id: sceneId,
        title: sceneTitle,
        start: sceneStartSec,
        end: sceneEndSec,
        duration: sceneEndSec - sceneStartSec,
        zoom: (sceneFocusTimeline[sceneId] && sceneFocusTimeline[sceneId].zoom_level) || scene.zoom || 1.0,
        highlight: scene.highlight || false,
        duration_target: scene.duration_target || 10,
        evidence_timestamp_ms: evidenceTimestampMs,  // V2.3
        evidence_timestamp_sec: evidenceTimestampMs / 1000.0  // V2.3
      };
      manifestTiming.push(timingEntry);

      // ── V2.3: Scene Timeline entry ──
      sceneTimeline[sceneId] = {
        start: sceneStartSec,
        end: sceneEndSec,
        evidence_timestamp: evidenceTimestampMs / 1000.0,
        url: currentUrl
      };

      sceneScreenshots.push({
        scene_id: sceneId,
        title: sceneTitle,
        screenshot_path: screenshotPath,
        screenshot_exists: fs.existsSync(screenshotPath),
        screenshot_size_bytes: fs.existsSync(screenshotPath) ? fs.statSync(screenshotPath).size : 0
      });

      // ── V2.3: Scene Evidence Entry ──
      const urlOk = scene.expected_url_contains
        ? currentUrl.includes(scene.expected_url_contains)
        : true;

      const sceneEvidenceEntry = {
        scene_id: sceneId,
        title: sceneTitle,
        url: currentUrl,
        page_title: domEvidence.pageTitle,
        screenshot: evidenceScreenshotPath,
        screenshot_relative: `scene-evidence/${sceneId}.png`,
        timestamp_ms: evidenceTimestampMs,
        evidence_hash: evidenceHash,
        expected_action: scene.expected_result || '',
        expected_text: expectedTexts,
        text_found: domEvidence.textFound,
        text_missing: domEvidence.textMissing,
        expected_url_contains: scene.expected_url_contains || null,
        url_ok: urlOk,
        forbidden_text: forbiddenTexts,
        forbidden_text_found: foundForbiddenText,
        forbidden_text_clean: foundForbiddenText.length === 0,
        dom_evidence: {
          headings: domEvidence.headings,
          buttons: domEvidence.buttons.slice(0, 8),
          alerts: domEvidence.alerts,
          nav_items: domEvidence.navItems.slice(0, 10),
          form_labels: domEvidence.formLabels.slice(0, 10),
          selector_states: domEvidence.selectorStates
        },
        verification_required: scene.verification_required !== false,
        verification_status: (() => {
          if (foundForbiddenText.length > 0) return 'FAIL';
          if (!urlOk) return 'REVIEW';
          if (domEvidence.textMissing.length > domEvidence.textFound.length) return 'REVIEW';
          return 'PASS';
        })()
      };
      sceneEvidenceList.push(sceneEvidenceEntry);

      console.log(`  [V2.3] Scene Evidence: URL=${urlOk ? 'OK' : 'REVIEW'} | textFound=${domEvidence.textFound.length}/${expectedTexts.length} | forbidden=${foundForbiddenText.length === 0 ? 'CLEAN' : 'FOUND:' + foundForbiddenText.join(',')}`);
      console.log(`  [V2.3] Evidence hash: ${evidenceHash} | Status: ${sceneEvidenceEntry.verification_status}`);
      if (sceneFocusTimeline[sceneId]) {
        const f = sceneFocusTimeline[sceneId];
        console.log(`  [V3.3] Focus Engine: target=${f.target || 'none'} | zoom=${f.zoom_level}x | center=(${f.center.cx}, ${f.center.cy}) | status=${f.status}`);
      }
    }
  } finally {
    await page.waitForTimeout(1000);
    await context.close();
    await browser.close();
  }

  // ── V2.2: Find and rename LATEST webm by mtime ──
  const webmFiles = fs.readdirSync(outDir)
    .filter(f => f.endsWith('.webm'))
    .map(f => {
      const fp = path.join(outDir, f);
      return { name: f, path: fp, mtime: fs.statSync(fp).mtimeMs, size: fs.statSync(fp).size };
    })
    .sort((a, b) => b.mtime - a.mtime);

  let recordingFilePath = null;
  let recordingFileSize = 0;
  let recordingCreatedTimestamp = null;
  let recordingHash = null;

  if (webmFiles.length > 0) {
    const newestWebm = webmFiles[0];
    const targetWebm = path.join(outDir, 'raw-recording.webm');
    if (newestWebm.path !== targetWebm) {
      if (fs.existsSync(targetWebm)) fs.unlinkSync(targetWebm);
      fs.renameSync(newestWebm.path, targetWebm);
    }
    recordingFilePath = targetWebm;
    recordingFileSize = fs.statSync(targetWebm).size;
    recordingCreatedTimestamp = new Date().toISOString();
    recordingHash = fileHash(targetWebm);
    console.log(`[V2.2] Recording file: ${targetWebm} (${recordingFileSize} bytes, hash: ${recordingHash})`);
  } else {
    console.error('[V2.2] FATAL: No .webm recording file found after recording!');
    process.exit(1);
  }

  const planHash = fileHash(planPath);

  // ── V2.2: recording-manifest.json ──
  const recordingManifest = {
    run_id: runId,
    tutorial_id: tutorialId,
    target_user: targetUser,
    mode: mode,
    timestamp: recordingStartTimestamp,
    recording_completed_timestamp: recordingCreatedTimestamp,
    plan_path: planPath,
    plan_hash: planHash,
    initial_url: plan.base_url || plan.scenes?.[0]?.target_url || null,
    scene_count: plan.scenes?.length || 0,
    scene_titles: (plan.scenes || []).map(s => s.title || s.scene_id),
    expected_scenes: (plan.scenes || []).map(s => ({
      scene_id: s.scene_id || s.id,
      title: s.title,
      expected_result: s.expected_result || null
    })),
    recording_file_path: recordingFilePath,
    recording_file_size_bytes: recordingFileSize,
    recording_file_hash: recordingHash,
    recording_creation_timestamp: recordingCreatedTimestamp,
    variant: variantArg || (isMobile ? 'mobile' : 'desktop'),
    timeline_hash: canonicalTimeline?.timeline_hash || null,
    scene_screenshots: sceneScreenshots,
    timing: manifestTiming,
    expected_visual_keywords: plan.expected_visual_keywords || [],
    forbidden_visual_keywords: plan.forbidden_visual_keywords || []
  };

  fs.writeFileSync(
    path.join(outDir, 'recording-manifest.json'),
    JSON.stringify(recordingManifest, null, 2)
  );

  // ── V3.5.2: canonical-timeline.json ──
  if (canonicalTimeline) {
    fs.writeFileSync(
      path.join(outDir, 'canonical-timeline.json'),
      JSON.stringify(canonicalTimeline, null, 2)
    );
    console.log(`[V3.5.2] Persisted canonical-timeline.json into ${outDir}`);

    if (canonicalTimeline.video_timeline) {
      fs.writeFileSync(
        path.join(outDir, 'video-timeline.json'),
        JSON.stringify(canonicalTimeline.video_timeline, null, 2)
      );
      console.log(`[V3.5.2.5] Persisted video-timeline.json into ${outDir}`);
    }

    if (canonicalTimeline.narration_visual_map) {
      fs.writeFileSync(
        path.join(outDir, 'narration-visual-map.json'),
        JSON.stringify(canonicalTimeline.narration_visual_map, null, 2)
      );
      console.log(`[V3.5.2.5] Persisted narration-visual-map.json into ${outDir}`);
    }
  }

  // ── V2.2 compat: timing_manifest.json ──
  fs.writeFileSync(
    path.join(outDir, 'timing_manifest.json'),
    JSON.stringify(manifestTiming, null, 2)
  );

  // ── V2.3 PHASE 5: scene-evidence-manifest.json ──
  const overallFail = sceneEvidenceList.some(e => e.verification_status === 'FAIL');
  const overallReview = sceneEvidenceList.some(e => e.verification_status === 'REVIEW');
  const sceneEvidenceManifest = {
    run_id: runId,
    tutorial_id: tutorialId,
    target_user: targetUser,
    mode: mode,
    generated_at: new Date().toISOString(),
    overall_status: overallFail ? 'FAIL' : (overallReview ? 'REVIEW' : 'PASS'),
    scene_evidence_dir: sceneEvidenceDir,
    scenes: sceneEvidenceList
  };

  fs.writeFileSync(
    path.join(outDir, 'scene-evidence-manifest.json'),
    JSON.stringify(sceneEvidenceManifest, null, 2)
  );

  // ── V2.3 PHASE 5: scene-timeline.json ──
  fs.writeFileSync(
    path.join(outDir, 'scene-timeline.json'),
    JSON.stringify(sceneTimeline, null, 2)
  );

  // ── V3.3 PHASE 11: scene_focus_timeline.json & focus-manifest.json ──
  fs.writeFileSync(
    path.join(outDir, 'scene_focus_timeline.json'),
    JSON.stringify(sceneFocusTimeline, null, 2)
  );

  const focusManifest = {
    run_id: runId,
    tutorial_id: tutorialId,
    focus_mode: globalFocusMode,
    focus_defaults: focusDefaults,
    cursor_defaults: cursorDefaults,
    total_scenes: plan.scenes?.length || 0,
    focused_scenes: Object.values(sceneFocusTimeline).filter(f => f.focus_enabled).length,
    events: focusManifestList
  };

  fs.writeFileSync(
    path.join(outDir, 'focus-manifest.json'),
    JSON.stringify(focusManifest, null, 2)
  );

  // ── V3.4/V3.5: audio-effects-timeline.json, typing-events.json & audio-effects.json ──
  const audioEffectsTimeline = {
    run_id: runId,
    tutorial_id: tutorialId,
    click_sound_mode: clickSoundMode,
    total_events: audioEffectsEvents.length,
    events: audioEffectsEvents
  };

  fs.writeFileSync(
    path.join(outDir, 'audio-effects-timeline.json'),
    JSON.stringify(audioEffectsTimeline, null, 2)
  );

  // V3.5 Phase 5: Actual typing interval detection
  fs.writeFileSync(
    path.join(outDir, 'typing-events.json'),
    JSON.stringify(typingEventsList, null, 2)
  );

  const defaultAudioEffectsConfig = {
    click_source: configuredClickSource,
    effective_click_source: effectiveClickSource,
    click_source_type: clickSourceType,
    warning: clickSourceWarning,
    click_sound_mode: effectiveClickSource,
    click: {
      enabled: effectiveClickSource !== 'off',
      mode: effectiveClickSource,
      source_type: clickSourceType,
      file: effectiveClickSource === 'mobile_tap' ? 'resources/audio/tap.wav' : (effectiveClickSource === 'synthetic_ui' ? 'resources/audio/click.wav' : 'resources/audio/mouse-click.wav'),
      volume: isMobile ? 0.16 : 0.18
    },
    typing: {
      enabled: true,
      source_type: ((audioMeta.assets && audioMeta.assets['typing.wav']) || {}).source_type || 'synthetic',
      file: 'resources/audio/typing.wav',
      volume: 0.08
    },
    focus_transition: {
      enabled: false,
      volume: 0.04
    }
  };

  fs.writeFileSync(
    path.join(outDir, 'audio-effects.json'),
    JSON.stringify(defaultAudioEffectsConfig, null, 2)
  );

  console.log(`[V3.5] typing-events.json written (${typingEventsList.length} typing interval(s))`);
  console.log(`[V3.5] audio-effects-timeline.json written (${audioEffectsEvents.length} audio event(s), mode=${clickSoundMode})`);
  console.log(`[V3.5] audio-effects.json written`);
  console.log(`[V3.3] scene_focus_timeline.json written (${Object.keys(sceneFocusTimeline).length} scenes)`);
  console.log(`[V3.3] focus-manifest.json written (${focusManifest.focused_scenes} focused scenes)`);
  console.log(`[V2.3] scene-evidence-manifest.json written: overall_status=${sceneEvidenceManifest.overall_status}`);
  console.log(`[V2.3] scene-timeline.json written`);
  console.log(`[V2.2] recording-manifest.json written`);
  console.log(`Recording completed: ${outDir}`);

  // ── V2.3: Abort if scene evidence shows FAIL ──
  if (overallFail) {
    console.error('\n[V2.3] FATAL: One or more scenes have FAIL status in scene evidence!');
    console.error('       Forbidden visual content detected during recording.');
    console.error('       DO NOT proceed to TTS/Render. Fix the tutorial plan or application state first.');
    process.exit(5);  // exit code 5 = scene evidence FAIL
  }
}

main().catch(err => { console.error(err.stack || err); process.exit(1); });
