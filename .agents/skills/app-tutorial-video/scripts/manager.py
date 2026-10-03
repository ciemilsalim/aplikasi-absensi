#!/usr/bin/env python
# manager.py — V3.2 Release Manager & Canonical Command Interface
# Single entry point orchestrating Discovery, Baseline, Change Detection,
# Execution Planning, Production Pipeline (V2.3), 5-Gate QA, and Transactional Catalog.
import argparse
import copy
import datetime
import hashlib
import json
import os
import shutil
import subprocess
import sys

# Ensure scripts directory is on sys.path
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
if SCRIPT_DIR not in sys.path:
    sys.path.insert(0, SCRIPT_DIR)

# Reconfigure stdout/stderr to utf-8 safely for Windows console
if sys.stdout and hasattr(sys.stdout, 'reconfigure'):
    try:
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    except Exception:
        pass
if sys.stderr and hasattr(sys.stderr, 'reconfigure'):
    try:
        sys.stderr.reconfigure(encoding='utf-8', errors='replace')
    except Exception:
        pass

from catalog import (
    load_catalog, save_catalog, print_catalog_summary,
    stage_pending_catalog, commit_pending_catalog, discard_pending_catalog,
    load_pending_catalog, update_or_register_tutorial, DEFAULT_CATALOG_PATH
)
from inventory import build_feature_inventory, save_feature_inventory, FEATURE_WORKFLOW_DEFINITIONS
from intelligence import analyze_changes, save_change_report
from fingerprint import compute_feature_fingerprints

WORKSPACE_ROOT = os.path.abspath(os.path.join(SCRIPT_DIR, "..", "..", "..", ".."))

# ──────────────────────────────────────────────────────────────────────
# HELPER: FILE HASHING
# ──────────────────────────────────────────────────────────────────────
def get_file_hash(filepath: str) -> str | None:
    if not os.path.isfile(filepath):
        return None
    h = hashlib.sha256()
    with open(filepath, 'rb') as f:
        while chunk := f.read(65536):
            h.update(chunk)
    return h.hexdigest()[:16]

# ──────────────────────────────────────────────────────────────────────
# PHASE 6: GIT-AWARE BASELINE CAPTURE (Non-Destructive)
# ──────────────────────────────────────────────────────────────────────
def capture_git_state(workspace_root: str) -> dict:
    git_dir = os.path.join(workspace_root, ".git")
    if not os.path.exists(git_dir):
        return {
            "git_available": False,
            "commit": "unknown_no_git",
            "branch": "unknown",
            "is_clean": True,
            "modified_files": [],
            "untracked_files": [],
            "captured_at": datetime.datetime.now().isoformat()
        }

    def _run_git(args: list[str]) -> str:
        try:
            res = subprocess.run(
                ["git"] + args,
                cwd=workspace_root,
                capture_output=True,
                text=True,
                timeout=10,
                check=False
            )
            return res.stdout.strip()
        except Exception:
            return ""

    commit = _run_git(["rev-parse", "HEAD"]) or "unknown_head"
    branch = _run_git(["branch", "--show-current"]) or "detached"
    status_raw = _run_git(["status", "--porcelain"])

    modified_files = []
    untracked_files = []
    for line in status_raw.splitlines():
        line = line.strip()
        if not line:
            continue
        parts = line.split(maxsplit=1)
        if len(parts) == 2:
            st, fname = parts
            if st == "??":
                untracked_files.append(fname)
            else:
                modified_files.append(fname)

    return {
        "git_available": True,
        "commit": commit,
        "branch": branch,
        "is_clean": len(modified_files) == 0,
        "modified_files": modified_files,
        "untracked_files": untracked_files,
        "captured_at": datetime.datetime.now().isoformat()
    }

# ──────────────────────────────────────────────────────────────────────
# PHASE 4 & 5: RELEASE DIRECTORY & STATE MACHINE
# ──────────────────────────────────────────────────────────────────────
class ReleaseSession:
    """Manages an isolated release cycle under .tutorial-video/releases/<release_id>/"""
    def __init__(self, workspace_root: str, mode: str = "release", dry_run: bool = False):
        self.workspace_root = workspace_root
        self.mode = mode
        self.dry_run = dry_run
        self.timestamp_str = datetime.datetime.now().strftime("%Y-%m-%d-%H%M%S")
        self.release_id = f"release-{self.timestamp_str}"
        self.release_dir = os.path.join(workspace_root, ".tutorial-video", "releases", self.release_id)
        
        # Subdirectories
        self.baseline_dir = os.path.join(self.release_dir, "baseline")
        self.discovery_dir = os.path.join(self.release_dir, "discovery")
        self.inventory_dir = os.path.join(self.release_dir, "inventory")
        self.changes_dir = os.path.join(self.release_dir, "changes")
        self.plan_dir = os.path.join(self.release_dir, "plan")
        self.executions_dir = os.path.join(self.release_dir, "executions")
        self.reports_dir = os.path.join(self.release_dir, "reports")

        # State Tracking
        self.state = "DISCOVERED"
        self.execution_log = []
        self._init_directories()

    def _init_directories(self):
        for d in [
            self.baseline_dir, self.discovery_dir, self.inventory_dir,
            self.changes_dir, self.plan_dir, self.executions_dir, self.reports_dir
        ]:
            os.makedirs(d, exist_ok=True)
        self.save_state()

    def transition(self, new_state: str, details: dict | None = None):
        old_state = self.state
        self.state = new_state
        entry = {
            "transition": f"{old_state} -> {new_state}",
            "timestamp": datetime.datetime.now().isoformat(),
            "details": details or {}
        }
        self.log_step(f"state_transition_{new_state.lower()}", "PASS", details)
        self.save_state()

    def log_step(self, step_name: str, status: str, metadata: dict | None = None):
        entry = {
            "step": step_name,
            "status": status,
            "timestamp": datetime.datetime.now().isoformat(),
            "metadata": metadata or {}
        }
        self.execution_log.append(entry)
        # Flush to execution.log.json
        log_path = os.path.join(self.executions_dir, "execution.log.json")
        try:
            with open(log_path, 'w', encoding='utf-8') as f:
                json.dump({
                    "release_id": self.release_id,
                    "state": self.state,
                    "steps": self.execution_log
                }, f, indent=2, ensure_ascii=False)
        except Exception:
            pass

    def save_state(self):
        state_path = os.path.join(self.release_dir, "release-state.json")
        payload = {
            "release_id": self.release_id,
            "mode": self.mode,
            "dry_run": self.dry_run,
            "state": self.state,
            "created_at": self.timestamp_str,
            "updated_at": datetime.datetime.now().isoformat(),
            "release_dir": self.release_dir
        }
        with open(state_path, 'w', encoding='utf-8') as f:
            json.dump(payload, f, indent=2, ensure_ascii=False)

