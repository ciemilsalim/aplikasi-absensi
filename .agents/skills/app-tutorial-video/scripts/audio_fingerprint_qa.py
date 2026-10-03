#!/usr/bin/env python
# audio_fingerprint_qa.py -- V3.5.2.1 Audio Content Fingerprint (SHA-256)
# Computes SHA-256 for all audio assets (narration, click, typing) for both variants.
# Ensures mobile != desktop for narration if timelines differ.
import argparse, hashlib, json, os, sys

if sys.stdout and hasattr(sys.stdout, 'reconfigure'):
    try: sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    except: pass

def sha256_full(path):
    if not os.path.isfile(path): return None
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        while chunk := f.read(65536): h.update(chunk)
    return h.hexdigest()

def sha256_short(path): 
    h = sha256_full(path)
    return h[:16] if h else None

def fingerprint_variant(run_dir, variant, skill_root):
    audio_dir = os.path.join(run_dir, variant, 'audio')
    assets = {}
    # Narration
    narr = os.path.join(audio_dir, 'narration.mp3')
    assets['narration'] = {'path': narr, 'sha256': sha256_full(narr), 'short': sha256_short(narr), 'exists': os.path.isfile(narr)}
    # Click SFX (shared from skill resources)
    click_path = os.path.join(skill_root, 'resources', 'audio', 'click.wav')
    mouse_path = os.path.join(skill_root, 'resources', 'audio', 'mouse-click.wav')
    tap_path   = os.path.join(skill_root, 'resources', 'audio', 'tap.wav')
    typing_path = os.path.join(skill_root, 'resources', 'audio', 'typing.wav')
    for name, p in [('click.wav', click_path), ('mouse-click.wav', mouse_path), ('tap.wav', tap_path), ('typing.wav', typing_path)]:
        assets[name] = {'path': p, 'sha256': sha256_full(p), 'short': sha256_short(p), 'exists': os.path.isfile(p)}
    # Load provenance for run_id, timeline_hash
    prov_path = os.path.join(run_dir, variant, 'video', 'render-provenance.json')
    prov = {}
    if os.path.isfile(prov_path):
        with open(prov_path, encoding='utf-8') as f: prov = json.load(f)
    return {
        'variant': variant,
        'run_id': prov.get('run_id', 'unknown'),
        'tutorial_id': prov.get('tutorial_id', 'unknown'),
        'timeline_hash': prov.get('timeline_hash', 'unknown'),
        'assets': assets,
    }

