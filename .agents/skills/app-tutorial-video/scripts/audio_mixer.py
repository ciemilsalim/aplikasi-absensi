#!/usr/bin/env python
"""
audio_mixer.py — Deterministic Adaptive Audio Mixing & Ducking Engine (V4)
Provides core algorithms for background music (BGM) integration, smooth cosine
ducking envelopes, seamless crossfade looping, fade-in/fade-out, and peak protection.
"""

import hashlib
import math
import os
import subprocess
from typing import Any, Dict, List, Optional, Tuple
import numpy as np

SR = 48000
UPBEAT_JAZZ_OFFICIAL_SHA256 = "cab3b56caf103af4dd402d50265c0bc9ccc2c15bab76be5e7ff4f2d74396abaa"


def compute_file_sha256(filepath: str) -> Optional[str]:
    """Compute sha256 checksum of a file on disk."""
    if not filepath or not os.path.isfile(filepath):
        return None
    h = hashlib.sha256()
    with open(filepath, "rb") as f:
        while chunk := f.read(65536):
            h.update(chunk)
    return h.hexdigest()


GENRE_PRESETS: Dict[str, Dict[str, Any]] = {
    "upbeat_jazz": {
        "file_rel": os.path.join("resources", "audio", "music", "upbeat-jazz-v1.wav"),
        "expected_sha256": UPBEAT_JAZZ_OFFICIAL_SHA256,
        "base_volume": 0.16,
        "ducking_volume": 0.045,
        "ducking_attack_ms": 200,
        "ducking_release_ms": 500,
        "fade_in_ms": 1200,
        "fade_out_ms": 2500,
        "loop": True,
        "tempo_bpm": 80,
        "instruments": "rhodes_piano, electric_soul_bass, soul_pocket_drums, brushed_hihat, rhodes_bell_chimes",
        "license": "Royalty-Free Deterministic Procedural Composition (Skill app-tutorial-video)"
    },
    "ambient": {
        "file_rel": os.path.join("resources", "audio", "music", "ambient-test-v1.wav"),
        "base_volume": 0.18,
        "ducking_volume": 0.055,
        "ducking_attack_ms": 250,
        "ducking_release_ms": 600,
        "fade_in_ms": 1200,
        "fade_out_ms": 2500,
        "loop": True,
        "tempo_bpm": 72,
        "instruments": "synthesizer_pads, sub_bass, shimmer",
        "license": "Royalty-Free Synthetic Ambient Composition (Skill app-tutorial-video)"
    }
}

DEFAULT_MUSIC_CONFIG = {
    "enabled": False,
    "genre": "upbeat_jazz",
    "expected_sha256": UPBEAT_JAZZ_OFFICIAL_SHA256,
    "file": None,
    "base_volume": 0.16,
    "ducking_volume": 0.045,
    "ducking_attack_ms": 200,
    "ducking_release_ms": 500,
    "fade_in_ms": 1200,
    "fade_out_ms": 2500,
    "loop": True,
    "tempo_bpm": 80,
    "instruments": "rhodes_piano, electric_soul_bass, soul_pocket_drums, brushed_hihat, rhodes_bell_chimes",
    "license": "Royalty-Free Deterministic Procedural Composition (Skill app-tutorial-video)"
}


