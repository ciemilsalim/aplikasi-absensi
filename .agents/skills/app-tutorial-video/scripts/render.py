#!/usr/bin/env python
# render.py — V3.3 (Smart Focus, Zoom, Pan & Tutorial Cursor Render Engine)
# V2.2/V2.3 RETAINED:
#   [BUG#4] Provenance check before render (tutorial_id, target_user, recording hash)
#   Intro/Outro titlecard video generation
#   Audio narration + optional BGM mixing
#   Burn-in subtitles with custom styling
#   render-provenance.json output
# V3.3 NEW:
#   Smart Focus Engine: Dynamic multi-scene zoompan piecewise interpolation
#   Coordinated Zoom-In -> Hold -> Zoom-Out with Smart Center & Subtitle Safety
#   Backward compatible fallback if focus is disabled or unavailable
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

def snap_event_to_video_frame(event_time_ms: float, fps: int = 30) -> tuple[int, float]:
    """
    V3.5.2.3 Frame-Quantization Compensation.
    Snaps an event time in milliseconds to the nearest video frame.
    Returns (frame_index: int, visual_frame_pts_ms: float).
    """
    frame_dur_ms = 1000.0 / float(fps)
    frame_idx = int(round(float(event_time_ms) / frame_dur_ms))
    pts_ms = frame_idx * frame_dur_ms
    return frame_idx, round(pts_ms, 1)

