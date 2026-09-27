# AUDIT AKSES ROLE VIEWER & DOKUMENTASI EVIDENCE SIASEK

**Aplikasi:** SIASEK (Sistem Informasi & Absensi Sekolah)  
**Project Path:** `D:\laragon\www\siasek\aplikasi-absensi`  
**Live Application URL:** `https://presensi-smpn1biau.zahradev.id`  
**Tanggal Audit:** 27 September 2026  
**Status Audit:** COMPLETED & VERIFIED (EMAIL LOGIN IDENTIFIER)  

---

## 1. STRUKTUR AUTENTIKASI & AUTHORIZATION (FASE 1)

### 1.1 Model & Tabel Database
- **Model User:** `App\Models\User` (`app/Models/User.php`)
- **Login Identifier:** `email` (`siasek_evidence@example.com`)
- **Kolom Peran Lokal:** `users.role` (varchar, default: `'user'`)
- **Integration Role:** Model `User` menggunakan metode `hasAnyRole($roles)` dan `hasRole($role)` yang secara cerdas memeriksa:
  1. Tabel Spatie Permission (`model_has_roles` dan `roles`) jika ada.
  2. Fallback ke kolom string lokal `users.role` (case-insensitive).

### 1.2 Spatie Role & Model Linkage
- **Role Name:** `viewer`
- **Spatie Role ID:** 18 (`roles` table, `guard_name: web`)
- **User Account:**
  - **User ID:** 227
  - **Display Name:** `SIASEK Evidence`
  - **Login Email:** `siasek_evidence@example.com`
  - **Role Lokal:** `viewer`
  - **Spatie Pivot:** Registered di `model_has_roles` (`model_id: 227`, `role_id: 18`, `model_type: App\Models\User`)

### 1.3 Middleware Authorization & Redirection
- **Middleware Class:** `App\Http\Middleware\CheckRoleMiddleware` (`app/Http/Middleware/CheckRoleMiddleware.php`)
- **Middleware Alias:** `'role' => \App\Http\Middleware\CheckRoleMiddleware::class` (didaftarkan di `bootstrap/app.php`)
- **Dashboard Redirection Logic (`routes/web.php`):**
  Rute `/dashboard` memeriksa peran pengguna yang login:
  - Role `kepala_sekolah` / `headmaster` -> Redirect ke `/principal/dashboard`
  - Role `admin` / `operator` / `satpam` / `viewer` -> Redirect ke `/admin/dashboard`
  - Role `parent` -> Redirect ke `/parent.dashboard`
  - Role `teacher` -> Redirect ke `/teacher.dashboard`
  - Role tidak dikenal -> Logout otomatis dengan pesan error.

---

## 2. HASIL AUDIT AKSES ROLE VIEWER (FASE 2)

Tabel berikut menyajikan pemetaan lengkap halaman/menu yang dapat diakses oleh role `viewer` beserta tingkat otorisasi (READ-ONLY) dan relevansi untuk dokumentasi/evidence aplikasi:

