#!/usr/bin/env python
# validate.py — V2.3 (5-Gate QA: Technical + Content + Provenance + Scene + Audio-Visual)
# V2.2 RETAINED: Technical / Content / Provenance gates unchanged
# V2.3 ADDED:
#   Gate 4 — Scene QA (scene-evidence-manifest, final-scene-evidence comparison)
#   Gate 5 — Audio-Visual QA (scene-audio-map, narration alignment)
import argparse, datetime, hashlib, json, os, re, shutil, subprocess, sys

def parse_srt_timestamp(ts):
    ts = ts.replace('.', ',')
    parts = ts.split(':')
    if len(parts) != 3:
        raise ValueError(f"Invalid timestamp format: {ts}")
    h, m, s_ms = parts
    s, ms = s_ms.split(',')
    return int(h) * 3600 + int(m) * 60 + int(s) + int(ms) / 1000.0

def validate_srt_file(srt_path):
    errors = []
    if not os.path.isfile(srt_path):
        return [f"Subtitle file does not exist: {srt_path}"]
    
    try:
        with open(srt_path, 'r', encoding='utf-8') as f:
            content = f.read()
    except UnicodeDecodeError:
        return [f"Subtitle file is not valid UTF-8: {srt_path}"]
    except Exception as e:
        return [f"Error reading subtitle file: {e}"]

    ts_pattern = re.compile(r'(\d{2}:\d{2}:\d{2}[,\.]\d{3})\s*-->\s*(\d{2}:\d{2}:\d{2}[,\.]\d{3})')
    blocks = re.split(r'\n\s*\n', content.strip())
    prev_end = 0.0
    
    for i, block in enumerate(blocks, 1):
        block_lines = [l.strip() for l in block.split('\n') if l.strip()]
        if not block_lines:
            continue
        
        ts_match = None
        for line in block_lines:
            match = ts_pattern.search(line)
            if match:
                ts_match = match
                break
        
        if not ts_match:
            errors.append(f"SRT Block {i}: Missing or malformed timestamp line.")
            continue
            
        start_str, end_str = ts_match.groups()
        try:
            start_sec = parse_srt_timestamp(start_str)
            end_sec = parse_srt_timestamp(end_str)
        except Exception as e:
            errors.append(f"SRT Block {i}: Timestamp parse error - {e}")
            continue
            
        if end_sec <= start_sec:
            errors.append(f"SRT Block {i}: Reversed or zero timestamp duration ({start_str} --> {end_str}).")
            
        if start_sec < prev_end - 0.2:
            errors.append(f"SRT Block {i}: Unreasonable overlap with previous subtitle (Start: {start_str}, Previous End: {prev_end:.3f}s).")
            
        prev_end = end_sec
        
    return errors

def check_black_screen(ffmpeg_exe, video_path):
    cmd = [ffmpeg_exe, '-i', video_path, '-vf', 'blackdetect=d=2:pix_th=0.10', '-f', 'null', '-']
    res = subprocess.run(cmd, capture_output=True, text=True)
    stderr = res.stderr
    if 'black_start' in stderr:
        black_matches = re.findall(r'black_duration: ([\d\.]+)', stderr)
        total_black = sum(float(d) for d in black_matches)
        if total_black > 5.0:
            return f"Video contains significant black screen sections (Total black duration: {total_black:.2f}s)."
    return None

def file_hash(path):
    """Compute SHA256 hex digest of a file (first 16 chars)."""
    if not os.path.isfile(path):
        return None
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        for chunk in iter(lambda: f.read(8192), b''):
            h.update(chunk)
    return h.hexdigest()[:16]

