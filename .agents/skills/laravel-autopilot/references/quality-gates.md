# Quality Gates Reference (`quality-gates.md` — Phase 2)

This document defines the strict verification criteria, status taxonomy, and Quality Gates required by `laravel-autopilot` before any task can be marked complete.

---

## 1. The 8 Quality Gates

```
[ Gate 1: Preflight ] ──► [ Gate 2: Syntax ] ──► [ Gate 3: Routing ] ──► [ Gate 4: DB Safety ]
          ▲                                                                       │
          │                                                                       ▼
[ Gate 8: Git Scope ] ◄── [ Gate 7: Regression ] ◄── [ Gate 6: Negative/Auth ] ◄── [ Gate 5: Tests ]
```

---

## 2. Gate Status Taxonomy

Every Quality Gate must return exactly one of the four formal states:

| Status | Definition | Rule |
| :---: | :--- | :--- |
| **`PASS`** | The gate passed completely with verified empirical evidence. | **Strict Invariant**: A gate CANNOT be set to `PASS` without active supporting evidence collected during execution. |
| **`FAIL`** | The check was executed, but assertion(s) or syntax failed. | Triggers Phase E (Bounded Self-Repair) if repairable within 3 iterations. |
| **`BLOCKED`** | The check could not proceed safely due to environment, missing dependencies, or ambiguous targets. | Halts execution immediately; prompts human review or reports limitation. |
| **`NOT_RUN`** | The gate was not executed in the current session (e.g. regression suite deferred or not applicable). | Must remain `NOT_RUN`; never promoted to `PASS` without execution. |

---

## 3. The 8 Gates Detailed

### Gate 1: Workspace & Preflight Gate (`GATE_1_PREFLIGHT`)
- **Requirement**: The workspace must be verified as a valid Laravel project with working PHP (extensions verified), Composer, and Git.
- **Verification Command**:
  ```bash
  python .agents/skills/laravel-autopilot/scripts/preflight.py --json
  ```
- **Pass Evidence**: `report["status"] in ("PASS", "WARNING")` and `blockers == []`.

---

### Gate 2: Syntax & Frontend Build Gate (`GATE_2_SYNTAX`)
- **Requirement**:
  1. **PHP Syntax**: Every newly created or modified PHP file must compile cleanly via `php -l` without parse errors.
  2. **Frontend Build Verification (Pilot 11)**: If `package.json` defines a `build` script (e.g. Vite, React, Vue, Tailwind bundle), the build must execute and exit with code `0`.
- **Verification Commands**:
  ```bash
  # Check PHP files syntax
  python .agents/skills/laravel-autopilot/scripts/quality_gate.py --check-syntax <files> --json

  # Check Frontend build explicitly or via core evaluation
  python .agents/skills/laravel-autopilot/scripts/quality_gate.py --eval-core --check-frontend-build --json
  ```
- **Status Taxonomy for Frontend Build**:
  - `PASS`: Build executed and completed with exit code 0.
  - `FAIL`: Build failed (exit code non-zero, JSX/TSX syntax error, Rollup failure) or process timed out. Blocks overall PASS.
  - `NOT_RUN`: No `package.json`, pure Blade project without asset pipeline, or explicitly bypassed with `--skip-frontend-build` (must record bypass reason; never masks unverified state as PASS).
  - `BLOCKED`: Build is applicable, but prerequisites (Node.js, npm with Windows `.cmd` support, or `node_modules`) are missing; OR a frontend asset stack (Vite configuration or Inertia Pages) is detected but lacks a `build` script in `package.json`. Blocks overall PASS.
- **Pass Evidence**: Clean `php -l` output across all touched PHP files AND (if applicable) successful frontend build report with exit code 0.


---

### Gate 3: Routing & Middleware Gate (`GATE_3_ROUTING`)
- **Requirement**: All newly declared routes must register cleanly in Laravel's routing table without conflicting URIs, missing controllers, or missing auth middleware.
- **Verification Command**:
  ```bash
  python .agents/skills/laravel-autopilot/scripts/laravel_inspector.py --routes --filter-route <route-name> --json
  ```
