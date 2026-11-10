---
name: app-tutorial-video
description: Professional Reusable Cross-Project Video Tutorial Engine (V4 Continuous Browser Video, V4.4 Upbeat Jazz Adaptive BGM & Dual Output, V4.6 YouTube Publisher & Upload Kit) — Live Chromium compositor capture, virtual-time clock, minimum-jerk cursor, adaptive ducked background music, multi-framework adapters (Laravel, React/Vite, Generic Web), project profiles, declarative tutorial plans, isolated artifact provenance, 8-Gate Continuous QA Suite (Gates 24–31), and automated YouTube Publishing (SEO titles, chapters/timestamps, targeted tags, YouTube Shorts auto-detection, and YouTube Data API v3 integration).
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
| **BGM Genre** | `upbeat_jazz` | Rhodes Soul Jazz Trio (Rhodes, upright bass, brushed drums, 80 BPM). |
| **BGM Asset** | `resources/audio/music/upbeat-jazz-v1.wav` | SHA-256: `cab3b56caf103af4dd402d50265c0bc9ccc2c15bab76be5e7ff4f2d74396abaa`. |
| **Base Volume** | `0.18` ($\approx -14\text{ LUFS}$) | Level musik saat jeda hening / tanpa narasi. |
| **Ducking Volume**| `0.05` | Level musik saat narasi aktif (atenuasi $\ge 3\text{ dB}$, voice adv $\ge 8\text{ dB}$). |
| **Fade In / Out** | `2.0` detik | Kurva linear fade-in di awal dan fade-out di akhir video. |
| **Data Safety** | `TRACKED_EPHEMERAL_TEST_DATA` | Dilarang mutasi data produksi nyata; cleanup terbatas ID tes. |

---

## 3. Struktur Direktori Lintas Proyek

Pada setiap repositori proyek target, seluruh konfigurasi dan artefak disimpan di dalam folder `.tutorial-video/` yang terisolasi:

```
<project-root>/
├── .tutorial-video/
│   └── projects/
│       └── <project_id>/
│           ├── project-profile.json       # Identitas proyek, base URL, preferensi viewport
│           ├── tutorial-plan.json         # Rencana adegan & aksi tutorial deklaratif
│           └── runs/
│               └── <run_id>/              # Direktori run terisolasi per sesi perekaman
│                   ├── effective-plan.json
│                   ├── narration.json
│                   ├── audio/
│                   ├── recording/
│                   ├── final/             # Video MP4 (Desktop & Mobile 60 FPS)
│                   ├── perceptual-evidence/
│                   ├── youtube/           # YouTube Metadata, Timestamps, Upload Kit & Thumbnail
│                   ├── qa_v4_report.json
│                   └── provenance-manifest.json
```

---

## 4. Panduan Perintah CLI (Unified CLI)

CLI terpadu dijalankan melalui `python .agents/skills/app-tutorial-video/scripts/cli.py` (atau dari folder skill global):

### A. Bootstrap Proyek Baru
Membuat profil proyek dan rencana tutorial awal secara otomatis melalui inspeksi framework (Laravel, React+Vite, Generic Web):
```bash
python .agents/skills/app-tutorial-video/scripts/cli.py bootstrap --workspace-root "." --mode dual
```

### B. Preflight Selector Validation
Memvalidasi keterjangkauan route dan keberadaan seluruh selektor CSS / DOM sebelum proses rekaman:
```bash
python .agents/skills/app-tutorial-video/scripts/cli.py preflight --plan ".tutorial-video/projects/<id>/tutorial-plan.json"
```

### C. Perekaman Video (V4 Continuous Engine)
Merekam video continuous, melakukan synthesis TTS, render live browser, audio ducking, muxing CFR 60 FPS, menjalankan QA otomatis, dan menyusun YouTube Upload Kit:
```bash
# Mode Dual (Desktop 1920x1080 & Mobile 1080x1920)
python .agents/skills/app-tutorial-video/scripts/cli.py record --plan ".tutorial-video/projects/<id>/tutorial-plan.json" --mode dual

# Mode Khusus Mobile (1080x1920 Portrait)
python .agents/skills/app-tutorial-video/scripts/cli.py record --plan ".tutorial-video/projects/<id>/tutorial-plan.json" --mode mobile

# Mode Khusus Desktop (1920x1080 Landscape)
python .agents/skills/app-tutorial-video/scripts/cli.py record --plan ".tutorial-video/projects/<id>/tutorial-plan.json" --mode desktop
```

