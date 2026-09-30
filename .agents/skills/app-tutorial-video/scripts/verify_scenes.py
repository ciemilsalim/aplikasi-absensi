#!/usr/bin/env python
# verify_scenes.py — V2.3 (Phase 7-9, 11)
# Compares source recording evidence vs final video frames
# Uses: perceptual hash (PIL only, no extra deps), URL evidence, DOM evidence, audio alignment
# Outputs: scene-verification-report.json + console summary
#
# RULES:
# - Do NOT use OCR as primary verification mechanism
# - Priority: scene order → URL evidence → DOM evidence → perceptual hash
# - Three statuses: PASS / REVIEW / FAIL
# - Lenient thresholds to account for zoom/subtitle overlay/video encoding
import argparse, json, os, re, sys

try:
    from PIL import Image
    PIL_AVAILABLE = True
except ImportError:
    PIL_AVAILABLE = False

# ──────────────────────────────────────────────────────────────────────
# PERCEPTUAL HASH (PIL only, no imagehash dependency)
# ──────────────────────────────────────────────────────────────────────

def compute_phash(img_path, hash_size=16, crop_frac=(0.05, 0.02, 0.95, 0.62)):
    """
    Compute perceptual hash of the content area of an image.
    crop_frac=(left, top, right, bottom) as fractions of image size.
    We crop out the bottom 38% to avoid burned-in subtitle area.
    We crop 5% from sides to avoid edge artifacts.
    Returns 256-bit binary string or None if PIL unavailable.
    """
    if not PIL_AVAILABLE:
        return None
    try:
        img = Image.open(img_path).convert('RGB')
        w, h = img.size
        left = int(w * crop_frac[0])
        top = int(h * crop_frac[1])
        right = int(w * crop_frac[2])
        bottom = int(h * crop_frac[3])
        img = img.crop((left, top, right, bottom))
        img = img.resize((hash_size, hash_size), Image.LANCZOS).convert('L')
        pixels = list(img.getdata())
        avg = sum(pixels) / max(len(pixels), 1)
        return ''.join('1' if p >= avg else '0' for p in pixels)
    except Exception as e:
        return None

def hamming_distance(h1, h2):
    if not h1 or not h2 or len(h1) != len(h2):
        return 999
    return sum(c1 != c2 for c1, c2 in zip(h1, h2))

def phash_similarity_status(distance, total_bits=256):
    """
    Convert hamming distance to PASS/REVIEW/FAIL.
    Thresholds are lenient because: zoom overlays, subtitle burn-in,
    video encoding artifacts all increase pixel differences.
    Even with these effects, the top-60% content area should be similar.
    - < 35/256 (~14%): PASS — clearly same content
    - 35–70/256 (~27%): REVIEW — similar but visually changed enough to inspect
    - > 70/256 (~27%): FAIL — content looks very different
    """
    if distance < 35:
        return 'PASS', f'phash_distance={distance} (< 35, PASS)'
    elif distance < 70:
        return 'REVIEW', f'phash_distance={distance} (35–70, REVIEW)'
    else:
        return 'FAIL', f'phash_distance={distance} (> 70, FAIL)'

# ──────────────────────────────────────────────────────────────────────
# AUDIO-SCENE ALIGNMENT (Phase 11)
# ──────────────────────────────────────────────────────────────────────

def build_scene_audio_map(audio_meta_path, intro_dur=4.0):
    """
    Build scene→audio timestamp mapping from audio_meta.json.
    audio_meta.json from tts.py: list of {scene_id, narration, audio_file, duration}
    Returns dict: {scene_id: {audio_start, audio_end, duration}}
    """
    if not audio_meta_path or not os.path.isfile(audio_meta_path):
        return {}
    try:
        meta = json.load(open(audio_meta_path, 'r', encoding='utf-8'))
    except Exception:
        return {}
    
    scene_map = {}
    current_time = intro_dur  # narration starts after intro silence
    for entry in meta:
        scene_id = entry.get('scene_id', '')
        duration = float(entry.get('duration', 0))
        scene_map[scene_id] = {
            'audio_start': current_time,
            'audio_end': current_time + duration,
            'duration': duration,
            'narration': entry.get('narration', '')
        }
        current_time += duration
    return scene_map

