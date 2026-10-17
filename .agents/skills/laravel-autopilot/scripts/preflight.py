#!/usr/bin/env python3
"""
preflight.py - Laravel Autopilot Preflight Checker
Checks the workspace, required executables, PHP/Laravel environment,
and test database safety without exposing secrets or mutating files.
Security hardened: strict shell=False, UTF-8 decoding, deep redaction.
"""

import argparse
import json
import os
import shutil
import subprocess
import sys
import xml.etree.ElementTree as ET
from pathlib import Path


def run_cmd(cmd, cwd=None, timeout=10):
    """Run an argument list command safely with shell=False."""
    try:
        proc = subprocess.run(
            cmd,
            cwd=cwd,
            capture_output=True,
            timeout=timeout,
            shell=False  # Strict non-negotiable invariant
        )
        out = proc.stdout.decode("utf-8", errors="replace").strip()
        err = proc.stderr.decode("utf-8", errors="replace").strip()
        return proc.returncode, out, err
    except subprocess.TimeoutExpired:
        return -1, "", f"Command timed out after {timeout}s"
    except Exception as e:
        return -1, "", str(e)


def detect_executables():
    """Detect presence and paths of required development executables."""
    tools = ["php", "composer", "git", "python", "node", "npm"]
    results = {}
    for tool in tools:
        found_path = shutil.which(tool)
        results[tool] = {
            "available": found_path is not None,
            "path": found_path or ""
        }
    return results


def check_php_details():
    """Check PHP version and essential extensions."""
    php_path = shutil.which("php") or "php"
    code, out, _ = run_cmd([php_path, "-v"])
    version_line = out.splitlines()[0] if out else "Unknown"

    code_ext, out_ext, _ = run_cmd([php_path, "-m"])
    extensions = [line.strip().lower() for line in out_ext.splitlines() if line.strip()] if code_ext == 0 else []

    essential = ["pdo", "pdo_sqlite", "sqlite3", "mbstring", "openssl", "tokenizer", "xml", "curl"]
    missing = [ext for ext in essential if ext not in extensions]

    return {
        "version_line": version_line,
        "extensions_checked": essential,
        "missing_extensions": missing,
    }


def inspect_git_status(project_root):
    """Check Git status and branch without making any changes."""
    git_path = shutil.which("git") or "git"
    code_branch, out_branch, _ = run_cmd([git_path, "rev-parse", "--abbrev-ref", "HEAD"], cwd=project_root)
    code_status, out_status, _ = run_cmd([git_path, "status", "--porcelain"], cwd=project_root)

    is_repo = code_branch == 0
    branch = out_branch if is_repo else "N/A"
    clean = len(out_status) == 0 if is_repo else False
    changed_files = [line.strip() for line in out_status.splitlines()] if out_status else []

    return {
        "is_git_repo": is_repo,
        "current_branch": branch,
        "working_tree_clean": clean,
        "uncommitted_changes_count": len(changed_files),
        "uncommitted_files_preview": changed_files[:10],
    }


# Import hardened db safety helper from test_runner
try:
    from .test_runner import inspect_effective_test_db
except ImportError:
    try:
        from test_runner import inspect_effective_test_db
    except ImportError:
        _scripts_dir = Path(__file__).resolve().parent
        if str(_scripts_dir) not in sys.path:
            sys.path.insert(0, str(_scripts_dir))
        from test_runner import inspect_effective_test_db


def inspect_test_db_safety(project_root):
    """
    Safely inspect test database configuration from phpunit.xml, .env.testing,
    and bootstrap/cache/config.php using test_runner's multi-layer isolation check.
    Required behavior:
    - Config cache detected -> BLOCKED
    - Database configuration ambiguous -> BLOCKED
    - Non-isolated database target -> BLOCKED
    - Verified SQLite in-memory configuration -> PASS
    """
    db_findings = inspect_effective_test_db(project_root)
    return {
        "phpunit_xml_found": db_findings.get("phpunit_xml_found", False),
        "test_db_connection": db_findings.get("connection", "unknown"),
        "test_db_database": db_findings.get("database", "unknown"),
        "is_isolated_safe": db_findings.get("is_safe", False),
        "safety_assessment": db_findings.get("assessment", ""),
        "config_cache_found": db_findings.get("config_cache_found", False),
        "env_testing_found": db_findings.get("env_testing_found", False),
        "warnings": db_findings.get("warnings", [])
    }


def run_preflight(project_root):
    """Execute complete preflight check."""
    root_path = Path(project_root).resolve()
    artisan_path = root_path / "artisan"
    composer_path = root_path / "composer.json"

    is_laravel = artisan_path.is_file() and composer_path.is_file()

    executables = detect_executables()
    php_info = check_php_details()
    git_info = inspect_git_status(root_path)
    test_db_info = inspect_test_db_safety(root_path)

    ready = True
    blockers = []

    if not is_laravel:
        ready = False
        blockers.append("Directory is not a valid Laravel root (missing artisan or composer.json).")

    if not executables.get("php", {}).get("available"):
        ready = False
        blockers.append("PHP executable is missing from PATH.")

    if not executables.get("composer", {}).get("available"):
        blockers.append("Composer executable is not detected in PATH.")

    if not git_info.get("is_git_repo"):
        blockers.append("Workspace is not a Git repository. Git checkpoint features will be disabled.")

    if not test_db_info.get("is_isolated_safe"):
        ready = False
        blockers.append(f"Test database safety check failed: {test_db_info.get('safety_assessment')}")

    return {
        "status": "PASS" if ready and not blockers else ("WARNING" if ready else "BLOCKED"),
        "project_root": str(root_path),
        "is_laravel_project": is_laravel,
        "executables": executables,
        "php_environment": php_info,
        "git_state": git_info,
        "test_database_safety": test_db_info,
        "blockers": blockers
    }


def main():
    parser = argparse.ArgumentParser(description="Laravel Autopilot Preflight Checker")
    parser.add_argument("--project-path", default=".", help="Path to Laravel project root (default: current directory)")
    parser.add_argument("--json", action="store_true", help="Output results in JSON format")

    args = parser.parse_args()
    report = run_preflight(args.project_path)

    if args.json:
        print(json.dumps(report, indent=2))
    else:
        print("=== Laravel Autopilot Preflight Check ===")
        print(f"Status:             {report['status']}")
        print(f"Project Root:       {report['project_root']}")
        print(f"Laravel Project:    {'YES' if report['is_laravel_project'] else 'NO'}")
        print(f"PHP Version:        {report['php_environment']['version_line']}")
        print(f"Git Repo / Branch:  {'YES' if report['git_state']['is_git_repo'] else 'NO'} ({report['git_state']['current_branch']})")
        print(f"Working Tree Clean: {'YES' if report['git_state']['working_tree_clean'] else 'NO'} ({report['git_state']['uncommitted_changes_count']} changed files)")
        print(f"Test DB Safety:     {report['test_database_safety']['safety_assessment']}")
        if report['blockers']:
            print("\nBlockers / Warnings:")
            for b in report['blockers']:
                print(f"  - {b}")
        print("==========================================")

    sys.exit(0 if report["status"] in ("PASS", "WARNING") else 1)


if __name__ == "__main__":
    main()
