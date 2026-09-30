#!/usr/bin/env node
// record.js — V2.3 (Automated Scene Verification)
// V2.2 RETAINED: run isolation, clean dir, newest-mtime webm, recording-manifest.json, provenance
// V2.3 NEW:
//   [PHASE 2] scene-evidence/ directory with named screenshots + evidence_hash per scene
//   [PHASE 4] DOM evidence capture per scene (headings, buttons, alerts, URL, expected_text check)
//   [PHASE 5] scene-evidence-manifest.json and scene-timeline.json
import fs from 'fs';
import path from 'path';
import crypto from 'crypto';
import { fileURLToPath } from 'url';
import playwright from 'playwright';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

function help() {
  console.log(`Usage: node scripts/record.js --plan <path> --out <dir> [--headed] [--run-id <id>]\n\nEnvironment:\n  TUTORIAL_EMAIL / TUTORIAL_PASSWORD can supply values referenced by valueFromEnv.\n\nV2.3 Changes (adds on top of V2.2):\n  scene-evidence/ directory with per-scene screenshots + DOM evidence\n  scene-evidence-manifest.json with evidence_hash, URL, DOM data, expected_text check\n  scene-timeline.json with accurate evidence_timestamp per scene\n`);
}

function arg(name, fallback = null) {
  const i = process.argv.indexOf(name);
  return i >= 0 ? process.argv[i + 1] : fallback;
}

if (process.argv.includes('--help')) { help(); process.exit(0); }

const planPath = arg('--plan');
const outDir = arg('--out', '.tutorial-video/recording');
const runId = arg('--run-id') || null;
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