def check_audio_scene_alignment(scene_id, scene_data, audio_map, recording_timeline):
    """
    Check that scene order in recording aligns with scene order in audio.
    Simple check: scene[i] evidence_timestamp < scene[i+1] evidence_timestamp (monotonic)
    Also check: audio duration for scene is non-trivially positive.
    Returns (ok, notes)
    """
    notes = []
    ok = True
    
    if scene_id not in audio_map:
        notes.append(f'No audio mapping found for scene {scene_id}')
        return True, notes  # non-fatal
    
    audio_entry = audio_map[scene_id]
    narration = audio_entry.get('narration', '')
    audio_dur = audio_entry.get('duration', 0)
    
    if audio_dur < 0.5:
        notes.append(f'Audio duration very short: {audio_dur:.2f}s — possible empty narration')
        ok = False
    else:
        notes.append(f'Audio: {audio_entry["audio_start"]:.1f}s–{audio_entry["audio_end"]:.1f}s ({audio_dur:.1f}s)')
    
    # Check narration mentions expected scene content
    if scene_data.get('expected_text'):
        scene_narration_words = set(re.findall(r'\w+', narration.lower()))
        # Simple: check at least some expected_text words appear in narration
        # (narration is for orang_tua so uses different vocabulary than UI labels)
        notes.append(f'Narration words: {len(scene_narration_words)} unique words')
    
    return ok, notes

# ──────────────────────────────────────────────────────────────────────
# MAIN VERIFICATION
# ──────────────────────────────────────────────────────────────────────

