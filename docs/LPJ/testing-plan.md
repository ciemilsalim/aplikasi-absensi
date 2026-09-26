# RENCANA & HASIL AUDIT PENGUJIAN SISTEM (TESTING PLAN & AUDIT)
**Aplikasi Presensi SIASEK (Sistem Kehadiran Real-time)**  
*SMP Negeri 1 Biau / Ekosistem Pendidikan Digital SIASEK*  
*Tanggal Audit: 26 September 2026*  
*Test Framework: Pest PHP v3.8.5 / PHPUnit v11.5.46*

---

## 1. Hasil Pengujian Aktual (Empirical Test Run)

Pengujian otomatis dijalankan secara langsung pada lingkungan lokal menggunakan perintah `php artisan test`.

### Ringkasan Eksekusi Pengujian Otomatis
- **Total Test Cases**: 25 pengujian
- **Lulus (Passed)**: 6 pengujian (24%)
- **Gagal (Failed)**: 19 pengujian (76%)
- **Total Assertions**: 29 asersi
- **Durasi Eksekusi**: 19.37 detik (eksekusi pengujian terbaru; pada putaran awal tercatat 50.41 detik)

### Rincian Hasil Pengujian per Berkas Test

| Berkas Pengujian | Jenis Test | Lulus | Gagal | Status | Catatan Teknis / Penyebab |
|---|---|---|---|---|---|
| `tests/Unit/ExampleTest.php` | Unit | 1 | 0 | ✅ Lulus | Asersi dasar PHPUnit (`true is true`) |
| `tests/Feature/ExampleTest.php` | Feature | 0 | 1 | ❌ Gagal | Pengujian `it returns a successful response` mengharapkan status 200 pada root `/`, namun sistem melakukan redirect 302 ke `/login` |
| `tests/Feature/Auth/AuthenticationTest.php` | Feature | 2 | 2 | ❌ Gagal Sebagian | `login screen can be rendered` (Lulus), `users can not authenticate with invalid password` (Lulus). Pengujian login sukses gagal karena redirect rute `/dashboard` menolak user tanpa role dan mengalihkan ke `/login`. |
| `tests/Feature/Auth/EmailVerificationTest.php` | Feature | 1 | 2 | ❌ Gagal Sebagian | `email is not verified with invalid hash` (Lulus). Pengujian rendering & verifikasi gagal akibat ketiadaan kolom `users.deleted_at` di SQLite. |
| `tests/Feature/Auth/PasswordConfirmationTest.php` | Feature | 0 | 3 | ❌ Gagal | Gagal akibat ketiadaan kolom `users.deleted_at` saat memproses middleware `UpdateLastSeenMiddleware`. |
| `tests/Feature/Auth/PasswordResetTest.php` | Feature | 1 | 3 | ❌ Gagal Sebagian | `reset password link screen can be rendered` (Lulus). Pengujian token & update gagal karena konfigurasi email array dan ketiadaan field soft delete. |
| `tests/Feature/Auth/PasswordUpdateTest.php` | Feature | 0 | 2 | ❌ Gagal | Gagal akibat error skema database in-memory. |
| `tests/Feature/Auth/RegistrationTest.php` | Feature | 1 | 1 | ❌ Gagal Sebagian | `registration screen can be rendered` (Lulus). `new users can register` gagal karena redirect langsung diarahkan ke middleware `EnsureParentOnboardingCompleted`. |
| `tests/Feature/ProfileTest.php` | Feature | 0 | 5 | ❌ Gagal | Gagal akibat `SQLSTATE[HY000]: no such column: users.deleted_at`. |

---

## 2. Analisis Akar Masalah (Root Cause Analysis)

Investigasi mendalam membuktikan bahwa kegagalan pengujian otomatis saat ini **bukan disebabkan oleh rusaknya logika aplikasi produksi**, melainkan disebabkan oleh 4 faktor konfigurasi pengujian:

1. **Ketidaksinkronan Skema Database Uji (SQLite In-Memory vs MySQL Shared DB)**:
   - Berkas `phpunit.xml` mendefinisikan environment pengujian menggunakan SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).
   - Model `App\Models\User` menggunakan *trait* `SoftDeletes`, yang secara default memerlukan kolom `deleted_at`.
   - Pada repositori lokal `aplikasi-absensi`, migrasi awal `0001_01_01_000000_create_users_table.php` tidak memiliki `$table->softDeletes()`. Kolom `deleted_at` pada database MySQL produksi ditambahkan melalui migrasi aplikasi SIPADA di dalam arsitektur shared database.
   - Akibatnya, saat test runner menjalankan migrasi lokal ke SQLite, kolom `deleted_at` tidak pernah dibuat, memicu fatal error:
     ```text
     SQLSTATE[HY000]: General error: 1 no such column: users.deleted_at 
     (SQL: update "users" set "last_seen_at" = ... where "id" = 1 and "users"."deleted_at" is null)
     ```

