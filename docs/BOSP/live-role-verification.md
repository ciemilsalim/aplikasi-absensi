# Laporan Verifikasi Role Live Production — SIASEK

**Tanggal Verifikasi**: 27 September 2026  
**Target Aplikasi**: SIASEK Live Production ([https://presensi-smpn1biau.zahradev.id](https://presensi-smpn1biau.zahradev.id))  
**Metode Verifikasi**: Browser Automation & Read-Only Live Role Verification  

---

## 1. Executive Summary

Verifikasi peran (role) secara langsung pada aplikasi SIASEK LIVE telah dilaksanakan untuk seluruh akun riil yang terdaftar pada `.env.siasek-bos`. Seluruh proses berjalan secara **READ-ONLY 100%** tanpa melakukan mutasi data, pembuatan akun baru, pengubahan role, maupun pengiriman form.

```text
ROLE SUDAH LIVE VERIFIED       : 5 Roles (Viewer, Teacher/Wali Kelas, Parent, Principal, Satpam/Piket)
ROLE BELUM LIVE VERIFIED       : 0 Roles
ROLE YANG MEMBUTUHKAN AKUN ASLI: Teacher/Wali Kelas, Parent, Principal, Satpam
ROLE YANG CUKUP DENGAN VIEWER  : Audit BOSP, Report Analytics, Monitoring Manajerial
ROLE YANG TIDAK MEMBUTUHKAN AKUN: Student (Diwakili QR Card/Parent), Operator/TU (Covered by Admin/Satpam)
WORKFLOW OPERASIONAL VERIFIED  : 6 Primary Workflows (Audit BOSP, Presensi Guru, Anecdotes Siswa, Onboarding Parent, Executive Principal, Gate Scanner)
TOTAL LIVE SCREENSHOTS         : 14 Tangkapan Layar (Di simpan di docs/BOSP/live-evidence/)
```

---

## 2. Classification of Roles (FASE 1)

Dalam arsitektur SIASEK, peranan pengguna diklasifikasikan ke dalam 3 lapisan:

1. **Role Database (`users.role`)**: Kolom dasar di tabel `users` (`admin`, `teacher`, `guru`, `parent`, `user`, `student`, `viewer`).
2. **Role Functional (Middleware & Permissions)**: Penentu akses route via `AdminMiddleware`, `TeacherMiddleware`, `ParentMiddleware`, `CheckRoleMiddleware`, `ScannerAccessMiddleware`.
3. **Role Assignment (Atribut Relasional)**: Tugas tambahan yang melekat pada model (misalnya: Guru yang di-assign sebagai **Wali Kelas 7D** via relasi `homeroomClass`, atau Orang Tua yang terikat dengan relasi anak).

---

## 3. Matriks Hasil Verifikasi Role Live (FASE 2, 3, & 4)

| ROLE | ACCOUNT ALIAS | ROLE ACTUAL | LOGIN STATUS | INITIAL REDIRECT | LIVE DATA TERLIHAT | EVIDENCE STATUS | SCREENSHOT BUKTI | MASKING REQUIRED |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Viewer** | `VIEWER_EVIDENCE` | `viewer` | **SUCCESS** | `/admin/dashboard` | 368 Siswa, 26 Guru, 10 Jurnal, 219 Request Izin | **LIVE OPERATIONAL EVIDENCE** | `bukti_01` s/d `bukti_07` | **YES** |
| **Kepala Sekolah** | `PRINCIPAL_EVIDENCE` | `kepala_sekolah` | **SUCCESS** | `/principal/dashboard` | Marlinda, S.Pd (Executive Overview, Rata-rata 14 Hari 59.1%, P5 & Ekskul) | **LIVE OPERATIONAL EVIDENCE** | `bukti_14_kepsek_dashboard.png` | **YES** |
| **Guru & Wali Kelas** | `TEACHER_EVIDENCE` | `teacher` + Wali Kelas 7D | **SUCCESS** | `/scanner` | Elyana Saputri Agung, S.Pd, Gr (Jadwal Mengajar, Absensi Saya, Anecdotes Siswa) | **LIVE OPERATIONAL EVIDENCE** | `bukti_08` s/d `bukti_10` | **YES** |
| **Orang Tua** | `PARENT_EVIDENCE` | `parent` | **SUCCESS** | `/parent/onboarding` | System Enforcement: 3-Step Verification Onboarding Flow for Unlinked Parents | **LIVE OPERATIONAL EVIDENCE** | `bukti_11` & `bukti_12` | **YES** |
| **Satpam / Piket** | `SATPAM_EVIDENCE` | `user` / Satpam | **SUCCESS** | `/admin/dashboard` | Syukur (Shortcut Scan Kehadiran, Scan Izin Keluar, Intervensi TU) | **LIVE OPERATIONAL EVIDENCE** | `bukti_13_satpam_dashboard.png` | **YES** |
| **Scanner Kiosk** | `SCANNER_KIOSK` | Protected Endpoint | **REDIRECT** | `/login` | Verified: Path `/scanner` memerlukan autentikasi login (Auth Protected) | **LIVE ACCESS VERIFIED** | N/A | **NO** |

---

## 4. Analisis Khusus Per Role (FASE 5 - FASE 10)

### 4.1 Kepala Sekolah (FASE 5)
- **Email Login**: `kepsek@admin.com` (`Marlinda, S.Pd`).
- **Role Badge**: `Kepala Sekolah`.
- **Temuan**: Saat login, Kepala Sekolah langsung dialihkan ke `/principal/dashboard`. Fitur dan metrik yang tampil identik dengan tampilan principal pada akun `viewer`, namun akun `kepsek@admin.com` memberikan **LIVE OPERATIONAL EVIDENCE** resmi untuk identitas Kepala Sekolah.

### 4.2 Wali Kelas (FASE 6)
- **Email Login**: `elianaputri1988@gmail.com` (`Elyana Saputri Agung, S.Pd, Gr`).
- **Temuan**: Wali Kelas bukanlah tabel role terpisah di database, melainkan **Assignment Atribut** (`Teacher` yang memiliki `homeroomClass` Kelas 7D). 
- **Kesimpulan**: Satu akun guru asli ini terverifikasi mampu menjalankan dua workflow sekaligus (Guru Mapel & Wali Kelas). `NO SEPARATE HOMEROOM ACCOUNT CREATION REQUIRED`.

### 4.3 Orang Tua / Parent (FASE 7)
- **Email Login**: `awaludin914@guru.smp.belajar.id`.
- **Temuan**: Pengujian membuktikan penerapan middleware `EnsureParentOnboardingCompleted`. Akun orang tua yang belum menyelesaikan klaim relasi anak secara otomatis diarahkan ke alur verifikasi 3-langkah (`/parent/onboarding`).
- **Verifikasi Read-Only**: Tidak ada submit klaim atau pengajuan izin baru yang dikirimkan.

### 4.4 Siswa / Student (FASE 8)
- **Temuan**: Siswa berinteraksi dengan sistem menggunakan Kartu QR Code / Barcode pada Scanner Gerbang atau perangkat guru. Tidak ada dashboard web terpisah yang wajib diakses oleh siswa.
- **Kesimpulan**: `NO_SEPARATE_EVIDENCE_ACCOUNT_REQUIRED`.

### 4.5 Operator / TU / Satpam (FASE 9 & 10)
- **Email Login Satpam**: `satpam@siasek.com` (`Syukur`).
- **Temuan Kiosk Scanner**: Pengujian navigasi anonim ke `/scanner` membuktikan bahwa antarmuka scanner terlindungi oleh autentikasi (`Auth Protected`). Saat login sebagai Satpam/Guru, antarmuka scanner siap digunakan.
- **Verifikasi Read-Only**: Zero scan siswa dilakukan selama pengujian.

---

## 5. Daftar Tangkapan Layar Evidence (FASE 11)

Seluruh 14 file tangkapan layar bukti live disimpan pada folder [`docs/BOSP/live-evidence/`](file:///d:/laragon/www/siasek/aplikasi-absensi/docs/BOSP/live-evidence/):

1. `bukti_01_dashboard.png` — Viewer Admin Dashboard (368 Siswa Aktif) [MANAGERIAL]
2. `bukti_02_principal_dashboard.png` — Viewer Executive Principal Dashboard [MANAGERIAL]
3. `bukti_03_laporan_presensi.png` — Rekap Laporan Presensi [MANAGERIAL]
4. `bukti_04_analytics_charts.png` — Analytics Kehadiran Kelas 7A [MANAGERIAL]
5. `bukti_05_supervisi_jurnal.png` — Supervisi Jurnal Pembelajaran Guru [MANAGERIAL]
6. `bukti_06_parent_verifications.png` — Antrean Verifikasi Orang Tua [MANAGERIAL]
7. `bukti_07_leave_requests.png` — Manajemen Izin/Sakit Siswa [MANAGERIAL]
8. `bukti_08_teacher_dashboard.png` — Dashboard Guru & Wali Kelas 7D (Elyana Saputri Agung, S.Pd, Gr) [OPERATIONAL]
9. `bukti_09_teacher_attendance.png` — Dashboard Absensi Saya Guru [OPERATIONAL]
10. `bukti_10_teacher_anecdotes.png` — Form Catatan Anecdotes Siswa Guru [OPERATIONAL]
11. `bukti_11_parent_dashboard.png` — Portal Orang Tua & Alur Onboarding Verifikasi [OPERATIONAL]
12. `bukti_12_parent_leave_requests.png` — Form Pengajuan Izin/Sakit Orang Tua [OPERATIONAL]
13. `bukti_13_satpam_dashboard.png` — Dashboard Satpam/Piket (Syukur) [OPERATIONAL]
14. `bukti_14_kepsek_dashboard.png` — Executive Dashboard Kepala Sekolah (Marlinda, S.Pd) [MANAGERIAL]

---

## 6. Privacy & Masking Policy (FASE 12)

- **Sensitive Data Detected**: Nama Siswa, Nama Guru, Kelas, dan Status Kehadiran.
- **Masking Standard**: `MASKING_REQUIRED = YES`. Seluruh tangkapan layar yang akan dilampirkan pada laporan publik BOSP wajib menerapkan penutupan/blur pada nama siswa, NISN, dan nomor kontak pribadi.
- **Zero Production Mutation**: Seluruh proses verifikasi dilaksanakan tanpa menulis atau mengubah database production.
