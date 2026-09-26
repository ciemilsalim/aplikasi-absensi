# MATRIKS & HASIL EVALUASI PENGUJIAN SISTEM
**Aplikasi Presensi SIASEK (Sistem Kehadiran Real-time)**  
*Pemilik & Pengembang: Zahradev | Pengguna Layanan: SMP Negeri 1 Biau*  
*Skema: Sewa/Penggunaan Layanan Aplikasi (Rp1.000,-/siswa/bulan)*  
*Tanggal Pengujian: 26 September 2026*  
*Dokumen Rujukan: docs/LPJ/testing-plan.md*

---

## 1. Hasil Pengujian Otomatis (Automated Test Suite)

Pengujian otomatis dieksekusi menggunakan test runner **Pest v3.8.5** / **PHPUnit v11.5.46** via CLI:
```bash
php artisan test
```

### Rekapitulasi Eksekusi
- **Total Uji**: 25 Test Cases
- **Lulus (Passed)**: 6 Test Cases (24.0 %)
- **Gagal (Failed)**: 19 Test Cases (76.0 %)
- **Asersi Teruji**: 29 Assertions
- **Durasi Eksekusi**: 19.37 detik (eksekusi pengujian terbaru; pada putaran awal tercatat 50.41 detik)

---

## 2. Tabel Rinci Hasil Uji Otomatis per Test Case

| No | Berkas Uji (Test File) | Skenario Pengujian Spesifik | Hasil Aktual | Status | Akar Masalah Kegagalan |
|---|---|---|---|---|---|
| 1 | `tests/Unit/ExampleTest.php` | Asersi dasar PHPUnit (`true is true`) | Berhasil dieksekusi (1.43s) | Lulus | - |
| 2 | `tests/Feature/ExampleTest.php` | Respons awal akses root sistem (`/`) | Mengharapkan HTTP 200, menerima HTTP 302 | Gagal | Sistem sengaja me-redirect root `/` ke `/login` bagi pengunjung tanpa sesi login. |
| 3 | `tests/Feature/Auth/AuthenticationTest.php` | Halaman login dapat dirender | Formulir login berhasil dimuat (6.87s) | Lulus | - |
| 4 | `tests/Feature/Auth/AuthenticationTest.php` | Pengguna dapat login via form | Dialihkan kembali ke `/login` dengan error role | Gagal | Pengguna uji bawaan Breeze tidak memiliki role; rute `/dashboard` menolak akun tanpa role resmi. |
| 5 | `tests/Feature/Auth/AuthenticationTest.php` | Password salah ditolak sistem | Ditolak dengan session error (1.26s) | Lulus | - |
| 6 | `tests/Feature/Auth/AuthenticationTest.php` | Pengguna dapat logout | Terinterupsi ketiadaan kolom soft delete | Gagal | Ketiadaan kolom `users.deleted_at` di SQLite in-memory. |
| 7 | `tests/Feature/Auth/EmailVerificationTest.php` | Render halaman verifikasi email | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 8 | `tests/Feature/Auth/EmailVerificationTest.php` | Proses verifikasi email berhasil | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 9 | `tests/Feature/Auth/EmailVerificationTest.php` | Hash verifikasi tidak valid ditolak | Asersi penolakan berhasil (1.03s) | Lulus | - |
| 10 | `tests/Feature/Auth/PasswordConfirmationTest.php` | Render layar konfirmasi password | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 11 | `tests/Feature/Auth/PasswordConfirmationTest.php` | Konfirmasi password valid berhasil | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 12 | `tests/Feature/Auth/PasswordConfirmationTest.php` | Konfirmasi password salah ditolak | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 13 | `tests/Feature/Auth/PasswordResetTest.php` | Render formulir minta link reset | Formulir berhasil dirender (0.41s) | Lulus | - |
| 14 | `tests/Feature/Auth/PasswordResetTest.php` | Pengiriman link reset via email | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 15 | `tests/Feature/Auth/PasswordResetTest.php` | Render halaman reset password token | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 16 | `tests/Feature/Auth/PasswordResetTest.php` | Reset password dengan token valid | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 17 | `tests/Feature/Auth/PasswordUpdateTest.php` | Pembaruan password pengguna berhasil | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 18 | `tests/Feature/Auth/PasswordUpdateTest.php` | Password saat ini salah ditolak | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 19 | `tests/Feature/Auth/RegistrationTest.php` | Render layar registrasi mandiri | Layar registrasi berhasil dimuat (0.74s) | Lulus | - |
| 20 | `tests/Feature/Auth/RegistrationTest.php` | Registrasi user baru | Dialihkan ke wizard onboarding ortu | Gagal | Breeze mengharapkan `/dashboard`, sistem mengarahkan ke `parent.onboarding.index`. |
| 21 | `tests/Feature/ProfileTest.php` | Render halaman profil pengguna | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 22 | `tests/Feature/ProfileTest.php` | Update informasi profil | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 23 | `tests/Feature/ProfileTest.php` | Status verifikasi email tidak berubah | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 24 | `tests/Feature/ProfileTest.php` | Penghapusan akun pengguna | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |
| 25 | `tests/Feature/ProfileTest.php` | Password salah saat hapus akun ditolak | Terinterupsi query database | Gagal | `SQLSTATE[HY000]: no such column: users.deleted_at`. |

