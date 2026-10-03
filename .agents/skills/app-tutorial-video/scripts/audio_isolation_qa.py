#!/usr/bin/env python
# audio_isolation_qa.py -- V3.5.2.1 Hard Variant Isolation + Negative Cross-Test
# Asserts that desktop video ONLY uses desktop audio and vice versa.
# Includes NEGATIVE TESTS: force mobile+desktop pairing -- must FAIL.
import argparse, hashlib, json, os, sys

if sys.stdout and hasattr(sys.stdout, 'reconfigure'):
    try: sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    except: pass

def sha256_short(path, length=16):
    if not os.path.isfile(path): return None
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        while chunk := f.read(65536): h.update(chunk)
    return h.hexdigest()[:length]

def load_provenance(video_dir):
    prov_path = os.path.join(video_dir, 'render-provenance.json')
    if not os.path.isfile(prov_path): return {}
    with open(prov_path, encoding='utf-8') as f: return json.load(f)

def load_audio_prov(audio_dir):
    ap_path = os.path.join(audio_dir, 'audio-provenance.json')
    if not os.path.isfile(ap_path): return {}
    with open(ap_path, encoding='utf-8') as f: return json.load(f)

def check_isolation(video_prov, audio_prov, expected_variant):
    issues = []
    v_rid  = video_prov.get('run_id')
    v_tid  = video_prov.get('tutorial_id')
    v_var  = video_prov.get('variant')
    v_th   = video_prov.get('timeline_hash')
    a_prov = audio_prov if (audio_prov and len(audio_prov) > 0) else video_prov.get('audio_provenance', {})
    a_rid  = a_prov.get('run_id')
    a_tid  = a_prov.get('tutorial_id')
    a_var  = a_prov.get('variant')
    a_th   = a_prov.get('timeline_hash')
    checks = []
    def _chk(name, val_v, val_a, must_match=True):
        ok = (val_v == val_a) if must_match else (val_v != val_a)
        status = 'PASS' if ok else 'FAIL'
        checks.append({'field': name, 'video': val_v, 'audio': val_a, 'status': status})
        if not ok:
            issues.append(f"ISOLATION VIOLATION: {name} mismatch (video={val_v}, audio={val_a})")
        return ok
    _chk('run_id', v_rid, a_rid)
    _chk('tutorial_id', v_tid, a_tid)
    _chk('variant', v_var, a_var)
    if v_th and a_th:
        _chk('timeline_hash', v_th, a_th)
    variant_ok = (v_var == expected_variant)
    if not variant_ok:
        issues.append(f"VARIANT MISMATCH: expected={expected_variant}, actual={v_var}")
    return len(issues) == 0, checks, issues

