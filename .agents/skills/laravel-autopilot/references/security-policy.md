# Security & Safety Policy Reference (`security-policy.md` — Phase 2)

This document defines the non-negotiable security boundaries, subprocessor execution rules, and data protection invariants governing all operations by `laravel-autopilot`.

---

## 1. Multi-Layer Database Safety & Target Classification

### Target Classification Matrix
Before executing any test suite, artisan command, or migration, classify the active database target:

| Target Category | Allowed Operations | Strictly Prohibited Operations | Verification Requirement |
| :--- | :--- | :--- | :--- |
| **Isolated Test Database** (e.g. SQLite `:memory:`, `testing.sqlite`) | Read, Insert, Update, Delete, Rollback inside automated tests | Operations affecting non-test environments | Verified via multi-layer check (Layer 1: `phpunit.xml`, Layer 2: `.env.testing`, Layer 3: config cache) |
| **Local Development Database** (e.g. local MySQL/Postgres/SQLite) | Read, additive migrations (`migrate`), non-destructive queries | `migrate:fresh`, `db:wipe`, table truncation, destructive drops | Explicit human confirmation required for resets |
| **Production / Unidentified Database** | Read-only inspection (with human oversight) | ALL destructive or mutating commands | Zero automated mutation allowed |

### Multi-Layer Safety Invariant
1. **Never Assume `phpunit.xml` Alone Proves Isolation**:
   - `phpunit.xml` `<env>` definitions can be bypassed if:
     - `.env.testing` specifies a live/production database.
     - Configuration cache exists (`bootstrap/cache/config.php`), locking in live `.env` settings.
2. **Three-Layer Safety Check (Implemented in `test_runner.py`)**:
   - **Layer 1 (Config Cache)**: Verifies `bootstrap/cache/config.php` does not exist (or has been cleared via `php artisan config:clear`).
   - **Layer 2 (`phpunit.xml`)**: Verifies `DB_CONNECTION == sqlite` and `DB_DATABASE == :memory:` (or dedicated test database).
   - **Layer 3 (`.env.testing`)**: Inspects `.env.testing` if present to guarantee no non-isolated database override exists.
3. **Ambiguous Target Action**:
   - If database isolation cannot be conclusively verified across all three layers, the test runner immediately marks the check as **BLOCKED** and refuses execution.

---

## 2. Hardened Subprocess Execution & Argument Rejection

### Invariants
1. **Larik Argumen Wajib (`shell=False`)**:
   - Semua pemanggilan executable (`php`, `artisan`, `git`) wajib menggunakan list of strings (array argumen), bukan string tunggal yang di-pass ke command shell.
   - `shell=True` dilarang keras untuk mencegah arbitrary shell injection.
2. **Rejeksi Otomatis Perintah Destruktif**:
   - Test runner menolak dan memblokir argumen berikut:
     - `--drop-databases`
     - `--migrate-fresh`
     - `--recreate-databases`
     - `--force`
     - `--seed`
     - `db:wipe`
     - `migrate:fresh`
     - `migrate:reset`
     - `migrate:rollback`
3. **Pembersihan Pola Filter**:
   - Karakter metakarakter shell (`;`, `&`, `|`, `` ` ``, `$()`, `>`, `<`) dalam pola filter tes ditolak secara tegas.

---

## 3. Secrets Management & Deep Redaction Standards

### Invariant
No credentials, passwords, tokens, private keys, or unmasked database connection strings may appear in console output, agent transcripts, logs, or generated markdown artifacts.

### Automated Redaction Patterns (Applied in all helper scripts)
1. **Key-Value Secrets**:
   - `(?i)(password|secret|key|token|api_key|db_password|auth_token|client_secret)=([^\s&;\'"]+)` -> `\1=[REDACTED]`
2. **Bearer Tokens**:
   - `(?i)bearer\s+[a-zA-Z0-9_\-\.]{10,}` -> `Bearer [REDACTED]`
3. **Base64 Encryption Keys**:
   - `base64:[a-zA-Z0-9+/=]{20,}` -> `base64:[REDACTED]`
4. **Database URIs with Credentials**:
   - `(?i)([a-z]+://[^:]+):([^@]+)@` -> `\1:[REDACTED]@`
5. **PDO Connection Strings**:
   - `(?i)(password\s*=\s*)([^\s;]+)` -> `\1[REDACTED]`

---

## 4. Authorization & IDOR Protection

### Invariant
Every endpoint that modifies state or reads private tenant data must enforce server-side authorization.

### Rules
1. **IDOR (Insecure Direct Object Reference) Prevention**:
   - Never query models by raw ID without scoping to the authenticated user or tenant, unless guarded by an explicit Policy:
     ```php
     // INCORRECT (Vulnerable to IDOR)
     $journal = TeachingJournal::findOrFail($id);

     // CORRECT (Scoped or Policy Gate)
     $journal = Auth::user()->teachingJournals()->findOrFail($id);
     // or
     $this->authorize('view', $journal);
     ```
2. **Role & Permission Enforcement**:
   - Ensure all routes are guarded by appropriate middleware (e.g. `auth`, `verified`, `CheckRoleMiddleware`).
3. **Mass Assignment Protection**:
   - Explicitly define `$fillable` attributes on Eloquent models. Avoid blind `$guarded = []`.
   - Never use `$request->all()` in `Model::create()` or `Model::update()`. Always use `$request->validated()` or `$request->only([...])`.

---

## 5. Git Safety & Precision Policy

### Invariants
1. **Precise Inspection**:
   - Selalu periksa `git status --short`.
   - Pisahkan pemahaman antara perubahan file yang terlacak (*tracked modifications*) dan berkas baru yang belum terlacak (*untracked files*).
2. **Never Force-Push & Never Push to Remote**:
   - `git push --force` dilarang mutlak.
   - Jangan pernah melakukan `git push` ke remote origin secara otomatis.
3. **Non-Destructive Operations**:
   - Jangan pernah menjalankan `git reset --hard` atau `git clean -fd` yang berpotensi memusnahkan hasil kerja pengguna.
4. **Clean Checkpoint Only**:
   - Commit lokal hanya dibuat jika seluruh Quality Gates bernilai `PASS`, diff telah diperiksa, dan kepemilikan file jelas.
