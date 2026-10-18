#!/usr/bin/env python
"""
cli.py — Unified Command Line Interface for App Tutorial Video Skill.
Commands:
  bootstrap            Initialize a new project profile and starter plan.
  record               Execute V4 Continuous Browser Video recording.
  qa                   Run 6-Gate V4 Quality Assurance on a recording run.
  preflight            Verify route and selector reachability before recording.
"""

import argparse
import json
import os
import subprocess
import sys
from typing import Any, Dict, List, Optional

HERE = os.path.dirname(os.path.abspath(__file__))
if HERE not in sys.path:
    sys.path.insert(0, HERE)

from bootstrap import run_bootstrap_workflow
from continuous_qa import run_qa_gates
from preflight import validate_plan_selectors
from artifact_manager import ArtifactManager
from profile import load_profile, apply_profile_to_plan


def cmd_bootstrap(args):
    res = run_bootstrap_workflow(
        workspace_root=args.workspace_root,
        project_id=args.project_id,
        base_url=args.base_url,
        mode=args.mode,
        record_prototype=args.record,
        output_dir=args.output_dir
    )
    return 0 if res.get("status") == "COMPLETED" else 1


def resolve_isolated_workspace(plan_path: str, profile_path: Optional[str] = None, user_ws_root: Optional[str] = None) -> str:
    """
    Enforces strict workspace isolation.
    If plan/profile belongs to a project's .tutorial-video directory, that workspace is prioritized
    to prevent accidental cross-project pollution.
    """
    plan_abs = os.path.abspath(plan_path) if plan_path else ""
    detected_ws = None
    if ".tutorial-video" in plan_abs:
        detected_ws = plan_abs.split(".tutorial-video")[0].rstrip("/\\")
    elif profile_path and ".tutorial-video" in os.path.abspath(profile_path):
        detected_ws = os.path.abspath(profile_path).split(".tutorial-video")[0].rstrip("/\\")

    if user_ws_root:
        user_ws = os.path.abspath(user_ws_root)
        if detected_ws and os.path.abspath(detected_ws).lower() != user_ws.lower():
            print(f"[CLI ISOLATION] Auto-correcting workspace mismatch: plan belongs to '{detected_ws}', overriding '{user_ws}' to preserve isolation.")
            return os.path.abspath(detected_ws)
        return user_ws

    return os.path.abspath(detected_ws) if detected_ws else os.path.abspath(os.getcwd())


def cmd_preflight(args):
    ws_root = resolve_isolated_workspace(args.plan, user_ws_root=args.workspace_root)
    report = validate_plan_selectors(args.plan, workspace_root=ws_root)
    print(json.dumps(report, indent=2))
    return 0 if report.get("passed", False) else 1


def cmd_record(args):
    with open(args.plan, "r", encoding="utf-8") as f:
        plan = json.load(f)

    project_id = args.project_id or plan.get("project_id", "default_project")
    profile_path = args.profile
    if profile_path and os.path.isfile(profile_path):
        prof = load_profile(profile_path)
        plan = apply_profile_to_plan(prof, plan)

    # Determine and enforce correct workspace root for isolation
    ws_root = resolve_isolated_workspace(args.plan, profile_path=profile_path, user_ws_root=args.workspace_root)

    # Manage isolated run directory inside target project workspace
    storage_root = args.output_dir or os.path.join(ws_root, ".tutorial-video")
    mgr = ArtifactManager(storage_root)
    run_info = mgr.prepare_run(project_id, run_id=args.run_id)
    run_dir = run_info["run_dir"]

    # Write merged plan into run directory
    effective_plan_path = os.path.join(run_dir, "effective-plan.json")
    with open(effective_plan_path, "w", encoding="utf-8") as f:
        json.dump(plan, f, indent=2, ensure_ascii=False)

    pipeline_py = os.path.join(HERE, "continuous_pipeline.py")
    cmd = [
        sys.executable, pipeline_py,
        "--plan", effective_plan_path,
        "--run-dir", run_dir,
        "--workspace-root", ws_root
    ]
    if args.scenes:
        cmd += ["--scenes", args.scenes]
    if args.headed:
        cmd += ["--headed"]
    if args.playwright:
        cmd += ["--playwright", args.playwright]
    if getattr(args, "bgm", None):
        cmd += ["--bgm", args.bgm]
    if getattr(args, "bgm_genre", None):
        cmd += ["--bgm-genre", args.bgm_genre]
    if getattr(args, "mode", None):
        cmd += ["--mode", args.mode]

    print(f"[CLI] Launching Continuous V4 Pipeline -> {run_dir}")
    res = subprocess.run(cmd)
    if res.returncode != 0:
        return res.returncode

    # Run QA automatically
    if not args.skip_qa:
        print("[CLI] Running V4 Quality Gates (Gates 24–31)...")
        qa_rep = run_qa_gates(run_dir)
        with open(os.path.join(run_dir, "qa_v4_report.json"), "w", encoding="utf-8") as f:
            json.dump(qa_rep, f, indent=2)
        print(f"[CLI] QA Result: {qa_rep.get('overall_status')}")

    # Resolve music config for manifest tracking
    music_cfg = None
    try:
        from audio_mixer import resolve_music_config
        music_cfg = resolve_music_config(
            plan,
            cli_bgm=getattr(args, "bgm", None),
            cli_genre=getattr(args, "bgm_genre", None),
            skill_root=os.path.dirname(HERE)
        )
    except Exception:
        pass

    # Generate Provenance Manifest
    mgr.generate_provenance_manifest(project_id, run_info["run_id"], effective_plan_path, profile_path, music_config=music_cfg)
    print(f"[CLI] Finished recording run. Isolated run directory: {run_dir}")
    return 0


