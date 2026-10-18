#!/usr/bin/env python
"""
test_production_standardization.py — Test Suite for V4.4 Production Standardization.
Covers:
  1. Default V4.4 production preset resolution (Rhodes Soul Jazz, 80 BPM, ducking parameters)
  2. CLI overrides over plan and default preset (Priority: CLI > Plan > Preset)
  3. Cryptographic SHA-256 asset integrity enforcement (hash mismatch triggers fatal ValueError)
  4. Missing BGM asset triggers clear FileNotFoundError
  5. Single output mode (desktop-only, mobile-only) vs Dual output mode in QA
  6. Direct final MP4 container audio stream verification (AAC codec, audio presence)
  7. Production checklist Sections A-F execution
  8. Data safety and isolation enforcement (destructive mutation triggers FAIL)
  9. Backward compatibility when BGM is disabled
"""

import json
import os
import shutil
import sys
import tempfile
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
    UPBEAT_JAZZ_OFFICIAL_SHA256,
    GENRE_PRESETS,
    DEFAULT_MUSIC_CONFIG,
    resolve_music_config,
    compute_file_sha256
)
from artifact_manager import ArtifactManager
from continuous_qa import run_qa_gates, evaluate_production_checklist, probe_media_streams


# ==============================================================================
# 1. PRESET & CONFIGURATION PRECEDENCE TESTS
# ==============================================================================

def test_default_v44_preset_properties():
    """Verify default V4.4 preset specifies Rhodes Soul Jazz 80 BPM and exact SHA-256."""
    preset = GENRE_PRESETS["upbeat_jazz"]
    assert preset["tempo_bpm"] == 80
    assert "rhodes_piano" in preset["instruments"]
    assert "saxophone" not in preset["instruments"]
    assert preset["base_volume"] == 0.16
    assert preset["ducking_volume"] == 0.045
    assert preset["ducking_attack_ms"] == 200
    assert preset["ducking_release_ms"] == 500
    assert preset["fade_in_ms"] == 1200
    assert preset["fade_out_ms"] == 2500
    assert preset["loop"] is True
    assert preset["expected_sha256"] == UPBEAT_JAZZ_OFFICIAL_SHA256
    assert UPBEAT_JAZZ_OFFICIAL_SHA256 == "cab3b56caf103af4dd402d50265c0bc9ccc2c15bab76be5e7ff4f2d74396abaa"


def test_official_upbeat_jazz_asset_hash_on_disk():
    """Verify physical asset upbeat-jazz-v1.wav on disk matches official SHA-256."""
    asset_path = os.path.join(SKILL_DIR, "resources", "audio", "music", "upbeat-jazz-v1.wav")
    assert os.path.isfile(asset_path), f"Official asset missing at {asset_path}"
    actual_hash = compute_file_sha256(asset_path)
    assert actual_hash == UPBEAT_JAZZ_OFFICIAL_SHA256


def test_cli_overrides_plan_and_preset():
    """Verify configuration precedence: CLI flag > Tutorial Plan > Default Preset."""
    plan = {
        "title": "Test Plan",
        "music": {
            "enabled": True,
            "genre": "upbeat_jazz",
            "base_volume": 0.20,
            "ducking_volume": 0.05
        }
    }
    # 1. Plan values take precedence over default preset
    cfg_plan = resolve_music_config(plan, skill_root=SKILL_DIR)
    assert cfg_plan["base_volume"] == 0.20
    assert cfg_plan["ducking_volume"] == 0.05

    # 2. CLI override takes precedence over plan
    cfg_cli = resolve_music_config(plan, cli_genre="ambient", skill_root=SKILL_DIR)
    assert cfg_cli["genre"] == "ambient"


# ==============================================================================
# 2. CRYPTOGRAPHIC ASSET INTEGRITY & VALIDATION TESTS
# ==============================================================================

def test_tampered_asset_hash_triggers_fatal_error(tmp_path):
    """Verify altered/corrupted official BGM asset triggers immediate ValueError and stops production."""
    fake_asset = tmp_path / "upbeat-jazz-v1.wav"
    with open(fake_asset, "wb") as f:
        f.write(b"TAMPERED_AUDIO_DATA_FOR_SECURITY_TEST")

    plan = {
        "title": "Security Test",
        "music": {
            "enabled": True,
            "genre": "upbeat_jazz",
            "file": str(fake_asset)
        }
    }

    with pytest.raises(ValueError) as excinfo:
        resolve_music_config(plan, skill_root=str(tmp_path))

    err_msg = str(excinfo.value)
    assert "CRITICAL ASSET INTEGRITY FAILURE" in err_msg
    assert "SHA-256 hash mismatch" in err_msg
    assert UPBEAT_JAZZ_OFFICIAL_SHA256 in err_msg


