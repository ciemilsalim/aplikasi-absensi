#!/usr/bin/env python
# extract_scene_frames.py — V2.3 (Phase 6)
# Extracts final-video frames at each scene's evidence_timestamp
# Uses scene-timeline.json from recording session for accurate timing
# Accounts for video speed adjustment (pts_factor) from render pipeline
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
    scene_evidence_timestamp_sec,  # timestamp from recording session
    recording_total_duration_sec,  # total recording duration
    intro_dur_sec,                 # intro titlecard duration (added before main video)
    target_main_video_dur_sec,     # target duration for main video content
):
    """
    Map recording evidence_timestamp → final video timestamp.
    
    The render pipeline may speed up or slow down the recording to fit
    target_main_video_dur. We apply the same scaling factor.
    Then add intro_dur offset since intro titlecard is prepended.
    """
    # Calculate pts_factor used in render.py
    pts_factor = 1.0
    if recording_total_duration_sec > target_main_video_dur_sec + 5.0:
        pts_factor = target_main_video_dur_sec / recording_total_duration_sec
    
    # Scale evidence timestamp
    scaled_ts = scene_evidence_timestamp_sec * pts_factor
    
    # Add intro offset
    final_ts = intro_dur_sec + scaled_ts
    
    return final_ts, pts_factor

def main():
    p = argparse.ArgumentParser(description='V2.3 Scene Frame Extractor — extract final video frames per scene.')
    p.add_argument('--video', required=True, help='Path to final MP4 video')
    p.add_argument('--timeline', required=True, help='Path to scene-timeline.json from recording')
    p.add_argument('--out-dir', required=True, help='Output directory for final scene frames')
    p.add_argument('--plan', help='Path to tutorial-plan.json (for intro/outro durations)')
    p.add_argument('--recording-duration', type=float, help='Total recording duration in seconds (overrides auto-detect)')
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

    # Load plan for intro/outro duration
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

    # Get actual video duration
    video_dur = get_video_duration(ffmpeg, args.video)
    print(f'[V2.3] Final video duration: {video_dur:.2f}s')
    print(f'[V2.3] Intro: {intro_dur}s | Target main: {target_main_dur}s | Outro: {outro_dur}s')

    # Get recording total duration from timeline
    recording_total = args.recording_duration
    if not recording_total:
        scene_ids = list(timeline.keys())
        if scene_ids:
            last_scene = timeline[scene_ids[-1]]
            recording_total = last_scene.get('end', last_scene.get('evidence_timestamp', 60.0))
        else:
            recording_total = target_main_dur
    print(f'[V2.3] Recording duration (from timeline): {recording_total:.2f}s')

    os.makedirs(args.out_dir, exist_ok=True)

    results = {}
    for scene_id, scene_data in timeline.items():
        evidence_ts = scene_data.get('evidence_timestamp', scene_data.get('end', 0))

        # Map to final video timestamp
        final_ts, pts_factor = compute_scene_video_timestamp(
            evidence_ts, recording_total, intro_dur, target_main_dur
        )

        print(f'\n[V2.3] Scene: {scene_id}')
        print(f'  Recording evidence_timestamp: {evidence_ts:.2f}s')
        print(f'  pts_factor: {pts_factor:.4f}')
        print(f'  Final video timestamp: {final_ts:.2f}s')

        # Clamp to valid video range
        final_ts = max(intro_dur, min(final_ts, video_dur - outro_dur - 0.1))

        # Try primary timestamp, -tolerance, +tolerance
        candidates = [final_ts, final_ts - args.tolerance, final_ts + args.tolerance]
        out_path = os.path.join(args.out_dir, f'{scene_id}.png')

        extracted = False
        for ts in candidates:
            ts = max(0.1, min(ts, video_dur - 0.1))
            if extract_frame(ffmpeg, args.video, ts, out_path):
                size = os.path.getsize(out_path)
                print(f'  Frame extracted at {ts:.2f}s -> {out_path} ({size} bytes)')
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
            'scenes': results
        }, f, indent=2)

    extracted_count = sum(1 for r in results.values() if r['extracted'])
    print(f'\n[V2.3] Extracted {extracted_count}/{len(results)} scene frames to {args.out_dir}')
    print(f'[V2.3] Extraction report: {report_path}')

    if extracted_count == 0:
        print('Error: No frames extracted.', file=sys.stderr)
        sys.exit(1)

if __name__ == '__main__':
    main()