def cmd_preview_audio(args):
    from preview_bgm import generate_preview
    res = generate_preview(
        narration_path=args.narration,
        bgm_path=args.bgm,
        bgm_genre=args.genre,
        out_path=args.out,
        base_volume=args.base_volume,
        ducking_volume=args.ducking_volume,
        target_duration_sec=args.duration
    )
    print(json.dumps(res, indent=2))
    return 0


def cmd_qa(args):
    report = run_qa_gates(args.run_dir)
    print(json.dumps(report, indent=2))
    out_file = os.path.join(args.run_dir, "qa_v4_report.json")
    with open(out_file, "w", encoding="utf-8") as f:
        json.dump(report, f, indent=2)
    print(f"[CLI] QA report saved to {out_file}")
    return 0 if report.get("overall_status") == "PASS" else 1


def main():
    parser = argparse.ArgumentParser(description="App Tutorial Video CLI (V4 Continuous Engine)")
    subparsers = parser.add_subparsers(dest="command", required=True)

    # Subcommand: bootstrap
    sp_boot = subparsers.add_parser("bootstrap", help="Bootstrap a new project for tutorial creation")
    sp_boot.add_argument("--workspace-root", required=True)
    sp_boot.add_argument("--project-id", default=None)
    sp_boot.add_argument("--base-url", default=None)
    sp_boot.add_argument("--mode", default="mobile", choices=["mobile", "desktop"])
    sp_boot.add_argument("--record", action="store_true")
    sp_boot.add_argument("--output-dir", default=None)

    # Subcommand: preflight
    sp_pref = subparsers.add_parser("preflight", help="Validate DOM selectors and route reachability")
    sp_pref.add_argument("--plan", required=True)
    sp_pref.add_argument("--workspace-root", default=None)

    # Subcommand: record
    sp_rec = subparsers.add_parser("record", help="Record video using V4 Continuous Engine")
    sp_rec.add_argument("--plan", required=True)
    sp_rec.add_argument("--profile", default=None)
    sp_rec.add_argument("--project-id", default=None)
    sp_rec.add_argument("--run-id", default=None)
    sp_rec.add_argument("--scenes", default="")
    sp_rec.add_argument("--headed", action="store_true")
    sp_rec.add_argument("--skip-qa", action="store_true")
    sp_rec.add_argument("--workspace-root", default=None)
    sp_rec.add_argument("--output-dir", default=None)
    sp_rec.add_argument("--playwright", default=None)
    sp_rec.add_argument("--bgm", default=None, help="Path to optional background music audio file")
    sp_rec.add_argument("--bgm-genre", default=None, choices=["upbeat_jazz", "ambient"], help="BGM genre preset")
    sp_rec.add_argument("--mode", default=None, choices=["dual", "desktop", "mobile"], help="Execution mode: dual, desktop, or mobile")

    # Subcommand: preview-audio
    sp_prev = subparsers.add_parser("preview-audio", help="Generate fast standalone audio preview with adaptive ducking")
    sp_prev.add_argument("--narration", default=None, help="Path to optional narration WAV/MP3")
    sp_prev.add_argument("--bgm", default=None, help="Path to custom BGM WAV file")
    sp_prev.add_argument("--genre", default="upbeat_jazz", choices=["upbeat_jazz", "ambient"], help="BGM genre preset")
    sp_prev.add_argument("--out", required=True, help="Output preview WAV file path")
    sp_prev.add_argument("--base-volume", type=float, default=None, help="Base music volume level")
    sp_prev.add_argument("--ducking-volume", type=float, default=None, help="Ducked music volume level")
    sp_prev.add_argument("--duration", type=float, default=None, help="Target preview duration in seconds")

    # Subcommand: qa
    sp_qa = subparsers.add_parser("qa", help="Run V4 Quality Assurance Gates (Gates 24-31)")
    sp_qa.add_argument("--run-dir", required=True)

    args = parser.parse_args()
    if args.command == "bootstrap":
        sys.exit(cmd_bootstrap(args))
    elif args.command == "preflight":
        sys.exit(cmd_preflight(args))
    elif args.command == "record":
        sys.exit(cmd_record(args))
    elif args.command == "preview-audio":
        sys.exit(cmd_preview_audio(args))
    elif args.command == "qa":
        sys.exit(cmd_qa(args))


if __name__ == "__main__":
    main()
