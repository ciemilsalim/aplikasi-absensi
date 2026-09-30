# V2 Audit & Diagnostic Report — Professional Tutorial Generator

**Tanggal Audit**: 2026-09-28  
**Project Context**: SIASEK (Sistem Informasi & Absensi Sekolah)  
**Versi Skill Baseline**: V1.0.0  
**Target Upgrade**: V2.0.0 (Professional Tutorial Generator)

---

## 1. Kemampuan V1 (Baseline Capabilities)
- **Struktur & Discovery**: Mampu menganalisis file-file proyek (routes, controllers, views) dan menghasilkan `discovery.md` serta `storyboard.md`.
- **Tutorial Plan**: Menggunakan JSON schema dasar (`tutorial-plan.json`) untuk mendefinisikan langkah aksi visual dan teks narasi.
- **Perekaman Layar**: Menggunakan Playwright Chromium headless untuk menjalankan urutan URL/klik/fill dan menyimpan rekaman `.webm` / screenshot.
- **Voice-over**: Menggunakan `edge-tts` Python wrapper dengan suara `id-ID-ArdiNeural` untuk mengubah teks narasi menjadi MP3.
- **Subtitle**: Membangun subtitle SRT dari durasi estimasi scene dan melakukan burn-in subtitle via filter FFmpeg.
- **Rendering**: Menggabungkan video + audio + subtitle via FFmpeg / `imageio-ffmpeg` menjadi file MP4 H.264 / AAC.
- **Quality Assurance**: Memeriksa keberadaan file dan stream audio/video dasar via `validate.py`.

---

## 2. Dependency Audit
- **Node.js**: v22.22.0 (OK)
- **Playwright**: Installed (OK)
- **Python**: v3.12.0 (OK)
- **Edge-TTS**: Installed (`edge_tts`) (OK)
- **FFmpeg**: Bundled binary via `imageio-ffmpeg` (`ffmpeg-win-x86_64-v7.1.exe`) & system detection (OK)

---

## 3. Kelemahan Recording V1
- **Gerakan Kursor Robotik**: Kursor melompat secara instan ke elemen tanpa pergerakan manusiawi (*humanized cursor move*).
- **Indikator Klik Tidak Terlihat**: Pengguna tidak dapat melihat kapan dan di mana kursor melakukan aksi klik.
- **Pacing Terlalu Cepat**: Jedanya kaku (`wait: 1000ms`), tidak memberikan waktu yang cukup bagi audiens untuk membaca halaman.
- **Tanpa Konfigurasi Delay**: Tidak ada parameter terpusat untuk `actionDelay`, `sceneDelay`, `typingDelay`, dan `postActionDelay`.
- **Scene Jumping**: Perpindahan antar scene terjadi mendadak tanpa transisi atau penahanan (*hold frame*) pada aksi penting.

---

## 4. Kelemahan Narration V1
- **Bahasa Kurang Natural**: Naskah terdengar seperti membaca dokumentasi teknis atau langkah uji QA.
- **Istilah Developer/Internal**: Menampilkan route Laravel (`/teacher/journals/create`), nama ID database, atau parameter internal.
- **Pola Narasi Tidak Terstruktur**: Belum menerapkan standar **WHAT -> ACTION -> RESULT** per scene.
- **Tidak Beradaptasi pada Role User**: Teks tidak dapat menyesuaikan tone berdasarkan `target_user` (misal: Guru, Orang Tua, Kepala Sekolah).
- **Panjang Narasi Tidak Terkontrol**: Narasi terlalu panjang atau terlalu pendek dibanding target durasi scene.

---

## 5. Kelemahan Subtitle V1
- **Pembagian Matematis Kaku**: Subtitle dipotong berdasarkan estimasi karakter/waktu tanpa memperhatikan batas kalimat natural.
- **Resiko Timestamp & Overlap**: Tidak ada validasi kaku terhadap urutan timestamp (misal: waktu mulai > waktu selesai, atau baris berikutnya overlapping secara tidak wajar).
- **Teks Terlalu Panjang**: Kadang menghasilkan 3–4 baris subtitle sekaligus yang menutupi area visual utama.
- **Bukan Native Sentence Alignment**: Belum tersinkronisasi presisi dengan durasi audio narator aktual.

---

## 6. Kelemahan Rendering V1
- **Tanpa Intro & Outro**: Video langsung dimulai dari rekaman browser tanpa judul/titlecard professional, dan berakhir mendadak.
- **Tanpa Auto Highlight**: Tidak ada penyorotan visual (highlight/box overlay) pada tombol atau form input yang sedang dijelaskan.
- **Tanpa Auto Zoom**: Tidak ada efek pembesaran (zoom/crop) pada elemen visual penting untuk mengarahkan fokus penonton.
- **Tanpa Dukungan Background Music & Ducking**: Tidak mendukung BGM opsional dengan kontrol volume aman.
- **Tidak Sinkron dengan Target Durasi**: Video direkam apa adanya tanpa penyesuaian otomatis (*video timing fit / frame hold*) terhadap panjang audio.

---

## 7. Kelemahan Quality Gate V1
- **Aturan Durasi Tidak Terbaca/Tidak Ketat**: Prototype V1 meloloskan durasi **53.26 detik** padahal aturan target prototype adalah **60–90 detik**.
- **Tidak Ada Inspeksi Frame/Layar Hitam**: `validate.py` hanya mengecek apakah stream video ada, tanpa memastikan tidak ada frame hitam (*black screen*) atau *blank render*.
- **Validasi Subtitle Sangat Minis**: Tidak memeriksa integritas urutan SRT, baris kosong, atau overlap.
- **Gagal Memberikan Diagnosis**: Jika gagal, `validate.py` tidak memberikan arahan perbaikan otomatis yang jelas.

---

## 8. Rencana Tindakan Upgrade V2 (Professional Tutorial Generator)
1. **Phase 2**: Memperbaiki `validate.py` dengan Quality Gate ketat (60–90s mandatory, check black screen, frame rate, resolution, audio/video duration match, SRT validation).
2. **Phase 3**: Memperluas schema JSON (`tutorial-plan.schema.json`) untuk menyokong `target_user`, `mode`, `highlight`, `zoom`, `cursor_action`, `transition`, dll.
3. **Phase 4**: Upgrade `record.js` dengan smooth mouse movement, visual click ripple effect, typing delay, & post-action holds.
4. **Phase 5 & 6**: Implementasi Auto Highlight & Auto Zoom pada pipeline render Python via FFmpeg canvas / filter dynamic overlays.
5. **Phase 7 & 8**: Naskah narasi WHAT-ACTION-RESULT berdasar `target_user` & generator SRT berbasis kalimat natural & sync audio.
6. **Phase 9 & 10**: Engine generator Titlecard Intro/Outro (3–5s) & optional volume-controlled background music.
7. **Phase 11 & 12**: Multi-stage FFmpeg render engine & dynamic duration solver (pad/hold frame jika video lebih cepat dari audio).
8. **Phase 13, 14, 15**: Multi-role target user & mode support (`feature`, `module`, `onboarding`, `release`), serta restrukturisasi folder `.tutorial-video/`.
9. **Phase 16 & 17**: Laporan `qa-v2.md` & eksekusi Acceptance Test nyata untuk Pengisian Jurnal Mengajar Guru (Target 60–90s).