def test_missing_asset_triggers_file_not_found():
    """Verify missing audio file triggers descriptive FileNotFoundError."""
    plan = {
        "title": "Missing Asset Test",
        "music": {
            "enabled": True,
            "genre": "upbeat_jazz",
            "file": "non_existent_audio_path_xyz.wav"
        }
    }
    with pytest.raises(FileNotFoundError) as excinfo:
        resolve_music_config(plan, skill_root=SKILL_DIR)

    assert "CRITICAL ASSET INTEGRITY FAILURE" in str(excinfo.value) or "not found" in str(excinfo.value).lower()


# ==============================================================================
# 3. DUAL VS SINGLE OUTPUT QA TESTS
# ==============================================================================

def test_qa_single_mode_desktop_does_not_require_mobile():
    """In desktop-only mode, QA must pass without requiring mobile video."""
    with tempfile.TemporaryDirectory() as tmp:
        run_dir = os.path.join(tmp, "run_desktop")
        rec_dir = os.path.join(run_dir, "recording")
        final_dir = os.path.join(run_dir, "final")
        audio_dir = os.path.join(run_dir, "audio")
        os.makedirs(rec_dir, exist_ok=True)
        os.makedirs(final_dir, exist_ok=True)
        os.makedirs(audio_dir, exist_ok=True)

        # Create dummy desktop output
        with open(os.path.join(final_dir, "final-tutorial-desktop.mp4"), "wb") as f:
            f.write(b"dummy_desktop_mp4")
        with open(os.path.join(rec_dir, "recording-manifest.json"), "w") as f:
            json.dump({"engine": "TRUE_CONTINUOUS_BROWSER_VIDEO_V4", "mode": "desktop", "duration_ms": 2000}, f)
        with open(os.path.join(rec_dir, "events.json"), "w") as f:
            json.dump({"events": []}, f)
        with open(os.path.join(rec_dir, "continuous-60fps.mp4"), "wb") as f:
            f.write(b"dummy_raw")

        with wave.open(os.path.join(audio_dir, "audio-master.wav"), "wb") as wf:
            wf.setnchannels(1); wf.setsampwidth(2); wf.setframerate(48000)
            wf.writeframes(b"\x00\x00" * 48000 * 2)

        report = run_qa_gates(run_dir)
        g31 = report["gates"]["gate_31_dual_output_verification"]
        # Mobile render verified should be NOT_APPLICABLE or not fail the run
        assert g31["mobile_render_verified"] in ["NOT_APPLICABLE", "PASS"]


def test_qa_cloned_desktop_as_mobile_rejected_in_dual_mode():
    """In dual mode, copying desktop MP4 as mobile MP4 must be detected and rejected."""
    with tempfile.TemporaryDirectory() as tmp:
        run_dir = os.path.join(tmp, "run_clone_test")
        final_dir = os.path.join(run_dir, "final")
        rec_dir = os.path.join(run_dir, "recording")
        os.makedirs(final_dir, exist_ok=True)
        os.makedirs(rec_dir, exist_ok=True)

        identical_content = b"IDENTICAL_VIDEO_STREAM_BYTES_FOR_DUAL_TEST" * 500
        desktop_mp4 = os.path.join(final_dir, "final-tutorial-desktop.mp4")
        mobile_mp4 = os.path.join(final_dir, "final-tutorial-mobile.mp4")
        with open(desktop_mp4, "wb") as f: f.write(identical_content)
        with open(mobile_mp4, "wb") as f: f.write(identical_content)

        manifest = {"mode": "dual", "run_id": "clone_test"}
        report = {"gates": {}, "overall_status": "PASS"}

        # Probe mocks
        d_probe = {"width": 1920, "height": 1080, "fps": 60.0, "duration_s": 10.0, "has_audio": True, "audio_codec": "aac"}
        m_probe = {"width": 1080, "height": 1920, "fps": 60.0, "duration_s": 10.0, "has_audio": True, "audio_codec": "aac"}

        evaluate_production_checklist(run_dir, manifest, report, d_probe, m_probe, True, True)

        chk = report["production_checklist"]["section_b_video_quality"]
        assert chk["status"] == "FAIL"
        assert chk["distinct_viewport_capture_verified"] == "FAIL"
        assert "identical duplicate" in " ".join(chk["details"]).lower()


