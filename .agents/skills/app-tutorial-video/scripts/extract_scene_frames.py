#!/usr/bin/env python
# extract_scene_frames.py — V3.3 (Phase 6 & 21: Scene & Focus Keyframe Extraction)
# Extracts final-video frames at each scene's evidence_timestamp
# V3.3 NEW:
#   [PHASE 21] 4 Focus Keyframes per focus scene:
#     - before: normal view before zoom starts
#     - zoom: peak zoom frame
#     - click: action execution moment (ripple & cursor)
#     - after: returned to normal framing after action
#   Outputs focus-evidence-manifest.json in focus-evidence/
import argparse, json, math, os, shutil, subprocess, sys

def get_ffmpeg():
    ffmpeg = shutil.which('ffmpeg')
    if not ffmpeg:
        try:
            import imageio_ffmpeg
            ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
        except Exception:
            pass
    if not ffmpeg:
        print('Error: FFmpeg not found.', file=sys.stderr)
        sys.exit(3)
    return ffmpeg

def get_video_duration(ffmpeg, video_path):
    """Get video duration in seconds via ffmpeg stderr."""
    import re
    r = subprocess.run([ffmpeg, '-i', video_path], capture_output=True, text=True)
    m = re.search(r'Duration:\s*(\d+):(\d+):([\d\.]+)', r.stderr)
    if m:
        h, mn, s = m.groups()
        return int(h)*3600 + int(mn)*60 + float(s)
    return 0.0

def extract_frame(ffmpeg, video_path, timestamp_sec, out_path):
    """Extract a single frame from video at given timestamp."""
    os.makedirs(os.path.dirname(os.path.abspath(out_path)), exist_ok=True)
    cmd = [
        ffmpeg, '-y',
        '-ss', f'{timestamp_sec:.3f}',
        '-i', video_path,
        '-frames:v', '1',
        '-q:v', '2',
        out_path
    ]
    r = subprocess.run(cmd, capture_output=True)
    return r.returncode == 0 and os.path.isfile(out_path) and os.path.getsize(out_path) > 0

def compute_scene_video_timestamp(
    scene_evidence_timestamp_sec,
    recording_total_duration_sec,
    intro_dur_sec,
    target_main_video_dur_sec,
):
    pts_factor = 1.0
    if recording_total_duration_sec > target_main_video_dur_sec + 5.0:
        pts_factor = target_main_video_dur_sec / recording_total_duration_sec
    scaled_ts = scene_evidence_timestamp_sec * pts_factor
    final_ts = intro_dur_sec + scaled_ts
    return final_ts, pts_factor

