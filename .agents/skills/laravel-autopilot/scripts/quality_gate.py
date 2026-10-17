#!/usr/bin/env python3
"""
quality_gate.py - Laravel Autopilot Quality Gate Engine
Phase 2 Implementation:
- Formal verification of 8 Quality Gates
- Returns strict taxonomy states: PASS, FAIL, BLOCKED, NOT_RUN
- Prohibits PASS without empirical supporting evidence
- Generates machine-readable JSON and audit tables
"""

import argparse
import datetime
import hashlib
import hmac
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

# Import sibling helpers if available
script_dir = Path(__file__).resolve().parent
sys.path.insert(0, str(script_dir))

try:
    import preflight
    import test_runner
    import laravel_inspector
except ImportError:
    pass

GATE_NAMES = [
    "GATE_1_PREFLIGHT",
    "GATE_2_SYNTAX",
    "GATE_3_ROUTING",
    "GATE_4_DB_SAFETY",
    "GATE_5_UNIT_FEATURE_TEST",
    "GATE_6_NEGATIVE_AUTH_TEST",
    "GATE_7_REGRESSION_TEST",
    "GATE_8_GIT_SCOPE"
]


def get_file_sha256(filepath):
    """Compute SHA-256 hash of a file on disk. Returns None if file does not exist or cannot be read."""
    p = Path(filepath)
    if not p.is_file():
        return None
    try:
        h = hashlib.sha256()
        with open(p, "rb") as f:
            while chunk := f.read(65536):
                h.update(chunk)
        return h.hexdigest()
    except Exception:
        return None


def _clean_path(p_str):
    """Clean and normalize a relative path string."""
    if not p_str:
        return ""
    p_str = p_str.strip()
    if p_str.startswith('"') and p_str.endswith('"'):
        p_str = p_str[1:-1]
    p_str = p_str.replace("\\", "/")
    if p_str.startswith("./"):
        p_str = p_str[2:]
    return p_str


# Patterns for secret content detection in diffs and new files.
# NOTE: Heuristic regex scanning cannot guarantee detection of all forms
# of high-entropy secrets, custom tokens, or obfuscated credentials.
SECRET_CONTENT_PATTERNS = [
    ("Private Key", re.compile(r'-----BEGIN [A-Z\s]+ PRIVATE KEY-----')),
    ("AWS Access Key", re.compile(r'\b(AKIA[0-9A-Z]{16})\b')),
    ("GitHub Token", re.compile(r'\b(gh[pous]_[a-zA-Z0-9]{36,})\b')),
    ("Bearer Token", re.compile(r'(?i)\bbearer\s+([a-zA-Z0-9_\-\.]{25,})\b')),
    ("Database URI with Credentials", re.compile(r'(?i)[a-z]+://[^:\s]+:[^@\s]+@[^/\s]+')),
    ("High-Risk Secret Assignment", re.compile(r'(?i)\b(api[_-]?key|auth[_-]?token|client[_-]?secret|private[_-]?key|db[_-]?password)\s*[:=]\s*["\']([^"\'\s]{12,})["\']')),
]

IGNORED_SECRET_SCAN_EXTENSIONS = {
    ".pyc", ".png", ".jpg", ".jpeg", ".gif", ".ico", ".svg",
    ".sqlite", ".sqlite3", ".db", ".zip", ".tar", ".gz",
    ".pdf", ".woff", ".woff2", ".ttf", ".eot"
}


