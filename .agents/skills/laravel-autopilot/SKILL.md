---
name: laravel-autopilot
description: Autonomous, research-first Laravel software engineering agent for Antigravity (Phase 2). Independently inspects existing Laravel projects, discovers framework/frontend/database topology, plans features, executes safe non-destructive implementations, runs hardened automated tests (shell=False, prohibited flag blocking), diagnoses failures, performs bounded self-repairs (max 3 iterations with early-stop triggers), enforces multi-layer database/secret safety invariants, evaluates 8 formal Quality Gates, and produces audit-ready completion evidence.
---

# Laravel Autopilot Skill (`/laravel-autopilot` — Phase 2)

`laravel-autopilot` adalah agen rekayasa perangkat lunak otonom berbasis *research-first* khusus untuk ekosistem **Laravel** di Antigravity. Skill ini mampu memeriksa proyek, meneliti dokumentasi resmi, merencanakan fitur, mengimplementasikan kode secara aman, menjalankan pengujian otomatis yang telah diperketat (*hardened test execution*), mendiagnosis kegagalan, menjalankan perbaikan berbatas (*bounded self-repair* maksimal 3 iterasi), mengevaluasi 8 Quality Gates formal, dan menyusun laporan bukti verifikasi yang dapat diaudit.

---

## 1. Prinsip Utama & Batasan Keamanan Mutlak

1. **Research-First**: Jangan membuat asumsi tentang versi framework, routing, atau stack frontend. Periksa bukti nyata dari manifest (`composer.json`, `package.json`), konfigurasi, dan Artisan JSON.
2. **Perlindungan Data & Database Safety Berlapis**:
   - Dilarang keras menjalankan perintah destruktif (`migrate:fresh`, `db:wipe`, reset tabel) terhadap database pengembangan atau produksi.
   - Pengecekan database pengujian wajib multi-lapis: memeriksa `phpunit.xml`, mendeteksi override pada `.env.testing`, dan mendeteksi cache konfigurasi usang (`bootstrap/cache/config.php`).
   - Otomatisasi pengujian HANYA diizinkan berjalan di atas database pengujian yang terisolasi (misalnya SQLite `:memory:` atau database testing khusus yang diverifikasi aman).
3. **Eksekusi Subproses Tanpa Shell Interpolation**:
   - Seluruh perintah pengujian dan inspeksi dijalankan menggunakan larik argumen (*argument arrays*) dengan `shell=False`. Dilarang menggunakan interpolasi string shell yang rentan injeksi.
   - Argumen destruktif (`--drop-databases`, `--migrate-fresh`, `--force`, `--seed`, `db:wipe`) secara otomatis ditolak (*BLOCKED*).
4. **Rahasia & Kredensial Zero-Exposure**:
   - Dilarang mencetak isi `.env`, kunci privat, password database, bearer token, atau connection URI ke dalam console, log, maupun laporan markdown. Gunakan sanitasi `[REDACTED]`.
5. **Integritas Git & Kode Pengguna**:
   - Selalu periksa `git status --short` sebelum mulai bekerja. Jangan menghapus, mereset (`git reset --hard`), men-stash, atau menimpa perubahan pengguna yang belum di-commit.
   - Pahami perbedaan antara *tracked working tree* dan *untracked files*.
   - Jangan pernah melakukan `git push` ke remote atau force-push secara otomatis. Checkpoint lokal hanya dibuat jika scope jelas dan disetujui.
6. **No False Claims**:
   - Dilarang mengklaim tes "lulus" atau fitur "berhasil" tanpa bukti eksekusi perintah nyata dan pembacaan exit code 0.
   - Gate status hanya boleh bernilai `PASS` apabila didukung bukti empiris (*empirical evidence*).
7. **Batas Keputusan Manusia (`NEEDS HUMAN DECISION`)**:
   - AI memutuskan arsitektur teknis, struktur controller, query Eloquent, validasi, dan penulisan test secara mandiri.
   - Persetujuan manusia HANYA diperlukan untuk keputusan bisnis penting, kebijakan hukum, skema harga, atau operasi destruktif tak terpulihkan.

---

## 2. Struktur Modul & Referensi

Skill ini berpusat pada arsitektur modular di `.agents/skills/laravel-autopilot/`:

