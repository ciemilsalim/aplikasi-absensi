#!/usr/bin/env python
# render.py — V2.2 (Provenance Check + Content Consistency Fix)
# BUG FIXES:
#   [BUG#4] No provenance check before render — now validates tutorial_id match
#   Requires recording-manifest.json to exist and tutorial_id to match plan
import argparse, hashlib, json, os, shutil, subprocess, sys
from PIL import Image, ImageDraw, ImageFont

def get_ffmpeg():
    ffmpeg = shutil.which('ffmpeg')
    if not ffmpeg:
        try:
            import imageio_ffmpeg
            ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
        except Exception:
            pass
    if not ffmpeg:
        print('Error: FFmpeg binary not found on PATH or via imageio-ffmpeg.', file=sys.stderr)
        sys.exit(3)
    return ffmpeg

def file_hash(path):
    """Compute SHA256 hex digest of a file (first 16 chars)."""
    if not os.path.isfile(path):
        return None
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        for chunk in iter(lambda: f.read(8192), b''):
            h.update(chunk)
    return h.hexdigest()[:16]

def create_titlecard_image(text_title, text_sub, out_png_path, bg_color=(15, 23, 42)):
    os.makedirs(os.path.dirname(os.path.abspath(out_png_path)), exist_ok=True)
    w, h = 1920, 1080
    img = Image.new('RGB', (w, h), color=bg_color)
    draw = ImageDraw.Draw(img)

    # Decorative accent bar
    draw.rectangle([w//2 - 60, h//2 - 120, w//2 + 60, h//2 - 112], fill=(59, 130, 246))

    font_title = None
    font_sub = None
    try:
        font_title = ImageFont.truetype("arial.ttf", 64)
        font_sub = ImageFont.truetype("arial.ttf", 34)
    except Exception:
        font_title = ImageFont.load_default()
        font_sub = ImageFont.load_default()

    if hasattr(draw, 'textbbox'):
        bbox_t = draw.textbbox((0, 0), text_title, font=font_title)
        tw, th = bbox_t[2] - bbox_t[0], bbox_t[3] - bbox_t[1]
    else:
        tw, th = draw.textsize(text_title, font=font_title)
    
    draw.text(((w - tw) // 2, (h - th) // 2 - 40), text_title, fill=(255, 255, 255), font=font_title)

    if hasattr(draw, 'textbbox'):
        bbox_s = draw.textbbox((0, 0), text_sub, font=font_sub)
        sw, sh = bbox_s[2] - bbox_s[0], bbox_s[3] - bbox_s[1]
    else:
        sw, sh = draw.textsize(text_sub, font=font_sub)

    draw.text(((w - sw) // 2, (h - sh) // 2 + 50), text_sub, fill=(148, 163, 184), font=font_sub)

    img.save(out_png_path)

def create_titlecard_video(ffmpeg, text_title, text_sub, duration, out_path, temp_dir):
    png_path = os.path.join(temp_dir, f"titlecard_{hash(text_title)}.png")
    create_titlecard_image(text_title, text_sub, png_path)

    cmd = [
        ffmpeg, '-y', '-loop', '1', '-i', png_path,
        '-t', str(duration), '-r', '30', '-an',
        '-c:v', 'libx264', '-preset', 'fast', '-pix_fmt', 'yuv420p',
        out_path
    ]
    subprocess.run(cmd, check=True)

# ──────────────────────────────────────────────────────────────────────
# V2.2: PROVENANCE CHECK
# ──────────────────────────────────────────────────────────────────────
def check_provenance(plan, video_path, run_dir=None):
    """
    V2.2 provenance check: verify that the recording used for render
    was produced for the same tutorial_id as the plan.
    
    Returns (ok: bool, errors: list[str])
    """
    errors = []

    plan_tutorial_id = plan.get('tutorial_id') or plan.get('title', 'unknown')
    plan_target_user = plan.get('target_user', 'unknown')
    plan_mode = plan.get('mode', 'unknown')

    # Look for recording-manifest.json in the same dir as the video
    video_dir = os.path.dirname(os.path.abspath(video_path))
    manifest_path = os.path.join(video_dir, 'recording-manifest.json')

    if not os.path.isfile(manifest_path):
        errors.append(
            f"[PROVENANCE FAIL] recording-manifest.json not found at: {manifest_path}\n"
            f"  This means the recording has no provenance binding. Cannot verify content consistency.\n"
            f"  Re-run record.js (V2.2) to generate a fresh recording with provenance manifest."
        )
        return False, errors

    try:
        rec_manifest = json.load(open(manifest_path, 'r', encoding='utf-8'))
    except Exception as e:
        errors.append(f"[PROVENANCE FAIL] Cannot parse recording-manifest.json: {e}")
        return False, errors

    rec_tutorial_id = rec_manifest.get('tutorial_id', 'unknown')
    rec_target_user = rec_manifest.get('target_user', 'unknown')
    rec_mode = rec_manifest.get('mode', 'unknown')
    rec_run_id = rec_manifest.get('run_id')
    rec_file_hash = rec_manifest.get('recording_file_hash')

    print(f"\n[V2.2] PROVENANCE CHECK")
    print(f"  Plan tutorial_id  : {plan_tutorial_id}")
    print(f"  Recording tutorial_id : {rec_tutorial_id}")
    print(f"  Plan target_user  : {plan_target_user}")
    print(f"  Recording target_user : {rec_target_user}")
    print(f"  Plan mode         : {plan_mode}")
    print(f"  Recording mode    : {rec_mode}")
    print(f"  Recording run_id  : {rec_run_id}")
    print(f"  Recording file hash (manifest): {rec_file_hash}")

    # Verify recording file hash matches manifest
    actual_hash = file_hash(video_path)
    print(f"  Recording file hash (actual)  : {actual_hash}")

    if rec_file_hash and actual_hash and rec_file_hash != actual_hash:
        errors.append(
            f"[PROVENANCE FAIL] Recording file hash mismatch!\n"
            f"  Expected (from manifest): {rec_file_hash}\n"
            f"  Actual file hash:         {actual_hash}\n"
            f"  The recording file may have been replaced or corrupted since manifest was written."
        )

    # Check tutorial_id match — normalize for comparison
    def normalize_id(s):
        import re
        return re.sub(r'[^a-z0-9]', '', s.lower()) if s else ''

    plan_id_norm = normalize_id(plan_tutorial_id)
    rec_id_norm = normalize_id(rec_tutorial_id)

    # If both IDs are non-trivial, compare them
    # Allow partial match if plan title is contained in rec or vice versa
    id_match = (plan_id_norm == rec_id_norm) or \
               (plan_id_norm in rec_id_norm) or \
               (rec_id_norm in plan_id_norm)

    if not id_match and len(plan_id_norm) > 5 and len(rec_id_norm) > 5:
        errors.append(
            f"[PROVENANCE FAIL] Tutorial ID mismatch between plan and recording!\n"
            f"  Plan tutorial_id: '{plan_tutorial_id}'\n"
            f"  Recording tutorial_id: '{rec_tutorial_id}'\n"
            f"  The recording was made for a DIFFERENT tutorial. DO NOT render this video.\n"
            f"  ACTION REQUIRED: Re-run record.js for the correct tutorial."
        )

    # Check target_user match
    if plan_target_user != rec_target_user:
        errors.append(
            f"[PROVENANCE FAIL] Target user mismatch!\n"
            f"  Plan target_user: '{plan_target_user}'\n"
            f"  Recording target_user: '{rec_target_user}'"
        )

    # Check mode match
    if plan_mode != rec_mode:
        errors.append(
            f"[PROVENANCE WARNING] Mode mismatch (non-fatal):\n"
            f"  Plan mode: '{plan_mode}'\n"
            f"  Recording mode: '{rec_mode}'"
        )

    if errors:
        print(f"\n[V2.2] PROVENANCE STATUS: FAIL ({len(errors)} error(s))")
        return False, errors

    print(f"\n[V2.2] PROVENANCE STATUS: PASS")
    return True, []


def main():
    p = argparse.ArgumentParser(description='V2.2 Professional Tutorial Video Render Engine (Provenance-Aware).')
    p.add_argument('--plan', required=True, help='Path to tutorial-plan.json')
    p.add_argument('--video', required=True, help='Path to raw webm/mp4 recording')
    p.add_argument('--audio', required=True, help='Path to narration mp3 audio')
    p.add_argument('--output', required=True, help='Path to final MP4 file')
    p.add_argument('--subtitles', help='Path to SRT subtitle file')
    p.add_argument('--bgm', help='Path to optional background music file')
    p.add_argument('--target-duration', type=float, default=75.0, help='Target total video duration')
    p.add_argument('--run-id', help='Run ID for provenance tracking')
    p.add_argument('--skip-provenance-check', action='store_true', help='[DANGEROUS] Skip provenance check (for debugging only)')
    args = p.parse_args()

    ffmpeg = get_ffmpeg()
    plan = json.load(open(args.plan, 'r', encoding='utf-8'))
    
    # ── V2.2 FIX #4: PROVENANCE CHECK before any rendering ──
    if not args.skip_provenance_check:
        prov_ok, prov_errors = check_provenance(plan, args.video)
        if not prov_ok:
            print("\n" + "="*70, file=sys.stderr)
            print("RENDER ABORTED — PROVENANCE CHECK FAILED", file=sys.stderr)
            print("="*70, file=sys.stderr)
            for e in prov_errors:
                print(f"\n{e}", file=sys.stderr)
            print("\n  DO NOT create the final video. Fix the recording first.", file=sys.stderr)
            print("="*70, file=sys.stderr)
            sys.exit(10)  # exit code 10 = provenance failure
    else:
        print("[V2.2] WARNING: Provenance check SKIPPED (--skip-provenance-check flag set)")
    
    render_dir = os.path.dirname(os.path.abspath(args.output))
    temp_dir = os.path.join(render_dir, 'temp_render')
    os.makedirs(temp_dir, exist_ok=True)

    intro_cfg = plan.get('intro', {})
    outro_cfg = plan.get('outro', {})
    
    use_intro = intro_cfg.get('enabled', True)
    use_outro = outro_cfg.get('enabled', True)
    
    intro_dur = float(intro_cfg.get('duration', 4.0)) if use_intro else 0.0
    outro_dur = float(outro_cfg.get('duration', 4.0)) if use_outro else 0.0

    intro_file = os.path.join(temp_dir, 'intro.mp4')
    outro_file = os.path.join(temp_dir, 'outro.mp4')

    # 1. Create Intro & Outro titlecard videos (Video Only)
    if use_intro:
        create_titlecard_video(
            ffmpeg,
            text_title=intro_cfg.get('title', plan.get('title', 'TUTORIAL SIASEK')),
            text_sub=intro_cfg.get('subtitle', f"Panduan {plan.get('target_user', 'Pengguna')}"),
            duration=intro_dur,
            out_path=intro_file,
            temp_dir=temp_dir
        )
        
    if use_outro:
        create_titlecard_video(
            ffmpeg,
            text_title=outro_cfg.get('title', 'Tutorial Selesai'),
            text_sub=outro_cfg.get('subtitle', 'Terima kasih dan selamat bertugas.'),
            duration=outro_dur,
            out_path=outro_file,
            temp_dir=temp_dir
        )

    # 2. Main video processing & dynamic duration fitting
    target_total = args.target_duration
    target_main_video_dur = target_total - intro_dur - outro_dur

    print(f"Target Total: {target_total:.2f}s | Intro: {intro_dur}s | Outro: {outro_dur}s | Main Video Target: {target_main_video_dur:.2f}s")

    main_processed = os.path.join(temp_dir, 'main_processed.mp4')
    
    ffprobe = shutil.which('ffprobe')
    raw_video_dur = target_main_video_dur
    if ffprobe:
        r = subprocess.run([ffprobe, '-v', 'error', '-show_entries', 'format=duration', '-of', 'json', args.video], capture_output=True, text=True)
        if r.returncode == 0:
            raw_video_dur = float(json.loads(r.stdout).get('format', {}).get('duration', target_main_video_dur))
    else:
        # Fallback via ffmpeg -i
        r = subprocess.run([ffmpeg, '-i', args.video], capture_output=True, text=True)
        import re
        match = re.search(r'Duration:\s*(\d+):(\d+):([\d\.]+)', r.stderr)
        if match:
            h, m, s = match.groups()
            raw_video_dur = int(h) * 3600 + int(m) * 60 + float(s)

    print(f"Raw video duration: {raw_video_dur:.2f}s")
    
    pad_sec = 0.0
    pts_factor = 1.0
    if raw_video_dur < target_main_video_dur:
        pad_sec = target_main_video_dur - raw_video_dur
    elif raw_video_dur > target_main_video_dur + 5.0:
        pts_factor = target_main_video_dur / raw_video_dur

    vf_filters = ["scale=1920:1080,fps=30"]
    if pts_factor != 1.0:
        vf_filters.append(f"setpts={pts_factor:.4f}*PTS")
    if pad_sec > 0:
        vf_filters.append(f"tpad=stop_mode=clone:stop_duration={pad_sec:.2f}")

    vf_main = ','.join(vf_filters)

    cmd_main = [
        ffmpeg, '-y', '-i', args.video,
        '-vf', vf_main, '-an',
        '-c:v', 'libx264', '-preset', 'medium', '-crf', '20', '-pix_fmt', 'yuv420p',
        main_processed
    ]
    subprocess.run(cmd_main, check=True)

    # 3. Concatenate pure Video streams
    concat_list = os.path.join(temp_dir, 'concat.txt')
    with open(concat_list, 'w', encoding='utf-8') as f:
        if use_intro:
            f.write("file '{}'\n".format(intro_file.replace('\\', '/')))
        f.write("file '{}'\n".format(main_processed.replace('\\', '/')))
        if use_outro:
            f.write("file '{}'\n".format(outro_file.replace('\\', '/')))

    concatenated_video = os.path.join(temp_dir, 'concatenated.mp4')
    cmd_concat = [
        ffmpeg, '-y', '-f', 'concat', '-safe', '0', '-i', concat_list,
        '-c', 'copy', concatenated_video
    ]
    subprocess.run(cmd_concat, check=True)

    # 4. Final Render: Concatenated Video + Audio Narration + Subtitles
    audio_inputs = ['-i', args.audio]
    filter_complex = []
    
    use_bgm = plan.get('music', False) and args.bgm and os.path.isfile(args.bgm)
    
    if use_bgm:
        audio_inputs.extend(['-i', args.bgm])
        filter_complex.append(f"[1:a]apad=whole_dur={target_total},volume=1.0[narration];[2:a]volume=0.08[bgm];[narration][bgm]amix=inputs=2:duration=first[aout]")
        map_audio = '[aout]'
    else:
        filter_complex.append(f"[1:a]apad=whole_dur={target_total}[aout]")
        map_audio = '[aout]'

    cmd_final = [ffmpeg, '-y', '-i', concatenated_video] + audio_inputs
    
    vf_final = []
    if args.subtitles:
        sub_path = os.path.abspath(args.subtitles).replace('\\', '/').replace(':', '\\:')
        vf_final.append(
            f"subtitles='{sub_path}':force_style='FontSize=22,PrimaryColour=&H00FFFFFF,OutlineColour=&H00000000,BorderStyle=1,Outline=2,Shadow=1,MarginV=35'"
        )

    if filter_complex:
        cmd_final.extend(['-filter_complex', ';'.join(filter_complex)])
        cmd_final.extend(['-map', '0:v:0', '-map', map_audio])
    else:
        cmd_final.extend(['-map', '0:v:0', '-map', '1:a:0'])

    if vf_final:
        cmd_final.extend(['-vf', ','.join(vf_final)])

    cmd_final.extend([
        '-t', str(target_total),
        '-c:v', 'libx264', '-preset', 'medium', '-crf', '20', '-pix_fmt', 'yuv420p',
        '-c:a', 'aac', '-b:a', '192k', '-movflags', '+faststart',
        args.output
    ])

    print("Executing Final Render Pipeline:")
    print(" ".join(cmd_final))
    subprocess.run(cmd_final, check=True)

    # Clean up temp
    try:
        shutil.rmtree(temp_dir)
    except Exception:
        pass

    # ── V2.2: Write provenance into final manifest ──
    final_hash = file_hash(args.output)
    audio_hash = file_hash(args.audio)
    subtitle_hash = file_hash(args.subtitles) if args.subtitles else None
    plan_hash = file_hash(args.plan)

    # Load recording manifest for run_id
    video_dir = os.path.dirname(os.path.abspath(args.video))
    rec_manifest_path = os.path.join(video_dir, 'recording-manifest.json')
    rec_manifest = {}
    if os.path.isfile(rec_manifest_path):
        try:
            rec_manifest = json.load(open(rec_manifest_path, 'r', encoding='utf-8'))
        except Exception:
            pass

    provenance_manifest = {
        "render_version": "2.2",
        "tutorial_id": plan.get('tutorial_id') or plan.get('title', 'unknown'),
        "feature": plan.get('feature') or plan.get('mode', 'unknown'),
        "target_user": plan.get('target_user', 'unknown'),
        "mode": plan.get('mode', 'unknown'),
        "run_id": args.run_id or rec_manifest.get('run_id'),
        "plan_hash": plan_hash,
        "recording_hash": rec_manifest.get('recording_file_hash'),
        "audio_hash": audio_hash,
        "subtitle_hash": subtitle_hash,
        "final_video_hash": final_hash,
        "output_path": args.output,
        "rendered_at": __import__('datetime').datetime.now().isoformat()
    }

    provenance_path = os.path.join(os.path.dirname(os.path.abspath(args.output)), 'render-provenance.json')
    with open(provenance_path, 'w', encoding='utf-8') as f:
        json.dump(provenance_manifest, f, indent=2)

    print(f"\n[V2.2] Render provenance written: {provenance_path}")
    print(f"Final MP4 Rendered Successfully: {args.output}")

if __name__ == '__main__':
    main()
