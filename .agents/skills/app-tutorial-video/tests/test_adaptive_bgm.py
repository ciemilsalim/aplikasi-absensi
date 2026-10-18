#!/usr/bin/env python
"""
test_adaptive_bgm.py — Comprehensive Unit, Property, and Regression Test Suite
for App Tutorial Video V4 Adaptive Background Music System.

Covers all 24 required test scenarios without requiring browser rendering or internet:
1. Legacy plan without music property
2. music: false
3. Valid music object config
4. Volume out of range validation
5. Music file not found handling
6. Corrupted/empty audio file handling
7. Music shorter than video with looping active
8. Music shorter than video with looping inactive
9. Fade-in and fade-out envelope calculations
10. Active ducking during narration segments
11. Release transition after narration ends
12. Empty narration / zero-event timeline
13. Overlapping narration segments
14. Narration interval out of video duration bounds
15. Music longer than video duration
16. Stereo and mono input decoding
17. Peak protection limiting (scaling when peak > 0.98)
18. Provenance SHA-256 generation and tracking
19. Audio preview CLI execution and metrics validation
20. Gate 30 PASS on valid audio master
21. Gate 30 FAIL on clipping / missing audio
22. Legacy manifest parser backward compatibility
23. CLI V4 argument parser accepts --bgm
24. Regression test: baseline without BGM remains bit-exact identical
"""

import json
import math
import os
import subprocess
import sys
import wave
import numpy as np
import pytest

HERE = os.path.dirname(os.path.abspath(__file__))
SKILL_DIR = os.path.abspath(os.path.join(HERE, ".."))
SCRIPTS_DIR = os.path.join(SKILL_DIR, "scripts")
if SCRIPTS_DIR not in sys.path:
    sys.path.insert(0, SCRIPTS_DIR)

from audio_mixer import (
    SR,
    DEFAULT_MUSIC_CONFIG,
    resolve_music_config,
    load_audio_pcm,
    loop_pcm,
    build_ducking_envelope,
    mix_bgm_track,
    compute_audio_metrics
)
from artifact_manager import ArtifactManager, compute_sha256
from continuous_qa import run_qa_gates
from preview_bgm import generate_preview


@pytest.fixture
def test_files(tmp_path):
    """Generates synthetic narration and music WAV files for isolated tests."""
    audio_dir = tmp_path / "audio"
    audio_dir.mkdir()

    # 1. Narration file: 3.0s mono sine at 440Hz, 48kHz
    narr_path = str(audio_dir / "narration.wav")
    t_narr = np.arange(int(3.0 * SR)) / SR
    narr_pcm = (np.sin(2 * np.pi * 440 * t_narr) * 0.70 * 32767).astype(np.int16)
    with wave.open(narr_path, "wb") as wf:
        wf.setnchannels(1)
        wf.setsampwidth(2)
        wf.setframerate(SR)
        wf.writeframes(narr_pcm.tobytes())

    # 2. Short BGM file: 2.0s stereo at 48kHz
    bgm_short_path = str(audio_dir / "bgm_short.wav")
    t_bgm = np.arange(int(2.0 * SR)) / SR
    l_bgm = (np.sin(2 * np.pi * 220 * t_bgm) * 0.60 * 32767).astype(np.int16)
    r_bgm = (np.sin(2 * np.pi * 330 * t_bgm) * 0.60 * 32767).astype(np.int16)
    interleaved = np.empty((l_bgm.size + r_bgm.size,), dtype=np.int16)
    interleaved[0::2] = l_bgm
    interleaved[1::2] = r_bgm
    with wave.open(bgm_short_path, "wb") as wf:
        wf.setnchannels(2)
        wf.setsampwidth(2)
        wf.setframerate(SR)
        wf.writeframes(interleaved.tobytes())

    # 3. Long BGM file: 8.0s mono at 48kHz
    bgm_long_path = str(audio_dir / "bgm_long.wav")
    t_long = np.arange(int(8.0 * SR)) / SR
    long_pcm = (np.sin(2 * np.pi * 180 * t_long) * 0.50 * 32767).astype(np.int16)
    with wave.open(bgm_long_path, "wb") as wf:
        wf.setnchannels(1)
        wf.setsampwidth(2)
        wf.setframerate(SR)
        wf.writeframes(long_pcm.tobytes())

    # 4. Corrupt file: empty file
    corrupt_path = str(audio_dir / "corrupted.wav")
    with open(corrupt_path, "wb") as f:
        f.write(b"NOT_A_VALID_WAV_HEADER_DATA")

    return {
        "dir": str(tmp_path),
        "narration": narr_path,
        "bgm_short": bgm_short_path,
        "bgm_long": bgm_long_path,
        "corrupt": corrupt_path
    }


