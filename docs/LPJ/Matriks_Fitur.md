# MATRIKS KELENGKAPAN FITUR APLIKASI
**Aplikasi Presensi SIASEK (Sistem Kehadiran Real-time)**  
*Pemilik & Pengembang: Zahradev | Pengguna Layanan: SMP Negeri 1 Biau*  
*Skema: Sewa/Penggunaan Layanan Aplikasi (Rp1.000,-/siswa/bulan)*  
*Tanggal Evaluasi: 26 September 2026*  
*Dokumen Rujukan: docs/LPJ/feature-inventory.md*

---

## 1. Rekapitulasi Statistik Status Fitur

Berdasarkan audit faktual terhadap 47 fitur teridentifikasi:

| Klasifikasi Status | Jumlah Fitur | Persentase | Definisi Operasional |
|---|---|---|---|
| **Terimplementasi** | **39** | **83.0 %** | Kode controller, model, antarmuka Blade, dan tabel database aktif dan berfungsi penuh. |
| **Terimplementasi sebagian** | **6** | **12.8 %** | Kode program lengkap tersedia di repository, namun dialihkan di runtime melalui middleware `RedirectToSipada` karena dipusatkan di SIPADA. |
| **Belum terverifikasi** | **0** | **0.0 %** | Seluruh klaim kode berhasil diuji dan diverifikasi terhadap database lokal aktif. |
|  **Tidak Ditemukan** | **2** | **4.2 %** | Fitur berada di luar ruang lingkup aplikasi presensi (WhatsApp Gateway berbayar & Keuangan/SPP). |
| **TOTAL KESELURUHAN** | **47** | **100.0 %** | **Total Fitur Terpetakan** |

---

## 2. Matriks 47 Fitur Berdasarkan Domain Sistem

### Klaster I: Presensi Gerbang & Keamanan Sekolah (Gate Attendance)

| No | Nama Fitur | Bukti Source Code | Bukti Runtime / Database / UI | Status | Catatan Teknis |
|---|---|---|---|---|---|
| 1 | **Pemindai Masuk Gerbang Siswa (Kamera Web / QR / Barcode)** | `app/Http/Controllers/AttendanceController.php:32-150` | `resources/views/scanner.blade.php`, tabel `attendances` (215 baris) | Terimplementasi | Mendukung barcode/QR NIS maupun NIS-UUID. Audio feedback terpasang. |
| 2 | **Pengenalan Wajah Siswa Gerbang (Face Recognition)** | `app/Http/Controllers/AttendanceController.php:178-210` | `public/models/`, kolom `students.face_descriptor` | Terimplementasi | Ekstraksi 128-dimensi vektor wajah via Face-API.js di browser. |
| 3 | **Validasi Radius GPS Gerbang (Haversine Formula)** | `app/Http/Controllers/AttendanceController.php:96-108` | Tabel `settings` (`school_latitude`, `school_longitude`, `attendance_radius`) | Terimplementasi | Menolak scan jika perangkat berada di luar radius sekolah (toleransi 100m). |
| 4 | **Pencegahan Absensi Hari Libur & Akhir Pekan** | `app/Http/Controllers/AttendanceController.php:69-94` | Model `Calendar`, tabel `calendars` (2 baris) | Terimplementasi | Pengecekan otomatis `isWeekend()` dan sinkronisasi hari libur kalender. |
| 5 | **Presensi Pulang Siswa (Clock-Out)** | `app/Http/Controllers/AttendanceController.php:123-150` | Kolom `attendances.checkout_time`, setting `jam_pulang` | Terimplementasi | Menolak absen pulang sebelum batas jam pulang resmi yang dikonfigurasi. |
| 6 | **Proteksi Duplikasi & Siswa Berstatus Izin/Sakit** | `app/Http/Controllers/AttendanceController.php:114-131` | Tabel `attendances.status` | Terimplementasi | Siswa yang telah tercatat izin/sakit pada hari berjalan ditolak memindai. |
| 7 | **Pemindai Dispensasi Masuk/Keluar Gerbang (Permit Scanner)** | `app/Http/Controllers/PermitController.php:14-149` | `resources/views/permit-scanner.blade.php`, tabel `student_permits` (10 baris) | Terimplementasi | Mencatat `time_out` (alasan keluar), status `izin_keluar`, dan `time_in` saat kembali. |

