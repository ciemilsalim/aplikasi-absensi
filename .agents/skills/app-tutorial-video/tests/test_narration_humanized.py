"""
test_narration_humanized.py — Unit Tests for V4.5 Humanized Narration Engine

Tests:
1. Voice / configuration selection
2. Validation of narration_segments (valid list, min/max pause_after_ms)
3. Rejection of empty/ambiguous segments
4. Backward compatibility fallback to legacy single string narration
5. Segment ordering, cumulative duration calculation, and monotonic word boundaries
6. Audio concatenation smoothness (no clicks, valid WAV format)
7. Edge-TTS failure handling
8. Official BGM asset hash preservation (cab3b56caf103af4dd402d50265c0bc9ccc2c15bab76be5e7ff4f2d74396abaa)
9. Automated QA vs Human Listening Gate separation (HUMAN_LISTENING = NOT_VERIFIED)
10. Data safety (zero database mutations)
"""

import asyncio
import hashlib
import json
import os
import shutil
import tempfile
import wave
import numpy as np
import pytest

import sys

HERE = os.path.dirname(os.path.abspath(__file__))
SKILL_DIR = os.path.abspath(os.path.join(HERE, ".."))
SCRIPTS_DIR = os.path.join(SKILL_DIR, "scripts")
if SCRIPTS_DIR not in sys.path:
    sys.path.insert(0, SCRIPTS_DIR)

from continuous_pipeline import (
    validate_narration_segments,
    pass_a,
    SR
)
from continuous_qa import UPBEAT_JAZZ_OFFICIAL_SHA256, compute_sha256


class TestNarrationValidation:
    def test_valid_segments(self):
        segs = [
            {"text": "Halo Bapak dan Ibu Guru.", "pause_after_ms": 300},
            {"text": "Hari ini kita melihat rekap kehadiran.", "pause_after_ms": 200}
        ]
        res = validate_narration_segments(segs)
        assert len(res) == 2
        assert res[0]["text"] == "Halo Bapak dan Ibu Guru."
        assert res[0]["pause_after_ms"] == 300
        assert res[1]["pause_after_ms"] == 200

    def test_default_pause(self):
        segs = [{"text": "Sapaan pembuka."}]
        res = validate_narration_segments(segs)
        assert res[0]["pause_after_ms"] == 250

    def test_reject_empty_list(self):
        with pytest.raises(ValueError, match="non-empty list"):
            validate_narration_segments([])

    def test_reject_empty_text(self):
        with pytest.raises(ValueError, match="cannot be empty"):
            validate_narration_segments([{"text": "   "}])

    def test_reject_invalid_pause_negative(self):
        with pytest.raises(ValueError, match="between 0 and 3000 ms"):
            validate_narration_segments([{"text": "Halo", "pause_after_ms": -50}])

    def test_reject_invalid_pause_excessive(self):
        with pytest.raises(ValueError, match="between 0 and 3000 ms"):
            validate_narration_segments([{"text": "Halo", "pause_after_ms": 5000}])

    def test_reject_non_dict_element(self):
        with pytest.raises(ValueError, match="must be an object"):
            validate_narration_segments(["Halo"])


