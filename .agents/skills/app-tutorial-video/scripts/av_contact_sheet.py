#!/usr/bin/env python
# av_contact_sheet.py -- V3.5.2.1 A/V Sync Evidence Contact Sheet
# Extracts frames at -150ms, -50ms, exact, +50ms, +150ms for each click event
# and start/mid/end for each typing event. Creates tiled contact sheet JPG via Pillow.
import argparse
import json
import math
import os
import shutil
import subprocess
import sys

from PIL import Image, ImageDraw

if sys.stdout and hasattr(sys.stdout, 'reconfigure'):
    try:
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    except Exception:
        pass


def get_ffmpeg():
    ffmpeg = shutil.which('ffmpeg')
    if not ffmpeg:
        try:
            import imageio_ffmpeg
            ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
        except Exception:
            pass
    if not ffmpeg:
        sys.exit(3)
    return ffmpeg


def extract_frame(ffmpeg, video, t_sec, out_path):
    cmd = [ffmpeg, '-y', '-ss', f'{max(0, t_sec):.4f}', '-i', video,
           '-frames:v', '1', '-q:v', '2', out_path]
    res = subprocess.run(cmd, capture_output=True, timeout=30)
    return res.returncode == 0 and os.path.isfile(out_path)


def tile_frames_pillow(frame_files, frame_labels, output_path, cols=5, is_mobile=False):
    n = len(frame_files)
    if n == 0:
        return False
    rows = math.ceil(n / cols)
    tile_w = 216 if is_mobile else 320
    tile_h = 384 if is_mobile else 180
    header_h = 24
    canvas_w = cols * tile_w
    canvas_h = rows * (tile_h + header_h)

    sheet_img = Image.new('RGB', (canvas_w, canvas_h), color=(15, 20, 30))
    draw = ImageDraw.Draw(sheet_img)

    for i, (ff, lbl) in enumerate(zip(frame_files, frame_labels)):
        col = i % cols
        row = i // cols
        x = col * tile_w
        y = row * (tile_h + header_h)

        draw.text((x + 6, y + 5), lbl, fill=(220, 230, 255))
        if os.path.isfile(ff):
            try:
                with Image.open(ff) as img:
                    resized = img.resize((tile_w, tile_h), Image.Resampling.LANCZOS)
                    sheet_img.paste(resized, (x, y + header_h))
            except Exception:
                pass

    sheet_img.save(output_path, 'JPEG', quality=88)
    return os.path.isfile(output_path)


def run_av_contact_sheet(video_path, recording_dir, output_dir, variant='desktop'):
    ffmpeg = get_ffmpeg()
    ct_path = os.path.join(recording_dir, 'canonical-timeline.json')
    if not os.path.isfile(ct_path):
        ct_path = os.path.join(os.path.dirname(recording_dir), 'canonical-timeline.json')
    if not os.path.isfile(ct_path):
        print('[WARN] canonical-timeline.json not found')
        return False, {}
    with open(ct_path, encoding='utf-8') as f:
        ct = json.load(f)
    events = ct.get('events', [])

    os.makedirs(output_dir, exist_ok=True)
    tmp = os.path.join(output_dir, f'_frames_tmp_{variant}')
    os.makedirs(tmp, exist_ok=True)

    frame_files = []
    frame_labels = []
    evidence = []

    # ── Click events: -150ms, -50ms, exact, +50ms, +150ms ───────────────
    for ev in events:
        if ev.get('type') not in ('click', 'tap'):
            continue
        eid = ev.get('id', 'unknown')
        t_ev = ev.get('time_ms', ev.get('audio_start_ms', 0)) / 1000.0
        offsets = [
            (-0.15, 'before_150ms'),
            (-0.05, 'before_50ms'),
            (0, 'click_exact'),
            (0.05, 'after_50ms'),
            (0.15, 'after_150ms')
        ]
        ev_frames = []
        for off, tag in offsets:
            t = t_ev + off
            fname = os.path.join(tmp, f'click_{eid}_{tag}.png')
            ok = extract_frame(ffmpeg, video_path, t, fname)
            if ok:
                frame_files.append(fname)
                frame_labels.append(f'{eid} ({tag})')
                ev_frames.append({'tag': tag, 'time_sec': round(t, 3), 'frame': fname})
        evidence.append({'event_id': eid, 'type': 'click', 'event_time_ms': int(t_ev * 1000), 'frames': ev_frames})

    # ── Typing events: start, mid, end ──────────────────────────────────
    for ev in events:
        if ev.get('type') != 'typing':
            continue
        eid = ev.get('id', 'unknown')
        t_start = ev.get('start_ms', 0) / 1000.0
        t_end   = ev.get('end_ms', t_start * 1000 + 1000) / 1000.0
        t_mid   = (t_start + t_end) / 2.0
        offsets = [(t_start, 'type_start'), (t_mid, 'type_mid'), (t_end, 'type_end')]
        ev_frames = []
        for t, tag in offsets:
            fname = os.path.join(tmp, f'typing_{eid}_{tag}.png')
            ok = extract_frame(ffmpeg, video_path, t, fname)
            if ok:
                frame_files.append(fname)
                frame_labels.append(f'{eid} ({tag})')
                ev_frames.append({'tag': tag, 'time_sec': round(t, 3), 'frame': fname})
        evidence.append({'event_id': eid, 'type': 'typing', 'start_ms': int(t_start * 1000), 'end_ms': int(t_end * 1000), 'frames': ev_frames})

    # ── Build contact sheet via Pillow ───────────────────────────────────
    sheet_path = os.path.join(output_dir, f'{variant}-av-sync-contact-sheet.jpg')
    sheet_ok = tile_frames_pillow(frame_files, frame_labels, sheet_path, cols=5, is_mobile=(variant == 'mobile'))
    shutil.rmtree(tmp, ignore_errors=True)

    report = {
        'gate': 'A/V SYNC CONTACT SHEET -- V3.5.2.1',
        'variant': variant,
        'total_frames_extracted': len(frame_files),
        'contact_sheet': sheet_path if sheet_ok else None,
        'evidence': evidence,
        'status': 'PASS' if sheet_ok else 'WARN',
    }
    json_path = os.path.join(output_dir, f'{variant}-av-sync-evidence.json')
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(report, f, indent=2)

    print(f'[V3.5.2.1] A/V Contact Sheet ({variant}): {"PASS" if sheet_ok else "FAIL"} -- {len(frame_files)} frames, sheet={sheet_path}')
    return sheet_ok, report


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--video', required=True)
    p.add_argument('--recording-dir', required=True)
    p.add_argument('--output-dir', required=True)
    p.add_argument('--variant', default='desktop', choices=['desktop', 'mobile'])
    args = p.parse_args()
    ok, rep = run_av_contact_sheet(args.video, args.recording_dir, args.output_dir, args.variant)
    print(json.dumps({k: v for k, v in rep.items() if k != 'evidence'}, indent=2))
    sys.exit(0 if ok else 1)


if __name__ == '__main__':
    main()