### D. Audio Preview Cepat (Tanpa Render Video)
Menghasilkan pratinjau audio mixing dan ducking dalam hitungan detik untuk validasi akustik:
```bash
python .agents/skills/app-tutorial-video/scripts/cli.py preview-audio --genre upbeat_jazz --out "preview.wav"
```

---

## 5. V4.5 Approved Narration Baseline (Profile F: Warm Conversational)

Berdasarkan konfirmasi eksplisit pengguna (*Explicit Human Approval: "Setuju — Profile F"*), tata kelola narasi V4.5 resmi mencatat spesifikasi berikut:

### A. Konfigurasi TTS Resmi
- **Voice ID**: `id-ID-ArdiNeural` (Microsoft Ardi Online Natural - Indonesian)
- **Parameter Suara**: `rate: "+0%"`, `pitch: "+0Hz"` (pacing native neural transformer).
- **Gaya Narasi Pemenang**: **Profile F (Warm Conversational)** — Gaya instruktur yang membimbing secara hangat, santun, dan inklusif.

### B. Aturan Penulisan Naskah Tutorial (Adaptif Lintas Proyek)
1. **Ketentuan Wajib Branding Pembukaan Dinamis**: Setiap pembukaan tutorial pada scene pertama **wajib memperkenalkan nama resmi aplikasi secara eksplisit sesuai `project_name` pada `project-profile.json` / `APP_NAME`**:
   - **Proyek SIASEK (`aplikasi-absensi`)**: Wajib menyebut **"SIASEK Presensi"** (contoh: *"Halo Bapak dan Ibu Guru Wali Kelas, selamat datang di panduan aplikasi SIASEK Presensi. Hari ini kita bersama-sama akan melihat..."*).
   - **Proyek Apotek (`apotek`)**: Wajib menyebut **"Apotek Sehat"** (contoh: *"Halo Rekan-Rekan Staf Farmasi dan Kasir, selamat datang di panduan aplikasi Apotek Sehat. Hari ini kita bersama-sama akan mempelajari..."*).
   - **Proyek Lainnya**: Wajib menyebut **`<project_name>`** sesuai identitas aplikasi target dan menyapa audiens sesuai peran pengguna (`target_users`).
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

## 7. Protokol Keamanan Data & Isolasi Database

1. **Larangan Mutasi Data Produksi:** Dilarang keras menghapus, memperbarui, atau memasukkan data ke tabel presensi atau data bisnis nyata aplikasi selama pengujian tutorial.
2. **Mekanisme Sandbox & Fixture:** Seluruh skenario tutorial yang membutuhkan interaksi penulisan data wajib menggunakan akun pengujian atau data uji efemeral yang dilacak secara eksplisit.
3. **Aturan Cleanup:** Cleanup rutin pasca-rekaman hanya boleh menghapus record yang dibuat oleh run itu sendiri (menggunakan ID yang terlacak).
4. **Verifikasi Keamanan:** Jika skenario terdeteksi memuat perintah destruktif database (`DROP`, `TRUNCATE`), checklist Section E akan langsung menetapkan status `FAIL` / `BLOCKED`.

---

## 8. YouTube Publishing & Dynamic Metadata Integration (V4.6)

Skill secara otomatis menghasilkan paket publikasi YouTube lengkap yang disesuaikan secara dinamis dengan konten video tutorial yang baru saja direkam:

### A. Fitur Metadata Otomatis & Terstruktur
1. **Judul Video SEO Optimal (Title)**:
   - Menghasilkan judul informatif dan menarik berdasarkan `title` rencana tutorial dan nama proyek.
   - **Deteksi Cerdas YouTube Shorts**: Jika video berformat vertikal `9:16` (mobile) dan durasi $\le 180\text{ detik}$, judul otomatis ditambahkan tag `#Shorts` dan panjang karakter dijaga $\le 100\text{ karakter}$.
   - Untuk format landscape `16:9`, judul diberi awalan `Tutorial: ` atau format resmi yang mudah dicari.
2. **Daftar Isi & Timestamps Presisi (YouTube Chapters)**:
   - Menghitung waktu awal tiap adegan secara matematis dari rekaman event browser/narration.
   - Wajib diawali dari `00:00` agar YouTube secara otomatis membuat chapter interaktif pada video player.