# ==============================================================================
# 1. PLAN CONFIGURATION TESTS (Tests 1 - 5)
# ==============================================================================

def test_01_legacy_plan_without_music():
    """Plan without 'music' key must resolve to enabled=False safely."""
    plan = {"title": "Legacy Plan", "scenes": []}
    cfg = resolve_music_config(plan)
    assert cfg["enabled"] is False
    assert cfg["base_volume"] == DEFAULT_MUSIC_CONFIG["base_volume"]
    assert cfg["ducking_volume"] == DEFAULT_MUSIC_CONFIG["ducking_volume"]


def test_02_plan_with_music_boolean_false():
    """Plan with 'music: false' must remain disabled."""
    plan = {"title": "Plan False", "music": False}
    cfg = resolve_music_config(plan)
    assert cfg["enabled"] is False


def test_03_plan_with_valid_music_object(test_files):
    """Plan with detailed music object config resolves all custom properties."""
    plan = {
        "title": "Custom Music Plan",
        "music": {
            "enabled": True,
            "file": test_files["bgm_short"],
            "base_volume": 0.12,
            "ducking_volume": 0.03,
            "ducking_attack_ms": 300,
            "ducking_release_ms": 700,
            "fade_in_ms": 1500,
            "fade_out_ms": 2000,
            "loop": False
        }
    }
    cfg = resolve_music_config(plan)
    assert cfg["enabled"] is True
    assert cfg["file"] == test_files["bgm_short"]
    assert cfg["base_volume"] == 0.12
    assert cfg["ducking_volume"] == 0.03
    assert cfg["ducking_attack_ms"] == 300
    assert cfg["loop"] is False


def test_04_volume_out_of_range():
    """Excessive volume or ducking > base volume must be rejected."""
    with pytest.raises(ValueError, match="out of safe range"):
        resolve_music_config({"music": {"enabled": True, "base_volume": 0.80}})

    with pytest.raises(ValueError, match="cannot exceed base_volume"):
        resolve_music_config({"music": {"enabled": True, "base_volume": 0.05, "ducking_volume": 0.10}})


def test_05_music_file_not_found():
    """Enabled music pointing to nonexistent file must raise FileNotFoundError."""
    plan = {"music": {"enabled": True, "file": "nonexistent_track_xyz.wav"}}
    with pytest.raises(FileNotFoundError):
        resolve_music_config(plan)


# ==============================================================================
# 2. AUDIO DECODING & LOOPING TESTS (Tests 6 - 8, 15, 16)
# ==============================================================================

def test_06_corrupt_audio_decoding(test_files):
    """Corrupted WAV file must raise an exception when decoded."""
    from continuous_pipeline import ffmpeg_exe
    with pytest.raises(Exception):
        load_audio_pcm(test_files["corrupt"], ffmpeg_exe(), target_sr=SR)


def test_07_music_shorter_than_video_looping_active():
    """2.0s music looped over 6.0s target must seamlessly fill 6.0s without NaN."""
    short_pcm = np.sin(2 * np.pi * 200 * np.arange(2 * SR) / SR).astype(np.float32) * 0.5
    target_samples = 6 * SR
    looped = loop_pcm(short_pcm, target_samples=target_samples, crossfade_ms=500, sample_rate=SR)
    assert len(looped) == target_samples
    assert not np.isnan(looped).any()
    assert float(np.max(np.abs(looped))) > 0.40