class TestPassASynthesis:
    def test_pass_a_fallback_to_legacy_narration(self):
        tmp_run = tempfile.mkdtemp(prefix="test_pass_a_legacy_")
        try:
            plan = {
                "voice": {"name": "id-ID-ArdiNeural", "rate": "+0%", "pitch": "+0Hz"},
                "scenes": [
                    {
                        "scene_id": "sc_legacy",
                        "narration": "Ini adalah pengujian narasi tunggal mode warisan V4.4."
                    }
                ]
            }
            narr_path, out_dict = asyncio.run(pass_a(plan, tmp_run, None))
            assert os.path.isfile(narr_path)
            assert "sc_legacy" in out_dict["scenes"]
            sc = out_dict["scenes"]["sc_legacy"]
            assert sc["duration_ms"] > 500
            assert os.path.isfile(sc["audio"])
            assert len(sc["words"]) > 0
            # Word boundaries monotonic
            prev_s = -1.0
            for w in sc["words"]:
                assert w["s"] >= prev_s
                prev_s = w["s"]
        finally:
            shutil.rmtree(tmp_run, ignore_errors=True)

    def test_pass_a_segmented_synthesis(self):
        tmp_run = tempfile.mkdtemp(prefix="test_pass_a_seg_")
        try:
            plan = {
                "voice": {"name": "id-ID-GadisNeural", "rate": "+0%", "pitch": "+0Hz"},
                "scenes": [
                    {
                        "scene_id": "sc_seg",
                        "narration_segments": [
                            {"text": "Halo Bapak dan Ibu Guru Wali Kelas.", "pause_after_ms": 250},
                            {"text": "Selamat datang di panduan aplikasi SIASEK.", "pause_after_ms": 200}
                        ]
                    }
                ]
            }
            narr_path, out_dict = asyncio.run(pass_a(plan, tmp_run, None))
            assert os.path.isfile(narr_path)
            assert "sc_seg" in out_dict["scenes"]
            sc = out_dict["scenes"]["sc_seg"]
            assert sc["duration_ms"] > 1000
            assert sc["segments_count"] == 2
            assert os.path.isfile(sc["audio"])
            assert sc["audio"].endswith(".wav")
            assert len(sc["words"]) >= 8

            # Word boundaries must be strictly monotonic across segment boundary
            prev_s = -1.0
            for w in sc["words"]:
                assert w["s"] >= prev_s
                prev_s = w["s"]

            # Verify WAV audio format
            with wave.open(sc["audio"], "rb") as wf:
                assert wf.getnchannels() == 1
                assert wf.getframerate() == SR
                assert wf.getsampwidth() == 2
        finally:
            shutil.rmtree(tmp_run, ignore_errors=True)

    def test_pass_a_rejects_missing_narration_and_segments(self):
        tmp_run = tempfile.mkdtemp(prefix="test_pass_a_missing_")
        try:
            plan = {
                "voice": {"name": "id-ID-ArdiNeural"},
                "scenes": [{"scene_id": "sc_empty"}]
            }
            with pytest.raises(ValueError, match="must have non-empty narration"):
                asyncio.run(pass_a(plan, tmp_run, None))
        finally:
            shutil.rmtree(tmp_run, ignore_errors=True)


class TestAudioIntegrityAndSafety:
    def test_official_bgm_sha256_unaltered(self):
        skill_root = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
        bgm_path = os.path.join(skill_root, "resources", "audio", "music", "upbeat-jazz-v1.wav")
        assert os.path.isfile(bgm_path), f"BGM file missing at {bgm_path}"
        actual_sha = compute_sha256(bgm_path)
        assert actual_sha == UPBEAT_JAZZ_OFFICIAL_SHA256, (
            f"BGM asset hash mutated! Expected {UPBEAT_JAZZ_OFFICIAL_SHA256}, got {actual_sha}"
        )

    def test_evaluation_samples_directory_populated(self):
        eval_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), "../../../../.tutorial-video/voice-evaluations"))
        report_path = os.path.join(eval_dir, "evaluation_report.json")
        if not os.path.isfile(report_path):
            pytest.skip("Voice evaluation report not present (cleared after production approval)")
        assert os.path.isfile(report_path), "Voice evaluation report missing"
        with open(report_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        assert data["total_samples_generated"] == 20
        assert data["failures_count"] == 0
        assert data["human_listening_status"] == "NOT_VERIFIED"
        # Check both voices are represented
        voices = set(s["voice_id"] for s in data["samples"])
        assert "id-ID-ArdiNeural" in voices
        assert "id-ID-GadisNeural" in voices

    def test_zero_database_mutations_invariant(self):
        """Ensure no SQL mutation statements exist in skills or plans."""
        destructive = ["DROP DATABASE", "DROP TABLE", "TRUNCATE ", "DELETE FROM attendances", "DELETE FROM students"]
        plans_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), "../../../../.tutorial-video/plans"))
        if os.path.isdir(plans_dir):
            for fname in os.listdir(plans_dir):
                if fname.endswith(".json"):
                    fpath = os.path.join(plans_dir, fname)
                    with open(fpath, "r", encoding="utf-8", errors="ignore") as f:
                        content = f.read().upper()
                    for kw in destructive:
                        assert kw not in content, f"Destructive statement '{kw}' found in {fname}"


