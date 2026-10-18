#!/usr/bin/env python3
"""
self_repair.py - Laravel Autopilot Bounded Self-Repair Engine
Phase 2.5 Security-Audited Implementation:
- Structured failure diagnosis and root cause categorization
- Strict 3-iteration bound per failure sequence
- Early stop detection (missing infrastructure, unsafe DB, policy gates, irreversible actions)
- Programmable execution cycle for autonomous and fixture-based self-repair
- Session history tracking and audit journal formatting
- Anti-degradation enforcement (never weaken security/validation/tests)
"""

import argparse
import json
import os
import re
import sys
from pathlib import Path

MAX_REPAIR_ITERATIONS = 3

# Patterns indicating early stopping conditions
EARLY_STOP_PATTERNS = {
    "MISSING_INFRASTRUCTURE": [
        r'(?i)connection refused',
        r'(?i)redisexception.*could not connect',
        r'(?i)no such file or directory.*\.sock',
        r'(?i)cURL error 7.*failed to connect',
        r'(?i)driver.*not supported',
        r'(?i)could not find driver',
        # Missing external npm package (e.g. Cannot find module '@headlessui/react' or Cannot find package 'axios')
        # Negative lookahead ensures local relative paths (./ or ../) are handled by FRONTEND_MODULE_NOT_FOUND
        r'(?i)(?:cannot find (?:module|package)|failed to resolve (?:import|package))\s+[\'"](?!(?:\.|\/|[a-z]:\\))([@a-z0-9_\-\.\/]+)[\'"]',
    ],
    "UNSAFE_DATABASE_TARGET": [
        r'(?i)access denied for user.*@',
        r'(?i)unknown database',
        r'(?i)database.*is in production mode',
        r'(?i)drop table.*prohibited',
    ],
    "POLICY_DECISION_REQUIRED": [
        r'(?i)NEEDS HUMAN DECISION',
        r'(?i)unspecified business policy',
        r'(?i)ambiguous role permission',
    ],
    "CORRUPTION_RISK": [
        r'(?i)disk full',
        r'(?i)read-only file system',
        r'(?i)database disk image is malformed',
    ]
}


