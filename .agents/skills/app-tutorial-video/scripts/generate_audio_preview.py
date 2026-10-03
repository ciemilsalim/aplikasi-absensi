#!/usr/bin/env python
# generate_audio_preview.py — V3.5.1 Audio Preview Generator for Human Review
# Generates:
#   .tutorial-video/audio-preview/typing-preview.wav
#   .tutorial-video/audio-preview/click-preview.wav
#   .tutorial-video/audio-preview/mixed-preview.wav
import os
import shutil
import struct
import sys
import wave

def get_ffmpeg():
    ffmpeg = shutil.which('ffmpeg')
    if not ffmpeg:
        try:
            import imageio_ffmpeg
            ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
        except Exception:
            pass
    return ffmpeg

def load_wav_pcm(path):
    if not os.path.isfile(path):
        return []
    with wave.open(path, 'r') as wf:
        cf = wf.readframes(wf.getnframes())
        return [struct.unpack('<h', cf[i:i+2])[0] for i in range(0, len(cf), 2)]

def extract_narration_pcm(narration_path, duration_sec=5.0):
    import subprocess
    ffmpeg = get_ffmpeg()
    if not (ffmpeg and os.path.isfile(narration_path)):
        # Generate clean synthetic voice tone for standalone preview if narration file is absent
        samples = []
        for i in range(int(44100 * duration_sec)):
            t = i / 44100.0
            val = int(3200 * math.sin(2 * math.pi * 220 * t) * (0.8 + 0.2 * math.sin(2 * math.pi * 3 * t)))
            samples.append(val)
        return samples

    cmd = [
        ffmpeg, '-y', '-ss', '4.0', '-t', str(duration_sec),
        '-i', narration_path,
        '-vn', '-ac', '1', '-ar', '44100', '-f', 's16le', '-'
    ]
    proc = subprocess.run(cmd, capture_output=True)
    raw = proc.stdout
    count = len(raw) // 2
    if count == 0:
        return [0] * int(44100 * duration_sec)
    return list(struct.unpack(f'<{count}h', raw[:count*2]))

def save_wav(pcm_samples, out_path, framerate=44100):
    os.makedirs(os.path.dirname(os.path.abspath(out_path)), exist_ok=True)
    with wave.open(out_path, 'w') as wf:
        wf.setnchannels(1)
        wf.setsampwidth(2)
        wf.setframerate(framerate)
        packed = bytearray()
        for s in pcm_samples:
            clamped = max(-32767, min(32767, int(s)))
            packed.extend(struct.pack('<h', clamped))
        wf.writeframes(packed)
    print(f"[Audio Preview] Generated: {out_path} ({len(pcm_samples)/framerate:.2f}s)")

def generate_previews(audio_dir, narration_path, out_preview_dir):
    skill_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    click_path = os.path.join(skill_root, 'resources', 'audio', 'mouse-click.wav')
    if not os.path.isfile(click_path):
        click_path = os.path.join(skill_root, 'resources', 'audio', 'click.wav')
    typing_path = os.path.join(skill_root, 'resources', 'audio', 'typing.wav')

    click_samples = load_wav_pcm(click_path)
    typing_samples = load_wav_pcm(typing_path)

    framerate = 44100

    # 1. typing-preview.wav (5.0s: narration + typing keystrokes at 8% volume)
    narr_samples_5s = extract_narration_pcm(narration_path, 5.0)
    typing_buf = list(narr_samples_5s)
    # inject keystrokes from 1.0s to 4.2s
    start_idx = int(1.0 * framerate)
    end_idx = int(4.2 * framerate)
    curr = start_idx
    step = len(typing_samples)
    while curr < end_idx and typing_samples:
        for i, s in enumerate(typing_samples):
            if curr + i < len(typing_buf):
                typing_buf[curr + i] += int(s * 0.08)
        curr += step + int(framerate * 0.075)

    save_wav(typing_buf, os.path.join(out_preview_dir, 'typing-preview.wav'))

    # 2. click-preview.wav (5.0s: narration + click at 1.5s and 3.5s at 18% volume)
    click_buf = list(narr_samples_5s)
    for t_click in [1.5, 3.5]:
        c_idx = int(t_click * framerate)
        for i, s in enumerate(click_samples):
            if c_idx + i < len(click_buf):
                click_buf[c_idx + i] += int(s * 0.18)

    save_wav(click_buf, os.path.join(out_preview_dir, 'click-preview.wav'))

    # 3. mixed-preview.wav (7.0s: narration + click + typing + final click)
    narr_samples_7s = extract_narration_pcm(narration_path, 7.0)
    mixed_buf = list(narr_samples_7s)
    # First click at 1.0s
    c1_idx = int(1.0 * framerate)
    for i, s in enumerate(click_samples):
        if c1_idx + i < len(mixed_buf):
            mixed_buf[c1_idx + i] += int(s * 0.18)

    # Typing from 2.0s to 5.2s
    t_curr = int(2.0 * framerate)
    t_end = int(5.2 * framerate)
    while t_curr < t_end and typing_samples:
        for i, s in enumerate(typing_samples):
            if t_curr + i < len(mixed_buf):
                mixed_buf[t_curr + i] += int(s * 0.08)
        t_curr += step + int(framerate * 0.07)

    # Final submission click at 5.8s
    c2_idx = int(5.8 * framerate)
    for i, s in enumerate(click_samples):
        if c2_idx + i < len(mixed_buf):
            mixed_buf[c2_idx + i] += int(s * 0.18)

    save_wav(mixed_buf, os.path.join(out_preview_dir, 'mixed-preview.wav'))

if __name__ == '__main__':
    root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', '..', '..'))
    out_dir = os.path.join(root, '.tutorial-video', 'audio-preview')
    audio_dir = os.path.join(root, '.agents', 'skills', 'app-tutorial-video', 'resources', 'audio')
    narr_mp3 = os.path.join(root, '.tutorial-video', 'runs', 'run-20261003-parent-leave-v35-desktop', 'audio', 'narration.mp3')
    generate_previews(audio_dir, narr_mp3, out_dir)
