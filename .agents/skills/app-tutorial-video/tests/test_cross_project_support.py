#!/usr/bin/env python
"""
test_cross_project_support.py — Comprehensive Cross-Project Test Suite for App Tutorial Video Skill.
Verifies that app-tutorial-video works across non-SIASEK projects (specifically Apotek Sehat,
React+Vite SPAs, Generic Web apps) as well as SIASEK Presensi.
"""

import json
import os
import sys
import tempfile
import pytest

HERE = os.path.dirname(os.path.abspath(__file__))
SKILL_DIR = os.path.abspath(os.path.join(HERE, ".."))
SCRIPTS_DIR = os.path.join(SKILL_DIR, "scripts")
if SCRIPTS_DIR not in sys.path:
    sys.path.insert(0, SCRIPTS_DIR)

from adapters import AdapterRegistry, LaravelAdapter
from bootstrap import run_bootstrap_workflow
from inventory import build_feature_inventory
from profile import create_default_profile, apply_profile_to_plan
from catalog import resolve_active_workspace_root

APOTEK_ROOT = r"D:\laragon\www\apotek"
SIASEK_ROOT = r"D:\laragon\www\siasek\aplikasi-absensi"


@pytest.mark.skipif(not os.path.isdir(APOTEK_ROOT), reason="Apotek project directory not present on disk")
class TestApotekProjectIntegration:
    """Verify full adapter detection, route/page discovery, seeded auth, inventory, and bootstrap on Apotek project."""

    def test_apotek_stack_and_metadata_detection(self):
        reg = AdapterRegistry()
        adapter, meta = reg.detect(APOTEK_ROOT)
        assert adapter.name == "laravel"
        assert meta["confidence"] >= 0.95
        assert meta["has_login_route"] is True
        details = meta["details"]
        assert details["name"] == "Apotek Sehat"
        assert details["project_id"] == "apotek"
        assert "Inertia + React" in details["frontend_stack"]

        # Verify seeded test accounts discovered from DatabaseSeeder.php
        test_accounts = details.get("test_accounts", [])
        emails = [a["email"] for a in test_accounts]
        assert "admin@apotek.test" in emails
        assert "operator@apotek.test" in emails
        assert "kasir@apotek.test" in emails
        # Admin should be prioritized first
        assert test_accounts[0]["role"] == "admin"
        assert test_accounts[0]["email"] == "admin@apotek.test"

    def test_apotek_prefix_route_and_inertia_page_discovery(self):
        adapter = LaravelAdapter()
        pages = adapter.discover_pages(APOTEK_ROOT)
        paths = [p["path_or_url"] for p in pages]

        # Root '/' redirects to login in Apotek, so '/dashboard' should be the first real page
        assert "/" not in paths
        assert paths[0] == "/dashboard"

        # Prefix-grouped routes in routes/web.php must all be discovered accurately
        expected_paths = [
            "/dashboard",
            "/pos",
            "/pos/history",
            "/medicines",
            "/medicines/adjustments/history",
            "/categories",
            "/expiries",
            "/prescriptions",
            "/prescriptions/create",
            "/reports",
            "/reports/profit-loss",
            "/finance",
            "/users",
            "/settings/pharmacy",
        ]
        for ep in expected_paths:
            assert ep in paths, f"Expected route {ep} not discovered in Apotek routes/web.php"

        # Parameterized and export routes must be excluded from page navigation list
        for p in paths:
            assert "{" not in p
            assert not p.endswith("/export")
            assert not p.endswith("/export/pdf")

    def test_apotek_feature_inventory_real_fingerprints(self):
        inv = build_feature_inventory(APOTEK_ROOT)
        assert inv["total_features"] >= 10
        features_by_id = {f["feature_id"]: f for f in inv["features"]}
        assert "flow_dashboard" in features_by_id
        assert "flow_pos" in features_by_id
        assert "flow_medicines" in features_by_id
        assert "flow_prescriptions" in features_by_id

        # Fingerprints must be real 16-char SHA-256 hex strings, not placeholder workflow_ids
        pos_fp = features_by_id["flow_pos"]["fingerprints"]
        assert "feature_fingerprint" in pos_fp
        assert "route_fingerprint" in pos_fp
        assert "ui_fingerprint" in pos_fp
        assert "workflow_fingerprint" in pos_fp
        assert len(pos_fp["feature_fingerprint"]) == 16
        assert pos_fp["feature_fingerprint"] != "flow_pos"

    def test_apotek_bootstrap_generates_valid_dual_profile_and_plan(self, tmp_path):
        out_dir = str(tmp_path / ".tutorial-video")
        res = run_bootstrap_workflow(
            workspace_root=APOTEK_ROOT,
            mode="dual",
            record_prototype=False,
            output_dir=out_dir
        )
        assert res["status"] == "COMPLETED"
        artifacts = res["steps"]["step_6_artifacts"]
        profile_path = artifacts["profile_path"]
        plan_path = artifacts["plan_path"]

        assert os.path.isfile(profile_path)
        assert os.path.isfile(plan_path)

        with open(profile_path, "r", encoding="utf-8") as f:
            prof = json.load(f)
        assert prof["project_id"] == "apotek"
        assert prof["project_name"] == "Apotek Sehat"
        assert prof["mode"] == "dual"
        assert prof["voice"]["name"] == "id-ID-ArdiNeural"
        assert prof["auth"]["default_email"] == "admin@apotek.test"

        with open(plan_path, "r", encoding="utf-8") as f:
            plan = json.load(f)
        assert plan["project_id"] == "apotek"
        assert plan["mode"] == "dual"
        assert plan["music"]["enabled"] is True
        assert plan["music"]["genre"] == "upbeat_jazz"
        assert "Apotek Sehat" in plan["scenes"][0]["narration"]

        # Verify setup login steps use resolved email rather than raw unresolved ${TUTORIAL_EMAIL}
        fill_steps = [s for s in plan["setup"] if s.get("do") == "fill"]
        assert len(fill_steps) == 2
        assert fill_steps[0]["value"] == "admin@apotek.test"
        assert fill_steps[1]["value"] == "password"