def create_titlecard_image(text_title, text_sub, out_png_path, bg_color=(15, 23, 42), is_mobile=False):
    os.makedirs(os.path.dirname(os.path.abspath(out_png_path)), exist_ok=True)
    w, h = (1080, 1920) if is_mobile else (1920, 1080)
    img = Image.new('RGB', (w, h), color=bg_color)
    draw = ImageDraw.Draw(img)

    # Decorative accent bar
    draw.rectangle([w//2 - 60, h//2 - 120, w//2 + 60, h//2 - 112], fill=(59, 130, 246))

    font_title = None
    font_sub = None
    try:
        font_title = ImageFont.truetype("arial.ttf", 56 if is_mobile else 64)
        font_sub = ImageFont.truetype("arial.ttf", 30 if is_mobile else 34)
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

def create_titlecard_video(ffmpeg, text_title, text_sub, duration, out_path, temp_dir, is_mobile=False):
    png_path = os.path.join(temp_dir, f"titlecard_{hash(text_title)}.png")
    create_titlecard_image(text_title, text_sub, png_path, is_mobile=is_mobile)

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
    """
    errors = []

    plan_tutorial_id = plan.get('tutorial_id') or plan.get('title', 'unknown')
    plan_target_user = plan.get('target_user', 'unknown')
    plan_mode = plan.get('mode', 'unknown')

    video_dir = os.path.dirname(os.path.abspath(video_path))
    manifest_path = os.path.join(video_dir, 'recording-manifest.json')

    if not os.path.isfile(manifest_path):
        errors.append(
            f"[PROVENANCE FAIL] recording-manifest.json not found at: {manifest_path}\n"
            f"  This means the recording has no provenance binding. Cannot verify content consistency.\n"
            f"  Re-run record.js to generate a fresh recording with provenance manifest."
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
    print(f"  Plan tutorial_id      : {plan_tutorial_id}")
    print(f"  Recording tutorial_id : {rec_tutorial_id}")
    print(f"  Plan target_user      : {plan_target_user}")
    print(f"  Recording target_user : {rec_target_user}")
    print(f"  Plan mode             : {plan_mode}")
    print(f"  Recording mode        : {rec_mode}")
    print(f"  Recording run_id      : {rec_run_id}")
    print(f"  Recording file hash   : {rec_file_hash}")

    actual_hash = file_hash(video_path)
    print(f"  Actual file hash      : {actual_hash}")

    if rec_file_hash and actual_hash and rec_file_hash != actual_hash:
        errors.append(
            f"[PROVENANCE FAIL] Recording file hash mismatch!\n"
            f"  Expected: {rec_file_hash}\n"
            f"  Actual  : {actual_hash}\n"
            f"  The recording file may have been replaced or corrupted."
        )

    def normalize_id(s):
        import re
        return re.sub(r'[^a-z0-9]', '', s.lower()) if s else ''

    plan_id_norm = normalize_id(plan_tutorial_id)
    rec_id_norm = normalize_id(rec_tutorial_id)

    id_match = (plan_id_norm == rec_id_norm) or \
               (plan_id_norm in rec_id_norm) or \
               (rec_id_norm in plan_id_norm)

    if not id_match and len(plan_id_norm) > 5 and len(rec_id_norm) > 5:
        errors.append(
            f"[PROVENANCE FAIL] Tutorial ID mismatch between plan and recording!\n"
            f"  Plan tutorial_id: '{plan_tutorial_id}'\n"
            f"  Recording tutorial_id: '{rec_tutorial_id}'"
        )

    if plan_target_user != rec_target_user:
        errors.append(
            f"[PROVENANCE FAIL] Target user mismatch!\n"
            f"  Plan target_user: '{plan_target_user}'\n"
            f"  Recording target_user: '{rec_target_user}'"
        )

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

# ──────────────────────────────────────────────────────────────────────
# V3.5.2 PHASE 5 & 6: HARD VARIANT AUDIO ISOLATION PROVENANCE CHECK
# ──────────────────────────────────────────────────────────────────────
def check_audio_provenance(plan, audio_path, expected_variant, rec_manifest=None):
    """
    V3.5.2 Phase 5 & 6: Hard variant isolation check.
    Enforces that:
    1. audio.variant == video.variant
    2. audio.run_id matches video.run_id (if specified)
    3. audio.tutorial_id matches video.tutorial_id
    4. audio.timeline_hash matches (if specified)
    Terminates with fatal error if mismatch.
    """
    audio_dir = os.path.dirname(os.path.abspath(audio_path))
    prov_path = os.path.join(audio_dir, 'audio-provenance.json')
    if not os.path.isfile(prov_path):
        parent_prov = os.path.join(os.path.dirname(audio_dir), 'audio-provenance.json')
        if os.path.isfile(parent_prov):
            prov_path = parent_prov
        else:
            print(f"[V3.5.2 PROVENANCE WARNING] audio-provenance.json not found in {audio_dir}", file=sys.stderr)
            return True, {"variant": expected_variant, "status": "UNBOUND"}

    try:
        audio_prov = json.load(open(prov_path, 'r', encoding='utf-8'))
    except Exception as e:
        return False, f"Could not read audio-provenance.json: {e}"

    audio_variant = audio_prov.get('variant')
    if audio_variant and audio_variant != expected_variant:
        err = (
            f"[FATAL PROVENANCE MISMATCH] Video variant '{expected_variant}' cannot use Audio variant '{audio_variant}'!\n"
            f"  CROSS-VARIANT AUDIO CONTAMINATION IS FORBIDDEN BY V3.5.2 SPEC (PHASE 5 & 6).\n"
            f"  Audio Path: {audio_path}\n"
            f"  Audio Provenance: {prov_path}"
        )
        return False, err

    audio_tut_id = audio_prov.get('tutorial_id')
    plan_tut_id = plan.get('tutorial_id')
    if audio_tut_id and plan_tut_id and audio_tut_id != plan_tut_id:
        err = f"[FATAL PROVENANCE MISMATCH] Audio tutorial_id '{audio_tut_id}' != Plan tutorial_id '{plan_tut_id}'"
        return False, err

    return True, audio_prov

# ──────────────────────────────────────────────────────────────────────
# V3.3: BUILD PIECEWISE ZOOMPAN FILTER EXPRESSION
# ──────────────────────────────────────────────────────────────────────
def build_zoompan_filter(focus_timeline, pts_factor, fps=30, out_res='1920x1080'):
    """
    Constructs a smooth, piecewise FFmpeg zoompan filter for scenes with focus.
    Returns filter string: zoompan=z='...':x='...':y='...':d=1:s={out_res}:fps=30
    or None if no focus events exist.
    """
    if not focus_timeline:
        return None, []

    events = []
    for scene_id, data in focus_timeline.items():
        if not data.get('focus_enabled'):
            continue
        timing = data.get('timing', {})
        t_in = timing.get('zoom_in_start', 0) * pts_factor
        t_h1 = timing.get('hold_start', t_in + 0.7) * pts_factor
        t_h2 = timing.get('hold_end', t_h1 + 1.0) * pts_factor
        t_out = timing.get('zoom_out_end', t_h2 + 0.7) * pts_factor
        
        Z = float(data.get('zoom_level', 1.0))
        if Z <= 1.01:
            continue

        center = data.get('center', {})
        cx = int(center.get('cx', 960))
        cy = int(center.get('cy', 540))

        f_in = int(round(t_in * fps))
        f_h1 = int(round(t_h1 * fps))
        f_h2 = int(round(t_h2 * fps))
        f_out = int(round(t_out * fps))

        if f_out > f_in:
            events.append({
                'scene_id': scene_id,
                'f_in': f_in,
                'f_h1': max(f_in + 1, f_h1),
                'f_h2': max(f_h1, f_h2),
                'f_out': max(f_h2 + 1, f_out),
                'Z': round(Z, 2),
                'cx': cx,
                'cy': cy
            })

    if not events:
        return None, []

    # Sort events by frame number
    events.sort(key=lambda x: x['f_in'])

    # Build nested if-expressions
    z_expr = '1.0'
    x_expr = '(iw-iw/zoom)/2'
    y_expr = '(ih-ih/zoom)/2'

    for ev in reversed(events):
        fi = ev['f_in']
        fh1 = ev['f_h1']
        fh2 = ev['f_h2']
        fo = ev['f_out']
        Z = ev['Z']
        cx = ev['cx']
        cy = ev['cy']

        d_in = max(1, fh1 - fi)
        d_out = max(1, fo - fh2)

        # V3.4 Phase 5: Smoothstep polynomial easing S(u) = 3*u^2 - 2*u^3
        # Guarantees zero jerk / camera snap: slow acceleration -> smooth zoom -> soft deceleration -> hold -> soft zoom-out
        zk_in = f"1.0+({Z}-1.0)*(3*pow((on-{fi})/{d_in},2)-2*pow((on-{fi})/{d_in},3))"
        zk_out = f"{Z}-({Z}-1.0)*(3*pow((on-{fh2})/{d_out},2)-2*pow((on-{fh2})/{d_out},3))"
        zk = (f"if(between(on,{fi},{fh1}), {zk_in},"
              f"if(between(on,{fh1},{fh2}), {Z},"
              f"if(between(on,{fh2},{fo}), {zk_out}, 1.0)))")
        
        xk = f"min(max(0, {cx} - iw/zoom/2), iw - iw/zoom)"
        yk = f"min(max(0, {cy} - ih/zoom/2), ih - ih/zoom)"

        z_expr = f"if(between(on,{fi},{fo}), {zk}, {z_expr})"
        x_expr = f"if(between(on,{fi},{fo}), {xk}, {x_expr})"
        y_expr = f"if(between(on,{fi},{fo}), {yk}, {y_expr})"

    filter_str = f"zoompan=z='{z_expr}':x='{x_expr}':y='{y_expr}':d=1:s={out_res}:fps={fps}"
    return filter_str, events


# ──────────────────────────────────────────────────────────────────────
# V3.4/V3.5: INTERACTIVE AUDIO SFX MIXER & VERIFICATION ENGINE
# ──────────────────────────────────────────────────────────────────────
import wave, struct, math

def load_wav_samples(path):
    if not os.path.isfile(path):
        return []
    try:
        with wave.open(path, 'r') as wf:
            cf = wf.readframes(wf.getnframes())
            return [struct.unpack('<h', cf[i:i+2])[0] for i in range(0, len(cf), 2)]
    except Exception as e:
        print(f"[V3.5 SFX Warning] Could not load {path}: {e}")
        return []

def build_sfx_track(temp_dir, target_total, intro_dur, pts_factor, video_dir, timeline_path=None):
    """
    Synthesizes a sample-accurate 44.1kHz WAV SFX track from canonical-timeline.json (V3.5.2)
    or audio-effects-timeline.json and local royalty-safe audio assets.
    Returns (sfx_wav_path, event_count, is_canonical) or (None, 0, False).
    """
    events = []
    is_canonical = False

    # Candidate paths for canonical-timeline.json
    candidate_timelines = []
    if timeline_path and os.path.isfile(timeline_path):
        candidate_timelines.append(timeline_path)
    candidate_timelines.extend([
        os.path.join(video_dir, 'canonical-timeline.json'),
        os.path.join(os.path.dirname(video_dir), 'canonical-timeline.json'),
        os.path.join(os.path.dirname(os.path.dirname(video_dir)), 'canonical-timeline.json')
    ])

    for c_path in candidate_timelines:
        if os.path.isfile(c_path):
            try:
                c_data = json.load(open(c_path, 'r', encoding='utf-8'))
                if c_data.get('events'):
                    events = c_data['events']
                    is_canonical = True
                    print(f"[V3.5.2] Loaded Canonical Event Timeline for SFX ({len(events)} events): {c_path}")
                    break
            except Exception:
                pass

    if not events:
        eff_path = os.path.join(video_dir, 'audio-effects-timeline.json')
        if os.path.isfile(eff_path):
            try:
                tdata = json.load(open(eff_path, 'r', encoding='utf-8'))
                events = tdata.get('events', [])
            except Exception as e:
                print(f"[V3.5 SFX Warning] Could not read audio-effects-timeline: {e}")
                return None, 0, False

    if not events:
        return None, 0, False

    skill_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    meta_path = os.path.join(skill_root, 'resources', 'audio', 'metadata.json')
    audio_meta = {}
    if os.path.isfile(meta_path):
        try:
            audio_meta = json.load(open(meta_path, 'r', encoding='utf-8'))
        except Exception:
            pass
    mouse_meta = audio_meta.get('assets', {}).get('mouse-click.wav', {})
    is_mouse_recorded = mouse_meta.get('source_type') == 'recorded'

    mouse_click_path = os.path.join(skill_root, 'resources', 'audio', 'mouse-click.wav')
    click_path = os.path.join(skill_root, 'resources', 'audio', 'click.wav')
    tap_path = os.path.join(skill_root, 'resources', 'audio', 'tap.wav')
    typing_path = os.path.join(skill_root, 'resources', 'audio', 'typing.wav')

    mouse_click_samples = load_wav_samples(mouse_click_path)
    click_samples = load_wav_samples(click_path)
    tap_samples = load_wav_samples(tap_path)
    typing_samples = load_wav_samples(typing_path)

    framerate = 44100
    total_samples = int(framerate * target_total)
    buffer = [0] * total_samples

    valid_events = 0
    fallback_warned = False
    for ev in events:
        ev_type = ev.get('type')
        ev_mode = ev.get('mode')
        vol = float(ev.get('volume', 0.18))
        if ev_type in ('click', 'tap'):
            samples = None
            if ev_type == 'tap' or ev_mode == 'mobile_tap':
                samples = tap_samples or click_samples
            elif ev_mode in ('synthetic_ui', 'ui_click'):
                samples = click_samples
            elif ev_mode in ('recorded_mouse', 'physical_mouse') or not ev_mode:
                if not is_mouse_recorded:
                    if not fallback_warned:
                        print("\n[WARN] ==================================================")
                        print("[WARN] REAL MOUSE AUDIO NOT AVAILABLE")
                        print("[WARN] USING SYNTHETIC FALLBACK")
                        print("[WARN] (mouse-click.wav source_type is 'synthetic')")
                        print("[WARN] ==================================================\n")
                        fallback_warned = True
                    samples = click_samples or mouse_click_samples
                else:
                    samples = mouse_click_samples or click_samples
            else:
                samples = mouse_click_samples or click_samples

            if samples:
                if is_canonical:
                    # V3.5.2.3: Audio mixing MUST follow rendered visual frame PTS
                    pts_ms = ev.get('visual_frame_pts_ms')
                    if pts_ms is None:
                        pts_ms = ev.get('effective_audio_ms')
                    if pts_ms is None:
                        canonical_ms = ev.get('time_ms', 0)
                        _, pts_ms = snap_event_to_video_frame(canonical_ms, fps=30)
                    codec_comp_ms = ev.get('codec_compensation_ms', 0)
                    eff_audio_ms = pts_ms + codec_comp_ms
                    t_final = eff_audio_ms / 1000.0
                else:
                    t_event = float(ev.get('time', 0.0))
                    t_final = intro_dur + t_event * pts_factor
                idx = int(round(t_final * framerate))
                for i, s in enumerate(samples):
                    if 0 <= idx + i < total_samples:
                        buffer[idx + i] += int(s * vol)
                valid_events += 1
        elif ev_type == 'typing' and typing_samples:
            if is_canonical:
                # V3.5.2.3: Audio mixing follows visual frame PTS bounds
                start_pts_ms = ev.get('visual_start_frame_pts_ms')
                if start_pts_ms is None:
                    start_pts_ms = ev.get('effective_audio_start_ms')
                if start_pts_ms is None:
                    start_pts_ms = ev.get('start_ms', 0)
                end_pts_ms = ev.get('visual_end_frame_pts_ms')
                if end_pts_ms is None:
                    end_pts_ms = ev.get('effective_audio_end_ms')
                if end_pts_ms is None:
                    end_pts_ms = ev.get('end_ms', start_pts_ms + 1000)
                t_start = start_pts_ms / 1000.0
                t_end = end_pts_ms / 1000.0
            else:
                t_start = intro_dur + float(ev.get('start', 0.0)) * pts_factor
                t_end = intro_dur + float(ev.get('end', t_start + 1.0)) * pts_factor
            idx_start = max(0, int(round(t_start * framerate)))
            idx_end = min(total_samples, int(round(t_end * framerate)))
            curr = idx_start
            step = len(typing_samples)
            while curr < idx_end:
                keystroke_vol = vol * (0.85 + (curr % 7) * 0.04)
                for i, s in enumerate(typing_samples):
                    if curr + i < idx_end:
                        buffer[curr + i] += int(s * keystroke_vol)
                next_step = step + int(framerate * 0.060) + ((curr * 11) % int(framerate * 0.025))
                if curr + next_step >= idx_end and (idx_end - curr) > int(framerate * 0.035):
                    final_pos = max(curr + step, idx_end - len(typing_samples))
                    for i, s in enumerate(typing_samples):
                        if 0 <= final_pos + i < total_samples:
                            buffer[final_pos + i] += int(s * keystroke_vol)
                    break
                curr += next_step
            valid_events += 1

    if valid_events == 0:
        return None, 0, is_canonical

    sfx_wav_path = os.path.join(temp_dir, 'sfx_track.wav')
    with wave.open(sfx_wav_path, 'w') as wf:
        wf.setnchannels(1)
        wf.setsampwidth(2)
        wf.setframerate(framerate)
        packed = bytearray()
        for s in buffer:
            clamped = max(-32767, min(32767, s))
            packed.extend(struct.pack('<h', clamped))
        wf.writeframes(packed)

    print(f"[V3.5.2] Interactive SFX track generated: {sfx_wav_path} ({valid_events} events mixed, canonical={is_canonical})")
    return sfx_wav_path, valid_events, is_canonical

def verify_typing_audio_energy(final_video_path, video_dir, intro_dur=0.0, pts_factor=1.0):
    """
    V3.5 Phase 7: Extracts audio segments during actual typing intervals from final rendered video
    and computes RMS energy to empirically prove that typing audio exists on the final audio track.
    Outputs typing-audio-verification.json.
    """
    events = []
    canonical_candidates = [
        os.path.join(video_dir, 'canonical-timeline.json'),
        os.path.join(os.path.dirname(video_dir), 'recording', 'canonical-timeline.json'),
    ]
    for cpath in canonical_candidates:
        if os.path.isfile(cpath):
            try:
                ct = json.load(open(cpath, 'r', encoding='utf-8'))
                for ev in ct.get('events', []):
                    if ev.get('type') == 'typing':
                        s_sec = float(ev.get('start_sec', (ev.get('start_ms', ev.get('canonical_start_ms', 0)) / 1000.0)))
                        e_sec = float(ev.get('end_sec', (ev.get('end_ms', ev.get('canonical_end_ms', 0)) / 1000.0)))
                        events.append({
                            'scene_id': ev.get('scene_id', 'unknown'),
                            'start_sec': s_sec,
                            'end_sec': e_sec,
                            'is_canonical': True
                        })
                if events:
                    break
            except Exception:
                pass

    if not events:
        timeline_path = os.path.join(video_dir, 'audio-effects-timeline.json')
        typing_events_path = os.path.join(video_dir, 'typing-events.json')
        if os.path.isfile(typing_events_path):
            try:
                events = json.load(open(typing_events_path, 'r', encoding='utf-8'))
            except Exception:
                pass
        if not events and os.path.isfile(timeline_path):
            try:
                tdata = json.load(open(timeline_path, 'r', encoding='utf-8'))
                events = [e for e in tdata.get('events', []) if e.get('type') == 'typing']
            except Exception:
                pass

    results = []
    ffmpeg = get_ffmpeg()
    for ev in events:
        scene_id = ev.get('scene_id', 'unknown')
        if ev.get('is_canonical'):
            t_start = ev['start_sec']
            t_end = ev['end_sec']
        else:
            t_start = intro_dur + float(ev.get('start', 0.0)) * pts_factor
            t_end = intro_dur + float(ev.get('end', t_start + 1.0)) * pts_factor
        dur = max(0.2, t_end - t_start)
        
        cmd = [
            ffmpeg, '-y', '-ss', f"{t_start:.3f}", '-t', f"{dur:.3f}",
            '-i', final_video_path,
            '-vn', '-ac', '1', '-ar', '44100', '-f', 's16le', '-'
        ]
        proc = subprocess.run(cmd, capture_output=True)
        raw_pcm = proc.stdout
        if len(raw_pcm) >= 4:
            count = len(raw_pcm) // 2
            samples = struct.unpack(f'<{count}h', raw_pcm[:count*2])
            rms = math.sqrt(sum(s*s for s in samples) / float(count))
        else:
            rms = 0.0

        detected = rms > 8.0  # Clear energy above silence baseline (silence RMS = 0.0)
        status = "PASS" if detected else "WARN"
        results.append({
            "scene_id": scene_id,
            "expected": True,
            "start": round(t_start, 2),
            "end": round(t_end, 2),
            "rms_energy": round(rms, 2),
            "detected_audio_energy": detected,
            "status": status
        })

    out_json = os.path.join(video_dir, 'typing-audio-verification.json')
    with open(out_json, 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=2)

    render_parent = os.path.dirname(os.path.abspath(final_video_path))
    if render_parent != video_dir:
        out_render = os.path.join(render_parent, 'typing-audio-verification.json')
        with open(out_render, 'w', encoding='utf-8') as f:
            json.dump(results, f, indent=2)

    print(f"[V3.5] Typing audio energy verification written: {out_json} ({len(results)} interval(s) verified)")
    return results


def main():
    p = argparse.ArgumentParser(description='V3.5.3 Video-Master / Picture-Lock Tutorial Video Render Engine.')
    p.add_argument('--plan', required=True, help='Path to tutorial-plan.json')
    p.add_argument('--video', required=True, help='Path to raw webm/mp4 recording')
    p.add_argument('--audio', default=None, help='Path to narration mp3 audio')
    p.add_argument('--output', required=True, help='Path to final MP4 file')
    p.add_argument('--visual-master-only', action='store_true', help='Render ONLY visual-master.mp4 (Picture Lock Phase A)')
    p.add_argument('--visual-master-out', default=None, help='Explicit path for visual-master.mp4')
    p.add_argument('--audio-master-out', default=None, help='Explicit path for audio-master-for-final-video.wav')
    p.add_argument('--subtitles', help='Path to SRT subtitle file')
    p.add_argument('--bgm', help='Path to optional background music file')
    p.add_argument('--target-duration', type=float, default=75.0, help='Target total video duration')
    p.add_argument('--run-id', help='Run ID for provenance tracking')
    p.add_argument('--skip-provenance-check', action='store_true', help='[DANGEROUS] Skip provenance check')
    p.add_argument('--focus-mode', choices=['auto', 'manual', 'off'], help='Override plan focus_mode')
    p.add_argument('--timeline', help='Path to canonical-timeline.json or final-video-timeline.json')
    p.add_argument('--variant', choices=['desktop', 'mobile'], help='Explicit variant override')
    args = p.parse_args()

    ffmpeg = get_ffmpeg()
    plan = json.load(open(args.plan, 'r', encoding='utf-8'))
    
    video_dir = os.path.dirname(os.path.abspath(args.video))
    rec_manifest_path = os.path.join(video_dir, 'recording-manifest.json')
    rec_manifest = {}
    if os.path.isfile(rec_manifest_path):
        try:
            rec_manifest = json.load(open(rec_manifest_path, 'r', encoding='utf-8'))
        except Exception:
            pass

    is_mobile = (args.variant == 'mobile') or (rec_manifest.get('variant') == 'mobile') or (plan.get('tutorial_mode') == 'mobile') or (plan.get('mode') == 'mobile')
    video_variant = 'mobile' if is_mobile else 'desktop'

    # ── V2.2 PROVENANCE CHECK ──
    if not args.skip_provenance_check:
        prov_ok, prov_errors = check_provenance(plan, args.video)
        if not prov_ok:
            print("\n" + "="*70, file=sys.stderr)
            print("RENDER ABORTED — PROVENANCE CHECK FAILED", file=sys.stderr)
            print("="*70, file=sys.stderr)
            for e in prov_errors:
                print(f"\n{e}", file=sys.stderr)
            sys.exit(10)

        # ── V3.5.2 PHASE 5 & 6: HARD VARIANT AUDIO ISOLATION PROVENANCE CHECK ──
        if not args.visual_master_only and args.audio:
            audio_ok, audio_prov = check_audio_provenance(plan, args.audio, video_variant, rec_manifest)
            if not audio_ok:
                print("\n" + "="*70, file=sys.stderr)
                print("RENDER ABORTED — HARD VARIANT AUDIO ISOLATION FAILED", file=sys.stderr)
                print("="*70, file=sys.stderr)
                print(f"\n{audio_prov}", file=sys.stderr)
                sys.exit(11)
            print(f"[V3.5.2] Audio Provenance OK: variant='{audio_prov.get('variant')}', tutorial_id='{audio_prov.get('tutorial_id')}'")
        else:
            audio_prov = {"phase": "visual_master_picture_lock"}
    else:
        audio_prov = {}
        print("[V2.2] WARNING: Provenance check SKIPPED")
    
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

    is_mobile = (plan.get('tutorial_mode') == 'mobile') or (plan.get('mode') == 'mobile')

    # 1. Create Intro & Outro titlecard videos
    if use_intro:
        create_titlecard_video(
            ffmpeg,
            text_title=intro_cfg.get('title', plan.get('title', 'TUTORIAL APLIKASI')),
            text_sub=intro_cfg.get('subtitle', f"Panduan {plan.get('target_user', 'Pengguna')}"),
            duration=intro_dur,
            out_path=intro_file,
            temp_dir=temp_dir,
            is_mobile=is_mobile
        )
        
    if use_outro:
        create_titlecard_video(
            ffmpeg,
            text_title=outro_cfg.get('title', 'Tutorial Selesai'),
            text_sub=outro_cfg.get('subtitle', 'Terima kasih dan selamat bertugas.'),
            duration=outro_dur,
            out_path=outro_file,
            temp_dir=temp_dir,
            is_mobile=is_mobile
        )

    # 2. Main video processing & duration fitting
    target_total = args.target_duration
    target_main_video_dur = target_total - intro_dur - outro_dur

    print(f"Target Total: {target_total:.2f}s | Intro: {intro_dur}s | Outro: {outro_dur}s | Main Video Target: {target_main_video_dur:.2f}s | Mode: {'MOBILE' if is_mobile else 'DESKTOP'}")

    main_processed = os.path.join(temp_dir, 'main_processed.mp4')
    
    ffprobe = shutil.which('ffprobe')
    raw_video_dur = target_main_video_dur
    if ffprobe:
        r = subprocess.run([ffprobe, '-v', 'error', '-show_entries', 'format=duration', '-of', 'json', args.video], capture_output=True, text=True)
        if r.returncode == 0:
            raw_video_dur = float(json.loads(r.stdout).get('format', {}).get('duration', target_main_video_dur))
    else:
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

    # ── V3.3/V3.5: Load scene_focus_timeline.json ──
    video_dir = os.path.dirname(os.path.abspath(args.video))
    focus_timeline_path = os.path.join(video_dir, 'scene_focus_timeline.json')
    focus_timeline = {}
    if os.path.isfile(focus_timeline_path):
        try:
            focus_timeline = json.load(open(focus_timeline_path, 'r', encoding='utf-8'))
        except Exception as e:
            print(f"[V3.3] Warning: could not load scene_focus_timeline.json: {e}")

    effective_focus_mode = args.focus_mode or plan.get('focus_mode', 'auto')
    zoompan_filter = None
    focused_events = []

    out_res = '1080x1920' if is_mobile else '1920x1080'
    if effective_focus_mode != 'off' and focus_timeline:
        zoompan_filter, focused_events = build_zoompan_filter(focus_timeline, pts_factor, fps=30, out_res=out_res)
        if zoompan_filter:
            print(f"[V3.5] Smart Focus Engine ({out_res}): {len(focused_events)} dynamic focus event(s) enabled.")
            for fe in focused_events:
                print(f"  - Scene {fe['scene_id']}: Zoom={fe['Z']}x, Center=({fe['cx']},{fe['cy']}), Frames=[{fe['f_in']}..{fe['f_out']}]")
        else:
            print("[V3.5] Focus Engine: No focus events required zoom. Using standard framing.")
    else:
        print(f"[V3.5] Focus Engine: disabled (mode={effective_focus_mode}). Using standard framing.")

    if is_mobile:
        vf_filters = ["scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(ow-iw)/2:(oh-ih)/2:color=black"]
    else:
        vf_filters = ["scale=1920:1080"]

    if zoompan_filter:
        vf_filters.append(zoompan_filter)
    vf_filters.append("fps=30")

    if pts_factor != 1.0:
        vf_filters.append(f"setpts={pts_factor:.4f}*PTS")
    if pad_sec > 0:
        vf_filters.append(f"tpad=stop_mode=clone:stop_duration={pad_sec:.2f}")

    vf_main = ','.join(vf_filters)

    cmd_main = [
        ffmpeg, '-y', '-i', args.video,
        '-t', str(target_main_video_dur),
        '-vf', vf_main, '-an',
        '-c:v', 'libx264', '-preset', 'medium', '-crf', '20', '-pix_fmt', 'yuv420p',
        main_processed
    ]
    print(f"[V3.5] Executing main video transformation ({out_res})...")
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

    # ── V3.5.3 Phase A: Save Visual Master (Picture Lock) ──
    visual_master_path = args.visual_master_out or os.path.join(render_dir, 'visual-master.mp4')
    shutil.copyfile(concatenated_video, visual_master_path)
    print(f"[V3.5.3] Visual Master (Picture Lock) created: {visual_master_path}")

    if args.visual_master_only:
        print("[V3.5.3] --visual-master-only requested. Picture Lock established. Exiting cleanly.")
        try:
            shutil.rmtree(temp_dir)
        except Exception:
            pass
        return

    # 4. Final Render: Concatenated Video + Audio Narration + Subtitles
    sfx_wav_path, sfx_events_count, used_canonical = build_sfx_track(temp_dir, target_total, intro_dur, pts_factor, video_dir, timeline_path=args.timeline)
    audio_effects_hash = file_hash(os.path.join(video_dir, 'audio-effects-timeline.json'))

    # ── V3.4/V3.5: Subtitle Collision & Dynamic Placement ──
    adapted_sub_path = None
    sub_placement_hash = None
    if args.subtitles and os.path.isfile(args.subtitles):
        try:
            import importlib.util
            sub_col_script = os.path.join(os.path.dirname(__file__), 'subtitle_collision.py')
            if os.path.isfile(sub_col_script):
                spec = importlib.util.spec_from_file_location("subtitle_collision", sub_col_script)
                sub_mod = importlib.util.module_from_spec(spec)
                spec.loader.exec_module(sub_mod)
                
                srt_entries = sub_mod.parse_srt(args.subtitles)
                viewport = (1080, 1920) if is_mobile else (1920, 1080)
                placements = sub_mod.calculate_subtitle_placements(focus_timeline, srt_entries, viewport=viewport, is_mobile=is_mobile)
                
                placement_path = os.path.join(video_dir, 'subtitle-placement.json')
                with open(placement_path, 'w', encoding='utf-8') as f:
                    json.dump(placements, f, indent=2)
                sub_placement_hash = file_hash(placement_path)
                
                ass_out_path = os.path.join(temp_dir, 'adapted_subtitles.ass')
                sub_mod.generate_adaptive_ass(srt_entries, placements, ass_out_path, is_mobile=is_mobile)
                adapted_sub_path = ass_out_path
                print(f"[V3.5] Subtitle Collision Engine: adapted .ass generated with safe placements ({'mobile' if is_mobile else 'desktop'}).")
        except Exception as e:
            print(f"[V3.5 Warning] Subtitle collision processing error: {e}")

    audio_inputs = ['-i', args.audio]
    filter_complex = []
    
    use_bgm = plan.get('music', False) and args.bgm and os.path.isfile(args.bgm)
    
    curr_audio_idx = 0
    mix_inputs = ["[narration]"]
    filter_complex.append(f"[0:a]apad=whole_dur={target_total},volume=1.0[narration]")

    if sfx_wav_path and os.path.isfile(sfx_wav_path):
        curr_audio_idx += 1
        audio_inputs.extend(['-i', sfx_wav_path])
        filter_complex.append(f"[{curr_audio_idx}:a]apad=whole_dur={target_total},volume=1.0[sfx]")
        mix_inputs.append("[sfx]")

    if use_bgm:
        curr_audio_idx += 1
        audio_inputs.extend(['-i', args.bgm])
        filter_complex.append(f"[{curr_audio_idx}:a]volume=0.06[bgm]")
        mix_inputs.append("[bgm]")

    if len(mix_inputs) > 1:
        inputs_str = "".join(mix_inputs)
        filter_complex.append(f"{inputs_str}amix=inputs={len(mix_inputs)}:duration=first:dropout_transition=2[aout]")
        map_audio = '[aout]'
    else:
        map_audio = '[narration]'

    # ── V3.5.3 Phase D: Generate Audio Master (WAV) ──
    audio_master_wav_path = args.audio_master_out or os.path.join(render_dir, 'audio-master-for-final-video.wav')
    cmd_audio_master = [ffmpeg, '-y'] + audio_inputs
    if filter_complex:
        cmd_audio_master.extend(['-filter_complex', ';'.join(filter_complex)])
        cmd_audio_master.extend(['-map', map_audio])
    else:
        cmd_audio_master.extend(['-map', '0:a:0'])
    cmd_audio_master.extend([
        '-t', str(target_total),
        '-c:a', 'pcm_s16le', '-ar', '44100',
        audio_master_wav_path
    ])
    print(f"[V3.5.3] Generating Audio Master Track -> {audio_master_wav_path}...")
    subprocess.run(cmd_audio_master, check=True)
    print(f"[V3.5.3] Audio Master created: {audio_master_wav_path}")

    # ── V3.5.3 Phase D: MUX Visual Master + Audio Master into Final MP4 ──
    cmd_final = [
        ffmpeg, '-y',
        '-i', visual_master_path,
        '-i', audio_master_wav_path
    ]
    
    vf_final = []
    effective_sub = adapted_sub_path if adapted_sub_path else args.subtitles
    if effective_sub and os.path.isfile(effective_sub):
        sub_path = os.path.abspath(effective_sub).replace('\\', '/').replace(':', '\\:')
        if effective_sub.endswith('.ass'):
            vf_final.append(f"subtitles='{sub_path}'")
        else:
            font_size = 36 if is_mobile else 22
            margin_v = 45 if is_mobile else 35
            vf_final.append(
                f"subtitles='{sub_path}':force_style='FontSize={font_size},PrimaryColour=&H00FFFFFF,OutlineColour=&H00000000,BorderStyle=1,Outline=2,Shadow=1,MarginV={margin_v}'"
            )

    cmd_final.extend(['-map', '0:v:0', '-map', '1:a:0'])

    if vf_final:
        cmd_final.extend(['-vf', ','.join(vf_final)])

    cmd_final.extend([
        '-t', str(target_total),
        '-c:v', 'libx264', '-preset', 'medium', '-crf', '20', '-pix_fmt', 'yuv420p',
        '-c:a', 'aac', '-b:a', '192k', '-movflags', '+faststart',
        args.output
    ])

    print("Executing Final MUX Pipeline (Visual Master + Audio Master):")
    print(" ".join(cmd_final))
    subprocess.run(cmd_final, check=True)

    # Also save final-tutorial.mp4 if name differs
    final_tut_path = os.path.join(render_dir, 'final-tutorial.mp4')
    if os.path.abspath(args.output) != os.path.abspath(final_tut_path):
        try:
            shutil.copyfile(args.output, final_tut_path)
            print(f"[V3.5.3] Synchronized final tutorial video to: {final_tut_path}")
        except Exception:
            pass

    # ── V3.5 Phase 7: Actual Typing Audio Energy Verification ──
    typing_verification = verify_typing_audio_energy(args.output, video_dir, intro_dur, pts_factor)
    typing_verif_hash = file_hash(os.path.join(video_dir, 'typing-audio-verification.json'))

    # Clean up temp
    try:
        shutil.rmtree(temp_dir)
    except Exception:
        pass

    # ── V3.5.2.4: Copy narration-visual-map.json into final render dir ──
    map_candidates = [
        os.path.join(video_dir, 'narration-visual-map.json'),
        os.path.join(os.path.dirname(os.path.abspath(args.audio)), 'narration-visual-map.json'),
        os.path.join(os.path.dirname(video_dir), 'audio', 'narration-visual-map.json'),
        os.path.join(os.path.dirname(video_dir), 'recording', 'narration-visual-map.json')
    ]
    render_out_dir = os.path.dirname(os.path.abspath(args.output))
    target_map_path = os.path.join(render_out_dir, 'narration-visual-map.json')
    for mc in map_candidates:
        if os.path.isfile(mc):
            try:
                shutil.copy2(mc, target_map_path)
                print(f"[V3.5.2.4] Copied narration-visual-map.json to {target_map_path}")
                break
            except Exception:
                pass

    map_hash = file_hash(target_map_path) if os.path.isfile(target_map_path) else None

    # ── V3.5: Write provenance into final manifest ──
    final_hash = file_hash(args.output)
    audio_hash = file_hash(args.audio)
    subtitle_hash = file_hash(effective_sub) if effective_sub else None
    plan_hash = file_hash(args.plan)

    rec_manifest_path = os.path.join(video_dir, 'recording-manifest.json')
    rec_manifest = {}
    if os.path.isfile(rec_manifest_path):
        try:
            rec_manifest = json.load(open(rec_manifest_path, 'r', encoding='utf-8'))
        except Exception:
            pass

    effects_path = os.path.join(video_dir, 'audio-effects.json')
    eff_cfg = {}
    if os.path.isfile(effects_path):
        try:
            eff_cfg = json.load(open(effects_path, 'r', encoding='utf-8'))
        except Exception:
            pass

    skill_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    meta_path = os.path.join(skill_root, 'resources', 'audio', 'metadata.json')
    audio_meta_cfg = {}
    if os.path.isfile(meta_path):
        try:
            audio_meta_cfg = json.load(open(meta_path, 'r', encoding='utf-8'))
        except Exception:
            pass
    mouse_meta = audio_meta_cfg.get('assets', {}).get('mouse-click.wav', {})
    is_mouse_recorded = mouse_meta.get('source_type') == 'recorded'

    provenance_manifest = {
        "render_version": "3.5.3",
        "engine": "Video-Master / Picture-Lock Audio Engine",
        "tutorial_id": plan.get('tutorial_id') or plan.get('title', 'unknown'),
        "feature": plan.get('feature') or plan.get('mode', 'unknown'),
        "target_user": plan.get('target_user', 'unknown'),
        "mode": plan.get('mode', 'unknown'),
        "tutorial_mode": "mobile" if is_mobile else "desktop",
        "variant": video_variant,
        "resolution": "1080x1920" if is_mobile else "1920x1080",
        "run_id": args.run_id or rec_manifest.get('run_id'),
        "plan_hash": plan_hash,
        "recording_hash": rec_manifest.get('recording_file_hash'),
        "timeline_hash": rec_manifest.get('timeline_hash') or audio_prov.get('timeline_hash'),
        "visual_master_hash": file_hash(visual_master_path),
        "audio_master_hash": file_hash(audio_master_wav_path),
        "video_timeline_hash": file_hash(os.path.join(video_dir, 'video-timeline.json')) or file_hash(os.path.join(render_dir, 'final-video-timeline.json')),
        "video_master_timeline_hash": file_hash(os.path.join(video_dir, 'video-master-timeline.json')) or file_hash(os.path.join(render_dir, 'video-master-timeline.json')),
        "audio_provenance": audio_prov,
        "audio_hash": audio_hash,
        "subtitle_hash": subtitle_hash,
        "subtitle_placement_hash": sub_placement_hash,
        "narration_visual_map_hash": map_hash,
        "focus_timeline_hash": file_hash(os.path.join(video_dir, 'scene_focus_timeline.json')),
        "audio_effects_hash": audio_effects_hash,
        "typing_audio_verification_hash": typing_verif_hash,
        "final_video_hash": final_hash,
        "output_path": args.output,
        "audio_authenticity": {
            "click_source_configured": eff_cfg.get('click_source', 'recorded_mouse'),
            "effective_click_source": eff_cfg.get('effective_click_source', 'synthetic_ui'),
            "click_source_type": eff_cfg.get('click_source_type', 'synthetic_fallback'),
            "mouse_click_asset_source": mouse_meta.get('source_type', 'synthetic'),
            "is_physical_recording": bool(mouse_meta.get('is_physical_recording', False)),
            "fallback_used": not bool(mouse_meta.get('is_physical_recording', False)),
            "warning": eff_cfg.get('warning') or ("REAL MOUSE AUDIO NOT AVAILABLE - USING SYNTHETIC FALLBACK" if not is_mouse_recorded else None)
        },
        "focus_engine": {
            "mode": effective_focus_mode,
            "focused_scenes": len(focused_events),
            "events": focused_events
        },
        "interactive_audio": {
            "enabled": bool(sfx_wav_path),
            "events_count": sfx_events_count,
            "typing_intervals_verified": len(typing_verification)
        },
        "rendered_at": __import__('datetime').datetime.now().isoformat()
    }

    provenance_path = os.path.join(render_out_dir, 'render-provenance.json')
    with open(provenance_path, 'w', encoding='utf-8') as f:
        json.dump(provenance_manifest, f, indent=2)

    print(f"\n[V3.5.2.5] Render provenance written: {provenance_path}")
    print(f"Final MP4 Rendered Successfully: {args.output}")

if __name__ == '__main__':
    main()
