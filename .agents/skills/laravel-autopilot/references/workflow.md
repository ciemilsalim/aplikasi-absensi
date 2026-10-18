# Autonomous Engineering Workflow Reference (`workflow.md` — Phase 2)

This document defines the 6-phase autonomous engineering lifecycle used by `laravel-autopilot` (Phase 2 Upgrade). Each phase must be executed with verified evidence rather than assumptions.

---

## Phase A — Discover

### Objective
Map the exact topology, dependencies, framework versions, route hierarchy, and git state before writing any code or proposing solutions.

### Steps
1. **Identify Laravel Project Root**:
   - Verify presence of `artisan` and `composer.json`.
   - Run `python .agents/skills/laravel-autopilot/scripts/preflight.py --json`.
2. **Inspect Manifests and Versions**:
   - Run `python .agents/skills/laravel-autopilot/scripts/laravel_inspector.py --json`.
   - Record exact versions: PHP constraint, installed Laravel framework version (from `composer.lock`), dev dependencies.
3. **Detect Frontend Stack**:
   - Check whether the application uses:
     - Blade templates (`resources/views`)
     - Livewire components (`app/Livewire` or `app/Http/Livewire`)
     - Inertia.js SPA (`resources/js/Pages` with React, Vue, or Svelte)
     - Styling engine: Tailwind CSS (v3 or v4), CSS Modules, or Bootstrap.
4. **Detect Authentication & Authorization**:
   - Check authentication starter kits: Breeze, Jetstream, Fortify, or custom guards.
   - Check API security: Laravel Sanctum, Passport, or session-only.
   - Check authorization mechanisms: Gates, Policies (`app/Policies`), Middleware (`CheckRoleMiddleware`, etc.).
5. **Inspect Registered Routes**:
   - Run `python .agents/skills/laravel-autopilot/scripts/laravel_inspector.py --routes --json`.
   - Never use fragile regex on route files when `php artisan route:list --json` is available.
6. **Task Identification & Cryptographic Baseline Capture**:
   - Establish unique task identifier (`task_id`) and repository identity.
   - Run `python .agents/skills/laravel-autopilot/scripts/quality_gate.py --capture-baseline --task-id <task-id> --json`.
   - Verify baseline evidence is securely recorded in external storage outside the repository root.
   - Confirm baseline cannot be silently replaced or overwritten.
7. **Approved File Scope Definition**:
   - Explicitly list approved files/directories for the task before modifying code.
8. **Inspect Git Working Tree Precision**:
   - Run `git status --short`.
   - Strictly distinguish between tracked modifications and untracked files.
   - Note any pre-existing uncommitted files so they are not touched or overwritten.
9. **Secrets Safety Invariant**:
   - Never inspect or log raw `.env` contents.
   - Use `.env.example` or sanitized Artisan configurations.

---

## Phase B — Research and Plan

### Objective
Formulate a minimal, robust, and verified implementation plan grounded in authoritative documentation and existing project patterns.

### Steps
1. **Authoritative Documentation & Compatibility**:
   - Verify features and syntax against the installed Laravel version (e.g. Laravel 11/12 features vs legacy Laravel 8/9 methods).
   - Check package documentation for installed dependencies.
2. **Code Path Tracing**:
   - Trace requests from Route -> Middleware -> Controller / Action -> Request Validator -> Model / Service / Transaction -> View / JSON Response.
3. **Risk & Safety Assessment**:
   - **Security**: IDOR vulnerabilities, missing authorization gates, unvalidated input.
   - **Data Integrity**: Multi-table writes requiring database transactions (`DB::transaction`).
   - **Regression**: Impact on existing routes, shared models, or database schemas.
4. **Implementation Plan & Acceptance Criteria**:
   - Define exact files to create or modify.
   - Write clear, testable acceptance criteria.
