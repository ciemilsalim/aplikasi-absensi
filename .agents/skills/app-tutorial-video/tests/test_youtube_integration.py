#!/usr/bin/env python3
"""
test_youtube_integration.py — Test suite for YouTube metadata generation and publishing integration.
Verifies tailored metadata generation, timestamps/chapters, #Shorts detection, tags,
offline upload kit creation, and human confirmation gate safety.
"""

import json
import os
import shutil
import sys
import tempfile
import pytest

HERE = os.path.dirname(os.path.abspath(__file__))
SKILL_DIR = os.path.abspath(os.path.join(HERE, ".."))
SCRIPTS_DIR = os.path.join(SKILL_DIR, "scripts")
if SCRIPTS_DIR not in sys.path:
    sys.path.insert(0, SCRIPTS_DIR)

from youtube_publisher import (
    generate_youtube_metadata,
    upload_to_youtube,
    format_timestamp,
    clean_scene_title,
    extract_scene_timestamps,
)


class TestYouTubePublisherUnit:
    """Unit tests for timestamp formatting, scene titles, and YouTube metadata structures."""

    def test_timestamp_formatting(self):
        assert format_timestamp(0) == "00:00"
        assert format_timestamp(15000) == "00:15"
        assert format_timestamp(65000) == "01:05"
        assert format_timestamp(3665000) == "01:01:05"

    def test_clean_scene_title(self):
        assert clean_scene_title("scene_01_masukkan_nomor_whatsapp") == "Masukkan Nomor Whatsapp"
        assert clean_scene_title("scene_02_isi_nama_dan_email") == "Isi Nama Dan Email"
        assert clean_scene_title("custom_id", raw_title="Judul Kustom Saya") == "Judul Kustom Saya"

    def test_metadata_generation_on_real_siasek_mobile_run(self):
        # 4 levels up to workspace root from .agents/skills/app-tutorial-video/tests
        ws_root = os.path.abspath(os.path.join(HERE, "..", "..", "..", ".."))
        run_dir = os.path.join(ws_root, ".tutorial-video", "projects", "siasek", "runs", "run_20261101_135451_0d1728")
        if not os.path.isdir(run_dir):
            pytest.skip(f"Run directory not found: {run_dir}")

        res = generate_youtube_metadata(run_dir, privacy="private", category_id="27")
        assert res["status"] == "OK"
        assert res["is_shorts"] is True
        assert "#Shorts" in res["title"]
        assert len(res["title"]) <= 100
        assert res["chapters_count"] >= 3

        # Verify JSON payload conforms to YouTube Data API v3
        payload_file = res["payload_json"]
        assert os.path.isfile(payload_file)
        with open(payload_file, "r", encoding="utf-8") as f:
            payload = json.load(f)

        assert "snippet" in payload
        assert "status" in payload
        snippet = payload["snippet"]
        assert snippet["title"] == res["title"]
        assert len(snippet["description"]) <= 5000
        assert "00:00" in snippet["description"]
        assert snippet["categoryId"] == "27"
        assert snippet["defaultLanguage"] == "id"
        assert isinstance(snippet["tags"], list)
        assert len(snippet["tags"]) > 0

        # Verify Markdown Upload Kit
        kit_file = res["upload_kit_md"]
        assert os.path.isfile(kit_file)
        with open(kit_file, "r", encoding="utf-8") as f:
            kit_text = f.read()
        assert "## 1. Judul Video" in kit_text
        assert "## 2. Deskripsi Video" in kit_text
        assert "## 3. Tag Video" in kit_text
        assert "00:00 - Masukkan Nomor Whatsapp" in kit_text

    def test_landscape_video_does_not_force_shorts(self):
        """Synthetic test to ensure 16:9 desktop videos are treated as standard videos without #Shorts tag."""
        with tempfile.TemporaryDirectory() as tmp_dir:
            # Create synthetic plan & effective plan
            plan = {
                "project_id": "siasek",
                "title": "Panduan Dashboard Administrator Sekolah",
                "mode": "desktop",
                "scenes": [
                    {"scene_id": "scene_01_login_admin", "title": "Login ke Panel Admin", "narration": "Silakan login ke panel administrator."},
                    {"scene_id": "scene_02_lihat_rekap", "title": "Melihat Rekapitulasi Presensi", "narration": "Lihat statistik kehadiran siswa hari ini."},
                    {"scene_id": "scene_03_export_laporan", "title": "Ekspor Laporan Bulanan", "narration": "Unduh laporan dalam format PDF atau Excel."}
                ]
            }
            with open(os.path.join(tmp_dir, "effective-plan.json"), "w", encoding="utf-8") as f:
                json.dump(plan, f)

            # Create synthetic recording folder with desktop manifest
            rec_dir = os.path.join(tmp_dir, "recording")
            os.makedirs(rec_dir, exist_ok=True)
            manifest = {
                "engine": "TRUE_CONTINUOUS_BROWSER_VIDEO_V4",
                "mode": "desktop",
                "duration_ms": 95000.0,
                "mobile": False,
                "output": [1920, 1080]
            }
            with open(os.path.join(rec_dir, "recording-manifest-desktop.json"), "w", encoding="utf-8") as f:
                json.dump(manifest, f)

            res = generate_youtube_metadata(tmp_dir, prefer_mode="desktop")
            assert res["status"] == "OK"
            assert res["is_shorts"] is False
            assert "#Shorts" not in res["title"]
            assert "Panduan Dashboard Administrator Sekolah" in res["title"]
            assert res["chapters_count"] == 3

    def test_human_confirmation_gate_on_upload(self):
        with tempfile.TemporaryDirectory() as tmp_dir:
            res = upload_to_youtube(tmp_dir, confirm_upload=False)
            assert res["status"] == "NEEDS_HUMAN_CONFIRMATION"
            assert "confirm-upload" in res["message"]

    def test_missing_credentials_guidance(self):
        with tempfile.TemporaryDirectory() as tmp_dir:
            creds_dir = os.path.join(tmp_dir, ".credentials")
            os.makedirs(creds_dir, exist_ok=True)
            res = upload_to_youtube(tmp_dir, creds_dir=creds_dir, confirm_upload=True)
            assert res["status"] == "CREDENTIALS_REQUIRED"
            assert "youtube-auth" in res["setup_command"]
