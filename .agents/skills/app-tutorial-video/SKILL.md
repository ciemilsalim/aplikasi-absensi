---
name: app-tutorial-video
description: Narration-First Deterministic Composer (V3.6) — 2-Pass Production (Pass A: Narration TTS Measurement -> Pass B: Video Generation & Deterministic Composition). Hard Invariant: action_start >= narration_end + 200ms. 100% Laptop Independence. 16-Gate QA Suite.
---

# App Tutorial Video Skill (V3.6 — Narration-First Deterministic Composer)

## Purpose & Core Rule

Mulai versi **V3.6**, tutorial bekerja seperti manusia yang sedang mengajar:

```
"NARASI MEMBERI INSTRUKSI"
        ↓
"NARASI SELESAI"
        ↓
"JEDA REAKSI (200–400ms)"
        ↓
"VIDEO MELAKUKAN INSTRUKSI"
```

1. **Remove Real-Time Audio/Video Dependency**:
   - Tidak lagi bergantung pada wall clock, `Date.now()`, waktu sleep browser, waktu render FFmpeg, atau kecepatan proses komputer.
   - Laptop lambat atau cepat **TIDAK MENGUBAH** timing hasil akhir (0.00ms variance — Laptop Independence Invariant).
   - Seluruh timing bersifat *purely deterministic*.

2. **Two-Pass Production**:
   - **PASS A (Narration Master)**:
     - Generate TTS terlebih dahulu untuk setiap instruksi.
     - Ukur durasi aktual dengan `ffprobe` / PCM decoder.
     - Simpan `narration-master.json`.
   - **PASS B (Deterministic Video Composition)**:
     - Hitung jadwal deterministik: $\text{action\_start} = \text{narration\_end} + \text{reaction\_delay}$ (default 300ms, min 200ms).
     - Visual dibagi menjadi 3 klip deterministik:
       - **Pre-Action Clip**: UI stabil, kursor diam, target terlihat, durasi = $\text{narration\_duration} + \text{reaction\_delay}$.
       - **Action Clip**: Kursor bergerak, klik, dropdown, atau pengetikan.
       - **Result Hold**: Tahan hasil aksi (Normal $\ge 800$ms, Important $\ge 1200$ms, Critical $\ge 1800$ms).
     - Rangkai visual master, assemble master audio (sample-accurate 44.1kHz), dan mux final MP4.

3. **Critical Invariants**:
   - **Hard Invariant**: $\text{action\_start\_ms} \ge \text{narration\_end\_ms} + 200\text{ms}$.
   - **Zero Action During Speech**: Layar dan kursor 100% diam selama narasi berlangsung.
   - **Click Invariant**: Audio klik ditempatkan tepat pada *visual click frame PTS* ($\Delta \le 20$ms, target $\le 10$ms).
   - **Typing Invariant**: Audio ketukan ditempatkan tepat pada rentang pengetikan visual ($\text{start} \le 20$ms, $\text{end} \le 50$ms).

4. **16-Gate Quality Assurance Suite**:
   - Memvalidasi seluruh 16 Gate teknis, konten, adegan, fokus, efek audio, deterministik A/V, dan Gate 16 (Narration-Cued Action).
       ▼ [PHASE 4 — EXTRACT VIDEO MASTER TIMELINE]
[Timeline Extractor (scripts/extract_video_timeline.py)]
• Inspects visual-master.mp4 frame PTS, fps, scene bounds
• Snaps narration windows, reaction pauses, action starts, clicks, and result holds
• OUTPUT: final-video-timeline.json & narration-cued-timeline.json
       │
       ▼ [PHASE 5 — NARRATION & SUBTITLE MASTER TRACK]
[TTS Narration Engine (scripts/tts.py)]
• Generates sample-accurate 44.1kHz master narration track & tutorial.srt
• OUTPUT: narration.mp3, narration-cued-action-map.json, narration-word-timestamps.json
       │
       ▼ [PHASE 6 — AUDIO MASTER MIXING & MUX]
[Audio Master & MUX Engine (scripts/render.py)]
• Mixes narration + click SFX on click PTS + typing SFX on typing bounds
• Muxes visual-master.mp4 + audio-master-for-final-video.wav + subtitles
• OUTPUT: final-tutorial.mp4
       │
       ▼ [PHASE 7 — 16-GATE FINAL MP4 VALIDATION]
