---
name: app-tutorial-video
description: Creates professional end-to-end tutorial videos (V2.2 - Content Consistency & Recording Isolation) for web applications in the Antigravity workspace. Generates humanized browser automation, synchronized Indonesian voice-over, custom intro/outro titlecards, non-destructive visual highlights/zoom, UTF-8 SRT subtitles, dynamic target duration fitting (60-90s prototype gate), and strict Three-Gate Quality Gate reports (Technical + Content + Provenance).
---

# App Tutorial Video Skill (V2.2 — Content Consistency & Recording Isolation)

## Purpose

Turn real, working application features into professional, user-facing tutorial videos. Treat the active running application as the source of truth.

## V2.2 Critical Bug Fixes (over V2.1)

**Root Cause Fixed**: V2.1 had a content consistency bug where a recording from Tutorial A (Jurnal Mengajar Guru) was used when rendering Tutorial B (Pengajuan Izin Orang Tua), because:
1. `record.js` used a **static alias** (`raw-recording.webm`) and picked the **first alphabetical** `.webm` from a shared, never-cleaned directory — which could be a file from a previous run.
2. The **recording directory was never cleared** between runs, allowing stale artifacts to contaminate new recordings.
3. No **`recording-manifest.json`** was written, so there was no `tutorial_id` binding.
4. `render.py` had **no provenance check** — it blindly accepted any `--video` path.
5. `validate.py` only checked **technical properties** (codec, duration, FPS) — never verified that video content matched the tutorial plan topic.

**All 5 bugs are fixed in V2.2.**

## V2 Core Improvements & Principles (retained from V2.0)

1. **Truth from the Product**: Inspect the workspace codebase, routes, controllers, views, database, and live runtime behavior before drafting narration or storyboard.
2. **Target User Centric**: Adapt narration tone, vocabulary, and workflow to the `target_user` (`guru`, `orang_tua`, `operator`, `kepala_sekolah`, `satpam`, `siswa`, `umum`). **Never expose developer routes (e.g. `/teacher/journals/create`), Blade templates, or internal database terms to the end user.**
3. **Structured Professional Narration (WHAT -> ACTION -> RESULT)**:
   - **WHAT**: Explain the purpose of the scene
   - **ACTION**: Clear natural spoken instruction
   - **RESULT**: Spoken confirmation of visual outcome
4. **Humanized Visual Recording**: 1920x1080, smooth mouse, ripple indicators, DOM highlights, humanized delays.
5. **Dynamic Target Duration & Quality Gate (60–90 Seconds)**: Video duration < 60s or > 90s results in strict **FAIL**.
6. **Titlecards, Subtitles, and Optional BGM**: 3–5s intro/outro, aligned UTF-8 SRT, optional BGM.

---

## V2.2 New Requirements for tutorial-plan.json

Each `tutorial-plan.json` MUST now include:

```json
{
  "tutorial_id": "parent-leave-request",   ← REQUIRED: unique machine-readable ID
  "feature": "pengajuan_izin_orang_tua",   ← REQUIRED: feature slug
  "expected_visual_keywords": [            ← OPTIONAL but recommended
    "izin", "sakit", "siswa", "alasan"
  ],
  "forbidden_visual_keywords": [           ← OPTIONAL but recommended
    "jurnal mengajar", "pokok bahasan", "refleksi"
  ]
}
```

---

## Invocation Modes & Command Syntax

### `/app-tutorial-video feature <feature_name>`
Creates a focused video tutorial for a single feature.

### `/app-tutorial-video module <module_name>`
Creates a comprehensive tutorial covering a connected module of features.

### `/app-tutorial-video onboarding <target_user>`
Creates an end-to-end user onboarding journey.

### `/app-tutorial-video release`
Inspects the latest product features/changes and generates video tutorial coverage.

---

## Output Contract & Directory Structure (V2.2)

All runtime artifacts are organized under `.tutorial-video/runs/<run_id>/` for **isolation**:

```text
.tutorial-video/
├── discovery.md                # Deep product discovery & route/feature matrix
├── storyboard.md               # Scene-by-scene visual & narration breakdown
├── tutorial-plan.json          # Schema-validated execution plan (with tutorial_id)
├── v2-audit.md                 # Audit report
└── runs/
    └── <run_id>/               # e.g. 2026-09-28_141500-parent-leave-request
        ├── plan/               # Copy of tutorial-plan.json used for this run
        ├── recording/
        │   ├── raw-recording.webm          # NEWEST .webm from Playwright (by mtime)
        │   ├── recording-manifest.json     # Provenance: tutorial_id, run_id, hash
        │   ├── timing_manifest.json        # Scene timing data
        │   └── scene_XX_<id>.png           # Scene screenshots
        ├── audio/
        │   ├── narration.mp3
        │   └── audio_meta.json
        ├── subtitle/
        │   └── tutorial.srt
        ├── render/
        │   └── render-provenance.json      # Hash chain: plan→recording→audio→video
        ├── thumbnail/
        ├── manifest.json                   # Final milestone metadata
        └── qa-v2.md                        # Three-Gate QA report (Technical+Content+Provenance)

.tutorial-video/final/
└── tutorial-<feature>-v2.2.mp4            # Final delivered video
```

