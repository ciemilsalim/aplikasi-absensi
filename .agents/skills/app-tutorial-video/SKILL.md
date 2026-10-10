---
name: app-tutorial-video
description: Professional Reusable Cross-Project Video Tutorial Engine (V4 Continuous Browser Video & V4.1 Polish) — Live Chromium compositor capture, virtual-time clock, minimum-jerk cursor, multi-framework adapters (Laravel, React/Vite, Generic Web), project profiles, declarative tutorial plans, isolated artifact provenance, and 6-Gate Continuous QA Suite (Gates 24–29).
---

# App Tutorial Video Skill (V4.1 — Reusable Cross-Project Tutorial Engine)

Skill agent otonom untuk menghasilkan video tutorial aplikasi profesional berkualitas tinggi di berbagai repository proyek web (Laravel, React/Vite, Vue, Generic Web SPA/MPA) menggunakan **V4 Continuous Browser Video Engine**.

---

## 1. Arsitektur 5 Komponen Modular

Sistem memisahkan tanggung jawab secara tegas antara engine rendering dan konfigurasi proyek:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        PROJECT PROFILE & ADAPTER                       │
│  - project-profile.json (Identity, Base URL, Auth, Viewport, Defaults) │
│  - adapters/ (Laravel, React+Vite, Generic Web Auto-Detection)         │
└────────────────────────────────────┬───────────────────────────────────┘
                                     │ configures
                                     ▼
┌────────────────────────────────────────────────────────────────────────┐
│                             TUTORIAL PLAN                              │
│  - tutorial-plan.json (Declarative Scenes, Narration, Real DOM Steps)  │
└────────────────────────────────────┬───────────────────────────────────┘
                                     │ executes
                                     ▼
┌────────────────────────────────────────────────────────────────────────┐
│                              CORE ENGINE                               │
│  - continuous/engine.cjs : Live Chromium virtual-time compositor (CFR) │
│  - continuous/overlay.js : Subtitles, minimum-jerk cursor, ripples     │
│  - continuous_pipeline.py: Pass A (TTS), B (Video), C (SFX), D (Mux)   │
│  - continuous_qa.py      : Gates 24-29 Verification Engine             │
└────────────────────────────────────┬───────────────────────────────────┘
                                     │ outputs
                                     ▼
┌────────────────────────────────────────────────────────────────────────┐
│                           ARTIFACT MANAGER                             │
│  - Isolated runs: .tutorial-video/projects/<id>/runs/<run_id>/         │
│  - Provenance manifests with SHA-256 hashes (Zero silent asset reuse)  │
└────────────────────────────────────────────────────────────────────────┘
```

### A. Core Engine (`scripts/continuous/`, `scripts/continuous_pipeline.py`)
- **Browser Capture**: Menangkap frame live browser langsung dari Chromium compositor via CDP `Page.captureScreenshot` pada virtual time ($vt = k \times 16.667\text{ms}$). Tidak ada screenshot slideshow atau interpolasi gambar diam.
- **Natural Motion**: Trajektori kursor dengan kurva minimum-jerk quintic, deceleration ease-out, dan micro-settle stabil ($\ge 180\text{ms}$) sebelum klik/ketukan.
- **In-Page Subtitle & Pointer (`overlay.js`)**: Subtitle teks dinamis tanpa truncation, ripple interaksi nyata, dan native `<select>` picker sheet.
- **Narration-First Timing**: Pass A menghasilkan audio TTS berbasis `edge-tts` dengan sample-accurate word boundaries sebelum perekaman video.
- **SFX Synthesis**: Efek klik mouse mekanis, ketukan layar sentuh, dan keystroke typing disintesis secara deterministik dari recorded events log.
- **Lossless Muxing**: Video stream disalin bit-exact (`-c:v copy`), audio AAC 192k 48kHz.

### B. Project Adapter (`scripts/adapters/`)
Mengenali struktur dan karakteristik aplikasi tanpa bergantung pada asumsi satu framework:
- **`LaravelAdapter`**: Membaca `composer.json`, `routes/web.php`, controller, dan template Blade.
- **`ReactViteAdapter`**: Membaca `package.json`, `vite.config.*`, `index.html`, dan komponen React/SPA di `src/`.
- **`GenericWebAdapter`**: Mendukung aplikasi web berbasis HTML/JS/CSS statis atau framework apa pun.
- **`AdapterRegistry`**: Melakukan deteksi otomatis berperingkat keyakinan (*confidence-based ranking*).

### C. Project Profile (`scripts/profile.py`, `project-profile.schema.json`)
Menyimpan konfigurasi unik proyek dalam `project-profile.json`:
- `project_id`: Identifier unik (slug).
- `project_name`: Nama tampilan aplikasi.
- `base_url`: URL lokal server (misal `http://127.0.0.1:8000` atau `http://localhost:5173`).
- `mode`: `"mobile"` (414x736 -> 1080x1920) atau `"desktop"` (1920x1080).
- `auth`: Aturan autentikasi (`none` atau `form_login` dengan pemetaan selector & env var `TUTORIAL_EMAIL`/`TUTORIAL_PASSWORD`).
- `workflows`: Daftar alur pengguna yang telah diverifikasi.