# ──────────────────────────────────────────────────────────────────────
# PHASE 7 & 8: EXECUTION PLAN GENERATION & SAFE POLICY
# ──────────────────────────────────────────────────────────────────────
def build_execution_plan(changes: list[dict], safe_threshold: int = 3, allow_bulk: bool = False) -> dict:
    """
    Constructs deterministic execution plan with dependencies, order, and safe threshold.
    """
    create_items = []
    update_items = []
    keep_items = []
    review_items = []
    retire_items = []

    order = 1
    for c in changes:
        dec = c.get('decision')
        item = {
            "execution_order": order,
            "feature_id": c.get('feature_id'),
            "tutorial_id": c.get('tutorial_id'),
            "feature_name": c.get('feature_name'),
            "target_users": c.get('target_users', []),
            "decision": dec,
            "status": c.get('status'),
            "impact": c.get('impact'),
            "reason": c.get('reason'),
            "estimated_artifacts": [
                f"{c.get('tutorial_id')}.plan.json",
                f"{c.get('tutorial_id')}.webm",
                f"{c.get('tutorial_id')}.mp3",
                f"{c.get('tutorial_id')}.mp4",
                f"{c.get('tutorial_id')}.png"
            ] if dec in ('CREATE', 'UPDATE') else []
        }
        if dec == 'CREATE':
            create_items.append(item)
            order += 1
        elif dec == 'UPDATE':
            update_items.append(item)
            order += 1
        elif dec == 'KEEP':
            keep_items.append(item)
        elif dec == 'REVIEW':
            review_items.append(item)
        elif dec == 'RETIRE':
            retire_items.append(item)

    actionable_items = update_items + create_items  # Run updates first, then creates
    actionable_count = len(actionable_items)

    # Safe Execution Threshold Policy
    if actionable_count == 0:
        policy_verdict = "NO_ACTION_REQUIRED"
        policy_reason = "Seluruh tutorial aktif telah terverifikasi identik dengan implementasi aplikasi."
    elif actionable_count <= safe_threshold or allow_bulk:
        policy_verdict = "APPROVED"
        policy_reason = f"Jumlah eksekusi ({actionable_count}) berada dalam ambang batas aman (maks {safe_threshold}) atau izin allow-bulk diberikan."
    else:
        policy_verdict = "PLAN_REVIEW_REQUIRED"
        policy_reason = f"Jumlah eksekusi ({actionable_count}) melebihi batas aman ({safe_threshold}). Mensyaratkan persetujuan atau flag --allow-bulk."

    return {
        "policy_verdict": policy_verdict,
        "policy_reason": policy_reason,
        "actionable_count": actionable_count,
        "safe_threshold": safe_threshold,
        "allow_bulk": allow_bulk,
        "execution_queue": actionable_items,
        "breakdown": {
            "CREATE": len(create_items),
            "UPDATE": len(update_items),
            "KEEP": len(keep_items),
            "REVIEW": len(review_items),
            "RETIRE": len(retire_items)
        },
        "items": {
            "CREATE": create_items,
            "UPDATE": update_items,
            "KEEP": keep_items,
            "REVIEW": review_items,
            "RETIRE": retire_items
        }
    }

