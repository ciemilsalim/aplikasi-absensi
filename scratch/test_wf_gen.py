import os, sys, math, struct, subprocess, wave, json
from PIL import Image, ImageDraw, ImageFont
import imageio_ffmpeg

ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()

def extract_pcm_window(mp4_path, start_ms, dur_ms):
    t_start = max(0, start_ms / 1000.0)
    dur = dur_ms / 1000.0
    cmd = [ffmpeg, '-y', '-ss', f'{t_start:.4f}', '-t', f'{dur:.4f}', '-i', mp4_path,
           '-vn', '-ac', '1', '-ar', '44100', '-f', 's16le', '-']
    p = subprocess.run(cmd, capture_output=True)
    raw = p.stdout
    count = len(raw) // 2
    return [struct.unpack('<h', raw[i*2:i*2+2])[0] for i in range(count)]

def draw_waveform_image(samples, exp_ms, act_ms, window_start_ms, window_dur_ms,
                        title, out_path, event_type='Click'):
    width = 1000
    height = 360
    img = Image.new('RGB', (width, height), color=(13, 17, 23))
    draw = ImageDraw.Draw(img)
    
    # Title & border
    draw.rectangle([(0, 0), (width-1, height-1)], outline=(48, 54, 61), width=1)
    draw.text((20, 15), title, fill=(240, 246, 252))
    
    # Waveform box
    wf_left = 60
    wf_right = width - 40
    wf_top = 60
    wf_bottom = height - 60
    wf_w = wf_right - wf_left
    wf_h = wf_bottom - wf_top
    center_y = wf_top + wf_h // 2
    
    # Background grid
    for step in range(5):
        gy = wf_top + int(step * (wf_h / 4))
        draw.line([(wf_left, gy), (wf_right, gy)], fill=(22, 27, 34), width=1)
    draw.line([(wf_left, center_y), (wf_right, center_y)], fill=(48, 54, 61), width=1)
    
    # Plot waveform
    n = len(samples)
    if n > 1:
        max_val = max(max(abs(s) for s in samples), 1000)
        scale_y = (wf_h * 0.42) / max_val
        prev_x = wf_left
        prev_y = center_y
        for x_px in range(wf_w):
            idx0 = int((x_px / wf_w) * n)
            idx1 = max(idx0 + 1, int(((x_px + 1) / wf_w) * n))
            chunk = samples[idx0:idx1]
            if chunk:
                min_s = min(chunk)
                max_s = max(chunk)
                y1 = center_y - int(max_s * scale_y)
                y2 = center_y - int(min_s * scale_y)
                draw.line([(wf_left + x_px, y1), (wf_left + x_px, y2)], fill=(56, 189, 248), width=1)
    
    # Markers: EXPECTED
    exp_rel_ms = exp_ms - window_start_ms
    exp_x = wf_left + int((exp_rel_ms / window_dur_ms) * wf_w)
    if wf_left <= exp_x <= wf_right:
        draw.line([(exp_x, wf_top - 5), (exp_x, wf_bottom + 5)], fill=(245, 158, 11), width=2)
        draw.text((exp_x + 5, wf_top + 10), f"EXPECTED: {exp_ms} ms", fill=(245, 158, 11))
        
    # Markers: ACTUAL AUDIO
    act_rel_ms = act_ms - window_start_ms
    act_x = wf_left + int((act_rel_ms / window_dur_ms) * wf_w)
    if wf_left <= act_x <= wf_right:
        draw.line([(act_x, wf_top - 5), (act_x, wf_bottom + 5)], fill=(16, 185, 129), width=2)
        offset = abs(act_ms - exp_ms)
        draw.text((act_x + 5, wf_bottom - 25), f"ACTUAL AUDIO: {act_ms} ms (offset: {offset} ms)", fill=(16, 185, 129))
        
    # Time axis labels
    for frac in [0.0, 0.25, 0.5, 0.75, 1.0]:
        t_val = int(window_start_ms + frac * window_dur_ms)
        tx = wf_left + int(frac * wf_w)
        draw.line([(tx, wf_bottom), (tx, wf_bottom + 5)], fill=(139, 148, 158), width=1)
        draw.text((tx - 15, wf_bottom + 10), f"{t_val}ms", fill=(139, 148, 158))
        
    os.makedirs(os.path.dirname(out_path), exist_ok=True)
    img.save(out_path, 'PNG')
    print(f"Saved waveform: {out_path}")

# Test
samples = extract_pcm_window(r'.tutorial-video\runs\run-20261003-parent-leave-v352-desktop\desktop\video\final-tutorial.mp4', 23125, 300)
draw_waveform_image(samples, 23275, 23275, 23125, 300,
                    "Desktop Click Waveform (evt_click_003)",
                    r'scratch\test_waveform.png')