def scan_working_tree_secret_content(repo_root, entries=None, skip_prefixes=(".agents/", ".git/")):
    """
    Inspect working tree diffs and newly created files for secret patterns.
    - Uses strict argument list subprocess execution (shell=False).
    - Checks added lines in tracked working tree and staged diffs.
    - Checks content of untracked files up to a safe size limit.
    - Never prints or exposes raw secret values in output or logs.
    - Returns tuple: (findings_list, error_string)
        findings_list: [{"category": str, "file": str, "line": int}, ...]
        error_string: str if git diff/reading failed, None otherwise
    NOTE: Heuristic regex scanning cannot guarantee detection of all forms
    of high-entropy secrets, custom tokens, or obfuscated credentials.
    """
    root = Path(repo_root).resolve()
    findings = []

    # 1. Inspect diff of tracked and staged changes
    diff_args = ["git", "diff", "HEAD", "-U0", "--no-color"]
    proc = subprocess.run(diff_args, cwd=root, capture_output=True, text=True, shell=False)
    if proc.returncode != 0:
        # Fallback if repository has no HEAD commit yet
        diff_args = ["git", "diff", "-U0", "--no-color"]
        proc = subprocess.run(diff_args, cwd=root, capture_output=True, text=True, shell=False)
        if proc.returncode != 0:
            return None, f"Failed to execute git diff: {proc.stderr.strip()}"

    current_file = None
    current_line = 0
    hunk_regex = re.compile(r'^@@ -\d+(?:,\d+)? \+(\d+)(?:,\d+)? @@')

    for line in proc.stdout.splitlines():
        if line.startswith("diff --git "):
            parts = line.split(" ")
            if len(parts) >= 4:
                b_path = parts[3]
                if b_path.startswith("b/"):
                    b_path = b_path[2:]
                current_file = _clean_path(b_path)
            else:
                current_file = None
            current_line = 0
            continue

        if not current_file:
            continue

        if any(current_file.startswith(sp) for sp in skip_prefixes):
            continue
        if current_file.endswith(".example") or any(current_file.endswith(ext) for ext in IGNORED_SECRET_SCAN_EXTENSIONS):
            continue

        hunk_match = hunk_regex.match(line)
        if hunk_match:
            current_line = int(hunk_match.group(1))
            continue

        if line.startswith("+") and not line.startswith("+++"):
            added_text = line[1:]
            for cat, pat in SECRET_CONTENT_PATTERNS:
                if pat.search(added_text):
                    findings.append({
                        "category": cat,
                        "file": current_file,
                        "line": current_line
                    })
                    break
            current_line += 1
        elif line.startswith("-"):
            pass
        elif line.startswith(" "):
            current_line += 1

    # 2. Inspect untracked files from porcelain entries
    if entries:
        for entry in entries:
            if entry.get("status") == "??":
                u_path = entry["path"]
                if any(u_path.startswith(sp) for sp in skip_prefixes):
                    continue
                if u_path.endswith(".example") or any(u_path.endswith(ext) for ext in IGNORED_SECRET_SCAN_EXTENSIONS):
                    continue

                full_p = root / u_path
                if not full_p.is_file():
                    continue

                try:
                    if full_p.stat().st_size > 1024 * 1024:
                        continue
                    with open(full_p, "r", encoding="utf-8", errors="replace") as f:
                        for line_idx, file_line in enumerate(f, start=1):
                            if line_idx > 5000:
                                break
                            for cat, pat in SECRET_CONTENT_PATTERNS:
                                if pat.search(file_line):
                                    findings.append({
                                        "category": cat,
                                        "file": u_path,
                                        "line": line_idx
                                    })
                                    break
                except Exception as e:
                    return None, f"Failed to read untracked file '{u_path}': {e}"

    return findings, None



def parse_git_status_z(status_bytes):
    """
    Parse the raw binary output of `git status --porcelain -z -uall`.
    Returns a list of dicts:
    [
        {"status": "XY", "path": "normalized/path", "orig_path": "old/path/if/rename"},
        ...
    ]
    """
    if not status_bytes:
        return []

    entries = []
    tokens = status_bytes.split(b"\x00")
    i = 0
    total = len(tokens)
    while i < total:
        token = tokens[i]
        if not token:
            i += 1
            continue
        if len(token) < 3:
            i += 1
            continue
        status = token[:2].decode("utf-8", errors="replace")
        raw_path = token[3:].decode("utf-8", errors="replace")
        orig_path = None

        if "R" in status or "C" in status:
            if i + 1 < total:
                i += 1
                orig_path = tokens[i].decode("utf-8", errors="replace")
                orig_path = _clean_path(orig_path)

        path = _clean_path(raw_path)
        entries.append({
            "status": status,
            "path": path,
            "orig_path": orig_path
        })
        i += 1
    return entries


def get_git_repo_id(repo_root):
    """Get unique repository identity combining absolute root path and HEAD commit hash."""
    root = Path(repo_root).resolve()
    head = "NO_HEAD"
    try:
        proc = subprocess.run(
            ["git", "rev-parse", "HEAD"],
            cwd=root,
            capture_output=True,
            text=True,
            shell=False
        )
        if proc.returncode == 0:
            head = proc.stdout.strip()
    except Exception:
        pass
    return f"{root.as_posix()}@{head}"


def get_supervisor_key():
    """
    Retrieve external supervisor HMAC authentication key if configured.
    Checks AUTOPILOT_SUPERVISOR_KEY environment variable, then AUTOPILOT_SUPERVISOR_KEY_FILE.
    Returns bytes or None.
    NOTE: In local desktop environments where the agent runs with user privileges,
    environment variables are accessible to the agent process unless managed by an external
    process sandbox / supervisor. We explicitly document this boundary limitation.
    """
    key_env = os.environ.get("AUTOPILOT_SUPERVISOR_KEY")
    if key_env:
        return key_env.strip().encode("utf-8")
    key_file = os.environ.get("AUTOPILOT_SUPERVISOR_KEY_FILE")
    if key_file:
        p = Path(key_file).resolve()
        if p.is_file():
            try:
                return p.read_bytes().strip()
            except Exception:
                pass
    return None


def get_repo_slug(repo_root):
    """Generate a clean slug for the repository root to partition baseline snapshots."""
    root_resolved = Path(repo_root).resolve()
    name = root_resolved.name or "repo"
    root_str = str(root_resolved).replace("\\", "/")
    root_hash = hashlib.sha256(root_str.encode("utf-8")).hexdigest()[:12]
    return f"{name}_{root_hash}"