---

### Klaster II: Presensi Pembelajaran di Kelas & Kegiatan Sekolah

| No | Nama Fitur | Bukti Source Code | Bukti Runtime / Database / UI | Status | Catatan Teknis |
|---|---|---|---|---|---|
| 8 | **Presensi Siswa Per Mata Pelajaran (KBM)** | `app/Http/Controllers/Teacher/SubjectAttendanceController.php:53-250` | `resources/views/teacher/subject_attendance_scanner.blade.php`, tabel `subject_attendances` (4.367 baris) | Terimplementasi | Dioperasikan guru mapel via webcam QR, face recognition, dan tombol entri cepat. |
| 9 | **Pencatatan Siswa Bolos pada Sesi Pelajaran** | `app/Http/Controllers/Teacher/SubjectAttendanceController.php` & migrasi `2025_08_16_171551` | Enum `subject_attendances.status` mencakup `'bolos'` | Terimplementasi | Menandai siswa yang hadir di gerbang tetapi tidak mengikuti jam pelajaran kelas. |
| 10 | **Koreksi & Rekapitulasi Presensi Mapel** | `app/Http/Controllers/Teacher/SubjectAttendanceController.php:260-450` | `resources/views/teacher/report_preview.blade.php` | Terimplementasi | Form filter tanggal dan preview matriks kehadiran siswa sebelum dicetak. |
| 11 | **Cetak Dokumen Resmi Presensi Mapel** | `app/Http/Controllers/Teacher/SubjectAttendanceController.php:460-550` | `resources/views/teacher/report_print.blade.php` | Terimplementasi | Format dokumen siap cetak kertas resmi berkop sekolah dan kolom tanda tangan. |
| 12 | **Analitik Grafik Kehadiran Mapel** | `app/Http/Controllers/Teacher/SubjectAttendanceController.php:560-650` | `resources/views/teacher/subject_charts.blade.php` | Terimplementasi | Grafik interaktif berbasis Chart.js per kelas per semester. |
| 13 | **Presensi Kegiatan Ekstrakurikuler** | `app/Http/Controllers/Teacher/ExtracurricularAttendanceController.php:18-577` | `resources/views/teacher/extracurricular_attendance/`, tabel `extracurricular_attendances` (3 baris) | Terimplementasi | Khusus pembina ekskul (`checkCoachAccess`), scan QR, entri manual, dan cetak. |

---

### Klaster III: Presensi & Administrasi Guru (Teacher Module)

