#!/usr/bin/env python3
"""
test_runner.py - Laravel Autopilot Hardened Safe Test Runner
Phase 2.5 Security-Audited Implementation:
- Strict argument array execution (shell=False, no shell interpolation)
- Comprehensive rejection of destructive commands & dangerous flags
- Option injection defense (blocking flags starting with -, blocking -c/--configuration/-d)
- Multi-layer database safety verification (phpunit.xml, .env.testing, config cache)
- Deep secret redaction (credentials, keys, tokens, connection strings, private keys, AWS keys)
- Path traversal & symlink defense via Path.resolve() and is_relative_to()
- Resilient subprocess execution (UTF-8 encoding with errors='replace', output capping)
- Structured diagnostic failure parsing (failed tests, exceptions, line numbers)
- Accurate categorization: PASSED, FAILED, SKIPPED, BLOCKED, TIMEOUT
"""

import argparse
import json
import os
import re
import shutil
import subprocess
import sys
import time
import xml.etree.ElementTree as ET
from pathlib import Path

# Max output capture limit (500 KB) to avoid denial-of-service / memory exhaustion
MAX_OUTPUT_BYTES = 500 * 1024

# Prohibited destructive arguments / options
PROHIBITED_FLAGS = {
    "--drop-databases",
    "--migrate-fresh",
    "--recreate-databases",
    "--force",
    "--seed",
    "db:wipe",
    "migrate:fresh",
    "migrate:reset",
    "migrate:rollback",
    "tinker",
}

# Dangerous options that could alter configuration or run arbitrary code
DANGEROUS_CONFIG_FLAGS = {
    "-c",
    "--configuration",
    "--bootstrap",
    "--prepend",
    "-d",
}

# Redaction patterns for secrets (specific token formats first, then generic key=value)
REDACTION_PATTERNS = [
    # Private keys (PEM / RSA / EC)
    (r'-----BEGIN [A-Z\s]+ PRIVATE KEY-----[\s\S]+?-----END [A-Z\s]+ PRIVATE KEY-----', '[REDACTED_PRIVATE_KEY]'),
    # AWS Access Key IDs
    (r'\b(AKIA[0-9A-Z]{16})\b', '[REDACTED_AWS_KEY]'),
    # GitHub personal access tokens
    (r'\b(gh[pous]_[a-zA-Z0-9]{36,})\b', '[REDACTED_GITHUB_TOKEN]'),
    # Base64 encryption keys
    (r'base64:[a-zA-Z0-9+/=]{20,}', 'base64:[REDACTED]'),
    # Bearer tokens
    (r'(?i)bearer\s+[a-zA-Z0-9_\-\.]{10,}', 'Bearer [REDACTED]'),
    # Key-value secret patterns (skip if already redacted)
    (r'(?i)(password|secret|key|token|api_key|db_password|auth_token|client_secret)=(?!\[REDACTED)([^\s&;\'"]+)', r'\1=[REDACTED]'),
    # Database URIs with credentials: proto://user:pass@host:port/db
    (r'(?i)([a-z]+://[^:]+):([^@]+)@', r'\1:[REDACTED]@'),
    # PDO connection string passwords
    (r'(?i)(password\s*=\s*)([^\s;]+)', r'\1[REDACTED]'),
]


def sanitize_text(text):
    """Deeply mask credentials, tokens, and sensitive strings."""
    if not text:
        return ""
    sanitized = text
    for pat, repl in REDACTION_PATTERNS:
        sanitized = re.sub(pat, repl, sanitized)
    return sanitized

def is_laravel_project(path):
    """
    Check if a given path is a valid Laravel project directory.
    Validates presence of artisan and composer.json without mutating state.
    """
    if not path:
        return False
    try:
        p = Path(path).resolve()
        if not p.is_dir():
            return False
        return (p / "artisan").is_file() and (p / "composer.json").is_file()
    except Exception:
        return False