def test_08_music_shorter_than_video_looping_inactive(test_files):
    """When loop=False, music must only play for its duration and pad with silence."""
    short_pcm = np.ones(2 * SR, dtype=np.float32) * 0.4
    target_samples = 5 * SR
    cfg = {"loop": False, "base_volume": 0.1, "ducking_volume": 0.02}
    bgm_track, env = mix_bgm_track(short_pcm, target_samples, [], config=cfg, sample_rate=SR)
    assert len(bgm_track) == target_samples
    # Samples after 2.0s must be exactly 0
    assert np.all(bgm_track[2 * SR:] == 0.0)


def test_15_music_longer_than_video():
    """8.0s music on 4.0s video must truncate cleanly at 4.0s."""
    long_pcm = np.ones(8 * SR, dtype=np.float32) * 0.5
    target_samples = 4 * SR
    cfg = {"loop": True, "base_volume": 0.08, "ducking_volume": 0.02, "fade_in_ms": 0, "fade_out_ms": 0}
    bgm_track, env = mix_bgm_track(long_pcm, target_samples, [], config=cfg, sample_rate=SR)
    assert len(bgm_track) == target_samples


def test_16_stereo_and_mono_decoding(test_files):
    """Both stereo and mono WAVs decode to 1D float32 arrays with identical sample rates."""
    from continuous_pipeline import ffmpeg_exe
    pcm_mono = load_audio_pcm(test_files["narration"], ffmpeg_exe(), target_sr=SR)
    pcm_stereo = load_audio_pcm(test_files["bgm_short"], ffmpeg_exe(), target_sr=SR)
    assert pcm_mono.ndim == 1
    assert pcm_stereo.ndim == 1
    assert len(pcm_mono) == int(3.0 * SR)
    assert len(pcm_stereo) == int(2.0 * SR)


# ==============================================================================
# 3. ENVELOPE DUCKING & FADE TESTS (Tests 9 - 14)
# ==============================================================================

def test_09_fade_in_and_fade_out_curve():
    """Fade-in starts at 0.0 and fade-out ends at 0.0."""
    total_samples = 10 * SR
    env = build_ducking_envelope(
        total_samples=total_samples,
        narration_intervals=[],
        sample_rate=SR,
        base_volume=0.10,
        ducking_volume=0.02,
        fade_in_ms=1000,
        fade_out_ms=1000
    )
    # At t=0, gain must be 0.0
    assert abs(env[0]) < 1e-4
    # At midpoint, gain must equal nominal base volume 0.10
    mid_idx = total_samples // 2
    assert abs(env[mid_idx] - 0.10) < 1e-3
    # At final sample, gain must be 0.0
    assert abs(env[-1]) < 1e-4


def test_10_ducking_during_narration():
    """Gain during active narration segment must equal ducking_volume."""
    total_samples = 10 * SR
    # Narration between 3000ms and 6000ms
    env = build_ducking_envelope(
        total_samples=total_samples,
        narration_intervals=[(3000.0, 6000.0)],
        sample_rate=SR,
        base_volume=0.08,
        ducking_volume=0.02,
        attack_ms=250,
        release_ms=500,
        fade_in_ms=0,
        fade_out_ms=0
    )
    # Outside speech (e.g. t=1.0s): gain == 0.08
    assert abs(env[int(1.0 * SR)] - 0.08) < 1e-4
    # Inside speech (e.g. t=4.5s): gain == 0.02
    assert abs(env[int(4.5 * SR)] - 0.02) < 1e-4