def resolve_music_config(
    plan: Dict[str, Any],
    cli_bgm: Optional[str] = None,
    cli_genre: Optional[str] = None,
    skill_root: Optional[str] = None
) -> Dict[str, Any]:
    """
    Parse and validate music configuration from tutorial plan and optional CLI overrides.
    Precedence: CLI flag > plan object > plan boolean.
    Supports genre selection (default: 'upbeat_jazz').
    """
    raw_music = plan.get("music", False)
    genre = "upbeat_jazz"

    # Detect genre preference
    if cli_genre:
        genre = cli_genre.strip().lower()
    elif isinstance(raw_music, dict) and raw_music.get("genre"):
        genre = str(raw_music["genre"]).strip().lower()
    elif isinstance(raw_music, dict) and raw_music.get("file") and "ambient" in str(raw_music["file"]).lower():
        genre = "ambient"

    if genre not in GENRE_PRESETS:
        # Fallback to upbeat_jazz if unknown preset specified
        genre = "upbeat_jazz"

    preset = dict(GENRE_PRESETS[genre])
    cfg = dict(DEFAULT_MUSIC_CONFIG)
    cfg.update(preset)

    if isinstance(raw_music, bool):
        cfg["enabled"] = raw_music
    elif isinstance(raw_music, dict):
        cfg["enabled"] = raw_music.get("enabled", True)
        cfg["genre"] = genre
        for k in ["base_volume", "ducking_volume", "ducking_attack_ms", "ducking_release_ms",
                  "fade_in_ms", "fade_out_ms", "loop", "file"]:
            if k in raw_music:
                cfg[k] = raw_music[k]
    elif raw_music is not None:
        raise ValueError(f"Invalid 'music' configuration type in plan: {type(raw_music).__name__}")

    # CLI flag overrides
    if cli_bgm:
        cfg["enabled"] = True
        cfg["file"] = cli_bgm
    if cli_genre:
        cfg["genre"] = cli_genre

    # Validate volume ranges (safe range [0.0, 0.60] and ducking [0.0, 0.35])
    if not (0.0 <= cfg["base_volume"] <= 0.60):
        raise ValueError(f"base_volume {cfg['base_volume']} out of safe range [0.0, 0.60]")
    if not (0.0 <= cfg["ducking_volume"] <= 0.35):
        raise ValueError(f"ducking_volume {cfg['ducking_volume']} out of safe range [0.0, 0.35]")
    if cfg["ducking_volume"] > cfg["base_volume"]:
        raise ValueError(f"ducking_volume ({cfg['ducking_volume']}) cannot exceed base_volume ({cfg['base_volume']})")

    # Validate timing non-negative
    for k in ["ducking_attack_ms", "ducking_release_ms", "fade_in_ms", "fade_out_ms"]:
        if cfg[k] < 0:
            raise ValueError(f"{k} must be non-negative: {cfg[k]}")

    # Resolve default genre asset if enabled but file not explicitly set
    if cfg["enabled"] and not cfg["file"]:
        rel_target = preset.get("file_rel", os.path.join("resources", "audio", "music", "upbeat-jazz-v1.wav"))
        if skill_root:
            default_asset = os.path.join(skill_root, rel_target)
            if os.path.isfile(default_asset):
                cfg["file"] = default_asset
        if not cfg["file"]:
            here = os.path.dirname(os.path.abspath(__file__))
            candidate = os.path.abspath(os.path.join(here, "..", rel_target))
            if os.path.isfile(candidate):
                cfg["file"] = candidate

    # If enabled, verify file exists and enforce cryptographic hash integrity
    if cfg["enabled"]:
        if not cfg["file"]:
            raise ValueError(f"Background music is enabled ({cfg['genre']}) but audio file was not found.")
        if not os.path.isfile(cfg["file"]):
            raise FileNotFoundError(f"CRITICAL ASSET INTEGRITY FAILURE: Background music file not found: {cfg['file']}")

        # Enforce strict SHA-256 verification for official preset assets
        expected_sha = preset.get("expected_sha256")
        is_official_preset_asset = (
            cfg.get("genre") == "upbeat_jazz"
            and (os.path.basename(cfg["file"]) == "upbeat-jazz-v1.wav" or cfg["file"] == preset.get("file_rel"))
        )
        if expected_sha and is_official_preset_asset:
            actual_sha = compute_file_sha256(cfg["file"])
            if actual_sha != expected_sha:
                raise ValueError(
                    f"CRITICAL ASSET INTEGRITY FAILURE: Background music asset '{cfg['file']}' SHA-256 hash mismatch! "
                    f"Expected {expected_sha}, got {actual_sha}. "
                    f"Production halted to prevent compromised, corrupted, or non-compliant audio."
                )
            cfg["sha256_verified"] = True
            cfg["sha256"] = actual_sha

    return cfg


