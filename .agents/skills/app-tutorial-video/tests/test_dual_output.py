#!/usr/bin/env python
"""
test_dual_output.py — Test Suite for Dual Desktop/Mobile Output in V4 Engine.
Verifies CLI mode parsing, pipeline invocation, artifact tracking, and Gate 31 QA.
"""

import os
import sys
import json
import tempfile
import pytest

HERE = os.path.dirname(os.path.abspath(__file__))
SKILL_DIR = os.path.abspath(os.path.join(HERE, ".."))
SCRIPTS_DIR = os.path.join(SKILL_DIR, "scripts")
if SCRIPTS_DIR not in sys.path:
    sys.path.insert(0, SCRIPTS_DIR)

from artifact_manager import ArtifactManager
from continuous_qa import run_qa_gates


def test_dual_mode_artifact_manager_tracking():
    with tempfile.TemporaryDirectory() as tmp:
        mgr = ArtifactManager(tmp)
        run_info = mgr.prepare_run("test_proj", run_id="run_dual_test")
        run_dir = run_info["run_dir"]

        # Simulate presence of dual mp4 outputs
        final_dir = os.path.join(run_dir, "final")
        os.makedirs(final_dir, exist_ok=True)
        desktop_mp4 = os.path.join(final_dir, "final-tutorial-desktop.mp4")
        mobile_mp4 = os.path.join(final_dir, "final-tutorial-mobile.mp4")
        legacy_mp4 = os.path.join(final_dir, "final-tutorial.mp4")
        with open(desktop_mp4, "wb") as f: f.write(b"fake_desktop_video_content")
        with open(mobile_mp4, "wb") as f: f.write(b"fake_mobile_video_content")
        with open(legacy_mp4, "wb") as f: f.write(b"fake_legacy_video_content")

        rec_dir = os.path.join(run_dir, "recording")
        os.makedirs(rec_dir, exist_ok=True)
        events_json = os.path.join(rec_dir, "events.json")
        rec_mp4 = os.path.join(rec_dir, "continuous-60fps.mp4")
        with open(events_json, "w") as f: json.dump({"events": []}, f)
        with open(rec_mp4, "wb") as f: f.write(b"fake_raw_rec")

        audio_dir = os.path.join(run_dir, "audio")
        os.makedirs(audio_dir, exist_ok=True)
        audio_wav = os.path.join(audio_dir, "audio-master.wav")
        with open(audio_wav, "wb") as f: f.write(b"fake_wav")

        plan_path = os.path.join(run_dir, "plan.json")
        with open(plan_path, "w") as f: json.dump({"mode": "dual"}, f)

        manifest = mgr.generate_provenance_manifest("test_proj", "run_dual_test", plan_path)
        arts = manifest["artifacts"]
        assert "final_video_desktop" in arts
        assert "final_video_mobile" in arts
        assert arts["final_video_desktop"]["path"] == os.path.join("final", "final-tutorial-desktop.mp4").replace("\\", "/") or arts["final_video_desktop"]["path"] == os.path.join("final", "final-tutorial-desktop.mp4")
        assert arts["final_video_mobile"]["path"] == os.path.join("final", "final-tutorial-mobile.mp4").replace("\\", "/") or arts["final_video_mobile"]["path"] == os.path.join("final", "final-tutorial-mobile.mp4")


def test_gate_31_dual_output_missing_desktop_reports_fail():
    with tempfile.TemporaryDirectory() as tmp:
        run_dir = os.path.join(tmp, "run_test")
        rec_dir = os.path.join(run_dir, "recording")
        final_dir = os.path.join(run_dir, "final")
        audio_dir = os.path.join(run_dir, "audio")
        os.makedirs(rec_dir, exist_ok=True)
        os.makedirs(final_dir, exist_ok=True)
        os.makedirs(audio_dir, exist_ok=True)

        # Only mobile present in dual mode
        with open(os.path.join(final_dir, "final-tutorial-mobile.mp4"), "wb") as f: f.write(b"mobile")
        with open(os.path.join(rec_dir, "recording-manifest.json"), "w") as f:
            json.dump({"engine": "TRUE_CONTINUOUS_BROWSER_VIDEO_V4", "mode": "dual", "duration_ms": 1000}, f)
        with open(os.path.join(rec_dir, "events.json"), "w") as f:
            json.dump({"events": []}, f)
        with open(os.path.join(rec_dir, "continuous-60fps.mp4"), "wb") as f: f.write(b"mp4")

        # Fake wave file
        import wave
        with wave.open(os.path.join(audio_dir, "audio-master.wav"), "wb") as wf:
            wf.setnchannels(1); wf.setsampwidth(2); wf.setframerate(48000)
            wf.writeframes(b"\x00\x00" * 48000)

        report = run_qa_gates(run_dir)
        assert "gate_31_dual_output_verification" in report["gates"]
        g31 = report["gates"]["gate_31_dual_output_verification"]
        assert g31["status"] == "FAIL"
        assert g31["desktop_render_verified"] == "FAIL"
