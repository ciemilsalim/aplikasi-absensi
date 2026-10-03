#!/usr/bin/env python
# test_cursor_suite.py -- V3.5.2.1 Master Verification Suite
# Orchestrates all V3.5.2.1 tests for BOTH desktop AND mobile:
# 1. Cursor continuity (frame-by-frame)
# 2. Click A/V sync (numerical)
# 3. Typing sync (numerical)
# 4. Variant audio isolation + negative tests
# 5. Audio fingerprint (SHA-256)
# 6. A/V contact sheets (frame evidence)
# 7. Timeline dependency audit
# Reports final verdict per acceptance checklist.
import json, os, subprocess, sys, datetime

if sys.stdout and hasattr(sys.stdout, 'reconfigure'):
    try: sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    except: pass

SCRIPT_DIR    = os.path.dirname(os.path.abspath(__file__))
WORKSPACE_ROOT = os.path.abspath(os.path.join(SCRIPT_DIR, '..', '..', '..', '..'))
SKILL_ROOT    = os.path.dirname(SCRIPT_DIR)
TV_DIR        = os.path.join(WORKSPACE_ROOT, '.tutorial-video')
RUNS_DIR      = os.path.join(TV_DIR, 'runs')
import argparse
p_suite = argparse.ArgumentParser(description='V3.5.2.1 Master Verification Suite')
p_suite.add_argument('--desktop-run', help='Path or name of desktop run')
p_suite.add_argument('--mobile-run', help='Path or name of mobile run')
p_suite.add_argument('--output-dir', help='Output directory for evidence')
suite_args, _ = p_suite.parse_known_args()

def find_latest_run(substr):
    if not os.path.isdir(RUNS_DIR): return None
    matches = sorted([d for d in os.listdir(RUNS_DIR) if substr in d], reverse=True)
    return matches[0] if matches else None

DESKTOP_RUN = suite_args.desktop_run or find_latest_run('desktop') or 'run-20261003-parent-leave-v36-desktop'
MOBILE_RUN  = suite_args.mobile_run or find_latest_run('mobile') or 'run-20261003-parent-leave-v36-mobile'
OUTPUT_DIR  = suite_args.output_dir or os.path.join(TV_DIR, 'run-20261003-parent-leave-v36')

def run_script(script_name, args_list, label):
    script = os.path.join(SCRIPT_DIR, script_name)
    cmd = [sys.executable, script] + args_list
    print(f'\n[Suite] Running: {label}')
    res = subprocess.run(cmd, cwd=WORKSPACE_ROOT, text=True, capture_output=False)
    ok = res.returncode == 0
    print(f'[Suite] {label}: {"PASS" if ok else "FAIL"} (exit={res.returncode})')
    return ok