def get_default_snapshot_path(project_root, task_id):
    """
    Get the default storage path for a baseline snapshot.
    Stored outside the agent-writable project workspace by default to prevent workspace pollution,
    accidental deletion, and unintended Git status modifications.
    Configurable via AUTOPILOT_BASELINE_STORE.
    """
    store_dir = os.environ.get("AUTOPILOT_BASELINE_STORE")
    if store_dir:
        base_dir = Path(store_dir).resolve()
    else:
        base_dir = Path(tempfile.gettempdir()) / "autopilot_baselines"

    slug = get_repo_slug(project_root)
    safe_task_id = re.sub(r"[^\w\-.]", "_", task_id or "default-task")
    return base_dir / slug / f"{safe_task_id}.json"


class BaselineSnapshot:
    """
    Cryptographic Baseline Snapshot for workspace integrity protection.
    Binds to task_id, repository identity, records SHA-256 of all dirty/baseline files,
    and includes tamper-evident digest/HMAC of snapshot contents.

    Trust Boundary Modes:
    - SUPERVISOR_HMAC: When AUTOPILOT_SUPERVISOR_KEY is present in the supervisor environment,
      an HMAC-SHA256 signature is calculated and verified.
    - SUPERVISED_USE_LOCAL: Standard unauthenticated SHA-256 digest when running in local
      supervised environments without an external supervisor key. Provides procedural
      integrity checks, but cannot defend against an adversarial process with identical
      filesystem/memory permissions.
    """
    def __init__(self, repo_id, task_id, files=None, created_at=None, snapshot_hash=None, trust_level=None, signature=None):
        self.repo_id = repo_id
        self.task_id = task_id
        self.files = files or {}  # norm_path -> {status, exists, sha256, size, orig_path}
        self.created_at = created_at or datetime.datetime.now(datetime.timezone.utc).isoformat()
        supervisor_key = get_supervisor_key()
        self.trust_level = trust_level or ("SUPERVISOR_HMAC" if supervisor_key else "SUPERVISED_USE_LOCAL")
        self.snapshot_hash = snapshot_hash or self.calculate_integrity_hash()
        self.signature = signature or (self.calculate_signature(supervisor_key) if supervisor_key else None)

    def _get_canonical_manifest(self):
        """Generate canonical deterministic manifest string."""
        manifest = json.dumps(self.files, sort_keys=True)
        return f"{self.repo_id}|{self.task_id}|{self.created_at}|{manifest}"

    def calculate_integrity_hash(self):
        """Calculate tamper-evident SHA-256 of metadata and file states."""
        token = self._get_canonical_manifest()
        return hashlib.sha256(token.encode("utf-8")).hexdigest()

    def calculate_signature(self, key):
        """Calculate HMAC-SHA256 signature using the supervisor key."""
        if not key:
            return None
        token = f"{self._get_canonical_manifest()}|{self.snapshot_hash}"
        return hmac.new(key, token.encode("utf-8"), hashlib.sha256).hexdigest()

    def verify_integrity(self, supervisor_key=None):
        """
        Verify that the snapshot hash and signature match its contents.
        If an external supervisor key is configured or passed:
          - The snapshot must have been signed with the supervisor key.
          - If the signature is missing or does not match, verification returns False.
        If no supervisor key is present:
          - Verifies the unkeyed SHA-256 digest.
        """
        key = supervisor_key if supervisor_key is not None else get_supervisor_key()

        # 1. Digest verification
        expected_hash = self.calculate_integrity_hash()
        if self.snapshot_hash != expected_hash:
            return False

        # 2. Supervisor HMAC verification
        if key is not None:
            if not self.signature:
                return False
            expected_sig = self.calculate_signature(key)
            if not hmac.compare_digest(self.signature, expected_sig):
                return False
        elif self.trust_level == "SUPERVISOR_HMAC":
            if not self.signature:
                return False

        return True

    @classmethod
    def capture(cls, project_root, task_id="default-task", extra_files=None):
        """
        Capture the initial state of every relevant baseline file before task begins.
        Captures all tracked, staged, unstaged, untracked, deleted, and renamed paths
        reported by git status --porcelain -z -uall.
        """
        root = Path(project_root).resolve()
        repo_id = get_git_repo_id(root)

        proc = subprocess.run(
            ["git", "status", "--porcelain", "-z", "-uall"],
            cwd=root,
            capture_output=True,
            shell=False
        )
        if proc.returncode != 0:
            raise RuntimeError(f"Failed to capture git status in {root}")

        entries = parse_git_status_z(proc.stdout)
        files = {}

        for entry in entries:
            p = entry["path"]
            full_path = root / p
            exists = full_path.is_file()
            sha256 = get_file_sha256(full_path) if exists else None
            size = full_path.stat().st_size if exists else 0
            files[p] = {
                "status": entry["status"],
                "exists": exists,
                "sha256": sha256,
                "size": size,
                "orig_path": entry.get("orig_path")
            }
            if entry.get("orig_path"):
                orig_p = entry["orig_path"]
                orig_full = root / orig_p
                orig_exists = orig_full.is_file()
                files[orig_p] = {
                    "status": "R_ORIG",
                    "exists": orig_exists,
                    "sha256": get_file_sha256(orig_full) if orig_exists else None,
                    "size": orig_full.stat().st_size if orig_exists else 0,
                    "orig_path": None
                }

        # Extra protected files if explicitly specified
        if extra_files:
            for ef in extra_files:
                norm_ef = _clean_path(ef)
                if norm_ef not in files:
                    full_p = root / norm_ef
                    exists = full_p.is_file()
                    files[norm_ef] = {
                        "status": "PROTECTED",
                        "exists": exists,
                        "sha256": get_file_sha256(full_p) if exists else None,
                        "size": full_p.stat().st_size if exists else 0,
                        "orig_path": None
                    }

        return cls(repo_id=repo_id, task_id=task_id, files=files)

    def save(self, filepath=None, overwrite=False):
        """
        Save snapshot to disk.
        To prevent tampering, overwriting an existing snapshot is prohibited by default.
        If filepath is None, defaults to external storage outside the project workspace.
        """
        if filepath:
            p = Path(filepath).resolve()
        else:
            repo_root = self.repo_id.split("@")[0]
            p = get_default_snapshot_path(repo_root, self.task_id)

        if p.exists() and not overwrite:
            raise FileExistsError(
                f"Baseline snapshot file '{p}' already exists. "
                "Overwriting baseline snapshots is strictly prohibited to prevent tampering."
            )
        p.parent.mkdir(parents=True, exist_ok=True)
        data = {
            "task_id": self.task_id,
            "repo_id": self.repo_id,
            "created_at": self.created_at,
            "trust_level": self.trust_level,
            "snapshot_hash": self.snapshot_hash,
            "signature": self.signature,
            "files": self.files
        }
        with open(p, "w", encoding="utf-8") as f:
            json.dump(data, f, indent=2)
        return p

    @classmethod
    def load(cls, filepath_or_dict):
        """Load snapshot from JSON file path or dictionary."""
        if isinstance(filepath_or_dict, dict):
            data = filepath_or_dict
        else:
            p = Path(filepath_or_dict).resolve()
            if not p.is_file():
                raise FileNotFoundError(f"Baseline snapshot file not found: {p}")
            with open(p, "r", encoding="utf-8") as f:
                data = json.load(f)

        required_keys = {"task_id", "repo_id", "snapshot_hash", "files"}
        if not required_keys.issubset(data.keys()):
            raise ValueError(f"Invalid baseline snapshot schema; missing keys: {required_keys - set(data.keys())}")

        return cls(
            repo_id=data["repo_id"],
            task_id=data["task_id"],
            files=data.get("files", {}),
            created_at=data.get("created_at"),
            snapshot_hash=data["snapshot_hash"],
            trust_level=data.get("trust_level"),
            signature=data.get("signature")
        )