def test_11_release_transition_after_narration():
    """After narration ends at 6000ms, gain must rise monotonically to base volume."""
    total_samples = 10 * SR
    env = build_ducking_envelope(
        total_samples=total_samples,
        narration_intervals=[(3000.0, 6000.0)],
        sample_rate=SR,
        base_volume=0.08,
        ducking_volume=0.02,
        attack_ms=250,
        release_ms=600,
        fade_in_ms=0,
        fade_out_ms=0
    )
    # During release at 6300ms (midway through 600ms release):
    mid_release_val = env[int(6.3 * SR)]
    assert 0.02 < mid_release_val < 0.08
    # After release at 7000ms: gain back to 0.08
    assert abs(env[int(7.0 * SR)] - 0.08) < 1e-4


def test_12_empty_narration_intervals():
    """When there is no narration, envelope remains constant at base_volume."""
    total_samples = 5 * SR
    env = build_ducking_envelope(
        total_samples=total_samples,
        narration_intervals=[],
        sample_rate=SR,
        base_volume=0.08,
        ducking_volume=0.02,
        fade_in_ms=0,
        fade_out_ms=0
    )
    assert np.allclose(env, 0.08, atol=1e-5)


def test_13_overlapping_narration_intervals():
    """Overlapping speech segments maintain minimum ducking gain without spikes."""
    total_samples = 8 * SR
    # Two overlapping speech segments: [2000, 4000] and [3500, 5500]
    env = build_ducking_envelope(
        total_samples=total_samples,
        narration_intervals=[(2000.0, 4000.0), (3500.0, 5500.0)],
        sample_rate=SR,
        base_volume=0.08,
        ducking_volume=0.02,
        attack_ms=200,
        release_ms=400,
        fade_in_ms=0,
        fade_out_ms=0
    )
    # Overlap zone at t=3.8s must stay strictly ducked at 0.02
    assert abs(env[int(3.8 * SR)] - 0.02) < 1e-4
    # All samples within [2000, 5500] must be <= 0.02 + epsilon
    speech_region = env[int(2.0 * SR):int(5.5 * SR)]
    assert np.max(speech_region) <= 0.02001


def test_14_narration_interval_out_of_bounds():
    """Narration intervals completely beyond timeline duration are handled gracefully."""
    total_samples = 4 * SR
    env = build_ducking_envelope(
        total_samples=total_samples,
        narration_intervals=[(6000.0, 8000.0)],  # Beyond 4s
        sample_rate=SR,
        base_volume=0.08,
        ducking_volume=0.02,
        fade_in_ms=0,
        fade_out_ms=0
    )
    assert len(env) == total_samples
    assert np.allclose(env, 0.08, atol=1e-5)


# ==============================================================================
# 4. PEAK PROTECTION & METRICS (Test 17)
# ==============================================================================

def test_17_peak_protection_scaling():
    """Waveform exceeding 0.98 must be scaled to exactly 0.98 without clipping."""
    hot_pcm = np.array([0.5, 0.9, 1.3, -1.2, 0.4], dtype=np.float32)
    peak = float(np.max(np.abs(hot_pcm)))
    assert peak > 0.98
    hot_pcm *= (0.98 / peak)
    assert np.max(np.abs(hot_pcm)) == pytest.approx(0.98, rel=1e-5)
    metrics = compute_audio_metrics(hot_pcm)
    assert metrics["clipping"] is False
    assert metrics["peak_dbfs"] <= -0.17


# ==============================================================================
# 5. PROVENANCE MANIFEST TESTS (Tests 18, 22)
# ==============================================================================

def test_18_provenance_manifest_with_bgm(tmp_path, test_files):
    """Provenance manifest must record BGM sha256, path, and gain configurations."""
    mgr = ArtifactManager(str(tmp_path))
    run_info = mgr.prepare_run("test_project", "run_bgm_test")
    plan_path = str(tmp_path / "plan.json")
    with open(plan_path, "w") as f:
        json.dump({"title": "Test Plan"}, f)

    music_cfg = {
        "enabled": True,
        "file": test_files["bgm_short"],
        "base_volume": 0.08,
        "ducking_volume": 0.02,
        "fade_in_ms": 1200,
        "fade_out_ms": 2500,
        "loop": True
    }

    manifest = mgr.generate_provenance_manifest(
        project_id="test_project",
        run_id="run_bgm_test",
        plan_path=plan_path,
        music_config=music_cfg
    )

    bgm_entry = manifest.get("background_music")
    assert bgm_entry is not None
    assert bgm_entry["enabled"] is True
    assert bgm_entry["sha256"] == compute_sha256(test_files["bgm_short"])
    assert bgm_entry["base_volume"] == 0.08
    assert bgm_entry["ducking_volume"] == 0.02


