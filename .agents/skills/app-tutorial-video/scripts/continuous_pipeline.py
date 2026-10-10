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
        text = sc['narration']
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
        out['scenes'][sid] = {'text': text, 'audio': mp3, 'duration_ms': dur, 'words': words,
                              'sha256': hashlib.sha256(bytes(audio)).hexdigest()}
        print(f'[PASS A] {sid}: {dur:.0f} ms, {len(words)} word boundaries')
    p = os.path.join(run_dir, 'narration.json')
    with open(p, 'w', encoding='utf-8') as f:
        json.dump(out, f, indent=2, ensure_ascii=False)
    return p, out


# ── PASS B ────────────────────────────────────────────────────────────
def pass_b(plan_path, narr_path, run_dir, scene_filter, headed=False, workspace_root=None, playwright_path=None):
    cmd = ['node', os.path.join(HERE, 'continuous', 'engine.cjs'), '--plan', plan_path, '--narration', narr_path,
           '--run-dir', run_dir, '--ffmpeg', ffmpeg_exe()]
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
    print('[PASS B]', ' '.join(cmd))
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


def pass_c(run_dir, narr):
    with open(os.path.join(run_dir, 'recording', 'events.json'), encoding='utf-8') as f:
        evj = json.load(f)
    with open(os.path.join(run_dir, 'recording', 'recording-manifest.json'), encoding='utf-8') as f:
        man = json.load(f)
    total = int(round(man['duration_ms'] / 1000.0 * SR))
    mix = np.zeros(total + SR, dtype=np.float32)
    placements = []

    def put(sig, at_ms, kind, ref):
        i = int(round(at_ms / 1000.0 * SR))
        j = min(len(mix), i + len(sig))
        if i < len(mix):
            mix[i:j] += sig[: j - i]
        placements.append({'kind': kind, 'at_ms': round(at_ms, 3), 'dur_ms': round(len(sig) / SR * 1000, 1), 'ref': ref})

    for e in evj['events']:
        if e['type'] == 'narration_start':
            sid = e['scene_id']
            put(decode_pcm(narr['scenes'][sid]['audio']) * 0.92, e['at_ms'], 'narration', sid)
        elif e['type'] == 'tap':
            put(sfx_tap(e['frame']), e['vt_ms'], 'tap', e.get('label'))
        elif e['type'] == 'key':
            put(sfx_key(e['frame']), e['vt_ms'], 'key', e.get('ch'))
    mix = mix[:total]
    peak = float(np.max(np.abs(mix))) if len(mix) else 1.0
    if peak > 0.98:
        mix *= 0.98 / peak
    pcm = (np.clip(mix, -1, 1) * 32767).astype(np.int16)
    wav = os.path.join(run_dir, 'audio', 'audio-master.wav')
    with wave.open(wav, 'wb') as w:
        w.setnchannels(1); w.setsampwidth(2); w.setframerate(SR); w.writeframes(pcm.tobytes())
    with open(os.path.join(run_dir, 'audio', 'audio-placements.json'), 'w', encoding='utf-8') as f:
        json.dump({'sample_rate': SR, 'source': 'recording/events.json', 'placements': placements}, f, indent=2, ensure_ascii=False)
    print(f'[PASS C] audio master {len(pcm) / SR:.3f}s, {len(placements)} placements')
    return wav


def pass_d(run_dir, wav, out_name):
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
    a = ap.parse_args()
    scene_filter = [s for s in a.scenes.split(',') if s] or None
    os.makedirs(a.run_dir, exist_ok=True)
    with open(a.plan, encoding='utf-8') as f:
        plan = json.load(f)
    if a.skip_tts and os.path.isfile(os.path.join(a.run_dir, 'narration.json')):
        narr_path = os.path.join(a.run_dir, 'narration.json')
        with open(narr_path, encoding='utf-8') as f:
            narr = json.load(f)
    else:
        narr_path, narr = asyncio.run(pass_a(plan, a.run_dir, scene_filter))
    pass_b(a.plan, narr_path, a.run_dir, scene_filter, a.headed, a.workspace_root, a.playwright)
    wav = pass_c(a.run_dir, narr)
    pass_d(a.run_dir, wav, a.out_name)


if __name__ == '__main__':
    main()