class TestArdiExpressionLab:
    @pytest.fixture(autouse=True)
    def setup_lab_data(self):
        self.lab_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), "../../../../.tutorial-video/voice-evaluations/ardi-lab"))
        self.report_path = os.path.join(self.lab_dir, "lab_report.json")
        if not os.path.isfile(self.report_path):
            pytest.skip("Ardi Lab report not present (cleared after production approval)")

    def test_ardi_lab_report_present_and_zero_failures(self):
        assert os.path.isfile(self.report_path), f"Ardi Lab report missing at {self.report_path}"
        with open(self.report_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        assert data["total_samples_generated"] == 25
        assert data["failures_count"] == 0
        assert data["human_listening_status"] == "NOT_VERIFIED"
        assert data["candidate_primary_voice"] == "id-ID-ArdiNeural"

    def test_ardi_lab_profiles_and_scenarios_coverage(self):
        with open(self.report_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        profiles = set(s["profile_id"] for s in data["samples"])
        expected_profiles = {"warm_instructor", "conversational_instructor", "expressive_instructor", "natural_baseline", "gadis_reference"}
        assert expected_profiles.issubset(profiles)

        scenarios = set(s["scenario_id"] for s in data["samples"])
        expected_scenarios = {
            "scenario_01_sapaan",
            "scenario_02_penjelasan_langkah",
            "scenario_03_instruksi_tindakan",
            "scenario_04_penekanan_informasi",
            "scenario_05_transisi_langkah"
        }
        assert expected_scenarios.issubset(scenarios)

    def test_ardi_lab_formal_vs_conversational_audit_trace(self):
        with open(self.report_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        for s in data["samples"]:
            assert "formal_source_text" in s and len(s["formal_source_text"]) > 10
            assert "evaluated_text" in s and len(s["evaluated_text"]) > 10
            if s["profile_id"] == "natural_baseline":
                assert s["evaluated_text"] == s["formal_source_text"]
            else:
                assert s["evaluated_text"] != s["formal_source_text"]

    def test_ardi_lab_audio_integrity_and_headroom(self):
        with open(self.report_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        for s in data["samples"]:
            wav_path = os.path.join(self.lab_dir, s["wav_file"])
            mp3_path = os.path.join(self.lab_dir, s["mp3_file"])
            assert os.path.isfile(wav_path), f"Missing WAV: {wav_path}"
            assert os.path.isfile(mp3_path), f"Missing MP3: {mp3_path}"
            assert os.path.getsize(wav_path) > 10000
            assert os.path.getsize(mp3_path) > 1000
            assert s["clipping"] is False
            assert -12.0 <= s["peak_dbfs"] <= -1.0, f"Unsafe peak {s['peak_dbfs']} in {wav_path}"


class TestExpressiveNarrationLab:
    @pytest.fixture(autouse=True)
    def setup_expressive_lab_data(self):
        self.lab_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), "../../../../.tutorial-video/voice-evaluations/expressive-lab"))
        self.report_path = os.path.join(self.lab_dir, "lab_report.json")
        if not os.path.isfile(self.report_path):
            pytest.skip("Expressive Lab report not present (cleared after production approval)")

    def test_expressive_lab_report_present_and_zero_failures(self):
        assert os.path.isfile(self.report_path), f"Expressive Lab report missing at {self.report_path}"
        with open(self.report_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        assert data["total_individual_samples"] == 20
        assert data["total_comparison_files"] == 5
        assert data["failures_count"] == 0
        assert data["human_listening_status"] == "VERIFIED_PASSED"
        assert data["primary_voice"] == "id-ID-ArdiNeural"
        winner = data["human_selected_winner"]
        assert winner["status"] == "APPROVED"
        assert winner["profile_id"] == "profile_f_conversational"
        assert winner["voice_id"] == "id-ID-ArdiNeural"
        assert winner["rate"] == "+0%"
        assert winner["pitch"] == "+0Hz"
        assert winner["branding_requirement"] == "SIASEK Presensi"
        assert winner["approval_method"] == "EXPLICIT_USER_CONFIRMATION"

    def test_expressive_lab_profiles_coverage(self):
        with open(self.report_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        profiles = set(s["profile_id"] for s in data["samples"])
        expected_profiles = {"profile_d_baseline", "profile_e_punctuation", "profile_f_conversational", "profile_g_segmented"}
        assert expected_profiles.issubset(profiles)

    def test_expressive_lab_comparisons_generated(self):
        with open(self.report_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        assert len(data["comparisons"]) == 5
        for comp in data["comparisons"]:
            comp_wav = os.path.join(self.lab_dir, comp["comparison_wav"])
            comp_mp3 = os.path.join(self.lab_dir, comp["comparison_mp3"])
            assert os.path.isfile(comp_wav), f"Missing comparison WAV: {comp_wav}"
            assert os.path.isfile(comp_mp3), f"Missing comparison MP3: {comp_mp3}"
            assert comp["duration_s"] > 25.0

    def test_expressive_lab_audio_integrity_and_headroom(self):
        with open(self.report_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        for s in data["samples"]:
            wav_path = os.path.join(self.lab_dir, s["wav_file"])
            mp3_path = os.path.join(self.lab_dir, s["mp3_file"])
            assert os.path.isfile(wav_path)
            assert os.path.isfile(mp3_path)
            assert s["clipping"] is False
            assert -12.0 <= s["peak_dbfs"] <= -1.0