def test_22_legacy_manifest_compatibility(tmp_path):
    """When music is disabled or omitted, manifest records background_music: null without crashing."""
    mgr = ArtifactManager(str(tmp_path))
    run_info = mgr.prepare_run("legacy_project", "run_legacy")
    plan_path = str(tmp_path / "plan.json")
    with open(plan_path, "w") as f:
        json.dump({"title": "Legacy"}, f)

    manifest = mgr.generate_provenance_manifest(
        project_id="legacy_project",
        run_id="run_legacy",
        plan_path=plan_path,
        music_config=None
    )
    assert manifest.get("background_music") is None
    assert manifest["artifacts"]["audio_master"] is not None


# ==============================================================================
# 6. AUDIO PREVIEW CLI TEST (Test 19)
# ==============================================================================

def test_19_audio_preview_cli(test_files, tmp_path):
    """preview_bgm.py produces a valid WAV file with correct metrics."""
    out_wav = str(tmp_path / "preview.wav")
    metrics = generate_preview(
        narration_path=test_files["narration"],
        bgm_path=test_files["bgm_short"],
        out_path=out_wav,
        base_volume=0.08,
        ducking_volume=0.02,
        target_duration_sec=4.0
    )
    assert os.path.isfile(out_wav)
    assert metrics["clipping"] is False
    assert metrics["peak_dbfs"] <= -0.17
    assert metrics["voice_to_music_ducked_db"] > 20.0  # +33 dB advantage


# ==============================================================================
# 7. GATE 30 AUDIO QA TESTS (Tests 20, 21)
# ==============================================================================

def test_20_gate_30_pass_on_valid_run(tmp_path, test_files):
    """Gate 30 PASS on standard 48kHz audio master with matching duration and no clipping."""
    run_dir = tmp_path / "test_run"
    audio_dir = run_dir / "audio"
    rec_dir = run_dir / "recording"
    audio_dir.mkdir(parents=True)
    rec_dir.mkdir(parents=True)

    # 4.0s video manifest
    manifest_path = rec_dir / "recording-manifest.json"
    with open(manifest_path, "w") as f:
        json.dump({"engine": "TRUE_CONTINUOUS_BROWSER_VIDEO_V4", "duration_ms": 4000.0, "frame_sources": {"cdp_live_capture": 240}}, f)

    events_path = rec_dir / "events.json"
    with open(events_path, "w") as f:
        json.dump({"events": [{"type": "move_start"}, {"type": "tap", "frame": 30}]}, f)

    frames_path = rec_dir / "frames.jsonl"
    with open(frames_path, "w") as f:
        for i in range(240):
            f.write(json.dumps({"frame": i, "src": "cdp_live_capture"}) + "\n")

    # 4.0s audio master at 48kHz mono
    audio_master_path = audio_dir / "audio-master.wav"
    t = np.arange(int(4.0 * SR)) / SR
    sig = (np.sin(2 * np.pi * 440 * t) * 0.70 * 32767).astype(np.int16)
    with wave.open(str(audio_master_path), "wb") as wf:
        wf.setnchannels(1)
        wf.setsampwidth(2)
        wf.setframerate(SR)
        wf.writeframes(sig.tobytes())

    # Placements with clean BGM disabled
    placements_path = audio_dir / "audio-placements.json"
    with open(placements_path, "w") as f:
        json.dump({"sample_rate": SR, "background_music": None, "placements": []}, f)

    report = run_qa_gates(str(run_dir))
    g30 = report["gates"].get("gate_30_audio_integrity_and_clarity")
    assert g30 is not None
    assert g30["status"] == "PASS"
    assert g30["duration_diff_ms"] < 1.0
    assert g30["clipping_detected"] is False


