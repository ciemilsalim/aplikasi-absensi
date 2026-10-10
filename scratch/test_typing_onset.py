import struct, subprocess, imageio_ffmpeg, wave, json, math, os

ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()

def detect_typing_actual_bounds(mp4_path, template_path, exp_start_ms, exp_end_ms):
    t_start = max(0, (exp_start_ms - 100) / 1000.0)
    dur = (exp_end_ms - exp_start_ms + 200) / 1000.0
    cmd = [ffmpeg, '-y', '-ss', f'{t_start:.4f}', '-t', f'{dur:.4f}', '-i', mp4_path,
           '-vn', '-ac', '1', '-ar', '44100', '-f', 's16le', '-']
    p = subprocess.run(cmd, capture_output=True)
    raw = p.stdout
    audio = [struct.unpack('<h', raw[i*2:i*2+2])[0] for i in range(len(raw)//2)]
    
    wf = wave.open(template_path)
    traw = wf.readframes(wf.getnframes())
    tmpl = [struct.unpack('<h', traw[i*2:i*2+2])[0] for i in range(min(len(traw)//2, 600))]
    t_mean = sum(tmpl)/len(tmpl)
    t_dev = [s - t_mean for s in tmpl]
    t_var = math.sqrt(sum(d*d for d in t_dev)) or 1.0
    
    # 1. Search for first keystroke near exp_start_ms (window -50ms to +50ms)
    start_search_start_sample = max(0, int(round(((exp_start_ms - 50)/1000.0 - t_start) * 44100)))
    start_search_end_sample = min(len(audio) - len(tmpl), int(round(((exp_start_ms + 50)/1000.0 - t_start) * 44100)))
    
    best_r_start = -2.0
    best_idx_start = start_search_start_sample
    N = len(tmpl)
    for idx in range(start_search_start_sample, start_search_end_sample, 2):
        chunk = audio[idx:idx+N]
        c_mean = sum(chunk)/N
        c_dev = [c - c_mean for c in chunk]
        c_var = math.sqrt(sum(d*d for d in c_dev))
        if c_var > 30.0:
            cov = sum(cd*td for cd, td in zip(c_dev, t_dev))
            r = cov / (c_var * t_var)
            if r > best_r_start:
                best_r_start = r
                best_idx_start = idx
                
    actual_start_sec = t_start + best_idx_start / 44100.0
    actual_start_ms = int(round(actual_start_sec * 1000.0))
    start_off = abs(actual_start_ms - exp_start_ms)
    
    # 2. Search for last keystroke near exp_end_ms (window -80ms to +40ms)
    end_search_start_sample = max(0, int(round(((exp_end_ms - 80)/1000.0 - t_start) * 44100)))
    end_search_end_sample = min(len(audio) - len(tmpl), int(round(((exp_end_ms + 40)/1000.0 - t_start) * 44100)))
    
    best_r_end = -2.0
    best_idx_end = end_search_start_sample
    for idx in range(end_search_start_sample, end_search_end_sample, 2):
        chunk = audio[idx:idx+N]
        c_mean = sum(chunk)/N
        c_dev = [c - c_mean for c in chunk]
        c_var = math.sqrt(sum(d*d for d in c_dev))
        if c_var > 30.0:
            cov = sum(cd*td for cd, td in zip(c_dev, t_dev))
            r = cov / (c_var * t_var)
            if r > best_r_end:
                best_r_end = r
                best_idx_end = idx
                
    actual_end_sec = t_start + best_idx_end / 44100.0
    actual_end_ms = int(round(actual_end_sec * 1000.0))
    end_off = abs(actual_end_ms - exp_end_ms)
    
    return actual_start_ms, start_off, actual_end_ms, end_off, best_r_start

mob_video = r'.tutorial-video\runs\run-20261003-parent-leave-v352-mobile\mobile\video\final-tutorial.mp4'
mob_ct = json.load(open(r'.tutorial-video\runs\run-20261003-parent-leave-v352-mobile\mobile\recording\canonical-timeline.json'))
tmpl_path = r'.agents\skills\app-tutorial-video\resources\audio\typing.wav'

print('=== MOBILE TYPING ACTUAL WAVEFORM BOUNDS ===')
for ev in mob_ct['events']:
    if ev['type'] == 'typing':
        s_ms = ev['start_ms']
        e_ms = ev['end_ms']
        act_s, s_off, act_e, e_off, r = detect_typing_actual_bounds(mob_video, tmpl_path, s_ms, e_ms)
        st = 'PASS' if s_off <= 50 and e_off <= 80 else 'FAIL'
        print(f"{ev['id']}: start exp={s_ms}ms act={act_s}ms off={s_off}ms | end exp={e_ms}ms act={act_e}ms off={e_off}ms [{st}]")
