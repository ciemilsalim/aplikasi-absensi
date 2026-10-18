#!/usr/bin/env python
"""
preview_bgm.py — Fast Audio Preview Generator for Background Music & Narration
Generates a quick mixed audio preview without requiring browser rendering.
Uses the identical deterministic mixing and ducking algorithms as continuous_pipeline.py.
"""

import argparse
import json
import math
import os
import sys
from typing import Optional
import wave
import numpy as np

HERE = os.path.dirname(os.path.abspath(__file__))
if HERE not in sys.path:
    sys.path.insert(0, HERE)

from audio_mixer import (
    SR,
    load_audio_pcm,
    mix_bgm_track,
    compute_audio_metrics,
    GENRE_PRESETS,
    measure_bgm_acoustics
)


def get_ffmpeg():
    try:
        import imageio_ffmpeg
        return imageio_ffmpeg.get_ffmpeg_exe()
    except ImportError:
        return 'ffmpeg'


def generate_preview(
    narration_path: Optional[str] = None,
    bgm_path: Optional[str] = None,
    bgm_genre: str = "upbeat_jazz",
    out_path: str = "preview.wav",
    base_volume: Optional[float] = None,
    ducking_volume: Optional[float] = None,
    ducking_attack_ms: Optional[int] = None,
    ducking_release_ms: Optional[int] = None,
    fade_in_ms: Optional[int] = None,
    fade_out_ms: Optional[int] = None,
    target_duration_sec: Optional[float] = None,
    events_path: Optional[str] = None
) -> dict:
    ffmpeg = get_ffmpeg()
    from audio_mixer import GENRE_PRESETS, measure_bgm_acoustics

    preset = GENRE_PRESETS.get(bgm_genre, GENRE_PRESETS["upbeat_jazz"])
    b_vol = base_volume if base_volume is not None else preset["base_volume"]
    d_vol = ducking_volume if ducking_volume is not None else preset["ducking_volume"]
    d_att = ducking_attack_ms if ducking_attack_ms is not None else preset["ducking_attack_ms"]
    d_rel = ducking_release_ms if ducking_release_ms is not None else preset["ducking_release_ms"]
    f_in = fade_in_ms if fade_in_ms is not None else preset["fade_in_ms"]
    f_out = fade_out_ms if fade_out_ms is not None else preset["fade_out_ms"]

    # Resolve BGM path
    if not bgm_path:
        skill_root = os.path.dirname(HERE)
        bgm_path = os.path.abspath(os.path.join(skill_root, preset["file_rel"]))

    if not os.path.isfile(bgm_path):
        raise FileNotFoundError(f"Background music file not found: {bgm_path}")
    if os.path.abspath(bgm_path).lower() == os.path.abspath(out_path).lower():
        raise ValueError("Output path cannot overwrite input BGM file.")

    bgm_pcm = load_audio_pcm(bgm_path, ffmpeg, target_sr=SR)

    # CASE A: Standalone Music Preview (no narration)
    if not narration_path or not os.path.isfile(narration_path):
        total_sec = target_duration_sec or 35.0
        total_samples = int(total_sec * SR)

        music_cfg = {
            "enabled": True,
            "genre": bgm_genre,
            "file": bgm_path,
            "base_volume": b_vol,
            "ducking_volume": d_vol,
            "ducking_attack_ms": d_att,
            "ducking_release_ms": d_rel,
            "fade_in_ms": f_in,
            "fade_out_ms": f_out,
            "loop": True
        }

        bgm_track, env = mix_bgm_track(
            bgm_pcm=bgm_pcm,
            total_samples=total_samples,
            narration_intervals=[],
            config=music_cfg,
            sample_rate=SR
        )

        mix = bgm_track[:total_samples]
        peak = float(np.max(np.abs(mix))) if len(mix) else 0.0
        if peak > 0.98:
            mix *= 0.98 / peak
        pcm_16 = (np.clip(mix, -1, 1) * 32767).astype(np.int16)

        os.makedirs(os.path.dirname(os.path.abspath(out_path)), exist_ok=True)
        with wave.open(out_path, "wb") as wf:
            wf.setnchannels(1)
            wf.setsampwidth(2)
            wf.setframerate(SR)
            wf.writeframes(pcm_16.tobytes())

        rms = float(np.sqrt(np.mean(mix ** 2))) if len(mix) else 0.0
        return {
            "type": "standalone_music_preview",
            "genre": bgm_genre,
            "output_file": os.path.abspath(out_path),
            "sample_rate": SR,
            "duration_sec": round(len(pcm_16) / SR, 3),
            "base_volume": b_vol,
            "peak": round(peak, 4),
            "peak_dbfs": round(20.0 * math.log10(max(1e-5, peak)), 2),
            "rms_dbfs": round(20.0 * math.log10(max(1e-5, rms)), 2),
            "clipping": peak >= 0.999
        }

    # CASE B: Mixed Narration + Music Preview with Ducking
    if os.path.abspath(narration_path).lower() == os.path.abspath(out_path).lower():
        raise ValueError("Output path cannot overwrite input narration file.")

    narr_pcm = load_audio_pcm(narration_path, ffmpeg, target_sr=SR)
    narr_dur_sec = len(narr_pcm) / SR

    if target_duration_sec and target_duration_sec > 0:
        total_sec = target_duration_sec
    else:
        total_sec = narr_dur_sec + 3.0

    total_samples = int(total_sec * SR)
    narration_intervals = []

    # Narration intervals: start after 1.2s lead to demonstrate clean intro music
    start_ms = 1200.0
    end_ms = start_ms + (narr_dur_sec * 1000.0)
    narration_intervals.append((start_ms, end_ms))

    mix = np.zeros(total_samples + SR, dtype=np.float32)

    # Place speech
    n_start_sample = int(round(start_ms / 1000.0 * SR))
    n_copy_len = min(len(narr_pcm), total_samples - n_start_sample)
    if n_copy_len > 0:
        mix[n_start_sample:n_start_sample + n_copy_len] += (narr_pcm[:n_copy_len] * 0.92)

    music_cfg = {
        "enabled": True,
        "genre": bgm_genre,
        "file": bgm_path,
        "base_volume": b_vol,
        "ducking_volume": d_vol,
        "ducking_attack_ms": d_att,
        "ducking_release_ms": d_rel,
        "fade_in_ms": f_in,
        "fade_out_ms": f_out,
        "loop": True
    }

    bgm_track, env = mix_bgm_track(
        bgm_pcm=bgm_pcm,
        total_samples=total_samples,
        narration_intervals=narration_intervals,
        config=music_cfg,
        sample_rate=SR
    )

    mix[:total_samples] += bgm_track
    mix = mix[:total_samples]

    # Peak detection and protection
    peak = float(np.max(np.abs(mix))) if len(mix) else 1.0
    if peak > 0.98:
        mix *= (0.98 / peak)

    # Save output WAV (mono 48kHz PCM 16-bit)
    os.makedirs(os.path.dirname(os.path.abspath(out_path)), exist_ok=True)
    pcm_i16 = (np.clip(mix, -1.0, 1.0) * 32767).astype(np.int16)
    with wave.open(out_path, 'wb') as wf:
        wf.setnchannels(1)
        wf.setsampwidth(2)
        wf.setframerate(SR)
        wf.writeframes(pcm_i16.tobytes())

    metrics = compute_audio_metrics(mix)
    metrics["type"] = "mixed_narration_preview"
    metrics["genre"] = bgm_genre
    metrics["output_file"] = os.path.abspath(out_path)
    metrics["duration_sec"] = round(total_sec, 2)
    metrics["narration_duration_sec"] = round(narr_dur_sec, 2)
    metrics["base_volume"] = b_vol
    metrics["ducking_volume"] = d_vol
    metrics["voice_to_music_ducked_db"] = round(20.0 * math.log10(0.92 / max(1e-5, d_vol)), 2)

    return metrics


