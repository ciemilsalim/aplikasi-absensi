# Dokumen Asal-Usul & Pembekuan Tagihan (Billing Provenance) — September 2026

**Tanggal Snapshot**: 27 September 2026  
**Target Aplikasi**: SIASEK Live Production ([https://presensi-smpn1biau.zahradev.id](https://presensi-smpn1biau.zahradev.id))  
**Sumber Tagihan**: `LIVE_APPLICATION` (`/admin/dashboard`)  
**Status Pembekuan**: `VERIFIED` & `FROZEN` (`billing/billing-snapshot.json`)  

---

## 1. Konsep Arsitektur Tagihan BOSP SIASEK

Arsitektur penagihan BOSP pada skill `siasek-bos` v1.0 menerapkan 5 prinsip utama keamanan penagihan:

1. **Live Billing Extraction (Bulan Berjalan)**:
   Pada bulan berjalan (`CURRENT_PERIOD`), jumlah siswa aktif diambil secara langsung dari UI Aplikasi LIVE SIASEK melalui sesi Admin resmi (`/admin/dashboard`).

2. **Frozen Verified Billing Snapshot**:
   Setelah ekstrak billing bulan berjalan dinyatakan **VERIFIED** dan lulus **QA PASS**, sistem secara otomatis membekukan hasilnya ke dalam berkas artifak snapshot:
   `evidence/bosp/<YYYY>/<MM>-<bulan>/billing/billing-snapshot.json`

3. **Historical Period Reuse**:
   Untuk bulan yang telah selesai (`HISTORICAL_PERIOD`), generator tidak diperbolehkan mengambil data LIVE masa kini untuk menggantikan angka historis. Generator wajib menggunakan **Frozen Verified Billing Snapshot** yang telah dibekukan pada periode target.

4. **Snapshot History**:
   Setiap pembaruan snapshot pada bulan berjalan disimpan secara historis di `billing/snapshots/<YYYY-MM-DD>.json` untuk menjamin audit trail penagihan yang transparan.

5. **Blocked & Stale Artifact Safety**:
   Jika hasil billing historis `UNVERIFIED` atau QA dinyatakan `BLOCKED`, generator secara otomatis memindahkan berkas PDF/MD lama ke `blocked/previous-invalid-artifacts/` dan membuat berkas `BLOCKED_REPORT_<BULAN>_<TAHUN>.md` untuk mencegah penggunaan artifak usang.

---

## 2. Struktur Rincian Frozen Snapshot September 2026

```json
{
    "period": "September 2026",
    "snapshot_date": "2026-09-27",
    "source_type": "LIVE_APPLICATION",
    "source_role": "admin",
    "source_page": "/admin/dashboard",
    "source_url": "https://presensi-smpn1biau.zahradev.id",
    "active_student_count": 368,
    "rate_per_student": 1000,
    "total": 368000,
    "status": "VERIFIED",
    "frozen": true,
    "screenshot_ref": "bukti_billing_september_2026.png"
}
```

---

## 3. Matriks Rekonsiliasi Tagihan September 2026

- **Jumlah Siswa Aktif**: 368 Siswa
- **Tarif Per Siswa**: Rp1.000 / siswa / bulan
- **Total Tagihan**: Rp368.000 (Tiga Ratus Enam Puluh Delapan Ribu Rupiah)
- **Status Invoice**: `DRAFT` (`SIASEK-BIAU/2026/09/001`)
- **Status Provenance**: **PASS & FROZEN**