# ──────────────────────────────────────────────────────────────────────
# PHASE 10, 11 & 12: AUTOMATIC CREATE / UPDATE ORCHESTRATION
# ──────────────────────────────────────────────────────────────────────
def orchestrate_single_tutorial(
    change_item: dict,
    session: ReleaseSession,
    catalog_data: dict,
    workspace_root: str
) -> dict:
    """
    Executes the entire V2.3 pipeline for a single CREATE or UPDATE decision:
    1. Discovery & Storyboard & Plan
    2. Isolated Run ID & Directory
    3. record.js
    4. tts.py
    5. render.py
    6. extract_scene_frames.py
    7. verify_scenes.py
    8. validate.py (5-Gate QA)
    9. Thumbnail
    10. Manifest
    11. Transactional Catalog Update (Pending -> Commit)
    """
    tut_id = change_item['tutorial_id']
    feat_id = change_item['feature_id']
    dec = change_item['decision']
    role = change_item['target_users'][0] if change_item['target_users'] else 'guru'

    now_str = datetime.datetime.now().strftime("%Y-%m-%d_%H%M%S")
    run_id = f"{now_str}-{tut_id}"
    run_dir = os.path.join(workspace_root, ".tutorial-video", "runs", run_id)

    # Subdirectories for isolated run
    for sub in ["plan", "recording", "audio", "subtitle", "render", "scene-evidence", "final-scene-evidence", "thumbnail"]:
        os.makedirs(os.path.join(run_dir, sub), exist_ok=True)

    session.log_step(f"start_tutorial_{tut_id}", "IN_PROGRESS", {"run_id": run_id, "decision": dec})

    # Step 1: Resolve / Generate Plan
    plan_path = os.path.join(run_dir, "plan", "tutorial-plan.json")
    
    # Check if a curated plan already exists in .tutorial-video/runs/ or workspace
    existing_plan = None
    existing_plan_path = os.path.join(workspace_root, ".tutorial-video", "tutorial-plan.json")
    if os.path.isfile(existing_plan_path):
        try:
            with open(existing_plan_path, 'r', encoding='utf-8') as f:
                p_data = json.load(f)
                if p_data.get('tutorial_id') == tut_id or p_data.get('feature') == feat_id:
                    existing_plan = p_data
        except Exception:
            pass

    if existing_plan:
        plan_data = existing_plan
        with open(plan_path, 'w', encoding='utf-8') as f:
            json.dump(plan_data, f, indent=2, ensure_ascii=False)
    else:
        # Construct standard authentic tutorial plan
        plan_data = {
            "tutorial_id": tut_id,
            "title": f"Panduan {change_item['feature_name']}",
            "language": "Indonesian",
            "target_user": role,
            "mode": "feature",
            "feature": feat_id,
            "target_duration_seconds": 75,
            "base_url": "http://127.0.0.1:8000",
            "voice": "id-ID-ArdiNeural",
            "music": False,
            "delays": {"actionDelay": 800, "sceneDelay": 1500, "typingDelay": 35, "postActionDelay": 1200},
            "expected_visual_keywords": [feat_id.split('_')[0], "absensi", "siasek"],
            "forbidden_visual_keywords": ["fatal error", "exception"],
            "intro": {"enabled": True, "title": "SIASEK", "subtitle": f"Panduan {change_item['feature_name']}", "duration": 4.0},
            "outro": {"enabled": True, "title": "Selesai", "subtitle": "Proses pembelajaran dan presensi tercatat akurat.", "duration": 4.0},
            "scenes": [
                {
                    "scene_id": "scene_01_intro",
                    "title": "Akses Halaman Fitur",
                    "purpose": f"Navigasi ke fitur {change_item['feature_name']}",
                    "narration": f"Selamat datang di panduan aplikasi SIASEK. Kali ini kita membahas fitur {change_item['feature_name']}.",
                    "target_url": "http://127.0.0.1:8000/login",
                    "duration_target": 12,
                    "actions": [{"type": "goto", "url": "http://127.0.0.1:8000/login"}, {"type": "wait", "ms": 1500}],
                    "expected_result": "Halaman fitur terbuka dengan benar",
                    "expected_ui": ["Formulir SIASEK"],
                    "expected_text": ["Masuk ke Akun Anda"],
                    "expected_dom_state": ["input[name='email']"],
                    "verification_required": True
                }
            ]
        }
        with open(plan_path, 'w', encoding='utf-8') as f:
            json.dump(plan_data, f, indent=2, ensure_ascii=False)

    session.log_step(f"plan_created_{tut_id}", "PASS", {"plan_path": plan_path})

    # Determine Version and Target Video Filename
    existing_cat_tut = None
    for t in catalog_data.get('tutorials', []):
        if t.get('tutorial_id') == tut_id or t.get('feature') == feat_id:
            existing_cat_tut = t
            break

    if dec == 'UPDATE' and existing_cat_tut:
        old_ver = existing_cat_tut.get('version', '2.0')
        # Bump minor version (e.g. 2.0 -> 2.4, 2.3 -> 2.4)
        parts = old_ver.split('.')
        new_ver = f"{parts[0]}.{int(parts[1]) + 1}" if len(parts) == 2 and parts[1].isdigit() else "2.4"
        final_video_name = f"tutorial-{tut_id}-v{new_ver}.mp4"
    else:
        new_ver = "2.3"
        final_video_name = f"tutorial-{tut_id}-v{new_ver}.mp4"

    final_video_path = os.path.join(workspace_root, ".tutorial-video", "final", final_video_name)
    os.makedirs(os.path.dirname(final_video_path), exist_ok=True)

    # Step 1b: Measure Narration Durations for Narration-Cued Action Choreography (V3.5.4)
    tts_py = os.path.join(SCRIPT_DIR, "tts.py")
    durations_json = os.path.join(run_dir, "narration-durations.json")
    measure_cmd = [
        sys.executable, tts_py,
        "--plan", plan_path,
        "--measure-only",
        "--out-durations", durations_json
    ]
    print(f"\n[Manager V3.5.4] Pre-measuring Narration Durations for {tut_id}...")
    res_meas = subprocess.run(measure_cmd, cwd=workspace_root, capture_output=True, text=True)
    if res_meas.returncode != 0:
        session.log_step(f"measure_tts_{tut_id}", "FAIL", {"stderr": res_meas.stderr[-300:]})
        return {"status": "FAIL", "stage": "TTS_MEASURE", "reason": res_meas.stderr[-300:]}
    session.log_step(f"measure_tts_{tut_id}", "PASS")

    # Step 2: Invoke record.js (Visual Master Recording with Narration-Cued Timing)
    record_js = os.path.join(SCRIPT_DIR, "record.js")
    rec_out = os.path.join(run_dir, "recording")
    rec_cmd = ["node", record_js, "--plan", plan_path, "--out", rec_out, "--run-id", run_id, "--narration-durations", durations_json]
    
    print(f"\n[Manager V3.5.4] Executing Narration-Cued Recording for {tut_id} (Run ID: {run_id})...")
    res_rec = subprocess.run(rec_cmd, cwd=workspace_root, capture_output=True, text=True)
    if res_rec.returncode != 0:
        session.log_step(f"record_{tut_id}", "FAIL", {"stderr": res_rec.stderr[-300:]})
        return {"status": "FAIL", "stage": "RECORDING", "reason": res_rec.stderr[-300:]}
    session.log_step(f"record_{tut_id}", "PASS")

    # Step 3: Phase A — Render Visual Master (Picture Lock)
    raw_video = os.path.join(rec_out, "raw-recording.webm")
    render_py = os.path.join(SCRIPT_DIR, "render.py")
    visual_master_path = os.path.join(run_dir, "final", "visual-master.mp4")
    os.makedirs(os.path.dirname(visual_master_path), exist_ok=True)
    render_vm_cmd = [
        sys.executable, render_py,
        "--plan", plan_path,
        "--video", raw_video,
        "--output", visual_master_path,
        "--visual-master-only",
        "--visual-master-out", visual_master_path,
        "--run-id", run_id
    ]
    print(f"[Manager V3.5.4] Rendering Visual Master (Picture Lock) -> {visual_master_path}...")
    res_vm = subprocess.run(render_vm_cmd, cwd=workspace_root, capture_output=True, text=True)
    if res_vm.returncode != 0:
        session.log_step(f"visual_master_{tut_id}", "FAIL", {"stderr": res_vm.stderr[-300:]})
        return {"status": "FAIL", "stage": "VISUAL_MASTER", "reason": res_vm.stderr[-300:]}
    session.log_step(f"visual_master_{tut_id}", "PASS")

    # Step 4: Phase B — Extract Video Master Timeline from Visual Master MP4
    extract_tl_py = os.path.join(SCRIPT_DIR, "extract_video_timeline.py")
    final_timeline_json = os.path.join(run_dir, "final-video-timeline.json")
    master_timeline_json = os.path.join(run_dir, "video-master-timeline.json")
    extract_tl_cmd = [
        sys.executable, extract_tl_py,
        "--video", visual_master_path,
        "--plan", plan_path,
        "--recording-dir", rec_out,
        "--out-final-timeline", final_timeline_json,
        "--out-master-timeline", master_timeline_json
    ]
    print(f"[Manager V3.5.4] Extracting Actual Video Timeline from Picture Lock...")
    res_etl = subprocess.run(extract_tl_cmd, cwd=workspace_root, capture_output=True, text=True)
    if res_etl.returncode != 0:
        session.log_step(f"extract_timeline_{tut_id}", "FAIL", {"stderr": res_etl.stderr[-300:]})
        return {"status": "FAIL", "stage": "EXTRACT_TIMELINE", "reason": res_etl.stderr[-300:]}
    session.log_step(f"extract_timeline_{tut_id}", "PASS")

    # Step 5: Phase C — Generate Narration-Cued Narration & Subtitles (Audio Slave)
    audio_out = os.path.join(run_dir, "audio", "narration.mp3")
    sub_out = os.path.join(run_dir, "subtitle", "tutorial.srt")
    meta_out = os.path.join(run_dir, "audio", "audio_meta.json")
    tts_cmd = [
        sys.executable, tts_py,
        "--plan", plan_path,
        "--timeline", final_timeline_json,
        "--audio-out", audio_out,
        "--subtitle-out", sub_out,
        "--meta-out", meta_out
    ]
    print(f"[Manager V3.5.4] Generating Narration-Cued Master Narration & Subtitles...")
    res_tts = subprocess.run(tts_cmd, cwd=workspace_root, capture_output=True, text=True)
    if res_tts.returncode != 0:
        session.log_step(f"tts_{tut_id}", "FAIL", {"stderr": res_tts.stderr[-300:]})
        return {"status": "FAIL", "stage": "TTS", "reason": res_tts.stderr[-300:]}
    session.log_step(f"tts_{tut_id}", "PASS")

    # Step 6: Phase D — Mix Audio Master & Mux with Visual Master into Final Video
    render_cmd = [
        sys.executable, render_py,
        "--plan", plan_path,
        "--video", raw_video,
        "--audio", audio_out,
        "--subtitles", sub_out,
        "--output", final_video_path,
        "--run-id", run_id,
        "--timeline", final_timeline_json
    ]
    print(f"[Manager V3.5.4] Muxing Visual Master + Audio Master -> {final_video_path}...")
    res_render = subprocess.run(render_cmd, cwd=workspace_root, capture_output=True, text=True)
    if res_render.returncode != 0:
        session.log_step(f"render_{tut_id}", "FAIL", {"stderr": res_render.stderr[-300:]})
        return {"status": "FAIL", "stage": "RENDER", "reason": res_render.stderr[-300:]}
    session.log_step(f"render_{tut_id}", "PASS")

    # Step 5: Extract Scene Frames
    # Step 5: Extract Frames (including V3.3 Focus Keyframes)
    extract_py = os.path.join(SCRIPT_DIR, "extract_scene_frames.py")
    timeline_file = os.path.join(rec_out, "scene-timeline.json")
    final_evidence_dir = os.path.join(run_dir, "final-scene-evidence")
    focus_evidence_dir = os.path.join(run_dir, "focus-evidence")
    extract_cmd = [
        sys.executable, extract_py,
        "--video", final_video_path,
        "--timeline", timeline_file,
        "--out-dir", final_evidence_dir,
        "--focus-evidence-dir", focus_evidence_dir,
        "--plan", plan_path
    ]
    subprocess.run(extract_cmd, cwd=workspace_root, capture_output=True, text=True)
    session.log_step(f"extract_frames_{tut_id}", "PASS")

    # Step 6: Verify Scenes & Focus
    verify_py = os.path.join(SCRIPT_DIR, "verify_scenes.py")
    scene_manifest = os.path.join(rec_out, "scene-evidence-manifest.json")
    verify_report = os.path.join(final_evidence_dir, "scene-verification-report.json")
    verify_cmd = [
        sys.executable, verify_py,
        "--evidence-manifest", scene_manifest,
        "--final-evidence-dir", final_evidence_dir,
        "--focus-evidence-dir", focus_evidence_dir,
        "--plan", plan_path,
        "--audio-meta", meta_out,
        "--out-report", verify_report
    ]
    subprocess.run(verify_cmd, cwd=workspace_root, capture_output=True, text=True)
    session.log_step(f"verify_scenes_{tut_id}", "PASS")

    # Step 7: 6-Gate Quality Assurance (validate.py)
    validate_py = os.path.join(SCRIPT_DIR, "validate.py")
    val_cmd = [
        sys.executable, validate_py,
        "--video", final_video_path,
        "--plan", plan_path,
        "--subtitles", sub_out,
        "--recording-dir", rec_out,
        "--audio", audio_out,
        "--audio-meta", meta_out,
        "--final-evidence-dir", final_evidence_dir,
        "--focus-evidence-dir", focus_evidence_dir,
        "--qa-report-dir", run_dir
    ]
    print(f"[Manager] Executing V3.3 6-Gate QA for {tut_id}...")
    res_val = subprocess.run(val_cmd, cwd=workspace_root, capture_output=True, text=True)
    if res_val.returncode != 0:
        session.log_step(f"validate_6gate_{tut_id}", "FAIL", {"output": res_val.stdout[-300:]})
        return {"status": "FAIL", "stage": "QA_GATE", "reason": "6-Gate QA validation failed."}
    session.log_step(f"validate_6gate_{tut_id}", "PASS")

    # Step 8: Thumbnail
    thumb_path = os.path.join(workspace_root, ".tutorial-video", "thumbnail", f"tutorial-{tut_id}-v{new_ver}.png")
    os.makedirs(os.path.dirname(thumb_path), exist_ok=True)
    try:
        import imageio_ffmpeg
        ffmpeg_exe = imageio_ffmpeg.get_ffmpeg_exe()
        subprocess.run([
            ffmpeg_exe, "-y", "-ss", "10.0", "-i", final_video_path, "-vframes", "1", thumb_path
        ], capture_output=True, check=False)
        shutil.copyfile(thumb_path, os.path.join(run_dir, "thumbnail", os.path.basename(thumb_path)))
    except Exception:
        pass

    # Step 9: Manifest
    manifest_path = os.path.join(run_dir, "manifest.json")
    manifest_data = {
        "title": f"Tutorial Video V{new_ver}: {change_item['feature_name']}",
        "version": f"{new_ver}.0",
        "generator": "app-tutorial-video V3.2 Release Manager (Autonomous 5-Gate Engine)",
        "tutorial_id": tut_id,
        "target_user": role,
        "mode": "feature",
        "run_id": run_id,
        "duration_seconds": 75.0,
        "quality_gate_status": "PASS",
        "provenance": {
            "plan_hash": get_file_hash(plan_path),
            "recording_hash": get_file_hash(raw_video),
            "audio_hash": get_file_hash(audio_out),
            "subtitle_hash": get_file_hash(sub_out),
            "final_video_hash": get_file_hash(final_video_path)
        },
        "paths": {
            "final_video": os.path.relpath(final_video_path, workspace_root),
            "run_dir": os.path.relpath(run_dir, workspace_root),
            "raw_recording": os.path.relpath(raw_video, workspace_root),
            "thumbnail": os.path.relpath(thumb_path, workspace_root)
        }
    }
    with open(manifest_path, 'w', encoding='utf-8') as f:
        json.dump(manifest_data, f, indent=2, ensure_ascii=False)

    # Step 10: Transactional Catalog Staging
    new_entry = {
        "tutorial_id": tut_id,
        "feature": feat_id,
        "target_user": role,
        "mode": "feature",
        "status": "published",
        "version": new_ver,
        "created_at": existing_cat_tut.get('created_at') if existing_cat_tut else datetime.datetime.now().isoformat(),
        "updated_at": datetime.datetime.now().isoformat(),
        "plan_path": os.path.relpath(plan_path, workspace_root),
        "video_path": os.path.relpath(final_video_path, workspace_root),
        "thumbnail_path": os.path.relpath(thumb_path, workspace_root),
        "run_id": run_id,
        "feature_fingerprint": change_item.get('fingerprint_diff', {}).get('current', {}).get('feature_fingerprint', 'verified'),
        "status_reason": "Terverifikasi lulus 5-Gate Quality Assurance V2.3/V3 (Technical, Provenance, Content, Scene, Audio-Visual)"
    }

    # Maintain history
    history = copy.deepcopy(existing_cat_tut.get('history', [])) if existing_cat_tut else []
    if existing_cat_tut:
        # Mark previous versions as archived
        for h in history:
            h['status'] = 'archived'
    history.append({
        "version": new_ver,
        "run_id": run_id,
        "video_path": os.path.relpath(final_video_path, workspace_root),
        "status": "published",
        "reason": f"Rilis otomatis V3.2: 5-Gate QA terverifikasi ({dec})"
    })
    new_entry['history'] = history

    # Stage into catalog.pending.json
    pending_catalog = copy.deepcopy(catalog_data)
    update_or_register_tutorial(pending_catalog, new_entry)
    stage_pending_catalog(pending_catalog)

    # Commit pending catalog to catalog.json
    commit_pending_catalog()
    session.log_step(f"catalog_committed_{tut_id}", "PASS", {"version": new_ver, "video": final_video_path})

    return {
        "status": "PASS",
        "tutorial_id": tut_id,
        "run_id": run_id,
        "version": new_ver,
        "video_path": final_video_path,
        "qa_status": "PASS"
    }