class QualityGateRegistry:
    def __init__(self, project_root="."):
        self.root = Path(project_root).resolve()
        self.gates = {name: {"status": "NOT_RUN", "evidence": None, "details": ""} for name in GATE_NAMES}

    def set_gate(self, name, status, evidence=None, details=""):
        if name not in self.gates:
            raise ValueError(f"Unknown gate: {name}")
        if status not in ("PASS", "FAIL", "BLOCKED", "NOT_RUN"):
            raise ValueError(f"Invalid status: {status}. Allowed: PASS, FAIL, BLOCKED, NOT_RUN")

        # Invariant: Cannot be PASS without evidence
        if status == "PASS" and not evidence:
            raise ValueError(f"Gate {name} cannot be marked PASS without empirical supporting evidence!")

        self.gates[name] = {
            "status": status,
            "evidence": evidence,
            "details": details
        }

    def evaluate_preflight(self):
        """Evaluate Gate 1: Workspace & Preflight."""
        try:
            report = preflight.run_preflight(self.root)
            status = report.get("status")
            if status == "PASS":
                self.set_gate("GATE_1_PREFLIGHT", "PASS", evidence=report, details="PHP, Composer, Git, and Laravel root verified.")
            elif status == "WARNING":
                self.set_gate("GATE_1_PREFLIGHT", "PASS", evidence=report, details="Preflight passed with non-blocking warnings.")
            else:
                self.set_gate("GATE_1_PREFLIGHT", "BLOCKED", evidence=report, details="Preflight blocked: " + "; ".join(report.get("blockers", [])))
        except Exception as e:
            self.set_gate("GATE_1_PREFLIGHT", "BLOCKED", evidence=str(e), details=f"Error executing preflight: {e}")

    def evaluate_php_syntax(self, files_to_check):
        """Evaluate Gate 2: PHP Syntax."""
        if not files_to_check:
            self.set_gate("GATE_2_SYNTAX", "NOT_RUN", details="No PHP files specified for syntax evaluation.")
            return

        php_path = shutil.which("php")
        if not php_path:
            self.set_gate("GATE_2_SYNTAX", "BLOCKED", details="PHP executable not available in PATH.")
            return

        errors = []
        checked = []
        for f in files_to_check:
            full_path = self.root / f if not os.path.isabs(f) else Path(f)
            if not full_path.is_file() or not str(full_path).endswith(".php"):
                continue
            proc = subprocess.run([php_path, "-l", str(full_path)], capture_output=True, text=True)
            checked.append(str(full_path))
            if proc.returncode != 0:
                errors.append(proc.stdout.strip() or proc.stderr.strip())

        if not checked:
            self.set_gate("GATE_2_SYNTAX", "NOT_RUN", details="No existing PHP files to check.")
        elif errors:
            self.set_gate("GATE_2_SYNTAX", "FAIL", evidence=errors, details=f"Syntax errors detected in {len(errors)} file(s).")
        else:
            self.set_gate("GATE_2_SYNTAX", "PASS", evidence={"checked_files": checked, "count": len(checked)}, details=f"Syntax clean across {len(checked)} file(s).")

    def evaluate_db_safety(self):
        """Evaluate Gate 4: Database Safety."""
        try:
            info = test_runner.inspect_effective_test_db(self.root)
            if info.get("is_safe"):
                self.set_gate("GATE_4_DB_SAFETY", "PASS", evidence=info, details=info.get("assessment"))
            else:
                self.set_gate("GATE_4_DB_SAFETY", "BLOCKED", evidence=info, details=info.get("assessment"))
        except Exception as e:
            self.set_gate("GATE_4_DB_SAFETY", "BLOCKED", evidence=str(e), details=f"Error evaluating DB safety: {e}")

    def evaluate_git_scope(self, approved_files=None, baseline_snapshot=None, task_id=None, authorized_baseline_modifications=None, baseline_changes=None):
        """
        Evaluate Gate 8: Git Scope & Diff Safety (Phase 1B Hardened).
        Enforces:
        1. No secrets, credentials, or private keys in working tree.
        2. Cryptographic baseline integrity: protected baseline files must retain their exact initial state (SHA-256).
        3. Unauthorized modification, deletion, or rename of baseline files -> FAIL.
        4. Directory prefix match NEVER authorizes modification to protected baseline files.
        5. Agent-created changes must be strictly within approved_files scope -> PASS.
        6. Missing baseline evidence, invalid snapshot, or repo/session mismatch -> BLOCKED.
        7. Robust NUL-separated (-z) Git porcelain parsing for special characters and renames.
        8. Purely read-only: does not execute any mutating Git commands.
        """
        try:
            proc = subprocess.run(
                ["git", "status", "--porcelain", "-z", "-uall"],
                cwd=self.root,
                capture_output=True,
                shell=False
            )
            if proc.returncode != 0:
                self.set_gate("GATE_8_GIT_SCOPE", "BLOCKED", details="Git command failed or directory is not a Git repository.")
                return

            entries = parse_git_status_z(proc.stdout)
            current_dirty_paths = {e["path"] for e in entries}

            # 1. Check for secrets/credentials leaks in working tree (immediate non-negotiable failure)
            secret_pattern = re.compile(r'(\.env|secret|credential|id_rsa|\.key|\.pem)', re.IGNORECASE)
            leaks = []
            for entry in entries:
                p = entry["path"]
                if secret_pattern.search(p) and not p.endswith(".example"):
                    leaks.append(p)
                if entry.get("orig_path"):
                    op = entry["orig_path"]
                    if secret_pattern.search(op) and not op.endswith(".example"):
                        leaks.append(op)

            if leaks:
                self.set_gate(
                    "GATE_8_GIT_SCOPE",
                    "FAIL",
                    evidence={"secret_leaks": sorted(leaks)},
                    details=f"Potential secrets detected in working tree: {', '.join(sorted(leaks))}"
                )
                return

            # 1b. Check for secrets content in diffs and added/modified files (Remediasi D)
            secret_content_findings, scan_err = scan_working_tree_secret_content(self.root, entries)
            if scan_err:
                self.set_gate(
                    "GATE_8_GIT_SCOPE",
                    "BLOCKED",
                    evidence={"scan_error": scan_err},
                    details=f"BLOCKED: Failed to inspect diff content for secrets: {scan_err}"
                )
                return
            if secret_content_findings:
                finding_summaries = [f"{f['category']} in {f['file']}:{f['line']}" for f in secret_content_findings]
                self.set_gate(
                    "GATE_8_GIT_SCOPE",
                    "FAIL",
                    evidence={"secret_content_findings": secret_content_findings},
                    details=f"Potential secrets detected in file content: {'; '.join(finding_summaries[:5])}" +
                            (f" (and {len(finding_summaries) - 5} more)" if len(finding_summaries) > 5 else "")
                )
                return

            # 2. Resolve baseline snapshot if provided in any supported form
            snapshot = None
            if isinstance(baseline_snapshot, BaselineSnapshot):
                snapshot = baseline_snapshot
            elif isinstance(baseline_snapshot, (str, Path)):
                try:
                    snapshot = BaselineSnapshot.load(baseline_snapshot)
                except Exception as e:
                    self.set_gate("GATE_8_GIT_SCOPE", "BLOCKED", details=f"BLOCKED: Failed to load baseline snapshot: {e}")
                    return
            elif isinstance(baseline_snapshot, dict):
                try:
                    snapshot = BaselineSnapshot.load(baseline_snapshot)
                except Exception as e:
                    self.set_gate("GATE_8_GIT_SCOPE", "BLOCKED", details=f"BLOCKED: Invalid baseline snapshot dictionary: {e}")
                    return
            elif baseline_snapshot is None and isinstance(baseline_changes, BaselineSnapshot):
                snapshot = baseline_changes
            elif baseline_snapshot is None and task_id:
                # Automatic resolution from external default snapshot store
                default_p = get_default_snapshot_path(self.root, task_id)
                if default_p.is_file():
                    try:
                        snapshot = BaselineSnapshot.load(default_p)
                    except Exception as e:
                        self.set_gate("GATE_8_GIT_SCOPE", "BLOCKED", details=f"BLOCKED: Failed to load baseline snapshot from {default_p}: {e}")
                        return

            # 3. If a baseline snapshot was provided, verify its integrity and bindings first
            if snapshot is not None:
                supervisor_key = get_supervisor_key()
                if not snapshot.verify_integrity(supervisor_key):
                    self.set_gate(
                        "GATE_8_GIT_SCOPE",
                        "BLOCKED",
                        evidence={"snapshot_hash": snapshot.snapshot_hash, "trust_level": getattr(snapshot, "trust_level", "UNKNOWN")},
                        details="BLOCKED: Baseline snapshot integrity check failed. Snapshot file was corrupted, forged, or supervisor authentication failed."
                    )
                    return

                if task_id and snapshot.task_id != task_id:
                    self.set_gate(
                        "GATE_8_GIT_SCOPE",
                        "BLOCKED",
                        evidence={"expected_task_id": task_id, "snapshot_task_id": snapshot.task_id},
                        details=f"BLOCKED: Baseline snapshot task ID mismatch (expected '{task_id}', found '{snapshot.task_id}'). Snapshot belongs to another task or session."
                    )
                    return

                current_repo_id = get_git_repo_id(self.root)
                snapshot_repo_root = snapshot.repo_id.split("@")[0]
                current_repo_root = current_repo_id.split("@")[0]
                if snapshot_repo_root != current_repo_root or snapshot.repo_id != current_repo_id:
                    self.set_gate(
                        "GATE_8_GIT_SCOPE",
                        "BLOCKED",
                        evidence={"snapshot_repo": snapshot.repo_id, "current_repo": current_repo_id},
                        details=f"BLOCKED: Snapshot belongs to another repository or commit session ({snapshot.repo_id} != {current_repo_id})."
                    )
                    return

            # 4. Case: Working tree is completely clean
            if not current_dirty_paths:
                if snapshot is not None:
                    # Verify no baseline files that were supposed to exist were deleted
                    deleted_baseline = [bp for bp, binfo in snapshot.files.items() if binfo.get("exists") and not (self.root / bp).is_file()]
                    if deleted_baseline:
                        self.set_gate(
                            "GATE_8_GIT_SCOPE",
                            "FAIL",
                            evidence={"deleted_baseline_files": deleted_baseline},
                            details=f"Protected baseline file(s) deleted: {', '.join(deleted_baseline)}"
                        )
                        return
                self.set_gate(
                    "GATE_8_GIT_SCOPE",
                    "PASS",
                    evidence={"working_tree_files": [], "agent_changes": []},
                    details="Working tree is completely clean. No scope violations."
                )
                return

            # 5. Case: Working tree has dirty files, but no cryptographic baseline snapshot was provided
            if snapshot is None:
                msg = "BLOCKED: Missing baseline cryptographic snapshot evidence. Working tree contains uncommitted changes"
                if approved_files is None:
                    msg += ", and approved_files list was not provided to verify scope."
                else:
                    msg += ", but no valid baseline snapshot was captured prior to task execution."
                self.set_gate(
                    "GATE_8_GIT_SCOPE",
                    "BLOCKED",
                    evidence={"dirty_files": sorted(list(current_dirty_paths))},
                    details=msg
                )
                return

            # 8. Check baseline files integrity: modifications, deletions, renames
            authorized_mod_set = set()
            if authorized_baseline_modifications:
                for abm in authorized_baseline_modifications:
                    authorized_mod_set.add(_clean_path(abm))

            baseline_violations = []

            # Check unauthorized renames of baseline files from git porcelain entries
            renamed_from_baseline = {entry["orig_path"]: entry["path"] for entry in entries if entry.get("orig_path")}

            for b_path, b_info in snapshot.files.items():
                full_b_path = self.root / b_path
                orig_existed = b_info.get("exists", False)
                orig_sha = b_info.get("sha256")

                if orig_existed:
                    if b_path in renamed_from_baseline:
                        if b_path not in authorized_mod_set:
                            baseline_violations.append(f"Protected baseline file was renamed without authorization: '{b_path}' -> '{renamed_from_baseline[b_path]}'")
                    elif not full_b_path.is_file():
                        if b_path not in authorized_mod_set:
                            baseline_violations.append(f"Protected baseline file was deleted: '{b_path}'")
                    else:
                        curr_sha = get_file_sha256(full_b_path)
                        if curr_sha != orig_sha:
                            # Content changed! Directory prefix NEVER authorizes baseline modification
                            if b_path not in authorized_mod_set:
                                baseline_violations.append(f"Protected baseline file was modified without authorization: '{b_path}'")

            if baseline_violations:
                self.set_gate(
                    "GATE_8_GIT_SCOPE",
                    "FAIL",
                    evidence={"baseline_violations": baseline_violations},
                    details=f"Baseline integrity violation: {'; '.join(baseline_violations)}"
                )
                return

            # 9. Verify Agent-created / Agent-modified changes against approved scope
            agent_changes = set()
            for entry in entries:
                p = entry["path"]
                if p in snapshot.files:
                    # If it was modified and explicitly authorized, count as agent change
                    if p in authorized_mod_set:
                        agent_changes.add(p)
                else:
                    agent_changes.add(p)

            if not agent_changes:
                self.set_gate(
                    "GATE_8_GIT_SCOPE",
                    "PASS",
                    evidence={"baseline_verified_count": len(snapshot.files), "agent_changes": []},
                    details=f"Git scope verified: protected baseline intact ({len(snapshot.files)} files). No new unapproved changes introduced."
                )
                return

            if approved_files is None:
                self.set_gate(
                    "GATE_8_GIT_SCOPE",
                    "BLOCKED",
                    evidence={"agent_changes": sorted(list(agent_changes))},
                    details="Working tree contains changes introduced by agent, but approved_files list was not provided to verify scope."
                )
                return

            norm_approved = {_clean_path(a) for a in approved_files}
            unapproved = set()
            for ac in agent_changes:
                is_approved = False
                for ap in norm_approved:
                    if ac == ap or ac.startswith(ap.rstrip("/") + "/"):
                        is_approved = True
                        break
                if not is_approved:
                    unapproved.add(ac)

            if unapproved:
                self.set_gate(
                    "GATE_8_GIT_SCOPE",
                    "FAIL",
                    evidence={
                        "unapproved_files": sorted(list(unapproved)),
                        "approved_scope": sorted(list(norm_approved)),
                        "agent_changes": sorted(list(agent_changes))
                    },
                    details=f"Unapproved file modifications detected outside task scope: {', '.join(sorted(unapproved))}"
                )
                return

            self.set_gate(
                "GATE_8_GIT_SCOPE",
                "PASS",
                evidence={
                    "agent_changes": sorted(list(agent_changes)),
                    "approved_scope": sorted(list(norm_approved)),
                    "baseline_verified_count": len(snapshot.files)
                },
                details=f"Git scope verified: all {len(agent_changes)} modification(s) match approved scope. All {len(snapshot.files)} protected baseline files retain their initial state."
            )

        except Exception as e:
            self.set_gate("GATE_8_GIT_SCOPE", "BLOCKED", evidence=str(e), details=f"Error evaluating Git scope: {e}")

    def to_dict(self):
        return {
            "project_root": str(self.root),
            "summary": {
                "total": len(self.gates),
                "pass": sum(1 for g in self.gates.values() if g["status"] == "PASS"),
                "fail": sum(1 for g in self.gates.values() if g["status"] == "FAIL"),
                "blocked": sum(1 for g in self.gates.values() if g["status"] == "BLOCKED"),
                "not_run": sum(1 for g in self.gates.values() if g["status"] == "NOT_RUN"),
            },
            "gates": self.gates
        }

    def format_markdown_table(self):
        lines = [
            "| Gate ID | Gate Name | Status | Evidence / Details |",
            "| :--- | :--- | :---: | :--- |"
        ]
        labels = {
            "GATE_1_PREFLIGHT": "Workspace & Preflight",
            "GATE_2_SYNTAX": "PHP Syntax Validation",
            "GATE_3_ROUTING": "Route & Middleware Check",
            "GATE_4_DB_SAFETY": "Database Safety & Isolation",
            "GATE_5_UNIT_FEATURE_TEST": "Unit & Feature Tests",
            "GATE_6_NEGATIVE_AUTH_TEST": "Authorization & Negative Tests",
            "GATE_7_REGRESSION_TEST": "Regression Suite Check",
            "GATE_8_GIT_SCOPE": "Git Scope & Secrets Exclusion"
        }
        for name, data in self.gates.items():
            status = data["status"]
            badge = {"PASS": "[PASS]", "FAIL": "[FAIL]", "BLOCKED": "[BLOCKED]", "NOT_RUN": "[NOT_RUN]"}.get(status, status)
            details = data["details"] or ("Evidence verified" if data["evidence"] else "None")
            lines.append(f"| `{name}` | {labels.get(name, name)} | {badge} | {details} |")
        return "\n".join(lines)


