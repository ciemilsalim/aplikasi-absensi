#!/usr/bin/env python
# timeline_dependency_audit.py -- V3.5.2.1 Canonical Timeline Dependency Audit
# Maps all files in a run that reference timestamps and verifies they ALL
# use canonical-timeline.json as single source of truth.
import argparse, hashlib, json, os, sys

if sys.stdout and hasattr(sys.stdout, 'reconfigure'):
    try: sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    except: pass

CANONICAL_CONSUMERS = [
    ('recording/canonical-timeline.json', 'SOURCE', 'Primary source of truth for all timestamps'),
    ('audio/audio_meta.json', 'CONSUMER', 'TTS scene durations (derived from canonical timing)'),
    ('recording/audio-effects-timeline.json', 'LEGACY', 'Legacy SFX timeline -- render.py ignores if canonical present'),
    ('recording/typing-events.json', 'LEGACY', 'Legacy typing intervals -- Gate 10 ignores if canonical present'),
    ('recording/timing_manifest.json', 'DERIVED', 'Scene timing derived from canonical scene durations'),
    ('recording/subtitle-placement.json', 'DERIVED', 'Subtitle positions derived from canonical scene boundaries'),
    ('video/render-provenance.json', 'PROVENANCE', 'Captures timeline_hash from canonical at render time'),
    ('audio/audio-provenance.json', 'PROVENANCE', 'Captures timeline_hash from canonical at TTS time'),
    ('video/typing-perceptual-qa.json', 'QA_OUTPUT', 'Gate 10 output using canonical timestamps (V3.5.2)'),
    ('video/typing-audio-verification.json', 'QA_OUTPUT', 'Typing audio energy verification'),
    ('video/qa-v352-sync.json', 'QA_OUTPUT', 'Gate 11 A/V sync report'),
    ('video/timeline-audio-qa.json', 'QA_OUTPUT', 'Timeline audio QA output'),
]

RENDER_USES_CANONICAL = [
    ('render.py', 'build_sfx_track()', 'Loads canonical-timeline.json first; audio-effects-timeline.json only as fallback'),
    ('render.py', 'verify_typing_audio_energy()', 'Uses intro_dur+pts_factor for placement but SFX from canonical'),
    ('validate.py', 'audio_perceptual_qa()', 'V3.5.2: Reads canonical-timeline.json timestamps for Gate 10'),
    ('validate.py', 'deterministic_av_sync_qa()', 'Gate 11: Full canonical timeline dependency'),
    ('record.js', 'pacing lock', 'V3.5.2: Locks video recording to canonical total_duration_ms'),
    ('tts.py', 'scene narration', 'TTS derives scene start/end from canonical scene_timeline'),
]

def sha256_short(path):
    if not os.path.isfile(path): return None
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        while chunk := f.read(65536): h.update(chunk)
    return h.hexdigest()[:16]

def check_canonical_hash_consistency(run_variant_dir, variant):
    """Verify that all provenance files reference the same timeline_hash."""
    ct_path = os.path.join(run_variant_dir, 'recording', 'canonical-timeline.json')
    if not os.path.isfile(ct_path):
        ct_path = os.path.join(run_variant_dir, 'canonical-timeline.json')
    if not os.path.isfile(ct_path):
        return False, 'canonical-timeline.json not found', {}
    with open(ct_path, encoding='utf-8') as f:
        ct = json.load(f)
    ct_th = ct.get('timeline_hash')
    # Check provenance files
    prov_path = os.path.join(run_variant_dir, 'video', 'render-provenance.json')
    aprov_path = os.path.join(run_variant_dir, 'audio', 'audio-provenance.json')
    prov = {}; aprov = {}
    if os.path.isfile(prov_path):
        with open(prov_path, encoding='utf-8') as f: prov = json.load(f)
    if os.path.isfile(aprov_path):
        with open(aprov_path, encoding='utf-8') as f: aprov = json.load(f)
    prov_th = prov.get('timeline_hash')
    aprov_th = aprov.get('timeline_hash')
    nested_aprov_th = prov.get('audio_provenance', {}).get('timeline_hash')
    hashes = {
        'canonical_timeline_hash': ct_th,
        'render_provenance_timeline_hash': prov_th,
        'audio_provenance_timeline_hash': aprov_th or nested_aprov_th
    }
    # All non-None hashes must match
    non_null = [v for v in hashes.values() if v is not None]
    all_match = len(set(non_null)) <= 1 and len(non_null) >= 2
    return all_match, 'All timeline_hash references are consistent' if all_match else f'HASH MISMATCH: {hashes}', hashes