### D. Tutorial Plan (`tutorial-plan.json`)
Spesifikasi deklaratif yang mendefinisikan skenario video tutorial:
- `setup`: Langkah pre-record (navigasi, login, persiapan halaman awal).
- `scenes`: Daftar scene terstruktur:
  - `scene_id`: Slug unik adegan.
  - `narration`: Teks narasi suara yang dibacakan.
  - `steps`: Interaksi nyata DOM (`tap`, `type`, `select`, `scroll`, `wait_for`, `hold`, `pointer_hide`).
  - `result_hold_ms`: Durasi penahanan UI setelah aksi selesai.

### E. Artifact Manager (`scripts/artifact_manager.py`)
- Isolasi ketat: Disimpan per-proyek dan per-run di `.tutorial-video/projects/<project_id>/runs/<run_id>/`.
- Zero silent asset reuse: Dilarang menggunakan ulang video/audio lama.
- Provenance manifest: Menghasilkan `provenance-manifest.json` yang mencatat hash SHA-256 untuk video master, audio WAV, log event, file plan, dan profil proyek.

---

## 2. 10-Step New Project Bootstrap Workflow

Saat dijalankan pada repository atau proyek web baru, jalankan urutan berikut:

1. **Detect Framework & Entry Points**: Panggil `AdapterRegistry.detect(workspace_root)`.
2. **Detect Server & Local URL**: Periksa port aktif (8000, 5173, 4173, 3000, 80) dan catat URL akses lokal.
3. **Discover Pages & Views**: Petakan halaman publik dan komponen yang ada di repository.
4. **Determine Authentication Needs**: Evaluasi apakah halaman tutorial membutuhkan login akun pengujian.
5. **Map Verified Workflows**: Buat daftar skenario tutorial berdasarkan fitur nyata yang ditemukan.
6. **Generate Project Profile & Plan**: Tulis `project-profile.json` dan starter `tutorial-plan.json`.
7. **Preflight Selector Validation**: Jalankan preflight headless untuk memvalidasi selector DOM sebelum recording.
8. **Inspect Prototype Scene**: Evaluasi rancangan 1 scene awal (narasi, timing, interaksi).
9. **Record Continuous V4 Video**: Eksekusi Pass A, B, C, D melalui `continuous_pipeline.py`.
10. **Execute Quality Gates (QA)**: Jalankan QA Gates 24–29 dan laporkan bukti kelulusan.

---

## 3. Command Line Interface (CLI)

Gunakan `scripts/cli.py` untuk mengontrol seluruh tahapan:

### A. Bootstrap Proyek Baru
```bash
python scripts/cli.py bootstrap --workspace-root <path> [--base-url <url>] [--mode mobile|desktop] [--record]
```

### B. Preflight Selector Validation
```bash
python scripts/cli.py preflight --plan <path/to/tutorial-plan.json> [--workspace-root <path>]
```

### C. Record Video Tutorial (V4 Continuous)
```bash
python scripts/cli.py record --plan <path/to/tutorial-plan.json> [--profile <path/to/project-profile.json>] [--project-id <id>]
```

### D. Quality Assurance Verification
```bash
python scripts/cli.py qa --run-dir <path/to/run_dir>
```

---

## 4. Quality Assurance Specification (Gates 24–29)

| Gate | Kategori QA | Syarat Kelulusan (PASS Criteria) |
|---|---|---|
| **Gate 24** | **True Continuous Video** | 100% frame ditangkap dari live Chromium compositor (`cdp_live_capture_ratio = 1.0`). Nol slideshow/static image interpolation. |
| **Gate 25** | **Duplicate Frame Analysis** | Duplikasi frame aktif dibatasi ($\le 5\%$), tidak ada freeze atau stutter saat kursor bergerak atau pengetikan. |
| **Gate 26** | **Frame Pacing** | Constant Frame Rate tepat 60.0 FPS, durasi frame seragam ($16.667\text{ms} \pm 0.5\text{ms}$). |
| **Gate 27** | **Motion Continuity** | Minimum-jerk quintic trajectory, zero coordinate teleportation ($\max(\Delta P) \le 25\text{px}$/frame). |
| **Gate 28** | **Real Interaction Motion** | Event interaksi terdeteksi nyata: per-character typing, touch pulse ripples, scroll progression, dropdown commits. |
| **Gate 29** | **Human Perceptual Motion** | Contact sheet visual terekstrak di `perceptual-evidence/perceptual_contact_sheet.png` untuk verifikasi manusia. |