| MENU | ROUTE NAME | ROUTE PATH | ACCESS VIEWER | READ / WRITE | RELEVANSI EVIDENCE |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Dasbor Utama Admin** | `admin.dashboard` | `/admin/dashboard` | ALLOWED | READ-ONLY | **SANGAT TINGGI** (Evidence ringkasan statistik kehadiran siswa & grafik harian) |
| **Dasbor Eksekutif (Kepala Sekolah)** | `principal.dashboard` | `/principal/dashboard` | ALLOWED | READ-ONLY | **SANGAT TINGGI** (Evidence pemantauan eksekutif & BOSP) |
| **Laporan & Rekap Kehadiran Siswa** | `admin.reports.create` | `/admin/reports` | ALLOWED | READ-ONLY | **SANGAT TINGGI** (Evidence rekap harian/bulanan presensi siswa) |
| **Visualisasi Analytics Presensi** | `admin.reports.charts` | `/admin/reports/charts` | ALLOWED | READ-ONLY | **SANGAT TINGGI** (Evidence grafik analytics & tren kehadiran) |
| **Supervisi Jurnal Mengajar Guru** | `admin.teaching_journals.index` | `/admin/teaching-journals` | ALLOWED | READ-ONLY | **SANGAT TINGGI** (Evidence pengawasan jurnal kegiatan mengajar guru) |
| **Detail Jurnal Mengajar Guru** | `admin.teaching_journals.show` | `/admin/teaching-journals/teacher/{id}` | ALLOWED | READ-ONLY | **TINGGI** (Evidence rincian materi & absensi per jam pelajaran) |
| **Verifikasi Klaim Orang Tua** | `admin.parent_verification.index` | `/admin/parent-verifications` | ALLOWED | READ-ONLY | **SEDANG** (Evidence verifikasi akun wali murid) |
| **Daftar Pengajuan Izin Siswa** | `admin.leave_requests.index` | `/admin/leave-requests` | ALLOWED | READ-ONLY | **SEDANG** (Evidence daftar permohonan izin/sakit siswa) |
| **Panduan Penggunaan Sistem** | `guide` | `/guide` | ALLOWED | READ-ONLY | **SEDANG** (Evidence panduan penggunaan PWA/sistem) |
| **Tentang Aplikasi** | `about` | `/about` | ALLOWED | READ-ONLY | **SEDANG** (Evidence profil versi SIASEK) |
| **Profil Pengguna** | `profile.edit` | `/profile` | ALLOWED | READ-ONLY | **RENDAH** |
| **Simpan / Edit Izin Siswa Manual** | `admin.leave_requests.store_manual` | `/admin/leave-requests/manual` | ❌ BLOCKED | WRITE (403) | N/A (Keamanan terjaga) |
| **Persetujuan Izin Siswa** | `admin.leave_requests.approve` | `/admin/leave-requests/{id}/approve` | ❌ BLOCKED | WRITE (403) | N/A (Keamanan terjaga) |
| **Penolakan Izin Siswa** | `admin.leave_requests.reject` | `/admin/leave-requests/{id}/reject` | ❌ BLOCKED | WRITE (403) | N/A (Keamanan terjaga) |
| **Verifikasi Jurnal Guru** | `admin.teaching_journals.verify` | `/admin/teaching-journals/{id}/verify` | ❌ BLOCKED | WRITE (403) | N/A (Keamanan terjaga) |
| **Persetujuan Klaim Orang Tua** | `admin.parent_verification.approve` | `/admin/parent-verifications/{id}/approve` | ❌ BLOCKED | WRITE (403) | N/A (Keamanan terjaga) |
| **Manajemen User / CRUD Admin** | `admin.users.*` | `/admin/users` | ❌ BLOCKED | WRITE (403) | N/A (Keamanan terjaga) |
| **Pengaturan Identitas & Logo** | `admin.settings.*` | `/admin/settings/*` | ❌ BLOCKED | WRITE (403) | N/A (Keamanan terjaga) |

---

## 3. HASIL VERIFIKASI LOKAL & OTORISASI (FASE 4)

Pengujian empiris dilakukan pada runtime Laravel untuk memastikan integritas dan keamanan akun `siasek_evidence@example.com`:

1. **Verifikasi Peran & Akun Tunggal:**  
   - `$user->email === 'siasek_evidence@example.com'` -> **VERIFIED**
   - `$user->role === 'viewer'` -> **VERIFIED**
   - `$user->hasRole('viewer')` -> **VERIFIED (`true`)**
   - `$user->hasRole('admin')` -> **VERIFIED (`false`)** (Tidak memiliki akses admin/CRUD)
   - Total Akun Evidence di Database $\rightarrow$ **Tepat 1 Akun (No Duplicates)**

2. **Verifikasi Akses Read-Only:**  
   - `GET /dashboard` -> Status `302` (Redirect aman ke `/admin/dashboard`)
   - `GET /admin/dashboard` -> Status `200 OK`
   - `GET /principal/dashboard` -> Status `200 OK`
   - `GET /admin/reports` -> Status `200 OK`
   - `GET /admin/reports/charts` -> Status `200 OK`
   - `GET /admin/teaching-journals` -> Status `200 OK`
   - `GET /admin/parent-verifications` -> Status `200 OK`
   - `GET /admin/leave-requests` -> Status `200 OK`

