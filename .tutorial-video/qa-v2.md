# Quality Assurance Report V2.2 — Pengajuan Izin/Sakit Anak oleh Orang Tua

- **Target Deliverable**: `.tutorial-video/final/tutorial-pengajuan-izin-orang-tua-v2.2.mp4`
- **Tanggal Audit**: 2026-09-28
- **Target User**: Orang Tua / Wali Murid (`orang_tua`)
- **Mode**: Feature (`pengajuan_izin_orang_tua`)
- **Run ID**: `2026-09-28_152525-parent-leave-request`
- **Status Akhir**: **PASS**

---

## V2.2 Root Cause Fix Summary

Bug yang ditemukan pada V2.1 **telah diperbaiki sepenuhnya**:

| Bug | Deskripsi | Status |
|-----|-----------|--------|
| BUG#1 | Static `raw-recording.webm` alias — dipilih alphabetically first bukan newest by mtime | ✅ FIXED |
| BUG#2 | Recording directory tidak dibersihkan antar run | ✅ FIXED |
| BUG#3 | Tidak ada `recording-manifest.json` dengan `tutorial_id` | ✅ FIXED |
| BUG#4 | `render.py` tidak ada provenance check | ✅ FIXED |
| BUG#5 | `validate.py` hanya cek technical, tidak cek content/provenance | ✅ FIXED |

---

## Three-Gate QA Results

### GATE 1 — TECHNICAL QA ✅ PASS

- [x] **PASS**: Duration **75.02s** (Target: 60–90s)
- [x] **PASS**: Resolution **1920x1080** (Full HD)
- [x] **PASS**: Frame Rate **30.00 fps**
- [x] **PASS**: Video Stream: Present (H.264)
- [x] **PASS**: Audio Stream: Present (AAC)
- [x] **PASS**: Subtitles: Verified UTF-8 SRT

### GATE 2 — CONTENT QA ✅ PASS

- [x] **PASS**: `scene_01_intro.png` found (815,674 bytes)
- [x] **PASS**: `scene_02_login_navigasi.png` found (114,620 bytes)
- [x] **PASS**: `scene_03_pilih_anak_kategori.png` found (114,431 bytes)
- [x] **PASS**: `scene_04_isi_tanggal_alasan.png` found (117,476 bytes)
- [x] **PASS**: `scene_05_kirim_permohonan.png` found (115,526 bytes)
- [x] **PASS**: `scene_06_konfirmasi_sukses.png` found (115,521 bytes)
- [x] **PASS**: Expected visual keywords found in narration: `['izin', 'sakit', 'anak', 'alasan', 'permohonan', 'kirim']`
- [x] **PASS**: Forbidden visual keywords NOT found: `['jurnal mengajar', 'pokok bahasan', 'asesmen', 'refleksi', 'kbm', 'jurnal harian']` — **CLEAN**

### GATE 3 — PROVENANCE QA ✅ PASS

- [x] **PASS**: Run ID consistent: `2026-09-28_152525-parent-leave-request`
- [x] **PASS**: Recording `tutorial_id` = `parent-leave-request` ↔ Plan `tutorial_id` = `parent-leave-request` — **MATCH**
- [x] **PASS**: Recording file hash: `0e420015168c7b94` (manifest vs actual — **IDENTICAL**)
- [x] **PASS**: Final video hash: `565f1a7d91af5dfa` (matches render-provenance.json)
- [x] **PASS**: target_user: `orang_tua` (consistent across plan → recording → render)

---

## Provenance Chain

```
tutorial-plan.json
  plan_hash       : 6cc78cf10e79f8e8
  tutorial_id     : parent-leave-request
  target_user     : orang_tua
        ↓
recording-manifest.json
  run_id          : 2026-09-28_152525-parent-leave-request
  tutorial_id     : parent-leave-request
  recording_hash  : 0e420015168c7b94  ← verified against actual file
        ↓
render-provenance.json
  run_id          : 2026-09-28_152525-parent-leave-request
  tutorial_id     : parent-leave-request
  plan_hash       : 6cc78cf10e79f8e8
  recording_hash  : 0e420015168c7b94
  audio_hash      : (generated)
  final_video_hash: 565f1a7d91af5dfa
        ↓
tutorial-pengajuan-izin-orang-tua-v2.2.mp4
  Size: 4.96 MB
  Duration: 75.02s
  Resolution: 1920x1080 @ 30fps
```

---

## Content Verification

Recording menampilkan konten yang **benar**:
1. ✅ Login orang tua (`emilsalimramadhan@gmail.com`)
2. ✅ Dashboard orang tua
3. ✅ Navigasi ke `/parent/leave-requests`
4. ✅ Klik tombol buat pengajuan baru
5. ✅ Pilih nama anak (`#student_id`)
6. ✅ Pilih jenis: sakit (`#type = sakit`)
7. ✅ Isi alasan (`#reason`)
8. ✅ Submit formulir
9. ✅ Konfirmasi sukses

Tidak ada konten Jurnal Mengajar Guru dalam recording ini.

---

## Status Akhir

```
QUALITY GATE V2.2 STATUS: PASS
  TECHNICAL QA  : PASS
  CONTENT QA    : PASS
  PROVENANCE QA : PASS
```

**Final video**: `.tutorial-video/final/tutorial-pengajuan-izin-orang-tua-v2.2.mp4`