def run_fingerprint_qa(desktop_run, mobile_run, output_dir, skill_root):
    d_fp = fingerprint_variant(desktop_run, 'desktop', skill_root)
    m_fp = fingerprint_variant(mobile_run, 'mobile', skill_root)

    # Compare narration hashes -- should differ if timelines differ
    d_narr_hash = d_fp['assets']['narration']['sha256']
    m_narr_hash = m_fp['assets']['narration']['sha256']
    d_th = d_fp['timeline_hash']
    m_th = m_fp['timeline_hash']

    narration_differs = (d_narr_hash != m_narr_hash)
    timeline_differs = (d_th != m_th)

    narration_isolation_ok = True
    narration_isolation_note = ''
    if timeline_differs and not narration_differs:
        narration_isolation_ok = False
        narration_isolation_note = 'WARNING: Timelines differ but narration audio is identical -- cross-contamination possible'
    elif timeline_differs and narration_differs:
        narration_isolation_note = 'VERIFIED: Different timelines produce different narration audio'
    elif not timeline_differs and not narration_differs:
        narration_isolation_note = 'NOTE: Same timeline hash -- identical narration audio is expected and correct'
    else:
        narration_isolation_note = 'NOTE: Same narration despite same timeline -- expected if scenes are identical'

    # Shared SFX check -- click/typing SFX files are shared (same file)
    shared_sfx_ok = True
    sfx_notes = []
    for sfx_name in ('click.wav', 'mouse-click.wav', 'tap.wav', 'typing.wav'):
        d_h = d_fp['assets'][sfx_name]['sha256']
        m_h = m_fp['assets'][sfx_name]['sha256']
        if d_h and m_h and d_h == m_h:
            sfx_notes.append(f'{sfx_name}: SHARED (identical) -- expected, single asset for both variants')
        elif d_h and m_h:
            sfx_notes.append(f'{sfx_name}: DIFFERENT -- unexpected for shared SFX assets')

    # Build comparison table
    table = []
    for asset_name in ('narration', 'click.wav', 'mouse-click.wav', 'tap.wav', 'typing.wav'):
        d_info = d_fp['assets'][asset_name]
        m_info = m_fp['assets'][asset_name]
        same = d_info['sha256'] == m_info['sha256'] if (d_info['sha256'] and m_info['sha256']) else None
        table.append({
            'asset': asset_name,
            'desktop_sha256_short': d_info['short'],
            'mobile_sha256_short': m_info['short'],
            'identical': same,
            'desktop_run_id': d_fp['run_id'],
            'mobile_run_id': m_fp['run_id'],
            'desktop_timeline_hash': d_th,
            'mobile_timeline_hash': m_th,
        })

    report = {
        'gate': 'AUDIO FINGERPRINT QA -- V3.5.2.1',
        'desktop': {'run_id': d_fp['run_id'], 'timeline_hash': d_th},
        'mobile': {'run_id': m_fp['run_id'], 'timeline_hash': m_th},
        'timeline_hashes_differ': timeline_differs,
        'narration_hashes_differ': narration_differs,
        'narration_isolation_ok': narration_isolation_ok,
        'narration_isolation_note': narration_isolation_note,
        'sfx_notes': sfx_notes,
        'fingerprint_table': table,
        'status': 'PASS' if narration_isolation_ok else 'NEEDS REVIEW',
    }

    os.makedirs(output_dir, exist_ok=True)
    json_path = os.path.join(output_dir, 'audio-fingerprint-qa.json')
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(report, f, indent=2)

    md_lines = [
        '# Audio Content Fingerprint QA -- V3.5.2.1',
        '',
        '## Fingerprint Table',
        '',
        '| Asset | Desktop SHA-256 | Mobile SHA-256 | Desktop Run | Mobile Run | Desktop Timeline | Mobile Timeline |',
        '|---|---|---|---|---|---|---|',
    ]
    for row in table:
        identical_mark = '(same)' if row['identical'] else '(diff)'
        md_lines.append(
            f"| {row['asset']} | `{row['desktop_sha256_short']}` | `{row['mobile_sha256_short']}` {identical_mark} | "
            f"{row['desktop_run_id']} | {row['mobile_run_id']} | `{row['desktop_timeline_hash'][:8]}` | `{row['mobile_timeline_hash'][:8]}` |"
        )
    md_lines += [
        '',
        '## Narration Isolation',
        '',
        f'- Timeline hashes differ: **{timeline_differs}**',
        f'- Narration hashes differ: **{narration_differs}**',
        f'- {narration_isolation_note}',
        '',
        '## SFX Assets',
        '',
    ]
    for n in sfx_notes:
        md_lines.append(f'- {n}')
    md_lines += [
        '',
        '## Verdict',
        '',
        f'**{"VERIFIED" if narration_isolation_ok else "NEEDS REVIEW"}** -- {narration_isolation_note}',
        '',
    ]
    md_path = os.path.join(output_dir, 'audio-fingerprint-qa.md')
    with open(md_path, 'w', encoding='utf-8') as f:
        f.write('\n'.join(md_lines))

    print(f'[V3.5.2.1] Audio Fingerprint: {report["status"]}')
    print(f'  narration_differs={narration_differs}, timeline_differs={timeline_differs}')
    return narration_isolation_ok, report

def main():
    p = argparse.ArgumentParser()
    p.add_argument('--desktop-run', required=True)
    p.add_argument('--mobile-run', required=True)
    p.add_argument('--output-dir', required=True)
    p.add_argument('--skill-root', required=True)
    args = p.parse_args()
    ok, rep = run_fingerprint_qa(args.desktop_run, args.mobile_run, args.output_dir, args.skill_root)
    print(json.dumps(rep, indent=2))
    sys.exit(0 if ok else 1)

if __name__ == '__main__':
    main()
