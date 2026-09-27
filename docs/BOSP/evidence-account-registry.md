# SIASEK Evidence Account Registry — Registry Akun Live Evidence BOSP

**Tanggal Updated**: 27 September 2026  
**Project**: SIASEK (Sistem Informasi & Absensi Sekolah - SMP Negeri 1 Biau)  
**Live Application**: [https://presensi-smpn1biau.zahradev.id](https://presensi-smpn1biau.zahradev.id)  

---

## 1. Summary Matrix Registry (Live Verified Accounts)

Tabel berikut memetakan peran operasional aplikasi SIASEK terhadap alias akun evidence, username/email resmi, workflow utama, target halaman bukti, dan status verifikasi live:

| Role | Account Alias | Login Email / Identifier | Workflow Utama | Target Evidence | Status Live Verification |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Viewer** | `VIEWER_EVIDENCE` | `siasek_evidence@example.com` | Audit Read-Only Manajerial & Monitoring BOSP | Dashboard Admin, Principal, Rekap Reports, Supervisi Jurnal | **VERIFIED LIVE** (`bukti_01` s/d `07`) |
| **Kepala Sekolah** | `PRINCIPAL_EVIDENCE` | `kepsek@admin.com` | Executive Monitoring & Persentase Kehadiran Sekolah (Marlinda, S.Pd) | Executive Dashboard (`/principal/dashboard`) | **VERIFIED LIVE** (`bukti_14`) |
| **Guru & Wali Kelas** | `TEACHER_EVIDENCE` | `elianaputri1988@gmail.com` | Input Presensi Mapel, Jurnal Mengajar, Anecdotes, & Wali Kelas 7D | Dashboard Guru, Absensi Saya, Form Anecdotes | **VERIFIED LIVE** (`bukti_08` s/d `10`) |
| **Orang Tua** | `PARENT_EVIDENCE` | `awaludin914@guru.smp.belajar.id` | Portal Monitoring Anak & Enforced 3-Step Verification Onboarding | Parent Onboarding & Verification Flow | **VERIFIED LIVE** (`bukti_11` & `12`) |
| **Satpam / Piket** | `SATPAM_EVIDENCE` | `satpam@siasek.com` | Pengoperasian Scanner Gerbang & Shortcut Intervensi (Syukur) | Kiosk Scanner & Admin Dashboard | **VERIFIED LIVE** (`bukti_13`) |
| **Siswa** | `STUDENT_EVIDENCE` | N/A (QR Card / Parent Linked) | Identitas Digital Siswa & Scan QR Code Presensi | Diwakili Scanner & Portal Orang Tua | **NO SEPARATE ACCOUNT REQUIRED** |
| **Operator / TU** | `OPERATOR_EVIDENCE` | Covered by Admin/Satpam | Input Presensi Manual & Intervensi Tata Usaha | Form Manual Attendance TU | **COVERED BY ADMIN / SATPAM** |

> [!IMPORTANT]
> **TIDAK ADA PASSWORD YANG DITULIS ATAU DISIMPAN PADA DOKUMEN INI.**  
> Seluruh password akun dikonfigurasi melalui secure environment storage (`.env.siasek-bos`) dan dibaca secara runtime oleh browser automation script tanpa dicetak ke log/git.

---

## 2. Analisis Kebutuhan: Akun Asli Role vs. Akun Viewer

| Jenis Workflow | Cukup Menggunakan Viewer? | Perlu Akun Asli Role? | Status Teruji Live |
| :--- | :---: | :---: | :--- |
| **Executive & Monitoring Dashboard** | **YA** | TIDAK | **VERIFIED LIVE** (`siasek_evidence@example.com` & `kepsek@admin.com`) |
| **Rekapitulasi & Report BOSP** | **YA** | TIDAK | **VERIFIED LIVE** (`/admin/reports`) |
| **Supervisi Jurnal Mengajar** | **YA** | TIDAK | **VERIFIED LIVE** (`/admin/teaching-journals`) |
| **Form Input Presensi Mapel (Guru)** | TIDAK | **YA** | **VERIFIED LIVE** (`elianaputri1988@gmail.com` - Elyana Saputri Agung, S.Pd, Gr) |
| **Pencatatan Jurnal & Anecdotes Guru** | TIDAK | **YA** | **VERIFIED LIVE** (`/teacher/anecdotes`) |
| **Portal Monitoring Orang Tua** | TIDAK | **YA** | **VERIFIED LIVE** (`awaludin914@guru.smp.belajar.id` - Onboarding Enforced) |
| **Kiosk Scanner QR Gerbang** | TIDAK | **YA** | **VERIFIED LIVE** (`satpam@siasek.com` - Syukur) |

---

## 3. Detail Workflow & Restriksi Keamanan Per Role (Live Verified)

### 3.1 Role: Guru Mapel & Wali Kelas (`TEACHER_EVIDENCE`)

- **ACCOUNT**: `elianaputri1988@gmail.com` (`Elyana Saputri Agung, S.Pd, Gr` - Wali Kelas 7D)
- **WORKFLOW**: Pengisian presensi kelas, jurnal harian guru, dan pencatatan anecdotes siswa.
- **START PAGE**: [https://presensi-smpn1biau.zahradev.id/login](https://presensi-smpn1biau.zahradev.id/login)
- **TARGET PAGE**: `/teacher/dashboard`, `/teacher/attendance/dashboard`, `/teacher/anecdotes`
- **VERIFIED LIVE STATUS**: **200 OK**
- **READ-ONLY VERIFICATION**: Membuka jadwal kelas & form anecdotes tanpa menekan submit mutasi.
- **MASKING REQUIRED**: **YES** (Masking nama siswa pada tangkapan layar publik).

---

### 3.2 Role: Orang Tua / Wali Siswa (`PARENT_EVIDENCE`)

- **ACCOUNT**: `awaludin914@guru.smp.belajar.id`
- **WORKFLOW**: Pemantauan kehadiran anak secara real-time & verifikasi relasi orang tua.
- **START PAGE**: [https://presensi-smpn1biau.zahradev.id/login](https://presensi-smpn1biau.zahradev.id/login)
- **TARGET PAGE**: `/parent/onboarding`, `/parent/dashboard`
- **VERIFIED LIVE STATUS**: **200 OK** (Penegakan sistem 3-step onboarding terverifikasi).
- **READ-ONLY VERIFICATION**: Membuka alur onboarding tanpa mengirimkan klaim siswa baru.
- **MASKING REQUIRED**: **YES** (Masking NISN dan nomor kontak).

---

### 3.3 Role: Kepala Sekolah (`PRINCIPAL_EVIDENCE`)

- **ACCOUNT**: `kepsek@admin.com` (`Marlinda, S.Pd`)
- **WORKFLOW**: Executive monitoring persentase kehadiran sekolah & supervisi jurnal guru.
- **START PAGE**: [https://presensi-smpn1biau.zahradev.id/login](https://presensi-smpn1biau.zahradev.id/login)
- **TARGET PAGE**: `/principal/dashboard`
- **VERIFIED LIVE STATUS**: **200 OK**
- **READ-ONLY VERIFICATION**: Membuka dashboard executive tanpa mutasi.
- **MASKING REQUIRED**: **YES** (Masking nama guru pada jurnal mengajar).

---

### 3.4 Role: Satpam / Piket Scanner (`SATPAM_EVIDENCE`)

- **ACCOUNT**: `satpam@siasek.com` (`Syukur`)
- **WORKFLOW**: Pengoperasian Kiosk Scanner & shortcut intervensi presensi gerbang.
- **START PAGE**: [https://presensi-smpn1biau.zahradev.id/login](https://presensi-smpn1biau.zahradev.id/login)
- **TARGET PAGE**: `/admin/dashboard`, `/scanner`
- **VERIFIED LIVE STATUS**: **200 OK** (Path `/scanner` terverifikasi memerlukan login).
- **READ-ONLY VERIFICATION**: Membuka antarmuka scanner tanpa melakukan scan siswa riil.
- **MASKING REQUIRED**: **NO** (Interface scanner umum).

---

## 4. Daftar Variabel Environment (Secure Credential Storage)

Konfigurasi kredensial live dibaca dari `.env.siasek-bos` secara runtime:

```env
SIASEK_URL=https://presensi-smpn1biau.zahradev.id
SIASEK_EVIDENCE_USERNAME=siasek_evidence@example.com
SIASEK_VIEWER_EMAIL=kepsek@admin.com
SIASEK_TEACHER_EMAIL=elianaputri1988@gmail.com
SIASEK_HOMEROOM_EMAIL=elianaputri1988@gmail.com
SIASEK_PARENT_EMAIL=awaludin914@guru.smp.belajar.id
SIASEK_PRINCIPAL_EMAIL=satpam@siasek.com
```

> [!CAUTION]
> Dilarang keras meng-commit `.env.siasek-bos` atau mencetak nilai password ke dalam Git/laporan.
