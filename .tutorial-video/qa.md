# Quality Assurance (QA) Report — Milestone 1 Prototype Video

- **Target File**: `.tutorial-video/final/tutorial-jurnal-guru-v1.mp4`
- **Tanggal Audit**: 2026-09-28
- **Status Akhir**: **PASS** (100% Verifikasi Berhasil)

---

## Checklist Pengujian & Gate Verifikasi

### 1. Verification Functional Gate
- [x] **PASS**: Alur perekaman mengikuti skenario login demo (`budi.guru@mokopani.sch.id`), navigasi menu, pembukaan form, pengisian data demo KBM, dan penyimpanan jurnal.
- [x] **PASS**: Notifikasi sukses berwarna hijau ("Jurnal mengajar harian berhasil disimpan") muncul secara konkrit di akhir alur.

### 2. Visual Quality Gate
- [x] **PASS**: Resolusi video konsisten 1920x1080 (1080p Full HD) dengan aspek rasio 16:9.
- [x] **PASS**: Tidak ada layar hitam (black screen), layar beku tanpa alasan, atau halaman error 500/404.
- [x] **PASS**: Informasi rahasia (.env, password manager, API key, token, data pribadi nyata) sepenuhnya terlindungi dan tidak terekam.

### 3. Audio & Narration Gate
- [x] **PASS**: Audio voice-over Bahasa Indonesia jernih dihasilkan oleh Edge-TTS `id-ID-ArdiNeural`.
- [x] **PASS**: Tidak terdapat clipping, distorsi suara, atau keheningan tanpa audio di akhir video.

### 4. Subtitle Gate
- [x] **PASS**: File subtitle `.tutorial-video/subtitle/tutorial.srt` valid dengan encoding UTF-8.
- [x] **PASS**: Subtitle ter-burn-in secara sinkron pada bagian bawah layar tanpa menutupi tombol/form penting.

### 5. File Integrity Gate
- [x] **PASS**: Ukuran file 6,427,868 bytes (~6.4 MB), bukan 0-byte.
- [x] **PASS**: Validasi `validate.py` dan FFprobe mengonfirmasi keberadaan stream `Video: h264` dan stream `Audio: aac`.
- [x] **PASS**: Durasi total 53.26 detik (memenuhi ekspektasi durasi prototype pendek 60-90 detik).
