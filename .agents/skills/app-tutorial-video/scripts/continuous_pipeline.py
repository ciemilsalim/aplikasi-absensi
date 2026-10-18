#!/usr/bin/env python
# continuous_pipeline.py — TRUE CONTINUOUS BROWSER VIDEO ENGINE (V4) orchestrator
#
#   PASS A  narration : edge-tts per scene, REAL word boundaries, measured duration
#   PASS B  video     : continuous/engine.js (live browser capture @ 60 fps, virtual time)
#   PASS C  audio     : narration + interaction SFX placed from the RECORDED event log
#   PASS D  mux       : video stream copied bit-exact (-c:v copy), audio AAC
#
# There is no visual post-processing of frames anywhere in this pipeline.
# Subtitles and the pointer are rendered inside the live page by overlay.js.
import argparse, asyncio, hashlib, json, os, subprocess, sys, wave
import numpy as np

HERE = os.path.dirname(os.path.abspath(__file__))
SR = 48000

if sys.stdout and hasattr(sys.stdout, 'reconfigure'):
    try: sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    except Exception: pass


def ffmpeg_exe():
    try:
        import imageio_ffmpeg
        return imageio_ffmpeg.get_ffmpeg_exe()
    except ImportError:
        return 'ffmpeg'


def decode_pcm(path):
    raw = subprocess.run([ffmpeg_exe(), '-v', 'error', '-i', path, '-f', 's16le', '-ac', '1', '-ar', str(SR), 'pipe:1'],
                         capture_output=True, check=True).stdout
    return np.frombuffer(raw, dtype=np.int16).astype(np.float32) / 32768.0


def validate_narration_segments(segments):
    if not isinstance(segments, list) or len(segments) == 0:
        raise ValueError("narration_segments must be a non-empty list of segment objects")
    validated = []
    for i, seg in enumerate(segments):
        if not isinstance(seg, dict):
            raise ValueError(f"narration_segments[{i}] must be an object")
        text = seg.get("text", "")
        if not isinstance(text, str) or not text.strip():
            raise ValueError(f"narration_segments[{i}].text cannot be empty")
        pause = seg.get("pause_after_ms", 250)
        if not isinstance(pause, (int, float)) or pause < 0 or pause > 3000:
            raise ValueError(f"narration_segments[{i}].pause_after_ms must be an integer between 0 and 3000 ms (got {pause})")
        validated.append({
            "text": text.strip(),
            "pause_after_ms": int(pause)
        })
    return validated