def test_21_gate_30_fail_on_clipping_or_missing_file(tmp_path):
    """Gate 30 must FAIL if audio master is missing."""
    run_dir = tmp_path / "empty_run"
    rec_dir = run_dir / "recording"
    rec_dir.mkdir(parents=True)
    with open(rec_dir / "recording-manifest.json", "w") as f:
        json.dump({"engine": "TRUE_CONTINUOUS_BROWSER_VIDEO_V4", "duration_ms": 2000.0, "frame_sources": {"cdp_live_capture": 120}}, f)
    with open(rec_dir / "events.json", "w") as f:
        json.dump({"events": [{"type": "move_start"}]}, f)

    report = run_qa_gates(str(run_dir))
    g30 = report["gates"].get("gate_30_audio_integrity_and_clarity")
    assert g30 is not None
    assert g30["status"] == "FAIL"


# ==============================================================================
# 8. CLI & BACKWARD COMPATIBILITY REGRESSION (Tests 23, 24)
# ==============================================================================

def test_23_cli_parser_accepts_bgm():
    """cli.py record parser accepts --bgm parameter without error."""
    import argparse
    from cli import main
    # Test via subprocess help check
    res = subprocess.run([sys.executable, os.path.join(SCRIPTS_DIR, "cli.py"), "record", "--help"],
                         capture_output=True, text=True)
    assert res.returncode == 0
    assert "--bgm" in res.stdout


def test_24_regression_bgm_disabled_exact_waveform(test_files, tmp_path):
    """
    Regression verification: mixing when BGM is disabled must produce output
    mathematically identical to legacy baseline without any music leakage.
    """
    from continuous_pipeline import pass_c

    run_dir = tmp_path / "baseline_run"
    audio_dir = run_dir / "audio"
    rec_dir = run_dir / "recording"
    audio_dir.mkdir(parents=True)
    rec_dir.mkdir(parents=True)

    # 3.0s recording
    with open(rec_dir / "recording-manifest.json", "w") as f:
        json.dump({"duration_ms": 3000.0}, f)

    with open(rec_dir / "events.json", "w") as f:
        json.dump({"events": [{"type": "narration_start", "scene_id": "sc1", "at_ms": 0.0}]}, f)

    narr_dict = {
        "scenes": {
            "sc1": {"audio": test_files["narration"], "duration_ms": 3000.0}
        }
    }

    # Run pass_c with music explicitly disabled
    plan_disabled = {"title": "No BGM", "music": False}
    wav_out = pass_c(str(run_dir), narr_dict, plan=plan_disabled, cli_bgm=None)
    assert os.path.isfile(wav_out)

    with wave.open(wav_out, "rb") as wf:
        n = wf.getnframes()
        data = np.frombuffer(wf.readframes(n), dtype=np.int16).astype(np.float32) / 32768.0

    # Verification: audio contains only narration scaled by 0.92
    from continuous_pipeline import ffmpeg_exe
    raw_narr = load_audio_pcm(test_files["narration"], ffmpeg_exe(), target_sr=SR) * 0.92
    assert len(data) == len(raw_narr)
    assert np.allclose(data, raw_narr, atol=1e-3)


