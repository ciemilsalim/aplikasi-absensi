# PEMBARUAN FITUR SIASEK — September 2026

**Periode Repository Audit**: 2026-09-01 s.d. 2026-09-27  
**Git Commit Cutoff**: `c422e82`  

---

## 1. Klasifikasi Perubahan Aplikasi vs. Infrastruktur Evidence

> [!IMPORTANT]  
> Sesuai ketentuan audit, perubahan repositori dipisahkan secara tegas antara **Fitur Aplikasi SIASEK (Bisnis)** dengan **Infrastruktur Audit / Tooling Evidence**.

---

## 2. Tabel Pembaruan Fitur Aplikasi (Business Features)

| FEATURE | ROLE | CHANGE TYPE | GIT EVIDENCE (COMMIT/FILES) | LIVE STATUS | SCREENSHOT | NOTES |
| :--- | :--- | :---: | :--- | :--- | :--- | :--- |
| Viewer Role & Evidence Authorization Access | Viewer / Auditor | **NEW** | Commit 7940af5 / routes/web.php (role:viewer middleware) | LIVE_EVIDENCE | `bukti_01_dashboard.png` | Hak akses read-only khusus audit BOSP di `/admin/*` |
| Executive Principal Dashboard Metrics | Kepala Sekolah | **UPDATED** | Commit 7940af5 / PrincipalDashboardController.php | LIVE_EVIDENCE | `bukti_14_kepsek_dashboard.png` | Tampilan executive overview persentase kehadiran 14 hari & supervisi |
| Parent Onboarding & Verification Enforcer | Orang Tua | **UPDATED** | EnsureParentOnboardingCompleted.php | LIVE_EVIDENCE | `bukti_11_parent_dashboard.png` | Sistem penegakan verifikasi 3-langkah klaim anak binaan |
| Presensi Pembelajaran & Anecdotes Guru | Guru & Wali Kelas | **ACTIVE** | Teacher/DashboardController.php | LIVE_EVIDENCE | `bukti_08_teacher_dashboard.png` | Management presensi jam pelajaran & catatan anecdotes kelas 7D |


---

## 3. Tabel Pembaruan Infrastruktur Evidence & Tooling (Non-Business)

| ITEM / TOOLING | KATEGORI | GIT COMMIT / FILES | ALASAN & RISIKO |
| :--- | :--- | :--- | :--- |
| Skill `siasek-bos` & Live Billing Scraper | EVIDENCE INFRASTRUCTURE | `.agents/skills/siasek-bos/*` | Perangkat otomatisasi penyusunan paket bukti BOSP & live scraper |
| Laporan Matriks Peran & Registry Akun Evidence | DOCUMENTATION | `docs/BOSP/*` | Dokumentasi audit read-only dan pemetaan role evidence |