# ──────────────────────────────────────────────────────────────────────
# V2.2: CONTENT QA — verify scene screenshots and visual keywords
# ──────────────────────────────────────────────────────────────────────
def content_qa(plan, recording_dir):
    """
    V2.2 Content QA:
    1. Check that scene screenshots exist for each scene in the plan
    2. Check expected_visual_keywords appear in narration text (as proxy for scene content)
    3. Check forbidden_visual_keywords do NOT appear in narration text
    
    Returns (ok: bool, issues: list[str], notes: list[str])
    """
    issues = []
    notes = []

    if not recording_dir or not os.path.isdir(recording_dir):
        issues.append(f"[CONTENT QA] Recording directory not found: {recording_dir}")
        return False, issues, notes

    scenes = plan.get('scenes', [])
    expected_kw = [kw.lower() for kw in plan.get('expected_visual_keywords', [])]
    forbidden_kw = [kw.lower() for kw in plan.get('forbidden_visual_keywords', [])]

    # 1. Scene screenshot existence check
    missing_scenes = []
    found_scenes = []
    for scene in scenes:
        scene_id = scene.get('scene_id') or scene.get('id', 'unknown')
        expected_file = os.path.join(recording_dir, f"{scene_id}.png")
        if not os.path.isfile(expected_file):
            missing_scenes.append(scene_id)
            issues.append(f"[CONTENT QA] Scene screenshot missing: {scene_id}.png")
        else:
            sz = os.path.getsize(expected_file)
            found_scenes.append(scene_id)
            notes.append(f"[CONTENT QA] Scene screenshot found: {scene_id}.png ({sz} bytes)")

    if missing_scenes:
        issues.append(
            f"[CONTENT QA] {len(missing_scenes)} scene screenshot(s) missing: {missing_scenes}\n"
            f"  This could indicate the recording did not complete all scenes in the plan."
        )

    # 2. Narration text keyword check (as proxy for content topic)
    all_narration = ' '.join([
        scene.get('narration', '') for scene in scenes
    ]).lower()

    # Check expected keywords appear in narration
    if expected_kw:
        missing_expected = [kw for kw in expected_kw if kw not in all_narration]
        if missing_expected:
            issues.append(
                f"[CONTENT QA] Expected visual keywords NOT found in narration: {missing_expected}\n"
                f"  This may indicate the tutorial plan does not cover the expected content."
            )
        else:
            notes.append(f"[CONTENT QA] All expected_visual_keywords found in narration: {expected_kw}")

    # Check forbidden keywords do NOT appear in narration
    if forbidden_kw:
        found_forbidden = [kw for kw in forbidden_kw if kw in all_narration]
        if found_forbidden:
            issues.append(
                f"[CONTENT QA] FORBIDDEN visual keywords found in narration: {found_forbidden}\n"
                f"  CONTENT MISMATCH: Tutorial narration references content from another feature.\n"
                f"  This indicates the wrong tutorial plan was used."
            )
        else:
            notes.append(f"[CONTENT QA] No forbidden_visual_keywords found in narration: CLEAN")

    # 3. Check recording manifest for scene cross-reference
    rec_manifest_path = os.path.join(recording_dir, 'recording-manifest.json')
    if os.path.isfile(rec_manifest_path):
        try:
            rec_manifest = json.load(open(rec_manifest_path, 'r', encoding='utf-8'))
            rec_scenes = {s['scene_id'] for s in rec_manifest.get('timing', [])}
            plan_scenes = {(s.get('scene_id') or s.get('id')) for s in scenes}
            extra_scenes = rec_scenes - plan_scenes
            if extra_scenes:
                notes.append(
                    f"[CONTENT QA] Recording has extra scenes not in current plan: {extra_scenes}\n"
                    f"  (These may be from a previous tutorial run — verify recording isolation)"
                )
        except Exception as e:
            notes.append(f"[CONTENT QA] Could not parse recording-manifest.json for cross-check: {e}")
    else:
        issues.append(
            f"[CONTENT QA] recording-manifest.json not found in {recording_dir}\n"
            f"  Cannot verify scene-level content consistency. Re-run record.js V2.2."
        )

    return len(issues) == 0, issues, notes


