import struct, subprocess, imageio_ffmpeg, wave, json, math, os

ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()

def detect_click_onset(mp4_path, template_path, expected_ms, window_ms=250):
    t_start_sec = max(0, (expected_ms - window_ms) / 1000.0)
    dur_sec = (window_ms * 2) / 1000.0
    cmd = [ffmpeg, '-y', '-ss', f'{t_start_sec:.4f}', '-t', f'{dur_sec:.4f}', '-i', mp4_path,
           '-vn', '-ac', '1', '-ar', '44100', '-f', 's16le', '-']
    p = subprocess.run(cmd, capture_output=True)
    raw = p.stdout
    count = len(raw) // 2
    audio = [struct.unpack('<h', raw[i*2:i*2+2])[0] for i in range(count)]
    
    wf = wave.open(template_path)
    tmpl_raw = wf.readframes(wf.getnframes())
    tmpl_count = len(tmpl_raw) // 2
    tmpl = [struct.unpack('<h', tmpl_raw[i*2:i*2+2])[0] for i in range(min(tmpl_count, 600))]
    
    tmpl_norm = math.sqrt(sum(s*s for s in tmpl)) or 1.0
    
    corrs = []
    for idx in range(0, len(audio) - len(tmpl), 2):
        chunk = audio[idx:idx+len(tmpl)]
        c_norm = math.sqrt(sum(s*s for s in chunk))
        if c_norm > 5.0:
            dot = sum(c*t for c, t in zip(chunk, tmpl))
            corrs.append((dot / (c_norm * tmpl_norm), idx))
        else:
            corrs.append((0.0, idx))
            
    if not corrs:
        return None, 0, 0.0
        
    best_corr, best_idx = max(corrs, key=lambda x: x[0])
    actual_sec = t_start_sec + best_idx / 44100.0
    actual_ms = int(round(actual_sec * 1000.0))
    offset_ms = abs(actual_ms - expected_ms)
    
    avg_corr = sum(abs(c[0]) for c in corrs) / len(corrs) if corrs else 1.0
    confidence = min(1.0, max(0.0, (best_corr - avg_corr) / (best_corr if best_corr > 0 else 1.0)))
    return actual_ms, offset_ms, round(confidence, 3)

mob_video = r'.tutorial-video\runs\run-20261003-parent-leave-v352-mobile\mobile\video\final-tutorial.mp4'
mob_ct = json.load(open(r'.tutorial-video\runs\run-20261003-parent-leave-v352-mobile\mobile\recording\canonical-timeline.json'))
mob_tmpl = r'.agents\skills\app-tutorial-video\resources\audio\tap.wav'

print('=== MOBILE TAPS ===')
for ev in mob_ct['events']:
    if ev['type'] in ('click', 'tap'):
        exp_ms = ev['time_ms']
        act_ms, off, conf = detect_click_onset(mob_video, mob_tmpl, exp_ms)
        print(f"{ev['id']}: expected={exp_ms}ms, actual_onset={act_ms}ms, offset={off}ms, conf={conf}")
