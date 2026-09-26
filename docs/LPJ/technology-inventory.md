# INVENTARIS TEKNOLOGI & DEPENDENSI PROYEK (TECHNOLOGY INVENTORY)
**Aplikasi Presensi SIASEK (Sistem Kehadiran Real-time)**  
*SMP Negeri 1 Biau / Ekosistem Pendidikan Digital SIASEK*  
*Tanggal Audit: 26 September 2026*  
*Metode Audit: Pemeriksaan Langsung Source Code, Lockfile, dan Runtime Environment*

---

## 1. Ringkasan Eksekutif Lingkungan & Runtime

| Parameter | Spesifikasi / Temuan Nyata | Status Verifikasi | Catatan Audit |
|---|---|---|---|
| **Sistem Operasi Host** | Windows 11 (NT 10.0, x64) | ✅ Terverifikasi | Diuji via CLI environment |
| **PHP Runtime** | PHP 8.2.1 (cli) (ZTS Visual C++ 2019 x64) | ✅ Terverifikasi | OPcache aktif v8.2.1 |
| **Backend Framework** | Laravel Framework **12.56.0** | ✅ Terverifikasi | `README.md` menyebut Laravel 11, namun runtime & lockfile membuktikan Laravel 12.56.0 |
| **Frontend Rendering** | Blade Templating (Server-Side Rendering) | ✅ Terverifikasi | `resources/views` murni Blade |
| **CSS Styling Engine** | Tailwind CSS v3.1.0 & `@tailwindcss/vite` v4.0.0 | ✅ Terverifikasi | Konfigurasi ganda (CDN fallback & Vite) |
| **Asset Bundler** | Vite v6.2.4 + `laravel-vite-plugin` v1.2.0 | ✅ Terverifikasi | Berkas build ada di `public/build` |
| **Database Server** | MySQL (Shared Database `db_absen` via Laragon) | ✅ Terverifikasi | Terkoneksi aktif pada port 3306 |
| **Local Web Server** | Apache/Nginx (Laragon) pada port 80 & PHP Built-in | ✅ Terverifikasi | Port 80 aktif mendengarkan |
| **Test Runner** | Pest PHP v3.8.5 + PHPUnit v11.5.46 | ✅ Terverifikasi | Terdaftar di `composer.json` & `composer.lock` |
| **PWA Engine** | Web App Manifest + Service Worker (`sw.js`) | ✅ Terverifikasi | Ditemukan di `public/manifest.json` & `public/sw.js` |
| **Computer Vision Engine** | Face-API.js (Client-side TensorFlow.js model) | ✅ Terverifikasi | Bobot model neural network ada di `public/models` |

---

## 2. Dependensi Backend (PHP / Composer)