---

## Workflow Execution Steps (V2.2)

### Step 0 — Generate Run ID
```bash
# PowerShell
$RUN_ID = (Get-Date -Format "yyyy-MM-dd_HHmmss") + "-parent-leave-request"
$RUN_DIR = ".tutorial-video/runs/$RUN_ID"
New-Item -ItemType Directory -Force -Path "$RUN_DIR/recording", "$RUN_DIR/audio", "$RUN_DIR/subtitle", "$RUN_DIR/render", "$RUN_DIR/thumbnail"
```

### Step 1 — Product Discovery & Plan Creation
1. Inspect application routes, controllers, views, database, and running dev server.
2. Generate `.tutorial-video/discovery.md` and `.tutorial-video/storyboard.md`.
3. Build `.tutorial-video/tutorial-plan.json` with `tutorial_id`, `feature`, `expected_visual_keywords`, `forbidden_visual_keywords`.

### Step 2 — Humanized Browser Recording (Isolated)
```bash
node .agents/skills/app-tutorial-video/scripts/record.js \
  --plan .tutorial-video/tutorial-plan.json \
  --out "$RUN_DIR/recording" \
  --run-id "$RUN_ID"
```

**V2.2 Guarantees**:
- Recording directory is **cleared** before recording starts
- Output `.webm` is selected by **newest mtime** (not alphabetical order)
- `recording-manifest.json` is written with `tutorial_id`, `run_id`, file hash

### Step 3 — Voice-Over & Subtitle Generation
```bash
python .agents/skills/app-tutorial-video/scripts/tts.py \
  --plan .tutorial-video/tutorial-plan.json \
  --audio-out "$RUN_DIR/audio/narration.mp3" \
  --subtitle-out "$RUN_DIR/subtitle/tutorial.srt" \
  --meta-out "$RUN_DIR/audio/audio_meta.json"
```

### Step 4 — Multi-Stage Render Pipeline (Provenance-Aware)
```bash
python .agents/skills/app-tutorial-video/scripts/render.py \
  --plan .tutorial-video/tutorial-plan.json \
  --video "$RUN_DIR/recording/raw-recording.webm" \
  --audio "$RUN_DIR/audio/narration.mp3" \
  --subtitles "$RUN_DIR/subtitle/tutorial.srt" \
  --output .tutorial-video/final/tutorial-pengajuan-izin-orang-tua-v2.2.mp4 \
  --run-id "$RUN_ID"
```

**V2.2 Guarantees**:
- Provenance check: verifies `tutorial_id` in `recording-manifest.json` matches plan
- If mismatch: **ABORTS with exit code 10** before creating any video
- Writes `render-provenance.json` with hash chain after successful render

### Step 5 — Three-Gate Quality Validation
```bash
python .agents/skills/app-tutorial-video/scripts/validate.py \
  --video .tutorial-video/final/tutorial-pengajuan-izin-orang-tua-v2.2.mp4 \
  --plan .tutorial-video/tutorial-plan.json \
  --subtitles "$RUN_DIR/subtitle/tutorial.srt" \
  --recording-dir "$RUN_DIR/recording" \
  --audio "$RUN_DIR/audio/narration.mp3" \
  --min-duration 60 \
  --max-duration 90
```

**V2.2 Three Gates**:
1. **Technical**: codec, resolution, FPS, audio stream, duration, black screen, subtitle validity
2. **Content**: scene screenshots exist, expected keywords in narration, forbidden keywords absent
3. **Provenance**: run_id consistent, recording hash verified, plan→recording→render hash chain intact

Generate `.tutorial-video/runs/<run_id>/qa-v2.md` — never report PASS if any gate fails.

---

## V2.2 QA Gate Structure

```
QUALITY GATE V2.2
├── TECHNICAL QA
│   ├── [x] codec (H.264 video + AAC audio)
│   ├── [x] resolution (≥1280x720, target 1920x1080)
│   ├── [x] fps (15–60 fps)
│   ├── [x] audio stream present
│   ├── [x] duration (60–90s)
│   └── [x] subtitle SRT valid UTF-8
├── CONTENT QA
│   ├── [x] all scene screenshots exist in recording dir
│   ├── [x] expected_visual_keywords in narration
│   └── [x] forbidden_visual_keywords NOT in narration
└── PROVENANCE QA
    ├── [x] recording-manifest.json exists
    ├── [x] tutorial_id matches plan ↔ recording
    ├── [x] recording file hash matches manifest
    ├── [x] render-provenance.json exists
    └── [x] final video hash matches provenance

FINAL STATUS = PASS only if ALL gates pass
```