3. **Ringkasan Langkah & Deskripsi Menyeluruh (Description)**:
   - Paragraf pembuka yang ramah dan menarik.
   - Poin-poin langkah tutorial yang disintesis dari isi narasi aktual tiap scene.
   - Fitur utama aplikasi, link akses, dan ajakan interaksi (CTA).
   - Kumpulan hashtag relevan (`#SIASEK #PresensiSekolah #TutorialAplikasi #Shorts`).
   - Panjang deskripsi dijaga dalam batas aman YouTube ($\le 5.000\text{ karakter}$).
4. **Tag & Kata Kunci Relevan (Tags)**:
   - Ekstraksi kata kunci kontekstual dari proyek, fitur, peran pengguna, dan judul adegan.
   - Panjang total tag dibatasi $\le 500\text{ karakter}$.
5. **Ekstraksi Thumbnail Siap Upload**:
   - Memilih frame kunci terbaik dari `perceptual-evidence` atau contact sheet dan mengekspornya ke `<run_dir>/youtube/thumbnail.png`.
6. **Kategori & Pengaturan Default**:
   - Kategori default `27` (Pendidikan / Education) atau `28` (Sains & Teknologi).
   - Visibilitas default: `private` (aman untuk proses review akhir sebelum dipublikasikan).
   - `selfDeclaredMadeForKids: false`.

### B. Artefak Paket Publikasi YouTube
Pada setiap proses perekaman tutorial, folder `<run_dir>/youtube/` otomatis memuat:
- `youtube-metadata.json`: Payload resmi yang siap dikirim langsung ke endpoint Google YouTube Data API v3 (`videos.insert`).
- `youtube-upload-kit.md`: Dokumentasi rapi siap salin (*copy-paste*) yang memuat Judul, Deskripsi lengkap dengan Timestamps, Tag, dan instruksi upload.
- `thumbnail.png`: File thumbnail resolusi tinggi yang siap diunggah ke YouTube Studio.

### C. Panduan CLI YouTube
```powershell
# 1. Menghasilkan atau memperbarui paket metadata YouTube dari run yang ada:
python .agents/skills/app-tutorial-video/scripts/cli.py youtube-pack --run-dir "<run_dir>"

# 2. Login OAuth Google YouTube (hanya sekali, token disimpan di .credentials/youtube_token.json):
python .agents/skills/app-tutorial-video/scripts/cli.py youtube-auth --client-secrets .credentials/client_secret.json

# 3. Upload video langsung ke channel YouTube resmi (wajib flag --confirm-upload demi keamanan):
python .agents/skills/app-tutorial-video/scripts/cli.py youtube-upload --run-dir "<run_dir>" --privacy private --confirm-upload
```

---

## 9. Prosedur Menjalankan Pengujian (Testing Guide)

Jalankan seluruh rangkaian tes otomatis menggunakan pytest:
```bash
python -m pytest .agents/skills/app-tutorial-video/tests/ -v
```

Rangkaian tes mencakup:
- `test_production_standardization.py`: Validasi preset default V4.4, override CLI, integritas SHA-256 aset musik, proteksi kloning video, dan keamanan data.
- `test_adaptive_bgm.py`: Pengujian algoritma audio mixing, kurva ducking, looping, stereo decoding, dan gate audio.
- `test_dual_output.py`: Pengujian pelacakan artefak dual output desktop dan mobile.
- `test_narration_humanized.py`: Pengujian validasi segmen narasi V4.5, fallback single string V4.4, sintesis segmented WAV 48kHz, monotonisitas word boundaries, hash BGM, zero database mutation, serta verifikasi kelengkapan Ardi Expression Lab.
- `test_cross_project_support.py`: Pengujian multi-framework adapters (Laravel, React+Vite, Generic Web) dan resolusi dynamic workspace root.
- `test_youtube_integration.py`: Pengujian otomatis metadata YouTube, generator timestamps/chapters, deteksi YouTube Shorts, integritas skema JSON YouTube Data API v3, dan gerbang konfirmasi upload.

> [!NOTE]
> **Batasan Verifikasi Otomatis (Taxonomy Boundary):** Evaluasi kualitas suara narasi manusia, kehangatan intonasi, dan keselarasan ekspresi dicatat dengan status `NOT_VERIFIED (AWAITING_HUMAN_LISTENING)` hingga didengarkan dan dikonfirmasi langsung oleh pengguna manusia. Metrik telemetri audio (RMS, peak dBFS, zero clipping, kelengkapan format audio) diverifikasi secara matematis dengan status `PASS`.
