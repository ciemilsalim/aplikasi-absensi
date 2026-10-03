---
name: app-tutorial-video
description: Video-Master / Action-Word Synchronization Audio Engine (V3.5.3.1) — VIDEO ACTION IS ABSOLUTE MASTER. Word-level timing (narration-word-timestamps.json), Action-Word mapping (action-word-map.json), and 16-Gate QA Suite with Gate 16 (Action-Word Sync <= 20ms).
---

# App Tutorial Video Skill (V3.5.3.1 — Action-Word / Video-Master Synchronization Engine)

## Purpose & Core Decision

Mulai versi **V3.5.3.1**, **VIDEO ACTION IS THE ABSOLUTE MASTER**:
Setiap kata kerja dalam narasi yang mewakili tindakan ("klik", "ketik", "pilih", "buka", "masukkan", "tekan") **HARUS SINKRON** dengan tindakan visual aktual:
1. **Video Master, Audio Slave**: Final video tidak boleh berubah karena audio. Narasi dan efek audio yang menyesuaikan ke video frame PTS visual aktual, bukan sebaliknya.
2. **Word-Level Timing**: TTS menghasilkan timestamp tingkat kata (`narration-word-timestamps.json`) melalui streaming boundary `WordBoundary`.
3. **Action-Word Map (`action-word-map.json`)**: Memetakan setiap visual action frame PTS langsung ke timestamp awal pengucapan kata kerja aksi dan onset efek audio:
   - `video_action.frame_pts_ms` vs `narration_anchor.word_start_ms` vs `effect_audio.onset_ms`
   - Toleransi: $|offset| \le 20$ ms (ideal 0 ms).
4. **Click & Typing Effect Synchronization**:
   - Click sound onset anchor langsung ke `actual_click_frame_pts_ms`.
   - Typing sound mengikuti visual typing bounds `[start_frame_pts_ms, end_frame_pts_ms]`.
   - Kata narasi "ketik" dimulai tepat pada `start_frame_pts_ms`.
5. **16-Gate Quality Assurance Suite**:
   - **Gate 16 (Action-Word Sync)**: Memverifikasi keselarasan tripartit:
     `video_action_pts_ms` vs `narration_action_word_start_ms` vs `effect_audio_onset_ms` ($\le 20$ ms).
     Menghasilkan `action-word-audit.json` dan `action-word-audit.md`.

---

## Unified Two-Phase Pipeline Execution Order (V3.5.3.1)

```
Visual Choreography Plan (scenes, target elements, actions)
       │
       ▼ [PHASE A — VISUAL MASTER (PICTURE LOCK)]
[Visual Action Recorder (scripts/record.js)]
• Playwright browser recording
• Precise visual actions, clicks, and typing intervals
       │
       ▼
[FFmpeg Visual Master Render Engine (scripts/render.py --visual-master-only)]
• Piecewise zoom & pan with subtitle collision safety
• Renders intro titlecard + main processed recording + outro
• OUTPUT: visual-master.mp4 (100% PICTURE LOCKED, NO AUDIO)
       │
       ▼ [PHASE B — EXTRACT ACTUAL VIDEO TIMELINE]
[Timeline Extractor (scripts/extract_video_timeline.py)]
• Inspects visual-master.mp4 frame PTS, fps, scene bounds
• Computes visual_start_ms, visual_end_ms, and scene actions
• Snaps clicks & typing intervals to exact video frame PTS
• OUTPUT: final-video-timeline.json & video-master-timeline.json ("master": "FINAL_VIDEO")
       │
       ▼ [PHASE C — ACTION-WORD SYNCHRONIZED NARRATION]
[TTS Action-Word Sync Engine (scripts/tts.py)]
• Streams edge-tts with boundary='WordBoundary'
• Anchors action verb words ("klik", "ketik", "pilih") to visual action frame PTS
• OUTPUT: narration-word-timestamps.json & action-word-map.json
• Sample-accurate 44.1kHz master narration track & tutorial.srt
       │
       ▼ [PHASE D — AUDIO MASTER MIXING & MUX]
[Audio Master & MUX Engine (scripts/render.py)]
• Mixes narration + click SFX + typing SFX into: audio-master-for-final-video.wav
• Muxes visual-master.mp4 + audio-master-for-final-video.wav + adapted subtitles
• OUTPUT: final-tutorial.mp4 & tutorial.mp4
       │
       ▼ [PHASE E — 16-GATE FINAL MP4 VALIDATION]
[16-Gate Final MP4 Validator (scripts/validate.py)]
• Direct inspection of final MP4
• Gate 13: Video-Master Narration Sync (0 bleed, visual window fit)
• Gate 14: Final MP4 Audio Alignment (click <= 20ms, typing start <= 20ms, end <= 50ms)
• Gate 15: Interaction Audio Following Video ("master": "FINAL_VIDEO", 0 orphan events)
• Gate 16: Action-Word Sync (video PTS vs action word start vs sfx onset <= 20ms)
• Emits action-word-audit.json, action-word-audit.md, & qa-v3531.md
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
| **Gate 16** | **Action-Word Sync** | Tripartite alignment: `video_action_pts_ms` vs `narration_action_word_start_ms` vs `effect_audio_onset_ms` ($\le 20$ms) |

---

## Audio Assets & Specifications

- `resources/audio/metadata.json`: Kebijakan provenance audio, klasifikasi `source_type: "synthetic"` vs `"recorded"`, dan panduan penggantian fisik.
- `resources/audio/mouse-click.wav`: 65ms pemodelan transien saklar mekanis snap ganda (sintetis), 44.1kHz mono WAV, peak -3.2 dBFS.
- `resources/audio/tap.wav`: 45ms ketukan lembut layar sentuh kapasitif (sintetis), 44.1kHz mono WAV, peak -4.0 dBFS.
- `resources/audio/click.wav`: 80ms synthesized UI pop klasik V3.4 (dipertahankan).
- `resources/audio/typing.wav`: 100ms acoustic keystroke membrane switch (sintetis).
- Petunjuk penggantian aset kustom fisik tersedia di [`resources/audio/README.md`](file:///d:/laragon/www/siasek/aplikasi-absensi/.agents/skills/app-tutorial-video/resources/audio/README.md).

---

## Verification & Documentation Links

- Laporan Lengkap V3.5.1: [v351-report.md](file:///d:/laragon/www/siasek/aplikasi-absensi/.tutorial-video/v351-report.md)
- Spesifikasi Metadata Audio: [resources/audio/metadata.json](file:///d:/laragon/www/siasek/aplikasi-absensi/.agents/skills/app-tutorial-video/resources/audio/metadata.json)
- Panduan Audio Pengguna: [resources/audio/README.md](file:///d:/laragon/www/siasek/aplikasi-absensi/.agents/skills/app-tutorial-video/resources/audio/README.md)
- Preview Audio: [.tutorial-video/audio-preview/](file:///d:/laragon/www/siasek/aplikasi-absensi/.tutorial-video/audio-preview/)
- QA Report Desktop (Gate 10): [run-desktop/qa-v351.md](file:///d:/laragon/www/siasek/aplikasi-absensi/.tutorial-video/runs/run-20261003-parent-leave-v35-desktop/qa-v351.md)
- QA Report Mobile (Gate 10): [run-mobile/qa-v351.md](file:///d:/laragon/www/siasek/aplikasi-absensi/.tutorial-video/runs/run-20261003-parent-leave-v35-mobile/qa-v351.md)
- Laporan Audit Audio Awal: [v35-audio-audit.md](file:///d:/laragon/www/siasek/aplikasi-absensi/.tutorial-video/v35-audio-audit.md)