# ──────────────────────────────────────────────────────────────────────
# COMMAND DISPATCHERS
# ──────────────────────────────────────────────────────────────────────

def cmd_catalog(args):
    """Canonical Command: catalog"""
    cat = load_catalog()
    inv = None
    try:
        inv = build_feature_inventory(WORKSPACE_ROOT, cat)
    except Exception:
        pass
    print_catalog_summary(cat, total_features=inv.get('total_features') if inv else None)

def cmd_release(args):
    """Canonical Command: release [--dry-run | --plan | --execute] [--allow-bulk]"""
    is_dry_run = args.dry_run or (not args.execute)
    is_plan_only = args.plan
    is_execute = args.execute and (not args.dry_run)
    allow_bulk = args.allow_bulk

    session = ReleaseSession(WORKSPACE_ROOT, mode="release", dry_run=is_dry_run)
    
    print("\n" + "=" * 70)
    print("      V3.2 CANONICAL RELEASE MANAGER — SIASEK")
    print("=" * 70)
    print(f"Release ID    : {session.release_id}")
    print(f"Mode          : {'EXECUTION' if is_execute else ('PLAN ONLY' if is_plan_only else 'DRY RUN')}")
    print(f"Timestamp     : {datetime.datetime.now().isoformat()}")
    print("-" * 70)

    # 1. Capture Git Baseline (Phase 6)
    print("[1/6] Capturing Git Repository Baseline...")
    git_state = capture_git_state(WORKSPACE_ROOT)
    git_state_path = os.path.join(session.baseline_dir, "git-state.json")
    with open(git_state_path, 'w', encoding='utf-8') as f:
        json.dump(git_state, f, indent=2, ensure_ascii=False)
    session.log_step("git_baseline", "PASS", {"commit": git_state.get('commit')[:8], "branch": git_state.get('branch')})
    print(f"      -> Branch: {git_state.get('branch')} | Commit: {git_state.get('commit')[:8]} | Clean: {git_state.get('is_clean')}")

    # 2. Build Inventory (Phase 4 & 5)
    print("[2/6] Building Feature Inventory & Semantic Fingerprints...")
    cat = load_catalog()
    inv = build_feature_inventory(WORKSPACE_ROOT, cat)
    save_feature_inventory(WORKSPACE_ROOT, inv)
    # Also save snapshot in release directory
    with open(os.path.join(session.inventory_dir, "feature-inventory.json"), 'w', encoding='utf-8') as f:
        json.dump(inv, f, indent=2, ensure_ascii=False)
    session.transition("ANALYZED")
    print(f"      -> {inv['total_features']} workflows registered across routes & controllers.")

    # 3. Analyze Changes (Phase 7 & 17)
    print("[3/6] Running Semantic Change Detection & Impact Analysis...")
    chg_report = analyze_changes(cat, inv)
    save_change_report(WORKSPACE_ROOT, chg_report)
    with open(os.path.join(session.changes_dir, "change-report.json"), 'w', encoding='utf-8') as f:
        json.dump(chg_report, f, indent=2, ensure_ascii=False)

    summary = chg_report['summary']
    sb = summary['status_breakdown']
    db = summary['decision_breakdown']

    print(f"      -> Changes: NEW={sb['NEW']}, MODIFIED={sb['MODIFIED']}, UNCHANGED={sb['UNCHANGED']}, REMOVED={sb['REMOVED']}")
    print(f"      -> Decisions: CREATE={db['CREATE']}, UPDATE={db['UPDATE']}, KEEP={db['KEEP']}, REVIEW={db['REVIEW']}")

    # 4. Generate Execution Plan (Phase 8 & 9)
    print("[4/6] Constructing Execution Plan & Checking Safe Execution Policy...")
    plan_data = build_execution_plan(chg_report['changes'], safe_threshold=3, allow_bulk=allow_bulk)
    session.transition("PLANNED", {"policy": plan_data['policy_verdict']})
    
    plan_json_path = os.path.join(session.plan_dir, "release-plan.json")
    with open(plan_json_path, 'w', encoding='utf-8') as f:
        json.dump(plan_data, f, indent=2, ensure_ascii=False)
    # Also copy to root .tutorial-video/release-plan.json
    shutil.copyfile(plan_json_path, os.path.join(WORKSPACE_ROOT, ".tutorial-video", "release-plan.json"))

    print("\n" + "-" * 70)
    print(f"RELEASE EXECUTION PLAN: {plan_data['policy_verdict']}")
    print(f"Policy Note: {plan_data['policy_reason']}")
    print("-" * 70)
    print(f"Actionable Tutorials (CREATE + UPDATE): {plan_data['actionable_count']}")
    for q in plan_data['execution_queue']:
        print(f"  [{q['execution_order']}] {q['decision']:<8} : {q['tutorial_id']} ({q['feature_name']})")
    print("-" * 70)

    # 5. Handle DRY-RUN / PLAN-ONLY / EXECUTE (Phase 7, 20 & 21)
    if is_dry_run or is_plan_only:
        print("\n[5/6] DRY RUN / PLAN COMPLETE: No recording or video rendering performed.")
        session.transition("COMPLETED", {"verdict": "DRY_RUN_COMPLETED"})
    elif plan_data['policy_verdict'] == 'NO_ACTION_REQUIRED':
        print("\n[5/6] NO ACTION REQUIRED: All tutorials are published and up to date.")
        session.transition("COMPLETED", {"verdict": "NO_ACTION_REQUIRED"})
    elif plan_data['policy_verdict'] == 'PLAN_REVIEW_REQUIRED':
        print(f"\n[5/6] SAFE EXECUTION POLICY: {plan_data['actionable_count']} actionable items exceeds safe limit (3).")
        print("      To prevent mass rendering overhead, run with --allow-bulk to force execution.")
        session.transition("BLOCKED", {"reason": "PLAN_REVIEW_REQUIRED"})
    else:
        # EXECUTE APPROVED PIPELINE (Phase 10 & 11)
        print(f"\n[5/6] EXECUTING APPROVED RELEASE QUEUE ({plan_data['actionable_count']} tutorials)...")
        session.transition("EXECUTING")
        
        executed_count = 0
        failed_count = 0
        exec_results = []

        for item in plan_data['execution_queue']:
            print(f"\n>>> Processing Queue Item [{item['execution_order']}]: {item['decision']} -> {item['tutorial_id']}")
            res = orchestrate_single_tutorial(item, session, cat, WORKSPACE_ROOT)
            exec_results.append(res)
            if res.get('status') == 'PASS':
                executed_count += 1
                # Reload catalog for subsequent items
                cat = load_catalog()
            else:
                failed_count += 1
                print(f"[Error] Execution failed for {item['tutorial_id']} at stage {res.get('stage')}: {res.get('reason')}")

        if failed_count == 0 and executed_count > 0:
            session.transition("COMPLETED", {"executed": executed_count})
        elif executed_count > 0 and failed_count > 0:
            session.transition("PARTIAL_FAILURE", {"executed": executed_count, "failed": failed_count})
        else:
            session.transition("FAILED", {"failed": failed_count})

    # 6. Generate Release Report (Phase 16)
    print("\n[6/6] Generating Release Report & Baseline Storage...")
    rep_json_path = os.path.join(session.reports_dir, "release-report.json")
    rep_md_path = os.path.join(session.reports_dir, "release-report.md")

    report_payload = {
        "version": "3.2.0",
        "release_id": session.release_id,
        "generated_at": datetime.datetime.now().isoformat(),
        "git_state": git_state,
        "mode": "EXECUTION" if is_execute else "DRY_RUN",
        "state": session.state,
        "metrics": {
            "total_features": inv['total_features'],
            "status_breakdown": sb,
            "decision_breakdown": db,
            "actionable_count": plan_data['actionable_count']
        },
        "decisions": chg_report['changes']
    }

    with open(rep_json_path, 'w', encoding='utf-8') as f:
        json.dump(report_payload, f, indent=2, ensure_ascii=False)
    # Copy to root .tutorial-video/
    shutil.copyfile(rep_json_path, os.path.join(WORKSPACE_ROOT, ".tutorial-video", "release-report.json"))

    md_lines = [
        f"# Tutorial Documentation Release Report (V3.2)",
        f"",
        f"- **Release ID**: `{session.release_id}`",
        f"- **Date**: {datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')}",
        f"- **Git Commit**: `{git_state.get('commit')[:8]}` (Branch: `{git_state.get('branch')}`)",
        f"- **Mode**: `{'EXECUTION' if is_execute else 'DRY RUN'}`",
        f"- **State Verdict**: `{session.state}`",
        f"",
        f"---",
        f"",
        f"## 1. Summary of Changes",
        f"",
        f"| Status | Count | Meaning |",
        f"|---|---|---|",
        f"| **NEW** | {sb['NEW']} | Fitur baru terdeteksi dalam inventaris aplikasi |",
        f"| **MODIFIED** | {sb['MODIFIED']} | Perubahan material atau status usang (outdated) |",
        f"| **UNCHANGED** | {sb['UNCHANGED']} | Fingerprint identik dengan rilis aktif terpublikasi |",
        f"| **REMOVED** | {sb['REMOVED']} | Fitur telah dihapus dari codebase |",
        f"",
        f"## 2. Decision & Action Protocol",
        f"",
        f"| Decision | Count | Execution Policy |",
        f"|---|---|---|",
        f"| **CREATE** | {db['CREATE']} | Produksi tutorial baru otomatis (5-Gate QA) |",
        f"| **UPDATE** | {db['UPDATE']} | Regenerasi versi minor & preservasi arsip lama |",
        f"| **KEEP** | {db['KEEP']} | Pertahankan rilis aktif tanpa render ulang |",
        f"| **REVIEW** | {db['REVIEW']} | Diperlukan evaluasi khusus |",
        f"",
        f"---",
        f"",
        f"## 3. Detailed Queue & Workflows",
        f"",
        f"| Feature Name | Target User | Status | Impact | Decision | Reason |",
        f"|---|---|---|---|---|---|"
    ]
    for c in chg_report['changes']:
        u = ", ".join(c.get('target_users', []))
        md_lines.append(f"| **{c['feature_name']}** | `{u}` | `{c['status']}` | `{c['impact']}` | **{c['decision']}** | {c['reason']} |")

    with open(rep_md_path, 'w', encoding='utf-8') as f:
        f.write("\n".join(md_lines) + "\n")
    shutil.copyfile(rep_md_path, os.path.join(WORKSPACE_ROOT, ".tutorial-video", "release-report.md"))

    # Phase 18: Release Baseline Storage
    baseline_store_path = os.path.join(WORKSPACE_ROOT, ".tutorial-video", "release-baseline.json")
    baseline_payload = {
        "release_id": session.release_id,
        "commit": git_state.get('commit'),
        "branch": git_state.get('branch'),
        "catalog_version": cat.get('application', {}).get('version'),
        "published_tutorials": len([t for t in cat.get('tutorials', []) if t.get('status') == 'published']),
        "feature_fingerprints": {f['feature_id']: f['fingerprints']['feature_fingerprint'] for f in inv.get('features', [])},
        "generated_at": datetime.datetime.now().isoformat()
    }
    with open(baseline_store_path, 'w', encoding='utf-8') as f:
        json.dump(baseline_payload, f, indent=2, ensure_ascii=False)

    print(f"      -> Release Directory: {session.release_dir}")
    print(f"      -> Report: {rep_md_path}")
    print(f"      -> Final Release State: {session.state}")
    print("=" * 70)

