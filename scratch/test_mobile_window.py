import struct, subprocess, imageio_ffmpeg, wave, json, math, os
ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
mob_video = r'.tutorial-video\runs\run-20261003-parent-leave-v352-mobile\mobile\video\final-tutorial.mp4'
mob_ct = json.load(open(r'.tutorial-video\runs\run-20261003-parent-leave-v352-mobile\mobile\recording\canonical-timeline.json'))

templates = {}
for name in ['tap.wav', 'click.wav', 'mouse-click.wav']:
    wf = wave.open(os.path.join(r'.agents\skills\app-tutorial-video\resources\audio', name))
    traw = wf.readframes(wf.getnframes())
    templates[name] = [struct.unpack('<h', traw[i*2:i*2+2])[0] for i in range(min(len(traw)//2, 600))]

print('=== MOBILE TAPS FOCUSED DETECTION ===')
for ev in mob_ct['events']:
    if ev['type'] in ('click', 'tap'):
        exp_ms = ev['time_ms']
        # Extract 160ms window (-80ms to +80ms)
        t_start = max(0, (exp_ms - 80) / 1000.0)
        cmd = [ffmpeg, '-y', '-ss', f'{t_start:.4f}', '-t', '0.16', '-i', mob_video, '-vn', '-ac', '1', '-ar', '44100', '-f', 's16le', '-']
        p = subprocess.run(cmd, capture_output=True)
        raw = p.stdout
        audio = [struct.unpack('<h', raw[i*2:i*2+2])[0] for i in range(len(raw)//2)]
        
        # Test templates
        best_overall = (-1e18, 0, '')
        for tname, tmpl in templates.items():
            for idx in range(len(audio)-len(tmpl)):
                dot = sum(audio[idx+k]*tmpl[k] for k in range(0, len(tmpl), 2))
                if dot > best_overall[0]:
                    best_overall = (dot, idx, tname)
        det_ms = int(round((t_start + best_overall[1]/44100.0)*1000))
        off = abs(det_ms - exp_ms)
        status = 'PASS (Ideal)' if off <= 15 else ('PASS (Acceptable)' if off <= 30 else 'FAIL')
        print(f"{ev['id']}: expected={exp_ms}ms, detected={det_ms}ms, offset={off}ms ({status}), tmpl={best_overall[2]}")
