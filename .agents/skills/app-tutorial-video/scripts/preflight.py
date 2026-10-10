#!/usr/bin/env python
"""
preflight.py — Pre-flight DOM selector & route validation engine.
Verifies URL reachability, element visibility, and selector accuracy before recording.
"""

import argparse
import json
import os
import subprocess
import sys
from typing import Any, Dict, List, Optional, Tuple


def get_node_path() -> str:
    return "node"


PREFLIGHT_JS = """
const fs = require('fs');
const path = require('path');

function arg(name, fb = null) {
  const i = process.argv.indexOf('--' + name);
  return i >= 0 && i + 1 < process.argv.length ? process.argv[i + 1] : fb;
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
    console.error(JSON.stringify({ error: 'Playwright not resolved: ' + e.message }));
    process.exit(1);
  }
}

const playwright = resolvePlaywright();
const planPath = arg('plan');
const timeoutMs = parseInt(arg('timeout', '10000'), 10);

(async () => {
  const rawPlan = fs.readFileSync(planPath, 'utf8').replace(/^\uFEFF/, '');
  const plan = JSON.parse(rawPlan);
  const browser = await playwright.chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: plan.viewport || { width: 1280, height: 720 },
    isMobile: plan.mode === 'mobile'
  });
  const page = await context.newPage();
  const results = { passed: true, checks: [], errors: [] };

  try {
    for (const s of (plan.setup || [])) {
      if (s.do === 'goto') {
        try {
          await page.goto(s.url, { waitUntil: 'domcontentloaded', timeout: timeoutMs });
          results.checks.push({ step: 'setup:goto', target: s.url, status: 'OK' });
        } catch (e) {
          results.passed = false;
          results.errors.push({ step: 'setup:goto', target: s.url, error: e.message });
        }
      } else if (s.do === 'fill') {
        try {
          await page.locator(s.target).first().fill(s.value);
          results.checks.push({ step: 'setup:fill', target: s.target, status: 'OK' });
        } catch (e) {
          results.passed = false;
          results.errors.push({ step: 'setup:fill', target: s.target, error: e.message });
        }
      } else if (s.do === 'click') {
        try {
          await page.locator(s.target).first().click();
          results.checks.push({ step: 'setup:click', target: s.target, status: 'OK' });
        } catch (e) {
          results.passed = false;
          results.errors.push({ step: 'setup:click', target: s.target, error: e.message });
        }
      } else if (s.do === 'wait_for') {
        try {
          await page.locator(s.selector).first().waitFor({ state: 'visible', timeout: timeoutMs });
          results.checks.push({ step: 'setup:wait_for', target: s.selector, status: 'OK' });
        } catch (e) {
          results.passed = false;
          results.errors.push({ step: 'setup:wait_for', target: s.selector, error: e.message });
        }
      }
    }

    for (const sc of (plan.scenes || [])) {
      for (const st of (sc.steps || [])) {
        const target = st.target || st.selector;
        if (!target) continue;
        try {
          const loc = page.locator(target).first();
          const vis = await loc.isVisible({ timeout: 2000 }).catch(() => false);
          results.checks.push({
            scene: sc.scene_id,
            action: st.do,
            target,
            status: vis ? 'VISIBLE' : 'NOT_CURRENTLY_VISIBLE'
          });
        } catch (e) {
          results.errors.push({ scene: sc.scene_id, action: st.do, target, error: e.message });
        }
      }
    }
  } catch (err) {
    results.passed = false;
    results.errors.push({ global: err.message });
  } finally {
    await browser.close();
  }

  console.log(JSON.stringify(results, null, 2));
})();
"""


def validate_plan_selectors(plan_path: str, workspace_root: Optional[str] = None) -> Dict[str, Any]:
    """Execute headless Node script to test selectors against live page."""
    plan_path = os.path.abspath(plan_path)
    if workspace_root:
        workspace_root = os.path.abspath(workspace_root)
    here = os.path.dirname(os.path.abspath(__file__))
    temp_script = os.path.join(here, "_temp_preflight.cjs")
    with open(temp_script, "w", encoding="utf-8") as f:
        f.write(PREFLIGHT_JS)

    try:
        env = os.environ.copy()
        if "NODE_PATH" not in env:
            # Add workspace node_modules and skill root
            ws_nm = os.path.join(workspace_root or os.getcwd(), "node_modules")
            parent_nm = os.path.abspath(os.path.join(here, "..", "..", "..", "..", "node_modules"))
            env["NODE_PATH"] = os.pathsep.join([p for p in [ws_nm, parent_nm] if os.path.isdir(p)])

        cmd = ["node", temp_script, "--plan", plan_path]
        if workspace_root:
            cmd += ["--workspace-root", workspace_root]

        res = subprocess.run(cmd, capture_output=True, text=True, env=env, timeout=30)
        if res.returncode != 0:
            return {
                "passed": False,
                "error": res.stderr or res.stdout,
                "checks": []
            }
        return json.loads(res.stdout)
    except Exception as e:
        return {"passed": False, "error": str(e), "checks": []}
    finally:
        if os.path.isfile(temp_script):
            try:
                os.remove(temp_script)
            except Exception:
                pass


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Preflight DOM validation")
    parser.add_argument("--plan", required=True)
    parser.add_argument("--workspace-root", default=None)
    args = parser.parse_args()
    report = validate_plan_selectors(args.plan, args.workspace_root)
    print(json.dumps(report, indent=2))
    sys.exit(0 if report.get("passed", False) else 1)