# ──────────────────────────────────────────────────────────────────────
# V2.2: PROVENANCE QA
# ──────────────────────────────────────────────────────────────────────
def provenance_qa(plan, video_path, recording_dir, audio_path, subtitle_path):
    """
    V2.2 Provenance QA:
    - Verify run_id consistency between recording-manifest and render-provenance
    - Verify plan → recording → render chain
    
    Returns (ok: bool, issues: list[str], notes: list[str])
    """
    issues = []
    notes = []

    plan_tutorial_id = plan.get('tutorial_id') or plan.get('title', 'unknown')
    plan_hash = file_hash(args_plan_path)  # injected by main()

    # Load recording manifest
    rec_manifest = {}
    rec_manifest_path = os.path.join(recording_dir, 'recording-manifest.json') if recording_dir else None
    if rec_manifest_path and os.path.isfile(rec_manifest_path):
        try:
            rec_manifest = json.load(open(rec_manifest_path, 'r', encoding='utf-8'))
        except Exception as e:
            issues.append(f"[PROVENANCE QA] Cannot parse recording-manifest.json: {e}")

    # Load render provenance
    render_prov = {}
    video_dir = os.path.dirname(os.path.abspath(video_path))
    render_prov_path = os.path.join(video_dir, 'render-provenance.json')
    if os.path.isfile(render_prov_path):
        try:
            render_prov = json.load(open(render_prov_path, 'r', encoding='utf-8'))
        except Exception as e:
            issues.append(f"[PROVENANCE QA] Cannot parse render-provenance.json: {e}")
    else:
        issues.append(
            f"[PROVENANCE QA] render-provenance.json not found at {render_prov_path}\n"
            f"  This means render.py V2.2 was not used. Cannot verify full provenance chain."
        )

    # Check: recording run_id == render run_id
    rec_run_id = rec_manifest.get('run_id')
    render_run_id = render_prov.get('run_id')
    if rec_run_id and render_run_id and rec_run_id != render_run_id:
        issues.append(
            f"[PROVENANCE QA] Run ID mismatch between recording and render!\n"
            f"  Recording run_id: {rec_run_id}\n"
            f"  Render run_id:    {render_run_id}"
        )
    elif rec_run_id:
        notes.append(f"[PROVENANCE QA] Run ID: {rec_run_id} (consistent)")

    # Check: recording tutorial_id matches plan
    rec_tutorial_id = rec_manifest.get('tutorial_id', '')
    if rec_tutorial_id and rec_tutorial_id != plan_tutorial_id:
        def norm(s):
            return re.sub(r'[^a-z0-9]', '', s.lower())
        if norm(rec_tutorial_id) != norm(plan_tutorial_id) and \
           norm(rec_tutorial_id) not in norm(plan_tutorial_id) and \
           norm(plan_tutorial_id) not in norm(rec_tutorial_id):
            issues.append(
                f"[PROVENANCE QA] Tutorial ID mismatch: plan='{plan_tutorial_id}', recording='{rec_tutorial_id}'"
            )

    # Check: render recording_hash matches actual recording file
    if recording_dir:
        raw_rec = os.path.join(recording_dir, 'raw-recording.webm')
        if os.path.isfile(raw_rec):
            actual_rec_hash = file_hash(raw_rec)
            manifest_rec_hash = rec_manifest.get('recording_file_hash')
            render_rec_hash = render_prov.get('recording_hash')
            if manifest_rec_hash and actual_rec_hash and manifest_rec_hash != actual_rec_hash:
                issues.append(
                    f"[PROVENANCE QA] Recording file hash mismatch!\n"
                    f"  Manifest hash: {manifest_rec_hash}\n"
                    f"  Actual hash:   {actual_rec_hash}\n"
                    f"  Recording may have been replaced after manifest was written."
                )
            else:
                notes.append(f"[PROVENANCE QA] Recording file hash: {actual_rec_hash} (verified)")

    # Check: final video hash matches render-provenance
    if render_prov:
        prov_video_hash = render_prov.get('final_video_hash')
        actual_video_hash = file_hash(video_path)
        if prov_video_hash and actual_video_hash and prov_video_hash != actual_video_hash:
            issues.append(
                f"[PROVENANCE QA] Final video hash mismatch!\n"
                f"  Provenance hash: {prov_video_hash}\n"
                f"  Actual hash:     {actual_video_hash}\n"
                f"  The final video file was modified after render."
            )
        else:
            notes.append(f"[PROVENANCE QA] Final video hash: {actual_video_hash} (matches provenance)")

    # Check target_user
    plan_target_user = plan.get('target_user', '')
    prov_target_user = render_prov.get('target_user', '')
    if plan_target_user and prov_target_user and plan_target_user != prov_target_user:
        issues.append(
            f"[PROVENANCE QA] target_user mismatch: plan='{plan_target_user}', provenance='{prov_target_user}'"
        )
    elif plan_target_user:
        notes.append(f"[PROVENANCE QA] target_user: {plan_target_user} (consistent)")

    return len(issues) == 0, issues, notes


# Global for plan path (used inside provenance_qa)
args_plan_path = None


