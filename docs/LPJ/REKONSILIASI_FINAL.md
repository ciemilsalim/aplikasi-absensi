# BERITA ACARA REKONSILIASI FAKTA DAN METRIK FINAL
**Aplikasi Presensi SIASEK (Sistem Kehadiran Real-time)**  
*SMP Negeri 1 Biau / Ekosistem Pendidikan Digital SIASEK*  
*Tanggal Rekonsiliasi: 26 September 2026*  
*Metode: Verifikasi Kode Sumber Statis, CLI Artisan, Query Runtime Database, & Inspeksi Berkas*

---

Dokumen ini disusun untuk mendokumentasikan proses **Rekonsiliasi Fakta dan Metrik Teknis** antara data temuan audit awal dengan kondisi aktual fisik repositori dan basis data aktif per tanggal 26 September 2026. Rekonsiliasi ini memastikan seluruh angka yang disajikan dalam dokumen Laporan Pertanggungjawaban (LPJ) terbukti secara faktual tanpa asumsi atau estimasi.

---

## A. DAFTAR METRIK FINAL TERVERIFIKASI

Seluruh metrik berikut telah diverifikasi secara langsung melalui eksekusi perintah sistem dan query database:

| No | Entitas / Metrik | Nilai Terverifikasi | Metode & Perintah Verifikasi |
|---|---|:---:|---|
| 1 | **Controller** | **68 Berkas** | `Get-ChildItem -Path "app\Http\Controllers" -Filter "*.php" -Recurse` |
| 2 | **Model Eloquent** | **32 Berkas** | `Get-ChildItem -Path "app\Models" -Filter "*.php"` |
| 3 | **Route Terdaftar** | **268 Rute** | `php artisan route:list --json` |
| 4 | **Middleware Kustom** | **10 Berkas** | `Get-ChildItem -Path "app\Http\Middleware" -Filter "*.php"` |
| 5 | **Berkas Migrasi Lokal** | **65 Berkas** | `Get-ChildItem -Path "database\migrations" -Filter "*.php"` |
| 6 | **Migrasi Lokal Berstatus `Ran`** | **65 Migrasi** | `php artisan migrate:status` (100% tereksekusi) |
| 7 | **Riwayat di Tabel `migrations`** | **148 Baris** | `DB::table('migrations')->count()` (gabungan ekosistem) |
| 8 | **Total Tabel Fisik di MySQL** | **89 Tabel** | `count(DB::select('SHOW TABLES'))` pada database `db_absen` |
| 9 | **Pengguna Sistem (`users`)** | **191 Baris** | `DB::table('users')->count()` |
| 10 | **Data Induk Siswa (`students`)** | **163 Baris** | `DB::table('students')->count()` |
| 11 | **Data Pendidik (`teachers`)** | **14 Baris** | `DB::table('teachers')->count()` |
| 12 | **Profil Orang Tua (`parents`)** | **7 Baris** | `DB::table('parents')->count()` (8 user role parent) |
| 13 | **Log Presensi Gerbang (`attendances`)** | **215 Baris** | `DB::table('attendances')->count()` |
| 14 | **Log Presensi Mapel (`subject_attendances`)** | **4.367 Baris** | `DB::table('subject_attendances')->count()` |
| 15 | **Kasus Uji Otomatis (Automated Tests)** | **25 Kasus** | `php artisan test` (6 lulus, 19 gagal, 29 asersi) |
| 16 | **Target Tangkapan Layar (Screenshot)** | **27 ID** | Katalog pemetaan SS-01 s.d. SS-27 |
| 17 | **Tangkapan Layar Fisik Terverifikasi** | **25 Berkas** | Berkas PNG di `docs/LPJ/assets/screenshots/` |
| 18 | **Tangkapan Layar Belum Terverifikasi** | **2 ID** | SS-06 dan SS-08 (kondisi dinamis KBM) |
| 19 | **Tautan Rusak (Broken Links)** | **0 Tautan** | Evaluasi integritas direktori tangkapan layar |
| 20 | **Total Fitur Terpetakan** | **47 Fitur** | [Matriks_Fitur.md](file:///d:/laragon/www/siasek/aplikasi-absensi/docs/LPJ/Matriks_Fitur.md) (39 Terimplementasi, 6 Sebagian, 0 Belum, 2 Luar Scope) |

---

## B. PERBEDAAN DENGAN AUDIT SEBELUMNYA

Selama proses audit awal, terjadi beberapa perbedaan penyebutan angka yang disebabkan oleh variasi sudut pandang klasifikasi atau pembacaan sekilas:

| Metrik | Angka Audit Awal | Angka Verifikasi Aktual | Akar Perbedaan & Analisis Teknis |
|---|:---:|:---:|---|
| **Jumlah Model** | 34 Model | **32 Model** | Audit awal mencantumkan 34 karena memperkirakan adanya model pivot atau submodel. Penghitungan fisik file `.php` pada `app/Models` membuktikan jumlah riil tepat 32 berkas. |
| **Jumlah Controller** | 47 Controller | **68 Controller** | Audit awal hanya menghitung controller web utama tanpa menyertakan 15 controller API (`app/Http/Controllers/Api/`) dan 11 controller autentikasi Breeze (`Auth/`). Total seluruh berkas controller di direktori adalah 68 (atau 67 jika mengecualikan kelas dasar abstrak `Controller.php`). |
| **Tabel Database** | 67 Tabel | **89 Tabel** | Audit awal hanya mencatat 43 tabel presensi + 17 tabel LMS + 7 tabel Spatie (= 67 tabel). Namun, perintah SQL `SHOW TABLES` pada MySQL `db_absen` membuktikan ada **89 tabel fisik** (termasuk 29 tabel LMS, tabel framework, dan tabel relasi akademik tambahan). |
| **Berkas Migrasi** | 53 Berkas | **65 Berkas** | Sebagian dokumen menyebut 53 karena migrasi penyesuaian multi-semester dan perizinan manual belum terakumulasi. Faktanya di direktori `database/migrations/` terdapat 65 berkas migrasi lokal. |
| **Status Migrasi** | "67 Ran" | **65 Ran (Lokal) / 148 Ran (DB)** | Pencampuran definisi antara jumlah migrasi lokal repositori dengan jumlah total riwayat di tabel `migrations`. |
| **Fitur Luar Scope** | SMS + Portal Siswa | **WhatsApp + SPP** | Matriks Fitur (rujukan utama) secara eksplisit mendaftar Fitur 46 (WhatsApp/SMS Gateway) dan Fitur 47 (Modul Finansial/SPP). Siswa diidentifikasi via QR/wajah, bukan modul finansial. |
| **Tipe Server** | Hostinger Cloud | **Shared Hosting Hostinger** | Repositori pada `routes/web.php:503` menunjukkan path `/home/u478110651/presensi-smpn1biau/` yang merupakan struktur akun Shared Hosting cPanel/hPanel standar, bukan cloud server khusus. |

---

## C. HASIL VERIFIKASI TERBARU

### 1. Model Eloquent (32 Berkas)
Daftar seluruh 32 model terverifikasi pada `app/Models/`:
`AcademicYear`, `AdminConversation`, `AdminMessage`, `Announcement`, `Attendance`, `Calendar`, `Cocurricular`, `Conversation`, `Extracurricular`, `ExtracurricularAttendance`, `LeaveRequest`, `Level`, `Message`, `Notification`, `ParentModel`, `ParentStudentRequest`, `Schedule`, `SchoolClass`, `Semester`, `Setting`, `Student`, `StudentAnecdote`, `StudentPermit`, `Subject`, `SubjectAttendance`, `Teacher`, `TeacherAttendance`, `TeacherNote`, `TeacherSemesterReflection`, `TeachingAssignment`, `TeachingJournal`, `User`.

### 2. Berkas Migrasi & Status Basis Data
- **Jumlah Berkas Migrasi Lokal**: **65 berkas** di `database/migrations/`.
- **Status Migrasi Lokal**: **65 Ran** (seluruhnya tereksekusi pada batch 1 s.d. 108).
- **Jumlah Riwayat Tabel `migrations`**: **148 entri** (mengakomodasi migrasi gabungan ekosistem SIPADA & LMS Mokopani yang menggunakan skema bersama `db_absen`).
- **Jumlah Tabel Fisik di MySQL `db_absen`**: **89 tabel**, dengan pengelompokan fungsional:
  - 43 tabel modul presensi & master akademik sekolah
  - 29 tabel modul pembelajaran daring LMS Mokopani (`lms_*`)
  - 7 tabel Spatie Role-Based Access Control & Permission
  - 10 tabel framework Laravel (cache, jobs, sessions, migrations, token)

### 3. Controller (68 Berkas)
- Root Controller: 11 berkas
- Admin Controller: 21 berkas
- Api Controller: 15 berkas (12 di `Api/` + 3 di `Api/Parent/`)
- Auth Controller: 11 berkas
- Parent Controller: 3 berkas
- Principal Controller: 1 berkas
- Teacher Controller: 7 berkas
*(Total: 11 + 21 + 15 + 11 + 3 + 1 + 7 = 68 Controller)*

### 4. Hasil Pengujian Otomatis (`php artisan test`)
- Total Test Cases: **25 Kasus**
- Lulus (Passed): **6 Kasus (24.0%)**
- Gagal (Failed): **19 Kasus (76.0%)**
- Total Asersi: **29 Asersi**
- Durasi: **19.37 detik** (pada eksekusi terverifikasi 26 September 2026 pukul 11:48:37)
- Penyebab kegagalan 19 test case terbukti secara teknis: runner SQLite in-memory tidak menemukan kolom `deleted_at` pada model `User` (`SQLSTATE[HY000]: no such column: users.deleted_at`), di mana kolom tersebut ada di MySQL `db_absen` tetapi belum dibuatkan file migrasi lokal pada repositori presensi.

---

## D. KEPUTUSAN ANGKA FINAL

Untuk memastikan konsistensi mutlak di seluruh dokumen LPJ, diputuskan penggunaan angka-angka standar berikut:

1. **Jumlah Model**: **32 Model Eloquent**
2. **Jumlah Controller**: **68 Controller** (atau 67 kelas pengendali spesifik di luar kelas induk `Controller.php`)
3. **Jumlah Route**: **268 Rute**
4. **Jumlah Middleware**: **10 Middleware**
5. **Jumlah Berkas Migrasi Lokal**: **65 Berkas**
6. **Jumlah Status Migrasi Lokal**: **65 Berkas Berstatus Ran**
7. **Jumlah Record Tabel `migrations`**: **148 Entri**
8. **Jumlah Tabel Database**: **89 Tabel Fisik** pada basis data bersama `db_absen`
9. **Status Fitur**: **47 Fitur** (39 Terimplementasi Penuh, 6 Sebagian/Dialihkan ke SIPADA, 0 Belum Terverifikasi, 2 Di Luar Ruang Lingkup)
10. **Dua Fitur Luar Scope**:
    - Fitur 46: Notifikasi WhatsApp / SMS Gateway Berbayar Pihak Ketiga
    - Fitur 47: Modul Pembayaran SPP / Finansial Sekolah
11. **Lingkungan Server**: **Shared Hosting Hostinger (hPanel / Linux)**
12. **Hasil Uji Test Runner**: **25 Kasus Uji (6 Lulus, 19 Gagal, 29 Asersi)**
13. **Bukti Visual**: **27 Target ID, 25 Berkas PNG Terverifikasi, 2 Belum Diverifikasi (SS-06 dan SS-08), 0 Tautan Rusak**

---

## E. DAFTAR KLAIM YANG DIPERBAIKI

Dalam rangka menjaga etika rekayasa perangkat lunak dan asas pelaporan objektif:
1. **Penerapan Bahasa Faktual**: Seluruh terminologi promosi dihapus dan diganti dengan status capaian fungsional berbasis data: **83.0% terimplementasi penuh (39 fitur)** dan **12.8% dialihkan ke SIPADA (6 fitur)**.
2. **Klaim Keamanan Mutlak**: Istilah "aman sepenuhnya" disesuaikan menjadi "mekanisme proteksi standar CSRF dan Bcrypt terverifikasi aktif", dengan mempertahankan catatan temuan celah keamanan pada endpoint `/fix-storage-link`.
3. **Klaim Hosting "Hostinger Cloud"**: Dihapus dan dinormalisasi menjadi **Shared Hosting Hostinger** sesuai struktur direktori akun Linux `/home/u478110651/`.
4. **Klaim "27 Screenshot Berhasil Dicapture"**: Dikoreksi secara tegas bahwa **aktual yang berhasil dicapture adalah 25 berkas gambar**, sedangkan 2 target belum dapat diverifikasi.

---

## F. DAFTAR INFORMASI YANG MASIH BELUM DAPAT DIVERIFIKASI

Hanya terdapat dua item yang secara jujur dinyatakan belum dapat diverifikasi secara visual pada lingkungan lokal:
1. **SS-06 (Pemindai Presensi Mapel Kelas)**:
   - *Alasan Teknis*: Memerlukan adanya jadwal KBM aktif yang sesuai dengan hari kerja dan jam pelajaran berjalan saat pengujian berlangsung.
   - *Bukti Alternatif*: Kode sumber view Blade tersedia lengkap pada `resources/views/teacher/subject_attendance_scanner.blade.php`.
2. **SS-08 (Dokumen Cetak Presensi Mapel Berkop Resmi)**:
   - *Alasan Teknis*: Memerlukan parameter filter tanggal riil pelaksanaan KBM semester aktif.
   - *Bukti Alternatif*: Kode sumber view Blade tersedia lengkap pada `resources/views/teacher/report_print.blade.php`.

*Sesuai kaidah audit ilmiah, tim pengembang tidak memalsukan atau merekayasa tangkapan layar untuk kedua item tersebut.*

---

## G. TABEL REKONSILIASI SELURUH ANGKA DALAM DOKUMEN LPJ

| ANGKA | LOKASI DALAM LPJ | SUMBER DATA EMPIRIS | STATUS REKONSILIASI |
|---|---|---|:---:|
| **32** | Bab II.3, Bab III.6 | Direktori `app/Models` | VALID (32 file model) |
| **68** | Bab II.3, Bab III.1 | Direktori `app/Http/Controllers` (rekursif) | VALID (68 file controller) |
| **268** | Bab II.3, Bab III.7 | Perintah `php artisan route:list` | VALID (268 rute terdaftar) |
| **10** | Bab II.3, Bab III.5 | Direktori `app/Http/Middleware` | VALID (10 file middleware) |
| **65** | Bab II.3, Bab III.6 | Direktori `database/migrations` & `migrate:status` | VALID (65 berkas migrasi lokal Ran) |
| **148** | Bab III.6, Catatan DB | Tabel `migrations` di MySQL `db_absen` | VALID (148 riwayat migrasi bersama) |
| **89** | Bab II.3, Bab III.6 | Query `SHOW TABLES` di MySQL `db_absen` | VALID (89 tabel fisik aktif) |
| **191** | Bab III.6 | Query `SELECT COUNT(*) FROM users` | VALID (191 data pengguna) |
| **163** | Bab III.6 | Query `SELECT COUNT(*) FROM students` | VALID (163 data siswa) |
| **14** | Bab III.6 | Query `SELECT COUNT(*) FROM teachers` | VALID (14 data guru) |
| **7** | Bab III.6 | Query `SELECT COUNT(*) FROM parents` | VALID (7 data profil orang tua) |
| **8** | Bab III.3 | Query `SELECT COUNT(*) FROM users WHERE role='parent'` | VALID (8 akun user ortu) |
| **215** | Bab III.8 | Query `SELECT COUNT(*) FROM attendances` | VALID (215 transaksi gerbang) |
| **4.367** | Bab III.9 | Query `SELECT COUNT(*) FROM subject_attendances` | VALID (4.367 transaksi mapel) |
| **25** | Bab IV.2 | Perintah `php artisan test` | VALID (25 kasus uji otomatis) |
| **6** | Bab IV.2 | Perintah `php artisan test` (passed) | VALID (6 kasus uji lulus) |
| **19** | Bab IV.2 | Perintah `php artisan test` (failed) | VALID (19 kasus uji gagal) |
| **29** | Bab IV.2 | Perintah `php artisan test` (assertions) | VALID (29 asersi dievaluasi) |
| **47** | Bab III.2, Bab V.1 | Dokumen [Matriks_Fitur.md](file:///d:/laragon/www/siasek/aplikasi-absensi/docs/LPJ/Matriks_Fitur.md) | VALID (47 fitur terpetakan) |
| **39** | Bab III.2, Bab V.1 | Fitur terverifikasi fungsional | VALID (83.0% implementasi penuh) |
| **6** | Bab III.2, Bab V.1 | Fitur dialihkan ke SIPADA | VALID (12.8% sebagian/SIPADA) |
| **2** | Bab III.2, Bab V.1 | Fitur luar scope (WhatsApp & SPP) | VALID (4.2% di luar lingkup) |
| **27** | Bab IV.4, Lampiran E | Dokumen [Daftar_Screenshot.md](file:///d:/laragon/www/siasek/aplikasi-absensi/docs/LPJ/Daftar_Screenshot.md) | VALID (27 target screenshot ID) |
| **25** | Bab IV.4, Lampiran E | Direktori `docs/LPJ/assets/screenshots` | VALID (25 berkas screenshot riil) |
| **2** | Bab IV.4, Lampiran E | SS-06 dan SS-08 | VALID (2 belum terverifikasi) |
| **0** | Bab IV.4, Lampiran E | Validasi direktori fisik screenshot | VALID (0 broken links) |

---

## 9. Rekonsiliasi Aspek Administratif, Identitas Pemilik/Pengembang, dan Skema Layanan (Tahap 5A-2)

Berdasarkan penyesuaian administratif resmi pada Tahap 5A-2:

| Parameter | Fakta Administratif Terekonsiliasi | Rujukan Dokumen / Bagian | Status Verifikasi |
|---|---|---|:---:|
| **Pemilik/Pemegang Hak Aplikasi** | **Zahradev** | Cover, Pengesahan, Bab I, Bab II.1, Bab II.7, Bab V.1 | VALID |
| **Pengembang** | **Zahradev** | Cover, Pengesahan, Bab I, Bab II.1, Bab II.7, Bab V.1 | VALID |
| **Pengguna Layanan** | **SMP Negeri 1 Biau** | Cover, Pengesahan, Bab I, Bab II.1, Bab II.7, Bab V.1 | VALID |
| **Bentuk Pemanfaatan** | **Sewa/Penggunaan layanan aplikasi** (mencakup penggunaan dan pengembangan/penyesuaian) | Bab I.1, Bab II.1, Bab II.7, Bab V.1 | VALID |
| **Tarif Layanan** | **Rp1.000,- (seribu rupiah) per siswa per bulan** | Bab I.1, Bab II.1, Bab II.7, Bab V.1 | VALID |
| **Keberlanjutan Penggunaan** | **Mengikuti ketentuan kerja sama dan pembayaran layanan bulanan** | Bab II.1, Bab II.7, Bab V.1 | VALID |
| **Batas Data Tagihan** | Tidak menghitung total tagihan bulanan atau mengalikan jumlah siswa tanpa data administratif resmi | Bab I, Bab II, Bab V | VALID |
| **Format Dokumen Resmi** | Menggunakan bahasa Indonesia formal dan bebas emoji status grafis | Matriks Fitur & Matriks Pengujian | VALID |

### Pernyataan Ketentuan Layanan dan Batasan Administratif
Aplikasi Presensi SIASEK dibuat dan dikembangkan oleh Zahradev sebagai pemilik/pemegang hak atas aplikasi sesuai dengan ketentuan kerja sama yang berlaku. SMP Negeri 1 Biau menggunakan aplikasi tersebut sebagai pengguna layanan melalui skema sewa/penggunaan layanan aplikasi yang mencakup penggunaan serta pengembangan/penyesuaian sistem dengan tarif Rp1.000,- per siswa per bulan. Keberlanjutan hak penggunaan layanan mengikuti ketentuan kerja sama dan pembayaran layanan bulanan yang disepakati para pihak, sesuai dengan fakta administratif yang telah ditetapkan tanpa menambahkan klausul atau klaim sepihak di luar bukti dokumen project yang ada.


---

## 10. Rekonsiliasi Riwayat Pengembangan Repositori (Tahap 5A-4C)

Berdasarkan hasil verifikasi path perubahan (*changed files path inspection*) pada seluruh komit repositori per 26 September 2026:

| Parameter Riwayat | Fakta Audit Awal (Snapshot 5A-3) | Fakta Audit Terbaru (Tahap 5A-4C) | Akar Perbedaan & Analisis Teknis | Status Verifikasi |
|---|---|---|---|:---:|
| **Total Commit Repository `origin/main`** | 111 Komit | **458 Komit** | Seluruh 458 komit pada remote GitHub `ciemilsalim/aplikasi-absensi.git` ter-push 100%. | VALID (RPG-03) |
| **Commit Pengembangan Aplikasi Yang Teridentifikasi** | 111 Komit | **457 Komit (18 Juni 2025 s.d. 04 Sept 2026)** | 457 komit terbukti secara empiris memodifikasi komponen aplikasi (`app/`, `routes/`, `resources/`, `database/`, `config/`, `public/`, dsb.), terdiri dari 456 komit murni aplikasi + 1 komit hybrid `c2b78ff`. | VALID (RPG-01) |
| **Commit Dokumentasi / Tooling** | Tidak dipisahkan | **2 Komit** (`bcc3f7e` & `b131b87`) | 1 komit `README.md` di remote + 1 komit agent skill LPJ pada lokal `HEAD`. | VALID (RPG-05) |
| **Commit Hybrid (App + Docs/Metadata)** | Tidak dihitung | **1 Komit** (`c2b78ff` / init repo) | Inisialisasi awal Breeze memuat berkas aplikasi beserta `.gitignore` dan `README.md`. | VALID (RPG-01) |
| **Last Application Development Commit** |Tidak teridentifikasi | `f519ebde1f7ba4e56e1de29216c60f51d46d9407` (`f519ebd` / 04 Sept 2026) | Commit puncak pengembangan aplikasi yang mengubah controller, model `Student`, dan `sync-hpanel.php`. | VALID (RPG-05) |
| **First LPJ Tooling Commit** | Tidak teridentifikasi | `b131b87fb77ca41e1d2ef0ff21fa6265dcbce20d` (`b131b87` / 26 Sept 2026) | Commit awal pemasangan agent skill documentation `lpj-generator`. | VALID (RPG-05) |
| **Rentang Pengembangan Kode Aplikasi** | 18 Juni 2025 s.d. 02 Agustus 2025 | **18 Juni 2025 s.d. 04 September 2026 (444 hari kalender)** | Aktivitas komit faktual pada komponen aplikasi berlangsung dari Juni 2025 sampai September 2026. | VALID (RPG-01) |
| **Rentang Dokumentasi LPJ** | Tidak dipisahkan | **26 September 2026** | Komit tooling LPJ dan penyusunan berkas laporan pertanggungjawaban di `docs/LPJ/`. | VALID (RPG-05) |

### Catatan Rekonsiliasi Verifikasi Path Commit
Verifikasi path perubahan membuktikan bahwa dari **458 Total Commit Repository `origin/main`**, sebanyak **457 komit secara empiris terverifikasi sebagai Komit Pengembangan Aplikasi Yang Teridentifikasi** (termasuk 1 komit hybrid inisialisasi awal). Komit aplikasi terakhir (`f519ebd` pada 04 September 2026) telah 100% ter-push di remote GitHub. Rincian lengkap tersimpan pada berkas [Riwayat_Pengembangan.md](file:///d:/laragon/www/siasek/aplikasi-absensi/docs/LPJ/Riwayat_Pengembangan.md).