- **Pass Evidence**: Route exists in JSON output, HTTP methods match, and expected middleware (`auth`, `role`, etc.) are attached.

---

### Gate 4: Database Safety Gate (`GATE_4_DB_SAFETY`)
- **Requirement**: Automated tests must be proven to execute against an isolated database across 3 layers (`phpunit.xml`, `.env.testing`, and no stale `bootstrap/cache/config.php`).
- **Verification Command**:
  ```bash
  python .agents/skills/laravel-autopilot/scripts/test_runner.py --filter ExampleTest --json
  ```
- **Pass Evidence**: `db_info["is_safe"] == True` with confirmed isolated SQLite or dedicated test DB.

---

### Gate 5: Unit & Feature Test Gate (`GATE_5_UNIT_FEATURE_TEST`)
- **Requirement**: Targeted unit and feature tests covering newly introduced functionality must execute and pass completely.
- **Verification Command**:
  ```bash
  python .agents/skills/laravel-autopilot/scripts/test_runner.py --filter <TestName> --json
  ```
- **Pass Evidence**: `exit_code == 0`, `summary["failed"] == 0`, and assertions pass.

---

### Gate 6: Authorization & Negative Case Gate (`GATE_6_NEGATIVE_AUTH_TEST`)
- **Requirement**: Negative failure cases must be explicitly verified:
  1. **Unauthenticated Access**: Guest requests receive 401 Unauthorized or 302 Redirect.
  2. **Unauthorized Access**: Forbidden roles receive 403 Forbidden.
  3. **Validation Rejection**: Invalid inputs receive 422 Unprocessable Entity.
- **Pass Evidence**: Test runner output contains passing assertions for negative response codes.

---

### Gate 7: Regression Test Gate (`GATE_7_REGRESSION_TEST`)
- **Requirement**: Changes must not break neighboring features or pre-existing feature suites.
- **Verification Command**:
  ```bash
  python .agents/skills/laravel-autopilot/scripts/test_runner.py --suite Feature --json
  ```
- **Pass Evidence**: Pre-existing feature tests pass with 0 regressions.

---

### Gate 8: Git Scope & Secrets Exclusion Gate (`GATE_8_GIT_SCOPE`)
- **Requirement**:
  1. Cryptographic baseline snapshot captured before any file modification via `quality_gate.py --capture-baseline`.
  2. All pre-existing dirty/baseline files retain their exact initial states (SHA-256 match).
  3. No secrets, credentials, private keys, or `.env` files appear in working tree changes.
  4. Working tree changes contain only approved task files matching `--approved-files`.
  5. Pre-existing user modifications remain intact and uncommitted unless explicitly approved.
- **Evaluation Taxonomy & Exit Code**:
  - `PASS` (Exit 0): All protected baseline files intact; all modifications within approved task scope; no secret leaks.
  - `FAIL` (Exit 1): Baseline file modified/deleted/renamed without authorization, unapproved files in working tree, or secret leak detected.
  - `BLOCKED` (Exit 2): Baseline snapshot missing, tampered/forged, task mismatch, or repository identity mismatch. Workflow cannot complete.
- **Pass Evidence**: Cryptographic baseline verification hash match and clean diff inspection confirming absence of secrets or unapproved files.

---

## 4. Automated Quality Gate Tool (`quality_gate.py`)

Run the automated Quality Gate evaluation engine:

### 1. Capture Initial Baseline (Before Modifications):
```bash
python .agents/skills/laravel-autopilot/scripts/quality_gate.py --capture-baseline --task-id <task-id> [--json]
```

### 2. Evaluate Quality Gates & Git Scope (Before Completion):
```bash
python .agents/skills/laravel-autopilot/scripts/quality_gate.py --eval-core --task-id <task-id> --approved-files <files> [--json]
```

### Process Exit Code Contract:
- `0`: All evaluated gates returned `PASS`.
- `1`: At least one gate returned `FAIL`.
- `2`: Execution `BLOCKED` (missing baseline snapshot, signature failure, or DB safety blocker).
- `3`: Invalid CLI arguments or unhandled internal error.
