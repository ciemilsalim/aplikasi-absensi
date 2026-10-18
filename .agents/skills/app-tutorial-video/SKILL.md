---
name: app-tutorial-video
description: Professional Reusable Cross-Project Video Tutorial Engine (V4 Continuous Browser Video, V4.4 Upbeat Jazz Adaptive BGM & Dual Output) — Live Chromium compositor capture, virtual-time clock, minimum-jerk cursor, adaptive ducked background music, multi-framework adapters (Laravel, React/Vite, Generic Web), project profiles, declarative tutorial plans, isolated artifact provenance, and 8-Gate Continuous QA Suite (Gates 24–31).
---

# App Tutorial Video Skill (V4.4 — Production Standardization)

Skill agent otonom untuk menghasilkan video tutorial aplikasi profesional berkualitas tinggi di berbagai repository proyek web (Laravel, React/Vite, Vue, Generic Web SPA/MPA) menggunakan **V4 Continuous Browser Video Engine**.

**Golden Reference Produksi:** `.tutorial-video/projects/siasek/runs/run_20261018_074048_9215f3/`

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
│  - continuous_qa.py      : Gates 24-31 & Production Checklist (A–F)   │
└────────────────────────────────────┬───────────────────────────────────┘
                                     │ outputs
                                     ▼
┌────────────────────────────────────────────────────────────────────────┐
│                           ARTIFACT MANAGER                             │
│  - Isolated runs: .tutorial-video/projects/<id>/runs/<run_id>/         │
│  - Provenance manifests with SHA-256 hashes (Zero silent asset reuse)  │
│  - Data safety & isolation verified (Zero business data mutation)      │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Default Produksi V4.4 (Universal Baseline)

Preset V4.4 menjadi standar default produksi terintegrasi:

| Parameter | Konfigurasi Default V4.4 | Keterangan |
|---|---|---|
| **Engine** | `TRUE_CONTINUOUS_BROWSER_VIDEO_V4` | Live capture Chromium compositor via CDP virtual-time. |
| **Output Mode** | `dual` (Desktop & Mobile) | Menghasilkan video terpisah untuk masing-masing viewport. |
| **Desktop Format** | `1920x1080` (16:9, Landscape) | 60.0 FPS Constant Frame Rate (CFR), libx264 high profile. |
| **Mobile Format** | `1080x1920` (9:16, Portrait) | 60.0 FPS CFR (viewport 414×736 scaled to 1080×1920). |
| **BGM Genre** | `upbeat_jazz` | Rhodes Soul Jazz Trio (Rhodes piano, electric bass, swung drums). |
| **Aset Musik** | `upbeat-jazz-v1.wav` | SHA-256: `cab3b56caf103af4dd402d50265c0bc9ccc2c15bab76be5e7ff4f2d74396abaa` |
| **Tempo BGM** | `80 BPM` | Relaxed soul pocket groove (zero saxophone). |
| **Audio Ducking** | Base: `0.16`, Ducked: `0.045` | Attack: `200ms`, Release: `500ms`, Voice advantage: $\ge +24\text{ dB}$. |
| **Looping & Fade** | Fade In: `1200ms`, Fade Out: `2500ms` | Crossfade seamless 24-bar loop (72.0s). |
| **Data Safety** | `TRACKED_EPHEMERAL_TEST_DATA` | Dilarang keras memodifikasi/menghapus data bisnis nyata. |

### Aturan Prioritas Konfigurasi (Precedence Hierarchy)
Jika terjadi perbedaan nilai konfigurasi, engine menerapkan prioritas berjenjang berikut (Level 1 tertinggi):
1. **Level 1 — Parameter CLI** (`--mode`, `--bgm`, `--bgm-genre`, `--base-volume`, `--ducking-volume`)
2. **Level 2 — Properti Tutorial Plan** (`mode`, `music: { ... }`, `data_safety: { ... }`)
3. **Level 3 — Profil Proyek** (`project-profile.json`)
4. **Level 4 — Default Produksi V4.4** (Preset standar di `audio_mixer.py` & `continuous_pipeline.py`)