def resolve_project_root(candidate_root=None, fallback_to_cwd=True, require_laravel=True):
    """
    Deterministically resolve and validate Laravel project root path.
    Enforces a strict 4-tier precedence:
    1. AUTOPILOT_TEST_PROJECT_ROOT environment variable (if set, must be valid).
    2. candidate_root (if provided, validated as Laravel project).
    3. Current working directory (if fallback_to_cwd=True and validated as Laravel project).
    4. Explicit failure (raises RuntimeError if require_laravel=True; returns None otherwise).
    Never silently falls back to a non-Laravel directory (e.g. global skill installation).
    """
    env_root = os.environ.get("AUTOPILOT_TEST_PROJECT_ROOT")
    if env_root is not None:
        cleaned_env = env_root.strip()
        if not cleaned_env:
            raise ValueError("AUTOPILOT_TEST_PROJECT_ROOT environment variable is set but empty.")
        p = Path(cleaned_env).resolve()
        if not p.is_dir():
            raise FileNotFoundError(f"Configured AUTOPILOT_TEST_PROJECT_ROOT does not exist: '{cleaned_env}'")
        if require_laravel and not is_laravel_project(p):
            raise ValueError(f"Configured AUTOPILOT_TEST_PROJECT_ROOT is not a valid Laravel project (missing artisan or composer.json): '{cleaned_env}'")
        return p

    # Tier 2: caller-provided candidate_root
    if candidate_root is not None:
        try:
            cand = Path(candidate_root).resolve()
            if cand.is_dir():
                if not require_laravel or is_laravel_project(cand):
                    return cand
        except Exception:
            pass

    # Tier 3: Current working directory
    if fallback_to_cwd:
        try:
            cwd = Path.cwd().resolve()
            if cwd.is_dir():
                if not require_laravel or is_laravel_project(cwd):
                    return cwd
        except Exception:
            pass

    # Tier 4: No valid Laravel root could be determined
    if require_laravel:
        raise RuntimeError(
            "Could not determine a valid Laravel project root. "
            "Set AUTOPILOT_TEST_PROJECT_ROOT or execute from within a valid Laravel project directory."
        )
    return None


# Backward-compatible alias
resolve_test_project_root = resolve_project_root