class TestCrossFrameworkAndGlobalResolution:
    """Verify React+Vite, Generic Web, and Global Skill path resolution."""

    def test_global_skill_workspace_resolution_prefers_cwd(self, monkeypatch, tmp_path):
        fake_global_scripts = r"C:\Users\WIND11\.gemini\config\skills\app-tutorial-video\scripts"
        monkeypatch.chdir(tmp_path)
        monkeypatch.delenv("TUTORIAL_WORKSPACE_ROOT", raising=False)
        resolved = resolve_active_workspace_root(fake_global_scripts)
        assert os.path.abspath(resolved) == os.path.abspath(str(tmp_path))

    def test_synthetic_react_vite_project_bootstrap(self, tmp_path):
        proj_dir = tmp_path / "klinik-sehat-spa"
        src_dir = proj_dir / "src" / "components"
        src_dir.mkdir(parents=True)
        (proj_dir / "package.json").write_text(
            json.dumps({
                "name": "klinik-sehat-spa",
                "dependencies": {"react": "^18.2.0", "react-dom": "^18.2.0"},
                "devDependencies": {"vite": "^5.0.0"}
            }),
            encoding="utf-8"
        )
        (proj_dir / "index.html").write_text("<html><body><div id='root'></div></body></html>", encoding="utf-8")
        (src_dir / "JadwalDokter.jsx").write_text("export default function JadwalDokter() { return <div/>; }", encoding="utf-8")

        res = run_bootstrap_workflow(
            workspace_root=str(proj_dir),
            mode="dual",
            record_prototype=False
        )
        assert res["status"] == "COMPLETED"
        assert res["steps"]["step_1_detection"]["adapter_name"] == "react_vite"
        plan_path = res["steps"]["step_6_artifacts"]["plan_path"]
        with open(plan_path, "r", encoding="utf-8") as f:
            plan = json.load(f)
        assert plan["mode"] == "dual"
        assert "Klinik Sehat Spa" in plan["title"]