> [!IMPORTANT]
> **Cryptographic Asset Integrity:** Jika file musik `upbeat-jazz-v1.wav` tidak ditemukan atau SHA-256 hash-nya tidak cocok dengan `cab3b56caf103af4dd402d50265c0bc9ccc2c15bab76be5e7ff4f2d74396abaa`, proses produksi **segera dihentikan dengan pesan error fatal** (`ValueError`). Engine tidak akan pernah mengganti musik secara diam-diam.

---

## 3. Dukungan Universal Lintas Aplikasi

Skill ini dirancang **agnostik terhadap aplikasi sumber**:
- **Tidak ada selector khusus aplikasi** (seperti SIASEK) yang di-hardcode ke dalam engine global.
- Setiap aplikasi mendefinisikan alur, URL, selector, dan interaksinya sendiri di `tutorial-plan.json`.
- Seluruh artefak disimpan terisolasi di direktori proyek: `.tutorial-video/projects/<project_id>/runs/<run_id>/`.

---

## 4. Command Line Interface (CLI)

Gunakan `scripts/cli.py` untuk mengontrol seluruh tahapan:

### A. Bootstrap Proyek Baru
```bash
python scripts/cli.py bootstrap --workspace-root <path> [--base-url <url>] [--mode dual|mobile|desktop] [--record]
```

### B. Preflight Selector Validation
```bash
python scripts/cli.py preflight --plan <path/to/tutorial-plan.json> [--workspace-root <path>]
```

### C. Record Video Tutorial (V4 Continuous)
```bash
# Default mode: Dual Output (Desktop 1920x1080 + Mobile 1080x1920) dengan Upbeat Jazz
python scripts/cli.py record --plan <path/to/tutorial-plan.json>

# Override mode: Desktop Saja
python scripts/cli.py record --plan <path/to/tutorial-plan.json> --mode desktop

# Override mode: Mobile Saja
python scripts/cli.py record --plan <path/to/tutorial-plan.json> --mode mobile

# Menggunakan BGM khusus
python scripts/cli.py record --plan <path/to/tutorial-plan.json> --bgm <path/to/custom.wav>
```

### D. Quality Assurance Verification (Gates 24–31 & Checklist A–F)
```bash
python scripts/cli.py qa --run-dir <path/to/run_dir>
```

### E. Fast Audio Preview (Tanpa Browser Rendering)
```bash
# Preview Musik Jazz Mandiri (Standalone 35 detik)
python scripts/preview_bgm.py --genre upbeat_jazz --duration 35 --out <path/to/preview.wav>

# Preview Narasi + Ducking Musik Jazz
python scripts/preview_bgm.py --narration <path/to/scene.mp3> --genre upbeat_jazz --out <path/to/preview.wav>
```

### F. Ardi Expression Lab & Evaluasi Suara (V4.5)
```bash
# Evaluasi Baseline Ardi vs Gadis (+0% dan -2%)
python scripts/evaluate_voices.py

# Ardi Expression Lab (4 Profil Pedagogis: Warm, Conversational, Expressive, Baseline + Gadis Ref)
python scripts/evaluate_ardi_lab.py

# Expressive Narration Lab (Profile D, E, F, G + Stitched Comparisons)
python scripts/evaluate_expressive_lab.py
```

---

## 5. V4.5 Approved Narration Baseline (Profile F: Warm Conversational)

Berdasarkan konfirmasi eksplisit pengguna (*Explicit Human Approval: "Setuju — Profile F"*), tata kelola narasi V4.5 resmi mencatat spesifikasi berikut:

### A. Konfigurasi TTS Resmi
- **Voice ID**: `id-ID-ArdiNeural` (Microsoft Ardi Online Natural - Indonesian)
- **Parameter Suara**: `rate: "+0%"`, `pitch: "+0Hz"` (pacing native neural transformer).
- **Gaya Narasi Pemenang**: **Profile F (Warm Conversational)** — Gaya instruktur yang membimbing secara hangat, santun, dan inklusif.