def run_isolation_qa(desktop_run_dir, mobile_run_dir, output_dir):
    """
    Positive tests: desktop uses desktop audio, mobile uses mobile audio.
    Negative tests: desktop video + mobile audio must FAIL, mobile video + desktop audio must FAIL.
    """
    results = {'positive': {}, 'negative': {}, 'overall_status': 'UNKNOWN'}

    # ── POSITIVE: Desktop ────────────────────────────────────────────────
    d_vid_dir  = os.path.join(desktop_run_dir, 'desktop', 'video')
    d_audio_dir = os.path.join(desktop_run_dir, 'desktop', 'audio')
    d_prov = load_provenance(d_vid_dir)
    d_aprov = load_audio_prov(d_audio_dir)
    d_ok, d_checks, d_issues = check_isolation(d_prov, d_aprov, 'desktop')
    results['positive']['desktop'] = {
        'status': 'PASS' if d_ok else 'FAIL',
        'checks': d_checks,
        'issues': d_issues,
    }

    # ── POSITIVE: Mobile ─────────────────────────────────────────────────
    m_vid_dir  = os.path.join(mobile_run_dir, 'mobile', 'video')
    m_audio_dir = os.path.join(mobile_run_dir, 'mobile', 'audio')
    m_prov = load_provenance(m_vid_dir)
    m_aprov = load_audio_prov(m_audio_dir)
    m_ok, m_checks, m_issues = check_isolation(m_prov, m_aprov, 'mobile')
    results['positive']['mobile'] = {
        'status': 'PASS' if m_ok else 'FAIL',
        'checks': m_checks,
        'issues': m_issues,
    }

    # ── NEGATIVE: Mobile video + Desktop audio ───────────────────────────
    # Force cross-variant pairing and expect FAIL
    neg1_ok, neg1_checks, neg1_issues = check_isolation(m_prov, d_aprov, 'mobile')
    neg1_expected_fail = not neg1_ok  # We WANT this to fail
    results['negative']['mobile_video_desktop_audio'] = {
        'description': 'NEGATIVE TEST: Mobile video paired with Desktop audio (must FAIL isolation)',
        'isolation_passed': neg1_ok,
        'negative_test_passed': neg1_expected_fail,
        'status': 'PASS' if neg1_expected_fail else 'FAIL',
        'note': 'Correctly rejected cross-variant pairing' if neg1_expected_fail else 'CRITICAL: Cross-variant pairing was not rejected',
        'checks': neg1_checks,
    }

    # ── NEGATIVE: Desktop video + Mobile audio ───────────────────────────
    neg2_ok, neg2_checks, neg2_issues = check_isolation(d_prov, m_aprov, 'desktop')
    neg2_expected_fail = not neg2_ok
    results['negative']['desktop_video_mobile_audio'] = {
        'description': 'NEGATIVE TEST: Desktop video paired with Mobile audio (must FAIL isolation)',
        'isolation_passed': neg2_ok,
        'negative_test_passed': neg2_expected_fail,
        'status': 'PASS' if neg2_expected_fail else 'FAIL',
        'note': 'Correctly rejected cross-variant pairing' if neg2_expected_fail else 'CRITICAL: Cross-variant pairing was not rejected',
        'checks': neg2_checks,
    }

    all_positive_pass = d_ok and m_ok
    all_negative_pass = neg1_expected_fail and neg2_expected_fail
    overall = all_positive_pass and all_negative_pass
    results['overall_status'] = 'PASS' if overall else 'FAIL'
    results['positive_tests_pass'] = all_positive_pass
    results['negative_tests_pass'] = all_negative_pass

    os.makedirs(output_dir, exist_ok=True)
    json_path = os.path.join(output_dir, 'variant-isolation-qa.json')
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=2)

    md_lines = [
        '# Variant Audio Isolation QA -- V3.5.2.1',
        '',
        f'**Overall Status:** {"PASS" if overall else "FAIL"}',
        '',
        '## Positive Tests',
        '',
        f'| Variant | Status |',
        '|---|---|',
        f'| Desktop | {"PASS" if d_ok else "FAIL"} |',
        f'| Mobile  | {"PASS" if m_ok else "FAIL"} |',
        '',
        '## Negative Tests (Cross-Variant Pairing)',
        '',
        f'| Test | Expected | Result |',
        '|---|---|---|',
        f'| Mobile video + Desktop audio | FAIL (isolation) | {"CORRECTLY FAILED" if neg1_expected_fail else "INCORRECTLY PASSED -- CRITICAL BUG"} |',
        f'| Desktop video + Mobile audio | FAIL (isolation) | {"CORRECTLY FAILED" if neg2_expected_fail else "INCORRECTLY PASSED -- CRITICAL BUG"} |',
        '',
        '## Verdict',
        '',
    ]
    if overall:
        md_lines.append('**VERIFIED** -- Desktop and Mobile audio are strictly isolated. Negative cross-variant tests correctly rejected.')
    else:
        md_lines.append('**NEEDS VERIFICATION** -- Audio isolation violations detected. See issues above.')
    md_lines.append('')

    md_path = os.path.join(output_dir, 'variant-isolation-qa.md')
    with open(md_path, 'w', encoding='utf-8') as f:
        f.write('\n'.join(md_lines))

    print(f'[V3.5.2.1] Variant Isolation: overall={"PASS" if overall else "FAIL"}')
    print(f'  positive: desktop={"PASS" if d_ok else "FAIL"}, mobile={"PASS" if m_ok else "FAIL"}')
    print(f'  negative: neg1={"PASS" if neg1_expected_fail else "FAIL"}, neg2={"PASS" if neg2_expected_fail else "FAIL"}')
    return overall, results

def main():
    p = argparse.ArgumentParser()
    p.add_argument('--desktop-run', required=True)
    p.add_argument('--mobile-run', required=True)
    p.add_argument('--output-dir', required=True)
    args = p.parse_args()
    ok, rep = run_isolation_qa(args.desktop_run, args.mobile_run, args.output_dir)
    print(json.dumps(rep, indent=2))
    sys.exit(0 if ok else 1)

if __name__ == '__main__':
    main()