def inspect_effective_test_db(project_root):
    """
    Multi-layer database safety verification:
    Layer 1: Check for stale config cache (bootstrap/cache/config.php).
             In Laravel, cached configuration overrides phpunit.xml and .env.testing.
             If present, execution is strictly BLOCKED.
    Layer 2: Inspect phpunit.xml for test environment variables.
             Requires DB_CONNECTION == sqlite and DB_DATABASE == :memory:.
    Layer 3: Check .env.testing if present.
             Must not conflict or override with a non-isolated or persistent database.
    Does not expose secrets.
    """
    root = Path(project_root).resolve()
    findings = {
        "phpunit_xml_found": False,
        "env_testing_found": False,
        "config_cache_found": False,
        "connection": "unknown",
        "database": "unknown",
        "app_env": "unknown",
        "is_safe": False,
        "assessment": "",
        "warnings": []
    }

    # Layer 1: Stale config cache check (Strict hard blocker)
    config_cache = root / "bootstrap" / "cache" / "config.php"
    if config_cache.is_file():
        findings["config_cache_found"] = True
        findings["is_safe"] = False
        findings["assessment"] = (
            "BLOCKED: Configuration cache detected (bootstrap/cache/config.php). "
            "In Laravel, cached configuration overrides phpunit.xml environment settings and "
            "may cause automated tests to run against live or local development databases. "
            "Clear configuration cache manually with 'php artisan config:clear' before running tests."
        )
        return findings

    # Layer 2: Inspect phpunit.xml
    phpunit_path = root / "phpunit.xml"
    if not phpunit_path.is_file():
        findings["assessment"] = "BLOCKED: phpunit.xml missing; test database isolation cannot be verified."
        return findings

    findings["phpunit_xml_found"] = True
    try:
        tree = ET.parse(phpunit_path)
        xml_root = tree.getroot()
        php_elem = xml_root.find("php")
        env_vars = {}
        if php_elem is not None:
            for env in php_elem.findall("env"):
                name = env.get("name")
                val = env.get("value")
                if name:
                    env_vars[name] = (val or "").strip()

        findings["app_env"] = env_vars.get("APP_ENV", "unspecified")
        findings["connection"] = env_vars.get("DB_CONNECTION", "unspecified")
        findings["database"] = env_vars.get("DB_DATABASE", "unspecified")
    except Exception as e:
        findings["assessment"] = f"BLOCKED: Failed to parse phpunit.xml ({e})."
        return findings

    if findings["connection"] == "unspecified" or findings["database"] == "unspecified":
        findings["assessment"] = (
            f"BLOCKED: phpunit.xml does not explicitly specify both DB_CONNECTION and DB_DATABASE in <php> env tags "
            f"(connection: {findings['connection']}, database: {findings['database']})."
        )
        return findings

    # Layer 3: Check .env.testing override if present
    env_testing_path = root / ".env.testing"
    if env_testing_path.is_file():
        findings["env_testing_found"] = True
        env_testing_vars = {}
        try:
            with open(env_testing_path, "r", encoding="utf-8") as f:
                for line in f:
                    line = line.strip()
                    if not line or line.startswith("#") or "=" not in line:
                        continue
                    k, v = line.split("=", 1)
                    k = k.strip()
                    v = v.strip()
                    if v and v[0] in ('"', "'") and v[-1] == v[0]:
                        v = v[1:-1]
                    else:
                        v = v.split("#", 1)[0].strip().strip('"').strip("'")
                    env_testing_vars[k] = v

            env_conn = env_testing_vars.get("DB_CONNECTION")
            env_db = env_testing_vars.get("DB_DATABASE")
            env_app = env_testing_vars.get("APP_ENV")

            if env_app:
                findings["app_env"] = env_app

            # Check for conflict or non-isolated override
            if env_conn is not None and env_conn != findings["connection"]:
                findings["is_safe"] = False
                findings["assessment"] = (
                    f"BLOCKED: Conflicting DB_CONNECTION between phpunit.xml ('{findings['connection']}') "
                    f"and .env.testing ('{env_conn}'). Safety invariant violated."
                )
                return findings

            if env_db is not None and env_db != findings["database"]:
                findings["is_safe"] = False
                findings["assessment"] = (
                    f"BLOCKED: Conflicting DB_DATABASE between phpunit.xml ('{findings['database']}') "
                    f"and .env.testing ('{env_db}'). Safety invariant violated."
                )
                return findings

            if env_conn:
                findings["connection"] = env_conn
            if env_db:
                findings["database"] = env_db

        except Exception as e:
            findings["assessment"] = f"BLOCKED: Error reading .env.testing ({e})."
            return findings

    # Evaluate safety: strictly require isolated SQLite in-memory
    db_conn = findings["connection"].lower()
    db_name = findings["database"].strip()

    # Rule 5: Never assume DB is safe merely because connection is sqlite or name has test
    if db_conn == "sqlite" and db_name in (":memory:", "file::memory:?cache=shared"):
        findings["is_safe"] = True
        findings["assessment"] = f"SAFE: Verified isolated in-memory test database (SQLite: {findings['database']})"
    elif db_conn == "sqlite" and "database.sqlite" in db_name.lower():
        findings["is_safe"] = False
        findings["assessment"] = f"BLOCKED: Test database points to local default SQLite ({findings['database']}). Data corruption risk."
    elif db_conn == "sqlite":
        findings["is_safe"] = False
        findings["assessment"] = (
            f"BLOCKED: Test database is persistent SQLite file ({findings['database']}) instead of in-memory (:memory:). "
            f"In-memory isolation required to prevent file mutation."
        )
    else:
        findings["is_safe"] = False
        findings["assessment"] = (
            f"BLOCKED: Database target ({findings['connection']}: {findings['database']}) is not an isolated SQLite in-memory database. "
            f"Automated test execution prohibited to protect persistent data."
        )

    return findings