| No | Nama Fitur | Bukti Source Code | Bukti Runtime / Database / UI | Status | Catatan Teknis |
|---|---|---|---|---|---|
| 14 | **Presensi Mandiri Guru (GPS + Face + Selfie)** | `app/Http/Controllers/Teacher/TeacherAttendanceController.php:14-167` | `resources/views/teacher/attendance/`, tabel `teacher_attendances` (1 baris), `storage/app/public/teacher_attendances/` | Terimplementasi | Menggunakan `GpsValidationTrait`, verifikasi wajah, dan bukti foto selfie. |
| 15 | **Buku Jurnal Harian Mengajar Guru** | `app/Http/Controllers/Teacher/TeachingJournalController.php:22-350` | `resources/views/teacher/journals/index.blade.php`, tabel `teaching_journals` | Terimplementasi | Mencatat tanggal, JP, materi pokok, ringkasan KBM, hambatan, dan solusi. |
| 16 | **Supervisi & Verifikasi Jurnal oleh Manajemen** | `app/Http/Controllers/Admin/AdminTeachingJournalController.php:15-180` | `resources/views/admin/teaching_journals/`, rute `admin.teaching_journals.verify` | Terimplementasi | Verifikasi berkas jurnal guru oleh Admin, Wakasek Kurikulum, atau Kepala Sekolah. |
| 17 | **Refleksi Semester Pembelajaran oleh Guru** | `app/Http/Controllers/Teacher/TeachingJournalController.php:400-520` | `resources/views/teacher/journals/reflection.blade.php`, tabel `teacher_semester_reflections` | Terimplementasi | Evaluasi kendala dan rencana perbaikan pembelajaran semester berikutnya. |
| 18 | **Pencatatan Anekdot Sikap & Karakter Siswa** | `app/Http/Controllers/Teacher/StudentAnecdoteController.php:17-383` | `resources/views/teacher/anecdotes/`, tabel `student_anecdotes` | Terimplementasi | Mencatat perkembangan siswa pada kategori: akademik, kehadiran, dan sikap. |
| 19 | **Catatan Evaluasi Guru (Teacher Notes)** | `app/Http/Controllers/Teacher/DashboardController.php:1665-1722` | Tabel `teacher_notes` (9 baris data) | Terimplementasi | Catatan privat evaluasi siswa oleh wali kelas. |
| 20 | **Dasbor Guru & Wali Kelas** | `app/Http/Controllers/Teacher/DashboardController.php` | `resources/views/teacher/dashboard.blade.php` | Terimplementasi | Menampilkan daftar siswa kelas bimbingan, entri cepat, dan tombol ganti foto siswa. |

---

### Klaster IV: Kemitraan Orang Tua (Parent Module)

| No | Nama Fitur | Bukti Source Code | Bukti Runtime / Database / UI | Status | Catatan Teknis |
|---|---|---|---|---|---|
| 21 | **Onboarding Mandiri Akun Orang Tua Baru** | `app/Http/Controllers/Parent/ParentOnboardingController.php` & `EnsureParentOnboardingCompleted.php` | `resources/views/parent/onboarding.blade.php`, tabel `parents.is_onboarding_completed` | Terimplementasi | Wizard 3 langkah wajib verifikasi profil dan pencarian data anak sebelum masuk dasbor. |
| 22 | **Verifikasi Klaim Relasi Orang Tua - Siswa** | `app/Http/Controllers/Admin/ParentVerificationController.php:12-158` | `resources/views/admin/parent_verification/index.blade.php`, tabel `parent_student` (8 baris) | Terimplementasi | Admin/Wali Kelas menyetujui klaim anak berdasarkan NIS, menghubungkan ortu-siswa. |
| 23 | **Dasbor Pemantauan Orang Tua (Monitoring Anak)** | `app/Http/Controllers/Parent/DashboardController.php` | `resources/views/parent/dashboard.blade.php` | Terimplementasi | Menampilkan log kehadiran gerbang, kehadiran mapel, jadwal, ekskul, dan status izin. |
| 24 | **Pengajuan Permohonan Izin Online oleh Orang Tua** | `app/Http/Controllers/Parent/LeaveRequestController.php:11-79` | `resources/views/parent/leave_requests/create.blade.php`, tabel `leave_requests` (14 baris) | Terimplementasi | Mengunggah surat dokter / bukti izin ke `storage/app/public/attachments`. |
| 25 | **Intervensi Manual Permohonan Izin oleh TU/Admin** | `app/Http/Controllers/Admin/LeaveRequestController.php:20-210` | `resources/views/admin/leave_requests/index.blade.php` | Terimplementasi | TU dapat menginput surat izin fisik yang dibawa langsung tanpa aplikasi ortu. |
| 26 | **Persetujuan Izin & Sinkronisasi Otomatis Presensi** | `app/Http/Controllers/Admin/LeaveRequestController.php:240-350` & `Teacher\LeaveRequestController.php` | Tabel `leave_requests`, tabel `attendances`, tabel `subject_attendances` | Terimplementasi | Saat disetujui, sistem atomik mengisi status `'izin'`/`'sakit'` pada log harian & mapel. |
| 27 | **Komunikasi Dua Arah Guru <-> Orang Tua (Chat)** | `app/Http/Controllers/ChatController.php:16-175` | `resources/views/chat/index.blade.php`, tabel `conversations` (8) & `messages` (31) | Terimplementasi | Obrolan pribadi terenkapsulasi dengan penanda status pesan terbaca (`read_at`). |
| 28 | **Saluran Bantuan Orang Tua <-> Admin/TU** | `app/Http/Controllers/ChatController.php:158-160` & `AdminChatController.php` | Tabel `admin_conversations` (12) & `admin_messages` (42) | Terimplementasi | Jalur konsultasi administrasi sekolah bagi orang tua langsung ke admin. |