class QualityGateArgumentParser(argparse.ArgumentParser):
    """Custom ArgumentParser that exits with code 3 on argument parsing errors."""
    def error(self, message):
        sys.stderr.write(f"Argument error: {message}\n")
        sys.exit(3)


def main():
    parser = QualityGateArgumentParser(description="Laravel Autopilot Quality Gate Engine")
    parser.add_argument("--project-path", default=".", help="Project root path")
    parser.add_argument("--eval-core", action="store_true", help="Evaluate core automatic gates (Preflight, DB Safety, Git Scope)")
    parser.add_argument("--capture-baseline", action="store_true", help="Capture baseline snapshot before task begins")
    parser.add_argument("--snapshot-file", default=None, help="Path to save or load baseline snapshot JSON")
    parser.add_argument("--baseline-snapshot", default=None, help="Path to baseline snapshot JSON file for verification")
    parser.add_argument("--task-id", default=None, help="Task or session ID bound to baseline snapshot")
    parser.add_argument("--authorized-baseline-mods", nargs="*", default=None, help="Explicitly authorized modifications to baseline files")
    parser.add_argument("--approved-files", nargs="*", default=None, help="List of approved task files")
    parser.add_argument("--baseline-files", nargs="*", default=None, help="List of baseline pre-existing changed files (legacy / fallback)")
    parser.add_argument("--check-syntax", nargs="*", help="List of PHP files to check syntax for")
    parser.add_argument("--json", action="store_true", help="Output in JSON format")

    try:
        args = parser.parse_args()
    except SystemExit as se:
        raise se
    except Exception as e:
        print(f"Argument parsing failure: {e}", file=sys.stderr)
        sys.exit(3)

    if args.capture_baseline:
        try:
            task_id = args.task_id or "default-task"
            snapshot = BaselineSnapshot.capture(args.project_path, task_id=task_id)
            save_path = args.snapshot_file or get_default_snapshot_path(args.project_path, task_id)
            saved_p = snapshot.save(save_path)
            if args.json:
                print(json.dumps({
                    "task_id": snapshot.task_id,
                    "repo_id": snapshot.repo_id,
                    "snapshot_hash": snapshot.snapshot_hash,
                    "trust_level": snapshot.trust_level,
                    "signature_present": bool(snapshot.signature),
                    "snapshot_file": str(saved_p),
                    "files_count": len(snapshot.files),
                    "created_at": snapshot.created_at
                }, indent=2))
            else:
                print(f"Baseline snapshot captured and saved to: {saved_p}")
                print(f"Trust Level: {snapshot.trust_level} | Files: {len(snapshot.files)} | Hash: {snapshot.snapshot_hash[:16]}...")
            sys.exit(0)
        except FileExistsError as fee:
            print(f"BLOCKED: {fee}", file=sys.stderr)
            sys.exit(2)
        except Exception as e:
            print(f"BLOCKED: Failed to capture baseline snapshot: {e}", file=sys.stderr)
            sys.exit(2)

    try:
        registry = QualityGateRegistry(args.project_path)

        if args.eval_core:
            registry.evaluate_preflight()
            registry.evaluate_db_safety()
            snapshot_arg = args.baseline_snapshot or args.snapshot_file
            registry.evaluate_git_scope(
                approved_files=args.approved_files,
                baseline_snapshot=snapshot_arg,
                task_id=args.task_id,
                authorized_baseline_modifications=args.authorized_baseline_mods,
                baseline_changes=args.baseline_files
            )

        if args.check_syntax:
            registry.evaluate_php_syntax(args.check_syntax)

        summary = registry.to_dict()["summary"]

        if args.json:
            print(json.dumps(registry.to_dict(), indent=2))
        else:
            print("=== Laravel Autopilot Quality Gates ===")
            print(registry.format_markdown_table())
            print(f"\nSummary: {summary['pass']} PASS, {summary['fail']} FAIL, {summary['blocked']} BLOCKED, {summary['not_run']} NOT_RUN")
            print("========================================")

        # Process exit codes contract:
        # 0: all required evaluated gates PASS (at least one gate evaluated, all evaluated gates PASS)
        # 1: at least one gate FAIL
        # 2: execution BLOCKED, required evidence missing, or no gates evaluated
        # 3: invalid arguments or internal execution error
        if summary["fail"] > 0:
            sys.exit(1)
        elif summary["blocked"] > 0:
            sys.exit(2)
        elif summary["pass"] > 0:
            sys.exit(0)
        else:
            # No gates were evaluated (all NOT_RUN)
            if not args.json:
                print("BLOCKED: No quality gates were evaluated. At least one gate must be evaluated.", file=sys.stderr)
            sys.exit(2)

    except SystemExit as se:
        raise se
    except Exception as e:
        print(f"Internal execution error: {e}", file=sys.stderr)
        sys.exit(3)


if __name__ == "__main__":
    main()