def main():
    print('=' * 70)
    print('V3.5.2.1 MASTER VERIFICATION SUITE')
    print(f'Started: {datetime.datetime.now().isoformat()}')
    print('=' * 70)

    desktop_run = DESKTOP_RUN if os.path.isabs(DESKTOP_RUN) else os.path.join(RUNS_DIR, DESKTOP_RUN)
    mobile_run  = MOBILE_RUN if os.path.isabs(MOBILE_RUN) else os.path.join(RUNS_DIR, MOBILE_RUN)

    if not os.path.isdir(desktop_run):
        print(f'[ERROR] Desktop run not found: {desktop_run}')
        sys.exit(1)
    if not os.path.isdir(mobile_run):
        print(f'[ERROR] Mobile run not found: {mobile_run}')
        sys.exit(1)

    desktop_video   = os.path.join(desktop_run, 'desktop', 'video', 'final-tutorial.mp4')
    desktop_rec_dir = os.path.join(desktop_run, 'desktop', 'recording')
    mobile_video    = os.path.join(mobile_run, 'mobile', 'video', 'final-tutorial.mp4')
    mobile_rec_dir  = os.path.join(mobile_run, 'mobile', 'recording')

    os.makedirs(OUTPUT_DIR, exist_ok=True)
    desktop_out = os.path.join(OUTPUT_DIR, 'desktop')
    mobile_out  = os.path.join(OUTPUT_DIR, 'mobile')
    shared_out  = os.path.join(OUTPUT_DIR, 'shared')
    os.makedirs(desktop_out, exist_ok=True)
    os.makedirs(mobile_out, exist_ok=True)
    os.makedirs(shared_out, exist_ok=True)

    results = {}

    # ── 1. Cursor Continuity ─────────────────────────────────────────────
    results['cursor_desktop'] = run_script('cursor_continuity_qa.py', [
        '--video', desktop_video, '--recording-dir', desktop_rec_dir,
        '--output-dir', desktop_out, '--variant', 'desktop'
    ], 'Cursor Continuity (Desktop)')

    results['cursor_mobile'] = run_script('cursor_continuity_qa.py', [
        '--video', mobile_video, '--recording-dir', mobile_rec_dir,
        '--output-dir', mobile_out, '--variant', 'mobile'
    ], 'Cursor Continuity (Mobile)')

    # ── 2. Click + Typing Sync ──────────────────────────────────────────
    results['click_sync_desktop'] = run_script('click_sync_qa.py', [
        '--video', desktop_video, '--recording-dir', desktop_rec_dir,
        '--output-dir', desktop_out, '--variant', 'desktop'
    ], 'Click & Typing Sync (Desktop)')

    results['click_sync_mobile'] = run_script('click_sync_qa.py', [
        '--video', mobile_video, '--recording-dir', mobile_rec_dir,
        '--output-dir', mobile_out, '--variant', 'mobile'
    ], 'Click & Typing Sync (Mobile)')

    # ── 3. Variant Audio Isolation + Negative Tests ──────────────────────
    results['audio_isolation'] = run_script('audio_isolation_qa.py', [
        '--desktop-run', desktop_run, '--mobile-run', mobile_run,
        '--output-dir', shared_out
    ], 'Variant Audio Isolation + Negative Tests')

    # ── 4. Audio Fingerprint ─────────────────────────────────────────────
    results['audio_fingerprint'] = run_script('audio_fingerprint_qa.py', [
        '--desktop-run', desktop_run, '--mobile-run', mobile_run,
        '--output-dir', shared_out, '--skill-root', SKILL_ROOT
    ], 'Audio Fingerprint (SHA-256)')

    # ── 5. A/V Contact Sheets ────────────────────────────────────────────
    results['av_sheet_desktop'] = run_script('av_contact_sheet.py', [
        '--video', desktop_video, '--recording-dir', desktop_rec_dir,
        '--output-dir', desktop_out, '--variant', 'desktop'
    ], 'A/V Contact Sheet (Desktop)')

    results['av_sheet_mobile'] = run_script('av_contact_sheet.py', [
        '--video', mobile_video, '--recording-dir', mobile_rec_dir,
        '--output-dir', mobile_out, '--variant', 'mobile'
    ], 'A/V Contact Sheet (Mobile)')

    # ── 6. Timeline Dependency Audit ────────────────────────────────────
    results['timeline_audit_desktop'] = run_script('timeline_dependency_audit.py', [
        '--run-dir', desktop_run, '--variant', 'desktop', '--output-dir', desktop_out
    ], 'Timeline Dependency Audit (Desktop)')

    results['timeline_audit_mobile'] = run_script('timeline_dependency_audit.py', [
        '--run-dir', mobile_run, '--variant', 'mobile', '--output-dir', mobile_out
    ], 'Timeline Dependency Audit (Mobile)')

    # ── FINAL VERDICT ────────────────────────────────────────────────────
    total = len(results)
    passed = sum(1 for v in results.values() if v)
    all_pass = passed == total

    checklist = [
        ('cursor_desktop',    'Cursor frame continuity verified (Desktop)'),
        ('cursor_mobile',     'Cursor frame continuity verified (Mobile)'),
        ('click_sync_desktop','Click timing verified numerically (Desktop)'),
        ('click_sync_mobile', 'Click timing verified numerically (Mobile)'),
        ('audio_isolation',   'Desktop audio isolated + Mobile audio isolated + Negative tests pass'),
        ('audio_fingerprint', 'Audio SHA-256 fingerprint verified'),
        ('av_sheet_desktop',  'Visual contact sheets generated (Desktop)'),
        ('av_sheet_mobile',   'Visual contact sheets generated (Mobile)'),
        ('timeline_audit_desktop', 'Timeline dependency audited (Desktop)'),
        ('timeline_audit_mobile',  'Timeline dependency audited (Mobile)'),
    ]

    print('\n' + '=' * 70)
    print('V3.5.2.1 ACCEPTANCE CHECKLIST')
    print('=' * 70)
    for key, label in checklist:
        icon = '[PASS]' if results.get(key) else '[FAIL]'
        print(f'  {icon} {label}')

    print('\n' + '=' * 70)
    print(f'RESULT: {passed}/{total} tests passed')
    print(f'VERDICT: {"VERIFIED" if all_pass else "NEEDS VERIFICATION"}')
    print('=' * 70)

    # Save summary
    summary = {
        'suite': 'V3.5.2.1 Master Verification Suite',
        'run_at': datetime.datetime.now().isoformat(),
        'desktop_run': DESKTOP_RUN,
        'mobile_run': MOBILE_RUN,
        'results': results,
        'passed': passed,
        'total': total,
        'verdict': 'VERIFIED' if all_pass else 'NEEDS VERIFICATION',
        'output_dir': OUTPUT_DIR,
    }
    summary_path = os.path.join(OUTPUT_DIR, 'v3521-suite-summary.json')
    with open(summary_path, 'w', encoding='utf-8') as f:
        json.dump(summary, f, indent=2)
    print(f'[Suite] Summary saved: {summary_path}')

    sys.exit(0 if all_pass else 1)

if __name__ == '__main__':
    main()
