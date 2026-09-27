# Laporan Provenance & Rekonsiliasi Billing SIASEK — September 2026

**Tanggal Rekonsiliasi**: 27 September 2026  
**Evidence Cutoff**: 27 September 2026  
**Target Application**: SIASEK Live Production ([https://presensi-smpn1biau.zahradev.id](https://presensi-smpn1biau.zahradev.id))  
**Status Invoice**: **DRAFT**  
**Nomor Invoice**: `SIASEK-BIAU/2026/09/001`  

---

## 1. Live Application Billing Snapshot

> [!IMPORTANT]
> **Definisi Sumber Data Billing**:  
> Jumlah siswa aktif yang menjadi dasar perhitungan tagihan diambil secara langsung dari **LIVE APPLICATION BILLING SNAPSHOT** pada tanggal cutoff.  
> *Wording Resmi*: **"Jumlah siswa aktif berdasarkan snapshot aplikasi SIASEK LIVE pada 27 September 2026."**

- **Source Type**: `LIVE_APPLICATION`
- **Source URL**: `https://presensi-smpn1biau.zahradev.id`
- **Source Page**: `/admin/dashboard`
- **Authenticated Role**: `admin` (`admin@admin.com`)
- **Evidence Cutoff**: `2026-09-27`

---

## 2. Matriks Cross-Check 4 Poin (Verification Comparison)

Tabel berikut menunjukkan hasil rekonsiliasi silang antara 4 metode verifikasi independen:

| METODE VERIFIKASI | SUMBER / METODE | AKTIF SISWA DITEMUKAN | STATUS MATCH |
| :--- | :--- | :---: | :---: |
| **1. HTML cURL Extraction** | Scraper `live_billing_extractor.php` via HTTP Session | **368** | **MATCH** |
| **2. Browser LIVE Verification** | Headless Browser Automation Admin Session | **368** | **MATCH** |
| **3. Screenshot Visual Evidence** | Live Screenshot `bukti_billing_september_2026.png` | **368** | **MATCH** |
| **4. Manifest Record** | Document Package `manifest.json` metadata | **368** | **MATCH** |

```text
CURL_RESULT (368) == BROWSER_RESULT (368) == SCREENSHOT_RESULT (368) == MANIFEST_RESULT (368)
STATUS REKONSILIASI: 100% KONSISTEN (PASS)
```

---

## 3. Detail Verifikasi Visual Browser & Screenshot Evidence

- **File Tangkapan Layar**: [`docs/BOSP/live-evidence/bukti_billing_september_2026.png`](file:///d:/laragon/www/siasek/aplikasi-absensi/docs/BOSP/live-evidence/bukti_billing_september_2026.png)
- **Teks Eksplisit yang Terlihat pada UI**:
  > *"Total 368 Siswa Aktif terdaftar dalam sistem"*
- **Sub-Metrik Pendukung pada Layar**:
  - Hadir Total: `0 dari 368 Siswa`
  - Belum Absen: `368 Siswa`
  - Waktu Tangkapan: `Minggu, 27 September 2026`

---

## 4. Perhitungan Tagihan (Billing Calculation)

Perhitungan tagihan bulanan SIASEK Biau menggunakan formula resmi:

$$\text{Total Tagihan} = \text{Jumlah Siswa Aktif} \times \text{Tarif Per Siswa}$$

- **Jumlah Siswa Aktif (`billing_student_count`)**: 368 Siswa
- **Tarif Per Siswa (`billing_rate`)**: Rp1.000 / siswa / bulan
- **Total Tagihan (`billing_total`)**: **Rp368.000** (*Tiga Ratus Enam Puluh Delapan Ribu Rupiah*)

---

## 5. Metadata Provenance Lengkap (`manifest.json`)

```json
{
  "billing": {
    "source_type": "LIVE_APPLICATION",
    "source_url": "https://presensi-smpn1biau.zahradev.id",
    "source_page": "/admin/dashboard",
    "source_role": "admin",
    "capture_method": [
      "HTTP_HTML_EXTRACTION",
      "BROWSER_VISUAL_VERIFICATION"
    ],
    "cutoff": "2026-09-27",
    "active_student_count": 368,
    "rate_per_student": 1000,
    "total": 368000
  },
  "project_code": "SIASEK-BIAU",
  "customer": "SMP Negeri 1 Biau",
  "invoice_number": "SIASEK-BIAU/2026/09/001",
  "invoice_status": "DRAFT",
  "qa_status": "PASS"
}
```

---

## 6. Safety & Read-Only Invariants Assertion

- **Zero Data Mutations**: 0 HTTP POST/PUT/PATCH/DELETE pada entitas bisnis production.
- **Zero Credentials Leaked**: Password admin dibaca dari `.env.siasek-bos` dan tidak dicetak pada log.
- **Kepatuhan Pembayaran**: Status invoice tetap berstatus **`DRAFT`** (Tanpa menerbitkan bukti bayar).