---

### Klaster V: Pengawasan Pimpinan & Pelaporan (Executive & Reporting)

| No | Nama Fitur | Bukti Source Code | Bukti Runtime / Database / UI | Status | Catatan Teknis |
|---|---|---|---|---|---|
| 29 | **Dasbor Eksekutif Kepala Sekolah** | `app/Http/Controllers/Principal/PrincipalDashboardController.php:22-329` | `resources/views/principal/dashboard.blade.php`, rute `principal.diag` | Terimplementasi | Metrik persentase kehadiran sekolah, kehadiran guru dinas, sesi KBM, dan izin permit. |
| 30 | **Dasbor Pemantauan Piket / Admin Real-Time** | `app/Http/Controllers/Admin/DashboardController.php` | `resources/views/admin/dashboard.blade.php` | Terimplementasi | Layar monitor gerbang menampilkan siswa masuk, terlambat, izin detik demi detik. |
| 31 | **Cetak Laporan Presensi PDF (DomPDF)** | `app/Http/Controllers/Admin/ReportController.php:120-450` | `barryvdh/laravel-dompdf` v3.1.0 | Terimplementasi | Laporan harian, mingguan, bulanan, rekapitulasi, dan triwulan berkop resmi. |
| 32 | **Ekspor Rekapitulasi Presensi ke Excel (XLSX)** | `app/Http/Controllers/Teacher/DashboardController.php:1556-1663` | `app/Exports/AttendanceReportExport.php`, `maatwebsite/excel` v3.1.66 | Terimplementasi | Spreadsheet lengkap tanggal 1-31, jumlah H/S/I/A, hari libur, dan tandatangan kepsek. |

---

### Klaster VI: Otomasi, Ekosistem, & Infrastruktur (Ecosystem & Platform)