Data diambil secara langsung dari berkas [composer.json](file:///d:/laragon/www/siasek/aplikasi-absensi/composer.json) dan dikonfirmasi terhadap [composer.lock](file:///d:/laragon/www/siasek/aplikasi-absensi/composer.lock).

### 2.1 Dependensi Produksi (Require)

| No | Paket | Versi Manifest | Versi Terpasang (Lock) | Fungsi Teknis dalam Sistem | Bukti Pemakaian |
|---|---|---|---|---|---|
| 1 | `php` | `^8.2` | `8.2.1` | Bahasa pemrograman utama aplikasi | Runtime CLI & Web |
| 2 | `laravel/framework` | `^12.0` | `v12.56.0` | Framework web inti (Routing, Eloquent, Container, Security) | `bootstrap/app.php`, seluruh model & controller |
| 3 | `barryvdh/laravel-dompdf` | `^3.1` | `v3.1.0` | Pustaka konversi HTML/Blade menjadi dokumen PDF | Digunakan pada cetak laporan di `Admin\ReportController.php`, `Teacher\DashboardController.php`, dll. |
| 4 | `laravel/sanctum` | `*` | `v4.2.3` | Autentikasi token API tanpa status (Stateless API Tokens) | `app/Models/User.php`, endpoint di `routes/api.php` |
| 5 | `laravel/tinker` | `^2.10.1` | `v2.10.1` | CLI REPL interaktif untuk administrasi dan debugging | `artisan tinker` |
| 6 | `maatwebsite/excel` | `^3.1` | `3.1.66` | Pustaka pemrosesan dan ekspor data spreadsheet Excel (XLSX) | Digunakan pada `app/Exports/AttendanceReportExport.php` dan `Teacher\DashboardController@exportAttendanceExcel` |
| 7 | `simplesoftwareio/simple-qrcode` | `^4.2` | `4.2.0` | Generator kode QR SVG/PNG berbasis backend | Digunakan untuk generasi kartu NIS dan tiket permit siswa |
| 8 | `spatie/laravel-backup` | `^9.3` | `9.3.5` | Manajemen pencadangan database dan berkas secara otomatis | Dikonfigurasi di `config/backup.php` dan `Admin\BackupController.php` |

### 2.2 Dependensi Pengembangan (Require-Dev)

| No | Paket | Versi Manifest | Versi Terpasang (Lock) | Fungsi Teknis |
|---|---|---|---|---|
| 1 | `pestphp/pest` | `^3.8` | `v3.8.5` | Framework pengujian modern berorientasi ekspresi elegan |
| 2 | `pestphp/pest-plugin-laravel` | `^3.2` | `v3.2.0` | Integrasi spesifik Laravel untuk runner pengujian Pest |
| 3 | `laravel/breeze` | `^2.3` | `v2.3.8` | Paket perancah autentikasi awal (Login, Registrasi, Profil) |
| 4 | `laravel/pail` | `^1.2.2` | `v1.2.5` | Penampil log streaming real-time pada terminal |
| 5 | `laravel/pint` | `^1.13` | `v1.25.1` | Code style fixer berbasis PHP CS Fixer untuk standarisasi format |
| 6 | `laravel/sail` | `^1.41` | `v1.41.0` | Lingkungan runtime berbasis Docker lokal |
| 7 | `mockery/mockery` | `^1.6` | `1.6.12` | Framework mock objek untuk unit testing |
| 8 | `nunomaduro/collision` | `^8.6` | `v8.6.1` | Handler error CLI yang menyajikan stack trace interaktif |
| 9 | `fakerphp/faker` | `^1.23` | `v1.24.1` | Generator data tiruan untuk database seeding dan pengujian |

---

## 3. Dependensi Frontend (JavaScript & CSS)

Data diambil secara langsung dari berkas [package.json](file:///d:/laragon/www/siasek/aplikasi-absensi/package.json) dan berkas [package-lock.json](file:///d:/laragon/www/siasek/aplikasi-absensi/package-lock.json).

### 3.1 Paket Produksi & Pengembangan Frontend

| No | Paket | Versi Manifest | Kategori | Fungsi Teknis | Bukti Implementasi |
|---|---|---|---|---|---|
| 1 | `html5-qrcode` | `^2.3.8` | Dependency | Library pemindai QR Code & Barcode kamera web di browser | `resources/views/scanner.blade.php`, `permit-scanner.blade.php`, `subject_attendance_scanner.blade.php` |
| 2 | `alpinejs` | `^3.4.2` | DevDependency | Framework reaktivitas UI mikro di dalam Blade template | Navigasi dropdown, modal dialog, tab panel, flash alerts |
| 3 | `axios` | `^1.8.2` | DevDependency | HTTP Client berbasis Promise untuk pemanggilan API asinkron | Request kirim absensi, update catatan anekdot, verifikasi klaim |
| 4 | `vite` | `^6.2.4` | DevDependency | Frontend build tool dan local dev server berkas JS/CSS | `vite.config.js` |
| 5 | `laravel-vite-plugin` | `^1.2.0` | DevDependency | Plugin integrasi Vite untuk injeksi bundle ke Blade `@vite()` | `resources/views/layouts/app.blade.php` |
| 6 | `tailwindcss` | `^3.1.0` | DevDependency | Utility-first CSS framework utama aplikasi | Seluruh antarmuka admin, guru, orang tua, dan kepsek |
| 7 | `@tailwindcss/forms` | `^0.5.2` | DevDependency | Plugin normalisasi form reset untuk Tailwind CSS | Komponen input, radio, select, checkbox |
| 8 | `@tailwindcss/vite` | `^4.0.0` | DevDependency | Plugin bundler Vite generasi baru untuk Tailwind | `package.json` |
| 9 | `postcss` | `^8.4.31` | DevDependency | Tool transformasi style dengan plugin JavaScript | `postcss.config.js` |
| 10 | `autoprefixer` | `^10.4.2` | DevDependency | Penambahan vendor-prefix otomatis pada CSS | `postcss.config.js` |
| 11 | `concurrently` | `^9.0.1` | DevDependency | Menjalankan server `php artisan`, `pail`, dan `vite` bersamaan | Script `npm run dev` pada `composer.json` |

---

## 4. Pustaka & Aset Frontend Eksternal / On-Device Machine Learning

Aplikasi mengintegrasikan pustaka kecerdasan buatan (Computer Vision) dan visualisasi data yang dioperasikan pada sisi klien (client-side) tanpa ketergantungan API pihak ketiga berbayar:

### 4.1 Model Face-API.js (Face Recognition Lokal)
Tersedia berkas bobot neural network yang di-*host* mandiri di folder [public/models](file:///d:/laragon/www/siasek/aplikasi-absensi/public/models):
1. **SSD MobileNet V1**:
   - `ssd_mobilenetv1_model-weights_manifest.json` (26.5 KB)
   - `ssd_mobilenetv1_model-shard1` (4.19 MB)
   - `ssd_mobilenetv1_model-shard2` (1.42 MB)
   - *Fungsi*: Deteksi keberadaan dan posisi wajah pada frame kamera secara real-time.
2. **Face Landmark 68**:
   - `face_landmark_68_model-weights_manifest.json` (7.8 KB)
   - `face_landmark_68_model-shard1` (356.8 KB)
   - *Fungsi*: Ekstraksi 68 titik kontur wajah (mata, hidung, bibir, rahang).
3. **Face Recognition Model**:
   - `face_recognition_model-weights_manifest.json` (18.3 KB)
   - `face_recognition_model-shard1` (4.19 MB)
   - `face_recognition_model-shard2` (2.25 MB)
   - *Fungsi*: Ekstraksi 128-dimensional floating point vector (*descriptor*) untuk pencocokan identitas wajah siswa dan guru.

### 4.2 Visualisasi Data (Chart.js)
- Dimuat melalui CDN terkonfigurasi pada `resources/views/admin/reports/charts.blade.php` dan `resources/views/teacher/attendance/charts.blade.php`.
- Didukung oleh `ContentSecurityPolicyMiddleware.php` yang secara eksplisit mengizinkan CDN `https://cdn.jsdelivr.net`.

### 4.3 PWA & Service Worker
- `public/manifest.json`: Menyediakan konfigurasi aplikasi web progresif dengan tema warna `#0284c7` dan short name `SIASEK`.
- `public/sw.js`: Menyediakan caching aset offline dan pengalihan ke view [offline.blade.php](file:///d:/laragon/www/siasek/aplikasi-absensi/resources/views/offline.blade.php) saat koneksi terputus.

---

## 5. Ringkasan Perbedaan (Discrepancy) Temuan

> [!WARNING]
> **Ketidaksesuaian Dokumentasi Awal vs Fakta Kode**:
> - Dokumen [README.md](file:///d:/laragon/www/siasek/aplikasi-absensi/README.md) menyatakan: "*Backend Framework: Laravel 11 (PHP 8.2+)*".
> - Namun hasil investigasi nyata pada [composer.json](file:///d:/laragon/www/siasek/aplikasi-absensi/composer.json) baris 11 (`"laravel/framework": "^12.0"`), [composer.lock](file:///d:/laragon/www/siasek/aplikasi-absensi/composer.lock) baris 1611 (`"version": "v12.56.0"`), dan eksekusi perintah terminal `php artisan --version` membuktikan bahwa aplikasi berjalan di atas **Laravel Framework 12.56.0**.