---

## 3. Matriks Pengujian Manual & Verifikasi Antarmuka (Smoke Tests)

Pengujian manual dilakukan secara langsung terhadap antarmuka web, konektivitas database MySQL `db_absen`, dan respons HTTP server:

| No | Komponen Antarmuka / Rute | Prosedur Pengujian | Hasil yang Diharapkan | Hasil Aktual Lapangan | Status |
|---|---|---|---|---|---|
| 1 | **Halaman Login (`/login`)** | Akses rute `/login` tanpa autentikasi | Menampilkan antarmuka login bersih dengan input email & sandi | Status HTTP 200, aset Tailwind termuat lengkap | Lulus |
| 2 | **Redireksi Tamu (`/`)** | Akses beranda tanpa cookie sesi | Otomatis mengarahkan ke halaman login | Status HTTP 302 diarahkan tepat ke `/login` | Lulus |
| 3 | **Diagnostik SSO (`/sso/debug`)** | Akses endpoint pengawasan SSO | Menghasilkan status JSON koneksi database dan LMS target | Mengembalikan HTTP 200 JSON valid, mendeteksi database `db_absen` | Lulus |
| 4 | **Diagnostik Kepsek (`/principal/diag`)** | Akses endpoint diagnosa pimpinan | Menampilkan JSON jumlah siswa aktif, kehadiran, dan rombel | Menghasilkan HTTP 200 JSON valid, menghitung 163 siswa akurat | Lulus |
| 5 | **Utilitas Penyimpanan (`/fix-storage-link`)** | Akses dengan parameter `?key=presensi123` | Menampilkan laporan diagnostik symlink storage publik | Halaman merender status storage path dan tombol pembersihan cache | Lulus |
| 6 | **PWA Manifest (`/manifest.json`)** | Permintaan berkas web app manifest | Mengembalikan JSON konfigurasi aplikasi PWA SIASEK | HTTP 200 JSON dengan warna tema `#0284c7` dan ikon lengkap | Lulus |
| 7 | **Halaman Offline PWA (`/offline`)** | Akses langsung saat jaringan terputus | Menampilkan panduan mode luring terdesain rapi | Template `offline.blade.php` tampil responsif dan ramah pengguna | Lulus |
| 8 | **Fallback Storage (`/storage/{path}`)** | Akses gambar melalui rute fallback | Gambar disajikan secara streaming meskipun symlink mati | Menghasilkan header image/jpeg atau mime terkait dengan aman | Lulus |

---

## 4. Evaluasi Pengujian Keamanan & Ketahanan Sistem

| No | Skenario Keamanan | Metode Verifikasi | Hasil Evaluasi Teknis | Status |
|---|---|---|---|---|
| 1 | **Proteksi Manipulasi Path (Directory Traversal)** | Request URL `/storage/../../../../windows/win.ini` | Parameter disanitasi oleh `str_replace(['..', '\\'], '', $path)`, mengembalikan status 404 |  Aman |
| 2 | **Otorisasi Pemindai Gerbang** | Akun role orang tua mencoba membuka `/scanner` | Dicegat oleh `ScannerAccessMiddleware`, dialihkan dengan pesan error akses |  Aman |
| 3 | **Kedaluwarsa Token SSO** | Percobaan login dengan token SSO lewat dari 2 jam atau pemakaian ganda | Ditolak dengan pesan *"Token SSO tidak valid atau telah kadaluarsa"* |  Aman |
| 4 | **Injeksi SQL pada Form Filter Laporan** | Pengujian input karakter anomali (`' OR 1=1 --`) | Seluruh query dieksekusi via Eloquent ORM dengan prepared statement binding |  Aman |
| 5 | **Celah Parameter URL Utilitas Server** | Endpoint `/fix-storage-link` dapat diakses publik via query `?key=presensi123` | Ditemukan kerentanan bypass autentikasi jika token query diketahui pihak luar |  Temuan Celah |