# ──────────────────────────────────────────────────────────────────────
# V2.3: SCENE QA — Gate 4
# ──────────────────────────────────────────────────────────────────────
def scene_qa(plan, recording_dir, final_evidence_dir, audio_meta_path, scripts_dir):
    """
    V2.3 Gate 4: Scene-level verification.
    Calls verify_scenes.py as subprocess and returns (ok, review, issues, notes).
    Returns (ok: bool, review: bool, issues: list, notes: list)
    """
    issues = []
    notes = []
    has_review = False

    # Check scene-evidence-manifest.json
    evidence_manifest = os.path.join(recording_dir, 'scene-evidence-manifest.json') if recording_dir else None
    if not evidence_manifest or not os.path.isfile(evidence_manifest):
        issues.append('[SCENE QA] scene-evidence-manifest.json not found — V2.3 record.js required')
        return False, False, issues, notes

    try:
        ev_data = json.load(open(evidence_manifest, 'r', encoding='utf-8'))
    except Exception as e:
        issues.append(f'[SCENE QA] Cannot parse scene-evidence-manifest.json: {e}')
        return False, False, issues, notes

    # Check overall status from recording
    ev_status = ev_data.get('overall_status', 'UNKNOWN')
    if ev_status == 'FAIL':
        issues.append('[SCENE QA] scene-evidence-manifest overall_status = FAIL')
        issues.append('  Recording detected forbidden content in one or more scenes.')
        return False, False, issues, notes
    elif ev_status == 'REVIEW':
        notes.append('[SCENE QA] scene-evidence-manifest overall_status = REVIEW (agent inspection needed)')
        has_review = True

    # Check scene count
    plan_scenes = plan.get('scenes', [])
    ev_scenes = ev_data.get('scenes', [])
    if len(ev_scenes) != len(plan_scenes):
        issues.append(f'[SCENE QA] Scene count mismatch: plan={len(plan_scenes)}, evidence={len(ev_scenes)}')
        return False, has_review, issues, notes
    else:
        notes.append(f'[SCENE QA] Scene count: {len(ev_scenes)}/{len(plan_scenes)} OK')

    # Check scene order (IDs match in same order)
    plan_scene_ids = [s.get('scene_id') for s in plan_scenes]
    ev_scene_ids = [s.get('scene_id') for s in ev_scenes]
    if plan_scene_ids == ev_scene_ids:
        notes.append(f'[SCENE QA] Scene order: PASS ({ev_scene_ids})')
    else:
        issues.append(f'[SCENE QA] Scene order mismatch: plan={plan_scene_ids}, recording={ev_scene_ids}')
        return False, has_review, issues, notes

    # Check per-scene evidence
    for scene_ev in ev_scenes:
        scene_id = scene_ev.get('scene_id', '?')
        ev_status_scene = scene_ev.get('verification_status', 'UNKNOWN')
        ev_hash = scene_ev.get('evidence_hash', 'none')
        screenshot = scene_ev.get('screenshot', '')
        url = scene_ev.get('url', '')
        text_found = scene_ev.get('text_found', [])
        text_missing = scene_ev.get('text_missing', [])
        forbidden_found = scene_ev.get('forbidden_text_found', [])

        if forbidden_found:
            issues.append(f'[SCENE QA] {scene_id}: Forbidden text found: {forbidden_found}')
        elif ev_status_scene == 'FAIL':
            issues.append(f'[SCENE QA] {scene_id}: status=FAIL')
        elif ev_status_scene == 'REVIEW':
            notes.append(f'[SCENE QA] {scene_id}: REVIEW — URL or text evidence needs inspection')
            has_review = True
        else:
            notes.append(f'[SCENE QA] {scene_id}: PASS (hash={ev_hash}, textFound={len(text_found)}/{len(text_found)+len(text_missing)})')

        # Check screenshot exists
        if screenshot and not os.path.isfile(screenshot):
            # Try relative path
            alt = os.path.join(recording_dir, f'{scene_id}.png') if recording_dir else None
            if alt and not os.path.isfile(alt):
                issues.append(f'[SCENE QA] {scene_id}: screenshot file missing: {screenshot}')

    # If final evidence dir provided, check frames exist
    if final_evidence_dir and os.path.isdir(final_evidence_dir):
        for scene_ev in ev_scenes:
            scene_id = scene_ev.get('scene_id', '?')
            frame_path = os.path.join(final_evidence_dir, f'{scene_id}.png')
            if os.path.isfile(frame_path):
                sz = os.path.getsize(frame_path)
                notes.append(f'[SCENE QA] {scene_id}: final frame found ({sz} bytes)')
            else:
                notes.append(f'[SCENE QA] {scene_id}: final frame not found (extract_scene_frames.py needed)')
    else:
        notes.append('[SCENE QA] final-scene-evidence dir not provided — run extract_scene_frames.py for visual comparison')

    # Call verify_scenes.py for full perceptual verification (optional, non-fatal)
    verify_script = os.path.join(scripts_dir, 'verify_scenes.py') if scripts_dir else None
    if verify_script and os.path.isfile(verify_script) and final_evidence_dir and os.path.isdir(final_evidence_dir):
        verify_report_path = os.path.join(os.path.dirname(evidence_manifest), 'scene-verification-report.json')
        verify_args = [
            sys.executable, verify_script,
            '--evidence-manifest', evidence_manifest,
            '--final-evidence-dir', final_evidence_dir,
            '--out-report', verify_report_path
        ]
        if audio_meta_path and os.path.isfile(audio_meta_path):
            verify_args += ['--audio-meta', audio_meta_path]
        if args_plan_path:
            verify_args += ['--plan', args_plan_path]

        try:
            result = subprocess.run(verify_args, capture_output=True, text=True, timeout=60)
            if result.returncode == 0:
                notes.append('[SCENE QA] verify_scenes.py: PASS')
            elif result.returncode == 2:
                notes.append('[SCENE QA] verify_scenes.py: REVIEW (agent inspection needed)')
                has_review = True
            else:
                issues.append('[SCENE QA] verify_scenes.py: FAIL — scene content mismatch detected')
                issues.append(f'  {result.stdout.strip()[-500:]}')
        except subprocess.TimeoutExpired:
            notes.append('[SCENE QA] verify_scenes.py timed out — skipped')
        except Exception as e:
            notes.append(f'[SCENE QA] verify_scenes.py error: {e}')
    else:
        notes.append('[SCENE QA] verify_scenes.py not called (no final-evidence-dir or script not found)')

    return len(issues) == 0, has_review, issues, notes