| No | Nama Fitur | Bukti Source Code | Bukti Runtime / Database / UI | Status | Catatan Teknis |
|---|---|---|---|---|---|
| 33 | **Otomasi Pengecekan Siswa Alpa (Command 10:00)** | `app/Console/Commands/CheckAbsentStudents.php:12-84` | `bootstrap/app.php:38`, tabel `notifications` (8), `app_notifications` (456) | Terimplementasi | Otomatis berjalan setiap hari kerja pukul 10:00, menandai alpa dan notifikasi ortu. |
| 34 | **Notifikasi Sistem In-App (Database Notifications)** | `app/Http/Controllers/NotificationController.php:9-25` | Model `Notification`, tabel `notifications` | Terimplementasi | Badge notifikasi pada navbar dan fungsi penanda dibaca (`markAsRead`). |
| 35 | **Single Sign-On (SSO) ke LMS Mokopani** | `app/Http/Controllers/SSOController.php:11-98` | Tabel `sso_tokens` (9 baris data), rute `/sso/lms` | Terimplementasi | Token acak 60 karakter masa aktif 2 jam untuk login mulus ke LMS Mokopani. |
| 36 | **Penerima Autentikasi SSO Masuk dari Sistem Luar** | `app/Http/Controllers/Auth/SsoLoginController.php:10-50` | Rute `/sso/login?token=...` | Terimplementasi | Validasi token SSO satu kali pakai (*one-time use*) dan regenerasi sesi browser. |
| 37 | **Pengalih Semester & Tahun Ajaran Global** | `app/Http/Controllers/AcademicPeriodController.php` & `SetAcademicPeriod.php` | Session `active_semester_id`, tabel `semesters` (3), `academic_years` (2) | Terimplementasi | Pengguna dapat berpindah konteks semester lampau via dropdown header. |
| 38 | **Rekonstruksi Riwayat Kelas Multi-Semester** | Migrasi `2026_09_04_000001` & `2026_09_04_000002` | Tabel `class_student` (303) & `student_class_histories` (28) | Terimplementasi | Skrip migrasi rekursif cerdas memetakan penempatan kelas siswa pada semester lampau. |
| 39 | **Sinkronisasi Akun Pengguna Siswa** | `app/Console/Commands/SyncStudentUsers.php:7-52` | Command `students:sync-users`, tabel `users` (191) | Terimplementasi | Otomatis membuat akun user format email `nis@mokopani.com` bagi siswa baru. |
| 40 | **Kustomisasi Tampilan & Logo Sekolah** | `app/Http/Controllers/Admin/SettingController.php:23-112` | `resources/views/admin/settings/appearance.blade.php`, `storage/app/public/logos/` | Terimplementasi | Mengunggah logo instansi dan toggle tema gelap (*dark mode*). |
| 41 | **REST API untuk Aplikasi Mobile (Sanctum)** | `routes/api.php:1-96` | 15 Controller pada `app/Http/Controllers/Api/`, tabel `personal_access_tokens` (5) | Terimplementasi | Endpoint JSON presensi, jadwal, perizinan, dan chat dilindungi token Sanctum. |
| 42 | **Dukungan Progressive Web App (PWA) & Mode Offline** | `public/manifest.json`, `public/sw.js` | `resources/views/offline.blade.php`, rute `/offline` | Terimplementasi | PWA standalone mode dan service worker caching offline. |
| 43 | **CRUD Master Data Sekolah (Siswa, Guru, Ortu, Kelas, Mapel, Jadwal, Kalender)** | `app/Http/Controllers/Admin/*Controller.php` | Dialihkan oleh `RedirectToSipada.php` (lines 228-300 di `web.php`) | Terimplementasi sebagian | Berkas controller dan view lengkap tersedia, namun dialihkan ke SIPADA di runtime. |
| 44 | **Pencadangan Basis Data & Berkas (Backup System)** | `app/Http/Controllers/Admin/BackupController.php` & `config/backup.php` | Terdaftar pada rute `admin.backup.*`, dialihkan oleh `sipada.redirect` | Terimplementasi sebagian | Paket `spatie/laravel-backup` terpasang, namun akses admin dialihkan ke SIPADA. |
| 45 | **Pengaturan Identitas Lembaga & Waktu Global** | `app/Http/Controllers/Admin/SettingController.php:14-40` | Rute `admin.settings.identity` dialihkan oleh `sipada.redirect` | Terimplementasi sebagian | Nilai tersimpan di `settings`, namun form ubah identitas dialihkan ke SIPADA. |
| 46 | **Notifikasi WhatsApp / SMS Gateway Eksternal** | Tidak ditemukan dependensi gateway eksternal | Hanya notifikasi in-app database internal |  Tidak ditemukan | Pengiriman pesan kehadiran siswa hanya menggunakan notifikasi in-app lokal. |
| 47 | **Modul Pembayaran SPP / Finansial Sekolah** | Tidak ditemukan model/controller pembayaran | Tidak ada skema tabel keuangan di `db_absen` |  Tidak ditemukan | Berada di luar ruang lingkup aplikasi presensi (ditangani sistem terpisah). |
