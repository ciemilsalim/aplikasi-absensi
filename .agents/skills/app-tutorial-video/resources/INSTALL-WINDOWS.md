# Instalasi Windows

## 1. Letakkan Skill

Di root project Antigravity:

```powershell
mkdir .agents\skills -Force
```

Salin folder `app-tutorial-video` ke `.agents\skills\`.

## 2. Cek Node.js

```powershell
node --version
npm --version
```

## 3. Siapkan Playwright

```powershell
npm install -D playwright
npx playwright install chromium
```

## 4. Siapkan TTS

```powershell
python --version
python -m pip install edge-tts
```

## 5. Siapkan FFmpeg

Pastikan:

```powershell
ffmpeg -version
ffprobe -version
```

keduanya berhasil.

## 6. Uji helper

Dari root project:

```powershell
node .agents\skills\app-tutorial-video\scripts\record.js --help
python .agents\skills\app-tutorial-video\scripts\tts.py --help
python .agents\skills\app-tutorial-video\scripts\render.py --help
python .agents\skills\app-tutorial-video\scripts\validate.py --help
```

## Catatan keamanan

Jangan simpan password/API key di `tutorial-plan.json`. Gunakan environment variable ketika diperlukan.