3. **Verifikasi Penolakan Mutasi Data (Read-Only Enforcement):**  
   - `POST /admin/leave-requests/manual` -> Status `403 Forbidden`
   - `POST /admin/leave-requests/{id}/approve` -> Status `403 Forbidden`
   - `POST /admin/teaching-journals/{id}/verify` -> Status `403 Forbidden`
   - `POST /admin/parent-verifications/{id}/approve` -> Status `403 Forbidden`
   - `POST /admin/users` -> Status `403 Forbidden`

---

## 4. VERIFIKASI APLIKASI LIVE (LIVE APPLICATION VERIFICATION)

### 4.1 Status Login Live Environment
- **Target URL:** `https://presensi-smpn1biau.zahradev.id/login`
- **Uji Login Browser Subagent:** Dilakukan menggunakan credential email `siasek_evidence@example.com` dari `.env.siasek-bos`.
- **Hasil Uji Login:** Server live mengembalikan pesan `auth.failed` (*"These credentials do not match our records."*).
- **Akses Rute Terproteksi Langsung:** Navigasi ke `https://presensi-smpn1biau.zahradev.id/admin/dashboard` mengembalikan `302 Redirect` ke `/login`.

### 4.2 Analisis & Temuan (Findings)
- **Penyebab:** Perubahan kode (`routes/web.php` & `sidebar.blade.php`), Artisan command `siasek:create-evidence-user`, dan migrasi database (`2026_09_27_000001_create_viewer_role_and_evidence_account.php`) baru diuji di lokal dan belum di-deploy ke server produksi live (`presensi-smpn1biau.zahradev.id`).
- **Solusi Deployment:** Untuk mengaktifkan akun evidence di server live:
  1. Commit & push perubahan kode dan migrasi ke repository utama.
  2. Jalankan `git pull` & `php artisan migrate --force` (atau `php artisan siasek:create-evidence-user`) di server produksi live.

---

## 5. REKOMENDASI CANDIDATE SCREENSHOT EVIDENCE (BOSP)

Berikut adalah daftar 5 halaman prioritas utama untuk screenshot bukti pemanfaatan aplikasi SIASEK:

| NAMA BUKTI | URL HALAMAN | RELEVANSI BOSP | ELEMEN DATA UTAMA | REKOMENDASI MASKING |
| :--- | :--- | :--- | :--- | :--- |
| `bukti_01_dasbor_utama_presensi` | `/admin/dashboard` | Kehadiran Siswa Real-time | Card statistik, grafik kehadiran harian, persentase alpa/sakit/izin | Masking nama siswa pada widget aktivitas terbaru |
| `bukti_02_dasbor_eksekutif_pimpinan` | `/principal/dashboard` | Pemantauan Manajerial Kepala Sekolah | Metrics total siswa, total kelas, summary jurnal mengajar | Bebas masking (data agregat) |
| `bukti_03_laporan_rekap_presensi` | `/admin/reports` | Dokumen Laporan Fisik/PDF | Form filter kelas, tabel rekapitulasi presensi bulanan | Masking kolom NISN/Nama Siswa untuk publikasi |
| `bukti_04_analytics_grafik_trend` | `/admin/reports/charts` | Analisis Data Kehadiran | Chart interaktif mingguan/bulanan, breakdown status | Bebas masking (grafik statistik) |
| `bukti_05_supervisi_jurnal_mengajar` | `/admin/teaching-journals` | Supervisi Pembelajaran KBM | Tabel jurnal mengajar guru, jam pelajaran, mata pelajaran | Bebas masking (nama guru & mapel) |

---

## 6. REKOMENDASI PENYIMPANAN CREDENTIAL & AUTOMATION

### 6.1 Credential Safe Storage
- **Environment Variable (`.env.siasek-bos`):**
  ```env
  SIASEK_URL=https://presensi-smpn1biau.zahradev.id
  SIASEK_EVIDENCE_EMAIL=siasek_evidence@example.com
  SIASEK_EVIDENCE_PASSWORD=<Secure_Password_Here>
  ```
- **Aturan Keamanan:**  
  Password plaintext **TIDAK** pernah di-commit ke Git repository, source code, seeder, maupun file dokumentasi publik.