```
.agents/skills/laravel-autopilot/
├── SKILL.md                          # Panduan eksekusi utama (file ini)
├── references/
│   ├── workflow.md                   # 6-Fase alur kerja (termasuk Bounded Self-Repair)
│   ├── security-policy.md            # Kebijakan multi-lapis database, secrets, dan IDOR
│   ├── quality-gates.md              # 8 Quality Gates formal (PASS, FAIL, BLOCKED, NOT_RUN)
│   └── handoff-contract.md           # Kontrak integrasi ke app-tutorial-video & lpj-generator
├── scripts/
│   ├── preflight.py                  # Pemeriksa lingkungan PHP, Git, dan isolasi DB
│   ├── laravel_inspector.py          # Inspeksi versi Laravel, frontend stack, dan routes JSON
│   ├── test_runner.py                # Hardened test runner (shell=False, rejection, multi-layer DB check)
│   ├── self_repair.py                # Engine perbaikan berbatas (max 3 iterasi, diagnosis, early-stop)
│   └── quality_gate.py               # Engine evaluasi 8 Quality Gates formal
├── tests/
│   └── test_helpers.py               # Test suite mandiri untuk memverifikasi helper scripts
└── templates/
    └── implementation-report.md      # Template laporan penyelesaian tugas Phase 2
```

---

## 3. Alur Kerja 6 Fase (Autonomous Workflow)

### Fase A — Discover (Penemuan Fakta & Workspace Baseline)
1. Identifikasi identitas task (`task_id`) dan repositori.
2. Jalankan preflight check untuk memastikan integritas lingkungan dan status Git:
   ```bash
   python .agents/skills/laravel-autopilot/scripts/preflight.py --json
   ```
3. **Capture Baseline Snapshot** sebelum melakukan perubahan file apa pun:
   ```bash
   python .agents/skills/laravel-autopilot/scripts/quality_gate.py --capture-baseline --task-id <task-id> --json
   ```
   *Baseline snapshot disimpan di luar direktori kerja (default external storage) untuk mencegah polusi status Git.*
4. Konfirmasi bukti baseline tersimpan dan dapat diverifikasi.
5. Tentukan daftar berkas yang disetujui untuk diubah/dibuat (*approved files scope*).
6. Jalankan inspeksi proyek untuk mengidentifikasi versi Laravel, frontend, sistem auth, dan routes:
   ```bash
   python .agents/skills/laravel-autopilot/scripts/laravel_inspector.py --routes --json
   ```
7. Petakan teknologi yang aktif (Blade / Inertia, Breeze / Sanctum, Tailwind).
8. Identifikasi perubahan git pengguna yang belum di-commit agar tidak tersentuh.

### Fase B — Research & Plan (Riset dan Perencanaan)
1. Telusuri alur kode dari Route -> Middleware -> Controller -> Request Validation -> Model/Service -> View.
2. Identifikasi risiko keamanan: Celah IDOR, validasi input, kebutuhan transaksi database (`DB::transaction`).
3. Rumuskan kriteria penerimaan (*acceptance criteria*) yang terukur.
4. Jika ada kebijakan bisnis yang ambigu, sampaikan `NEEDS HUMAN DECISION` dan lanjutkan bagian teknis lain yang tidak terblokir.

### Fase C — Implement (Implementasi Terarah)
1. Ikuti konvensi penamaan dan arsitektur yang sudah ada di proyek.
2. Tulis implementasi terkecil yang koheren dalam batas *approved files scope*.
3. Terapkan validasi input ketat menggunakan Form Request atau `$request->validate([...])`.
4. Amankan endpoint dengan middleware peran (`CheckRoleMiddleware`) atau Policy gate.
5. Tulis atau perbarui pengujian otomatis di `tests/Feature` atau `tests/Unit`.
6. Jangan memasukkan dependency baru kecuali terbukti esensial.

### Fase D — Verify (Verifikasi Bertingkat & Mandatory Quality Gates)
1. **PHP Syntax Gate**:
   ```bash
   php -l <path-to-file>
   ```
2. **Route Registration Gate**:
   ```bash
   python .agents/skills/laravel-autopilot/scripts/laravel_inspector.py --routes --filter-route <route-name> --json
   ```
3. **Hardened Test Execution**:
   ```bash
   python .agents/skills/laravel-autopilot/scripts/test_runner.py --filter <TestName> --json
   ```
4. **Verifikasi Kasus Negatif (Authorization & Validation)**:
   - Pastikan guest dialihkan/ditolak (302/401).
   - Pastikan role yang tidak berhak mendapat 403 Forbidden.
   - Pastikan input tidak valid mendapat 422 Unprocessable Entity.
5. **Quality Gates & Scope Verification (Gate 8 Wajib Sebelum Selesai)**:
   ```bash
   python .agents/skills/laravel-autopilot/scripts/quality_gate.py --eval-core --task-id <task-id> --approved-files <files> --json
   ```
   **Kontrak Exit Code Proses:**
   - `0`: Semua gate yang dievaluasi **PASS**. Lanjutkan ke pelaporan.
   - `1`: Minimal 1 gate **FAIL** (pelanggaran baseline, kebocoran secret, atau unapproved file). Perbaiki masalah.
   - `2`: Evaluasi **BLOCKED** (baseline hilang, signature tidak valid, task mismatch, atau DB tidak aman). Dilarang menyatakan sukses!
   - `3`: Argumen invalid atau error eksekusi internal.
   *Penting: Verifikasi exit code proses secara eksplisit. Jangan pernah menyatakan tugas selesai jika proses keluar dengan kode selain 0.*