2. **Perbedaan Ekspektasi Alur Autentikasi (Breeze Boilerplate vs Multi-Role Redirect)**:
   - Seluruh berkas uji yang ada di `tests/Feature/` saat ini merupakan kode bawaan (*scaffold boilerplate*) dari **Laravel Breeze**.
   - Breeze mengasumsikan bahwa setelah login berhasil, pengguna akan selalu dialihkan ke URL `/dashboard` dan menerima respons status 200.
   - Kenyataannya, arsitektur aplikasi presensi telah disesuaikan menjadi sistem multi-peran (RBAC):
     - Rute `/dashboard` di `routes/web.php` memeriksa peran pengguna (`hasRole`). Jika pengguna tidak memiliki peran resmi (`admin`, `teacher`, `parent`, `satpam`, atau `kepala_sekolah`), sistem secara sengaja melakukan logout dan mengalihkan pengguna kembali ke `/login` dengan pesan error: *"Peran Anda tidak dikenali oleh sistem absensi."*
     - Akun tiruan yang dibuat oleh factory Breeze tidak memiliki peran, sehingga otomatis di-logout oleh sistem.

3. **Intersepsi Middleware Global**:
   - Middleware `EnsureParentOnboardingCompleted` dan `SetAcademicPeriod` didaftarkan secara global pada grup `web` di `bootstrap/app.php`.
   - Setiap request uji yang dibuat oleh test runner otomatis melewati middleware ini, yang membutuhkan konteks semester aktif atau status onboarding orang tua.

4. **Ketiadaan Test Suite Logika Bisnis Presensi (Zero Business Logic Tests)**:
   - Belum ada satupun berkas automated test yang ditulis untuk menguji modul inti presensi: pemindai gerbang, absensi mapel, dispensasi gerbang, perizinan, jurnal mengajar, maupun endpoint REST API.

---

## 3. Rencana Perbaikan & Strategi Pengujian (Testing Plan)

Untuk mencapai keandalan perangkat lunak tingkat produksi (*production grade reliability*), disusun rencana pengujian terstruktur 7 tahap:

### Tahap 1: Remediasi Lingkungan Pengujian (Test Environment Fix)
- **Tindakan**:
  1. Menambahkan migrasi lokal tambahan atau menggunakan basis data MySQL pengujian khusus (`db_absen_test`) agar skema identik dengan lingkungan produksi ekosistem SIPADA.
  2. Memperbarui `phpunit.xml` agar menggunakan koneksi database pengujian yang mendukung foreign key dan tipe data spesifik (termasuk kolom `deleted_at` dan tabel roles Spatie).
  3. Menyiapkan `DatabaseSeeder` pengujian yang menginjeksi peran (`admin`, `teacher`, `parent`, `kepala_sekolah`), semester aktif, dan pengaturan geolokasi sekolah.

### Tahap 2: Rencana Unit Testing (Logika Murni & Algoritma)

| No | Modul Target | Nama Test Case yang Direncanakan | Metode / Asersi yang Diuji |
|---|---|---|---|
| 1 | `GpsValidationTrait` | `test_haversine_calculates_distance_accurately` | Menguji akurasi jarak formula Haversine antara dua koordinat GPS terhadap toleransi radius meter |
| 2 | `GpsValidationTrait` | `test_validate_gps_rejects_outside_radius` | Memastikan koordinat di luar radius 100m mengembalikan status `isValid => false` |
| 3 | `App\Models\User` | `test_user_has_any_role_resolves_spatie_and_column` | Memastikan metode `hasAnyRole` bekerja baik melalui tabel Spatie maupun kolom `role` lokal |
| 4 | `App\Models\Calendar` | `test_calendar_detects_holiday_and_self_study` | Memastikan tanggal libur dan belajar mandiri terdeteksi benar pada rentang tanggal |
| 5 | `App\Models\Student` | `test_student_active_scope_filters_graduated` | Memastikan scope `Student::active()` mengabaikan siswa berstatus 'lulus' atau 'pindah' |

### Tahap 3: Rencana Feature Testing (Alur Bisnis Presensi)

| No | Modul Bisnis | Skenario Pengujian | Hasil yang Diharapkan |
|---|---|---|---|
| 1 | **Presensi Masuk Gerbang** | Siswa aktif memindai QR format `NIS-UUID` di dalam radius sekolah pada jam kerja normal | Status HTTP 200, record dibuat di `attendances`, status `tepat_waktu` atau `terlambat` |
| 2 | **Pencegahan Hari Libur** | Siswa memindai QR pada hari Minggu atau tanggal libur kalender | Status HTTP 403, pesan *"Hari ini libur / akhir pekan"*, tidak ada data tersimpan |
| 3 | **Pelanggaran Radius GPS** | Siswa memindai QR dengan koordinat GPS berjarak 500 meter dari sekolah | Status HTTP 403, pesan *"Anda berada di luar radius absensi"* |
| 4 | **Presensi Pulang** | Siswa mencoba absen pulang sebelum batas `jam_pulang` | Status HTTP 409, pesan *"Absen pulang baru bisa dilakukan setelah pukul XX:XX"* |
| 5 | **Dispensasi Gerbang (Permit)** | Siswa berstatus hadir memindai izin keluar dengan mengisi alasan | Record dibuat di `student_permits`, status absensi berubah menjadi `izin_keluar` |
| 6 | **Dispensasi Gerbang Kembali** | Siswa kembali dan memindai permit scanner kedua kalinya | Kolom `time_in` terisi waktu saat ini, status presensi kembali menjadi `tepat_waktu` |
| 7 | **Presensi Mata Pelajaran** | Guru mapel menyimpan kehadiran siswa kelas (Hadir, Sakit, Izin, Bolos) | Record tersimpan di `subject_attendances` sesuai jadwal KBM aktif |
| 8 | **Workflow Pengajuan Izin** | Orang tua mengajukan permohonan izin sakit dengan lampiran foto | Record tersimpan di `leave_requests` dengan status `pending` |
| 9 | **Approval Izin oleh Admin** | Admin menyetujui izin siswa | Status `leave_requests` berubah menjadi `approved`, tabel `attendances` dan `subject_attendances` otomatis terisi `'izin'` / `'sakit'` |
| 10 | **Otomasi Siswa Alpa (10:00)** | Menjalankan artisan `attendance:check-absent` saat ada siswa belum scan | Siswa tanpa keterangan otomatis dibuatkan record `attendances` berstatus `'alpa'`, notifikasi terkirim ke user ortu |