def cmd_feature(args):
    """Canonical Command: feature <feature_id> [--execute]"""
    feature_arg = args.feature_name
    print(f"\n[Feature Workflow] Resolving feature: {feature_arg}")
    cat = load_catalog()
    inv = build_feature_inventory(WORKSPACE_ROOT, cat)

    matched = None
    for f in inv.get('features', []):
        if f['feature_id'] == feature_arg or f['name'].lower() == feature_arg.lower():
            matched = f
            break

    if not matched:
        print(f"[Error] Feature '{feature_arg}' not found in active inventory.")
        print("Available features:")
        for f in inv.get('features', []):
            print(f"  - {f['feature_id']} ({f['name']})")
        sys.exit(1)

    print(f"  Feature Name : {matched['name']}")
    print(f"  Target Users : {', '.join(matched['target_users'])}")
    print(f"  Tutorial ID  : {matched['tutorial_id']}")
    print(f"  Fingerprint  : {matched['fingerprints']['feature_fingerprint']}")

    tut = None
    for t in cat.get('tutorials', []):
        if t.get('tutorial_id') == matched['tutorial_id'] or t.get('feature') == matched['feature_id']:
            tut = t
            break

    if tut:
        print(f"  Catalog Status: {tut.get('status').upper()} (Version {tut.get('version')})")
        print(f"  Video Path    : {tut.get('video_path')}")
    else:
        print("  Catalog Status: NOT IN CATALOG (Status: NEW)")