5. **Decision Boundary**:
   - **AI Technical Decisions**: Architecture, controller structure, query optimization, validation rules, test structure, refactoring conforming to conventions.
   - **Human Decision Gate (`NEEDS HUMAN DECISION`)**: Business rules, pricing, user roles/privileges, legal policies, destructive database resets, external financial integrations. Stop dependent tasks and report options clearly.

---

## Phase C — Implement

### Objective
Execute the smallest coherent implementation that satisfies the acceptance criteria while respecting existing conventions.

### Rules & Standards
1. **Conventions**:
   - Match naming conventions (PSR-12 / Laravel Pint, PascalCase controllers, snake_case table columns).
   - Reuse existing base classes, traits, and layout templates.
2. **Input Validation**:
   - Prefer Form Request classes (`php artisan make:request`) or `$request->validate([...])` with strict validation rules.
3. **Authorization**:
   - Enforce authorization in Controllers via `$this->authorize(...)` or Route middleware.
4. **Database Transactions**:
   - Wrap atomic state transitions or multi-record inserts in `DB::transaction(function () { ... })`.
5. **Automated Tests**:
   - Write or update corresponding Pest or PHPUnit tests in `tests/Feature` or `tests/Unit`.
6. **No Spurious Dependencies**:
   - Do not install new composer/npm packages unless strictly necessary and confirmed compatible.
7. **No Secrets in Code**:
   - Do not hardcode passwords, keys, or endpoints into source code.

---

## Phase D — Verify & Quality Gates

### Objective
Prove correctness through automated checks, hardened test execution, and formal Quality Gates evaluation.

### Verification Checklist
1. **PHP Syntax & Frontend Build Validation (Gate 2)**:
   - Run `php -l <path-to-modified-file>` on all newly created or touched PHP files.
   - If `package.json` contains a `build` script, run frontend build verification via `quality_gate.py --check-frontend-build` (or automatically via `--eval-core`). A build failure directly yields `FAIL` and blocks task completion.
2. **Route List Verification**:
   - Verify newly added or modified routes appear correctly via `laravel_inspector.py --filter-route <route-name>`.
3. **Hardened Test Execution**:
   - Run targeted tests via `python .agents/skills/laravel-autopilot/scripts/test_runner.py --filter <TestName> --json`.
   - Guaranteed `shell=False`, prohibited flag rejection, and multi-layer DB check.
4. **Feature & Unit Test Suites**:
   - Run relevant test suites via `test_runner.py --suite Feature` or `test_runner.py --suite Unit`.
5. **Negative Case & Authorization Checks**:
   - Explicitly test negative cases (unauthenticated 401/302, unauthorized 403, invalid input 422).
6. **Mandatory Quality Gates & Baseline Scope Verification**:

   - Run `python .agents/skills/laravel-autopilot/scripts/quality_gate.py --eval-core --task-id <task-id> --approved-files <files> --json`.
   - **Process Exit-Code Contract**:
     - `0`: All evaluated gates PASS. Verification succeeded.
     - `1`: At least one gate FAIL (baseline violation, secret leak, or unapproved file). Must fix defect before proceeding.
     - `2`: Evaluation BLOCKED (baseline missing, signature invalid, task mismatch, or unsafe DB). Workflow is halted; do NOT proceed.
     - `3`: Invalid CLI arguments or internal execution exception.
   - Callers and subagents must inspect the process exit code and fail closed if non-zero.

---

## Phase E — Bounded Self-Repair (7-Step Repeatable Loop)

### Objective
Diagnose and resolve failures autonomously within strict safety bounds and a maximum of 3 repair iterations.

### The 7-Step Repeatable Workflow:
```
[ 1. Run Targeted Test ] ──► [ 2. Capture Failure Output ] ──► [ 3. Identify Root Cause & Early-Stop ]
                                                                                   │
                                                                                   ▼
[ 7. Record Attempt Log ] ◄── [ 6. Rerun Failed Test ] ◄── [ 5. Smallest Justified Fix ] ◄── [ 4. Inspect Code & Tests ]
```

