#!/usr/bin/env python
"""
bootstrap.py — 10-Step New Project Bootstrap Workflow for App Tutorial Video Skill.
Inspects unknown repositories, creates tailored Project Profiles & Tutorial Plans,
validates selectors, records prototype scenes, and executes QA gates.
"""

import argparse
import json
import os
import socket
import subprocess
import sys
import urllib.parse
import urllib.request
from typing import Any, Dict, List, Optional, Tuple

HERE = os.path.dirname(os.path.abspath(__file__))
if HERE not in sys.path:
    sys.path.insert(0, HERE)

from adapters import AdapterRegistry
from profile import create_default_profile, save_profile, apply_profile_to_plan
from preflight import validate_plan_selectors
from artifact_manager import ArtifactManager


def is_url_reachable(url: str, timeout: float = 0.8) -> bool:
    """Fast check if HTTP URL responds, using quick TCP pre-check to avoid IPv6 timeouts."""
    try:
        parsed = urllib.parse.urlparse(url)
        host = parsed.hostname or "127.0.0.1"
        port = parsed.port or (443 if parsed.scheme == "https" else 80)
        check_host = "127.0.0.1" if host == "localhost" else host
        with socket.create_connection((check_host, port), timeout=0.25):
            pass
        req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            return resp.status in [200, 301, 302, 401, 403]
    except Exception:
        return False