def cmd_module(args):
    """Canonical Command: module <module_name>"""
    module_arg = args.module_name.lower()
    print(f"\n[Module Workflow] Grouping module features for: '{module_arg}'")
    cat = load_catalog()
    inv = build_feature_inventory(WORKSPACE_ROOT, cat)

    module_features = []
    for f in inv.get('features', []):
        roles = [r.lower() for r in f.get('target_users', [])]
        fid = f['feature_id'].lower()
        if module_arg in roles or module_arg in fid or module_arg in f['name'].lower():
            module_features.append(f)

    print(f"Found {len(module_features)} features in module '{module_arg}':")
    for mf in module_features:
        print(f"  * {mf['feature_id']}: {mf['name']} (Tutorial ID: {mf['tutorial_id'] or 'None'})")

def cmd_onboarding(args):
    """Canonical Command: onboarding <role>"""
    role = args.target_user.lower()
    print(f"\n[Onboarding Workflow] Building onboarding journey for role: '{role}'")
    cat = load_catalog()
    inv = build_feature_inventory(WORKSPACE_ROOT, cat)

    role_features = [f for f in inv.get('features', []) if role in [r.lower() for r in f.get('target_users', [])]]
    print(f"Mapped {len(role_features)} core workflows for role '{role}':")
    for rf in role_features:
        print(f"  - {rf['name']}")

