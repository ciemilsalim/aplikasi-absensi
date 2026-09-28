#!/usr/bin/env node
const fs = require('fs');
const path = require('path');

function help() {
  console.log(`Usage: node scripts/record.js --plan <path> --out <dir> [--headed]\n\nEnvironment:\n  TUTORIAL_EMAIL / TUTORIAL_PASSWORD can supply values referenced by valueFromEnv.\n`);
}

function arg(name, fallback = null) {
  const i = process.argv.indexOf(name);
  return i >= 0 ? process.argv[i + 1] : fallback;
}

if (process.argv.includes('--help')) { help(); process.exit(0); }

const planPath = arg('--plan');
const outDir = arg('--out', '.tutorial-video/recording');
const headed = process.argv.includes('--headed');
if (!planPath) { help(); process.exit(2); }

async function main() {
  let playwright;
  try { playwright = require('playwright'); }
  catch (e) {
    console.error('Playwright is not installed. Run: npm install -D playwright');
    process.exit(3);
  }

  const plan = JSON.parse(fs.readFileSync(planPath, 'utf8'));
  fs.mkdirSync(outDir, { recursive: true });

  const browser = await playwright.chromium.launch({ headless: !headed });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    recordVideo: { dir: outDir, size: { width: 1920, height: 1080 } }
  });
  const page = await context.newPage();

  async function runAction(action) {
    switch (action.type) {
      case 'goto': await page.goto(action.url, { waitUntil: 'networkidle' }); break;
      case 'wait': await page.waitForTimeout(action.ms || 1000); break;
      case 'click': await page.locator(action.selector).click(); break;
      case 'fill': {
        const value = action.valueFromEnv ? process.env[action.valueFromEnv] : action.value;
        if (value === undefined) throw new Error(`Missing environment variable: ${action.valueFromEnv}`);
        await page.locator(action.selector).fill(value);
        break;
      }
      case 'select': await page.locator(action.selector).selectOption(action.value); break;
      case 'press': await page.locator(action.selector).press(action.key); break;
      case 'screenshot': await page.screenshot({ path: action.path }); break;
      default: throw new Error(`Unsupported action type: ${action.type}`);
    }
  }

  try {
    for (const scene of plan.scenes) {
      console.log(`Recording ${scene.id}: ${scene.name}`);
      for (const action of scene.actions || []) await runAction(action);
      await page.waitForTimeout(1000);
      await page.screenshot({ path: path.join(outDir, `${scene.id}.png`) });
    }
  } finally {
    await context.close();
    await browser.close();
  }

  console.log(`Recording completed: ${outDir}`);
}

main().catch(err => { console.error(err.stack || err); process.exit(1); });