### Tahap 4: Rencana Pengujian REST API (Sanctum)

| No | Endpoint | Metode | Skenario Uji |
|---|---|---|---|
| 1 | `/api/login` | POST | Mengirim email dan password valid -> menerima personal access token |
| 2 | `/api/teacher/schedules` | GET | Mengakses jadwal dengan token guru -> menerima daftar jadwal mengajar aktif |
| 3 | `/api/teacher/attendance/scan` | POST | Mengirim token QR siswa via API mobile guru -> absensi tercatat |
| 4 | `/api/parent/dashboard` | GET | Mengakses data dengan token orang tua -> menerima ringkasan kehadiran anak |
| 5 | `/api/settings/gps` | GET | Mengakses konfigurasi lokasi sekolah tanpa autentikasi / dengan token |

### Tahap 5: Rencana Pengujian Keamanan & Otorisasi

| No | Vektor Keamanan | Metode Uji | Standar Kelulusan |
|---|---|---|---|
| 1 | **IDOR (Insecure Direct Object References)** | User ortu A mengakses data absensi anak ortu B via URL ID | Harus diblokir dengan respons 403 Forbidden |
| 2 | **Otorisasi Role Scanner** | User dengan role `parent` mengakses halaman `/scanner` gerbang | Diblokir oleh `ScannerAccessMiddleware` (hanya admin, operator, guru, satpam) |
| 3 | **Proteksi Directory Traversal** | Mengakses rute fallback `/storage/../../../../etc/passwd` | Disanitasi oleh `str_replace(['..', '\\'], '', $path)`, mengembalikan 404 |
| 4 | **Pencegahan Token SSO Kadaluarsa** | Menggunakan token SSO yang sudah lewat 2 jam atau sudah dipakai sekali | Ditolak dengan pesan *"Token SSO tidak valid atau telah kadaluarsa"* |
| 5 | **Injeksi SQL** | Parameter pencarian pada filter laporan dan input izin manual | Menggunakan parameter binding Eloquent / PDO murni tanpa raw string concatenation |

---

## 4. Matriks Pengujian Manual & Verifikasi Antarmuka (Smoke Test Matrix)

| No | Skenario Uji | Prosedur Langkah | Hasil yang Diharapkan | Hasil Pengujian Aktual | Status |
|---|---|---|---|---|---|
| 1 | Akses Halaman Login | Buka browser ke URL `/login` | Form login tampil bersih dengan input email & password | Halaman login Laravel Breeze berhasil dirender dengan asset Tailwind | Lulus |
| 2 | Redirect Beranda `/` | Buka URL `/` tanpa sesi login | Pengguna otomatis dialihkan ke rute `/login` | Dialihkan dengan status 302 ke `/login` | Lulus |
| 3 | Endpoint Diagnostik SSO | Akses URL `/sso/debug` di browser | Mengembalikan JSON status database, konfigurasi LMS, dan tabel token | JSON valid dengan HTTP 200, status database `db_absen` terhubung | Lulus |
| 4 | Diagnostic Route Kepsek | Akses URL `/principal/diag` | Mengembalikan JSON statistik jumlah siswa, kelas, dan absensi hari ini | Mengembalikan JSON akurat sesuai database `db_absen` | Lulus |
| 5 | Rute Utilitas Storage | Akses URL `/fix-storage-link?key=presensi123` | Menampilkan laporan diagnostik symlink storage dan pembersihan cache | Halaman utilitas berhasil merender status symlink dan direktori storage | Lulus |
| 6 | Pemeriksaan Service Worker | Buka browser DevTools -> Application -> Service Workers | File `sw.js` terdaftar dan aktif melayani scope `/` | Terdaftar aktif dengan konfigurasi `manifest.json` | Lulus |
| 7 | Halaman Offline PWA | Akses URL `/offline` secara langsung | Tampilan informatif bahwa perangkat berada di luar jaringan | Blade template `resources/views/offline.blade.php` tampil responsif | Lulus |
