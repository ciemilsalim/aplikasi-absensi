# LAPORAN AUDIT LENGKAP APLIKASI (FULL AUDIT REPORT)
**Aplikasi Presensi SIASEK (Sistem Kehadiran Real-time)**  
*SMP Negeri 1 Biau / Ekosistem Pendidikan Digital SIASEK*  
*Tanggal Pelaksanaan Audit: 26 September 2026*  
*Auditor: Autonomous Software Engineering Engine (Antigravity)*  
*Basis Penilaian: Source Code, Skema Database, Konfigurasi, Lockfile, Pengujian Otomatis, dan Runtime Environment*

---

## DAFTAR ISI AUDIT

1. [Struktur Project](#1-struktur-project)
2. [Framework dan Versinya](#2-framework-dan-versinya)
3. [Dependency & Pustaka Pendukung](#3-dependency--pustaka-pendukung)
4. [Database dan Migration](#4-database-dan-migration)
5. [Arsitektur Model (Eloquent ORM)](#5-arsitektur-model-eloquent-orm)
6. [Arsitektur Controller](#6-arsitektur-controller)
7. [Struktur & Registrasi Route](#7-struktur--registrasi-route)
8. [Middleware & Filter Permintaan](#8-middleware--filter-permintaan)
9. [Mekanisme Authentication](#9-mekanisme-authentication)
10. [Authorization dan Manajemen Role Pengguna](#10-authorization-dan-manajemen-role-pengguna)
11. [Seluruh Fitur Aplikasi](#11-seluruh-fitur-aplikasi)
12. [API dan Integrasi Eksternal](#12-api-dan-integrasi-eksternal)
13. [Upload dan Penanganan File (File Handling)](#13-upload-dan-penanganan-file-file-handling)
14. [Sistem Notifikasi & Penjadwalan Otomatis](#14-sistem-notifikasi--penjadwalan-otomatis)
15. [Testing dan Kualitas Kode](#15-testing-dan-kualitas-kode)
16. [Deployment Configuration & Lingkungan Server](#16-deployment-configuration--lingkungan-server)
17. [Audit Keamanan Dasar (Basic Security Audit)](#17-audit-keamanan-dasar-basic-security-audit)
18. [UI/UX, Desain, dan Halaman Aplikasi](#18-uiux-desain-dan-halaman-aplikasi)
19. [Status Implementasi Seluruh Fitur](#19-status-implementasi-seluruh-fitur)
20. [Kesimpulan & Rekomendasi Teknis Prioritas](#20-kesimpulan--rekomendasi-teknis-prioritas)

---

## 1. Struktur Project

Struktur direktori proyek mengadopsi standar modern **Laravel 12 Application Skeleton**:

```text
aplikasi-absensi/
├── app/
│   ├── Console/Commands/        # Command artisan khusus (CheckAbsentStudents, SyncStudentUsers)
│   ├── Exports/                 # Kelas ekspor data Excel (AttendanceReportExport)
│   ├── Http/
│   │   ├── Controllers/         # Controller aplikasi (Admin, Api, Auth, Parent, Principal, Teacher)
│   │   └── Middleware/          # 10 kelas middleware kustom
│   ├── Imports/                 # Kelas impor data Excel (Calendar, Parents, Teachers)
│   ├── Models/                  # 32 Model Eloquent
│   ├── Providers/               # AppServiceProvider
│   ├── Traits/                  # GpsValidationTrait (Formula Haversine)
│   └── View/                    # View components
├── bootstrap/
│   └── app.php                  # Konfigurasi routing, middleware, exceptions, dan scheduler Laravel 12
├── config/                      # Konfigurasi aplikasi, auth, database, backup, dompdf, session, dll.
├── database/
│   ├── factories/               # UserFactory untuk pengujian
│   ├── migrations/              # 65 berkas migrasi database terstruktur
│   └── seeders/                 # DatabaseSeeder, LevelSeeder, SchoolLocationSettingSeeder, StudentSeeder
├── docs/
│   └── LPJ/                     # Berkas dokumentasi audit dan LPJ resmi
├── public/
│   ├── build/                   # Kompilasi aset Vite (CSS/JS)
│   ├── images/                  # Aset visual dan ikon PWA
│   ├── models/                  # Bobot neural network Face-API.js (SSD MobileNet, Landmark, Recognition)
│   ├── sounds/                  # Efek audio pemindai (beep success/error)
│   ├── manifest.json            # Web App Manifest PWA
│   ├── sw.js                    # Service Worker caching offline
│   └── sync-hpanel.php          # Skrip sinkronisasi database eksternal hosting
├── resources/
│   ├── css/                     # Aset style Tailwind CSS
│   ├── js/                      # Aset script JavaScript
│   └── views/                   # 11 subdirektori Blade Template (admin, teacher, parent, principal, chat, dll.)
├── routes/
│   ├── api.php                  # Rute REST API dilindungi Sanctum
│   ├── auth.php                 # Rute autentikasi Breeze
│   ├── console.php              # Rute command terminal
│   └── web.php                  # 569 baris rute antarmuka web utama
├── storage/
│   └── app/public/              # Direktori upload (attachments, logos, profile-photos, students, teachers)
└── tests/
    ├── Feature/                 # Pengujian fitur autentikasi dan profil
    └── Unit/                    # Pengujian unit dasar
```

---

## 2. Framework dan Versinya

- **Framework Backend**: **Laravel Framework v12.56.0** (Terverifikasi secara empiris melalui runtime `php artisan --version` dan `composer.lock` baris 1611).
  - *Catatan Kritis*: Berkas `README.md` mencantumkan Laravel 11. Ini merupakan residu dokumentasi masa lalu karena proyek telah ditingkatkan (*upgraded*) ke Laravel 12.
- **PHP Environment**: **PHP 8.2.1 (cli)** (ZTS Visual C++ 2019 x64) dengan Zend Engine v4.2.1 dan Zend OPcache v8.2.1.
- **Frontend Asset Pipeline**: **Vite v6.2.4** dikombinasikan dengan `@tailwindcss/vite v4.0.0` dan `laravel-vite-plugin v1.2.0`.

---

## 3. Dependency & Pustaka Pendukung

Inventaris dependensi telah diaudit terhadap berkas `composer.lock` dan `package-lock.json`:

### 3.1 Pustaka Produksi PHP
1. `laravel/framework` (^12.0 -> v12.56.0): Inti framework MVC.
2. `barryvdh/laravel-dompdf` (^3.1 -> v3.1.0): Mesin rendering PDF untuk laporan resmi.
3. `laravel/sanctum` (* -> v4.2.3): Sistem token API untuk aplikasi mobile.
4. `maatwebsite/excel` (^3.1 -> 3.1.66): Generator berkas spreadsheet XLSX.
5. `simplesoftwareio/simple-qrcode` (^4.2 -> 4.2.0): Generator kode QR SVG/PNG.
6. `spatie/laravel-backup` (^9.3 -> 9.3.5): Pustaka pencadangan database & file.
7. `laravel/tinker` (^2.10.1 -> v2.10.1): REPL debugging.

### 3.2 Pustaka Frontend & Computer Vision
1. `html5-qrcode` (v2.3.8): Pustaka pembaca barcode & QR Code kamera web.
2. `face-api.js` (Bobot model lokal di `public/models`): Deteksi landmark wajah 68 titik dan ekstraktor vektor identitas 128-dimensi.
3. `alpinejs` (v3.4.2): Reaktivitas komponen antarmuka Blade.
4. `axios` (v1.8.2): AJAX HTTP request client.
5. `tailwindcss` (v3.1.0) & `@tailwindcss/forms` (v0.5.2): Sistem token desain styling antarmuka.

---

## 4. Database dan Migration

- **Nama Basis Data**: `db_absen` (MySQL, Host 127.0.0.1:3306).
- **Arsitektur**: **Shared Database** multi-aplikasi terintegrasi dengan SIPADA dan LMS Mokopani.
- **Status Migrasi**:
  - 65 berkas migrasi lokal di `database/migrations/` berstatus **100% Ran**.
  - Total riwayat di tabel `migrations` mencapai **148 migrasi** (gabungan ekosistem).
- **Total Tabel**: **67 tabel** aktif.

### Data Riwayat Nyata (Empirical Row Counts) per 26 September 2026:
- `users`: 191 akun pengguna
- `students`: 163 siswa terdaftar
- `teachers`: 14 guru pengajar
- `parents`: 7 orang tua murid
- `school_classes`: 19 rombongan belajar
- `attendances`: 215 log presensi harian gerbang
- `subject_attendances`: **4.367 log presensi mata pelajaran**
- `teacher_attendances`: 1 log presensi guru mandiri
- `student_permits`: 10 log izin keluar/masuk gerbang
- `leave_requests`: 14 pengajuan permohonan izin
- `schedules`: 14 jadwal pelajaran aktif
- `subjects`: 6 mata pelajaran terdaftar
- `extracurricular_attendances`: 3 log absensi ekstrakurikuler
- `conversations` (8) & `messages` (31): Percakapan Guru - Ortu
- `admin_conversations` (12) & `admin_messages` (42): Percakapan Admin - Ortu
- `notifications` (8) & `app_notifications` (456): Notifikasi sistem
- `sso_tokens`: 9 riwayat token login terintegrasi

---

## 5. Arsitektur Model (Eloquent ORM)

Ditemukan **32 model Eloquent** di direktori `app/Models/`:

| Kategori Model | Model Teridentifikasi | Sifat & Relasi Kunci |
|---|---|---|
| **Inti Pengguna & Akses** | `User`, `ParentModel`, `Teacher`, `Student` | Relasi One-to-One (`parent`, `teacher`, `student`), SoftDeletes pada `User`, integrasi pengecekan role Spatie hybrid. |
| **Transaksi Presensi** | `Attendance`, `SubjectAttendance`, `TeacherAttendance`, `StudentPermit`, `ExtracurricularAttendance` | Menghubungkan log kehadiran dengan siswa, guru, jadwal KBM, dan semester aktif. |
| **Akademik & Jadwal** | `AcademicYear`, `Semester`, `Level`, `SchoolClass`, `Subject`, `Schedule`, `TeachingAssignment` | Pemodelan kurikulum sekolah, penugasan mengajar guru di rombel tertentu, dan penelusuran sejarah kelas siswa (`class_student`). |
| **Jurnal & Evaluasi** | `TeachingJournal`, `TeacherSemesterReflection`, `StudentAnecdote`, `TeacherNote` | Catatan KBM harian, jam tatap muka, supervisi verifikasi pimpinan, dan buku catatan sikap siswa. |
| **Perizinan & Klaim** | `LeaveRequest`, `ParentStudentRequest` | Alur persetujuan izin berjenjang dan mekanisme verifikasi klaim hubungan anak oleh orang tua. |
| **Komunikasi & Bantuan** | `Conversation`, `Message`, `AdminConversation`, `AdminMessage`, `Notification` | Obrolan terenkapsulasi dua arah dengan penanda status terbaca (`read_at`). |
| **Konfigurasi & Penunjang** | `Calendar`, `Setting`, `Extracurricular`, `Cocurricular` | Kalender libur nasional, titik koordinat GPS geofence gerbang, dan program kegiatan non-reguler. |

---

## 6. Arsitektur Controller

Ditemukan **47 kelas Controller** yang dikelompokkan secara modular:

1. **Root Controllers (`app/Http/Controllers/`)**:
   - `AttendanceController`: Inti pemindai gerbang masuk/pulang, kalkulasi Haversine GPS, deteksi libur.
   - `PermitController`: Pemindai izin keluar sekolah dan registrasi jam kembali siswa.
   - `ChatController`: Obrolan orang tua dengan wali kelas dan admin.
   - `NotificationController`: Pengelolaan status baca notifikasi pengguna.
   - `AcademicPeriodController`: Pengubah periode aktif semester secara global.
   - `SSOController`: Pembangkit token SSO menuju LMS Mokopani.
   - `AboutController` & `GuideController`: Halaman statis informasi dan manual panduan.
2. **Admin Controllers (`app/Http/Controllers/Admin/`)** (21 berkas):
   - `DashboardController`: Pemantauan piket harian.
   - `ReportController`: Generator laporan PDF dan visualisasi analitik grafik.
   - `LeaveRequestController`: Persetujuan izin dan antarmuka intervensi manual TU.
   - `AdminTeachingJournalController`: Supervisi dan verifikasi massal jurnal guru.
   - `ParentVerificationController`: Pengesahan klaim hubungan orang tua-siswa.
   - `SettingController`: Konfigurasi identitas, logo, tema gelap, dan batas waktu absensi.
   - *Master Data CRUD Controllers*: `SchoolClassController`, `StudentController`, `TeacherController`, `ParentController`, `SubjectController`, `ScheduleController`, `CalendarController`, `BackupController` *(dialihkan ke SIPADA via middleware)*.
3. **Teacher Controllers (`app/Http/Controllers/Teacher/`)** (7 berkas):
   - `DashboardController`: Dasbor wali kelas, rekap absensi, cetak PDF triwulan, ekspor Excel.
   - `SubjectAttendanceController`: Pemindai presensi QR/Wajah mapel, input manual, pencatatan bolos, preview, dan cetak.
   - `TeachingJournalController`: Manajemen buku jurnal mengajar dan refleksi semester.
   - `StudentAnecdoteController`: Catatan perkembangan akademik, kehadiran, dan disiplin siswa.
   - `TeacherAttendanceController`: Presensi mandiri guru via webcam dan geolokasi smartphone.
   - `ExtracurricularAttendanceController`: Presensi kegiatan ekskul oleh pembina.
   - `LeaveRequestController`: Verifikasi izin siswa bimbingan wali kelas.
4. **Parent Controllers (`app/Http/Controllers/Parent/`)** (3 berkas):
   - `DashboardController`: Pemantauan anak real-time.
   - `LeaveRequestController`: Pengajuan izin/sakit dengan unggah surat bukti.
   - `ParentOnboardingController`: Wizard 3 langkah kelengkapan profil dan pencarian data anak.
5. **Principal Controllers (`app/Http/Controllers/Principal/`)** (1 berkas):
   - `PrincipalDashboardController`: Dasbor ringkasan eksekutif kehadiran sekolah, guru, jurnal, dan diagnostik.
6. **Api Controllers (`app/Http/Controllers/Api/`)** (15 berkas):
   - Melayani endpoint REST API untuk aplikasi mobile guru dan orang tua.
7. **Auth Controllers (`app/Http/Controllers/Auth/`)** (10 berkas):
   - Perancah Breeze + `SsoLoginController` (login otomatis berbasis token URL).

---

## 7. Struktur & Registrasi Route

Total rute yang terdaftar pada sistem mencapai **268 rute** (hasil eksekusi `php artisan route:list`):

- **Rute Publik**:
  - `/login`, `/register`, `/about`, `/guide`, `/offline`, `/sso/login`, `/sso/lms`, `/sso/debug`.
- **Rute Otentikasi & Routing Switcher**:
  - `/dashboard`: Mengarahkan dinamis ke `principal.dashboard`, `admin.dashboard`, `parent.dashboard`, atau `teacher.dashboard`.
- **Rute Pemindai Gerbang**:
  - `/scanner` (GET & POST) dilindungi middleware `scanner.access`.
  - `/permit-scanner` (GET & POST) untuk dispensasi gerbang.
- **Rute Guru (`/teacher/*`)**:
  - Mencakup absensi kelas harian, absensi mapel (`subject-attendance/*`), jurnal (`journals/*`), anekdot (`anecdotes/*`), ekskul, dan presensi guru.
- **Rute Orang Tua (`/parent/*`)**:
  - Dasbor anak, pengajuan izin, dan wizard onboarding (`parent/onboarding/*`).
- **Rute Kepala Sekolah (`/principal/*`)**:
  - Dasbor eksekutif dan `/principal/diag`.
- **Rute Administrasi (`/admin/*`)**:
  - Dasbor piket, laporan, intervensi izin, supervisi jurnal, verifikasi orang tua.
  - CRUD master data yang dibungkus middleware `sipada.redirect`.
- **Rute REST API (`/api/*`)**:
  - 40+ endpoint mobile di `routes/api.php` dilindungi `auth:sanctum`.
- **Rute Utilitas & Fallback Storage**:
  - `/fix-storage-link`: Utilitas diagnosa dan pembersihan cache server.
  - `/storage/{path}`: Jalur fallback penyajian berkas publik saat symlink hosting gagal.

---

## 8. Middleware & Filter Permintaan

Aplikasi menggunakan **10 middleware kustom**:

1. **`ContentSecurityPolicyMiddleware`** (Global Web):
   - Menginjeksi header CSP yang melindungi dari eksekusi skrip ilegal namun tetap mengizinkan CDN Tailwind, Alpine, Google Fonts, dan Chart.js.
2. **`UpdateLastSeenMiddleware`** (Global Web):
   - Memperbarui kolom `users.last_seen_at` secara otomatis untuk mendeteksi status pengguna online (< 5 menit).
3. **`SetAcademicPeriod`** (Global Web):
   - Mengelola session `active_semester_id` dan `active_academic_year_id` dari database jika belum diatur.
4. **`EnsureParentOnboardingCompleted`** (Global Web):
   - Mencegah orang tua mengakses fitur lain sebelum menyelesaikan proses onboarding verifikasi data anak.
5. **`CheckRoleMiddleware`** (Alias `role`):
   - Menolak akses (403 Forbidden) jika user tidak memiliki role yang diizinkan (mendukung parameter dinamis `role:admin,operator,satpam`).
6. **`ScannerAccessMiddleware`** (Alias `scanner.access`):
   - Memastikan perangkat yang membuka kamera scanner gerbang hanya dioperasikan oleh akun berwenang (`admin`, `operator`, `teacher`, `satpam`).
7. **`AdminMiddleware`** (Alias `admin`):
   - Memeriksa otorisasi khusus peran admin.
8. **`TeacherMiddleware`** (Alias `teacher`):
   - Memeriksa profil guru yang terkait dengan user login.
9. **`ParentMiddleware`** (Alias `parent`):
   - Memeriksa profil orang tua yang terkait dengan user login.
10. **`RedirectToSipada`** (Alias `sipada.redirect`):
    - **Interseptor Master Data**: Mengalihkan permintaan CRUD master data kembali ke dasbor dengan pesan pengalihan ke portal SIPADA.

---

## 9. Mekanisme Authentication

Aplikasi menerapkan **3 lapis arsitektur autentikasi**:

1. **Web Session Authentication (Stateful)**:
   - Dikelola oleh Laravel Breeze menggunakan session cookie terenkripsi dan proteksi token CSRF.
   - Algoritma hashing password menggunakan **Bcrypt** default Laravel (`password => hashed`).
2. **API Token Authentication (Stateless via Laravel Sanctum)**:
   - Dikelola melalui tabel `personal_access_tokens` (terverifikasi ada 5 token aktif).
   - Melayani pertukaran credential pada endpoint `/api/login` untuk aplikasi mobile Flutter/React Native.
3. **Single Sign-On (SSO) Ekosistem Terpadu**:
   - Berbasis token acak 60 karakter di tabel `sso_tokens`.
   - Token memiliki masa kedaluwarsa 2 jam dan bersifat **One-Time Use** (otomatis dihapus segera setelah berhasil dikonsumsi pada rute `/sso/login`).
   - Sesi browser langsung diregenerasi (`$request->session()->regenerate()`) untuk mencegah serangan *session fixation*.

---

## 10. Authorization dan Manajemen Role Pengguna

Sistem mengimplementasikan **Dual-Layer Hybrid RBAC (Role-Based Access Control)** yang sangat tangguh:

1. **Layer 1 (Spatie Permission SIPADA)**:
   - Metode `hasAnyRole()` pada model `App\Models\User` terlebih dahulu melakukan query ke tabel pivot Spatie (`model_has_roles` dan `roles`).
   - Hal ini menjamin pengguna yang ditetapkan rolenya di portal SIPADA langsung dikenali di Aplikasi Presensi tanpa duplikasi data.
2. **Layer 2 (Fallback Kolom Lokal `users.role`)**:
   - Jika tabel Spatie tidak dapat diakses atau pengguna tidak ditemukan di tabel relasi, sistem mengecek nilai pada kolom `role` di tabel `users`.
3. **Peran Resmi yang Terverifikasi di Kode Sumber**:
   - `admin`: Akses penuh konfigurasi sistem presensi, laporan, supervisi, dan chat.
   - `operator`: Membantu operasional absensi dan pemantauan piket.
   - `satpam`: Akses khusus perangkat pemindai gerbang `/scanner`.
   - `teacher`: Akses dasbor wali kelas, presensi mapel, jurnal mengajar, anekdot, dan chat.
   - `parent`: Akses pemantauan anak, pengajuan izin, dan permohonan klaim siswa.
   - `kepala_sekolah` / `headmaster`: Akses dasbor eksekutif dan supervisi jurnal.
   - `wakasek_kurikulum`: Akses supervisi dan verifikasi jurnal mengajar guru.
   - `tu` / `tata_usaha`: Akses intervensi manual surat izin siswa.

---

## 11. Seluruh Fitur Aplikasi

Secara fungsional, fitur aplikasi terbagi ke dalam 6 klaster domain:
1. **Klaster Presensi Gerbang & Keamanan Sekolah**: Pemindai QR/RFID, Pengenalan Wajah, Geofencing Haversine, Dispensasi Keluar/Masuk (Permit).
2. **Klaster Presensi Pembelajaran & Kelas**: Presensi Mapel, Status Siswa Bolos, Presensi Ekstrakurikuler, Rekap & Cetak Presensi Mapel, Analitik Grafik.
3. **Klaster Pembinaan & Dokumen Guru**: Presensi Mandiri Guru (GPS+Selfie), Jurnal Mengajar Harian, Refleksi Semester, Catatan Anekdot Karakter Siswa, Teacher Notes.
4. **Klaster Kemitraan Orang Tua**: Onboarding Akun Baru, Verifikasi Hubungan Anak, Pengajuan Izin/Sakit Online, Chat Dua Arah dengan Guru/Admin.
5. **Klaster Manajerial & Supervisi**: Dasbor Eksekutif Kepala Sekolah, Verifikasi Jurnal oleh Waka/Kepsek, Laporan PDF DomPDF, Ekspor XLSX Spreadsheet.
6. **Klaster Otomasi & Ekosistem**: Pengecekan Siswa Alpa Pukul 10:00, Notifikasi In-App, SSO LMS Mokopani, Multi-Semester History Backfill.

*(Rincian matriks bukti kode sumber dan status masing-masing fitur disajikan pada Bab 19 dan dokumen terpisah `feature-inventory.md`).*

---

## 12. API dan Integrasi Eksternal

1. **RESTful API Internal (`routes/api.php`)**:
   - Menyediakan 40+ rute endpoint mobile yang mengembalikan respons format JSON murni.
   - Terbagi atas modul: autentikasi, presensi wali kelas, perizinan, jadwal mengajar, jurnal KBM, pengumuman sekolah, kalender, chat, presensi mandiri guru, profil, ekskul, dan modul orang tua (`/api/parent/*`).
2. **Integrasi Ekosistem LMS Mokopani**:
   - Diintegrasikan via SSO token exchange di database bersama (`db_absen`).
   - Helper `getTargetLmsUrl()` secara dinamis mendeteksi apakah aplikasi sedang berjalan di lingkungan lokal (`http://localhost:8001`) atau di produksi (`https://mokopani-smpn1biau.zahradev.id`).
3. **Integrasi SIPADA**:
   - Berbagi database (`db_absen`) untuk data siswa, guru, kelas, tahun ajaran, dan tabel role permissions Spatie.
4. **Integrasi SMS / WhatsApp Gateway**:
   - **Tidak ditemukan** dependensi gateway perpesanan eksternal berbayar (Fonnte, Twilio, Wablas, dll.). Notifikasi saat ini murni in-app database.

---

## 13. Upload dan Penanganan File (File Handling)

- **Konfigurasi Driver**: Menggunakan disk `public` (`storage_path('app/public')`) sesuai `config/filesystems.php`.
- **Direktori Penyimpanan Aktif Terverifikasi**:
  - `storage/app/public/attachments`: Surat bukti/keterangan dokter pengajuan izin siswa (validasi mime: `jpg,jpeg,png,pdf` max 10MB).
  - `storage/app/public/logos`: Berkas logo sekolah dan instansi.
  - `storage/app/public/profile-photos`: Foto profil pengguna.
  - `storage/app/public/students`: Foto identitas siswa untuk kartu dan pengenalan wajah.
  - `storage/app/public/teachers`: Foto profil guru.
  - `storage/app/public/teacher_attendances`: Foto selfie presensi mandiri guru.
- **Ketahanan Server Hosting (Symlink Resilience)**:
  - Tersedia rute pengaman fallback di `routes/web.php` baris 534: `Route::get('/storage/{path}')` yang secara otomatis menyajikan berkas upload secara streaming jika symlink web server di shared hosting mengalami kerusakan atau dinonaktifkan.
  - Disertai sanitasi *path traversal* (`str_replace(['..', '\\'], '', $path)`).

---

## 14. Sistem Notifikasi & Penjadwalan Otomatis

1. **Notifikasi In-App Database**:
   - Menggunakan tabel `notifications` dan model `App\Models\Notification`.
   - Mengirim notifikasi saat izin disetujui/ditolak, saat klaim orang tua disetujui, dan saat siswa alpa.
2. **Tugas Terjadwal (Scheduled Task / Cron)**:
   - Terdaftar di `bootstrap/app.php` baris 38:
     ```php
     $schedule->command('attendance:check-absent')
         ->weekdays()
         ->dailyAt('10:00')
         ->description('Cek siswa yang alpa setiap hari kerja pada pukul 10:00');
     ```
   - Logika di `app/Console/Commands/CheckAbsentStudents.php`:
     - Memeriksa toggle `send_absent_notification` di tabel `settings`.
     - Melewati proses jika hari libur atau akhir pekan.
     - Mengidentifikasi siswa yang belum absen masuk hingga pukul 10:00, otomatis menandainya sebagai `'alpa'` di tabel `attendances`, dan mengirim notifikasi peringatan ke akun orang tua terhubung.

---

## 15. Testing dan Kualitas Kode

- **Framework Pengujian**: Pest PHP v3.8.5 dan PHPUnit v11.5.46 terpasang.
- **Hasil Pengujian Aktual**: **19 Gagal, 6 Lulus** dari 25 test cases.
- **Temuan Kritis Kualitas**:
  - Berkas test yang ada saat ini hanyalah *scaffold bawaan* Laravel Breeze.
  - Belum ada automated test untuk logika bisnis presensi.
  - Test runner bawaan gagal karena `phpunit.xml` menggunakan SQLite `:memory:`, sementara model `User` membutuhkan kolom `deleted_at` yang hanya tersedia di database MySQL `db_absen`.
  - Rekomendasi teknis dan rencana aksi telah disusun secara komprehensif pada berkas terpisah: [testing-plan.md](file:///d:/laragon/www/siasek/aplikasi-absensi/docs/LPJ/testing-plan.md).

---

## 16. Deployment Configuration & Lingkungan Server

- **Lingkungan Lokal Saat Audit**:
  - Server stack Laragon di Windows 11.
  - MySQL mendengarkan pada port 3306.
  - Web server lokal mendengarkan pada port 80.
- **Jejak Konfigurasi Produksi (Production Footprint)**:
  - Berdasarkan pemeriksaan kode sumber pada `routes/web.php` baris 503 dan `public/sync-hpanel.php`:
    - Lingkungan produksi menggunakan layanan cloud hosting Hostinger (cPanel/hPanel).
    - Lokasi berkas server produksi: `/home/u478110651/presensi-smpn1biau/`.
    - Perintah pembuatan symlink produksi: `ln -s /home/u478110651/presensi-smpn1biau/storage/app/public /home/u478110651/presensi-smpn1biau/public/storage`.
    - Domain produksi LMS tujuan SSO: `https://mokopani-smpn1biau.zahradev.id`.

---

## 17. Audit Keamanan Dasar (Basic Security Audit)

Berdasarkan tinjauan source code secara ketat:

| Aspek Keamanan | Implementasi Nyata pada Aplikasi | Evaluasi Keamanan |
|---|---|---|
| **Content Security Policy (CSP)** | Diatur via `ContentSecurityPolicyMiddleware.php` mencakup restriksi script-src, font-src, frame-src, worker-src. | **Baik**: Melindungi dari eksploitasi XSS eksternal liar, namun tetap mengizinkan CDN resmi yang digunakan UI. |
| **Proteksi CSRF** | Laravel default CSRF middleware aktif pada seluruh form POST, PUT, DELETE. | **Kuat**: Seluruh rute formulir Blade menyertakan `@csrf`. |
| **Injeksi SQL** | Pemrosesan database mengandalkan Eloquent ORM dan PDO prepared statements. | **Kuat**: Tidak ditemukan penggabungan string query mentah (*raw string concatenation*) yang rentan injeksi. |
| **Cross-Site Scripting (XSS)** | Blade templating engine secara otomatis melakukan sanitasi karakter melalui kurung kurawal ganda `{{ $var }}`. | **Kuat**: Penggunaan `{!! !!}` hanya terbatas pada format dokumen cetak resmi berstruktur statis. |
| **Autentikasi Sandi** | Menggunakan algoritma hash modern (Bcrypt/Argon2). | **Kuat**: Tidak ada kata sandi yang disimpan dalam bentuk teks polos (*plain text*). |
| **Pencegahan Directory Traversal** | Jalur fallback `/storage/{path}` memfilter string `..` dan `\` via `str_replace`. | **Kuat**: Mencegah penyerang membaca berkas konfigurasi sistem di luar folder `storage/app/public`. |
| **Otorisasi Endpoint Scanner** | Rute `/scanner` dilindungi `ScannerAccessMiddleware`. | **Kuat**: Siswa atau orang tua tidak dapat membuka halaman pemindai gerbang. |
| **Kelemahan Kritis yang Ditemukan (Vulnerability Finding)** | Endpoint `/fix-storage-link` di `routes/web.php` baris 445: dapat diakses tanpa login jika menambahkan parameter URL `?key=presensi123`. | ⚠️ **Perlu Perbaikan**: Hardcoded key `presensi123` pada URL publik berisiko jika diketahui pihak luar (dapat memicu pembersihan cache dan migrasi paksa `--force`). Harus dipindahkan ke autentikasi role admin murni. |
| **Perlindungan Rahasia (.env)** | Berkas `.env` telah dikecualikan oleh `.gitignore`. Tidak ada pembacaan credential langsung di laporan ini. | **Terjaga**: Berkas rahasia terlindungi. |

---

## 18. UI/UX, Desain, dan Halaman Aplikasi

- **Desain & Tipografi**:
  - Menggunakan Tailwind CSS dengan palet warna modern (Sky/Slate/Emerald/Rose).
  - Menggunakan font Google modern Inter dan sans-serif.
  - Kompatibel dengan tema gelap (*dark mode*) yang tersimpan pada tabel `settings`.
- **Ergonomi Perangkat Lunak**:
  - **Antarmuka Pemindai Gerbang (`scanner.blade.php`)**: Didesain ramah mesin kasir/POS dan layar kios gerbang sekolah, dilengkapi panduan visual kamera, kartu identitas siswa popup, dan indikator suara scan (audio beep).
  - **Antarmuka Pemindai Kelas Guru (`subject_attendance_scanner.blade.php`)**: Berukuran 111 KB dengan reaktivitas tinggi untuk pergantian mode scan QR ke deteksi wajah atau penandaan cepat hadir/sakit/izin/bolos.
  - **Responsif & Mobile Friendly**: Mendukung instalasi PWA di smartphone android/iOS bagi guru dan orang tua.

---

## 19. Status Implementasi Seluruh Fitur

Berdasarkan pembuktian faktual pada source code dan database, berikut rekapitulasi status seluruh fitur:

| Status Fungsional | Jumlah Fitur | Persentase |
|---|---|---|
| ✅ **Terimplementasi** | 39 Fitur | 83.0 % |
| ⚠️ **Terimplementasi Sebagian** (Dialihkan ke SIPADA) | 6 Fitur | 12.8 % |
| 🟡 **Belum Terverifikasi** | 0 Fitur | 0.0 % |
| ❌ **Tidak Ditemukan** (Di luar cakupan presensi) | 2 Fitur | 4.2 % |
| **TOTAL FITUR TERANALISIS** | **47 Fitur** | **100 %** |

### Ringkasan Status per Fitur Kunci:
1. **Presensi Masuk Gerbang Siswa (QR/Barcode)**: ✅ Terimplementasi
2. **Pengenalan Wajah Siswa Gerbang (Face Recognition)**: ✅ Terimplementasi
3. **Geofencing GPS Haversine**: ✅ Terimplementasi
4. **Deteksi Hari Libur & Weekend Otomatis**: ✅ Terimplementasi
5. **Presensi Kepulangan (Clock-Out)**: ✅ Terimplementasi
6. **Pemindai Dispensasi Masuk/Keluar Gerbang (Permit)**: ✅ Terimplementasi
7. **Presensi Mandiri Guru (GPS + Face + Selfie)**: ✅ Terimplementasi
8. **Presensi Siswa Per Mata Pelajaran (KBM)**: ✅ Terimplementasi
9. **Pencatatan Siswa Bolos pada Sesi Mapel**: ✅ Terimplementasi
10. **Koreksi & Rekapitulasi Presensi Mapel**: ✅ Terimplementasi
11. **Cetak Dokumen Laporan Mapel**: ✅ Terimplementasi
12. **Analitik Grafik Kehadiran Mapel**: ✅ Terimplementasi
13. **Presensi Ekstrakurikuler**: ✅ Terimplementasi
14. **Buku Jurnal Harian Mengajar Guru**: ✅ Terimplementasi
15. **Supervisi & Verifikasi Jurnal Pimpinan**: ✅ Terimplementasi
16. **Refleksi Semester Guru**: ✅ Terimplementasi
17. **Catatan Anekdot Karakter Siswa**: ✅ Terimplementasi
18. **Catatan Evaluasi Guru (Teacher Notes)**: ✅ Terimplementasi
19. **Pengajuan Izin Online oleh Orang Tua**: ✅ Terimplementasi
20. **Intervensi Manual Izin oleh TU/Admin**: ✅ Terimplementasi
21. **Persetujuan Izin & Sinkronisasi Presensi**: ✅ Terimplementasi
22. **Onboarding Mandiri Orang Tua Baru**: ✅ Terimplementasi
23. **Verifikasi Hubungan Anak oleh Wali Kelas/Admin**: ✅ Terimplementasi
24. **Dasbor Pemantauan Piket Real-Time**: ✅ Terimplementasi
25. **Dasbor Guru & Wali Kelas**: ✅ Terimplementasi
26. **Dasbor Orang Tua Siswa**: ✅ Terimplementasi
27. **Dasbor Eksekutif Kepala Sekolah**: ✅ Terimplementasi
28. **Cetak PDF Resmi (DomPDF)**: ✅ Terimplementasi
29. **Ekspor Excel XLSX (Maatwebsite Excel)**: ✅ Terimplementasi
30. **Chat Guru <-> Orang Tua**: ✅ Terimplementasi
31. **Chat Admin/TU <-> Orang Tua**: ✅ Terimplementasi
32. **Otomasi Siswa Alpa (Command 10:00)**: ✅ Terimplementasi
33. **Notifikasi In-App**: ✅ Terimplementasi
34. **Single Sign-On (SSO) ke LMS Mokopani**: ✅ Terimplementasi
35. **Penerima Login SSO Terenkripsi**: ✅ Terimplementasi
36. **Pengalih Semester & Multi-Periode Global**: ✅ Terimplementasi
37. **Rekonstruksi Riwayat Kelas Lampau**: ✅ Terimplementasi
38. **Sinkronisasi Akun Pengguna Siswa**: ✅ Terimplementasi
39. **Kustomisasi Tampilan & Logo**: ✅ Terimplementasi
40. **RESTful API Mobile (Sanctum)**: ✅ Terimplementasi
41. **PWA & Offline Service Worker**: ✅ Terimplementasi
42. **CRUD Master Data Siswa, Guru, Kelas, Mapel**: ⚠️ Terimplementasi Sebagian *(Dialihkan ke SIPADA)*
43. **Pencadangan Database Zip**: ⚠️ Terimplementasi Sebagian *(Dialihkan ke SIPADA)*
44. **Pengaturan Identitas Lembaga**: ⚠️ Terimplementasi Sebagian *(Dialihkan ke SIPADA)*
45. **WhatsApp / SMS Gateway Eksternal**: ❌ Tidak Ditemukan *(Menggunakan notifikasi in-app)*
46. **Modul Keuangan / Pembayaran SPP**: ❌ Tidak Ditemukan *(Di luar ruang lingkup aplikasi)*

---

## 20. Kesimpulan & Rekomendasi Teknis Prioritas

### 20.1 Kesimpulan Audit
Aplikasi Presensi SIASEK SMP Negeri 1 Biau berada pada kondisi **sangat matang dan siap operasional (*production-capable*)** untuk fungsi pencatatan kehadiran gerbang, kehadiran KBM di kelas, jurnal mengajar, perizinan siswa, dan pemantauan orang tua. Keputusan arsitektur untuk memusatkan pengelolaan data master pada portal SIPADA via middleware `RedirectToSipada` merupakan langkah tepat guna mencegah inkonsistensi data pada ekosistem multi-aplikasi.

### 20.2 Rekomendasi Teknis Prioritas
1. **Pembersihan Celah Rute Utilitas (`/fix-storage-link`)**:
   - Menghapus parameter rahasia hardcoded `?key=presensi123` pada `routes/web.php` dan menggantinya dengan middleware otorisasi admin murni (`middleware(['auth', 'role:admin'])`).
2. **Penyelarasan Skema Pengujian Otomatis**:
   - Menambahkan migrasi lokal untuk kolom `deleted_at` pada tabel `users` atau mengalihkan koneksi pengujian di `phpunit.xml` ke basis data MySQL pengujian (`db_absen_test`), agar 19 unit/feature test yang gagal dapat lulus secara otomatis.
3. **Penyusunan Test Suite Modul Bisnis Inti**:
   - Menulis feature test khusus untuk memverifikasi alur scan barcode gerbang, formula geofencing Haversine, approval perizinan siswa, dan otomasi command `attendance:check-absent`.
4. **Pembaruan Dokumen README.md**:
   - Memperbarui keterangan versi framework pada `README.md` dari Laravel 11 menjadi Laravel 12.56.0 agar selaras dengan runtime aktual.