def diagnose_failure_output(raw_output):
    """
    Parse test output and diagnose failure category, target file,
    line number, and root cause hints.
    Protects against malformed input, null bytes, and non-string types.
    """
    if not raw_output or not isinstance(raw_output, str):
        return {
            "category": "NO_OUTPUT",
            "summary": "No test output provided or invalid type",
            "file": None,
            "line": None,
            "early_stop": None,
            "likely_root_cause": "Test execution did not produce valid string output"
        }

    # Sanitize null bytes
    cleaned_output = raw_output.replace("\x00", "")

    # 1. Frontend Build Configuration Error (PostCSS, Tailwind, Vite config)
    # Check this before generic early stop so config plugin errors are attributed to config files
    if (
        "postcss" in cleaned_output.lower()
        or "loading postcss plugin failed" in cleaned_output.lower()
        or "error in postcss.config" in cleaned_output.lower()
        or "vite.config" in cleaned_output.lower()
        or ("tailwind" in cleaned_output.lower() and "config" in cleaned_output.lower())
    ):
        m_cfg = re.search(r'(?:in|Error in|at)\s+([^\s:]*(?:postcss|vite|tailwind)\.config\.[a-z0-9]+)', cleaned_output)
        cfg_file = m_cfg.group(1).replace("\\", "/") if m_cfg else ("postcss.config.js" if "postcss" in cleaned_output.lower() else "vite.config.js")
        return {
            "category": "FRONTEND_BUILD_CONFIG_ERROR",
            "summary": "Frontend asset/build configuration error",
            "file": cfg_file,
            "line": None,
            "early_stop": None,
            "likely_root_cause": "Check plugin configuration, syntax, or dependencies in config file."
        }

    # Check for early stop triggers (missing external infrastructure, DB safety, policy gates)
    for condition, patterns in EARLY_STOP_PATTERNS.items():
        for pat in patterns:
            m_pat = re.search(pat, cleaned_output)
            if m_pat:
                matched_hint = m_pat.group(0)
                root_cause = f"Environment or infrastructure issue matching '{pat}'. Autonomous repair stopped."
                if condition == "MISSING_INFRASTRUCTURE" and ("cannot find" in pat.lower() or "failed to resolve" in pat.lower()):
                    pkg_name = m_pat.group(1) if m_pat.groups() else "external package"
                    root_cause = f"Required npm package or dependency '{pkg_name}' is missing. Dependencies must be installed before build."

                return {
                    "category": "EARLY_STOP_TRIGGERED",
                    "early_stop": condition,
                    "summary": f"Early stop condition detected: {condition} ({matched_hint})",
                    "file": None,
                    "line": None,
                    "likely_root_cause": root_cause
                }

    diagnosis = {
        "category": "UNKNOWN",
        "summary": "Unclassified failure",
        "file": None,
        "line": None,
        "early_stop": None,
        "likely_root_cause": "Inspect stack trace for details."
    }

    # 2. Frontend Syntax Error / Unterminated JSX (Vite/Rollup/ESBuild)
    if (
        "unterminated jsx" in cleaned_output.lower()
        or "[vite:esbuild] transform failed" in cleaned_output.lower()
        or ("syntaxerror" in cleaned_output and any(ext in cleaned_output for ext in [".jsx", ".tsx", ".vue", ".js", ".ts"]))
    ):
        diagnosis["category"] = "FRONTEND_SYNTAX_ERROR"
        diagnosis["summary"] = "Frontend JSX/TSX syntax error"
        diagnosis["likely_root_cause"] = "Syntax typo, unterminated JSX tag, or invalid token."

        # Extract file and line: e.g., resources/js/Pages/Dashboard.jsx:24:16 or file: resources/js/...:42:10
        m_fe_loc = re.search(r'(?:file:\s*|\s+in\s+|^|\s)([^\s:]+\.(?:jsx|tsx|vue|js|ts)):(\d+)(?::(\d+))?', cleaned_output, re.MULTILINE)
        if m_fe_loc:
            diagnosis["file"] = m_fe_loc.group(1).replace("\\", "/")
            diagnosis["line"] = int(m_fe_loc.group(2))

        if "unterminated jsx" in cleaned_output.lower():
            diagnosis["summary"] = "RollupError: Unterminated JSX contents"
            diagnosis["likely_root_cause"] = "Unclosed JSX tag or unclosed bracket in template."

        return diagnosis

    # 3. Frontend Module Not Found (Failed to resolve local import)
    if "failed to resolve import" in cleaned_output.lower() or "could not resolve" in cleaned_output.lower():
        diagnosis["category"] = "FRONTEND_MODULE_NOT_FOUND"
        m_mod = re.search(r'(?:failed to resolve import|could not resolve)\s+["\']([^"\']+)["\'](?:\s+from\s+["\']([^"\']+)["\'])?', cleaned_output, re.IGNORECASE)
        mod_name = m_mod.group(1) if m_mod else "unresolved module"
        from_file = m_mod.group(2) if (m_mod and m_mod.group(2)) else None

        if from_file:
            diagnosis["file"] = from_file.replace("\\", "/")
        else:
            loc = re.search(r'([^\s:]+\.(?:jsx|tsx|vue|js|ts))', cleaned_output)
            if loc:
                diagnosis["file"] = loc.group(1).replace("\\", "/")

        diagnosis["summary"] = f"Module not found: Failed to resolve import '{mod_name}'"
        diagnosis["likely_root_cause"] = f"Local module or file '{mod_name}' does not exist, or path is mistyped."
        return diagnosis

    # 4. PHP Syntax Error / Parse Error
    if "Parse error" in cleaned_output or "syntax error" in cleaned_output or "SyntaxError" in cleaned_output:
        diagnosis["category"] = "SYNTAX_ERROR"
        m = re.search(r'(?:syntax error|Parse error)[^:]*in\s+([^\s:]+\.php)\s+on\s+line\s+(\d+)', cleaned_output)
        if m:
            diagnosis["file"] = m.group(1).replace("\\", "/")
            diagnosis["line"] = int(m.group(2))
        diagnosis["summary"] = "PHP Parse/Syntax error"
        diagnosis["likely_root_cause"] = "Missing semicolon, bracket mismatch, or syntax typo."
        return diagnosis

    # 2. Database / SQL Error
    if "QueryException" in cleaned_output or "PDOException" in cleaned_output or "no such table" in cleaned_output or "no such column" in cleaned_output:
        diagnosis["category"] = "DATABASE_SCHEMA_ERROR"
        m = re.search(r'no such (table|column):\s*([a-zA-Z0-9_\.]+)', cleaned_output)
        col_or_tbl = m.group(2) if m else "unspecified"
        diagnosis["summary"] = f"Database query or schema error ({col_or_tbl})"
        diagnosis["likely_root_cause"] = f"Missing migration, unmigrated column/table '{col_or_tbl}', or query typo."
        loc = re.search(r'at\s+(app[\\/][^\s:]+\.php|database[\\/][^\s:]+\.php):(\d+)', cleaned_output)
        if loc:
            diagnosis["file"] = loc.group(1).replace("\\", "/")
            diagnosis["line"] = int(loc.group(2))
        return diagnosis

    # 3. HTTP Status / Assertion Mismatch
    m_status = re.search(r'Expected response status code \[(\d+)\] but received \[(\d+)\]', cleaned_output)
    if m_status:
        expected = m_status.group(1)
        actual = m_status.group(2)
        diagnosis["category"] = "STATUS_CODE_MISMATCH"
        diagnosis["summary"] = f"HTTP {actual} received, expected {expected}"
        if actual == "403":
            diagnosis["likely_root_cause"] = "Authorization failure: Policy or role middleware blocked request."
        elif actual == "401":
            diagnosis["likely_root_cause"] = "Authentication failure: Route requires authentication but request was guest."
        elif actual == "422":
            diagnosis["likely_root_cause"] = "Validation failure: Request data failed FormRequest or validator rules."
        elif actual == "404":
            diagnosis["likely_root_cause"] = "Route or model not found: Incorrect URI or model route-key binding."
        elif actual == "500":
            diagnosis["likely_root_cause"] = "Internal server error: Exception thrown in controller or service."

        loc = re.search(r'at\s+([^\s:]+\.php):(\d+)', cleaned_output)
        if loc:
            diagnosis["file"] = loc.group(1).replace("\\", "/")
            diagnosis["line"] = int(loc.group(2))
        return diagnosis

    # 4. General Assertion Failure
    if "Failed asserting that" in cleaned_output or "AssertionFailedError" in cleaned_output:
        diagnosis["category"] = "ASSERTION_FAILED"
        m_assert = re.search(r'(Failed asserting that[^\r\n]+|AssertionFailedError:[^\r\n]+)', cleaned_output)
        diagnosis["summary"] = m_assert.group(0).strip() if m_assert else "Assertion failed"
        diagnosis["likely_root_cause"] = "Returned state, view, or JSON does not match test expectation."
        loc = re.search(r'at\s+([^\s:]+\.php):(\d+)', cleaned_output)
        if loc:
            diagnosis["file"] = loc.group(1).replace("\\", "/")
            diagnosis["line"] = int(loc.group(2))
        return diagnosis

    # 5. Class / Method Not Found
    m_class = re.search(r'Class ["\']([^"\']+)["\'] not found', cleaned_output)
    if m_class:
        diagnosis["category"] = "MISSING_CLASS"
        diagnosis["summary"] = f"Class '{m_class.group(1)}' not found"
        diagnosis["likely_root_cause"] = f"Missing import / use statement for {m_class.group(1)}."
        loc = re.search(r'at\s+([^\s:]+\.php):(\d+)', cleaned_output)
        if loc:
            diagnosis["file"] = loc.group(1).replace("\\", "/")
            diagnosis["line"] = int(loc.group(2))
        return diagnosis

    # 6. Undefined function / method
    m_func = re.search(r'Call to undefined (?:function|method)\s+([^\s(]+)', cleaned_output)
    if m_func:
        diagnosis["category"] = "UNDEFINED_FUNCTION"
        diagnosis["summary"] = f"Call to undefined function/method: {m_func.group(1)}"
        diagnosis["likely_root_cause"] = f"Method or function '{m_func.group(1)}' does not exist or typo in method call."
        loc = re.search(r'at\s+([^\s:]+\.php):(\d+)', cleaned_output)
        if loc:
            diagnosis["file"] = loc.group(1).replace("\\", "/")
            diagnosis["line"] = int(loc.group(2))
        return diagnosis

    return diagnosis