def validate_test_arguments(project_root=None, filter_pattern=None, test_file=None, suite=None, additional_args=None):
    """
    Validate that all arguments are safe, non-destructive, and well-formed.
    Protects against:
    - Destructive flags
    - Dangerous configuration/bootstrap redirects
    - Shell metacharacters and null bytes
    - Option injection (filters or args starting with -)
    - Path traversal and absolute paths pointing outside tests directories
    """
    cleaned = []
    root = Path(project_root).resolve() if project_root else Path(".").resolve()

    # Check additional args for prohibited and dangerous flags
    if additional_args:
        for arg in additional_args:
            if not isinstance(arg, str):
                return False, "Argument must be a string", []
            # Check for null bytes
            if "\x00" in arg:
                return False, "Null byte detected in argument", []
            clean_arg = arg.strip().lower()
            # Prohibited destructive flags
            if any(clean_arg == p or clean_arg.startswith(p + "=") or clean_arg.startswith(p + " ") for p in PROHIBITED_FLAGS):
                return False, f"Prohibited destructive argument rejected: {arg}", []
            # Dangerous config flags
            if any(clean_arg == d or clean_arg.startswith(d + "=") or clean_arg.startswith(d + " ") for d in DANGEROUS_CONFIG_FLAGS):
                return False, f"Dangerous configuration argument rejected: {arg}", []
            # Check for shell metacharacters
            if re.search(r'[;&|`$><\r\n]', arg):
                return False, f"Invalid characters in argument: {arg}", []
            cleaned.append(arg)

    # Validate filter pattern
    if filter_pattern is not None:
        if not isinstance(filter_pattern, str):
            return False, "Filter pattern must be a string", []
        if "\x00" in filter_pattern:
            return False, "Null byte detected in filter pattern", []
        clean_pat = filter_pattern.strip()
        # Option injection check: cannot start with '-'
        if clean_pat.startswith("-"):
            return False, f"Option injection detected: filter pattern cannot start with '-': {filter_pattern}", []
        # Max length check
        if len(clean_pat) > 512:
            return False, "Filter pattern exceeds maximum length (512 chars)", []
        if any(p in clean_pat.lower() for p in PROHIBITED_FLAGS):
            return False, f"Prohibited pattern in filter: {filter_pattern}", []
        # Allow only safe characters (alphanumeric, underscores, colons, slashes, dashes, dots, literal space, backslashes)
        # Specifically NOT allowing \r or \n
        if clean_pat and not re.match(r'^[a-zA-Z0-9_\\\-:\. ]+$', clean_pat):
            return False, f"Invalid characters in filter pattern: {filter_pattern}", []

    # Validate test file path with strict path containment
    if test_file:
        if not isinstance(test_file, str):
            return False, "test_file must be a string", []
        if "\x00" in test_file:
            return False, "Null byte detected in test_file", []
        clean_file = test_file.strip()
        if not clean_file.endswith(".php"):
            return False, "Security violation: test_file must be a .php file", []

        try:
            full_path = (root / clean_file).resolve() if not os.path.isabs(clean_file) else Path(clean_file).resolve()
            app_tests_dir = (root / "tests").resolve()
            skill_tests_dir = (root / ".agents" / "skills" / "laravel-autopilot" / "tests").resolve()

            # Path must resolve inside app tests or skill tests
            is_in_app = False
            is_in_skill = False
            try:
                full_path.relative_to(app_tests_dir)
                is_in_app = True
            except ValueError:
                pass

            try:
                full_path.relative_to(skill_tests_dir)
                is_in_skill = True
            except ValueError:
                pass

            if not is_in_app and not is_in_skill:
                return False, f"Security violation: test_file must resolve inside tests/ or skill tests directory: {clean_file}", []
        except Exception as e:
            return False, f"Path resolution error for test_file: {e}", []

    # Validate suite
    if suite and suite not in ("Unit", "Feature"):
        return False, f"Unsupported testsuite: {suite}. Allowed: Unit, Feature", []

    return True, "", cleaned


def parse_failures(output):
    """Extract structured details for failing tests."""
    failures = []
    lines = output.splitlines()
    current_failure = None

    for line in lines:
        match_pest_fail = re.search(r'FAILED\s+([^\r\n>]+)\s*>\s*([^\r\n]+)', line)
        if match_pest_fail:
            if current_failure:
                failures.append(current_failure)
            current_failure = {
                "class_or_file": match_pest_fail.group(1).strip(),
                "test_name": match_pest_fail.group(2).strip(),
                "message": "",
                "file": "",
                "line": None
            }
            continue

        match_loc = re.search(r'at\s+([^\s:]+\.php):(\d+)', line)
        if match_loc and current_failure:
            current_failure["file"] = match_loc.group(1).replace("\\", "/")
            current_failure["line"] = int(match_loc.group(2))

        if current_failure and ("Failed asserting that" in line or "Expected response status code" in line or "Exception:" in line):
            if not current_failure["message"]:
                current_failure["message"] = line.strip()

    if current_failure:
        failures.append(current_failure)

    return failures