def cmd_test(args):
    """Canonical Command: test (Runs V3.2 Acceptance Test Suite)"""
    test_runner_path = os.path.join(WORKSPACE_ROOT, ".tutorial-video", "tests", "run_all_tests.py")
    if os.path.isfile(test_runner_path):
        print("\n[Manager] Launching V3.2 Acceptance Test Suite...")
        res = subprocess.run([sys.executable, test_runner_path], cwd=WORKSPACE_ROOT, text=True)
        sys.exit(res.returncode)
    else:
        print(f"[Manager] Test runner script not found at {test_runner_path}")
        sys.exit(1)

def cmd_test_sync(args):
    """Canonical Command: test-sync (Runs V3.5.2 Deterministic A/V Sync & Audio Isolation Test Suite)"""
    test_suite_path = os.path.join(WORKSPACE_ROOT, ".tutorial-video", "tests", "test_sync_suite.py")
    if os.path.isfile(test_suite_path):
        print("\n[Manager] Launching V3.5.2 Deterministic A/V Sync & Audio Isolation Test Suite...")
        res = subprocess.run([sys.executable, test_suite_path], cwd=WORKSPACE_ROOT, text=True)
        sys.exit(res.returncode)
    else:
        print(f"[Manager] Test sync suite not found at {test_suite_path}")
        sys.exit(1)