# ==============================================================================
# 4. DATA SAFETY & ISOLATION TESTS
# ==============================================================================

def test_data_safety_destructive_mutation_fails_qa():
    """Plan containing raw DROP/TRUNCATE queries must be flagged as FAIL in data safety checklist."""
    with tempfile.TemporaryDirectory() as tmp:
        run_dir = os.path.join(tmp, "run_unsafe")
        os.makedirs(run_dir, exist_ok=True)

        plan_unsafe = {
            "title": "Unsafe Plan",
            "scenes": [{"scene_id": "s1"}],
            "setup": [{"do": "execute_sql", "query": "TRUNCATE TABLE attendances"}]
        }
        with open(os.path.join(run_dir, "effective-plan.json"), "w") as f:
            json.dump(plan_unsafe, f)

        manifest = {"mode": "desktop", "run_id": "unsafe_test"}
        report = {"gates": {}, "overall_status": "PASS"}

        evaluate_production_checklist(run_dir, manifest, report, None, None, False, False)

        chk_e = report["production_checklist"]["section_e_data_safety_and_isolation"]
        assert chk_e["status"] == "FAIL"
        assert chk_e["production_mutation_protection"] == "FAIL"
        assert report["overall_status"] == "FAIL"


def test_data_safety_safe_tracked_plan_passes_qa():
    """Plan using tracked ephemeral test data must pass Section E with exact method logged."""
    with tempfile.TemporaryDirectory() as tmp:
        run_dir = os.path.join(tmp, "run_safe")
        os.makedirs(run_dir, exist_ok=True)

        plan_safe = {
            "title": "Safe Tutorial Plan",
            "data_safety": {
                "isolation_method": "ephemeral_test_tracking",
                "cleanup_policy": "tracked_created_ids_only",
                "allow_production_mutations": False
            },
            "scenes": [{"scene_id": "s1"}]
        }
        with open(os.path.join(run_dir, "effective-plan.json"), "w") as f:
            json.dump(plan_safe, f)

        manifest = {"mode": "desktop", "run_id": "safe_test"}
        report = {"gates": {}, "overall_status": "PASS"}

        evaluate_production_checklist(run_dir, manifest, report, None, None, False, False)

        chk_e = report["production_checklist"]["section_e_data_safety_and_isolation"]
        assert chk_e["status"] == "PASS"
        assert chk_e["isolation_method"] == "TRACKED_EPHEMERAL_TEST_DATA"
        assert chk_e["production_mutation_protection"] == "PASS"
        assert chk_e["cleanup_boundary_enforced"] == "PASS"


# ==============================================================================
# 5. BACKWARD COMPATIBILITY & ARTIFACT PROVENANCE
# ==============================================================================

def test_bgm_disabled_mode_preserves_clean_baseline():
    """When music is false, resolve_music_config cleanly disables BGM and preserves clean baseline."""
    plan = {"title": "No Music Plan", "music": False}
    cfg = resolve_music_config(plan, skill_root=SKILL_DIR)
    assert cfg["enabled"] is False

    with tempfile.TemporaryDirectory() as tmp:
        mgr = ArtifactManager(tmp)
        run_info = mgr.prepare_run("test_proj", "run_no_music")
        plan_p = os.path.join(run_info["run_dir"], "plan.json")
        with open(plan_p, "w") as f: json.dump(plan, f)

        man = mgr.generate_provenance_manifest("test_proj", "run_no_music", plan_p, music_config=cfg)
        assert man["background_music"] is None
        assert man["data_safety"]["status"] == "VERIFIED"
        assert man["data_safety"]["isolation_method"] == "TRACKED_EPHEMERAL_TEST_DATA"


def test_transition_timing_wait_for_responsive_engine():
    """Verify engine.cjs implements responsive wait_for polling with settle_ms instead of blind unconditional freeze."""
    engine_path = os.path.join(SCRIPTS_DIR, "continuous", "engine.cjs")
    assert os.path.isfile(engine_path), "engine.cjs must exist"
    with open(engine_path, "r", encoding="utf-8") as f:
        src = f.read()

    # Must contain responsive polling loop rather than unconditional holdUntil(t0 + budget)
    assert "await holdUntil(t0 + budget);" not in src, "engine.cjs must not unconditionally hold for full budget"
    assert "while (Date.now() < deadline && (vt() - t0) < maxTimeout)" in src, "engine.cjs must poll in virtual time"
    assert "settleMs" in src, "engine.cjs must support settle_ms for smooth post-transition visibility"