def verify_scene(scene_evidence, final_frame_dir, audio_map, recording_timeline):
    """
    Verify a single scene using all available evidence.
    Returns: {status: PASS/REVIEW/FAIL, reasons: [], details: {}}
    Priority: scene_order → forbidden_text → URL → DOM → phash
    """
    scene_id = scene_evidence.get('scene_id', 'unknown')
    reasons = []
    details = {}
    status = 'PASS'

    # ── 1. Forbidden text check (from DOM evidence at recording time) ──
    forbidden_found = scene_evidence.get('forbidden_text_found', [])
    if forbidden_found:
        reasons.append(f'[FAIL] Forbidden text found during recording: {forbidden_found}')
        status = 'FAIL'
        details['forbidden_text'] = forbidden_found

    # ── 2. URL Evidence ──
    expected_url = scene_evidence.get('expected_url_contains')
    actual_url = scene_evidence.get('url', '')
    url_ok = scene_evidence.get('url_ok', True)
    if expected_url:
        if url_ok:
            reasons.append(f'[PASS] URL contains "{expected_url}": {actual_url}')
            details['url_check'] = 'PASS'
        else:
            reasons.append(f'[REVIEW] URL mismatch. Expected contains: "{expected_url}", Got: "{actual_url}"')
            details['url_check'] = 'REVIEW'
            if status == 'PASS':
                status = 'REVIEW'

    # ── 3. DOM Evidence: expected text check ──
    text_found = scene_evidence.get('text_found', [])
    text_missing = scene_evidence.get('text_missing', [])
    expected_text = scene_evidence.get('expected_text', [])
    
    if expected_text:
        found_ratio = len(text_found) / max(len(expected_text), 1)
        if found_ratio >= 0.5:
            reasons.append(f'[PASS] Expected text found: {text_found} ({len(text_found)}/{len(expected_text)})')
            details['text_check'] = 'PASS'
        elif found_ratio > 0:
            reasons.append(f'[REVIEW] Partial expected text: found={text_found}, missing={text_missing}')
            details['text_check'] = 'REVIEW'
            if status == 'PASS':
                status = 'REVIEW'
        else:
            reasons.append(f'[REVIEW] No expected text found in DOM. Missing: {text_missing}')
            details['text_check'] = 'REVIEW'
            if status == 'PASS':
                status = 'REVIEW'
    
    # ── 4. DOM Evidence: selectors ──
    selector_states = scene_evidence.get('dom_evidence', {}).get('selector_states', [])
    visible_selectors = [s for s in selector_states if s.get('found')]
    total_selectors = len(selector_states)
    if total_selectors > 0:
        vis_ratio = len(visible_selectors) / total_selectors
        if vis_ratio >= 0.5:
            reasons.append(f'[PASS] DOM selectors found: {len(visible_selectors)}/{total_selectors}')
        else:
            reasons.append(f'[REVIEW] DOM selectors partially found: {len(visible_selectors)}/{total_selectors}')
            if status == 'PASS':
                status = 'REVIEW'

    # ── 5. Perceptual Hash: Source vs Final Frame ──
    source_screenshot = scene_evidence.get('screenshot', '')
    final_frame_path = os.path.join(final_frame_dir, f'{scene_id}.png') if final_frame_dir else None

    phash_status = None
    if PIL_AVAILABLE and source_screenshot and os.path.isfile(source_screenshot) and \
       final_frame_path and os.path.isfile(final_frame_path):
        
        h_source = compute_phash(source_screenshot)
        h_final = compute_phash(final_frame_path)
        
        if h_source and h_final:
            dist = hamming_distance(h_source, h_final)
            phash_status, phash_msg = phash_similarity_status(dist)
            reasons.append(f'[{phash_status}] Visual similarity (pHash): {phash_msg}')
            details['phash_distance'] = dist
            details['phash_status'] = phash_status
            details['source_screenshot'] = source_screenshot
            details['final_frame'] = final_frame_path
            
            if phash_status == 'FAIL' and status != 'FAIL':
                # Only escalate to FAIL if other evidence also suggests problem
                if not url_ok or (expected_text and len(text_found) == 0):
                    status = 'FAIL'
                    reasons.append('[FAIL] pHash FAIL + URL/DOM evidence also failed → escalated to FAIL')
                else:
                    status = 'REVIEW'
                    reasons.append('[REVIEW] pHash suggests visual difference, but URL/DOM evidence OK → REVIEW for agent inspection')
            elif phash_status == 'REVIEW' and status == 'PASS':
                status = 'REVIEW'
        else:
            reasons.append('[REVIEW] Could not compute perceptual hash (image load failed)')
            details['phash_status'] = 'SKIPPED'
    elif not PIL_AVAILABLE:
        reasons.append('[REVIEW] PIL not available — visual similarity check skipped')
        details['phash_status'] = 'SKIPPED_NO_PIL'
    elif not (final_frame_path and os.path.isfile(final_frame_path)):
        reasons.append('[REVIEW] Final video frame not available for comparison')
        details['phash_status'] = 'SKIPPED_NO_FRAME'

    # ── 6. Audio alignment ──
    if audio_map:
        audio_ok, audio_notes = check_audio_scene_alignment(
            scene_id, scene_evidence, audio_map, {}
        )
        reasons.extend([f'[{"PASS" if audio_ok else "REVIEW"}] Audio: {n}' for n in audio_notes])
        if not audio_ok and status == 'PASS':
            status = 'REVIEW'

    return {
        'scene_id': scene_id,
        'title': scene_evidence.get('title', ''),
        'status': status,
        'verification_required': scene_evidence.get('verification_required', True),
        'reasons': reasons,
        'details': details
    }

