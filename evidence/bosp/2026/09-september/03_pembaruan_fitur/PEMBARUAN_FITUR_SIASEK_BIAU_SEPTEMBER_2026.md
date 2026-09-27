# PEMBARUAN FITUR SIASEK — September 2026

**Periode Repository Audit**: 2026-09-01 s.d. 2026-09-27  
**Git Commit Cutoff**: `a2d4390`  

---

## 1. Klasifikasi Perubahan Aplikasi vs. Infrastruktur Evidence

> [!IMPORTANT]  
> Sesuai ketentuan audit, perubahan repositori dipisahkan secara tegas antara **Fitur Aplikasi SIASEK (Bisnis)** dengan **Infrastruktur Audit / Tooling Evidence**.

---

## 2. Tabel Pembaruan Fitur Aplikasi (Business Features)

| FEATURE | ROLE | CHANGE TYPE | GIT EVIDENCE (COMMIT/FILES) | LIVE STATUS | SCREENSHOT | NOTES |
| :--- | :--- | :---: | :--- | :--- | :--- | :--- |
| Admin Manual Leave Intervention & Attendance Sync | Admin / TU (ADMIN_EVIDENCE) | **UPDATED** | Commit fcb90b6 / Admin/LeaveRequestController.php | LIVE_EVIDENCE | `bukti_02_admin_leave_september_2026.png` | Modul intervensi pengajuan izin siswa & auto-sync presensi |
| Subject-Based Attendance Tracking & Reporting | Guru Mapel / Wali Kelas (TEACHER_EVIDENCE) | **UPDATED** | Commit 96660f4 / SubjectAttendanceController.php | LIVE_EVIDENCE | `bukti_08_teacher_dashboard.png` | Pencatatan presensi per jam pelajaran & rekapitulasi guru |
| Executive Principal Dashboard Overview | Kepala Sekolah (PRINCIPAL_EVIDENCE) | **ACTIVE** | Commit 7940af5 / PrincipalDashboardController.php | LIVE_EVIDENCE | `bukti_14_kepsek_dashboard.png` | Tampilan executive overview persentase kehadiran 14 hari & supervisi |
| Parent Onboarding & Verification Enforcer | Orang Tua (PARENT_EVIDENCE) | **ACTIVE** | EnsureParentOnboardingCompleted.php | LIVE_EVIDENCE | `bukti_11_parent_dashboard.png` | Sistem penegakan verifikasi 3-langkah klaim anak binaan |
| Gate Scanner Kiosk Interface | Satpam / Piket (SATPAM_EVIDENCE) | **ACTIVE** | AttendanceController.php | LIVE_EVIDENCE | `bukti_13_satpam_dashboard.png` | Antarmuka scanner kiosk presensi gerbang kedatangan/kepulangan |


---

## 3. Tabel Pembaruan Infrastruktur Evidence & Tooling (Non-Business)

| ITEM / TOOLING | KATEGORI | GIT COMMIT / FILES | ALASAN & RISIKO |
| :--- | :--- | :--- | :--- |
| Viewer Role Read-Only Authorization Access | EVIDENCE INFRASTRUCTURE | `Commit 7940af5 / routes/web.php (role:viewer middleware)` | Hak akses khusus audit read-only BOSP di `/admin/*` |
| Skill `siasek-bos` & Live Billing Scraper | EVIDENCE INFRASTRUCTURE | `.agents/skills/siasek-bos/*` | Perangkat otomatisasi penyusunan paket bukti BOSP & live scraper |
| Laporan Matriks Peran & Registry Akun Evidence | DOCUMENTATION | `docs/BOSP/*` | Dokumentasi audit read-only dan pemetaan role evidence |