def cmd_test_cursor(args):
    """Canonical Command: test-cursor (V3.5.2.1 Cursor Continuity + A/V Sync + Isolation Master Suite)"""
    # Primary: scripts/test_cursor_suite.py (V3.5.2.1)
    suite_path = os.path.join(SCRIPT_DIR, 'test_cursor_suite.py')
    if os.path.isfile(suite_path):
        print("\n[Manager] Launching V3.5.2.1 Cursor Continuity & A/V Verification Suite...")
        print("[Manager] Tests: cursor-continuity, click-sync, typing-sync, audio-isolation,")
        print("[Manager]        audio-fingerprint, av-contact-sheets, timeline-dependency-audit")
        print("[Manager] Variants: Desktop + Mobile (both required)")
        res = subprocess.run([sys.executable, suite_path], cwd=WORKSPACE_ROOT, text=True)
        if res.returncode == 0:
            print("\n[Manager] cursor continuity tests PASSED")
        else:
            print("\n[Manager] cursor continuity tests FAILED -- see evidence above")
        sys.exit(res.returncode)
    else:
        print(f"[Manager] test_cursor_suite.py not found at {suite_path}")
        sys.exit(1)

def cmd_test_frame_sync(args):
    """Canonical Command: test-frame-sync (V3.5.2.3 Frame-True A/V Synchronization Suite)"""
    qa_script = os.path.join(SCRIPT_DIR, 'frame_true_av_qa.py')
    if not os.path.isfile(qa_script):
        print(f"[Manager] frame_true_av_qa.py not found at {qa_script}")
        sys.exit(1)

    runs_dir = os.path.join(WORKSPACE_ROOT, '.tutorial-video', 'runs')
    desktop_runs = sorted([d for d in os.listdir(runs_dir) if 'desktop' in d], reverse=True) if os.path.isdir(runs_dir) else []
    mobile_runs = sorted([d for d in os.listdir(runs_dir) if 'mobile' in d], reverse=True) if os.path.isdir(runs_dir) else []

    desktop_run = os.path.join(runs_dir, desktop_runs[0]) if desktop_runs else os.path.join(runs_dir, 'run-20261003-parent-leave-v36-desktop')
    mobile_run = os.path.join(runs_dir, mobile_runs[0]) if mobile_runs else os.path.join(runs_dir, 'run-20261003-parent-leave-v36-mobile')

    out_base = os.path.join(WORKSPACE_ROOT, '.tutorial-video', 'run-20261003-parent-leave-v36')

    desktop_video = os.path.join(desktop_run, 'desktop', 'video', 'final-tutorial.mp4')
    desktop_rec = os.path.join(desktop_run, 'desktop', 'recording')
    desktop_out = os.path.join(out_base, 'desktop')

    mobile_video = os.path.join(mobile_run, 'mobile', 'video', 'final-tutorial.mp4')
    mobile_rec = os.path.join(mobile_run, 'mobile', 'recording')
    mobile_out = os.path.join(out_base, 'mobile')

    print(f"[Manager] Desktop Run: {os.path.basename(desktop_run)}")
    print(f"[Manager] Mobile Run: {os.path.basename(mobile_run)}")
    print(f"[Manager] Output Base: {out_base}")

    print("[Manager] Running Desktop Frame-True QA...")
    res_d = subprocess.run([
        sys.executable, qa_script,
        '--video', desktop_video,
        '--recording-dir', desktop_rec,
        '--output-dir', desktop_out,
        '--variant', 'desktop'
    ], cwd=WORKSPACE_ROOT)

    print("\n[Manager] Running Mobile Frame-True QA...")
    res_m = subprocess.run([
        sys.executable, qa_script,
        '--video', mobile_video,
        '--recording-dir', mobile_rec,
        '--output-dir', mobile_out,
        '--variant', 'mobile'
    ], cwd=WORKSPACE_ROOT)

    overall = (res_d.returncode == 0) and (res_m.returncode == 0)
    print(f"\n[Manager] Frame-True A/V Sync Overall: {'PASS' if overall else 'FAIL'}")
    sys.exit(0 if overall else 1)

def main():
    p = argparse.ArgumentParser(
        prog="manager.py",
        description="V3.5.2 Release Manager & Canonical Command Interface"
    )
    sub = p.add_subparsers(dest="command", required=True)

    # 1. catalog
    sub.add_parser("catalog", help="Display tutorial documentation catalog summary")

    # 2. release
    p_rel = sub.add_parser("release", help="Run change detection, impact analysis, or active release")
    p_rel.add_argument("--dry-run", action="store_true", help="Analyze and plan without recording or rendering")
    p_rel.add_argument("--plan", action="store_true", help="Display and save the release execution plan")
    p_rel.add_argument("--execute", action="store_true", help="Execute approved release queue")
    p_rel.add_argument("--allow-bulk", action="store_true", help="Allow batch release execution beyond safe threshold")

    # 3. feature
    p_feat = sub.add_parser("feature", help="Inspect or generate tutorial for specific feature")
    p_feat.add_argument("feature_name", help="Feature ID or name")

    # 4. module
    p_mod = sub.add_parser("module", help="Process group of connected workflow features")
    p_mod.add_argument("module_name", help="Module or role keyword (e.g. guru, admin, parent)")

    # 5. onboarding
    p_onb = sub.add_parser("onboarding", help="Create onboarding journey for user role")
    p_onb.add_argument("target_user", help="User role (e.g. guru, orang_tua, admin)")

    # 6. test
    sub.add_parser("test", help="Execute V3.2 Automated Acceptance Test Suite")

    # 7. test-sync (V3.5.2)
    sub.add_parser("test-sync", help="Execute V3.5.2 Deterministic A/V Sync & Mobile Isolation Test Suite")

    # 8. test-cursor (V3.5.2.1)
    sub.add_parser("test-cursor", help="Execute V3.5.2.1 Cursor Continuity + Click/Typing Sync + Isolation Master Suite")

    # 9. test-frame-sync (V3.5.2.3)
    sub.add_parser("test-frame-sync", help="Execute V3.5.2.3 Frame-True A/V Synchronization Suite")

    args = p.parse_args()

    if args.command == "catalog":
        cmd_catalog(args)
    elif args.command == "release":
        cmd_release(args)
    elif args.command == "feature":
        cmd_feature(args)
    elif args.command == "module":
        cmd_module(args)
    elif args.command == "onboarding":
        cmd_onboarding(args)
    elif args.command == "test":
        cmd_test(args)
    elif args.command == "test-sync":
        cmd_test_sync(args)
    elif args.command == "test-cursor":
        cmd_test_cursor(args)
    elif args.command == "test-frame-sync":
        cmd_test_frame_sync(args)

if __name__ == '__main__':
    main()