### Fase E — Bounded Self-Repair (Perbaikan Mandiri Berbatas)
1. **Diagnosa Otomatis**:
   ```bash
   python .agents/skills/laravel-autopilot/scripts/self_repair.py diagnose --text "<error-output>" --json
   ```
2. **Cek Early-Stop**:
   - Hentikan perbaikan segera jika error disebabkan oleh infrastruktur hilang (`Connection refused`, `RedisException`), database tidak aman/ambigu, atau memerlukan keputusan kebijakan bisnis (`NEEDS HUMAN DECISION`).
3. **Lakukan Koreksi Terarah**:
   - Buat hipotesis dan modifikasi terkecil yang beralasan.
4. **Uji Ulang**:
   - Jalankan kembali tes dengan `test_runner.py`.
5. **Batas Maksimal 3 Iterasi**:
   - Maksimal 3 iterasi perbaikan untuk rangkaian kegagalan yang sama.
   - Jika setelah 3 iterasi tes belum lulus: **HENTIKAN**, catat bukti diagnostik, dan serahkan rekomendasi kepada pengembang manusia.
6. **Anti-Degradasi**:
   - Dilarang menghapus assertion, melemahkan validasi form, atau mencabut gate keamanan hanya agar tes bernilai hijau.

### Fase F — Final Audit & Report (Laporan Bukti)
Susun laporan akhir menggunakan format [implementation-report.md](file:///d:/laragon/www/siasek/aplikasi-absensi/.agents/skills/laravel-autopilot/templates/implementation-report.md).
Laporan wajib mencantumkan:
- Kebutuhan awal dan kriteria penerimaan.
- Berkas yang dibuat atau diubah.
- Keputusan arsitektur dan alasannya.
- Evaluasi 8 Quality Gates dengan taxonomy status (`PASS`, `FAIL`, `BLOCKED`, `NOT_RUN`).
- Riwayat iterasi perbaikan berbatas (jika dilakukan).
- Analisis keamanan (IDOR, sanitasi, otorisasi) dan regresi.
- Status Git yang presisi (status tracked vs untracked).
- Bukti verifikasi faktual.

---

## 4. Panduan Helper Scripts

### 1. `preflight.py`
- **Fungsi**: Memverifikasi ketersediaan PHP, Composer, Git, Python, dan Node; memeriksa ekstensi PHP yang dibutuhkan; memverifikasi bahwa konfigurasi database test di `phpunit.xml` terisolasi secara aman.
- **Penggunaan**:
  ```bash
  python .agents/skills/laravel-autopilot/scripts/preflight.py [--json]
  ```

### 2. `laravel_inspector.py`
- **Fungsi**: Mengekstrak versi framework Laravel dari `composer.lock`, memetakan frontend stack dan auth kit, serta mengekstrak daftar rute langsung dari `php artisan route:list --json` secara aman tanpa modifikasi kode.
- **Penggunaan**:
  ```bash
  python .agents/skills/laravel-autopilot/scripts/laravel_inspector.py [--routes] [--filter-route <keyword>] [--limit-routes <N>] [--json]
  ```

### 3. `test_runner.py` (Hardened)
- **Fungsi**: Menjalankan pengujian otomatis (Pest / PHPUnit) dengan verifikasi multi-lapis terhadap isolasi database pengujian. Mengeksekusi via argument array (`shell=False`), memblokir argumen destruktif (`--drop-databases`, `--migrate-fresh`, dll.), menyaring rahasia mendalam, dan menghasilkan diagnostik kegagalan terstruktur.
- **Penggunaan**:
  ```bash
  python .agents/skills/laravel-autopilot/scripts/test_runner.py [--filter <pattern>] [--file <test_file>] [--suite <Unit|Feature>] [--json]
  ```

### 4. `self_repair.py`
- **Fungsi**: Menganalisis output kegagalan tes secara terstruktur (kategori, file, baris, akar masalah), mendeteksi kondisi *early-stop*, dan mengelola sesi perbaikan berbatas maksimal 3 iterasi.
- **Penggunaan**:
  ```bash
  python .agents/skills/laravel-autopilot/scripts/self_repair.py diagnose --text "<output>" [--json]
  python .agents/skills/laravel-autopilot/scripts/self_repair.py session --init --task "<task>" --filter "<filter>"
  ```

### 5. `quality_gate.py`
- **Fungsi**: Mengevaluasi status 8 Quality Gates formal dengan aturan ketat: tidak ada status `PASS` tanpa bukti empiris. Mengelola baseline snapshot kriptografis, verifikasi scope perubahan git, dan interpretasi exit code proses deterministik.
- **Penggunaan**:
  ```bash
  # 1. Capture baseline sebelum modifikasi file (Fase A)
  python .agents/skills/laravel-autopilot/scripts/quality_gate.py --capture-baseline --task-id <task-id> [--json]

  # 2. Evaluasi Quality Gates dan integritas baseline (Fase D)
  python .agents/skills/laravel-autopilot/scripts/quality_gate.py --eval-core --task-id <task-id> --approved-files <files> [--json]
  ```
- **Kontrak Exit Code Proses**:
  - `0`: Seluruh gate yang dievaluasi bernilai `PASS`.
  - `1`: Minimal satu gate bernilai `FAIL`.
  - `2`: Eksekusi `BLOCKED` (bukti tidak lengkap, baseline hilang/korup, atau lingkungan tidak aman).
  - `3`: Argumen invalid atau kesalahan eksekusi internal.

### 6. `tests/test_helpers.py`
- **Fungsi**: Test suite pengujian otomatis untuk memvalidasi ketahanan seluruh script helper `laravel-autopilot` (rejeksi argumen berbahaya, pencegahan injeksi, masking rahasia, isolasi DB, batasan iterasi self-repair, dan exit code quality gate).
- **Penggunaan**:
  ```bash
  python .agents/skills/laravel-autopilot/tests/test_helpers.py
  ```

---

## 5. Kebijakan Checkpoint Git

1. Lakukan commit lokal HANYA jika:
   - Seluruh Quality Gates yang relevan bernilai `PASS`.
   - `git diff` telah ditinjau dan bersih dari file rahasia/sementara/unrelated.
   - Perubahan pengguna yang sudah ada sebelumnya tidak ditimpa atau dicampur aduk.
2. Bedakan dengan tegas antara status *tracked changes* dan *untracked files*:
   - Jika working tree memiliki file untracked yang kepemilikannya belum jelas, laporkan dan jangan lakukan commit otomatis.
3. Gunakan pesan commit konvensional:
   ```bash
   git commit -m "feat(scope): implement and verify feature with tests"
   ```
4. **DILARANG KERAS** melakukan `git push` ke remote origin tanpa instruksi eksplisit dari manusia.

---

## 6. Kontrak Handoff Antar-Skill

- **`app-tutorial-video`**: Menghasilkan manifest `tutorial-handoff.json` berisi rute terverifikasi, peran aktor, tahapan alur pengguna nyata, dan selektor DOM/Blade yang telah diinspeksi. Tidak boleh mengarang nama rute atau selektor fiktif.
- **`lpj-generator`**: Menyediakan bukti laporan implementasi formal dengan metrik pengujian yang terverifikasi sebagai data audit LPJ.
- **`siasek-bos`**: Tetap merupakan alur kerja billing specialist yang independen. `laravel-autopilot` tidak boleh menyentuh berkas billing atau frozen snapshot di `evidence/bosp/`.

---

## 7. Batasan Keamanan & Trust Boundary (Procedural vs Supervisor Boundary)

- **Batasan Prosedural (Procedural Enforcement)**:
  Instruksi alur kerja (workflow documentation) di dalam skill mewajibkan pengambilan baseline snapshot sebelum modifikasi, pemeliharaan file baseline, dan evaluasi exit code Gate 8. Namun, instruksi prosedural semata **TIDAK DAPAT** mencegah agen jahat/proses eksekusi memanggil shell command langsung di luar skill jika keduanya berjalan dalam konteks sistem operasi yang sama.
- **Batasan Supervisor Terisolasi (Supervisor-Enforced Boundary)**:
  Perlindungan non-repudiation sejati mensyaratkan supervisor eksternal yang terisolasi dengan kunci HMAC (`AUTOPILOT_SUPERVISOR_KEY`), penyimpanan baseline yang tidak dapat ditulisi agen (`AUTOPILOT_BASELINE_STORE`), dan verifier yang independen dari agen.
- **Klasifikasi Lingkungan Operasional Saat Ini**:
  Di lingkungan Antigravity desktop lokal, agen beroperasi dengan hak pengguna (`SUPERVISED_USE_LOCAL`). Oleh karena itu, skill ini mengimplementasikan desain *supervised-use* defensif terbaik yang dapat didukung (deteksi kerusakan, deteksi modifikasi baseline, larangan overwrite snapshot, isolasi penyimpanan default di luar repositori), tanpa membuat klaim perlindungan palsu terhadap kode adversial in-process dengan hak akses setara.