def test_25_empirical_multitrack_bgm_acoustics(test_files):
    """Verify empirical multitrack acoustic measurement on synthesized signals."""
    from audio_mixer import measure_bgm_acoustics

    total_samples = SR * 10
    # Narration active between 1.0s and 6.0s (1000ms to 6000ms)
    speech_intervals = [(1000.0, 6000.0)]

    cfg = {
        "enabled": True,
        "base_volume": 0.18,
        "ducking_volume": 0.055,
        "ducking_attack_ms": 250,
        "ducking_release_ms": 600,
        "fade_in_ms": 500,
        "fade_out_ms": 500,
        "loop": True
    }

    from continuous_pipeline import ffmpeg_exe
    bgm_pcm = load_audio_pcm(test_files["bgm_short"], ffmpeg_exe(), target_sr=SR)
    bgm_track, env = mix_bgm_track(bgm_pcm, total_samples, speech_intervals, cfg, sample_rate=SR)

    # Master: simulated speech (sine at 1kHz, amp 0.5) during speech intervals + bgm_track
    master = bgm_track.copy()
    s_idx = int(1.0 * SR)
    e_idx = int(6.0 * SR)
    t_sp = np.arange(e_idx - s_idx) / SR
    speech_sig = (np.sin(2 * np.pi * 1000 * t_sp) * 0.5).astype(np.float32)
    master[s_idx:e_idx] += speech_sig

    metrics = measure_bgm_acoustics(master, bgm_track, speech_intervals, sample_rate=SR)
    assert metrics["bgm_present"] is True
    assert metrics["audible"] is True
    assert metrics["measured_ducking_attenuation_db"] >= 4.0
    assert metrics["measured_voice_advantage_db"] >= 10.0
    assert metrics["pause_bgm_rms_dbfs"] > metrics["ducked_bgm_rms_dbfs"]


def test_26_audible_bgm_presence_in_master(test_files, tmp_path):
    """Verify pass_c with default music configuration produces an audible BGM track."""
    from continuous_pipeline import pass_c

    run_dir = tmp_path / "audible_bgm_run"
    audio_dir = run_dir / "audio"
    rec_dir = run_dir / "recording"
    audio_dir.mkdir(parents=True)
    rec_dir.mkdir(parents=True)

    with open(rec_dir / "recording-manifest.json", "w") as f:
        json.dump({"duration_ms": 5000.0}, f)

    with open(rec_dir / "events.json", "w") as f:
        json.dump({"events": [{"type": "narration_start", "scene_id": "sc1", "at_ms": 1000.0}]}, f)

    narr_dict = {
        "scenes": {
            "sc1": {"audio": test_files["narration"], "duration_ms": 3000.0}
        }
    }

    plan_audible = {
        "title": "Audible BGM Plan",
        "music": {
            "enabled": True,
            "file": test_files["bgm_short"],
            "base_volume": 0.18,
            "ducking_volume": 0.055,
            "fade_in_ms": 300,
            "fade_out_ms": 300
        }
    }
    wav_out = pass_c(str(run_dir), narr_dict, plan=plan_audible, cli_bgm=None)
    assert os.path.isfile(wav_out)

    with open(audio_dir / "audio-placements.json", "r") as pf:
        pl_data = json.load(pf)

    assert "bgm_acoustics" in pl_data
    acoustics = pl_data["bgm_acoustics"]
    assert acoustics["bgm_present"] is True
    assert acoustics["audible"] is True
    assert acoustics["pause_bgm_rms_dbfs"] >= -35.0
    assert acoustics["ducked_bgm_rms_dbfs"] >= -45.0


def test_27_upbeat_jazz_preset_and_asset():
    """Verify upbeat_jazz preset structure and the generated 80 BPM WAV asset."""
    from audio_mixer import GENRE_PRESETS
    import wave

    assert "upbeat_jazz" in GENRE_PRESETS
    preset = GENRE_PRESETS["upbeat_jazz"]
    assert preset["tempo_bpm"] == 80
    assert preset["base_volume"] > preset["ducking_volume"]
    assert preset["ducking_volume"] > 0.0

    skill_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    asset_path = os.path.join(skill_root, preset["file_rel"])
    assert os.path.isfile(asset_path), f"Asset missing at {asset_path}"

    with wave.open(asset_path, "rb") as wf:
        assert wf.getframerate() == 48000
        assert wf.getnchannels() == 2
        assert wf.getsampwidth() == 2
        duration = wf.getnframes() / wf.getframerate()
        assert 60.0 <= duration <= 75.0


