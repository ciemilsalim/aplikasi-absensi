# App Tutorial Video — Antigravity Agent Skill

Skill untuk membuat video tutorial aplikasi secara end-to-end: inspeksi aplikasi → tutorial plan → verifikasi alur → browser recording → voice-over → subtitle → render MP4 → QA.

## Instalasi workspace

Salin folder `app-tutorial-video` ke:

```text
<project-root>/.agents/skills/app-tutorial-video/
```

Antigravity saat ini menggunakan `.agents/skills` sebagai lokasi default workspace skill. Global skill dapat diletakkan di `~/.gemini/config/skills/`. citeturn833484search0

## Dependensi untuk pipeline video

Untuk recording berbasis Playwright:

```powershell
npm install -D playwright
npx playwright install chromium
```

Untuk voice-over contoh:

```powershell
python -m pip install edge-tts
```

Untuk render:

```powershell
ffmpeg -version
ffprobe -version
```

FFmpeg harus tersedia di PATH.

## Cara menggunakan di Antigravity

Setelah Skill terbaca, panggil:

```text
/app-tutorial-video
```

atau:

```text
/app-tutorial-video feature absensi guru
```

Lalu biarkan agent menginspeksi dan memverifikasi aplikasi. Jangan membuat tutorial dari screenshot/code yang belum diverifikasi.

## Tahap V1

V1 sengaja menggunakan arsitektur modular. `SKILL.md` adalah instruksi utama; `record.js`, `tts.py`, `render.py`, dan `validate.py` adalah helper yang dipanggil sebagai black box. Pola ini sesuai rekomendasi Skill Antigravity untuk menggunakan scripts sebagai helper dan menjaga instruksi utama tetap fokus. citeturn833484search0

## Upgrade V2 yang direncanakan

- auto-highlight klik/field
- scene stitching otomatis dari banyak WebM
- intro/outro template
- brand logo
- noise reduction
- auto chapter markers
- thumbnail generation
- voice provider adapters
- automatic detection of changed features between releases