def load_audio_pcm(path: str, ffmpeg_bin: str, target_sr: int = SR) -> np.ndarray:
    """
    Decodes audio file to mono float32 array in [-1.0, 1.0] at target_sr via FFmpeg.
    """
    if not os.path.isfile(path):
        raise FileNotFoundError(f"Audio file not found: {path}")

    cmd = [
        ffmpeg_bin, "-v", "error", "-i", path,
        "-f", "s16le", "-ac", "1", "-ar", str(target_sr), "pipe:1"
    ]
    res = subprocess.run(cmd, capture_output=True, check=True)
    raw = res.stdout
    if len(raw) == 0:
        raise ValueError(f"Decoded zero bytes from audio file: {path}")

    pcm = np.frombuffer(raw, dtype=np.int16).astype(np.float32) / 32768.0
    return pcm


def loop_pcm(pcm: np.ndarray, target_samples: int, crossfade_ms: int = 1500, sample_rate: int = SR) -> np.ndarray:
    """
    Seamlessly loops PCM data with equal-power cosine crossfade until target_samples is reached.
    """
    if len(pcm) == 0:
        return np.zeros(target_samples, dtype=np.float32)

    if len(pcm) >= target_samples:
        return pcm[:target_samples].copy()

    out = np.zeros(target_samples, dtype=np.float32)
    xfade_s = int(crossfade_ms / 1000.0 * sample_rate)
    xfade_s = min(xfade_s, len(pcm) // 4)

    curr = 0
    iteration = 0
    while curr < target_samples:
        if iteration == 0:
            copy_len = min(len(pcm), target_samples)
            out[:copy_len] = pcm[:copy_len]
            curr = copy_len
        else:
            # Crossfade previous tail with new loop head
            if xfade_s > 0 and curr >= xfade_s:
                t = np.arange(xfade_s) / xfade_s
                fade_out = np.cos(0.5 * np.pi * t)
                fade_in = np.sin(0.5 * np.pi * t)

                overlap_start = curr - xfade_s
                out[overlap_start:curr] = out[overlap_start:curr] * fade_out + pcm[:xfade_s] * fade_in

                remain_pcm = pcm[xfade_s:]
                append_len = min(len(remain_pcm), target_samples - curr)
                if append_len > 0:
                    out[curr:curr + append_len] = remain_pcm[:append_len]
                curr += append_len
            else:
                append_len = min(len(pcm), target_samples - curr)
                out[curr:curr + append_len] = pcm[:append_len]
                curr += append_len
        iteration += 1

    return out


def build_ducking_envelope(
    total_samples: int,
    narration_intervals: List[Tuple[float, float]],
    sample_rate: int = SR,
    base_volume: float = 0.08,
    ducking_volume: float = 0.02,
    attack_ms: int = 250,
    release_ms: int = 600,
    fade_in_ms: int = 1200,
    fade_out_ms: int = 2500
) -> np.ndarray:
    """
    Constructs sample-accurate gain automation envelope.
    - Nominal level: base_volume
    - During speech: smoothly ducked to ducking_volume with raised cosine curves
    - Multiple overlapping speech segments take the minimum gain envelope safely
    - Master fade-in and fade-out applied at boundaries
    """
    env = np.full(total_samples, base_volume, dtype=np.float32)
    delta_vol = base_volume - ducking_volume

    att_samples = int(attack_ms / 1000.0 * sample_rate)
    rel_samples = int(release_ms / 1000.0 * sample_rate)

    for start_ms, end_ms in narration_intervals:
        s_idx = int(round(start_ms / 1000.0 * sample_rate))
        e_idx = int(round(end_ms / 1000.0 * sample_rate))

        if s_idx >= total_samples or e_idx <= 0:
            continue

        s_bounded = max(0, min(total_samples, s_idx))
        e_bounded = max(0, min(total_samples, e_idx))

        # 1. Attack phase: [s_idx - att_samples, s_idx]
        if att_samples > 0:
            att_start = max(0, s_idx - att_samples)
            if att_start < s_bounded:
                seg_len = s_bounded - att_start
                # Map to [0, 1] relative to attack window
                u = (np.arange(seg_len) + (att_start - (s_idx - att_samples))) / att_samples
                att_gain = ducking_volume + delta_vol * 0.5 * (1.0 + np.cos(np.pi * u))
                env[att_start:s_bounded] = np.minimum(env[att_start:s_bounded], att_gain.astype(np.float32))

        # 2. Fully ducked speech phase: [s_bounded, e_bounded]
        if e_bounded > s_bounded:
            env[s_bounded:e_bounded] = np.minimum(env[s_bounded:e_bounded], ducking_volume)

        # 3. Release phase: [e_idx, e_idx + rel_samples]
        if rel_samples > 0:
            rel_end = min(total_samples, e_idx + rel_samples)
            if rel_end > e_bounded:
                seg_len = rel_end - e_bounded
                u = (np.arange(seg_len) + (e_bounded - e_idx)) / rel_samples
                rel_gain = ducking_volume + delta_vol * 0.5 * (1.0 - np.cos(np.pi * u))
                env[e_bounded:rel_end] = np.minimum(env[e_bounded:rel_end], rel_gain.astype(np.float32))

    # Master Fade-In
    fi_samples = int(fade_in_ms / 1000.0 * sample_rate)
    if fi_samples > 0 and total_samples > 0:
        fi_bounded = min(total_samples, fi_samples)
        fi_curve = 0.5 * (1.0 - np.cos(np.pi * np.arange(fi_bounded) / fi_samples))
        env[:fi_bounded] *= fi_curve.astype(np.float32)

    # Master Fade-Out
    fo_samples = int(fade_out_ms / 1000.0 * sample_rate)
    if fo_samples > 0 and total_samples > 0:
        fo_bounded = min(total_samples, fo_samples)
        fo_start = total_samples - fo_bounded
        fo_curve = 0.5 * (1.0 + np.cos(np.pi * (np.arange(fo_bounded) + (fo_samples - fo_bounded)) / fo_samples))
        env[fo_start:] *= fo_curve.astype(np.float32)

    return env


def mix_bgm_track(
    bgm_pcm: np.ndarray,
    total_samples: int,
    narration_intervals: List[Tuple[float, float]],
    config: Dict[str, Any],
    sample_rate: int = SR
) -> Tuple[np.ndarray, np.ndarray]:
    """
    Renders the ducked background music track ready to be summed into master mix.
    Returns: (bgm_pcm_track, envelope_array)
    """
    if config.get("loop", True):
        looped = loop_pcm(bgm_pcm, total_samples, crossfade_ms=1500, sample_rate=sample_rate)
    else:
        looped = np.zeros(total_samples, dtype=np.float32)
        take_len = min(len(bgm_pcm), total_samples)
        looped[:take_len] = bgm_pcm[:take_len]

    envelope = build_ducking_envelope(
        total_samples=total_samples,
        narration_intervals=narration_intervals,
        sample_rate=sample_rate,
        base_volume=config.get("base_volume", 0.08),
        ducking_volume=config.get("ducking_volume", 0.02),
        attack_ms=config.get("ducking_attack_ms", 250),
        release_ms=config.get("ducking_release_ms", 600),
        fade_in_ms=config.get("fade_in_ms", 1200),
        fade_out_ms=config.get("fade_out_ms", 2500)
    )

    bgm_track = looped * envelope
    return bgm_track, envelope


def compute_audio_metrics(pcm: np.ndarray) -> Dict[str, Any]:
    """Calculate rigorous acoustic metrics on PCM float32 array."""
    if len(pcm) == 0:
        return {"peak": 0.0, "peak_dbfs": -100.0, "rms": 0.0, "rms_dbfs": -100.0, "clipping": False}

    peak = float(np.max(np.abs(pcm)))
    rms = float(np.sqrt(np.mean(pcm ** 2)))
    peak_dbfs = 20.0 * math.log10(peak) if peak > 1e-9 else -100.0
    rms_dbfs = 20.0 * math.log10(rms) if rms > 1e-9 else -100.0

    return {
        "peak": round(peak, 5),
        "peak_dbfs": round(peak_dbfs, 2),
        "rms": round(rms, 5),
        "rms_dbfs": round(rms_dbfs, 2),
        "has_nan": bool(np.isnan(pcm).any()),
        "has_inf": bool(np.isinf(pcm).any()),
        "clipping": peak >= 0.999
    }


def measure_bgm_acoustics(
    master_pcm: np.ndarray,
    bgm_track: np.ndarray,
    speech_intervals: List[Tuple[float, float]],
    sample_rate: int = SR
) -> Dict[str, Any]:
    """
    Empirically measure BGM contribution, ducking attenuation, and voice advantage
    using actual isolated track signals.
    """
    total_len = min(len(master_pcm), len(bgm_track))
    if total_len == 0:
        return {
            "bgm_present": False,
            "ducked_bgm_rms_dbfs": -100.0,
            "pause_bgm_rms_dbfs": -100.0,
            "voice_advantage_db": 0.0,
            "ducking_attenuation_db": 0.0,
            "audible": False
        }

    speech_mask = np.zeros(total_len, dtype=bool)
    for st_ms, en_ms in speech_intervals:
        s_idx = max(0, min(total_len, int(round(st_ms / 1000.0 * sample_rate))))
        e_idx = max(0, min(total_len, int(round(en_ms / 1000.0 * sample_rate))))
        if e_idx > s_idx:
            speech_mask[s_idx:e_idx] = True

    # Isolated BGM during speech vs during pause
    ducked_bgm = bgm_track[:total_len][speech_mask]
    pause_bgm = bgm_track[:total_len][~speech_mask]

    ducked_rms = float(np.sqrt(np.mean(ducked_bgm ** 2))) if len(ducked_bgm) > 0 else 0.0
    pause_rms = float(np.sqrt(np.mean(pause_bgm ** 2))) if len(pause_bgm) > 0 else 0.0

    ducked_dbfs = 20.0 * math.log10(ducked_rms) if ducked_rms > 1e-9 else -100.0
    pause_dbfs = 20.0 * math.log10(pause_rms) if pause_rms > 1e-9 else -100.0

    # Speech in master (which contains voice + ducked bgm)
    speech_master = master_pcm[:total_len][speech_mask]
    speech_rms = float(np.sqrt(np.mean(speech_master ** 2))) if len(speech_master) > 0 else 0.0
    speech_dbfs = 20.0 * math.log10(speech_rms) if speech_rms > 1e-9 else -100.0

    voice_adv = round(speech_dbfs - ducked_dbfs, 2) if ducked_rms > 1e-9 else 100.0
    duck_atten = round(pause_dbfs - ducked_dbfs, 2) if (pause_rms > 1e-9 and ducked_rms > 1e-9) else 0.0

    # Audible criteria: BGM peak/rms must be detectable and audible above noise floor
    # Threshold: ducked BGM >= -45 dBFS and pause BGM >= -35 dBFS
    is_audible = (pause_dbfs >= -35.0) and (ducked_dbfs >= -45.0) and (duck_atten >= 4.0)

    return {
        "bgm_present": bool(pause_rms > 1e-6),
        "ducked_bgm_rms_dbfs": round(ducked_dbfs, 2),
        "pause_bgm_rms_dbfs": round(pause_dbfs, 2),
        "speech_master_rms_dbfs": round(speech_dbfs, 2),
        "measured_voice_advantage_db": voice_adv,
        "measured_ducking_attenuation_db": duck_atten,
        "audible": is_audible
    }