1. **Step 1 — Run Targeted Test**:
   - Execute the failing check using `test_runner.py`.
2. **Step 2 — Capture Actual Failure**:
   - Capture failure diagnostics (test class, method, failing assertion, exception, file, and line number).
3. **Step 3 — Identify Probable Root Cause & Early-Stop Check**:
   - Run `self_repair.py diagnose --text "<output>" --json`.
   - Diagnoses PHP errors as well as Frontend Build failures (`FRONTEND_SYNTAX_ERROR`, `FRONTEND_MODULE_NOT_FOUND`, `FRONTEND_BUILD_CONFIG_ERROR`).
   - Check Early-Stop triggers immediately:
     - Missing infrastructure or dependencies (`Connection refused`, `RedisException`, missing external npm package/dependency).
     - Unsafe or ambiguous database target.
     - Ambiguous business requirement (`NEEDS HUMAN DECISION`).
     - Destructive or irreversible action required.
   - If an early-stop condition is met, **HALT IMMEDIATELY** and report to human developer.
4. **Step 4 — Inspect Affected Code & Related Tests**:
   - Trace the exact file and line number diagnosed by the engine.
5. **Step 5 — Make Smallest Justified Correction**:
   - Make a focused, surgical edit addressing the proven root cause.
6. **Step 6 — Rerun Failed Test & Regression Suite**:
   - Rerun `test_runner.py` with the targeted test and relevant neighboring regression tests.
7. **Step 7 — Record Attempt & Evaluate Loop Bound**:
   - Record attempt outcome (iteration number, hypothesis, files modified, test status).
   - If resolved (`status == "PASSED"`): exit loop with success.
   - If failed and `iteration < 3`: return to Step 1 for next attempt.
   - If failed and `iteration >= 3`: **HALT REPAIR LOOP**, summarize diagnostic evidence, and escalate to human review.

### Anti-Degradation Rule:
Never comment out assertions, remove auth gates, or bypass validation rules just to make a test pass.

---

## Phase F — Final Audit and Report

### Objective
Produce a factual, audit-ready implementation report summarizing verified outcomes.

### Requirements
1. Render report using `templates/implementation-report.md`.
2. Include:
   - Requirement description.
   - Created / modified files.
   - Architecture & implementation decisions.
   - Evaluated 8 Quality Gates with statuses (`PASS`, `FAIL`, `BLOCKED`, `NOT_RUN`).
   - Self-repair journal (if iterations were performed).
   - Security, IDOR, and data integrity assessment.
   - Git status (distinguishing tracked modifications from untracked files).
   - Checkpoint reference (if created).
3. Do not declare work "complete" if acceptance criteria are unverified or failing.

---

## Trust Boundary Architecture & Enforcement Limits

### Procedural vs Supervisor Boundary
- **Procedural Enforcement**:
  Workflow rules inside this skill instruct the agent to capture baselines before file edits, verify Gate 8 before declaring completion, and respect exit codes. In a single-user desktop environment (e.g. Antigravity IDE), the agent possesses user-level shell privileges. Therefore, text-based workflow guidelines cannot technically prevent an adversarial script from running shell commands outside the skill.
- **Supervisor-Enforced Boundary**:
  A true cryptographic security boundary requires an external, isolated supervisor process:
  1. Supervisor captures baseline before invoking agent.
  2. Baseline snapshots are stored in protected storage inaccessible to the agent.
  3. Supervisor signs snapshots with `AUTOPILOT_SUPERVISOR_KEY` inaccessible to the agent.
  4. Verifier asserts the HMAC signature using that key.
- **Current Operational Claim**:
  `laravel-autopilot` implements the highest supportable supervised-use design (`SUPERVISED_USE_LOCAL`). It provides robust error detection, prevents accidental overwrite, and detects unauthorized changes, but does NOT claim non-repudiation against an adversarial process with equivalent filesystem access.
