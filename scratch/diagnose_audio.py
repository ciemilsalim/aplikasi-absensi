import os
import wave
import json
import numpy as np
import subprocess

run_dir = r"d:\laragon\www\siasek\aplikasi-absensi\.tutorial-video\projects\siasek\runs\run_20261018_060434_2c3908"
bgm_path = r"d:\laragon\www\siasek\aplikasi-absensi\.agents\skills\app-tutorial-video\resources\audio\music\ambient-test-v1.wav"
master_wav = os.path.join(run_dir, "audio", "audio-master.wav")
final_mp4 = os.path.join(run_dir, "final", "final-tutorial.mp4")

print("=== 1. BGM ASSET ANALYSIS ===")
with wave.open(bgm_path, 'rb') as w:
    sr = w.getframerate()
    ch = w.getnchannels()
    frames = w.getnframes()
    pcm = np.frombuffer(w.readframes(frames), dtype=np.int16).astype(np.float32) / 32768.0
    dur = frames / sr
    peak = np.max(np.abs(pcm))
    rms = np.sqrt(np.mean(pcm ** 2))
    print(f"BGM: channels={ch}, sr={sr}, dur={dur:.2f}s, peak={peak:.4f} ({20*np.log10(peak):.2f} dBFS), rms={rms:.4f} ({20*np.log10(rms):.2f} dBFS)")

print("\n=== 2. AUDIO PLACEMENTS & PLAN CONFIG ===")
with open(os.path.join(run_dir, "audio", "audio-placements.json"), encoding='utf-8') as f:
    placements = json.load(f)
print("BGM config in placements:", placements.get('background_music'))

print("\n=== 3. AUDIO MASTER ANALYSIS ===")
with wave.open(master_wav, 'rb') as w:
    sr = w.getframerate()
    ch = w.getnchannels()
    frames = w.getnframes()
    pcm_master = np.frombuffer(w.readframes(frames), dtype=np.int16).astype(np.float32) / 32768.0
    dur = frames / sr
    peak = np.max(np.abs(pcm_master))
    rms = np.sqrt(np.mean(pcm_master ** 2))
    print(f"Master: channels={ch}, sr={sr}, dur={dur:.2f}s, peak={peak:.4f} ({20*np.log10(peak):.2f} dBFS), rms={rms:.4f} ({20*np.log10(rms):.2f} dBFS)")

# Analisis saat jeda narasi vs saat narasi bicara
with open(os.path.join(run_dir, "recording", "events.json"), encoding='utf-8') as f:
    evj = json.load(f)

scenes = evj.get('scenes', [])
print(f"\nTotal scenes: {len(scenes)}")
speech_masks = np.zeros(len(pcm_master), dtype=bool)
for sc in scenes:
    s_idx = int(round(sc['narration_start_ms'] / 1000.0 * sr))
    e_idx = int(round(sc['narration_end_ms'] / 1000.0 * sr))
    speech_masks[s_idx:min(len(speech_masks), e_idx)] = True

speech_pcm = pcm_master[speech_masks]
pause_pcm = pcm_master[~speech_masks]

if len(speech_pcm) > 0:
    sp_peak = np.max(np.abs(speech_pcm))
    sp_rms = np.sqrt(np.mean(speech_pcm ** 2))
    print(f"Speech segments: peak={sp_peak:.4f} ({20*np.log10(sp_peak):.2f} dBFS), rms={sp_rms:.4f} ({20*np.log10(sp_rms):.2f} dBFS)")

if len(pause_pcm) > 0:
    pa_peak = np.max(np.abs(pause_pcm))
    pa_rms = np.sqrt(np.mean(pause_pcm ** 2))
    print(f"Pause segments: peak={pa_peak:.4f} ({20*np.log10(pa_peak):.2f} dBFS), rms={pa_rms:.4f} ({20*np.log10(pa_rms):.2f} dBFS)")

print("\n=== 4. FINAL MP4 AUDIO STREAM EXTRACTION ===")
from imageio_ffmpeg import get_ffmpeg_exe
ffmpeg = get_ffmpeg_exe()
cmd = [ffmpeg, "-v", "error", "-i", final_mp4, "-f", "s16le", "-ac", "1", "-ar", "48000", "pipe:1"]
raw = subprocess.run(cmd, capture_output=True, check=True).stdout
pcm_mp4 = np.frombuffer(raw, dtype=np.int16).astype(np.float32) / 32768.0
mp4_dur = len(pcm_mp4) / 48000.0
mp4_peak = np.max(np.abs(pcm_mp4))
mp4_rms = np.sqrt(np.mean(pcm_mp4 ** 2))
print(f"MP4 Extracted Audio: dur={mp4_dur:.2f}s, peak={mp4_peak:.4f} ({20*np.log10(mp4_peak):.2f} dBFS), rms={mp4_rms:.4f} ({20*np.log10(mp4_rms):.2f} dBFS)")