### B. Aturan Penulisan Naskah Tutorial
1. **Ketentuan Wajib Branding Pembukaan**: Setiap pembukaan tutorial pada scene pertama **wajib memperkenalkan nama aplikasi secara eksplisit sebagai "SIASEK Presensi"** (contoh terverifikasi: *"Halo Bapak dan Ibu Guru Wali Kelas, selamat datang di panduan aplikasi SIASEK Presensi. Hari ini kita bersama-sama akan melihat..."*).
2. **Kaidah Frasa Pemandu Natural**: Menggunakan frasa pembimbing yang ramah (*"kita bersama-sama..."*, *"Bagus, datanya sudah lengkap"*, *"Silakan pilih..."*) tanpa menggunakan kata pengisi informal yang canggung (*tanpa kata "Nah" atau "Hmm"*).
3. **Struktur Klausa Berimbang**: Membatasi kalimat menjadi klausa-klausa pendek (5–8 kata per klausa) yang dipisahkan koma ritmis untuk menghindari intonasi datar (*run-on reading*).

### C. Kompatibilitas & Batasan Klaim
1. **Dukungan Backward Compatibility**: Tutorial plan warisan V4.4 yang menggunakan format string tunggal `narration` tetap didukung penuh 100%. Dukungan opsional `narration_segments` tetap mempertahankan kontrak timing V4.4, aturan narasi-sebelum-aksi, serta sinkronisasi subtitle dan event.
2. **Batasan Klaim Perseptual**: Persetujuan pengguna berlaku spesifik untuk Profile F yang telah dievaluasi dan didengarkan langsung. Sistem tidak mengklaim kesempurnaan intonasi manusia seutuhnya untuk setiap kemungkinan variasi naskah di masa depan.
3. **Ruang Lingkup Izin**: Persetujuan tata kelola ini mengizinkan penggunaan standar Profile F pada penyusunan rencana tutorial produksi berikutnya, namun **tidak mengizinkan render ulang video produksi atau mutasi aplikasi secara otomatis pada tahap ini**.

---

## 6. Automated Production Checklist (Sections A–F) & Gates 24–31

Setiap eksekusi QA memvalidasi 6 seksi checklist produksi otomatis dan 8 Quality Gates:

### Checklist Produksi Otomatis:
- **Section A — Input & Provenance**: Validitas skema tutorial plan, verifikasi SHA-256 aset BGM, keunikan Run ID, kelengkapan manifest.
- **Section B — Video Quality**: Ketersediaan dual output saat diminta, resolusi target (Desktop 1920×1080 landscape, Mobile 1080×1920 portrait), verifikasi bahwa desktop dan mobile bukan hasil kloning melainkan rekaman independen pada viewport masing-masing, 60.0 FPS CFR.
- **Section C — Audio Engineering**: Kehadiran narasi, SFX, dan BGM; keterdengaran BGM saat jeda ($\ge -42\text{ dBFS}$); efektivitas ducking (atenuasi $\ge 3\text{ dB}$, voice advantage $\ge 8\text{ dB}$); proteksi clipping ($< 0.999$); verifikasi langsung stream AAC pada kedua container MP4 final; pemisahan eksplisit `automated_qa` dan `human_listening: NOT_VERIFIED`.
- **Section D — Synchronization & Subtitles**: Sinkronisasi video container sebagai master clock ($\Delta \le 50\text{ms}$), keselarasan subtitle dan batas kata narasi, pengukuran dan penjelasan selisih durasi responsif desktop/mobile.
- **Section E — Data Safety & Isolation**: Pencegahan mutasi data bisnis nyata, penolakan query destruktif (`DROP`, `TRUNCATE`), pembatasan cleanup hanya pada data yang dibuat oleh run pengujian.
- **Section F — Final Deliverables**: Aksesibilitas file MP4 final, validitas ukuran file, dan integritas path artefak.