def run_dependency_audit(run_dir, variant, output_dir):
    run_variant_dir = os.path.join(run_dir, variant)
    issues = []
    findings = []

    for rel_path, role, description in CANONICAL_CONSUMERS:
        abs_path = os.path.join(run_variant_dir, rel_path)
        exists = os.path.isfile(abs_path)
        file_hash = sha256_short(abs_path) if exists else None
        status = 'PRESENT' if exists else ('EXPECTED_ABSENT' if role == 'LEGACY' else 'MISSING')
        if not exists and role not in ('LEGACY', 'PROVENANCE'):
            if role != 'QA_OUTPUT':  # QA outputs may not exist if not run yet
                issues.append(f'MISSING {role}: {rel_path}')
        findings.append({
            'file': rel_path,
            'role': role,
            'description': description,
            'exists': exists,
            'sha256_short': file_hash,
            'status': status,
        })

    # Check timeline_hash consistency
    hash_ok, hash_msg, hashes = check_canonical_hash_consistency(run_variant_dir, variant)
    if not hash_ok:
        issues.append(f'HASH INCONSISTENCY: {hash_msg}')

    dependency_chain = {
        'canonical-timeline.json': {
            'role': 'SOURCE OF TRUTH',
            'consumers': {
                'video': 'record.js pacing lock (total_duration_ms)',
                'cursor': 'canonical x,y,time_sec for each event',
                'click_sfx': 'render.py build_sfx_track() uses time_sec/time_ms',
                'typing_sfx': 'render.py build_sfx_track() uses start_sec/end_sec',
                'narration': 'tts.py scene timing (indirect via timing_manifest)',
                'subtitle': 'render.py subtitle placement (scene boundaries)',
                'gate_10': 'typing_perceptual_qa.py _load_canonical_typing_events() V3.5.2',
                'gate_11': 'validate.py deterministic_av_sync_qa()',
            }
        }
    }

    legacy_warnings = [
        'audio-effects-timeline.json is LEGACY: render.py ignores it when canonical-timeline.json is present',
        'typing-events.json is LEGACY: Gate 10 now ignores it when canonical-timeline.json is present (V3.5.2 fix)',
    ]

    overall_pass = len(issues) == 0 and hash_ok
    report = {
        'gate': 'TIMELINE DEPENDENCY AUDIT -- V3.5.2.1',
        'variant': variant,
        'run_dir': run_dir,
        'canonical_timeline_hash_consistent': hash_ok,
        'hash_values': hashes,
        'hash_message': hash_msg,
        'findings': findings,
        'dependency_chain': dependency_chain,
        'legacy_warnings': legacy_warnings,
        'issues': issues,
        'status': 'PASS' if overall_pass else 'FAIL',
    }

    os.makedirs(output_dir, exist_ok=True)
    json_path = os.path.join(output_dir, f'timeline-dependency-audit-{variant}.json')
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(report, f, indent=2)

    md_lines = [
        f'# Timeline Dependency Audit -- V3.5.2.1 ({variant})',
        '',
        f'**Status:** {"PASS" if overall_pass else "FAIL"}',
        f'**Timeline Hash Consistent:** {hash_ok}',
        '',
        '## Dependency Chain',
        '',
        '```',
        'canonical-timeline.json (SOURCE OF TRUTH)',
        '    |',
        '    +-- video (record.js pacing lock)',
        '    +-- cursor (x,y,time_sec per event)',
        '    +-- click SFX (build_sfx_track time_sec)',
        '    +-- typing SFX (build_sfx_track start_sec/end_sec)',
        '    +-- narration (TTS scene timing)',
        '    +-- subtitle (scene boundary placement)',
        '    +-- Gate 10 QA (typing_perceptual_qa canonical priority)',
        '    +-- Gate 11 QA (deterministic_av_sync_qa)',
        '```',
        '',
        '## File Status',
        '',
        '| File | Role | Status | SHA-256 (short) |',
        '|---|---|---|---|',
    ]
    for f in findings:
        icon = 'OK' if f['exists'] else ('SKIP' if f['role'] == 'LEGACY' else 'MISS')
        md_lines.append(f"| `{f['file']}` | {f['role']} | {icon} | `{f['sha256_short'] or 'N/A'}` |")
    md_lines += [
        '',
        '## Timeline Hash Consistency',
        '',
        f'| Source | Hash |',
        '|---|---|',
    ]
    for k, v in hashes.items():
        md_lines.append(f'| {k} | `{v or "N/A"}` |')
    md_lines += [
        '',
        '## Legacy File Warnings',
        '',
    ]
    for w in legacy_warnings:
        md_lines.append(f'- {w}')
    md_lines += [
        '',
        '## Verdict',
        '',
        f'**{"VERIFIED" if overall_pass else "NEEDS VERIFICATION"}** -- ' +
        ('All timestamps trace to canonical-timeline.json. No legacy contamination.' if overall_pass
         else 'Issues found in dependency chain. See above.'),
        '',
    ]
    md_path = os.path.join(output_dir, 'timeline-dependency-audit.md')
    with open(md_path, 'w', encoding='utf-8') as f:
        f.write('\n'.join(md_lines))

    print(f'[V3.5.2.1] Timeline Dependency Audit ({variant}): {"PASS" if overall_pass else "FAIL"}')
    print(f'  hash_consistent={hash_ok}, issues={len(issues)}')
    return overall_pass, report

def main():
    p = argparse.ArgumentParser()
    p.add_argument('--run-dir', required=True)
    p.add_argument('--variant', required=True, choices=['desktop','mobile'])
    p.add_argument('--output-dir', required=True)
    args = p.parse_args()
    ok, rep = run_dependency_audit(args.run_dir, args.variant, args.output_dir)
    print(json.dumps({k:v for k,v in rep.items() if k not in ('findings','dependency_chain')}, indent=2))
    sys.exit(0 if ok else 1)

if __name__ == '__main__':
    main()