async function main() {
  const plan = JSON.parse(fs.readFileSync(planPath, 'utf8'));
  const tutorialId = plan.tutorial_id || plan.title || 'unknown-tutorial';
  const targetUser = plan.target_user || 'umum';
  const mode = plan.mode || 'feature';

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
  console.log(`[V2.3] Fresh recording directory: ${outDir}`);
  console.log(`[V2.3] Scene evidence directory: ${sceneEvidenceDir}`);

  const recordingStartTimestamp = new Date().toISOString();

  const delays = Object.assign({
    actionDelay: 800,
    sceneDelay: 1500,
    typingDelay: 40,
    postActionDelay: 1200
  }, plan.delays || {});

  const browser = await playwright.chromium.launch({
    headless: !headed,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });

  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    recordVideo: { dir: outDir, size: { width: 1920, height: 1080 } }
  });

  const page = await context.newPage();

  // Inject helper scripts for custom cursor, click ripple, and element highlight
  await page.addInitScript(() => {
    window.__injectTutorialOverlay = () => {
      if (document.getElementById('__tutorial_overlay_style')) return;
      const style = document.createElement('style');
      style.id = '__tutorial_overlay_style';
      style.innerHTML = `
        @keyframes ripple {
          0% { width: 0px; height: 0px; opacity: 0.9; }
          100% { width: 60px; height: 60px; opacity: 0; }
        }
        .__click_ripple {
          position: fixed;
          border: 3px solid #3b82f6;
          background: rgba(59, 130, 246, 0.35);
          border-radius: 50%;
          pointer-events: none;
          transform: translate(-50%, -50%);
          z-index: 999999;
          animation: ripple 0.6s ease-out forwards;
        }
        .__element_highlight {
          outline: 4px solid #3b82f6 !important;
          outline-offset: 3px !important;
          box-shadow: 0 0 15px rgba(59, 130, 246, 0.6) !important;
          transition: all 0.3s ease !important;
        }
      `;
      document.head.appendChild(style);
    };

    window.__showRipple = (x, y) => {
      window.__injectTutorialOverlay();
      const div = document.createElement('div');
      div.className = '__click_ripple';
      div.style.left = `${x}px`;
      div.style.top = `${y}px`;
      document.body.appendChild(div);
      setTimeout(() => div.remove(), 650);
    };

    window.__highlightElement = (selector) => {
      window.__injectTutorialOverlay();
      document.querySelectorAll('.__element_highlight').forEach(el => el.classList.remove('__element_highlight'));
      if (!selector) return;
      const el = document.querySelector(selector);
      if (el) el.classList.add('__element_highlight');
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

      // Check expected text in full page text
      const pageText = (document.body.innerText || '').toLowerCase();
      const textFound = (expectedTexts || []).filter(t => pageText.includes(t.toLowerCase()));
      const textMissing = (expectedTexts || []).filter(t => !pageText.includes(t.toLowerCase()));

      // Check expected selectors visibility
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
  });

  let mousePos = { x: 960, y: 540 };

  async function smoothMouseMove(targetX, targetY, steps = 15) {
    const startX = mousePos.x;
    const startY = mousePos.y;
    for (let i = 1; i <= steps; i++) {
      const x = startX + (targetX - startX) * (i / steps);
      const y = startY + (targetY - startY) * (i / steps);
      await page.mouse.move(x, y);
      await page.waitForTimeout(15);
    }
    mousePos = { x: targetX, y: targetY };
  }

  async function performAction(act, sceneInfo) {
    const actionType = act.type || sceneInfo.action || sceneInfo.cursor_action;
    const selector = act.selector || sceneInfo.locator;
    const url = act.url || sceneInfo.target_url;
    const highlight = act.highlight !== undefined ? act.highlight : sceneInfo.highlight;

    await page.evaluate(() => window.__injectTutorialOverlay && window.__injectTutorialOverlay()).catch(() => {});

    if (highlight && selector) {
      await page.evaluate((sel) => window.__highlightElement(sel), selector).catch(() => {});
      await page.waitForTimeout(400);
    }

    switch (actionType) {
      case 'goto': {
        console.log(`  -> Navigate: ${url}`);
        await page.goto(url, { waitUntil: 'networkidle' });
        await page.waitForTimeout(delays.actionDelay);
        break;
      }
      case 'click':
      case 'move-and-click': {
        if (!selector) break;
        console.log(`  -> Click: ${selector}`);
        const loc = page.locator(selector).first();
        await loc.waitFor({ state: 'visible', timeout: 10000 });
        const box = await loc.boundingBox();
        if (box) {
          const targetX = box.x + box.width / 2;
          const targetY = box.y + box.height / 2;
          await smoothMouseMove(targetX, targetY);
          await page.evaluate(({ x, y }) => window.__showRipple(x, y), { x: targetX, y: targetY });
          await page.waitForTimeout(200);
          await loc.click();
        } else {
          await loc.click();
        }
        await page.waitForTimeout(delays.postActionDelay);
        break;
      }
      case 'fill':
      case 'type': {
        if (!selector) break;
        let value = act.value;
        if (act.valueFromEnv) value = process.env[act.valueFromEnv];
        if (value === undefined) value = act.value || '';
        console.log(`  -> Type into ${selector}`);
        const loc = page.locator(selector).first();
        await loc.waitFor({ state: 'visible', timeout: 10000 });
        const box = await loc.boundingBox();
        if (box) {
          await smoothMouseMove(box.x + box.width / 2, box.y + box.height / 2);
        }
        await loc.focus();
        await loc.clear();
        for (const char of value) {
          if (loc.pressSequentially) {
            await loc.pressSequentially(char, { delay: delays.typingDelay });
          } else {
            await loc.type(char, { delay: delays.typingDelay });
          }
        }
        await page.waitForTimeout(delays.postActionDelay);
        break;
      }
      case 'select': {
        if (!selector) break;
        console.log(`  -> Select ${selector} = ${act.value}`);
        const loc = page.locator(selector).first();
        await loc.waitFor({ state: 'visible', timeout: 10000 });
        const box = await loc.boundingBox();
        if (box) {
          await smoothMouseMove(box.x + box.width / 2, box.y + box.height / 2);
          await page.evaluate(({ x, y }) => window.__showRipple(x, y), { x: box.x + box.width / 2, y: box.y + box.height / 2 });
        }
        await loc.selectOption(act.value);
        await page.waitForTimeout(delays.postActionDelay);
        break;
      }
      case 'press': {
        if (!selector) break;
        console.log(`  -> Press key ${act.key} on ${selector}`);
        await page.locator(selector).first().press(act.key);
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
        await performAction({ type: 'goto', url: scene.target_url }, scene);
      }

      if (scene.actions && scene.actions.length > 0) {
        for (const act of scene.actions) {
          await performAction(act, scene);
        }
      } else if (scene.action || scene.cursor_action) {
        await performAction({}, scene);
      } else {
        await page.waitForTimeout(scene.wait_after_action || 1500);
      }

      await page.waitForTimeout(delays.sceneDelay);

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
        zoom: scene.zoom || 1.0,
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
    scene_screenshots: sceneScreenshots,
    timing: manifestTiming,
    expected_visual_keywords: plan.expected_visual_keywords || [],
    forbidden_visual_keywords: plan.forbidden_visual_keywords || []
  };

  fs.writeFileSync(
    path.join(outDir, 'recording-manifest.json'),
    JSON.stringify(recordingManifest, null, 2)
  );

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