def main():
    parser = argparse.ArgumentParser(description="Fast Audio Preview Generator for Background Music")
    parser.add_argument("--narration", default=None, help="Path to narration audio file (optional for standalone music preview)")
    parser.add_argument("--bgm", default=None, help="Path to background music audio file (defaults to genre preset)")
    parser.add_argument("--genre", default="upbeat_jazz", choices=["upbeat_jazz", "ambient"], help="BGM genre preset")
    parser.add_argument("--out", required=True, help="Path to output preview WAV file")
    parser.add_argument("--base-volume", type=float, default=None, help="Base music volume (default: from preset)")
    parser.add_argument("--ducking-volume", type=float, default=None, help="Ducked music volume (default: from preset)")
    parser.add_argument("--duration", type=float, default=None, help="Preview duration in seconds")
    parser.add_argument("--events", default=None, help="Optional events.json file for exact scene timings")

    args = parser.parse_args()

    try:
        metrics = generate_preview(
            narration_path=args.narration,
            bgm_path=args.bgm,
            bgm_genre=args.genre,
            out_path=args.out,
            base_volume=args.base_volume,
            ducking_volume=args.ducking_volume,
            target_duration_sec=args.duration,
            events_path=args.events
        )
        print("=" * 60)
        print(f"[Audio Preview] Successfully generated {metrics['type']}:")
        print(f" -> Output:    {metrics['output_file']}")
        print(f" -> Genre:     {metrics['genre']}")
        print(f" -> Duration:  {metrics['duration_sec']}s")
        print(f" -> Peak:      {metrics['peak_dbfs']} dBFS (linear: {metrics['peak']})")
        print(f" -> RMS:       {metrics['rms_dbfs']} dBFS")
        if metrics.get("voice_to_music_ducked_db"):
            print(f" -> Voice/BGM: +{metrics['voice_to_music_ducked_db']} dB advantage during speech")
        print(f" -> Clipping:  {'YES (WARNING)' if metrics['clipping'] else 'NO (Clean)'}")
        print("=" * 60)
        sys.exit(0)
    except Exception as e:
        print(f"[Audio Preview Error] Failed: {e}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