# ── PASS A ────────────────────────────────────────────────────────────
async def pass_a(plan, run_dir, scene_filter):
    import edge_tts
    voice = plan.get('voice', {})
    name, rate, pitch = voice.get('name', 'id-ID-GadisNeural'), voice.get('rate', '-2%'), voice.get('pitch', '+0Hz')
    adir = os.path.join(run_dir, 'audio'); os.makedirs(adir, exist_ok=True)
    out = {'voice': {'name': name, 'rate': rate, 'pitch': pitch}, 'scenes': {}}
    for sc in plan['scenes']:
        sid = sc['scene_id']
        if scene_filter and sid not in scene_filter:
            continue

        has_segments = 'narration_segments' in sc and sc['narration_segments'] is not None
        if has_segments:
            segments = validate_narration_segments(sc['narration_segments'])
            scene_pcm_chunks = []
            words = []
            current_offset_ms = 0.0

            for seg_idx, seg in enumerate(segments):
                seg_text = seg['text']
                tmp_mp3 = os.path.join(adir, f'{sid}_seg{seg_idx:02d}.mp3')
                seg_words, seg_audio = [], bytearray()
                comm = edge_tts.Communicate(seg_text, name, rate=rate, pitch=pitch, boundary='WordBoundary')
                async for ch in comm.stream():
                    if ch['type'] == 'audio':
                        seg_audio.extend(ch['data'])
                    elif ch['type'] == 'WordBoundary':
                        seg_words.append({
                            'w': ch['text'],
                            's': round((ch['offset'] / 1e4) + current_offset_ms, 1),
                            'e': round(((ch['offset'] + ch['duration']) / 1e4) + current_offset_ms, 1)
                        })
                with open(tmp_mp3, 'wb') as f:
                    f.write(seg_audio)
                seg_pcm = decode_pcm(tmp_mp3)

                # Micro-fade (5ms) to prevent boundary clicks/pops
                fade_len = min(int(SR * 0.005), len(seg_pcm) // 4)
                if fade_len > 0:
                    fade_in = np.linspace(0.0, 1.0, fade_len, dtype=np.float32)
                    fade_out = np.linspace(1.0, 0.0, fade_len, dtype=np.float32)
                    seg_pcm[:fade_len] *= fade_in
                    seg_pcm[-fade_len:] *= fade_out

                scene_pcm_chunks.append(seg_pcm)
                words.extend(seg_words)
                seg_dur_ms = len(seg_pcm) / SR * 1000.0

                if seg_idx < len(segments) - 1 and seg['pause_after_ms'] > 0:
                    pause_samples = int(round(seg['pause_after_ms'] / 1000.0 * SR))
                    scene_pcm_chunks.append(np.zeros(pause_samples, dtype=np.float32))
                    current_offset_ms += seg_dur_ms + seg['pause_after_ms']
                else:
                    current_offset_ms += seg_dur_ms

                try:
                    os.remove(tmp_mp3)
                except Exception:
                    pass

            combined_pcm = np.concatenate(scene_pcm_chunks) if scene_pcm_chunks else np.zeros(0, dtype=np.float32)
            out_audio = os.path.join(adir, f'{sid}.wav')
            pcm_int16 = (np.clip(combined_pcm, -1.0, 1.0) * 32767).astype(np.int16)
            with wave.open(out_audio, 'wb') as wf:
                wf.setnchannels(1)
                wf.setsampwidth(2)
                wf.setframerate(SR)
                wf.writeframes(pcm_int16.tobytes())

            dur = round(len(combined_pcm) / SR * 1000.0, 1)
            full_text = ' '.join(seg['text'] for seg in segments)
            out['scenes'][sid] = {
                'text': full_text,
                'audio': out_audio,
                'duration_ms': dur,
                'words': words,
                'segments_count': len(segments),
                'sha256': hashlib.sha256(pcm_int16.tobytes()).hexdigest()
            }
            print(f'[PASS A (SEGMENTED)] {sid}: {dur:.0f} ms, {len(segments)} segments, {len(words)} word boundaries')
        else:
            text = sc.get('narration')
            if not text or not isinstance(text, str) or not text.strip():
                raise ValueError(f"Scene {sid} must have non-empty narration or narration_segments")
            text = text.strip()
            mp3 = os.path.join(adir, f'{sid}.mp3')
            words, audio = [], bytearray()
            comm = edge_tts.Communicate(text, name, rate=rate, pitch=pitch, boundary='WordBoundary')
            async for ch in comm.stream():
                if ch['type'] == 'audio':
                    audio.extend(ch['data'])
                elif ch['type'] == 'WordBoundary':
                    words.append({'w': ch['text'], 's': round(ch['offset'] / 1e4, 1), 'e': round((ch['offset'] + ch['duration']) / 1e4, 1)})
            with open(mp3, 'wb') as f:
                f.write(audio)
            pcm = decode_pcm(mp3)
            dur = round(len(pcm) / SR * 1000.0, 1)
            out['scenes'][sid] = {
                'text': text,
                'audio': mp3,
                'duration_ms': dur,
                'words': words,
                'sha256': hashlib.sha256(bytes(audio)).hexdigest()
            }
            print(f'[PASS A] {sid}: {dur:.0f} ms, {len(words)} word boundaries')
    p = os.path.join(run_dir, 'narration.json')
    with open(p, 'w', encoding='utf-8') as f:
        json.dump(out, f, indent=2, ensure_ascii=False)
    return p, out


# ── PASS B ────────────────────────────────────────────────────────────
def pass_b(plan_path, narr_path, run_dir, scene_filter, headed=False, workspace_root=None, playwright_path=None,
           mode=None, video_out=None, events_out=None, manifest_out=None, frames_out=None):
    cmd = ['node', os.path.join(HERE, 'continuous', 'engine.cjs'), '--plan', plan_path, '--narration', narr_path,
           '--run-dir', run_dir, '--ffmpeg', ffmpeg_exe()]
    if mode:
        cmd += ['--mode', mode]
    if video_out:
        cmd += ['--video-out', video_out]
    if events_out:
        cmd += ['--events-out', events_out]
    if manifest_out:
        cmd += ['--manifest-out', manifest_out]
    if frames_out:
        cmd += ['--frames-out', frames_out]
    if scene_filter:
        cmd += ['--scenes', ','.join(scene_filter)]
    if headed:
        cmd += ['--headed']
    if workspace_root:
        cmd += ['--workspace-root', workspace_root]
    if playwright_path:
        cmd += ['--playwright', playwright_path]
    env = os.environ.copy()
    host_nm = os.path.abspath(os.path.join(HERE, '..', '..', '..', '..', 'node_modules'))
    existing_np = env.get('NODE_PATH', '')
    all_nps = [p for p in [existing_np, host_nm] if p and os.path.isdir(p)]
    if all_nps:
        env['NODE_PATH'] = os.pathsep.join(all_nps)
    print(f'[PASS B {"(" + mode.upper() + ")" if mode else ""}]', ' '.join(cmd))
    subprocess.run(cmd, check=True, env=env)


# ── PASS C: SFX synthesis (soft, short, deterministic) ─────────────────
def sfx_tap(seed=0):
    rng = np.random.default_rng(seed)
    n = int(SR * 0.045); t = np.arange(n) / SR
    body = np.sin(2 * np.pi * 1650 * t) * np.exp(-t / 0.006) * 0.55
    thump = np.sin(2 * np.pi * 180 * t) * np.exp(-t / 0.012) * 0.35
    noise = rng.standard_normal(n) * np.exp(-t / 0.0025) * 0.25
    return (body + thump + noise) * 0.22


def sfx_key(seed):
    rng = np.random.default_rng(seed)
    n = int(SR * 0.03); t = np.arange(n) / SR
    f = 2300 + rng.uniform(-350, 350)
    click = np.sin(2 * np.pi * f * t) * np.exp(-t / 0.0035)
    noise = rng.standard_normal(n) * np.exp(-t / 0.0018) * 0.5
    return (click * 0.5 + noise * 0.5) * (0.085 + rng.uniform(-0.015, 0.015))


from audio_mixer import resolve_music_config, load_audio_pcm, mix_bgm_track, measure_bgm_acoustics


def pass_c(run_dir, narr, plan=None, cli_bgm=None, cli_genre=None, events_file='events.json', manifest_file='recording-manifest.json',
           out_wav='audio-master.wav', out_placements='audio-placements.json'):
    ev_path = os.path.join(run_dir, 'recording', events_file)
    if not os.path.isfile(ev_path):
        ev_path = os.path.join(run_dir, 'recording', 'events.json')
    with open(ev_path, encoding='utf-8') as f:
        evj = json.load(f)

    man_path = os.path.join(run_dir, 'recording', manifest_file)
    if not os.path.isfile(man_path):
        man_path = os.path.join(run_dir, 'recording', 'recording-manifest.json')
    with open(man_path, encoding='utf-8') as f:
        man = json.load(f)

    total = int(round(man['duration_ms'] / 1000.0 * SR))
    mix = np.zeros(total + SR, dtype=np.float32)
    placements = []
    narration_intervals = []

    def put(sig, at_ms, kind, ref):
        i = int(round(at_ms / 1000.0 * SR))
        j = min(len(mix), i + len(sig))
        if i < len(mix):
            mix[i:j] += sig[: j - i]
        placements.append({'kind': kind, 'at_ms': round(at_ms, 3), 'dur_ms': round(len(sig) / SR * 1000, 1), 'ref': ref})

    for e in evj['events']:
        if e['type'] == 'narration_start':
            sid = e['scene_id']
            sig = decode_pcm(narr['scenes'][sid]['audio']) * 0.92
            dur_ms = round(len(sig) / SR * 1000, 1)
            put(sig, e['at_ms'], 'narration', sid)
            narration_intervals.append((e['at_ms'], e['at_ms'] + dur_ms))
        elif e['type'] == 'tap':
            put(sfx_tap(e['frame']), e['vt_ms'], 'tap', e.get('label'))
        elif e['type'] == 'key':
            put(sfx_key(e['frame']), e['vt_ms'], 'key', e.get('ch'))

    # Adaptive Background Music (V4)
    music_cfg = None
    bgm_acoustics = None
    if plan or cli_bgm or cli_genre:
        try:
            skill_root = os.path.dirname(HERE)
            music_cfg = resolve_music_config(plan or {}, cli_bgm=cli_bgm, cli_genre=cli_genre, skill_root=skill_root)
        except Exception as ex:
            print(f"[PASS C Warning] Music config resolution error: {ex}")
            if cli_bgm or (plan and plan.get("music")):
                raise

    if music_cfg and music_cfg.get("enabled"):
        bgm_file = music_cfg["file"]
        print(f"[PASS C] Integrating adaptive background music: {bgm_file}")
        bgm_pcm = load_audio_pcm(bgm_file, ffmpeg_exe(), target_sr=SR)
        bgm_track, env = mix_bgm_track(
            bgm_pcm=bgm_pcm,
            total_samples=total,
            narration_intervals=narration_intervals,
            config=music_cfg,
            sample_rate=SR
        )
        mix[:total] += bgm_track
        placements.append({
            'kind': 'background_music',
            'file': bgm_file,
            'base_volume': music_cfg.get('base_volume', 0.18),
            'ducking_volume': music_cfg.get('ducking_volume', 0.055),
            'at_ms': 0.0,
            'dur_ms': round(total / SR * 1000.0, 1),
            'ref': 'bgm'
        })
        bgm_acoustics = measure_bgm_acoustics(
            master_pcm=mix[:total],
            bgm_track=bgm_track[:total],
            speech_intervals=narration_intervals,
            sample_rate=SR
        )
        print(f"[PASS C] BGM Acoustics: pause_rms={bgm_acoustics['pause_bgm_rms_dbfs']} dBFS, "
              f"ducked_rms={bgm_acoustics['ducked_bgm_rms_dbfs']} dBFS, "
              f"voice_adv=+{bgm_acoustics['measured_voice_advantage_db']} dB, "
              f"audible={bgm_acoustics['audible']}")

    mix = mix[:total]
    peak = float(np.max(np.abs(mix))) if len(mix) else 1.0
    if peak > 0.98:
        mix *= 0.98 / peak
    pcm = (np.clip(mix, -1, 1) * 32767).astype(np.int16)
    audio_dir = os.path.join(run_dir, 'audio'); os.makedirs(audio_dir, exist_ok=True)
    wav = os.path.join(audio_dir, out_wav)
    with wave.open(wav, 'wb') as w:
        w.setnchannels(1); w.setsampwidth(2); w.setframerate(SR); w.writeframes(pcm.tobytes())
    
    placements_data = {
        'sample_rate': SR,
        'source': os.path.join('recording', events_file),
        'background_music': music_cfg if (music_cfg and music_cfg.get("enabled")) else None,
        'bgm_acoustics': bgm_acoustics,
        'placements': placements
    }
    with open(os.path.join(audio_dir, out_placements), 'w', encoding='utf-8') as f:
        json.dump(placements_data, f, indent=2, ensure_ascii=False)
    print(f'[PASS C] audio master {len(pcm) / SR:.3f}s -> {out_wav}, {len(placements)} placements')
    return wav


def pass_d(run_dir, wav, out_name, vid_name='continuous-60fps.mp4'):
    vid = os.path.join(run_dir, 'recording', vid_name)
    if not os.path.isfile(vid):
        # Fallback to standard
        vid = os.path.join(run_dir, 'recording', 'continuous-60fps.mp4')
    out_dir = os.path.join(run_dir, 'final'); os.makedirs(out_dir, exist_ok=True)
    out = os.path.join(out_dir, out_name)
    subprocess.run([ffmpeg_exe(), '-y', '-v', 'error', '-i', vid, '-i', wav, '-map', '0:v:0', '-map', '1:a:0',
                    '-c:v', 'copy', '-c:a', 'aac', '-b:a', '192k', '-ac', '2', '-ar', str(SR),
                    '-movflags', '+faststart', '-shortest', out], check=True)
    print('[PASS D] final:', out)
    return out


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--plan', required=True)
    ap.add_argument('--run-dir', required=True)
    ap.add_argument('--scenes', default='')
    ap.add_argument('--out-name', default='final-tutorial.mp4')
    ap.add_argument('--headed', action='store_true')
    ap.add_argument('--skip-tts', action='store_true')
    ap.add_argument('--workspace-root', default=None)
    ap.add_argument('--playwright', default=None)
    ap.add_argument('--bgm', default=None, help='Path to optional background music file')
    ap.add_argument('--bgm-genre', default=None, help='Optional BGM genre preset override (e.g. upbeat_jazz, ambient)')
    ap.add_argument('--mode', default=None, choices=['dual', 'desktop', 'mobile'], help='Output mode: dual, desktop, or mobile')
    a = ap.parse_args()
    scene_filter = [s for s in a.scenes.split(',') if s] or None
    os.makedirs(a.run_dir, exist_ok=True)
    with open(a.plan, encoding='utf-8') as f:
        plan = json.load(f)

    # Determine mode: CLI flag overrides plan, default is dual
    mode = a.mode or plan.get('mode', 'dual')
    if mode not in ['dual', 'desktop', 'mobile']:
        mode = 'dual'

    print(f"[PIPELINE V4] Active execution mode: {mode.upper()}")

    # PASS A: Synthesis narration once
    if a.skip_tts and os.path.isfile(os.path.join(a.run_dir, 'narration.json')):
        narr_path = os.path.join(a.run_dir, 'narration.json')
        with open(narr_path, encoding='utf-8') as f:
            narr = json.load(f)
    else:
        narr_path, narr = asyncio.run(pass_a(plan, a.run_dir, scene_filter))

    import shutil
    rec_dir = os.path.join(a.run_dir, 'recording')
    audio_dir = os.path.join(a.run_dir, 'audio')
    final_dir = os.path.join(a.run_dir, 'final')

    if mode == 'dual':
        # 1. Desktop Recording & Render
        print("\n=== RECORDING PASS B: DESKTOP (1920x1080 Landscape) ===")
        pass_b(a.plan, narr_path, a.run_dir, scene_filter, a.headed, a.workspace_root, a.playwright,
               mode='desktop', video_out=os.path.join(rec_dir, 'continuous-desktop-60fps.mp4'),
               events_out=os.path.join(rec_dir, 'events-desktop.json'),
               manifest_out=os.path.join(rec_dir, 'recording-manifest-desktop.json'),
               frames_out=os.path.join(rec_dir, 'frames-desktop.jsonl'))
        
        wav_desktop = pass_c(a.run_dir, narr, plan=plan, cli_bgm=a.bgm, cli_genre=a.bgm_genre,
                             events_file='events-desktop.json', manifest_file='recording-manifest-desktop.json',
                             out_wav='audio-master-desktop.wav', out_placements='audio-placements-desktop.json')
        
        out_desktop = pass_d(a.run_dir, wav_desktop, 'final-tutorial-desktop.mp4', vid_name='continuous-desktop-60fps.mp4')

        # 2. Mobile Recording & Render
        print("\n=== RECORDING PASS B: MOBILE (1080x1920 Portrait) ===")
        pass_b(a.plan, narr_path, a.run_dir, scene_filter, a.headed, a.workspace_root, a.playwright,
               mode='mobile', video_out=os.path.join(rec_dir, 'continuous-mobile-60fps.mp4'),
               events_out=os.path.join(rec_dir, 'events-mobile.json'),
               manifest_out=os.path.join(rec_dir, 'recording-manifest-mobile.json'),
               frames_out=os.path.join(rec_dir, 'frames-mobile.jsonl'))

        wav_mobile = pass_c(a.run_dir, narr, plan=plan, cli_bgm=a.bgm, cli_genre=a.bgm_genre,
                            events_file='events-mobile.json', manifest_file='recording-manifest-mobile.json',
                            out_wav='audio-master-mobile.wav', out_placements='audio-placements-mobile.json')

        out_mobile = pass_d(a.run_dir, wav_mobile, 'final-tutorial-mobile.mp4', vid_name='continuous-mobile-60fps.mp4')

        # Provide legacy recording aliases (without redundant 3rd video in final/)
        shutil.copy2(os.path.join(rec_dir, 'continuous-mobile-60fps.mp4'), os.path.join(rec_dir, 'continuous-60fps.mp4'))
        shutil.copy2(os.path.join(rec_dir, 'events-mobile.json'), os.path.join(rec_dir, 'events.json'))
        shutil.copy2(os.path.join(rec_dir, 'recording-manifest-mobile.json'), os.path.join(rec_dir, 'recording-manifest.json'))
        shutil.copy2(wav_mobile, os.path.join(audio_dir, 'audio-master.wav'))
        shutil.copy2(os.path.join(audio_dir, 'audio-placements-mobile.json'), os.path.join(audio_dir, 'audio-placements.json'))

        print(f"\n[PIPELINE V4] Dual outputs successfully created:")
        print(f"  - Desktop Video: {out_desktop}")
        print(f"  - Mobile Video:  {out_mobile}")

    elif mode == 'desktop':
        pass_b(a.plan, narr_path, a.run_dir, scene_filter, a.headed, a.workspace_root, a.playwright,
               mode='desktop', video_out=os.path.join(rec_dir, 'continuous-desktop-60fps.mp4'),
               events_out=os.path.join(rec_dir, 'events-desktop.json'),
               manifest_out=os.path.join(rec_dir, 'recording-manifest-desktop.json'),
               frames_out=os.path.join(rec_dir, 'frames-desktop.jsonl'))
        
        wav = pass_c(a.run_dir, narr, plan=plan, cli_bgm=a.bgm, cli_genre=a.bgm_genre,
                     events_file='events-desktop.json', manifest_file='recording-manifest-desktop.json',
                     out_wav='audio-master-desktop.wav', out_placements='audio-placements-desktop.json')
        
        out_desktop = pass_d(a.run_dir, wav, 'final-tutorial-desktop.mp4', vid_name='continuous-desktop-60fps.mp4')
        shutil.copy2(out_desktop, os.path.join(final_dir, a.out_name))
        shutil.copy2(wav, os.path.join(audio_dir, 'audio-master.wav'))

    elif mode == 'mobile':
        pass_b(a.plan, narr_path, a.run_dir, scene_filter, a.headed, a.workspace_root, a.playwright,
               mode='mobile', video_out=os.path.join(rec_dir, 'continuous-mobile-60fps.mp4'),
               events_out=os.path.join(rec_dir, 'events-mobile.json'),
               manifest_out=os.path.join(rec_dir, 'recording-manifest-mobile.json'),
               frames_out=os.path.join(rec_dir, 'frames-mobile.jsonl'))
        
        wav = pass_c(a.run_dir, narr, plan=plan, cli_bgm=a.bgm, cli_genre=a.bgm_genre,
                     events_file='events-mobile.json', manifest_file='recording-manifest-mobile.json',
                     out_wav='audio-master-mobile.wav', out_placements='audio-placements-mobile.json')
        
        out_mobile = pass_d(a.run_dir, wav, 'final-tutorial-mobile.mp4', vid_name='continuous-mobile-60fps.mp4')
        shutil.copy2(out_mobile, os.path.join(final_dir, a.out_name))
        shutil.copy2(wav, os.path.join(audio_dir, 'audio-master.wav'))


if __name__ == '__main__':
    main()