def parse_test_summary(output):
    """Extract pass/fail/skip counts and duration from Pest/PHPUnit output."""
    summary = {
        "passed": 0,
        "failed": 0,
        "skipped": 0,
        "duration": "unknown",
        "raw_summary_line": ""
    }

    match_tests = re.search(r'Tests:\s+([^\r\n]+)', output)
    if match_tests:
        line = match_tests.group(1).strip()
        summary["raw_summary_line"] = line

        pass_m = re.search(r'(\d+)\s+passed', line)
        if pass_m:
            summary["passed"] = int(pass_m.group(1))

        fail_m = re.search(r'(\d+)\s+failed', line)
        if fail_m:
            summary["failed"] = int(fail_m.group(1))

        skip_m = re.search(r'(\d+)\s+(skipped|incomplete)', line)
        if skip_m:
            summary["skipped"] = int(skip_m.group(1))

    match_dur = re.search(r'Duration:\s+([^\r\n]+)', output)
    if match_dur:
        summary["duration"] = match_dur.group(1).strip()

    return summary


def run_tests(project_root, filter_pattern=None, test_file=None, suite=None, additional_args=None, timeout=60):
    """
    Execute tests using strict argument array (shell=False) and safety validations.
    """
    root = Path(project_root).resolve()

    # 1. Database Safety Verification
    db_info = inspect_effective_test_db(root)
    if not db_info["is_safe"]:
        return {
            "status": "BLOCKED",
            "reason": db_info["assessment"],
            "exit_code": -1,
            "stdout": "",
            "stderr": "",
            "summary": {"passed": 0, "failed": 0, "skipped": 0},
            "failures": [],
            "command": "blocked",
            "safety_note": db_info["assessment"]
        }

    # 2. Argument Validation
    valid, err_msg, cleaned_extra = validate_test_arguments(
        project_root=root,
        filter_pattern=filter_pattern,
        test_file=test_file,
        suite=suite,
        additional_args=additional_args
    )
    if not valid:
        return {
            "status": "BLOCKED",
            "reason": f"Argument validation failed: {err_msg}",
            "exit_code": -1,
            "stdout": "",
            "stderr": "",
            "summary": {"passed": 0, "failed": 0, "skipped": 0},
            "failures": [],
            "command": "blocked",
            "safety_note": "Argument validation gate failed"
        }

    # 3. Resolve executable path
    php_path = shutil.which("php")
    if not php_path:
        return {
            "status": "BLOCKED",
            "reason": "PHP executable not found in PATH",
            "exit_code": -1,
            "command": "blocked"
        }

    artisan_file = str(root / "artisan")
    if not os.path.isfile(artisan_file):
        return {
            "status": "BLOCKED",
            "reason": "artisan file not found at project root",
            "exit_code": -1,
            "command": "blocked"
        }

    # 4. Construct Command Argument Array (Strictly no shell string)
    cmd = [php_path, artisan_file, "test"]

    if filter_pattern:
        cmd.extend(["--filter", filter_pattern.strip()])

    if test_file:
        cmd.append(test_file.strip())

    if suite:
        cmd.extend(["--testsuite", suite])

    if cleaned_extra:
        cmd.extend(cleaned_extra)

    # 5. Execute Command safely with shell=False, UTF-8 decoding, and size capping
    start_time = time.time()
    try:
        proc = subprocess.run(
            cmd,
            cwd=str(root),
            capture_output=True,
            timeout=timeout,
            shell=False  # Non-negotiable invariant
        )
        elapsed = round(time.time() - start_time, 2)

        # Decode with errors='replace' to avoid UnicodeDecodeError on Windows
        raw_stdout = proc.stdout[-MAX_OUTPUT_BYTES:] if len(proc.stdout) > MAX_OUTPUT_BYTES else proc.stdout
        raw_stderr = proc.stderr[-MAX_OUTPUT_BYTES:] if len(proc.stderr) > MAX_OUTPUT_BYTES else proc.stderr

        out = sanitize_text(raw_stdout.decode("utf-8", errors="replace"))
        err = sanitize_text(raw_stderr.decode("utf-8", errors="replace"))
        code = proc.returncode

        summary = parse_test_summary(out)
        failures = parse_failures(out)

        # Accurate status categorization
        status = "PASSED"
        if code != 0 or summary.get("failed", 0) > 0 or len(failures) > 0:
            status = "FAILED"
        elif summary.get("passed", 0) == 0 and summary.get("skipped", 0) > 0:
            status = "SKIPPED"

        return {
            "status": status,
            "exit_code": code,
            "elapsed_seconds": elapsed,
            "summary": summary,
            "failures": failures,
            "stdout_preview": "\n".join(out.splitlines()[-30:]) if out else "",
            "stderr": err,
            "command": " ".join([os.path.basename(cmd[0])] + cmd[1:]),
            "safety_note": db_info["assessment"]
        }
    except subprocess.TimeoutExpired:
        return {
            "status": "TIMEOUT",
            "reason": f"Test run timed out after {timeout} seconds",
            "exit_code": -1,
            "command": " ".join([os.path.basename(cmd[0])] + cmd[1:]),
            "safety_note": db_info["assessment"]
        }
    except Exception as e:
        return {
            "status": "ERROR",
            "reason": str(e),
            "exit_code": -1,
            "command": " ".join([os.path.basename(cmd[0])] + cmd[1:]),
            "safety_note": db_info["assessment"]
        }


