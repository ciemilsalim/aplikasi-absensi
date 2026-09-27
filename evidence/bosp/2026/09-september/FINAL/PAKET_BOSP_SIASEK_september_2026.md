# PAKET DOKUMEN PENDUKUNG BOSP LAYANAN SIASEK — September 2026

> [!IMPORTANT]
> Dokumen ini merupakan Paket Bukti Penyedia Layanan Jasa SIASEK untuk mendukung LPJ BOSP Sekolah.
> ***Bukti pembayaran dilampirkan oleh pihak sekolah.***

---

# INVOICE / TAGIHAN LAYANAN SIASEK

**Nomor Invoice**: INV-SIASEK/2026/09/001  
**Tanggal**: 2026-09-27  
**Periode Layanan**: September 2026 (2026-09-01 s.d. 2026-09-27)  

---

### ITEM LAYANAN

| No | Deskripsi Layanan | Jumlah Siswa (User) | Tarif / Siswa / Bulan | Total Tagihan |
| :---: | :--- | :---: | :---: | :---: |
| 1 | **Jasa Layanan Penggunaan Aplikasi Presensi SIASEK**<br>Layanan SaaS Presensi Digital, Portal Orang Tua, Executive Analytics & Supervisi Jurnal Guru | 163 Siswa | Rp1.000 | **Rp163.000** |

**Terbilang**: *Seratus Enam Puluh Tiga Ribu  Rupiah*  

---

### PIHAK PENYEDIA & PELANGGAN

**Penyedia Layanan (Provider)**:  
**ZahraDev**  
Pengembang: Emil Salim, S.Kom  
Kontak: emil@zahradev.id  

**Pelanggan (Customer)**:  
**SMP Negeri 1 Biau**  
Alamat: Jl. Pendidikan No. 1 Biau, Kabupaten Buol  

---

### INSTRUKSI PEMBAYARAN

- **Bank**: Bank Central Asia (BCA) / Bank SulutGo  
- **Nomor Rekening**: 123-456-7890  
- **Atas Nama**: Emil Salim / ZahraDev  

> [!NOTE]  
> *Dokumen ini merupakan invoice resmi penagihan jasa layanan penggunaan aplikasi dari pihak penyedia.*  
> ***Bukti pembayaran dilampirkan oleh pihak sekolah.***


---

# RINCIAN PEMANFAATAN LAYANAN SIASEK — September 2026

**Nama Layanan**: Jasa Layanan Penggunaan Aplikasi Presensi SIASEK  
**Aplikasi**: SIASEK (Sistem Informasi & Absensi Sekolah)  
**URL Live**: https://presensi-smpn1biau.zahradev.id  
**Periode Audit**: 2026-09-01 s.d. 2026-09-27 (Evidence Cutoff: 2026-09-27)  
**Pelanggan**: SMP Negeri 1 Biau  

---

## 1. Ringkasan Pemanfaatan Layanan

Aplikasi SIASEK dimanfaatkan oleh sekolah untuk mendukung pengelolaan presensi digital, jurnal pembelajaran guru, pengawasan manajerial kepala sekolah, serta keterlibatan orang tua siswa.

---

## 2. Rincian Pemanfaatan Berdasarkan Role Pengguna

| Role Pengguna | Workflow Utama | Fitur Teruji | Status Verification | Live Evidence Ref |
| :--- | :--- | :--- | :--- | :--- |
| **Viewer / Auditor** | Monitoring Manajerial & Rekap BOSP | Dashboard Admin, Rekap Reports, Charts | **LIVE OPERATIONAL EVIDENCE** | `bukti_01` s/d `bukti_07` |
| **Kepala Sekolah** | Executive Monitoring & Attendance Trends | Executive Dashboard, Supervisi Jurnal | **LIVE OPERATIONAL EVIDENCE** | `bukti_14_kepsek_dashboard.png` |
| **Guru & Wali Kelas** | Input Presensi Mapel & Anecdotes Siswa | Dashboard Guru, Absensi Saya, Anecdotes | **LIVE OPERATIONAL EVIDENCE** | `bukti_08` s/d `bukti_10` |
| **Orang Tua** | Portal Monitoring Anak & Enforced Onboarding | 3-Step Student Claim Onboarding | **LIVE OPERATIONAL EVIDENCE** | `bukti_11` & `bukti_12` |
| **Satpam / Piket** | Gate Scanner & Shortcut Intervensi | Kiosk Scanner & Gate Access | **LIVE OPERATIONAL EVIDENCE** | `bukti_13_satpam_dashboard.png` |

---

## 3. Privasi & Masking Data

Tangkapan layar pada paket ini telah dievaluasi dengan kebijakan `MASKING_REQUIRED = YES`. Seluruh data pribadi siswa (nama, NISN, nomor telepon) yang dipublikasikan telah dilindungi sesuai pedoman kerahasiaan data sekolah.


---

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
| Skill `siasek-bos` & Generator BOSP | EVIDENCE INFRASTRUCTURE | `.agents/skills/siasek-bos/*` | Perangkat otomatisasi penyusunan paket bukti BOSP |
| Laporan Matriks Peran & Registry Akun Evidence | DOCUMENTATION | `docs/BOSP/*` | Dokumentasi audit read-only dan pemetaan role evidence |



---

# EVIDENCE INDEX — September 2026

**Periode Evidence**: 2026-09-01 s.d. 2026-09-27  
**Aplikasi**: SIASEK Live ([https://presensi-smpn1biau.zahradev.id](https://presensi-smpn1biau.zahradev.id))  

---

## Tabel Indeks Bukti (Evidence Index)

| ID | FEATURE | ROLE | ROUTE | EVIDENCE TYPE | GIT REFERENCE | SCREENSHOT REF | STATUS |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| EV-01 | Viewer Role & Evidence Authorization Access | Viewer / Auditor | `/admin/*` | LIVE_EVIDENCE | `c422e82` | `bukti_01_dashboard.png` | **PASSED** |
| EV-02 | Executive Principal Dashboard Metrics | Kepala Sekolah | `/admin/*` | LIVE_EVIDENCE | `c422e82` | `bukti_14_kepsek_dashboard.png` | **PASSED** |
| EV-03 | Parent Onboarding & Verification Enforcer | Orang Tua | `/admin/*` | LIVE_EVIDENCE | `c422e82` | `bukti_11_parent_dashboard.png` | **PASSED** |
| EV-04 | Presensi Pembelajaran & Anecdotes Guru | Guru & Wali Kelas | `/admin/*` | LIVE_EVIDENCE | `c422e82` | `bukti_08_teacher_dashboard.png` | **PASSED** |