# ──────────────────────────────────────────────────────────────────────
# V2.3: AUDIO-VISUAL QA — Gate 5
# ──────────────────────────────────────────────────────────────────────
def audio_visual_qa(plan, recording_dir, audio_path, subtitle_path):
    """
    V2.3 Gate 5: Audio-visual alignment.
    Checks that narration and subtitle ordering is consistent with scene plan.
    Returns (ok: bool, issues: list, notes: list)
    """
    issues = []
    notes = []

    scenes = plan.get('scenes', [])
    if not scenes:
        notes.append('[AUDIO-VISUAL QA] No scenes in plan — skipped')
        return True, issues, notes

    # Check scene-audio-map.json if exists
    audio_map_path = os.path.join(recording_dir, '..', 'audio', 'scene-audio-map.json') if recording_dir else None
    if audio_map_path and os.path.isfile(audio_map_path):
        try:
            audio_map = json.load(open(audio_map_path, 'r', encoding='utf-8'))
            plan_scene_ids = [s.get('scene_id') for s in scenes]
            mapped_ids = list(audio_map.keys())
            notes.append(f'[AUDIO-VISUAL QA] scene-audio-map.json: {len(mapped_ids)} scenes mapped')

            # Check order consistency
            # plan order vs audio map order
            mapped_in_plan = [sid for sid in plan_scene_ids if sid in audio_map]
            if mapped_in_plan == [sid for sid in mapped_ids if sid in plan_scene_ids]:
                notes.append('[AUDIO-VISUAL QA] Scene-audio order: PASS')
            else:
                issues.append(f'[AUDIO-VISUAL QA] Scene-audio order mismatch: plan={plan_scene_ids}, audio_map={mapped_ids}')

            # Check no scene has zero-duration audio
            for scene_id, entry in audio_map.items():
                dur = entry.get('duration', 0)
                if dur < 0.5:
                    issues.append(f'[AUDIO-VISUAL QA] Scene {scene_id}: audio duration too short ({dur:.2f}s)')
                else:
                    notes.append(f'[AUDIO-VISUAL QA] {scene_id}: audio {entry.get("audio_start",0):.1f}s–{entry.get("audio_end",0):.1f}s ({dur:.1f}s)')
        except Exception as e:
            notes.append(f'[AUDIO-VISUAL QA] Cannot parse scene-audio-map.json: {e}')
    else:
        notes.append('[AUDIO-VISUAL QA] scene-audio-map.json not found — run verify_scenes.py to generate')

    # Check narration coverage: all scenes should have non-empty narration
    empty_narration = []
    for scene in scenes:
        narr = (scene.get('narration') or '').strip()
        if not narr:
            empty_narration.append(scene.get('scene_id', '?'))
    if empty_narration:
        issues.append(f'[AUDIO-VISUAL QA] Scenes with empty narration: {empty_narration}')
    else:
        notes.append('[AUDIO-VISUAL QA] All scenes have non-empty narration: PASS')

    # Check subtitle file covers expected duration
    if subtitle_path and os.path.isfile(subtitle_path):
        try:
            with open(subtitle_path, 'r', encoding='utf-8') as f:
                srt_content = f.read()
            ts_pattern = re.compile(r'(\d{2}:\d{2}:\d{2}[,\.]\d{3})\s*-->\s*(\d{2}:\d{2}:\d{2}[,\.]\d{3})')
            timestamps = ts_pattern.findall(srt_content)
            subtitle_count = len(timestamps)
            notes.append(f'[AUDIO-VISUAL QA] Subtitle segments: {subtitle_count}')
            if subtitle_count < len(scenes):
                notes.append(f'[AUDIO-VISUAL QA] Subtitle segments ({subtitle_count}) < scenes ({len(scenes)}) — some scenes may share segments')
        except Exception as e:
            notes.append(f'[AUDIO-VISUAL QA] Could not parse subtitle file: {e}')

    # Check audio file exists and has non-zero size
    if audio_path and os.path.isfile(audio_path):
        audio_size = os.path.getsize(audio_path)
        notes.append(f'[AUDIO-VISUAL QA] Narration audio: {audio_size} bytes (present)')
    elif audio_path:
        issues.append(f'[AUDIO-VISUAL QA] Narration audio file missing: {audio_path}')

    return len(issues) == 0, issues, notes