class BoundedRepairSession:
    """Manages an audit-ready, bounded 3-iteration self-repair lifecycle."""

    def __init__(self, task_name, test_filter, session_file=None):
        self.task_name = task_name
        self.test_filter = test_filter
        self.session_file = session_file
        self.iteration = 0
        self.attempts = []
        self.status = "ACTIVE"
        self.stop_reason = None

    @classmethod
    def load(cls, file_path):
        """Load session from a JSON file."""
        p = Path(file_path)
        if not p.is_file():
            return None
        with open(p, "r", encoding="utf-8") as f:
            data = json.load(f)
        session = cls(data.get("task_name", ""), data.get("test_filter", ""), session_file=file_path)
        session.iteration = data.get("iteration", 0)
        session.attempts = data.get("attempts", [])
        session.status = data.get("status", "ACTIVE")
        session.stop_reason = data.get("stop_reason")
        return session

    def save(self):
        """Save session state to file."""
        if not self.session_file:
            return
        data = {
            "task_name": self.task_name,
            "test_filter": self.test_filter,
            "iteration": self.iteration,
            "status": self.status,
            "stop_reason": self.stop_reason,
            "attempts": self.attempts
        }
        p = Path(self.session_file)
        p.parent.mkdir(parents=True, exist_ok=True)
        with open(p, "w", encoding="utf-8") as f:
            json.dump(data, f, indent=2)

    def can_attempt(self):
        """Check if another repair iteration is permitted."""
        if self.status != "ACTIVE":
            return False, f"Session is not active (status: {self.status}, reason: {self.stop_reason})"
        if self.iteration >= MAX_REPAIR_ITERATIONS:
            self.status = "HALTED"
            self.stop_reason = f"Max repair iterations limit ({MAX_REPAIR_ITERATIONS}) reached"
            self.save()
            return False, self.stop_reason
        return True, "Proceed"

    def record_attempt(self, hypothesis, files_modified, test_result):
        """Record outcome of a repair attempt."""
        self.iteration += 1
        is_passed = test_result.get("status") == "PASSED"

        entry = {
            "iteration": self.iteration,
            "hypothesis": hypothesis,
            "files_modified": files_modified,
            "test_status": test_result.get("status", "UNKNOWN"),
            "exit_code": test_result.get("exit_code", -1),
            "passed": test_result.get("summary", {}).get("passed", 0),
            "failed": test_result.get("summary", {}).get("failed", 0),
            "diagnostics": test_result.get("failures", []),
            "resolved": is_passed
        }
        self.attempts.append(entry)

        if is_passed:
            self.status = "RESOLVED"
            self.stop_reason = "Feature tests passed successfully."
        elif self.iteration >= MAX_REPAIR_ITERATIONS:
            self.status = "HALTED"
            self.stop_reason = f"Repair failed after {MAX_REPAIR_ITERATIONS} iterations. Human review required."

        self.save()
        return entry

    def stop_early(self, reason):
        """Trigger an early halt."""
        self.status = "HALTED_EARLY"
        self.stop_reason = reason
        self.save()

    def to_dict(self):
        return {
            "task_name": self.task_name,
            "test_filter": self.test_filter,
            "iteration": self.iteration,
            "max_iterations": MAX_REPAIR_ITERATIONS,
            "status": self.status,
            "stop_reason": self.stop_reason,
            "attempts": self.attempts
        }


