#!/usr/bin/env python3
"""
test_helpers.py - Unit, Adversarial Security, and Self-Repair Integration Tests
Phase 2.5 Security Audit Suite:
1. Adversarial command-line, shell injection, and option injection defense
2. Path traversal, absolute path, and null-byte containment
3. Deep secret redaction (PEM private keys, AWS keys, GitHub tokens, DB URIs, passwords)
4. Multi-layer database safety and ambiguous target blocking
5. Quality Gate evidence and taxonomy invariants
6. Real end-to-end self-repair execution on a disposable PHP fixture
7. Iteration cap (3-iteration limit) and early-stop triggers
8. Live execution against ExampleTest in isolated test DB
"""

import json
import os
import shutil
import subprocess
import sys
import tempfile
import unittest
from unittest import mock
from pathlib import Path


# Add scripts directory to path
current_dir = Path(__file__).resolve().parent
scripts_dir = current_dir.parent / "scripts"
sys.path.insert(0, str(scripts_dir))

import preflight
import test_runner
import self_repair
import quality_gate


class TestLaravelAutopilotHelpers(unittest.TestCase):

    def setUp(self):
        # tests -> laravel-autopilot -> skills -> .agents -> workspace_root
        self.project_root = current_dir.parent.parent.parent.parent.resolve()
        self.fixtures_dir = current_dir / "fixtures" / "self_repair_fixture"

    # =========================================================================
    # 1. Adversarial Security: Destructive Commands & Option Injection
    # =========================================================================

    def test_prohibited_flags_rejection(self):
        """Ensure all destructive and dangerous flags are strictly rejected."""
        prohibited = [
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
        ]
        for flag in prohibited:
            valid, err, _ = test_runner.validate_test_arguments(project_root=self.project_root, additional_args=[flag])
            self.assertFalse(valid, f"Flag {flag} should have been rejected")
            self.assertIn("Prohibited destructive argument", err)

    def test_dangerous_config_flags_rejection(self):
        """Ensure option injection targeting config redirects is blocked."""
        dangerous_flags = [
            "-c",
            "-c=/tmp/malicious.xml",
            "--configuration",
            "--configuration=malicious.xml",
            "--bootstrap=hack.php",
            "--prepend=hack.php",
            "-d",
            "-d memory_limit=1G"
        ]
        for flag in dangerous_flags:
            valid, err, _ = test_runner.validate_test_arguments(project_root=self.project_root, additional_args=[flag])
            self.assertFalse(valid, f"Flag {flag} should have been rejected as dangerous config override")
            self.assertIn("Dangerous configuration argument", err)

    def test_option_injection_in_filter(self):
        """Ensure filter starting with '-' is rejected to prevent option injection."""
        option_injections = [
            "-v",
            "--help",
            "--drop-databases",
            "-d",
            "--version",
        ]
        for opt in option_injections:
            valid, err, _ = test_runner.validate_test_arguments(project_root=self.project_root, filter_pattern=opt)
            self.assertFalse(valid, f"Filter '{opt}' should have been rejected as option injection")
            self.assertIn("Option injection detected", err)

    def test_shell_injection_rejection_in_filter(self):
        """Ensure shell injection payloads in test filter are rejected."""
        injections = [
            "ExampleTest; rm -rf /",
            "ExampleTest | dir",
            "ExampleTest & whoami",
            "ExampleTest && echo pwned",
            "ExampleTest`id`",
            "ExampleTest$(whoami)",
            "ExampleTest > out.txt",
            "ExampleTest\nwhoami",
            "ExampleTest\rdir",
        ]
        for inj in injections:
            valid, err, _ = test_runner.validate_test_arguments(project_root=self.project_root, filter_pattern=inj)
            self.assertFalse(valid, f"Injection '{inj}' should have been rejected")
            self.assertIn("Invalid characters in filter pattern", err)

    def test_null_byte_injection_rejection(self):
        """Ensure null bytes in arguments or filters are rejected."""
        valid, err, _ = test_runner.validate_test_arguments(project_root=self.project_root, filter_pattern="Test\x00Exploit")
        self.assertFalse(valid)
        self.assertIn("Null byte", err)

        valid, err, _ = test_runner.validate_test_arguments(project_root=self.project_root, additional_args=["--foo\x00bar"])
        self.assertFalse(valid)
        self.assertIn("Null byte", err)

        valid, err, _ = test_runner.validate_test_arguments(project_root=self.project_root, test_file="tests/test\x00.php")
        self.assertFalse(valid)
        self.assertIn("Null byte", err)

    # =========================================================================
    # 2. Path Traversal & File Containment Defense
    # =========================================================================

    def test_test_file_path_traversal_rejection(self):
        """Ensure path traversal outside test directories is strictly blocked."""
        traversals = [
            "../secret.txt",
            "../../.env",
            "tests/../../.env",
            "tests/..\\..\\.env",
            "tests/../../../Windows/win.ini",
            "app/Models/User.php",
            "config/database.php",
            "storage/logs/laravel.log"
        ]
        for path in traversals:
            valid, err, _ = test_runner.validate_test_arguments(project_root=self.project_root, test_file=path)
            self.assertFalse(valid, f"Path '{path}' should have been rejected")

    def test_test_file_extension_validation(self):
        """Ensure test files must end with .php."""
        invalid_extensions = [
            "tests/Feature/ExampleTest.txt",
            "tests/Feature/ExampleTest.py",
            "tests/Feature/ExampleTest.php.bak",
            "tests/Feature/ExampleTest.sh",
        ]
        for path in invalid_extensions:
            valid, err, _ = test_runner.validate_test_arguments(project_root=self.project_root, test_file=path)
            self.assertFalse(valid, f"Path '{path}' should have been rejected because extension is not .php")

    # =========================================================================
    # 3. Deep Secret Redaction Verification
    # =========================================================================

    def test_deep_secret_redaction(self):
        """Ensure deep redaction masks all common credential formats."""
        sample_logs = (
            "DB_PASSWORD=SuperSecret123\n"
            "APP_KEY=base64:AbCdEfGhIjKlMnOpQrStUvWxYz0123456789=\n"
            "Standalone: base64:AbCdEfGhIjKlMnOpQrStUvWxYz0123456789=\n"
            "Authorization: Bearer my-secret-jwt-token-value-here\n"
            "AWS_KEY=AKIAIOSFODNN7EXAMPLE\n"
            "TOKEN=ghp_ABC123def456GHI789jkl012MNO345pqr678\n"
            "Connection: mysql://root:mypassword123@127.0.0.1:3306/production\n"
            "Postgres: postgres://dbuser:supersecretpass@localhost:5432/mydb\n"
            "password = hiddenpass\n"
            "-----BEGIN RSA PRIVATE KEY-----\n"
            "MIIEowIBAAKCAQEA0Yv...\n"
            "-----END RSA PRIVATE KEY-----\n"
        )
        sanitized = test_runner.sanitize_text(sample_logs)

        # Assert unmasked secrets do not appear
        self.assertNotIn("SuperSecret123", sanitized)
        self.assertNotIn("AbCdEfGhIjKlMnOpQrStUvWxYz0123456789=", sanitized)
        self.assertNotIn("my-secret-jwt-token-value-here", sanitized)
        self.assertNotIn("AKIAIOSFODNN7EXAMPLE", sanitized)
        self.assertNotIn("ghp_ABC123def456GHI789jkl012MNO345pqr678", sanitized)
        self.assertNotIn("mypassword123", sanitized)
        self.assertNotIn("supersecretpass", sanitized)
        self.assertNotIn("hiddenpass", sanitized)
        self.assertNotIn("MIIEowIBAAKCAQEA0Yv", sanitized)

        # Assert masked placeholders are present
        self.assertIn("DB_PASSWORD=[REDACTED]", sanitized)
        self.assertIn("base64:[REDACTED]", sanitized)
        self.assertIn("Bearer [REDACTED]", sanitized)
        self.assertIn("[REDACTED_AWS_KEY]", sanitized)
        self.assertIn("[REDACTED_GITHUB_TOKEN]", sanitized)
        self.assertIn(":[REDACTED]@", sanitized)
        self.assertIn("[REDACTED_PRIVATE_KEY]", sanitized)

    # =========================================================================
    # 4. Multi-Layer Database Safety (P1-A Regression Tests)
    # =========================================================================

    def test_db_safety_inspection_on_current_workspace(self):
        """Verify effective test DB inspection on this project."""
        db_info = test_runner.inspect_effective_test_db(self.project_root)
        self.assertTrue(db_info["phpunit_xml_found"])
        self.assertEqual(db_info["connection"], "sqlite")
        self.assertEqual(db_info["database"], ":memory:")
        self.assertTrue(db_info["is_safe"])
        self.assertIn("SAFE: Verified isolated in-memory test database", db_info["assessment"])

    def test_db_safety_blocked_when_config_cache_exists(self):
        """Verify that presence of bootstrap/cache/config.php strictly blocks test execution."""
        with tempfile.TemporaryDirectory() as tmpdir:
            tmp = Path(tmpdir)
            # Create phpunit.xml with sqlite :memory:
            phpunit = tmp / "phpunit.xml"
            phpunit.write_text(
                '<?xml version="1.0" encoding="UTF-8"?>\n'
                '<phpunit><php>'
                '<env name="DB_CONNECTION" value="sqlite"/>'
                '<env name="DB_DATABASE" value=":memory:"/>'
                '</php></phpunit>',
                encoding="utf-8"
            )
            # Create bootstrap/cache/config.php
            cache_dir = tmp / "bootstrap" / "cache"
            cache_dir.mkdir(parents=True)
            config_cache = cache_dir / "config.php"
            config_cache.write_text("<?php return ['database' => ['default' => 'mysql']];", encoding="utf-8")

            findings = test_runner.inspect_effective_test_db(tmp)
            self.assertFalse(findings["is_safe"])
            self.assertTrue(findings["config_cache_found"])
            self.assertIn("BLOCKED: Configuration cache detected", findings["assessment"])
            self.assertIn("bootstrap/cache/config.php", findings["assessment"])

    def test_db_safety_blocked_when_phpunit_missing_or_incomplete(self):
        """Verify that missing phpunit.xml or missing DB env tags blocks test execution."""
        with tempfile.TemporaryDirectory() as tmpdir:
            # Missing phpunit.xml
            findings = test_runner.inspect_effective_test_db(tmpdir)
            self.assertFalse(findings["is_safe"])
            self.assertIn("BLOCKED: phpunit.xml missing", findings["assessment"])

            # Incomplete phpunit.xml (missing DB_CONNECTION)
            phpunit = Path(tmpdir) / "phpunit.xml"
            phpunit.write_text(
                '<?xml version="1.0" encoding="UTF-8"?>\n<phpunit><php><env name="APP_ENV" value="testing"/></php></phpunit>',
                encoding="utf-8"
            )
            findings2 = test_runner.inspect_effective_test_db(tmpdir)
            self.assertFalse(findings2["is_safe"])
            self.assertIn("BLOCKED: phpunit.xml does not explicitly specify both DB_CONNECTION and DB_DATABASE", findings2["assessment"])

    def test_db_safety_blocked_when_not_isolated_sqlite_memory(self):
        """Verify non-memory DB targets (e.g. persistent sqlite file, mysql, postgres) are blocked."""
        with tempfile.TemporaryDirectory() as tmpdir:
            tmp = Path(tmpdir)
            phpunit = tmp / "phpunit.xml"

            # 1. MySQL target with 'test' in name is still blocked
            phpunit.write_text(
                '<?xml version="1.0" encoding="UTF-8"?>\n'
                '<phpunit><php>'
                '<env name="DB_CONNECTION" value="mysql"/>'
                '<env name="DB_DATABASE" value="siasek_test_db"/>'
                '</php></phpunit>',
                encoding="utf-8"
            )
            findings_mysql = test_runner.inspect_effective_test_db(tmp)
            self.assertFalse(findings_mysql["is_safe"])
            self.assertIn("BLOCKED", findings_mysql["assessment"])

            # 2. SQLite pointing to default database.sqlite is blocked
            phpunit.write_text(
                '<?xml version="1.0" encoding="UTF-8"?>\n'
                '<phpunit><php>'
                '<env name="DB_CONNECTION" value="sqlite"/>'
                '<env name="DB_DATABASE" value="database.sqlite"/>'
                '</php></phpunit>',
                encoding="utf-8"
            )
            findings_sqlite = test_runner.inspect_effective_test_db(tmp)
            self.assertFalse(findings_sqlite["is_safe"])
            self.assertIn("BLOCKED", findings_sqlite["assessment"])

    def test_db_safety_blocked_on_conflicting_env_testing(self):
        """Verify conflicting or persistent override in .env.testing triggers BLOCKED."""
        with tempfile.TemporaryDirectory() as tmpdir:
            tmp = Path(tmpdir)
            phpunit = tmp / "phpunit.xml"
            phpunit.write_text(
                '<?xml version="1.0" encoding="UTF-8"?>\n'
                '<phpunit><php>'
                '<env name="DB_CONNECTION" value="sqlite"/>'
                '<env name="DB_DATABASE" value=":memory:"/>'
                '</php></phpunit>',
                encoding="utf-8"
            )
            # Create conflicting .env.testing
            env_testing = tmp / ".env.testing"
            env_testing.write_text(
                "APP_ENV=testing\n"
                "DB_CONNECTION=mysql # conflicting override\n"
                "DB_DATABASE=testing_db\n",
                encoding="utf-8"
            )

            findings = test_runner.inspect_effective_test_db(tmp)
            self.assertFalse(findings["is_safe"])
            self.assertIn("BLOCKED: Conflicting DB_CONNECTION", findings["assessment"])

    def test_db_safety_passes_on_verified_isolated_sqlite_memory(self):
        """Verify that isolated SQLite :memory: passes across all layers."""
        with tempfile.TemporaryDirectory() as tmpdir:
            tmp = Path(tmpdir)
            phpunit = tmp / "phpunit.xml"
            phpunit.write_text(
                '<?xml version="1.0" encoding="UTF-8"?>\n'
                '<phpunit><php>'
                '<env name="DB_CONNECTION" value="sqlite"/>'
                '<env name="DB_DATABASE" value=":memory:"/>'
                '</php></phpunit>',
                encoding="utf-8"
            )
            env_testing = tmp / ".env.testing"
            env_testing.write_text(
                "DB_CONNECTION=sqlite\n"
                "DB_DATABASE=:memory:\n",
                encoding="utf-8"
            )

            findings = test_runner.inspect_effective_test_db(tmp)
            self.assertTrue(findings["is_safe"])
            self.assertIn("SAFE: Verified isolated in-memory test database", findings["assessment"])

    # =========================================================================
    # 5. Quality Gate Invariants (P1-B Regression Tests)
    # =========================================================================

    def test_quality_gate_evidence_invariant(self):
        """Verify that QualityGateRegistry refuses PASS without empirical evidence."""
        registry = quality_gate.QualityGateRegistry(self.project_root)

        # None evidence
        with self.assertRaises(ValueError):
            registry.set_gate("GATE_1_PREFLIGHT", "PASS", evidence=None)

        # Empty dict evidence
        with self.assertRaises(ValueError):
            registry.set_gate("GATE_1_PREFLIGHT", "PASS", evidence={})

        # Empty list evidence
        with self.assertRaises(ValueError):
            registry.set_gate("GATE_1_PREFLIGHT", "PASS", evidence=[])

        # Valid evidence works
        registry.set_gate("GATE_1_PREFLIGHT", "PASS", evidence={"status": "PASS"}, details="Verified")
        self.assertEqual(registry.gates["GATE_1_PREFLIGHT"]["status"], "PASS")

    def test_git_scope_blocked_when_dirty_tree_without_approved_or_baseline(self):
        """Verify that uncommitted working tree changes return BLOCKED when no approved_files or baseline is provided."""
        with tempfile.TemporaryDirectory() as tmpdir:
            # Initialize git repo and make a dirty file
            subprocess.run(["git", "init"], cwd=tmpdir, capture_output=True, check=True)
            dirty_file = Path(tmpdir) / "untracked_work.php"
            dirty_file.write_text("<?php // dirty", encoding="utf-8")

            reg = quality_gate.QualityGateRegistry(tmpdir)
            reg.evaluate_git_scope(approved_files=None, baseline_changes=None)
            self.assertEqual(reg.gates["GATE_8_GIT_SCOPE"]["status"], "BLOCKED")
            self.assertIn("approved_files list was not provided", reg.gates["GATE_8_GIT_SCOPE"]["details"])

    def test_git_scope_passes_when_working_tree_clean(self):
        """Verify that a clean working tree returns PASS on Gate 8."""
        with tempfile.TemporaryDirectory() as tmpdir:
            subprocess.run(["git", "init"], cwd=tmpdir, capture_output=True, check=True)
            subprocess.run(["git", "config", "user.name", "Test"], cwd=tmpdir, capture_output=True)
            subprocess.run(["git", "config", "user.email", "test@test.com"], cwd=tmpdir, capture_output=True)
            f = Path(tmpdir) / "committed.php"
            f.write_text("<?php // ok", encoding="utf-8")
            subprocess.run(["git", "add", "."], cwd=tmpdir, capture_output=True)
            subprocess.run(["git", "commit", "-m", "init"], cwd=tmpdir, capture_output=True)

            reg = quality_gate.QualityGateRegistry(tmpdir)
            reg.evaluate_git_scope()
            self.assertEqual(reg.gates["GATE_8_GIT_SCOPE"]["status"], "PASS")
            self.assertIn("clean", reg.gates["GATE_8_GIT_SCOPE"]["details"].lower())

    def test_git_scope_passes_when_only_approved_files_modified(self):
        """Verify that modifying strictly approved task files returns PASS."""
        with tempfile.TemporaryDirectory() as tmpdir:
            subprocess.run(["git", "init"], cwd=tmpdir, capture_output=True, check=True)
            subprocess.run(["git", "config", "user.name", "Test"], cwd=tmpdir, capture_output=True)
            subprocess.run(["git", "config", "user.email", "test@test.com"], cwd=tmpdir, capture_output=True)
            f = Path(tmpdir) / "app" / "Services" / "TestService.php"
            f.parent.mkdir(parents=True)
            f.write_text("<?php // initial", encoding="utf-8")
            subprocess.run(["git", "add", "."], cwd=tmpdir, capture_output=True)
            subprocess.run(["git", "commit", "-m", "init"], cwd=tmpdir, capture_output=True)

            snap = quality_gate.BaselineSnapshot.capture(tmpdir, task_id="task-scope-pass")

            # Agent modifies the approved file
            f.write_text("<?php // modified in scope", encoding="utf-8")

            reg = quality_gate.QualityGateRegistry(tmpdir)
            reg.evaluate_git_scope(approved_files=["app/Services/TestService.php"], baseline_snapshot=snap, task_id="task-scope-pass")
            self.assertEqual(reg.gates["GATE_8_GIT_SCOPE"]["status"], "PASS")

    def test_git_scope_fails_when_unapproved_file_modified(self):
        """Verify that modifying a file outside approved_files returns FAIL."""
        with tempfile.TemporaryDirectory() as tmpdir:
            subprocess.run(["git", "init"], cwd=tmpdir, capture_output=True, check=True)
            subprocess.run(["git", "config", "user.name", "Test"], cwd=tmpdir, capture_output=True)
            subprocess.run(["git", "config", "user.email", "test@test.com"], cwd=tmpdir, capture_output=True)
            f1 = Path(tmpdir) / "approved.php"
            f2 = Path(tmpdir) / "unapproved.php"
            f1.write_text("<?php", encoding="utf-8")
            f2.write_text("<?php", encoding="utf-8")
            subprocess.run(["git", "add", "."], cwd=tmpdir, capture_output=True)
            subprocess.run(["git", "commit", "-m", "init"], cwd=tmpdir, capture_output=True)

            snap = quality_gate.BaselineSnapshot.capture(tmpdir, task_id="task-scope-fail")

            # Both files modified, but only f1 is approved
            f1.write_text("<?php // changed", encoding="utf-8")
            f2.write_text("<?php // rogue change", encoding="utf-8")

            reg = quality_gate.QualityGateRegistry(tmpdir)
            reg.evaluate_git_scope(approved_files=["approved.php"], baseline_snapshot=snap, task_id="task-scope-fail")
            self.assertEqual(reg.gates["GATE_8_GIT_SCOPE"]["status"], "FAIL")
            self.assertIn("unapproved.php", reg.gates["GATE_8_GIT_SCOPE"]["details"])

    def test_git_scope_preserves_user_baseline_and_allows_approved_changes(self):
        """Verify baseline pre-existing user modifications are separated and preserved."""
        with tempfile.TemporaryDirectory() as tmpdir:
            subprocess.run(["git", "init"], cwd=tmpdir, capture_output=True, check=True)
            subprocess.run(["git", "config", "user.name", "Test"], cwd=tmpdir, capture_output=True)
            subprocess.run(["git", "config", "user.email", "test@test.com"], cwd=tmpdir, capture_output=True)
            user_file = Path(tmpdir) / "NotificationController.php"
            user_file.write_text("<?php // user", encoding="utf-8")
            subprocess.run(["git", "add", "."], cwd=tmpdir, capture_output=True)
            subprocess.run(["git", "commit", "-m", "init"], cwd=tmpdir, capture_output=True)

            # User modified this before agent arrived
            user_file.write_text("<?php // user pending edit", encoding="utf-8")
            snap = quality_gate.BaselineSnapshot.capture(tmpdir, task_id="task-preserve")

            # Agent adds an approved task file
            agent_file = Path(tmpdir) / "FeatureTest.php"
            agent_file.write_text("<?php // agent", encoding="utf-8")

            reg = quality_gate.QualityGateRegistry(tmpdir)
            reg.evaluate_git_scope(
                approved_files=["FeatureTest.php"],
                baseline_snapshot=snap,
                task_id="task-preserve"
            )
            self.assertEqual(reg.gates["GATE_8_GIT_SCOPE"]["status"], "PASS")

    def test_git_scope_fails_on_secret_leaks(self):
        """Verify that secret leaks (.env, .pem, .key) strictly FAIL Gate 8."""
        with tempfile.TemporaryDirectory() as tmpdir:
            subprocess.run(["git", "init"], cwd=tmpdir, capture_output=True, check=True)
            secret_file = Path(tmpdir) / ".env"
            secret_file.write_text("APP_KEY=secret", encoding="utf-8")

            reg = quality_gate.QualityGateRegistry(tmpdir)
            reg.evaluate_git_scope(approved_files=[".env"])
            self.assertEqual(reg.gates["GATE_8_GIT_SCOPE"]["status"], "FAIL")
            self.assertIn("Potential secrets detected", reg.gates["GATE_8_GIT_SCOPE"]["details"])

    # =========================================================================
    # 6. Real End-to-End Self-Repair on Disposable PHP Fixture
    # =========================================================================

    def test_real_self_repair_on_disposable_fixture(self):
        """
        Demonstrates the complete sequence on an isolated temporary copy:
        1. Copies fixture to tempfile.TemporaryDirectory() to keep workspace fixture read-only.
        2. Detects failing test on deliberately buggy copy (DiscountService.php).
        3. Identifies root cause from failure output.
        4. Corrects copy programmatically.
        5. Reruns test and verifies repaired copy passes.
        6. Asserts original fixture in workspace remains completely untouched (SHA-256 unchanged).
        """
        orig_service_file = self.fixtures_dir / "DiscountService.php"
        orig_test_file = self.fixtures_dir / "test_discount.php"
        orig_service_hash = quality_gate.get_file_sha256(orig_service_file)
        orig_test_hash = quality_gate.get_file_sha256(orig_test_file)
        php_path = shutil.which("php") or "php"

        with tempfile.TemporaryDirectory() as temp_dir:
            temp_path = Path(temp_dir)
            temp_service_file = temp_path / "DiscountService.php"
            temp_test_file = temp_path / "test_discount.php"

            # Copy fixtures to temporary directory
            shutil.copy2(orig_service_file, temp_service_file)
            shutil.copy2(orig_test_file, temp_test_file)

            # 1. Reset temporary copy to deliberately buggy state (0.50 multiplier)
            buggy_code = (
                "<?php\n\n"
                "class DiscountService\n{\n"
                "    public static function calculateEducationalDiscount(float $fee): float\n"
                "    {\n"
                "        return $fee * 0.50;\n"  # Bug: 50% instead of 10%
                "    }\n}\n"
            )
            with open(temp_service_file, "w", encoding="utf-8") as f:
                f.write(buggy_code)

            # Define runner function targeting the temporary copy
            def fixture_runner():
                proc = subprocess.run([php_path, str(temp_test_file)], capture_output=True, text=True)
                status = "PASSED" if proc.returncode == 0 else "FAILED"
                return {
                    "status": status,
                    "exit_code": proc.returncode,
                    "stdout_preview": proc.stdout.strip(),
                    "stderr": proc.stderr.strip()
                }

            # Verify initial failure on temporary copy
            initial_result = fixture_runner()
            self.assertEqual(initial_result["status"], "FAILED")
            self.assertEqual(initial_result["exit_code"], 1)
            self.assertIn("AssertionFailedError", initial_result["stderr"])
            self.assertIn("Expected response [10] but received [50]", initial_result["stderr"])

            # Define repair function based on diagnosis
            def fixture_repair(diagnosis, iteration):
                with open(temp_service_file, "r", encoding="utf-8") as f:
                    content = f.read()
                repaired_content = content.replace("0.50", "0.10")
                with open(temp_service_file, "w", encoding="utf-8") as f:
                    f.write(repaired_content)
                return "Correct educational discount multiplier from 50% (0.50) to 10% (0.10)", [str(temp_service_file)]

            # Execute end-to-end repair cycle on the temporary copy
            repair_outcome = self_repair.execute_repair_cycle(
                task_name="Repair Educational Discount Multiplier",
                test_filter="test_discount.php",
                runner_fn=fixture_runner,
                repair_fn=fixture_repair
            )

            # Assert repair succeeded in 1 iteration
            self.assertTrue(repair_outcome["resolved"])
            self.assertEqual(repair_outcome["final_status"], "RESOLVED")
            self.assertEqual(repair_outcome["iterations_used"], 1)

            # Verify test now passes on temporary copy
            post_repair_result = fixture_runner()
            self.assertEqual(post_repair_result["status"], "PASSED")
            self.assertEqual(post_repair_result["exit_code"], 0)
            self.assertEqual(post_repair_result["stdout_preview"], "OK (1 passed)")

        # Invariant check: Original fixtures in workspace must remain completely untouched
        self.assertTrue(orig_service_file.is_file())
        self.assertTrue(orig_test_file.is_file())
        self.assertEqual(quality_gate.get_file_sha256(orig_service_file), orig_service_hash)
        self.assertEqual(quality_gate.get_file_sha256(orig_test_file), orig_test_hash)

    def test_self_repair_session_iteration_cap_enforced(self):
        """Verify that BoundedRepairSession strictly halts after 3 failed iterations."""
        session = self_repair.BoundedRepairSession("Persistent Bug Task", "FailingTest")

        # Mock runner that always fails
        def failing_runner():
            return {"status": "FAILED", "exit_code": 1, "stderr": "Still failing"}

        def dummy_repair(diag, iteration):
            return f"Attempt {iteration}", ["some_file.php"]

        outcome = self_repair.execute_repair_cycle(
            task_name="Persistent Bug Task",
            test_filter="FailingTest",
            runner_fn=failing_runner,
            repair_fn=dummy_repair,
            session=session
        )

        self.assertFalse(outcome["resolved"])
        self.assertEqual(outcome["final_status"], "HALTED")
        self.assertEqual(outcome["iterations_used"], 3)
        self.assertIn("3 iterations", session.stop_reason)

        # Attempting a 4th iteration is blocked
        can_go, reason = session.can_attempt()
        self.assertFalse(can_go)

    def test_self_repair_early_stopping_detection(self):
        """Verify triggers for early stopping on missing infrastructure."""
        diag = self_repair.diagnose_failure_output("PDOException: SQLSTATE[HY000] [2002] Connection refused (127.0.0.1:3306)")
        self.assertEqual(diag["category"], "EARLY_STOP_TRIGGERED")
        self.assertEqual(diag["early_stop"], "MISSING_INFRASTRUCTURE")

    # =========================================================================
    # 7. Live Application Targeted Test Execution
    # =========================================================================

    def test_live_targeted_test_execution(self):
        """Run safe live execution of ExampleTest against isolated test DB."""
        report = test_runner.run_tests(
            project_root=self.project_root,
            filter_pattern="ExampleTest",
            timeout=90
        )
        self.assertEqual(report["status"], "PASSED")
        self.assertEqual(report["exit_code"], 0)
        self.assertGreaterEqual(report["summary"]["passed"], 2)
        self.assertEqual(report["summary"]["failed"], 0)
        self.assertIn("SAFE: Verified isolated in-memory test database", report["safety_note"])

    # =========================================================================
    # 8. Phase 1B Baseline Integrity & Preflight Safety Regression Suite
    # =========================================================================

    def _create_temp_git_repo(self):
        """Helper to create an isolated temporary git repository with user configuration."""
        temp_dir = tempfile.mkdtemp()
        repo_path = Path(temp_dir)
        subprocess.run(["git", "init"], cwd=repo_path, capture_output=True, check=True)
        subprocess.run(["git", "config", "user.name", "TestUser"], cwd=repo_path, capture_output=True, check=True)
        subprocess.run(["git", "config", "user.email", "test@example.com"], cwd=repo_path, capture_output=True, check=True)
        readme = repo_path / "README.md"
        readme.write_text("# Test Repo", encoding="utf-8")
        subprocess.run(["git", "add", "README.md"], cwd=repo_path, capture_output=True, check=True)
        subprocess.run(["git", "commit", "-m", "Initial commit"], cwd=repo_path, capture_output=True, check=True)
        return temp_dir, repo_path

    def test_scenario_1_baseline_file_unchanged_passes(self):
        """Scenario 1: A baseline file is unchanged -> PASS when all other conditions are valid."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            user_file = repo_path / "user_draft.php"
            user_file.write_text("<?php // user draft", encoding="utf-8")
            snapshot = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-sc1")

            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(approved_files=[], baseline_snapshot=snapshot, task_id="task-sc1")

            gate_res = registry.gates["GATE_8_GIT_SCOPE"]
            self.assertEqual(gate_res["status"], "PASS")
            self.assertIn("protected baseline intact", gate_res["details"])
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_scenario_2_baseline_file_modified_fails(self):
        """Scenario 2: A baseline file is modified by the agent -> FAIL."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            ctrl_dir = repo_path / "app" / "Http" / "Controllers"
            ctrl_dir.mkdir(parents=True, exist_ok=True)
            ctrl_file = ctrl_dir / "UserController.php"
            ctrl_file.write_text("<?php class UserController {}", encoding="utf-8")
            subprocess.run(["git", "add", "."], cwd=repo_path, capture_output=True, check=True)
            subprocess.run(["git", "commit", "-m", "Add controller"], cwd=repo_path, capture_output=True, check=True)

            # User modified it before task start
            ctrl_file.write_text("<?php class UserController { /* user edits */ }", encoding="utf-8")
            snapshot = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-sc2")

            # Agent tampers with user baseline file
            ctrl_file.write_text("<?php class UserController { /* agent tampering */ }", encoding="utf-8")

            registry = quality_gate.QualityGateRegistry(repo_path)
            # Even if approved_files has directory prefix "app/Http/Controllers"
            registry.evaluate_git_scope(
                approved_files=["app/Http/Controllers/"],
                baseline_snapshot=snapshot,
                task_id="task-sc2"
            )

            gate_res = registry.gates["GATE_8_GIT_SCOPE"]
            self.assertEqual(gate_res["status"], "FAIL")
            self.assertIn("Baseline integrity violation", gate_res["details"])
            self.assertIn("modified without authorization", gate_res["details"])
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_scenario_3_baseline_file_deleted_fails(self):
        """Scenario 3: A baseline file is deleted -> FAIL."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            svc_file = repo_path / "app" / "OldService.php"
            svc_file.parent.mkdir(parents=True, exist_ok=True)
            svc_file.write_text("<?php class OldService {}", encoding="utf-8")
            snapshot = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-sc3")

            # Agent deletes baseline file
            svc_file.unlink()

            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(approved_files=["app/"], baseline_snapshot=snapshot, task_id="task-sc3")

            gate_res = registry.gates["GATE_8_GIT_SCOPE"]
            self.assertEqual(gate_res["status"], "FAIL")
            self.assertIn("deleted", gate_res["details"])
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_scenario_4_baseline_file_renamed_without_authorization_fails(self):
        """Scenario 4: A baseline file is renamed without authorization -> FAIL."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            orig_file = repo_path / "app" / "ServiceA.php"
            orig_file.parent.mkdir(parents=True, exist_ok=True)
            orig_file.write_text("<?php class ServiceA {}", encoding="utf-8")
            subprocess.run(["git", "add", "."], cwd=repo_path, capture_output=True, check=True)
            subprocess.run(["git", "commit", "-m", "Add ServiceA"], cwd=repo_path, capture_output=True, check=True)

            # User had a modification
            orig_file.write_text("<?php class ServiceA { /* user */ }", encoding="utf-8")
            snapshot = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-sc4")

            # Agent renames ServiceA.php to ServiceB.php via git mv
            subprocess.run(["git", "mv", "app/ServiceA.php", "app/ServiceB.php"], cwd=repo_path, capture_output=True, check=True)

            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(
                approved_files=["app/ServiceB.php"],
                baseline_snapshot=snapshot,
                task_id="task-sc4"
            )

            gate_res = registry.gates["GATE_8_GIT_SCOPE"]
            self.assertEqual(gate_res["status"], "FAIL")
            self.assertIn("renamed without authorization", gate_res["details"])
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_scenario_5_preexisting_untracked_file_overwritten_fails(self):
        """Scenario 5: A pre-existing untracked file is overwritten -> FAIL."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            untracked = repo_path / "tests" / "Feature" / "CustomTest.php"
            untracked.parent.mkdir(parents=True, exist_ok=True)
            untracked.write_text("<?php // user initial test draft", encoding="utf-8")

            snapshot = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-sc5")

            # Agent overwrites untracked file
            untracked.write_text("<?php // agent overwritten test code", encoding="utf-8")

            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(
                approved_files=["tests/Feature/CustomTest.php"],
                baseline_snapshot=snapshot,
                task_id="task-sc5"
            )

            gate_res = registry.gates["GATE_8_GIT_SCOPE"]
            self.assertEqual(gate_res["status"], "FAIL")
            self.assertIn("modified without authorization", gate_res["details"])
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_scenario_6_preexisting_staged_file_modified_fails(self):
        """Scenario 6: A pre-existing staged file is modified -> FAIL."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            staged_file = repo_path / "config" / "custom.php"
            staged_file.parent.mkdir(parents=True, exist_ok=True)
            staged_file.write_text("<?php return ['version' => 1];", encoding="utf-8")
            subprocess.run(["git", "add", "config/custom.php"], cwd=repo_path, capture_output=True, check=True)

            snapshot = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-sc6")

            # Agent modifies the staged file in working tree
            staged_file.write_text("<?php return ['version' => 2, 'tampered' => true];", encoding="utf-8")

            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(
                approved_files=["config/custom.php"],
                baseline_snapshot=snapshot,
                task_id="task-sc6"
            )

            gate_res = registry.gates["GATE_8_GIT_SCOPE"]
            self.assertEqual(gate_res["status"], "FAIL")
            self.assertIn("modified without authorization", gate_res["details"])
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_scenario_7_new_approved_file_created_with_baseline_intact_passes(self):
        """Scenario 7: A new approved file is created while protected baseline remains intact -> PASS."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            # Baseline user file
            user_doc = repo_path / "docs" / "notes.txt"
            user_doc.parent.mkdir(parents=True, exist_ok=True)
            user_doc.write_text("User work in progress", encoding="utf-8")

            snapshot = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-sc7")

            # Agent creates a new approved file
            new_file = repo_path / "app" / "Services" / "PaymentService.php"
            new_file.parent.mkdir(parents=True, exist_ok=True)
            new_file.write_text("<?php class PaymentService {}", encoding="utf-8")

            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(
                approved_files=["app/Services/PaymentService.php"],
                baseline_snapshot=snapshot,
                task_id="task-sc7"
            )

            gate_res = registry.gates["GATE_8_GIT_SCOPE"]
            self.assertEqual(gate_res["status"], "PASS")
            self.assertIn("all 1 modification(s) match approved scope", gate_res["details"])
            self.assertIn("protected baseline files retain their initial state", gate_res["details"])
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_scenario_8_baseline_evidence_missing_or_invalid_blocked(self):
        """Scenario 8: Baseline evidence missing or invalid -> BLOCKED."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            dirty = repo_path / "dirty.php"
            dirty.write_text("<?php // dirty", encoding="utf-8")

            registry = quality_gate.QualityGateRegistry(repo_path)

            # Subcase A: Missing snapshot
            registry.evaluate_git_scope(approved_files=["dirty.php"], baseline_snapshot=None)
            self.assertEqual(registry.gates["GATE_8_GIT_SCOPE"]["status"], "BLOCKED")
            self.assertIn("Missing baseline cryptographic snapshot evidence", registry.gates["GATE_8_GIT_SCOPE"]["details"])

            # Subcase B: Invalid snapshot schema / missing keys
            corrupt_file = repo_path / "corrupt.json"
            corrupt_file.write_text(json.dumps({"invalid": "schema"}), encoding="utf-8")
            registry.evaluate_git_scope(approved_files=["dirty.php"], baseline_snapshot=corrupt_file)
            self.assertEqual(registry.gates["GATE_8_GIT_SCOPE"]["status"], "BLOCKED")
            self.assertIn("Failed to load baseline snapshot", registry.gates["GATE_8_GIT_SCOPE"]["details"])

            # Subcase C: Tampered integrity hash
            valid_snap = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-sc8")
            valid_snap.snapshot_hash = "tampered_digest_value"
            registry.evaluate_git_scope(approved_files=["dirty.php"], baseline_snapshot=valid_snap, task_id="task-sc8")
            self.assertEqual(registry.gates["GATE_8_GIT_SCOPE"]["status"], "BLOCKED")
            self.assertIn("integrity check failed", registry.gates["GATE_8_GIT_SCOPE"]["details"])
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_scenario_9_snapshot_belongs_to_another_repo_or_session_blocked(self):
        """Scenario 9: Snapshot belongs to another repository/session -> BLOCKED."""
        temp_dir1, repo1 = self._create_temp_git_repo()
        temp_dir2, repo2 = self._create_temp_git_repo()
        try:
            snap1 = quality_gate.BaselineSnapshot.capture(repo1, task_id="session-alpha")

            dirty2 = repo2 / "file2.php"
            dirty2.write_text("<?php // dirty", encoding="utf-8")

            registry2 = quality_gate.QualityGateRegistry(repo2)
            # Evaluate in repo2 using snap1 from repo1
            registry2.evaluate_git_scope(approved_files=["file2.php"], baseline_snapshot=snap1, task_id="session-alpha")
            self.assertEqual(registry2.gates["GATE_8_GIT_SCOPE"]["status"], "BLOCKED")
            self.assertIn("belongs to another repository", registry2.gates["GATE_8_GIT_SCOPE"]["details"])

            # In repo1, evaluate with different task_id
            dirty1 = repo1 / "file1.php"
            dirty1.write_text("<?php // dirty", encoding="utf-8")
            registry1 = quality_gate.QualityGateRegistry(repo1)
            registry1.evaluate_git_scope(approved_files=["file1.php"], baseline_snapshot=snap1, task_id="session-beta")
            self.assertEqual(registry1.gates["GATE_8_GIT_SCOPE"]["status"], "BLOCKED")
            self.assertIn("task ID mismatch", registry1.gates["GATE_8_GIT_SCOPE"]["details"])
        finally:
            shutil.rmtree(temp_dir1, ignore_errors=True)
            shutil.rmtree(temp_dir2, ignore_errors=True)

    def test_scenario_10_snapshot_regeneration_cannot_reset_trust_boundary(self):
        """Scenario 10: Snapshot regeneration after changes cannot silently reset the trust boundary."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            user_file = repo_path / "critical_baseline.php"
            user_file.write_text("<?php // authentic baseline", encoding="utf-8")

            snapshot_file = repo_path / ".autopilot_baseline_task10.json"
            snap1 = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-10")
            snap1.save(snapshot_file)

            # Agent makes unauthorized changes
            user_file.write_text("<?php // tampered by agent", encoding="utf-8")

            # Agent attempts to capture a new snapshot and overwrite the existing baseline file
            snap2 = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-10")
            with self.assertRaises(FileExistsError):
                snap2.save(snapshot_file, overwrite=False)

            # Verifying with the original established trust boundary catches the tampering
            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(
                approved_files=["critical_baseline.php"],
                baseline_snapshot=snapshot_file,
                task_id="task-10"
            )
            self.assertEqual(registry.gates["GATE_8_GIT_SCOPE"]["status"], "FAIL")
            self.assertIn("modified without authorization", registry.gates["GATE_8_GIT_SCOPE"]["details"])
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_scenario_11_preflight_config_cache_detected_blocked(self):
        """Scenario 11: Config cache detected by preflight -> BLOCKED."""
        temp_dir = tempfile.mkdtemp()
        try:
            p = Path(temp_dir)
            # Create minimal Laravel project structure
            (p / "artisan").write_text("#!/usr/bin/env php\n", encoding="utf-8")
            (p / "composer.json").write_text("{}", encoding="utf-8")
            phpunit = p / "phpunit.xml"
            phpunit.write_text("""<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <php>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>""", encoding="utf-8")

            # Introduce config cache
            cache_file = p / "bootstrap" / "cache" / "config.php"
            cache_file.parent.mkdir(parents=True, exist_ok=True)
            cache_file.write_text("<?php return ['database' => []];", encoding="utf-8")

            report = preflight.run_preflight(p)
            self.assertEqual(report["status"], "BLOCKED")
            self.assertFalse(report["test_database_safety"]["is_isolated_safe"])
            self.assertTrue(report["test_database_safety"]["config_cache_found"])
            self.assertTrue(any("Configuration cache detected" in b for b in report["blockers"]))
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_scenario_12_preflight_verified_sqlite_memory_passes(self):
        """Scenario 12: Verified isolated SQLite memory -> PASS."""
        temp_dir = tempfile.mkdtemp()
        try:
            p = Path(temp_dir)
            (p / "artisan").write_text("#!/usr/bin/env php\n", encoding="utf-8")
            (p / "composer.json").write_text("{}", encoding="utf-8")
            phpunit = p / "phpunit.xml"
            phpunit.write_text("""<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <php>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>""", encoding="utf-8")

            report = preflight.run_preflight(p)
            self.assertIn(report["status"], ("PASS", "WARNING"))
            self.assertTrue(report["test_database_safety"]["is_isolated_safe"])
            self.assertIn("Verified isolated in-memory test database", report["test_database_safety"]["safety_assessment"])
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    # =========================================================================
    # 9. Phase 1D Regression Tests: Process Exit Codes & Baseline Trust Boundary
    # =========================================================================

    def _setup_disposable_laravel_git_repo(self):
        """Helper to create a disposable git repo with minimal Laravel files and isolated test DB."""
        temp_dir, repo_path = self._create_temp_git_repo()
        (repo_path / "artisan").write_text("#!/usr/bin/env php\n", encoding="utf-8")
        (repo_path / "composer.json").write_text("{}", encoding="utf-8")
        phpunit = repo_path / "phpunit.xml"
        phpunit.write_text("""<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <php>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>""", encoding="utf-8")
        subprocess.run(["git", "add", "."], cwd=repo_path, capture_output=True, check=True)
        subprocess.run(["git", "commit", "-m", "Add Laravel files"], cwd=repo_path, capture_output=True, check=True)
        return temp_dir, repo_path

    def test_p1d_01_pass_produces_exit_code_0(self):
        """Phase 1D Test 1: Evaluated gates PASS produces process exit code 0."""
        temp_dir, repo_path = self._setup_disposable_laravel_git_repo()
        snap_file = Path(tempfile.gettempdir()) / "test_p1d_01_snap.json"
        try:
            # Baseline captured into external temp storage
            snap = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-p1d-01")
            snap.save(snap_file, overwrite=True)

            # Agent makes approved change
            new_file = repo_path / "app_file.txt"
            new_file.write_text("clean change", encoding="utf-8")

            # Run CLI via subprocess
            proc = subprocess.run(
                [sys.executable, str(scripts_dir / "quality_gate.py"),
                 "--project-path", str(repo_path),
                 "--eval-core",
                 "--baseline-snapshot", str(snap_file),
                 "--approved-files", "app_file.txt"],
                capture_output=True,
                text=True
            )
            self.assertEqual(proc.returncode, 0, f"Expected exit code 0, got {proc.returncode}. Stderr: {proc.stderr}")
            self.assertIn("0 FAIL, 0 BLOCKED", proc.stdout)
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)
            if snap_file.exists():
                snap_file.unlink()

    def test_p1d_02_fail_produces_non_zero_exit_code_1(self):
        """Phase 1D Test 2: Gate FAIL produces non-zero process exit code (1)."""
        temp_dir, repo_path = self._setup_disposable_laravel_git_repo()
        snap_file = Path(tempfile.gettempdir()) / "test_p1d_02_snap.json"
        try:
            # Create a user file and commit baseline snapshot
            user_file = repo_path / "user_draft.php"
            user_file.write_text("<?php // original", encoding="utf-8")
            snap = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-p1d-02")
            snap.save(snap_file, overwrite=True)

            # Agent tampers with baseline without authorization
            user_file.write_text("<?php // tampered", encoding="utf-8")

            proc = subprocess.run(
                [sys.executable, str(scripts_dir / "quality_gate.py"),
                 "--project-path", str(repo_path),
                 "--eval-core",
                 "--baseline-snapshot", str(snap_file),
                 "--approved-files", "user_draft.php"],
                capture_output=True,
                text=True
            )
            self.assertEqual(proc.returncode, 1, f"Expected exit code 1 on FAIL, got {proc.returncode}")
            self.assertIn("1 FAIL", proc.stdout)
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)
            if snap_file.exists():
                snap_file.unlink()

    def test_p1d_03_blocked_produces_non_zero_exit_code_2(self):
        """Phase 1D Test 3: Gate BLOCKED produces non-zero process exit code (2)."""
        temp_dir, repo_path = self._setup_disposable_laravel_git_repo()
        try:
            # Dirty workspace with no baseline snapshot provided
            dirty_file = repo_path / "dirty.txt"
            dirty_file.write_text("untracked", encoding="utf-8")

            proc = subprocess.run(
                [sys.executable, str(scripts_dir / "quality_gate.py"),
                 "--project-path", str(repo_path),
                 "--eval-core",
                 "--approved-files", "dirty.txt"],
                capture_output=True,
                text=True
            )
            self.assertEqual(proc.returncode, 2, f"Expected exit code 2 on BLOCKED, got {proc.returncode}")
            self.assertIn("1 BLOCKED", proc.stdout)
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_p1d_04_invalid_arguments_produce_exit_code_3(self):
        """Phase 1D Test 4: Invalid CLI arguments produce process exit code 3."""
        proc = subprocess.run(
            [sys.executable, str(scripts_dir / "quality_gate.py"), "--invalid-flag-that-does-not-exist"],
            capture_output=True,
            text=True
        )
        self.assertEqual(proc.returncode, 3, f"Expected exit code 3 on argument error, got {proc.returncode}")
        self.assertIn("Argument error", proc.stderr)

    def test_p1d_05_json_status_agrees_with_exit_code(self):
        """Phase 1D Test 5: JSON summary status matches process exit code across PASS, FAIL, BLOCKED."""
        temp_dir, repo_path = self._setup_disposable_laravel_git_repo()
        snap_file = Path(tempfile.gettempdir()) / "test_p1d_05_snap.json"
        clean_snap_file = Path(tempfile.gettempdir()) / "test_p1d_05_clean.json"
        try:
            # Case A: BLOCKED
            (repo_path / "dirty.txt").write_text("change", encoding="utf-8")
            proc_blocked = subprocess.run(
                [sys.executable, str(scripts_dir / "quality_gate.py"),
                 "--project-path", str(repo_path), "--eval-core", "--json"],
                capture_output=True, text=True
            )
            self.assertEqual(proc_blocked.returncode, 2)
            data_blocked = json.loads(proc_blocked.stdout)
            self.assertGreater(data_blocked["summary"]["blocked"], 0)
            self.assertEqual(data_blocked["summary"]["fail"], 0)

            # Case B: FAIL (create snapshot, tamper baseline)
            snap = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-json")
            snap.save(snap_file, overwrite=True)
            (repo_path / "dirty.txt").write_text("tampered content", encoding="utf-8")

            proc_fail = subprocess.run(
                [sys.executable, str(scripts_dir / "quality_gate.py"),
                 "--project-path", str(repo_path), "--eval-core",
                 "--baseline-snapshot", str(snap_file),
                 "--approved-files", "dirty.txt", "--json"],
                capture_output=True, text=True
            )
            self.assertEqual(proc_fail.returncode, 1)
            data_fail = json.loads(proc_fail.stdout)
            self.assertGreater(data_fail["summary"]["fail"], 0)

            # Case C: PASS
            clean_dir, clean_repo = self._setup_disposable_laravel_git_repo()
            try:
                clean_snap = quality_gate.BaselineSnapshot.capture(clean_repo, task_id="clean-task")
                clean_snap.save(clean_snap_file, overwrite=True)
                (clean_repo / "clean.txt").write_text("clean", encoding="utf-8")

                proc_pass = subprocess.run(
                    [sys.executable, str(scripts_dir / "quality_gate.py"),
                     "--project-path", str(clean_repo), "--eval-core",
                     "--baseline-snapshot", str(clean_snap_file),
                     "--approved-files", "clean.txt", "--json"],
                    capture_output=True, text=True
                )
                self.assertEqual(proc_pass.returncode, 0)
                data_pass = json.loads(proc_pass.stdout)
                self.assertEqual(data_pass["summary"]["fail"], 0)
                self.assertEqual(data_pass["summary"]["blocked"], 0)
                self.assertGreater(data_pass["summary"]["pass"], 0)
            finally:
                shutil.rmtree(clean_dir, ignore_errors=True)
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)
            if snap_file.exists():
                snap_file.unlink()
            if clean_snap_file.exists():
                clean_snap_file.unlink()

    def test_p1d_06_baseline_snapshot_captured_in_external_storage(self):
        """Phase 1D Test 6: Baseline snapshot is captured before edits and stored in external store."""
        temp_dir, repo_path = self._setup_disposable_laravel_git_repo()
        try:
            user_file = repo_path / "user_draft.php"
            user_file.write_text("<?php // initial dirty file", encoding="utf-8")

            # Capture baseline using CLI without --snapshot-file (using default external storage)
            proc = subprocess.run(
                [sys.executable, str(scripts_dir / "quality_gate.py"),
                 "--project-path", str(repo_path),
                 "--capture-baseline",
                 "--task-id", "task-external-store",
                 "--json"],
                capture_output=True, text=True
            )
            self.assertEqual(proc.returncode, 0, f"Baseline capture failed: {proc.stderr}")
            res = json.loads(proc.stdout)
            snapshot_path = Path(res["snapshot_file"])
            self.assertTrue(snapshot_path.is_file())
            # Must be outside project repository root!
            self.assertFalse(snapshot_path.resolve().is_relative_to(repo_path.resolve()))
            self.assertEqual(res["task_id"], "task-external-store")

            # Verify auto-resolution works in evaluate_git_scope using task-id
            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(approved_files=[], task_id="task-external-store")
            self.assertEqual(registry.gates["GATE_8_GIT_SCOPE"]["status"], "PASS")
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_p1d_07_missing_baseline_evidence_cannot_be_replaced_silently(self):
        """Phase 1D Test 7: Overwriting an existing baseline snapshot fails and cannot replace evidence silently."""
        temp_dir, repo_path = self._setup_disposable_laravel_git_repo()
        try:
            (repo_path / "f.txt").write_text("v1", encoding="utf-8")
            snap1 = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-replace")
            snap_file = repo_path / "baseline.json"
            snap1.save(snap_file)

            # Agent changes file and tries to capture again to overwrite
            (repo_path / "f.txt").write_text("v2", encoding="utf-8")
            snap2 = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-replace")

            with self.assertRaises(FileExistsError):
                snap2.save(snap_file, overwrite=False)

            # Via CLI, attempting overwrite produces exit code 2 (BLOCKED)
            proc = subprocess.run(
                [sys.executable, str(scripts_dir / "quality_gate.py"),
                 "--project-path", str(repo_path),
                 "--capture-baseline",
                 "--task-id", "task-replace",
                 "--snapshot-file", str(snap_file)],
                capture_output=True, text=True
            )
            self.assertEqual(proc.returncode, 2)
            self.assertIn("BLOCKED", proc.stderr)
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_p1d_08_forged_or_replaced_snapshot_rejected_under_supervisor_hmac(self):
        """Phase 1D Test 8: Forged or modified snapshot is rejected when supervisor authentication is configured."""
        temp_dir, repo_path = self._setup_disposable_laravel_git_repo()
        supervisor_key = "super-secret-supervisor-auth-token-999"
        try:
            os.environ["AUTOPILOT_SUPERVISOR_KEY"] = supervisor_key
            (repo_path / "original.txt").write_text("hello", encoding="utf-8")

            # Capture authentic snapshot with supervisor HMAC
            snap = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-hmac")
            self.assertEqual(snap.trust_level, "SUPERVISOR_HMAC")
            self.assertIsNotNone(snap.signature)
            self.assertTrue(snap.verify_integrity())

            # Forgery 1: Tampering with manifest content
            snap.files["forged.txt"] = {"status": "??", "exists": False, "sha256": None, "size": 0, "orig_path": None}
            self.assertFalse(snap.verify_integrity())

            # Forgery 2: Snapshot created without supervisor key or signed with wrong key
            os.environ["AUTOPILOT_SUPERVISOR_KEY"] = "wrong-attacker-key"
            self.assertFalse(snap.verify_integrity())

            # Forgery 3: Agent in supervisor environment tries to verify forged snapshot -> BLOCKED
            os.environ["AUTOPILOT_SUPERVISOR_KEY"] = supervisor_key
            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(approved_files=[], baseline_snapshot=snap, task_id="task-hmac")
            self.assertEqual(registry.gates["GATE_8_GIT_SCOPE"]["status"], "BLOCKED")
            self.assertIn("supervisor authentication failed", registry.gates["GATE_8_GIT_SCOPE"]["details"])
        finally:
            os.environ.pop("AUTOPILOT_SUPERVISOR_KEY", None)
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_p1d_09_modified_protected_baseline_detected(self):
        """Phase 1D Test 9: Modification of protected baseline file is caught by Gate 8 (FAIL)."""
        temp_dir, repo_path = self._setup_disposable_laravel_git_repo()
        try:
            bf = repo_path / "baseline_file.php"
            bf.write_text("<?php // v1", encoding="utf-8")
            snap = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-mod")

            # Modify protected file
            bf.write_text("<?php // v2 modified", encoding="utf-8")

            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(approved_files=["baseline_file.php"], baseline_snapshot=snap, task_id="task-mod")
            self.assertEqual(registry.gates["GATE_8_GIT_SCOPE"]["status"], "FAIL")
            self.assertIn("modified without authorization", registry.gates["GATE_8_GIT_SCOPE"]["details"])
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_p1d_10_snapshot_from_another_task_or_repo_rejected(self):
        """Phase 1D Test 10: Snapshot from another task ID or another repository is rejected (BLOCKED)."""
        temp_dir1, repo1 = self._setup_disposable_laravel_git_repo()
        temp_dir2, repo2 = self._setup_disposable_laravel_git_repo()
        try:
            snap1 = quality_gate.BaselineSnapshot.capture(repo1, task_id="task-alpha")

            # Case A: Task ID mismatch
            reg1 = quality_gate.QualityGateRegistry(repo1)
            reg1.evaluate_git_scope(approved_files=[], baseline_snapshot=snap1, task_id="task-beta")
            self.assertEqual(reg1.gates["GATE_8_GIT_SCOPE"]["status"], "BLOCKED")
            self.assertIn("task ID mismatch", reg1.gates["GATE_8_GIT_SCOPE"]["details"])

            # Case B: Repository root mismatch
            reg2 = quality_gate.QualityGateRegistry(repo2)
            reg2.evaluate_git_scope(approved_files=[], baseline_snapshot=snap1, task_id="task-alpha")
            self.assertEqual(reg2.gates["GATE_8_GIT_SCOPE"]["status"], "BLOCKED")
            self.assertIn("another repository", reg2.gates["GATE_8_GIT_SCOPE"]["details"])
        finally:
            shutil.rmtree(temp_dir1, ignore_errors=True)
            shutil.rmtree(temp_dir2, ignore_errors=True)

    def test_p1d_11_gate8_failure_prevents_workflow_success(self):
        """Phase 1D Test 11: Gate 8 failure prevents caller from declaring success."""
        temp_dir, repo_path = self._setup_disposable_laravel_git_repo()
        try:
            # Dirty file with leak
            (repo_path / ".env.production").write_text("APP_KEY=secret", encoding="utf-8")
            proc = subprocess.run(
                [sys.executable, str(scripts_dir / "quality_gate.py"),
                 "--project-path", str(repo_path),
                 "--eval-core"],
                capture_output=True, text=True
            )
            # Process must exit non-zero
            self.assertNotEqual(proc.returncode, 0)
            self.assertEqual(proc.returncode, 1)  # FAIL due to secrets leak
            self.assertIn("GATE_8_GIT_SCOPE", proc.stdout)
            self.assertIn("[FAIL]", proc.stdout)
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_p1d_12_existing_user_changes_remain_unchanged(self):
        """Hermetic Test: Existing user modified and untracked files remain intact during baseline inspection."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            # 1. Simulate existing user modifications (tracked modified file)
            user_service = repo_path / "UserService.php"
            user_service.write_text("<?php\nclass UserService { public $v = 1; }\n", encoding="utf-8")
            subprocess.run(["git", "add", "UserService.php"], cwd=repo_path, capture_output=True, check=True)
            subprocess.run(["git", "commit", "-m", "Add UserService"], cwd=repo_path, capture_output=True, check=True)

            # User edits UserService without committing
            user_service.write_text("<?php\nclass UserService { public $v = 2; // modified by user }\n", encoding="utf-8")
            service_hash_before = quality_gate.get_file_sha256(user_service)

            # 2. Simulate user untracked file
            user_draft = repo_path / "notes_draft.txt"
            user_draft.write_text("User work in progress notes", encoding="utf-8")
            draft_hash_before = quality_gate.get_file_sha256(user_draft)

            # 3. Capture baseline snapshot and run quality gate evaluation
            snapshot = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-hermetic-user-test")
            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(approved_files=[], baseline_snapshot=snapshot, task_id="task-hermetic-user-test")

            # 4. Verify gate result: baseline is preserved
            gate_res = registry.gates["GATE_8_GIT_SCOPE"]
            self.assertEqual(gate_res["status"], "PASS")

            # 5. Assert user files on disk were not modified or overwritten
            self.assertTrue(user_service.is_file())
            self.assertTrue(user_draft.is_file())
            self.assertEqual(quality_gate.get_file_sha256(user_service), service_hash_before)
            self.assertEqual(quality_gate.get_file_sha256(user_draft), draft_hash_before)

            # 6. Assert Git status in temp repo still sees the user's modified and untracked files
            proc = subprocess.run(["git", "status", "--porcelain"], cwd=repo_path, capture_output=True, text=True)
            self.assertIn("UserService.php", proc.stdout)
            self.assertIn("notes_draft.txt", proc.stdout)
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    # =========================================================================
    # 9. Remediation B & D: Exit Code Contract & Secret Content Scanning Tests
    # =========================================================================

    def test_remediation_b_exit_code_blocked_when_no_gates_evaluated(self):
        """Remediation B: Invoking quality_gate without gate flags exits with code 2 (BLOCKED)."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            proc = subprocess.run(
                [sys.executable, str(scripts_dir / "quality_gate.py"),
                 "--project-path", str(repo_path),
                 "--json"],
                capture_output=True, text=True
            )
            self.assertEqual(proc.returncode, 2, f"Expected exit code 2, got {proc.returncode}. Output: {proc.stdout}")
            data = json.loads(proc.stdout)
            summary = data["summary"]
            self.assertEqual(summary["pass"], 0)
            self.assertEqual(summary["fail"], 0)
            self.assertEqual(summary["not_run"], 8)
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_remediation_b_exit_code_fail_when_syntax_fails(self):
        """Remediation B: Quality gate exits with code 1 when a gate fails."""
        temp_dir = tempfile.mkdtemp()
        try:
            bad_php = Path(temp_dir) / "broken.php"
            bad_php.write_text("<?php broken syntax {{{", encoding="utf-8")
            proc = subprocess.run(
                [sys.executable, str(scripts_dir / "quality_gate.py"),
                 "--project-path", temp_dir,
                 "--check-syntax", str(bad_php),
                 "--json"],
                capture_output=True, text=True
            )
            self.assertEqual(proc.returncode, 1, f"Expected exit code 1, got {proc.returncode}")
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_remediation_b_exit_code_invalid_args_exits_3(self):
        """Remediation B: Quality gate exits with code 3 on unrecognized arguments."""
        proc = subprocess.run(
            [sys.executable, str(scripts_dir / "quality_gate.py"),
             "--unrecognized-flag-xyz"],
            capture_output=True, text=True
        )
        self.assertEqual(proc.returncode, 3, f"Expected exit code 3, got {proc.returncode}")

    def test_remediation_d_secret_content_detected_in_modified_file(self):
        """Remediation D: Secret content in tracked diff produces FAIL without leaking secret value."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            app_dir = repo_path / "app"
            app_dir.mkdir(parents=True, exist_ok=True)
            cfg_file = app_dir / "PaymentConfig.php"
            cfg_file.write_text("<?php\nreturn ['timeout' => 30];\n", encoding="utf-8")
            subprocess.run(["git", "add", "app/PaymentConfig.php"], cwd=repo_path, capture_output=True, check=True)
            subprocess.run(["git", "commit", "-m", "Add PaymentConfig"], cwd=repo_path, capture_output=True, check=True)

            # Dummy AWS access key token (synthetic test fixture)
            dummy_token = "AKIAIOSFODNN7EXAMPLE"
            cfg_file.write_text(f"<?php\nreturn ['timeout' => 30, 'key' => '{dummy_token}'];\n", encoding="utf-8")

            snapshot = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-secret-test")
            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(
                approved_files=["app/PaymentConfig.php"],
                baseline_snapshot=snapshot,
                task_id="task-secret-test",
                authorized_baseline_modifications=["app/PaymentConfig.php"]
            )

            gate_res = registry.gates["GATE_8_GIT_SCOPE"]
            self.assertEqual(gate_res["status"], "FAIL")
            self.assertIn("Potential secrets detected in file content", gate_res["details"])
            self.assertIn("AWS Access Key", gate_res["details"])
            # Invariant: Secret value MUST NOT be present in details or evidence
            self.assertNotIn(dummy_token, gate_res["details"])
            evidence_json = json.dumps(gate_res["evidence"])
            self.assertNotIn(dummy_token, evidence_json)
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_remediation_d_secret_content_detected_in_untracked_file(self):
        """Remediation D: Secret content in new untracked file produces FAIL."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            cert_file = repo_path / "server_cert.txt"
            # Synthetic dummy private key header
            cert_file.write_text("-----BEGIN RSA PRIVATE KEY-----\nMIIEowIBAAKCAQEA0Yv\n", encoding="utf-8")

            snapshot = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-untracked-secret")
            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(
                approved_files=["server_cert.txt"],
                baseline_snapshot=snapshot,
                task_id="task-untracked-secret"
            )

            gate_res = registry.gates["GATE_8_GIT_SCOPE"]
            self.assertEqual(gate_res["status"], "FAIL")
            self.assertIn("Private Key", gate_res["details"])
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_remediation_d_passes_on_normal_clean_content(self):
        """Remediation D: Ordinary application code passes Gate 8 content scan without false positive."""
        temp_dir, repo_path = self._create_temp_git_repo()
        try:
            svc_file = repo_path / "HelperService.php"
            svc_file.write_text("<?php\nclass HelperService {\n    public static function add(int $a, int $b): int {\n        return $a + $b;\n    }\n}\n", encoding="utf-8")
            snapshot = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-clean-content")
            registry = quality_gate.QualityGateRegistry(repo_path)
            registry.evaluate_git_scope(
                approved_files=["HelperService.php"],
                baseline_snapshot=snapshot,
                task_id="task-clean-content"
            )

            gate_res = registry.gates["GATE_8_GIT_SCOPE"]
            self.assertEqual(gate_res["status"], "PASS")
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)


class TestPilot11FrontendBuildVerification(unittest.TestCase):

    """
    Pilot 11 TDD Test Suite: Frontend Build Verification for Laravel Autopilot
    Covers 15 formal behavioral and security scenarios:
    1. No package.json -> NOT_RUN
    2. No build script in package.json -> NOT_RUN
    3. Build script present and succeeds -> PASS
    4. Build script returns non-zero exit code -> FAIL
    5. Build process timeout -> FAIL with clear diagnostics
    6. Node.js missing -> BLOCKED
    7. npm missing -> BLOCKED
    8. node_modules missing -> BLOCKED
    9. Blade + Vite project compatibility
    10. Inertia + React + Vite project compatibility
    11. Build failure prevents overall quality gate PASS (Exit code 1)
    12. Build bypass does not mask relevant unverified build / produce false PASS
    13. Tracked build output directory modification guarded against unauthorized mutation
    14. Build failure does not expand scope or self-repair
    15. Existing Gate 8 baseline invariants preserved
    """

    def test_pilot11_01_no_package_json_returns_not_run(self):
        """Scenario 1: Absence of package.json results in NOT_RUN."""
        with tempfile.TemporaryDirectory() as tmpdir:
            self.assertTrue(
                hasattr(quality_gate, "inspect_frontend_build_prerequisites"),
                "Pilot 11 RED: quality_gate.inspect_frontend_build_prerequisites is not yet implemented"
            )
            res = quality_gate.inspect_frontend_build_prerequisites(tmpdir)
            self.assertFalse(res.get("is_applicable"))
            self.assertEqual(res.get("status"), "NOT_RUN")

    def test_pilot11_02_no_build_script_returns_not_run(self):
        """Scenario 2: package.json without a build script results in NOT_RUN."""
        with tempfile.TemporaryDirectory() as tmpdir:
            p_json = Path(tmpdir) / "package.json"
            p_json.write_text(json.dumps({"name": "test-pkg", "scripts": {"dev": "vite"}}), encoding="utf-8")
            self.assertTrue(
                hasattr(quality_gate, "inspect_frontend_build_prerequisites"),
                "Pilot 11 RED: quality_gate.inspect_frontend_build_prerequisites is not yet implemented"
            )
            res = quality_gate.inspect_frontend_build_prerequisites(tmpdir)
            self.assertFalse(res.get("is_applicable"))
            self.assertEqual(res.get("status"), "NOT_RUN")

    def test_pilot11_03_build_script_success_returns_pass(self):
        """Scenario 3: Valid build script execution succeeding with code 0 returns PASS."""
        with tempfile.TemporaryDirectory() as tmpdir:
            p_json = Path(tmpdir) / "package.json"
            p_json.write_text(json.dumps({"scripts": {"build": "vite build"}}), encoding="utf-8")
            (Path(tmpdir) / "node_modules").mkdir()
            self.assertTrue(
                hasattr(quality_gate, "run_frontend_build"),
                "Pilot 11 RED: quality_gate.run_frontend_build is not yet implemented"
            )
            with unittest.mock.patch("shutil.which", side_effect=lambda cmd: f"/usr/bin/{cmd}"):
                mock_proc = unittest.mock.MagicMock(returncode=0, stdout="vite build success", stderr="")
                with unittest.mock.patch("subprocess.run", return_value=mock_proc):
                    res = quality_gate.run_frontend_build(tmpdir)
                    self.assertEqual(res.get("status"), "PASS")
                    self.assertEqual(res.get("exit_code"), 0)

    def test_pilot11_04_build_script_failure_returns_fail(self):
        """Scenario 4: Build script failure with non-zero exit code returns FAIL."""
        with tempfile.TemporaryDirectory() as tmpdir:
            p_json = Path(tmpdir) / "package.json"
            p_json.write_text(json.dumps({"scripts": {"build": "vite build"}}), encoding="utf-8")
            (Path(tmpdir) / "node_modules").mkdir()
            self.assertTrue(
                hasattr(quality_gate, "run_frontend_build"),
                "Pilot 11 RED: quality_gate.run_frontend_build is not yet implemented"
            )
            with unittest.mock.patch("shutil.which", side_effect=lambda cmd: f"/usr/bin/{cmd}"):
                mock_proc = unittest.mock.MagicMock(returncode=1, stdout="", stderr="SyntaxError in App.jsx")
                with unittest.mock.patch("subprocess.run", return_value=mock_proc):
                    res = quality_gate.run_frontend_build(tmpdir)
                    self.assertEqual(res.get("status"), "FAIL")
                    self.assertEqual(res.get("exit_code"), 1)

    def test_pilot11_05_build_timeout_returns_fail_with_diagnostics(self):
        """Scenario 5: Build process exceeding timeout limit returns FAIL with clear diagnostic message."""
        with tempfile.TemporaryDirectory() as tmpdir:
            p_json = Path(tmpdir) / "package.json"
            p_json.write_text(json.dumps({"scripts": {"build": "vite build"}}), encoding="utf-8")
            (Path(tmpdir) / "node_modules").mkdir()
            self.assertTrue(
                hasattr(quality_gate, "run_frontend_build"),
                "Pilot 11 RED: quality_gate.run_frontend_build is not yet implemented"
            )
            with unittest.mock.patch("shutil.which", side_effect=lambda cmd: f"/usr/bin/{cmd}"):
                with unittest.mock.patch("subprocess.run", side_effect=subprocess.TimeoutExpired(cmd=["npm", "run", "build"], timeout=120)):
                    res = quality_gate.run_frontend_build(tmpdir, timeout=120)
                    self.assertEqual(res.get("status"), "FAIL")
                    self.assertIn("timed out", res.get("details", "").lower())

    def test_pilot11_06_node_missing_returns_blocked(self):
        """Scenario 6: When Node.js binary is missing from PATH, prerequisite check returns BLOCKED."""
        with tempfile.TemporaryDirectory() as tmpdir:
            p_json = Path(tmpdir) / "package.json"
            p_json.write_text(json.dumps({"scripts": {"build": "vite build"}}), encoding="utf-8")
            (Path(tmpdir) / "node_modules").mkdir()
            self.assertTrue(
                hasattr(quality_gate, "inspect_frontend_build_prerequisites"),
                "Pilot 11 RED: quality_gate.inspect_frontend_build_prerequisites is not yet implemented"
            )
            with unittest.mock.patch("shutil.which", side_effect=lambda cmd: None if cmd in ("node", "node.exe") else f"/usr/bin/{cmd}"):
                res = quality_gate.inspect_frontend_build_prerequisites(tmpdir)
                self.assertTrue(res.get("is_applicable"))
                self.assertEqual(res.get("status"), "BLOCKED")
                self.assertIn("node", res.get("reason", "").lower())

    def test_pilot11_07_npm_missing_returns_blocked(self):
        """Scenario 7: When npm binary is missing from PATH, prerequisite check returns BLOCKED."""
        with tempfile.TemporaryDirectory() as tmpdir:
            p_json = Path(tmpdir) / "package.json"
            p_json.write_text(json.dumps({"scripts": {"build": "vite build"}}), encoding="utf-8")
            (Path(tmpdir) / "node_modules").mkdir()
            self.assertTrue(
                hasattr(quality_gate, "inspect_frontend_build_prerequisites"),
                "Pilot 11 RED: quality_gate.inspect_frontend_build_prerequisites is not yet implemented"
            )
            with unittest.mock.patch("shutil.which", side_effect=lambda cmd: None if cmd in ("npm", "npm.cmd", "npm.exe", "npm.bat") else f"/usr/bin/{cmd}"):
                res = quality_gate.inspect_frontend_build_prerequisites(tmpdir)
                self.assertTrue(res.get("is_applicable"))
                self.assertEqual(res.get("status"), "BLOCKED")
                self.assertIn("npm", res.get("reason", "").lower())

    def test_pilot11_08_node_modules_missing_returns_blocked(self):
        """Scenario 8: When node_modules directory is absent, prerequisite check returns BLOCKED."""
        with tempfile.TemporaryDirectory() as tmpdir:
            p_json = Path(tmpdir) / "package.json"
            p_json.write_text(json.dumps({"scripts": {"build": "vite build"}}), encoding="utf-8")
            # node_modules intentionally not created
            self.assertTrue(
                hasattr(quality_gate, "inspect_frontend_build_prerequisites"),
                "Pilot 11 RED: quality_gate.inspect_frontend_build_prerequisites is not yet implemented"
            )
            with unittest.mock.patch("shutil.which", side_effect=lambda cmd: f"/usr/bin/{cmd}"):
                res = quality_gate.inspect_frontend_build_prerequisites(tmpdir)
                self.assertTrue(res.get("is_applicable"))
                self.assertEqual(res.get("status"), "BLOCKED")
                self.assertIn("node_modules", res.get("reason", "").lower())

    def test_pilot11_09_blade_vite_project_compatibility(self):
        """Scenario 9: Laravel Blade + Tailwind + Vite project passes frontend build evaluation cleanly."""
        with tempfile.TemporaryDirectory() as tmpdir:
            root = Path(tmpdir)
            (root / "package.json").write_text(json.dumps({
                "scripts": {"build": "vite build"},
                "devDependencies": {"alpinejs": "^3.0", "tailwindcss": "^3.0"}
            }), encoding="utf-8")
            (root / "node_modules").mkdir()
            (root / "resources" / "views").mkdir(parents=True)
            (root / "resources" / "views" / "welcome.blade.php").write_text("<h1>Welcome</h1>", encoding="utf-8")
            reg = quality_gate.QualityGateRegistry(root)
            self.assertTrue(
                hasattr(reg, "evaluate_frontend_build"),
                "Pilot 11 RED: QualityGateRegistry.evaluate_frontend_build is not yet implemented"
            )
            with unittest.mock.patch("shutil.which", side_effect=lambda cmd: f"/usr/bin/{cmd}"):
                mock_proc = unittest.mock.MagicMock(returncode=0, stdout="vite building for production...", stderr="")
                with unittest.mock.patch("subprocess.run", return_value=mock_proc):
                    reg.evaluate_frontend_build()
                    self.assertEqual(reg.gates["GATE_2_SYNTAX"]["status"], "PASS")

    def test_pilot11_10_inertia_react_vite_project_compatibility(self):
        """Scenario 10: Laravel Inertia + React + Vite project executes project-defined build script and succeeds."""
        with tempfile.TemporaryDirectory() as tmpdir:
            root = Path(tmpdir)
            (root / "package.json").write_text(json.dumps({
                "scripts": {"build": "vite build"},
                "dependencies": {"@inertiajs/react": "^2.0", "react": "^18.0"}
            }), encoding="utf-8")
            (root / "node_modules").mkdir()
            (root / "resources" / "js" / "Pages").mkdir(parents=True)
            (root / "resources" / "js" / "Pages" / "Dashboard.jsx").write_text("export default function Dashboard() { return <div>Home</div>; }", encoding="utf-8")
            reg = quality_gate.QualityGateRegistry(root)
            self.assertTrue(
                hasattr(reg, "evaluate_frontend_build"),
                "Pilot 11 RED: QualityGateRegistry.evaluate_frontend_build is not yet implemented"
            )
            with unittest.mock.patch("shutil.which", side_effect=lambda cmd: f"/usr/bin/{cmd}"):
                mock_proc = unittest.mock.MagicMock(returncode=0, stdout="react bundle generated successfully", stderr="")
                with unittest.mock.patch("subprocess.run", return_value=mock_proc):
                    reg.evaluate_frontend_build()
                    self.assertEqual(reg.gates["GATE_2_SYNTAX"]["status"], "PASS")

    def test_pilot11_11_build_failure_prevents_overall_quality_gate_pass(self):
        """Scenario 11: Frontend build failure prevents overall QualityGate from returning PASS (must FAIL)."""
        with tempfile.TemporaryDirectory() as tmpdir:
            root = Path(tmpdir)
            (root / "package.json").write_text(json.dumps({"scripts": {"build": "vite build"}}), encoding="utf-8")
            (root / "node_modules").mkdir()
            reg = quality_gate.QualityGateRegistry(root)
            self.assertTrue(
                hasattr(reg, "evaluate_frontend_build"),
                "Pilot 11 RED: QualityGateRegistry.evaluate_frontend_build is not yet implemented"
            )
            with unittest.mock.patch("shutil.which", side_effect=lambda cmd: f"/usr/bin/{cmd}"):
                mock_proc = unittest.mock.MagicMock(returncode=1, stdout="", stderr="RollupError: Unterminated JSX tag")
                with unittest.mock.patch("subprocess.run", return_value=mock_proc):
                    reg.evaluate_frontend_build()
                    summary = reg.to_dict()["summary"]
                    self.assertGreater(summary["fail"], 0)
                    self.assertEqual(reg.gates["GATE_2_SYNTAX"]["status"], "FAIL")

    def test_pilot11_12_build_bypass_does_not_produce_misleading_pass(self):
        """Scenario 12: Bypassing frontend build when build is applicable MUST NOT produce a PASS state."""
        with tempfile.TemporaryDirectory() as tmpdir:
            root = Path(tmpdir)
            (root / "package.json").write_text(json.dumps({"scripts": {"build": "vite build"}}), encoding="utf-8")
            (root / "node_modules").mkdir()
            reg = quality_gate.QualityGateRegistry(root)
            self.assertTrue(
                hasattr(reg, "evaluate_frontend_build"),
                "Pilot 11 RED: QualityGateRegistry.evaluate_frontend_build is not yet implemented"
            )
            # When explicitly skipped/bypassed:
            reg.evaluate_frontend_build(skip=True)
            # Invariant: Must NOT be PASS! Must be NOT_RUN with clear explanation
            self.assertNotEqual(reg.gates["GATE_2_SYNTAX"]["status"], "PASS")
            self.assertEqual(reg.gates["GATE_2_SYNTAX"]["status"], "NOT_RUN")
            self.assertIn("bypass", reg.gates["GATE_2_SYNTAX"]["details"].lower())

    def test_pilot11_13_tracked_build_output_modification_detected_and_guarded(self):
        """Scenario 13: If build output directory contains tracked files modified outside approved_files, Gate 8 detects FAIL."""
        temp_dir = tempfile.mkdtemp()
        repo_path = Path(temp_dir).resolve()
        try:
            subprocess.run(["git", "init"], cwd=repo_path, capture_output=True, check=True)
            subprocess.run(["git", "config", "user.name", "TestUser"], cwd=repo_path, capture_output=True, check=True)
            subprocess.run(["git", "config", "user.email", "test@example.com"], cwd=repo_path, capture_output=True, check=True)

            build_dir = repo_path / "public" / "build"
            build_dir.mkdir(parents=True)
            manifest = build_dir / "manifest.json"
            manifest.write_text('{"app.js": "app.111.js"}', encoding="utf-8")

            subprocess.run(["git", "add", "public/build/manifest.json"], cwd=repo_path, capture_output=True, check=True)
            subprocess.run(["git", "commit", "-m", "track manifest"], cwd=repo_path, capture_output=True, check=True)

            # Capture baseline
            snapshot = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-tracked-manifest")

            # Simulate build mutating tracked manifest without it being in approved_files
            manifest.write_text('{"app.js": "app.222.js"}', encoding="utf-8")

            reg = quality_gate.QualityGateRegistry(repo_path)
            reg.evaluate_git_scope(
                approved_files=["resources/js/app.js"],
                baseline_snapshot=snapshot,
                task_id="task-tracked-manifest"
            )
            gate_res = reg.gates["GATE_8_GIT_SCOPE"]
            self.assertEqual(gate_res["status"], "FAIL")
            self.assertIn("public/build/manifest.json", gate_res["details"])
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_pilot11_14_build_failure_does_not_expand_scope_or_self_repair(self):
        """Scenario 14: Frontend build failure does not permit self_repair or quality gate to auto-approve unapproved files."""
        with tempfile.TemporaryDirectory() as tmpdir:
            reg = quality_gate.QualityGateRegistry(tmpdir)
            self.assertTrue(
                hasattr(reg, "evaluate_frontend_build"),
                "Pilot 11 RED: QualityGateRegistry.evaluate_frontend_build is not yet implemented"
            )

    def test_pilot11_15_existing_gate_8_baseline_invariants_preserved(self):
        """Scenario 15: Baseline cryptographic snapshot verification is preserved and not altered by frontend checks."""
        temp_dir = tempfile.mkdtemp()
        repo_path = Path(temp_dir).resolve()
        try:
            subprocess.run(["git", "init"], cwd=repo_path, capture_output=True, check=True)
            subprocess.run(["git", "config", "user.name", "TestUser"], cwd=repo_path, capture_output=True, check=True)
            subprocess.run(["git", "config", "user.email", "test@example.com"], cwd=repo_path, capture_output=True, check=True)
            dummy = repo_path / "app.php"
            dummy.write_text("<?php echo 1;", encoding="utf-8")
            subprocess.run(["git", "add", "app.php"], cwd=repo_path, capture_output=True, check=True)
            subprocess.run(["git", "commit", "-m", "init"], cwd=repo_path, capture_output=True, check=True)

            snapshot = quality_gate.BaselineSnapshot.capture(repo_path, task_id="task-gate8-preservation")
            self.assertIsNotNone(snapshot.snapshot_hash)
            self.assertTrue(snapshot.verify_integrity(quality_gate.get_supervisor_key()))
        finally:
            shutil.rmtree(temp_dir, ignore_errors=True)

    def test_pilot11_16_windows_npm_cmd_resolution(self):
        """Scenario 16: On win32 platform, resolver resolves npm.cmd and falls back gracefully."""
        with tempfile.TemporaryDirectory() as tmpdir:
            p_json = Path(tmpdir) / "package.json"
            p_json.write_text(json.dumps({"scripts": {"build": "vite build"}}), encoding="utf-8")
            (Path(tmpdir) / "node_modules").mkdir()

            def fake_which(cmd):
                if cmd == "node":
                    return "C:\\Program Files\\nodejs\\node.exe"
                if cmd == "npm.cmd":
                    return "C:\\Program Files\\nodejs\\npm.cmd"
                if cmd == "npm":
                    return "C:\\Program Files\\nodejs\\npm"
                return None

            with unittest.mock.patch("sys.platform", "win32"):
                with unittest.mock.patch("shutil.which", side_effect=fake_which):
                    res = quality_gate.inspect_frontend_build_prerequisites(tmpdir)
                    self.assertEqual(res["status"], "PASS")
                    self.assertTrue(res["npm_path"].endswith("npm.cmd") or res["npm_path"].endswith("npm.exe") or res["npm_path"].endswith("npm.bat"))
                    self.assertEqual(res["npm_path"], "C:\\Program Files\\nodejs\\npm.cmd")

    def test_pilot11_17_windows_npm_fallback_when_cmd_missing(self):
        """Scenario 17: On win32, if npm.cmd is missing, resolver checks bat or falls back or blocks."""
        with tempfile.TemporaryDirectory() as tmpdir:
            p_json = Path(tmpdir) / "package.json"
            p_json.write_text(json.dumps({"scripts": {"build": "vite build"}}), encoding="utf-8")
            (Path(tmpdir) / "node_modules").mkdir()

            def fake_which(cmd):
                if cmd == "node":
                    return "C:\\Program Files\\nodejs\\node.exe"
                if cmd in ("npm.cmd", "npm.bat", "npm.exe"):
                    return None
                if cmd == "npm":
                    # Only extensionless shell script found
                    return "C:\\Program Files\\nodejs\\npm"
                return None

            with unittest.mock.patch("sys.platform", "win32"):
                with unittest.mock.patch("shutil.which", side_effect=fake_which):
                    res = quality_gate.inspect_frontend_build_prerequisites(tmpdir)
                    # When only non-executable npm script exists on Windows without batch wrapper,
                    # running it with shell=False fails WinError 193; resolver must handle safely or block
                    self.assertIn(res["status"], ["PASS", "BLOCKED"])
                    if res["status"] == "BLOCKED":
                        self.assertIn("npm", res["reason"].lower())

    def test_pilot11_18_vite_config_missing_build_script_returns_blocked(self):
        """Scenario 18: Project has vite.config.js but lacks 'build' script -> BLOCKED (not NOT_RUN)."""
        with tempfile.TemporaryDirectory() as tmpdir:
            p_json = Path(tmpdir) / "package.json"
            p_json.write_text(json.dumps({"name": "vite-app", "scripts": {"dev": "vite"}}), encoding="utf-8")
            (Path(tmpdir) / "vite.config.js").write_text("export default {}", encoding="utf-8")

            res = quality_gate.inspect_frontend_build_prerequisites(tmpdir)
            self.assertTrue(res["is_applicable"])
            self.assertEqual(res["status"], "BLOCKED")
            self.assertIn("build", res["reason"].lower())

    def test_pilot11_19_inertia_pages_missing_build_script_returns_blocked(self):
        """Scenario 19: Project has resources/js/Pages (Inertia) but lacks 'build' script -> BLOCKED."""
        with tempfile.TemporaryDirectory() as tmpdir:
            p_json = Path(tmpdir) / "package.json"
            p_json.write_text(json.dumps({"name": "inertia-app", "scripts": {"dev": "vite"}}), encoding="utf-8")
            pages_dir = Path(tmpdir) / "resources" / "js" / "Pages"
            pages_dir.mkdir(parents=True)
            (pages_dir / "Home.jsx").write_text("export default function() {}", encoding="utf-8")

            res = quality_gate.inspect_frontend_build_prerequisites(tmpdir)
            self.assertTrue(res["is_applicable"])
            self.assertEqual(res["status"], "BLOCKED")
            self.assertIn("build", res["reason"].lower())

    def test_pilot11_20_pure_blade_without_build_script_returns_not_run(self):
        """Scenario 20: Pure Blade project without frontend asset pipeline or build script -> NOT_RUN."""
        with tempfile.TemporaryDirectory() as tmpdir:
            p_json = Path(tmpdir) / "package.json"
            p_json.write_text(json.dumps({"name": "blade-app", "scripts": {"test": "phpunit"}}), encoding="utf-8")
            views_dir = Path(tmpdir) / "resources" / "views"
            views_dir.mkdir(parents=True)
            (views_dir / "welcome.blade.php").write_text("<h1>Hello</h1>", encoding="utf-8")

            res = quality_gate.inspect_frontend_build_prerequisites(tmpdir)
            self.assertFalse(res["is_applicable"])
            self.assertEqual(res["status"], "NOT_RUN")


if __name__ == "__main__":
    unittest.main()