def main():
    p = argparse.ArgumentParser(description='V3.3 Scene & Focus Frame Extractor — extract final video frames & focus keyframes.')
    p.add_argument('--video', required=True, help='Path to final MP4 video')
    p.add_argument('--timeline', required=True, help='Path to scene-timeline.json from recording')
    p.add_argument('--out-dir', '--out', dest='out_dir', required=True, help='Output directory for final scene frames (final-scene-evidence/)')
    p.add_argument('--focus-timeline', help='Path to scene_focus_timeline.json (optional)')
    p.add_argument('--focus-evidence-dir', help='Output directory for focus keyframes (focus-evidence/)')
    p.add_argument('--plan', help='Path to tutorial-plan.json (for intro/outro durations)')
    p.add_argument('--recording-duration', type=float, help='Total recording duration in seconds')
    p.add_argument('--tolerance', type=float, default=0.5, help='Timestamp tolerance in seconds (default 0.5)')
    args = p.parse_args()

    ffmpeg = get_ffmpeg()

    if not os.path.isfile(args.video):
        print(f'Error: Video file not found: {args.video}', file=sys.stderr)
        sys.exit(1)

    if not os.path.isfile(args.timeline):
        print(f'Error: scene-timeline.json not found: {args.timeline}', file=sys.stderr)
        sys.exit(1)

    timeline = json.load(open(args.timeline, 'r', encoding='utf-8'))

    intro_dur = 4.0
    outro_dur = 4.0
    target_duration = 75.0
    if args.plan and os.path.isfile(args.plan):
        plan = json.load(open(args.plan, 'r', encoding='utf-8'))
        intro_cfg = plan.get('intro', {})
        outro_cfg = plan.get('outro', {})
        intro_dur = float(intro_cfg.get('duration', 4.0)) if intro_cfg.get('enabled', True) else 0.0
        outro_dur = float(outro_cfg.get('duration', 4.0)) if outro_cfg.get('enabled', True) else 0.0
        target_duration = float(plan.get('target_duration_seconds', 75.0))

    target_main_dur = target_duration - intro_dur - outro_dur
    video_dur = get_video_duration(ffmpeg, args.video)
    print(f'[V3.3] Final video duration: {video_dur:.2f}s')
    print(f'[V3.3] Intro: {intro_dur}s | Target main: {target_main_dur}s | Outro: {outro_dur}s')

    recording_total = args.recording_duration
    if not recording_total:
        scene_ids = list(timeline.keys())
        if scene_ids:
            last_scene = timeline[scene_ids[-1]]
            recording_total = last_scene.get('end', last_scene.get('evidence_timestamp', 60.0))
        else:
            recording_total = target_main_dur
    print(f'[V3.3] Recording duration (from timeline): {recording_total:.2f}s')

    os.makedirs(args.out_dir, exist_ok=True)

    results = {}
    pts_factor_used = 1.0

    for scene_id, scene_data in timeline.items():
        evidence_ts = scene_data.get('evidence_timestamp', scene_data.get('end', 0))
        final_ts, pts_factor = compute_scene_video_timestamp(
            evidence_ts, recording_total, intro_dur, target_main_dur
        )
        pts_factor_used = pts_factor
        final_ts = max(intro_dur, min(final_ts, video_dur - outro_dur - 0.1))

        candidates = [final_ts, final_ts - args.tolerance, final_ts + args.tolerance]
        out_path = os.path.join(args.out_dir, f'{scene_id}.png')

        extracted = False
        for ts in candidates:
            ts = max(0.1, min(ts, video_dur - 0.1))
            if extract_frame(ffmpeg, args.video, ts, out_path):
                size = os.path.getsize(out_path)
                print(f'  [Scene Evidence] {scene_id} -> {ts:.2f}s ({size} bytes)')
                extracted = True
                results[scene_id] = {
                    'scene_id': scene_id,
                    'evidence_timestamp_recording': evidence_ts,
                    'final_video_timestamp': ts,
                    'pts_factor': pts_factor,
                    'frame_path': out_path,
                    'frame_size_bytes': size,
                    'extracted': True
                }
                break

        if not extracted:
            print(f'  [WARN] Could not extract frame for {scene_id}', file=sys.stderr)
            results[scene_id] = {
                'scene_id': scene_id,
                'evidence_timestamp_recording': evidence_ts,
                'final_video_timestamp': final_ts,
                'pts_factor': pts_factor,
                'frame_path': out_path,
                'frame_size_bytes': 0,
                'extracted': False
            }

    # ── V3.3 PHASE 21: Extract Focus Keyframes (before, zoom, click, after) ──
    focus_timeline_path = args.focus_timeline
    if not focus_timeline_path:
        # Auto-detect in same directory as scene-timeline.json
        candidate_ft = os.path.join(os.path.dirname(args.timeline), 'scene_focus_timeline.json')
        if os.path.isfile(candidate_ft):
            focus_timeline_path = candidate_ft

    focus_ev_dir = args.focus_evidence_dir
    if not focus_ev_dir and focus_timeline_path:
        # Auto-detect sibling focus-evidence directory
        focus_ev_dir = os.path.join(os.path.dirname(os.path.abspath(args.out_dir)), 'focus-evidence')

    focus_results = {}
    if focus_timeline_path and os.path.isfile(focus_timeline_path) and focus_ev_dir:
        os.makedirs(focus_ev_dir, exist_ok=True)
        try:
            focus_timeline = json.load(open(focus_timeline_path, 'r', encoding='utf-8'))
            print(f'\n[V3.3] Extracting Focus Keyframes to: {focus_ev_dir}')

            for scene_id, f_data in focus_timeline.items():
                if not f_data.get('focus_enabled'):
                    continue

                timing = f_data.get('timing', {})
                t_in = timing.get('zoom_in_start', 0)
                t_h1 = timing.get('hold_start', t_in + 0.7)
                t_act = timing.get('action_time', t_h1)
                t_out = timing.get('zoom_out_end', t_act + 1.5)

                # Map recording seconds to final video seconds
                v_before = max(intro_dur, intro_dur + (max(0, t_in - 0.2)) * pts_factor_used)
                v_zoom = max(intro_dur, intro_dur + (t_h1 + 0.05) * pts_factor_used)
                v_click = max(intro_dur, intro_dur + t_act * pts_factor_used)
                v_after = min(video_dur - 0.2, intro_dur + (t_out + 0.2) * pts_factor_used)

                frames_def = [
                    ('before', v_before),
                    ('zoom', v_zoom),
                    ('click', v_click),
                    ('after', v_after)
                ]

                scene_focus_frames = {}
                for phase_name, v_ts in frames_def:
                    f_name = f'{scene_id}-{phase_name}.png'
                    f_path = os.path.join(focus_ev_dir, f_name)
                    ok = extract_frame(ffmpeg, args.video, v_ts, f_path)
                    sz = os.path.getsize(f_path) if ok else 0
                    scene_focus_frames[phase_name] = {
                        'file': f_name,
                        'path': f_path,
                        'timestamp_sec': round(v_ts, 2),
                        'size_bytes': sz,
                        'extracted': ok
                    }
                    print(f'  [Focus QA Frame] {scene_id} ({phase_name}) -> {v_ts:.2f}s ({sz} bytes)')

                focus_results[scene_id] = {
                    'scene_id': scene_id,
                    'target': f_data.get('target'),
                    'zoom_level': f_data.get('zoom_level'),
                    'frames': scene_focus_frames
                }

            # Write focus-evidence-manifest.json
            with open(os.path.join(focus_ev_dir, 'focus-evidence-manifest.json'), 'w', encoding='utf-8') as f:
                json.dump({
                    'video': args.video,
                    'total_focus_scenes': len(focus_results),
                    'pts_factor': pts_factor_used,
                    'events': focus_results
                }, f, indent=2)
            print(f'[V3.3] focus-evidence-manifest.json written ({len(focus_results)} focus events).')
        except Exception as e:
            print(f'[V3.3] Warning: Focus keyframe extraction error: {e}')

    # Write extraction report
    report_path = os.path.join(args.out_dir, 'extraction-report.json')
    with open(report_path, 'w', encoding='utf-8') as f:
        json.dump({
            'video': args.video,
            'video_duration': video_dur,
            'recording_duration': recording_total,
            'intro_dur': intro_dur,
            'target_main_dur': target_main_dur,
            'tolerance': args.tolerance,
            'scenes': results,
            'focus_scenes': focus_results
        }, f, indent=2)

    extracted_count = sum(1 for r in results.values() if r['extracted'])
    print(f'\n[V3.3] Extracted {extracted_count}/{len(results)} scene frames to {args.out_dir}')
    print(f'[V3.3] Extraction report: {report_path}')

if __name__ == '__main__':
    main()