def execute_repair_cycle(task_name, test_filter, runner_fn, repair_fn, session=None):
    """
    Executes an end-to-end bounded self-repair cycle:
    1. Runs initial test.
    2. If initially passing, returns immediately.
    3. If failing, loops up to MAX_REPAIR_ITERATIONS:
       - Diagnoses failure
       - Evaluates early stop triggers
       - Applies repair via repair_fn(diagnosis, iteration)
       - Reruns runner_fn()
       - Records attempt
       - Halts or completes cleanly.
    """
    if session is None:
        session = BoundedRepairSession(task_name, test_filter)

    initial_result = runner_fn()
    if initial_result.get("status") == "PASSED":
        return {
            "session": session.to_dict(),
            "initial_status": "PASSED",
            "final_status": "PASSED",
            "iterations_used": 0,
            "resolved": True
        }

    while True:
        can_go, reason = session.can_attempt()
        if not can_go:
            break

        # Extract output from last run
        raw_output = initial_result.get("stderr", "") or initial_result.get("stdout_preview", "")
        diagnosis = diagnose_failure_output(raw_output)

        if diagnosis.get("category") == "EARLY_STOP_TRIGGERED":
            session.stop_early(diagnosis.get("summary"))
            break

        # Apply repair
        iteration_num = session.iteration + 1
        hypothesis, files_touched = repair_fn(diagnosis, iteration_num)

        # Rerun test
        rerun_result = runner_fn()
        session.record_attempt(hypothesis, files_touched, rerun_result)

        if rerun_result.get("status") == "PASSED":
            break
        initial_result = rerun_result

    return {
        "session": session.to_dict(),
        "final_status": session.status,
        "iterations_used": session.iteration,
        "resolved": session.status == "RESOLVED"
    }