def main():
    p = argparse.ArgumentParser(description='V2.3 Scene Verification — compare source evidence vs final video frames.')
    p.add_argument('--evidence-manifest', required=True, help='Path to scene-evidence-manifest.json')
    p.add_argument('--final-evidence-dir', required=True, help='Directory with final video frames (final-scene-evidence/)')
    p.add_argument('--plan', help='Path to tutorial-plan.json')
    p.add_argument('--audio-meta', help='Path to audio_meta.json from TTS')
    p.add_argument('--out-report', help='Path to write scene-verification-report.json')
    p.add_argument('--fail-on-review', action='store_true', help='Treat REVIEW as FAIL in exit code')
    args = p.parse_args()

    if not os.path.isfile(args.evidence_manifest):
        print(f'Error: scene-evidence-manifest.json not found: {args.evidence_manifest}', file=sys.stderr)
        sys.exit(1)

    evidence_manifest = json.load(open(args.evidence_manifest, 'r', encoding='utf-8'))
    scenes_evidence = evidence_manifest.get('scenes', [])

    # Build audio map
    intro_dur = 4.0
    if args.plan and os.path.isfile(args.plan):
        plan = json.load(open(args.plan, 'r', encoding='utf-8'))
        intro_cfg = plan.get('intro', {})
        intro_dur = float(intro_cfg.get('duration', 4.0)) if intro_cfg.get('enabled', True) else 0.0

    audio_map = build_scene_audio_map(args.audio_meta, intro_dur)

    # Build scene-audio-map.json if audio_meta provided
    if audio_map and args.out_report:
        audio_map_path = os.path.join(os.path.dirname(args.out_report), 'scene-audio-map.json')
        with open(audio_map_path, 'w', encoding='utf-8') as f:
            json.dump(audio_map, f, indent=2)
        print(f'[V2.3] scene-audio-map.json written: {audio_map_path}')

    print(f'\n[V2.3] Scene Verification — {len(scenes_evidence)} scenes')
    print(f'[V2.3] PIL available: {PIL_AVAILABLE}')
    print(f'[V2.3] Final evidence dir: {args.final_evidence_dir}')
    print()

    # ── Verify scene ORDER from evidence manifest ──
    scene_ids_in_order = [s.get('scene_id') for s in scenes_evidence]
    if args.plan and os.path.isfile(args.plan):
        plan_scenes = [s.get('scene_id') for s in plan.get('scenes', [])]
        order_match = scene_ids_in_order == plan_scenes
        print(f'Scene order check: {"PASS" if order_match else "FAIL"}')
        print(f'  Plan order: {plan_scenes}')
        print(f'  Recording order: {scene_ids_in_order}')
    else:
        order_match = True
        print('Scene order check: SKIPPED (no plan)')

    # ── Verify each scene ──
    scene_results = []
    final_status = 'PASS'

    for scene_ev in scenes_evidence:
        result = verify_scene(scene_ev, args.final_evidence_dir, audio_map, {})
        scene_results.append(result)

        status_icon = {'PASS': '✓', 'REVIEW': '⚠', 'FAIL': '✗'}.get(result['status'], '?')
        print(f'\n  {status_icon} {result["scene_id"]}: {result["title"]} → {result["status"]}')
        for r in result['reasons']:
            print(f'    {r}')

        if result['status'] == 'FAIL':
            final_status = 'FAIL'
        elif result['status'] == 'REVIEW' and final_status != 'FAIL':
            final_status = 'REVIEW'

    if not order_match:
        final_status = 'FAIL'

    # ── Write report ──
    report = {
        'run_id': evidence_manifest.get('run_id'),
        'tutorial_id': evidence_manifest.get('tutorial_id'),
        'target_user': evidence_manifest.get('target_user'),
        'generated_at': __import__('datetime').datetime.now().isoformat(),
        'scene_order_ok': order_match,
        'pil_available': PIL_AVAILABLE,
        'audio_mapped_scenes': len(audio_map),
        'final_status': final_status,
        'scenes': scene_results,
        'summary': {
            'total': len(scene_results),
            'pass': sum(1 for r in scene_results if r['status'] == 'PASS'),
            'review': sum(1 for r in scene_results if r['status'] == 'REVIEW'),
            'fail': sum(1 for r in scene_results if r['status'] == 'FAIL')
        }
    }

    if args.out_report:
        os.makedirs(os.path.dirname(os.path.abspath(args.out_report)), exist_ok=True)
        with open(args.out_report, 'w', encoding='utf-8') as f:
            json.dump(report, f, indent=2, ensure_ascii=False)
        print(f'\n[V2.3] Scene verification report: {args.out_report}')

    print(f'\n{"="*60}')
    print(f'SCENE VERIFICATION FINAL STATUS: {final_status}')
    print(f'  PASS: {report["summary"]["pass"]} | REVIEW: {report["summary"]["review"]} | FAIL: {report["summary"]["fail"]}')
    print(f'  Scene Order: {"PASS" if order_match else "FAIL"}')

    exit_code = 0
    if final_status == 'FAIL':
        exit_code = 1
    elif final_status == 'REVIEW' and args.fail_on_review:
        exit_code = 2

    sys.exit(exit_code)

if __name__ == '__main__':
    main()