[16-Gate Final MP4 Validator (scripts/validate.py)]
• Gate 13: Video-Master Narration Sync (0 bleed, visual window fit)
• Gate 14: Final MP4 Audio Alignment (click <= 20ms, typing start <= 20ms)
• Gate 15: Interaction Audio Following Video ("master": "FINAL_VIDEO", 0 orphan events)
• Gate 16: Narration-Cued Action (action_start >= narration_end + 200ms)
• OUTPUT: narration-cued-action-audit.json, narration-cued-action-audit.md, qa-v354.md
```

---

## 16-Gate Quality Assurance Specification

| Gate | Kategori QA | Syarat Kelulusan (PASS Criteria) |
|---|---|---|
| **Gate 1** | Technical QA | Codec H.264/AAC, resolusi 1920x1080 (desktop) atau 1080x1920 portrait (mobile) @ 30fps, durasi valid (60–90s), non-zero file size |
| **Gate 2** | Provenance QA | Run isolation, hash manifest utuh, visual_master_hash & audio_master_hash terdata |
| **Gate 3** | Content QA | Narasi sinkron dengan storyboard, expected keywords terdeteksi, forbidden keywords bersih |
| **Gate 4** | Scene QA | Scene transition halus, intro/outro utuh, scene evidence terekstrak lengkap |
| **Gate 5** | Audio-Visual QA | Audio loudness terstandarisasi, semua scene memiliki audio dan subtitle |
| **Gate 6** | Focus QA | Bounding box dalam viewport, zoom level aman, subtitle collision resolved |
| **Gate 7** | Audio Effect QA | Asset audio tersedia dan valid WAV, volume terkontrol ($\le 0.25$) |
| **Gate 8** | Interactive Audio Realism QA | Event klik/tap sesuai mode (`physical_mouse` atau `mobile_tap`), RMS typing energy nyata terdata |
| **Gate 9** | Mobile Experience QA | Format portrait (tinggi > lebar, rasio 9:16), zoom level aman ($\le 1.40$x), safe margin subtitle $\ge 24$px |
| **Gate 10** | Audio Perceptual QA | Kejujuran asal audio, pengetikan terdengar (RMS > 8.0) dan tepat waktu, narasi dominan, bebas clipping |
| **Gate 11** | Deterministic A/V Sync | Canonical event timeline synchronization, strict cross-variant isolation |
| **Gate 12** | Frame-True A/V Sync | Click/tap audio onset $\le 20$ms dari rendered visual frame PTS, typing bounds $\le 20$ms/$\le 50$ms |
| **Gate 13** | **Video-Master Narration Sync** | Narration strictly inside visual window (`narration_end_ms <= visual_end_ms`), zero audio bleed into next scenes |
| **Gate 14** | **Final MP4 Audio Alignment** | Direct inspection of final MP4 demuxed audio; click onset vs video frame PTS $\le 20$ms; typing start $\le 20$ms, end $\le 50$ms |
| **Gate 15** | **Interaction Audio Following Video** | Master timeline has `"master": "FINAL_VIDEO"`; interaction audio occurs strictly on visual frame PTS; zero orphan audio |
| **Gate 16** | **Narration-Cued Action** | `action_start_ms >= narration_end_ms + reaction_delay_ms` ($\ge 200$ms reaction delay); zero visual action during speech |

---

## Audio Assets & Specifications

- `resources/audio/metadata.json`: Kebijakan provenance audio, klasifikasi `source_type: "synthetic"` vs `"recorded"`, dan panduan penggantian fisik.
- `resources/audio/mouse-click.wav`: 65ms pemodelan transien saklar mekanis snap ganda (sintetis), 44.1kHz mono WAV, peak -3.2 dBFS.
- `resources/audio/tap.wav`: 45ms ketukan lembut layar sentuh kapasitif (sintetis), 44.1kHz mono WAV, peak -4.0 dBFS.
- `resources/audio/click.wav`: 80ms synthesized UI pop klasik V3.4.
- `resources/audio/typing.wav`: 100ms acoustic keystroke membrane switch (sintetis).
