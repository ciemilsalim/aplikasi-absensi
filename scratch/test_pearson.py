import struct, subprocess, imageio_ffmpeg, wave, json, math, os
ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
mob_video = r'.tutorial-video\runs\run-20261003-parent-leave-v352-mobile\mobile\video\final-tutorial.mp4'
mob_ct = json.load(open(r'.tutorial-video\runs\run-20261003-parent-leave-v352-mobile\mobile\recording\canonical-timeline.json'))

wf = wave.open(r'.agents\skills\app-tutorial-video\resources\audio\tap.wav')
traw = wf.readframes(wf.getnframes())
tmpl = [struct.unpack('<h', traw[i*2:i*2+2])[0] for i in range(len(traw)//2)]
t_mean = sum(tmpl) / len(tmpl)
t_dev = [s - t_mean for s in tmpl]
t_var = math.sqrt(sum(d*d for d in t_dev)) or 1.0

print('=== PEARSON NORMALIZED CORRELATION ON TAP ===')
for ev in mob_ct['events']:
    if ev['type'] in ('click', 'tap'):
        exp_ms = ev['time_ms']
        # Extract 100ms window (-50ms to +50ms)
        t_start = max(0, (exp_ms - 50) / 1000.0)
        cmd = [ffmpeg, '-y', '-ss', f'{t_start:.4f}', '-t', '0.10', '-i', mob_video, '-vn', '-ac', '1', '-ar', '44100', '-f', 's16le', '-']
        p = subprocess.run(cmd, capture_output=True)
        raw = p.stdout
        audio = [struct.unpack('<h', raw[i*2:i*2+2])[0] for i in range(len(raw)//2)]
        
        best_r = -2.0
        best_idx = 0
        N = len(tmpl)
        for idx in range(0, len(audio) - N, 2):
            chunk = audio[idx:idx+N]
            c_mean = sum(chunk) / N
            c_dev = [c - c_mean for c in chunk]
            c_var = math.sqrt(sum(d*d for d in c_dev))
            if c_var > 100.0:
                cov = sum(cd * td for cd, td in zip(c_dev, t_dev))
                r = cov / (c_var * t_var)
                if r > best_r:
                    best_r = r
                    best_idx = idx
        det_ms = int(round((t_start + best_idx/44100.0)*1000))
        off = abs(det_ms - exp_ms)
        print(f"{ev['id']}: expected={exp_ms}ms, detected={det_ms}ms, offset={off}ms, r={best_r:.3f}")