def main():
    global args_plan_path

    p = argparse.ArgumentParser(description='V2.3 Five-Gate Validator — Technical + Content + Provenance + Scene + Audio-Visual.')
    p.add_argument('--video', required=True, help='Path to final MP4 video')
    p.add_argument('--plan', help='Path to tutorial plan JSON')
    p.add_argument('--subtitles', help='Path to subtitle SRT file')
    p.add_argument('--recording-dir', help='Path to recording directory (for content & provenance & scene QA)')
    p.add_argument('--audio', help='Path to narration audio file')
    p.add_argument('--audio-meta', help='Path to audio_meta.json from TTS (for audio-visual alignment)')
    p.add_argument('--final-evidence-dir', help='Path to final-scene-evidence/ directory (from extract_scene_frames.py)')
    p.add_argument('--min-duration', type=float, default=60.0, help='Minimum video duration in seconds')
    p.add_argument('--max-duration', type=float, default=90.0, help='Maximum video duration in seconds')
    p.add_argument('--skip-content-qa', action='store_true', help='Skip content QA')
    p.add_argument('--skip-provenance-qa', action='store_true', help='Skip provenance QA')
    p.add_argument('--skip-scene-qa', action='store_true', help='Skip scene QA (Gate 4)')
    p.add_argument('--skip-audiovisual-qa', action='store_true', help='Skip audio-visual QA (Gate 5)')
    p.add_argument('--qa-report-dir', help='Directory to write qa-v23.md report')
    args = p.parse_args()

    args_plan_path = args.plan

    tech_errors = []
    content_issues = []
    content_notes = []
    prov_issues = []
    prov_notes = []
    
    # ── TECHNICAL QA ──
    print("\n=== TECHNICAL QA ===")
    
    if not os.path.isfile(args.video):
        tech_errors.append(f"Final video file does not exist: {args.video}")
    elif os.path.getsize(args.video) == 0:
        tech_errors.append("Final video file size is 0 bytes.")
        
    if args.plan and not os.path.isfile(args.plan):
        tech_errors.append(f"Tutorial plan file does not exist: {args.plan}")
        
    if args.subtitles:
        srt_errors = validate_srt_file(args.subtitles)
        tech_errors.extend(srt_errors)

    ffprobe = shutil.which('ffprobe')
    ffmpeg = shutil.which('ffmpeg')
    if not ffmpeg:
        try:
            import imageio_ffmpeg
            ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
        except Exception:
            pass

    duration = 0.0
    width = 0
    height = 0
    has_video = False
    has_audio = False
    fps = 0.0

    if os.path.isfile(args.video):
        if ffprobe:
            cmd = [ffprobe, '-v', 'error', '-show_entries',
                   'format=duration:stream=codec_type,width,height,r_frame_rate,codec_name',
                   '-of', 'json', args.video]
            r = subprocess.run(cmd, capture_output=True, text=True)
            if r.returncode != 0:
                tech_errors.append(f"ffprobe failed to read video: {r.stderr.strip()}")
            else:
                try:
                    data = json.loads(r.stdout)
                    fmt = data.get('format', {})
                    duration = float(fmt.get('duration', 0))
                    streams = data.get('streams', [])
                    for s in streams:
                        ctype = s.get('codec_type')
                        if ctype == 'video':
                            has_video = True
                            width = s.get('width', 0)
                            height = s.get('height', 0)
                            r_fps = s.get('r_frame_rate', '0/1')
                            if '/' in r_fps:
                                num, den = r_fps.split('/')
                                fps = float(num) / float(den) if float(den) != 0 else 0
                            else:
                                fps = float(r_fps)
                        elif ctype == 'audio':
                            has_audio = True
                except Exception as e:
                    tech_errors.append(f"Failed to parse ffprobe JSON output: {e}")
        elif ffmpeg:
            r = subprocess.run([ffmpeg, '-i', args.video], capture_output=True, text=True)
            out = r.stderr
            dur_match = re.search(r'Duration:\s*(\d+):(\d+):([\d\.]+)', out)
            if dur_match:
                h, m, s = dur_match.groups()
                duration = int(h) * 3600 + int(m) * 60 + float(s)
            if 'Video:' in out:
                has_video = True
                res_match = re.search(r'(\d{3,4})x(\d{3,4})', out)
                if res_match:
                    width, height = int(res_match.group(1)), int(res_match.group(2))
                fps_match = re.search(r'([\d\.]+)\s*fps', out)
                if fps_match:
                    fps = float(fps_match.group(1))
            if 'Audio:' in out:
                has_audio = True
        else:
            tech_errors.append("Neither ffprobe nor ffmpeg available for media stream analysis.")

    if not has_video:
        tech_errors.append("Video stream missing from final MP4.")
    if not has_audio:
        tech_errors.append("Audio stream missing from final MP4.")
    if width < 1280 or height < 720:
        tech_errors.append(f"Resolution invalid or too low: {width}x{height} (Minimum required 1280x720, target 1920x1080).")
    if fps < 15.0 or fps > 60.0:
        tech_errors.append(f"Frame rate out of valid range: {fps:.2f} fps (Expected 15–60 fps).")
    if duration < args.min_duration:
        tech_errors.append(f"Duration too short: {duration:.2f}s < minimum target {args.min_duration:.0f}s. (FAIL)")
    elif duration > args.max_duration:
        tech_errors.append(f"Duration too long: {duration:.2f}s > maximum target {args.max_duration:.0f}s. (FAIL)")
    if ffmpeg and has_video and not tech_errors:
        bs_err = check_black_screen(ffmpeg, args.video)
        if bs_err:
            tech_errors.append(bs_err)

    # ── CONTENT QA ──
    print("\n=== CONTENT QA ===")
    plan = None
    if args.plan and os.path.isfile(args.plan):
        plan = json.load(open(args.plan, 'r', encoding='utf-8'))

    if not args.skip_content_qa and plan:
        rec_dir = args.recording_dir
        if not rec_dir:
            # Auto-detect recording dir
            video_dir = os.path.dirname(os.path.abspath(args.video))
            # Go up until we find recording/ subdir
            candidate = os.path.join(os.path.dirname(video_dir), 'recording')
            if os.path.isdir(candidate):
                rec_dir = candidate
            else:
                # Try sibling
                candidate2 = os.path.join(os.path.dirname(os.path.abspath(args.video)), '..', 'recording')
                if os.path.isdir(candidate2):
                    rec_dir = os.path.normpath(candidate2)

        content_ok, content_issues, content_notes = content_qa(plan, rec_dir)
        for note in content_notes:
            print(f"  {note}")
        if content_issues:
            for issue in content_issues:
                print(f"  [FAIL] {issue}")
    else:
        if args.skip_content_qa:
            content_notes.append("Content QA SKIPPED (--skip-content-qa flag set)")
        elif not plan:
            content_notes.append("Content QA SKIPPED (no plan provided)")

    # ── PROVENANCE QA ──
    print("\n=== PROVENANCE QA ===")
    if not args.skip_provenance_qa and plan:
        rec_dir = args.recording_dir
        if not rec_dir:
            video_parent = os.path.dirname(os.path.abspath(args.video))
            candidate = os.path.join(os.path.dirname(video_parent), 'recording')
            if os.path.isdir(candidate):
                rec_dir = candidate

        prov_ok, prov_issues, prov_notes = provenance_qa(
            plan, args.video, rec_dir, args.audio, args.subtitles
        )
        for note in prov_notes:
            print(f"  {note}")
        if prov_issues:
            for issue in prov_issues:
                print(f"  [FAIL] {issue}")
    else:
        if args.skip_provenance_qa:
            prov_notes.append("Provenance QA SKIPPED (--skip-provenance-qa flag set)")
        elif not plan:
            prov_notes.append("Provenance QA SKIPPED (no plan provided)")

    # ── GATE 4: SCENE QA (V2.3) ──
    print("\n=== SCENE QA ===")
    scene_issues = []
    scene_notes = []
    scene_has_review = False

    scripts_dir = os.path.dirname(os.path.abspath(__file__)) if '__file__' in dir() else None
    if not args.skip_scene_qa and plan:
        rec_dir_for_scene = args.recording_dir
        if not rec_dir_for_scene:
            video_parent = os.path.dirname(os.path.abspath(args.video))
            candidate = os.path.join(os.path.dirname(video_parent), 'recording')
            if os.path.isdir(candidate):
                rec_dir_for_scene = candidate

        if rec_dir_for_scene:
            final_ev_dir = args.final_evidence_dir
            scene_ok, scene_has_review, scene_issues, scene_notes = scene_qa(
                plan, rec_dir_for_scene, final_ev_dir, args.audio_meta, scripts_dir
            )
            for note in scene_notes:
                print(f"  {note}")
            if scene_issues:
                for issue in scene_issues:
                    print(f"  [FAIL] {issue}")
        else:
            scene_notes.append('[SCENE QA] recording-dir not found — skipped')
            print('  [SCENE QA] recording-dir not found — skipped')
    else:
        if args.skip_scene_qa:
            scene_notes.append('Scene QA SKIPPED (--skip-scene-qa)')
        elif not plan:
            scene_notes.append('Scene QA SKIPPED (no plan)')
        print(f'  {scene_notes[-1]}')

    # ── GATE 5: AUDIO-VISUAL QA (V2.3) ──
    print("\n=== AUDIO-VISUAL QA ===")
    av_issues = []
    av_notes = []

    if not args.skip_audiovisual_qa and plan:
        rec_dir_for_av = args.recording_dir
        if not rec_dir_for_av:
            video_parent = os.path.dirname(os.path.abspath(args.video))
            candidate = os.path.join(os.path.dirname(video_parent), 'recording')
            if os.path.isdir(candidate):
                rec_dir_for_av = candidate

        av_ok, av_issues, av_notes = audio_visual_qa(
            plan, rec_dir_for_av, args.audio, args.subtitles
        )
        for note in av_notes:
            print(f"  {note}")
        if av_issues:
            for issue in av_issues:
                print(f"  [FAIL] {issue}")
    else:
        if args.skip_audiovisual_qa:
            av_notes.append('Audio-Visual QA SKIPPED (--skip-audiovisual-qa)')
        elif not plan:
            av_notes.append('Audio-Visual QA SKIPPED (no plan)')
        print(f'  {av_notes[-1]}')

    # ── FINAL 5-GATE DECISION ──
    all_errors = tech_errors + content_issues + prov_issues + scene_issues + av_issues
    has_review = scene_has_review

    print("\n" + "="*70)

    final_status = 'FAIL' if all_errors else ('REVIEW' if has_review else 'PASS')

    if all_errors:
        print('QUALITY GATE STATUS: FAIL')
    elif has_review:
        print('QUALITY GATE STATUS: REVIEW (agent inspection required)')
    else:
        print('QUALITY GATE STATUS: PASS')

    def gate_summary(label, errors, notes):
        print(f'\n{label}:')
        if errors:
            for e in errors:
                print(f"  - [FAIL] {e}")
        else:
            print(f"  - [PASS] All {label.lower()} checks passed")

    gate_summary('TECHNICAL QA', tech_errors,
        [f'Duration: {duration:.2f}s', f'Resolution: {width}x{height}', f'FPS: {fps:.2f}'])
    gate_summary('CONTENT QA', content_issues, content_notes)
    gate_summary('PROVENANCE QA', prov_issues, prov_notes)
    gate_summary('SCENE QA', scene_issues, scene_notes)
    gate_summary('AUDIO-VISUAL QA', av_issues, av_notes)

    # ── V2.3: Write qa-v23.md ──
    qa_report_dir = args.qa_report_dir
    if not qa_report_dir and args.recording_dir:
        qa_report_dir = os.path.dirname(args.recording_dir)  # run_dir
    if not qa_report_dir:
        qa_report_dir = os.path.dirname(os.path.abspath(args.video))

    def g(errors): return 'PASS' if not errors else 'FAIL'
    def s(status): return {'PASS': '✅', 'REVIEW': '⚠️', 'FAIL': '❌'}.get(status, '?')

    qa_lines = [
        f'# Quality Gate V2.3 Report',
        f'',
        f'**Date**: {datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")}',
        f'**Tutorial Plan**: {args.plan or "N/A"}',
        f'**Final Video**: {args.video}',
        f'**Final Status**: {s(final_status)} **{final_status}**',
        f'',
        f'---',
        f'',
        f'## Gate 1 — Technical QA: {s(g(tech_errors))} {g(tech_errors)}',
        f'',
        f'- Duration: {duration:.2f}s (Target: {args.min_duration:.0f}–{args.max_duration:.0f}s)',
        f'- Resolution: {width}x{height}',
        f'- Frame Rate: {fps:.2f} fps',
        f'- Video Stream: {"Present" if has_video else "MISSING"}',
        f'- Audio Stream: {"Present" if has_audio else "MISSING"}',
        f'- Subtitles: {"Verified UTF-8 SRT" if args.subtitles else "N/A"}',
    ] + ([f'- ❌ {e}' for e in tech_errors] if tech_errors else []) + [
        f'',
        f'## Gate 2 — Provenance QA: {s(g(prov_issues))} {g(prov_issues)}',
        f'',
    ] + [f'- {n}' for n in prov_notes] + \
    ([f'- ❌ {e}' for e in prov_issues] if prov_issues else [f'- All provenance checks passed']) + [
        f'',
        f'## Gate 3 — Content QA: {s(g(content_issues))} {g(content_issues)}',
        f'',
    ] + [f'- {n}' for n in content_notes] + \
    ([f'- ❌ {e}' for e in content_issues] if content_issues else [f'- All content checks passed']) + [
        f'',
        f'## Gate 4 — Scene QA: {s("REVIEW" if scene_has_review and not scene_issues else g(scene_issues))} {"REVIEW" if scene_has_review and not scene_issues else g(scene_issues)}',
        f'',
    ] + [f'- {n}' for n in scene_notes] + \
    ([f'- ❌ {e}' for e in scene_issues] if scene_issues else [f'- All scene checks passed']) + [
        f'',
        f'## Gate 5 — Audio-Visual QA: {s(g(av_issues))} {g(av_issues)}',
        f'',
    ] + [f'- {n}' for n in av_notes] + \
    ([f'- ❌ {e}' for e in av_issues] if av_issues else [f'- All audio-visual checks passed']) + [
        f'',
        f'---',
        f'',
        f'## Final Verdict',
        f'',
        f'| Gate | Status |',
        f'|------|--------|',
        f'| Technical | {s(g(tech_errors))} {g(tech_errors)} |',
        f'| Provenance | {s(g(prov_issues))} {g(prov_issues)} |',
        f'| Content | {s(g(content_issues))} {g(content_issues)} |',
        f'| Scene | {s("REVIEW" if scene_has_review and not scene_issues else g(scene_issues))} {"REVIEW" if scene_has_review and not scene_issues else g(scene_issues)} |',
        f'| Audio-Visual | {s(g(av_issues))} {g(av_issues)} |',
        f'| **FINAL** | {s(final_status)} **{final_status}** |',
    ]

    try:
        os.makedirs(qa_report_dir, exist_ok=True)
        qa_path = os.path.join(qa_report_dir, 'qa-v23.md')
        with open(qa_path, 'w', encoding='utf-8') as f:
            f.write('\n'.join(qa_lines))
        print(f'\n[V2.3] QA report written: {qa_path}')
    except Exception as e:
        print(f'[V2.3] Warning: could not write qa-v23.md: {e}')

    sys.exit(0 if not all_errors else 1)

if __name__ == '__main__':
    main()
