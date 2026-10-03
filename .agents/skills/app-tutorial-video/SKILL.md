---
name: app-tutorial-video
description: Smooth Motion & Cinematic UI Engine (V3.6.1) — Continuous easeInOutCubic per-frame camera zoom & pan trajectory, Lanczos-4 subpixel scaling, motion-profile cursor, match-continuity transitions, locked 0.00ms timing variance, and 17-Gate QA Suite.
---

# App Tutorial Video Skill (V3.6.1 — Smooth Motion & Cinematic UI Engine)

## Purpose & Core Rule

Mulai versi **V3.6.1**, engine video tutorial mempertahankan **ABSOLUTE TIMING INVARIANT** dari V3.6 (0.00ms variance pada narasi, adegan, klik, dan durasi akhir) sambil meng-upgrade **KUALITAS GERAK VISUAL (Cinematic UI Motion)**:

```
"NARASI MEMBERI INSTRUKSI" (UI statis 1.0x overview)
        ↓
"NARASI SELESAI"
        ↓
"JEDA REAKSI (200–400ms)" -> "CONTINUOUS ZOOM-IN (easeInOutCubic)"
        ↓
"CURSOR MOTION PROFILE" (ease-in -> cruise -> ease-out -> settle >=180ms)
        ↓
"ACTION PERFORMED & SFX ONSET" (steady hold in focus)
        ↓
"RESULT HOLD" -> "CONTINUOUS ZOOM-OUT (easeInOutCubic to 1.0x)"
        ↓
"133ms DISSOLVE / MATCH-CONTINUITY TRANSITION"
```

1. **Continuous Smooth Zoom & Pan Engine**:
   - **Continuous Per-Frame Interpolation**: Tidak ada stepwise zoom jump atau camera teleportation. Setiap frame diinterpolasikan dengan formula `easeInOutCubic`.
   - **Float Subpixel Windowing**: Menggunakan `cv2.getRectSubPix` untuk mencegah spatial coordinate jitter.
   - **Lanczos-4 Scaling**: Menggunakan `cv2.INTER_LANCZOS4` untuk mempertahankan ketajaman teks UI tanpa artefak ghosting atau bilinear blurring.
   - **Safe Target Margins**: Zoom target maksimum default 1.60x (critical focus max 1.70x), dibatasi dengan padding 10-15% agar target tidak menyentuh tepi layar.

2. **Cursor Motion Profile**:
   - Pola gerak natural: `START -> ease-in -> cruise -> ease-out -> TARGET -> micro settle (>= 180ms) -> CLICK`.
   - Kursor berhenti stabil dan tenang sebelum aksi klik/gesture dieksekusi.

3. **Subtle Scene Transitions**:
   - Menggunakan match-continuity untuk UI yang berkelanjutan.
   - Menggunakan 133ms (4 frame) dissolve blend saat state halaman berubah tanpa mengubah timing boundaries atau memotong durasi video.

4. **17-Gate Quality Assurance Suite**:
   - Memvalidasi seluruh 17 Quality Gates, termasuk **Gate 17 (SMOOTH MOTION QA)**:
     - Zoom continuity: $\max(|\Delta Z|) \le 0.08$ / frame
     - Pan continuity: $\max(\Delta \text{pan}) \le 50.0$ px / frame
     - Scaling engine: Lanczos-4 verified
     - Motion evidence: 6-panel zoom contact sheet & 4-panel cursor contact sheet generated
     - Hard timing invariant: exact 0.00ms variance with V3.6 master.

---

## 17-Gate Quality Assurance Specification

| Gate | Kategori QA | Syarat Kelulusan (PASS Criteria) |
|---|---|---|
| **Gate 1** | Technical QA | Codec H.264/AAC, resolusi 1920x1080 (desktop) atau 1080x1920 portrait (mobile) @ 30fps, durasi valid (60–90s), non-zero file size |
| **Gate 2** | Provenance QA | Run isolation, hash manifest utuh, visual_master_hash & audio_master_hash terdata, motion engine specs terdata |
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
| **Gate 17** | **Smooth Motion & Cinematic UI** | $\max(|\Delta Z|) \le 0.08$/frame, $\max(\Delta \text{pan}) \le 50.0$px/frame, Lanczos-4 scaling, contact sheets in `motion-evidence/`, 0.00ms variance |
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
