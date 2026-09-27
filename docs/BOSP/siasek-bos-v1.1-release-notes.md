# SIASEK BOSP Evidence Generator — Release Notes v1.1

**Tanggal Rilis**: 27 September 2026  
**Versi Engine**: `1.1`  
**Base Release**: `1.0` (Commit Baseline: `015b73c`)  
**Project**: SIASEK BIAU (`SIASEK-BIAU`)  

---

## 1. Ringkasan Pembaruan Versi 1.1

Release `siasek-bos` v1.1 menghadirkan mekanisme pembekuan tagihan presisi, keamanan data historis, serta sistem pencegahan artefak usang (Stale Artifact Safety) untuk menjamin integritas paket bukti BOSP.

### Fitur Utama & Pembaruan Arsitektur:

1. **Live Application Billing Scraper Integration**:
   - Pengambilan data siswa aktif secara otomatis dan realtime dari antarmuka Admin LIVE SIASEK (`/admin/dashboard`).

2. **Historical Period Isolation & Safety**:
   - Pembatasan ketat komit Git dan tangkapan layar berdasarkan tanggal penutupan periode (`period_end`). Fitur atau screenshot masa mendatang (misal komit September) dilarang keras diklaim untuk periode historis (misal Agustus).

3. **Multi-Status Snapshot System**:
   - `VERIFIED_SNAPSHOT`: Snapshot terverifikasi untuk bulan berjalan (`CURRENT_PERIOD`), dapat diperbarui secara dinamis hingga akhir bulan (`final_frozen = false`).
   - `FINAL_FROZEN_SNAPSHOT`: Snapshot final yang terkunci setelah penutupan periode (`HISTORICAL_PERIOD`), tersimpan permanen di `billing/final/<YYYY-MM>-final.json`.

4. **Historical Snapshot Reuse**:
   - Mengulang penggunaan **Frozen Verified Snapshot** untuk bulan yang telah berlalu tanpa melakukan kueri data LIVE masa kini yang dapat mengubah nilai historis.

5. **Stale Artifact Protection & Hard Gates**:
   - Jika QA bernilai `BLOCKED`, generator secara otomatis mengarantina paket PDF/MD usang ke `blocked/previous-invalid-artifacts/` dan membuat laporan `BLOCKED_REPORT_<BULAN>_<TAHUN>.md`.

---

## 2. Hasil Pengujian Regresi (Regression Test Suite)

```text
ENGINE TEST SUITE  : PASS
SEPTEMBER PACKAGE  : PASS (368 Siswa Aktif, LIVE Application, VERIFIED_SNAPSHOT, DRAFT Invoice)
AUGUST PACKAGE     : BLOCKED — HISTORICAL EVIDENCE UNAVAILABLE (Expected Safety Gate)
HISTORICAL REUSE   : PASS
PERIOD ISOLATION   : PASS
SNAPSHOT STATUS    : PASS
PRIVACY MASKING    : PASS (100% menggunakan docs/BOSP/live-evidence/masked/)
INVOICE NUMBERING  : PASS (SIASEK-BIAU/2026/09/001 [DRAFT])
```

---

## 3. Batasan Terdeteksi (Known Limitations)

- **Akses Periode Historis Tanpa Snapshot**:
  Jika suatu bulan historis belum memiliki `billing-snapshot.json` yang dibekukan pada masanya, generator akan menolak pembuatan berkas dengan status **QA: BLOCKED**. Hal ini merupakan mekanisme perlindungan data, bukan kegagalan sistem (*expected business protection*).