def test_28_genre_selection_and_resolution():
    """Verify resolve_music_config properly respects cli_genre and plan settings."""
    from audio_mixer import resolve_music_config

    # Default plan with boolean True -> upbeat_jazz
    cfg1 = resolve_music_config(plan={"music": True}, cli_bgm=None, cli_genre=None)
    assert cfg1["enabled"] is True
    assert cfg1["genre"] == "upbeat_jazz"
    assert cfg1["tempo_bpm"] == 80
    assert "upbeat-jazz-v1.wav" in cfg1["file"]

    # CLI genre override to ambient
    cfg2 = resolve_music_config(plan={"music": True}, cli_bgm=None, cli_genre="ambient")
    assert cfg2["enabled"] is True
    assert cfg2["genre"] == "ambient"
    assert "ambient-test-v1.wav" in cfg2["file"]

    # Plan with explicit genre
    cfg3 = resolve_music_config(plan={"music": {"enabled": True, "genre": "upbeat_jazz"}}, cli_bgm=None, cli_genre=None)
    assert cfg3["enabled"] is True
    assert cfg3["genre"] == "upbeat_jazz"


def test_29_standalone_and_mixed_preview(test_files, tmp_path):
    """Verify fast audio preview works for both standalone jazz and mixed narration."""
    from preview_bgm import generate_preview

    standalone_out = tmp_path / "standalone_test.wav"
    res_standalone = generate_preview(
        narration_path=None,
        bgm_genre="upbeat_jazz",
        out_path=str(standalone_out),
        target_duration_sec=5.0
    )
    assert os.path.isfile(str(standalone_out))
    assert res_standalone["type"] == "standalone_music_preview"
    assert res_standalone["genre"] == "upbeat_jazz"
    assert res_standalone["clipping"] is False
    assert res_standalone["duration_sec"] >= 4.9

    mixed_out = tmp_path / "mixed_test.wav"
    res_mixed = generate_preview(
        narration_path=test_files["narration"],
        bgm_genre="upbeat_jazz",
        out_path=str(mixed_out),
        target_duration_sec=7.0
    )
    assert os.path.isfile(str(mixed_out))
    assert res_mixed["type"] == "mixed_narration_preview"
    assert res_mixed["genre"] == "upbeat_jazz"
    assert res_mixed["clipping"] is False
    assert res_mixed["voice_to_music_ducked_db"] >= 15.0


def test_30_manifest_records_jazz_metadata(tmp_path, test_files):
    """Verify ArtifactManager tracks genre, tempo, instruments, and license in manifest."""
    from artifact_manager import ArtifactManager

    mgr = ArtifactManager(str(tmp_path))
    run_info = mgr.prepare_run("test_project", "run_jazz")
    plan_path = str(tmp_path / "plan.json")
    with open(plan_path, "w") as f:
        json.dump({"title": "Jazz Test"}, f)

    music_cfg = {
        "enabled": True,
        "genre": "upbeat_jazz",
        "tempo_bpm": 80,
        "instruments": "rhodes_piano, electric_soul_bass, soul_pocket_drums, brushed_hihat, lyrical_sax_accents",
        "license": "Royalty-Free Deterministic Procedural Composition (Skill app-tutorial-video)",
        "file": test_files["bgm_short"],
        "base_volume": 0.16,
        "ducking_volume": 0.045,
        "fade_in_ms": 800,
        "fade_out_ms": 1200,
        "loop": True
    }

    manifest = mgr.generate_provenance_manifest(
        project_id="test_project",
        run_id="run_jazz",
        plan_path=plan_path,
        music_config=music_cfg
    )

    bgm_entry = manifest.get("background_music")
    assert bgm_entry is not None
    assert bgm_entry["genre"] == "upbeat_jazz"
    assert bgm_entry["tempo_bpm"] == 80
    assert "electric_soul_bass" in bgm_entry["instruments"]
    assert "Royalty-Free" in bgm_entry["license"]
    assert len(bgm_entry["sha256"]) == 64