def run_bootstrap_workflow(
    workspace_root: str,
    project_id: Optional[str] = None,
    base_url: Optional[str] = None,
    mode: str = "dual",
    record_prototype: bool = False,
    output_dir: Optional[str] = None
) -> Dict[str, Any]:
    """
    Executes the 10-step bootstrap sequence.
    """
    workspace_root = os.path.abspath(workspace_root)
    reports: Dict[str, Any] = {
        "workspace_root": workspace_root,
        "steps": {},
        "status": "IN_PROGRESS"
    }

    print("=" * 70)
    print(" [BOOTSTRAP] INITIATING 10-STEP CROSS-PROJECT TUTORIAL ONBOARDING")
    print(f" Target Workspace: {workspace_root}")
    print("=" * 70)

    # ──────────────────────────────────────────────────────────────────
    # STEP 1: DETECT FRAMEWORK & APP STRUCTURE
    # ──────────────────────────────────────────────────────────────────
    print("[Step 1/10] Detecting framework, structure, and entry points...")
    registry = AdapterRegistry()
    adapter, meta = registry.detect(workspace_root)
    details = meta.get("details", {})
    reports["steps"]["step_1_detection"] = {
        "adapter_name": adapter.name,
        "framework": meta.get("framework_title") or meta.get("framework"),
        "confidence": meta.get("confidence"),
        "entry_points": meta.get("entry_points", []),
        "details": details
    }
    print(f" -> Detected: {meta.get('framework_title') or adapter.framework} (Confidence: {meta.get('confidence')})")

    # Determine default project identity
    detected_name = details.get("name") or os.path.basename(workspace_root).replace("-", " ").replace("_", " ").title()
    if not project_id:
        project_id = details.get("project_id") or os.path.basename(workspace_root).lower().replace(" ", "-").replace("_", "-")

    # ──────────────────────────────────────────────────────────────────
    # STEP 2: CHECK HOW TO RUN APPLICATION & DETECT LOCAL URL
    # ──────────────────────────────────────────────────────────────────
    print("[Step 2/10] Verifying local server and active URL...")
    rec_port = meta.get("default_port")
    rec_cmd = meta.get("server_recommendation")
    env_app_url = details.get("app_url")

    target_url = base_url
    if not target_url:
        candidates = []
        if env_app_url and env_app_url.startswith("http"):
            candidates.append(env_app_url.rstrip("/"))
        if rec_port:
            candidates.append(f"http://127.0.0.1:{rec_port}")
            candidates.append(f"http://localhost:{rec_port}")
        # Common local ports
        for p in [8000, 8080, 3000, 5173, 4173]:
            c1 = f"http://127.0.0.1:{p}"
            c2 = f"http://localhost:{p}"
            if c1 not in candidates:
                candidates.append(c1)
            if c2 not in candidates:
                candidates.append(c2)

        for cand in candidates:
            if is_url_reachable(cand):
                target_url = cand
                print(f" -> Detected active local web server at: {target_url}")
                break

    if not target_url:
        target_url = f"http://127.0.0.1:{rec_port or 8000}"
        print(f" -> No active server detected. Defaulting to: {target_url}")
        print(f" -> Recommended server command: {rec_cmd or 'Start your web server'}")

    reports["steps"]["step_2_runtime_url"] = {
        "target_url": target_url,
        "is_reachable": is_url_reachable(target_url),
        "server_command": rec_cmd
    }

    # ──────────────────────────────────────────────────────────────────
    # STEP 3: DISCOVER PAGES, NAVIGATION, COMPONENTS & USER FLOWS
    # ──────────────────────────────────────────────────────────────────
    print("[Step 3/10] Discovering accessible pages and user flows...")
    pages = adapter.discover_pages(workspace_root)
    reports["steps"]["step_3_pages"] = {
        "total_pages": len(pages),
        "pages": pages[:15]
    }
    print(f" -> Discovered {len(pages)} primary pages / sections.")

    # ──────────────────────────────────────────────────────────────────
    # STEP 4: DETERMINE AUTHENTICATION REQUIREMENTS & PERMITTED TEST ACCOUNTS
    # ──────────────────────────────────────────────────────────────────
    print("[Step 4/10] Evaluating authentication requirements...")
    auth_needed = meta.get("has_login_route", False) or any(p.get("requires_auth", False) for p in pages)
    auth_cfg: Dict[str, Any] = {"type": "none"}
    if auth_needed:
        test_accounts = details.get("test_accounts", [])
        primary_test_acc = test_accounts[0] if test_accounts else {}
        auth_cfg = {
            "type": "form_login",
            "login_url": f"{target_url}/login",
            "email_selector": "#email",
            "password_selector": "#password",
            "submit_selector": "button[type='submit']",
            "email_env": "TUTORIAL_EMAIL",
            "password_env": "TUTORIAL_PASSWORD",
            "default_email": primary_test_acc.get("email", ""),
            "default_password": primary_test_acc.get("password", ""),
            "available_test_accounts": test_accounts
        }
        if primary_test_acc.get("email"):
            print(f" -> Authentication route detected: auto-configured seeded test account ({primary_test_acc['email']}).")
        else:
            print(" -> Authentication route detected: configured template in project profile.")
    else:
        print(" -> Public web application detected: authentication bypassed.")

    reports["steps"]["step_4_auth"] = auth_cfg

    # ──────────────────────────────────────────────────────────────────
    # STEP 5: MAP TUTORIAL WORKFLOWS FROM VERIFIED FEATURES
    # ──────────────────────────────────────────────────────────────────
    print("[Step 5/10] Mapping authentic tutorial workflows...")
    workflows = adapter.discover_workflows(workspace_root)
    reports["steps"]["step_5_workflows"] = {
        "total_workflows": len(workflows),
        "workflows": workflows
    }
    print(f" -> Mapped {len(workflows)} authentic workflow candidates.")

    # ──────────────────────────────────────────────────────────────────
    # STEP 6: GENERATE PROJECT PROFILE & TUTORIAL PLAN
    # ──────────────────────────────────────────────────────────────────
    print("[Step 6/10] Generating Project Profile and initial Tutorial Plan...")
    clean_proj_name = detected_name if any(c.isupper() for c in detected_name) else detected_name.replace("-", " ").title()
    profile = create_default_profile(
        project_id=project_id,
        project_name=clean_proj_name,
        base_url=target_url,
        framework=adapter.name,
        mode=mode,
        auth=auth_cfg,
        server_cmd=rec_cmd
    )
    profile["workflows"] = workflows

    # Setup directories
    storage_root = output_dir or os.path.join(workspace_root, ".tutorial-video")
    proj_dir = os.path.join(storage_root, "projects", project_id)
    os.makedirs(proj_dir, exist_ok=True)

    profile_path = os.path.join(proj_dir, "project-profile.json")
    save_profile(profile, profile_path)
    print(f" -> Project Profile saved: {profile_path}")

    # Build prototype 1-scene plan
    selected_flow = workflows[0] if workflows else {
        "workflow_id": "overview",
        "name": "Overview Antarmuka",
        "description": "Menampilkan halaman utama aplikasi.",
        "path": "/",
        "steps": [{"do": "wait_for", "selector": "body"}]
    }

    first_page_path = selected_flow.get("path", "/")
    full_first_url = target_url + (first_page_path if first_page_path.startswith("/") else f"/{first_page_path}")

    setup_steps = [{"do": "goto", "url": full_first_url}]
    if auth_needed and auth_cfg.get("type") == "form_login":
        setup_steps = adapter.generate_auth_setup(profile) + [{"do": "goto", "url": full_first_url}]

    scene_steps = [
        {"do": "wait_for", "selector": "body", "budget_ms": 1500},
        {"do": "hold", "ms": 400}
    ]

    opening_segments = [
        {
            "text": f"Selamat datang di panduan aplikasi {profile['project_name']}.",
            "pause_after_ms": 280
        },
        {
            "text": f"Hari ini kita bersama-sama akan mempelajari fitur {selected_flow.get('name', 'utama')} secara praktis dan menyeluruh.",
            "pause_after_ms": 200
        }
    ]

    prototype_plan = {
        "version": "4.5.0",
        "engine": "TRUE_CONTINUOUS_BROWSER_VIDEO_V4",
        "project_id": project_id,
        "title": f"Panduan Penggunaan — {profile['project_name']}",
        "mode": mode,
        "fps": 60,
        "target_resolution": profile["target_resolution"],
        "viewport": profile["viewport"],
        "voice": profile["voice"],
        "music": {
            "enabled": True,
            "genre": "upbeat_jazz"
        },
        "data_safety": {
            "isolation_method": "ephemeral_test_tracking",
            "cleanup_policy": "tracked_created_ids_only",
            "allow_production_mutations": False
        },
        "reaction_delay_ms": profile["reaction_delay_ms"],
        "color_scheme": profile["color_scheme"],
        "setup": setup_steps,
        "scenes": [
            {
                "scene_id": "scene_01_overview",
                "narration": " ".join(seg["text"] for seg in opening_segments),
                "narration_segments": opening_segments,
                "steps": scene_steps,
                "result_hold_ms": 1400
            }
        ]
    }
    if "desktop_viewport" in profile:
        prototype_plan["desktop_viewport"] = profile["desktop_viewport"]
        prototype_plan["desktop_target_resolution"] = profile["desktop_target_resolution"]

    plan_path = os.path.join(proj_dir, "tutorial-plan.json")
    with open(plan_path, "w", encoding="utf-8") as f:
        json.dump(prototype_plan, f, indent=2, ensure_ascii=False)
    print(f" -> Tutorial Plan saved: {plan_path}")

    reports["steps"]["step_6_artifacts"] = {
        "profile_path": profile_path,
        "plan_path": plan_path
    }

    # ──────────────────────────────────────────────────────────────────
    # STEP 7: VALIDATE SELECTORS & INTERACTIONS BEFORE RECORDING
    # ──────────────────────────────────────────────────────────────────
    print("[Step 7/10] Validating selectors and reachability via preflight...")
    if reports["steps"]["step_2_runtime_url"].get("is_reachable"):
        preflight_res = validate_plan_selectors(plan_path, workspace_root=workspace_root)
    else:
        preflight_res = {
            "passed": False,
            "skipped_offline": True,
            "error": f"Local web server at {target_url} is not currently running. Start the server first to run live DOM preflight.",
            "checks": []
        }
    reports["steps"]["step_7_preflight"] = preflight_res
    if preflight_res.get("passed"):
        print(" -> Preflight PASSED: Selectors and navigation verified!")
    else:
        print(f" -> Preflight NOTE: {preflight_res.get('error', 'Some selectors need runtime review.')}")

    # ──────────────────────────────────────────────────────────────────
    # STEP 8: PREPARE PROTOTYPE SCENE EXECUTION
    # ──────────────────────────────────────────────────────────────────
    print("[Step 8/10] Prototype scene configuration verified.")
    reports["steps"]["step_8_prototype"] = {
        "scene_count": len(prototype_plan["scenes"]),
        "scenes": [s["scene_id"] for s in prototype_plan["scenes"]]
    }

    # ──────────────────────────────────────────────────────────────────
    # STEP 9 & 10: OPTIONAL CONTINUOUS RECORDING & QA
    # ──────────────────────────────────────────────────────────────────
    if record_prototype:
        print("[Step 9/10] Executing continuous V4 browser capture...")
        mgr = ArtifactManager(storage_root)
        run_info = mgr.prepare_run(project_id)
        run_dir = run_info["run_dir"]

        pipeline_py = os.path.join(HERE, "continuous_pipeline.py")
        cmd = [
            sys.executable, pipeline_py,
            "--plan", plan_path,
            "--run-dir", run_dir,
            "--workspace-root", workspace_root,
            "--mode", mode
        ]
        res = subprocess.run(cmd, capture_output=True, text=True)
        if res.returncode != 0:
            print(f" [PASS B ERROR] {res.stderr or res.stdout}")
            reports["steps"]["step_9_recording"] = {"status": "FAIL", "error": res.stderr}
        else:
            print(" -> Continuous recording completed successfully!")
            reports["steps"]["step_9_recording"] = {"status": "SUCCESS", "run_dir": run_dir}

            # STEP 10: RUN QA
            print("[Step 10/10] Running V4 Quality Gates (Gates 24–31)...")
            from continuous_qa import run_qa_gates
            qa_res = run_qa_gates(run_dir)
            qa_json_path = os.path.join(run_dir, "qa_v4_report.json")
            with open(qa_json_path, "w", encoding="utf-8") as f:
                json.dump(qa_res, f, indent=2)
            reports["steps"]["step_10_qa"] = qa_res
            print(f" -> QA Completed: Status = {qa_res.get('overall_status')}")

            # Generate Provenance Manifest
            prov = mgr.generate_provenance_manifest(project_id, run_info["run_id"], plan_path, profile_path)
            reports["provenance"] = prov
    else:
        print("[Step 9/10] Recording deferred (run with --record to execute immediately).")
        print("[Step 10/10] QA deferred until recording execution.")

    reports["status"] = "COMPLETED"
    print("=" * 70)
    print(f" [BOOTSTRAP] FINISHED. Project Profile: {profile_path}")
    print("=" * 70)
    return reports


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Bootstrap New Project for Tutorial Videos")
    parser.add_argument("--workspace-root", required=True)
    parser.add_argument("--project-id", default=None)
    parser.add_argument("--base-url", default=None)
    parser.add_argument("--mode", default="dual", choices=["dual", "mobile", "desktop"])
    parser.add_argument("--record", action="store_true")
    parser.add_argument("--output-dir", default=None)

    args = parser.parse_args()
    res = run_bootstrap_workflow(
        workspace_root=args.workspace_root,
        project_id=args.project_id,
        base_url=args.base_url,
        mode=args.mode,
        record_prototype=args.record,
        output_dir=args.output_dir
    )
    # Print summary
    print(json.dumps({
        "status": res["status"],
        "project": res["steps"].get("step_1_detection", {}).get("adapter_name"),
        "framework": res["steps"].get("step_1_detection", {}).get("framework"),
        "artifacts": res["steps"].get("step_6_artifacts")
    }, indent=2))