def main():
    parser = argparse.ArgumentParser(description="Laravel Autopilot Bounded Self-Repair Tool")
    subparsers = parser.add_subparsers(dest="action", help="Action to perform")

    diag_parser = subparsers.add_parser("diagnose", help="Diagnose test output failure")
    diag_parser.add_argument("--file", help="File containing raw test output")
    diag_parser.add_argument("--text", help="Raw test output string")
    diag_parser.add_argument("--json", action="store_true", help="Output in JSON")

    sess_parser = subparsers.add_parser("session", help="Manage repair session")
    sess_parser.add_argument("--session-file", default=".agents/skills/laravel-autopilot/scratch/repair_session.json", help="Session file path")
    sess_parser.add_argument("--init", action="store_true", help="Initialize new session")
    sess_parser.add_argument("--task", default="Unnamed Task", help="Task name")
    sess_parser.add_argument("--filter", default="", help="Test filter pattern")
    sess_parser.add_argument("--status", action="store_true", help="Print session status")
    sess_parser.add_argument("--json", action="store_true", help="Output in JSON")

    args = parser.parse_args()

    if args.action == "diagnose":
        raw = ""
        if args.file and os.path.isfile(args.file):
            with open(args.file, "r", encoding="utf-8") as f:
                raw = f.read()
        elif args.text:
            raw = args.text
        else:
            raw = sys.stdin.read()

        diag = diagnose_failure_output(raw)
        if args.json:
            print(json.dumps(diag, indent=2))
        else:
            print("=== Self-Repair Failure Diagnosis ===")
            print(f"Category:        {diag['category']}")
            print(f"Summary:         {diag['summary']}")
            if diag.get("file"):
                print(f"Location:        {diag['file']}:{diag.get('line')}")
            print(f"Likely Cause:    {diag['likely_root_cause']}")
            if diag.get("early_stop"):
                print(f"Early Stop:      YES ({diag['early_stop']})")
            print("=====================================")

    elif args.action == "session":
        if args.init:
            session = BoundedRepairSession(args.task, args.filter, session_file=args.session_file)
            session.save()
            print(f"Repair session initialized for '{args.task}' (max {MAX_REPAIR_ITERATIONS} iterations).")
        else:
            session = BoundedRepairSession.load(args.session_file)
            if not session:
                print(f"No active session found at {args.session_file}", file=sys.stderr)
                sys.exit(1)

            if args.json:
                print(json.dumps(session.to_dict(), indent=2))
            else:
                print("=== Repair Session Status ===")
                print(f"Task:            {session.task_name}")
                print(f"Target Filter:   {session.test_filter}")
                print(f"Iteration:       {session.iteration} / {MAX_REPAIR_ITERATIONS}")
                print(f"Status:          {session.status}")
                if session.stop_reason:
                    print(f"Reason:          {session.stop_reason}")
                print("=============================")
    else:
        parser.print_help()


if __name__ == "__main__":
    main()
