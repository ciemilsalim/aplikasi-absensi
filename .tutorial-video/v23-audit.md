# V2.3 Audit — V2.2 State Assessment & Upgrade Plan
**Date**: 2026-09-28 | **Skill**: app-tutorial-video

---

## Apa yang SUDAH diverifikasi V2.2

| # | Kemampuan | Implementasi |
|---|-----------|--------------|
| 1 | Run ID isolation | `--run-id` arg, run directory per eksekusi |
| 2 | Recording cleanup | `fs.rmSync(outDir, {recursive:true})` sebelum record |
| 3 | Static filename fix | Pilih `.webm` terbaru by `mtime`, bukan alphabetically |
| 4 | recording-manifest.json | tutorial_id, run_id, SHA256 hash, timestamps |
| 5 | render.py provenance check | Validasi tutorial_id match sebelum render, abort exit(10) |
| 6 | render-provenance.json | Hash chain: plan→recording→audio→video |
| 7 | Technical QA Gate | codec, duration, fps, resolution, audio stream, subtitle SRT |
| 8 | Content QA Gate | expected_visual_keywords ∈ narration, forbidden_visual_keywords ∉ narration |
| 9 | Provenance QA Gate | run_id, tutorial_id, recording hash, video hash |
| 10 | Scene screenshots | 1 screenshot per scene (post-action) |
| 11 | Scene screenshot existence check | validate.py memeriksa keberadaan file |

---

## Apa yang BELUM dapat dibuktikan V2.2

| # | Gap | Dampak |
|---|-----|--------|
| 1 | **Scene-level URL evidence** | Tidak tahu apakah screenshot diambil dari URL yang benar |
| 2 | **DOM evidence per scene** | Tidak ada bukti bahwa elemen UI yang diharapkan benar-benar terlihat |
| 3 | **Expected text check per scene** | expected_visual_keywords hanya dicek dalam narration teks, bukan UI aktual |
| 4 | **Scene timeline dengan evidence_timestamp** | timing_manifest.json ada tapi tidak menyimpan timestamp saat screenshot diambil |
| 5 | **Final video frame extraction** | Tidak ada frame dari video final yang diekstrak untuk perbandingan |
| 6 | **Source vs Final comparison** | Tidak ada perbandingan visual antara recording screenshot dan video final |
| 7 | **Audio-scene alignment** | Tidak ada verifikasi bahwa narasi scene X sync dengan visual scene X |
| 8 | **Expected UI per scene** | tutorial-plan.json tidak punya expected_text/expected_ui per scene |
| 9 | **Scene order verification** | Tidak diverifikasi bahwa scene tampil dalam urutan yang benar |
| 10 | **Forbidden UI check pada visual** | forbidden_visual_keywords hanya dicek di narration, tidak di UI |

---

## Titik Paling Tepat untuk Menambahkan Scene Verification

| Fase | Titik Injeksi | File |
|------|---------------|------|
| Recording | Setelah setiap `page.screenshot()` | `record.js:L273-274` |
| Recording End | Sebelum browser.close() | `record.js:L296-300` |
| Post-Render | Setelah render.py berhasil | `extract_scene_frames.py` (new) |
| Pre-Validate | Sebelum Gate validation | `verify_scenes.py` (new) |
| Validate | Gate 4 + Gate 5 | `validate.py` |

---

## V2.3 Upgrade Plan

### New Files
- `scripts/extract_scene_frames.py` — ekstrak frame dari final video per scene
- `scripts/verify_scenes.py` — compare source evidence vs final frames (PASS/REVIEW/FAIL)

### Modified Files
- `scripts/record.js` — tambah DOM evidence, expected_text check, scene-evidence-manifest.json, scene-timeline.json
- `scripts/validate.py` — tambah Gate 4 (Scene) dan Gate 5 (Audio-Visual)
- `tutorial-plan.json` — tambah expected_ui, expected_text, expected_dom_state, verification_required per scene
- `SKILL.md` — update ke V2.3

### New Artifacts per Run
```
run_dir/
├── recording/
│   ├── scene-evidence-manifest.json   ← NEW
│   └── scene-timeline.json            ← NEW (extends timing_manifest)
├── scene-evidence/                    ← NEW dir
│   └── scene_XX.png (copies with metadata)
├── final-scene-evidence/              ← NEW dir (from extract_scene_frames.py)
│   └── scene_XX.png
├── audio/
│   └── scene-audio-map.json          ← NEW
└── qa-v23.md                         ← NEW (5-Gate QA report)
```

### V2.3 QA Gates (5 Gates)
```
Gate 1 — Technical  (retained V2.2)
Gate 2 — Provenance (retained V2.2)
Gate 3 — Content    (retained V2.2, enhanced)
Gate 4 — Scene      (NEW: evidence, order, DOM, expected text)
Gate 5 — Audio-Visual (NEW: narration-scene alignment)
```