### Quality Gates 24–31:
| Gate | Kategori QA | Syarat Kelulusan (PASS Criteria) |
|---|---|---|
| **Gate 24** | **True Continuous Video** | 100% frame dari live Chromium compositor (`cdp_live_capture_ratio = 1.0`). |
| **Gate 25** | **Duplicate Frame Analysis** | Duplikasi frame aktif $\le 5\%$, tidak ada freeze saat pergerakan kursor. |
| **Gate 26** | **Frame Pacing** | CFR tepat 60.0 FPS, durasi frame seragam ($16.667\text{ms} \pm 0.5\text{ms}$). |
| **Gate 27** | **Motion Continuity** | Minimum-jerk quintic trajectory, zero coordinate teleportation. |
| **Gate 28** | **Real Interaction Motion** | Event interaksi terdeteksi nyata: per-character typing, ripple tap, scroll progression. |
| **Gate 29** | **Human Perceptual Motion** | Contact sheet visual terekstrak di `perceptual-evidence/perceptual_contact_sheet.png`. |
| **Gate 30** | **Audio Integrity & Clarity** | Master audio 48kHz, desync $\le 50\text{ms}$, zero clipping (peak $< -0.01\text{ dBFS}$), ducking terverifikasi, segmentasi kata monoton, pemisahan automated QA vs human listening. |
| **Gate 31** | **Dual Output Deliverables** | Memastikan ketersediaan lengkap Desktop 1920x1080 dan Mobile 1080x1920 dengan stream audio AAC valid. |

---

## 6. Protokol Keamanan Data & Isolasi Database

1. **Larangan Mutasi Data Produksi:** Dilarang keras menghapus, memperbarui, atau memasukkan data ke tabel presensi atau data bisnis nyata aplikasi selama pengujian tutorial.
2. **Mekanisme Sandbox & Fixture:** Seluruh skenario tutorial yang membutuhkan interaksi penulisan data wajib menggunakan akun pengujian atau data uji efemeral yang dilacak secara eksplisit.
3. **Aturan Cleanup:** Cleanup rutin pasca-rekaman hanya boleh menghapus record yang dibuat oleh run itu sendiri (menggunakan ID yang terlacak).
4. **Verifikasi Keamanan:** Jika skenario terdeteksi memuat perintah destruktif database (`DROP`, `TRUNCATE`), checklist Section E akan langsung menetapkan status `FAIL` / `BLOCKED`.

---

## 7. Prosedur Menjalankan Pengujian (Testing Guide)

Jalankan seluruh rangkaian tes otomatis menggunakan pytest:
```bash
python -m pytest .agents/skills/app-tutorial-video/tests/ -v
```

Rangkaian tes mencakup:
- `test_production_standardization.py`: Validasi preset default V4.4, override CLI, integritas SHA-256 aset musik, proteksi kloning video, dan keamanan data.
- `test_adaptive_bgm.py`: Pengujian algoritma audio mixing, kurva ducking, looping, stereo decoding, dan gate audio.
- `test_dual_output.py`: Pengujian pelacakan artefak dual output desktop dan mobile.
- `test_narration_humanized.py`: Pengujian validasi segmen narasi V4.5, fallback single string V4.4, sintesis segmented WAV 48kHz, monotonisitas word boundaries, hash BGM, zero database mutation, serta verifikasi kelengkapan Ardi Expression Lab.

> [!NOTE]
> **Batasan Verifikasi Otomatis (Taxonomy Boundary):** Evaluasi kualitas suara narasi manusia, kehangatan intonasi, dan keselarasan ekspresi dicatat dengan status `NOT_VERIFIED (AWAITING_HUMAN_LISTENING)` hingga didengarkan dan dikonfirmasi langsung oleh pengguna manusia. Metrik telemetri audio (RMS, peak dBFS, zero clipping, kelengkapan format audio) diverifikasi secara matematis dengan status `PASS`.