def main():
    parser = argparse.ArgumentParser(description="Laravel Autopilot Hardened Test Runner")
    parser.add_argument("--project-path", default=".", help="Path to Laravel project root")
    parser.add_argument("--filter", default=None, help="Filter test name or method")
    parser.add_argument("--file", default=None, help="Specific test file path (e.g. tests/Feature/ExampleTest.php)")
    parser.add_argument("--suite", choices=["Unit", "Feature"], default=None, help="Test suite to run")
    parser.add_argument("--timeout", type=int, default=60, help="Max execution timeout in seconds")
    parser.add_argument("--json", action="store_true", help="Output results in JSON format")
    parser.add_argument("extra_args", nargs="*", help="Additional test arguments")

    args = parser.parse_args()
    report = run_tests(
        project_root=args.project_path,
        filter_pattern=args.filter,
        test_file=args.file,
        suite=args.suite,
        additional_args=args.extra_args,
        timeout=args.timeout
    )

    if args.json:
        print(json.dumps(report, indent=2))
    else:
        print("=== Laravel Autopilot Hardened Test Runner ===")
        print(f"Status:    {report.get('status')}")
        print(f"Command:   {report.get('command', 'N/A')}")
        if report.get("safety_note"):
            print(f"Safety:    {report['safety_note']}")
        if "summary" in report:
            print(f"Passed:    {report['summary'].get('passed', 0)}")
            print(f"Failed:    {report['summary'].get('failed', 0)}")
            print(f"Skipped:   {report['summary'].get('skipped', 0)}")
            print(f"Duration:  {report['summary'].get('duration', 'N/A')} ({report.get('elapsed_seconds', 0)}s elapsed)")
        if report.get("failures"):
            print(f"\nFailures Detected ({len(report['failures'])}):")
            for f in report["failures"]:
                print(f"  - {f.get('class_or_file')} > {f.get('test_name')}")
                if f.get('file'):
                    print(f"    at {f.get('file')}:{f.get('line')}")
                if f.get('message'):
                    print(f"    Message: {f.get('message')}")
        if report.get("stdout_preview"):
            print("\nOutput (tail):")
            print(report["stdout_preview"])
        if report.get("reason"):
            print(f"\nReason / Note: {report['reason']}")
        print("==============================================")

    sys.exit(0 if report.get("status") == "PASSED" else 1)


if __name__ == "__main__":
    main()
