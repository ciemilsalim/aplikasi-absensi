# DAFTAR BUKTI IMPLEMENTASI & AUDIT SISTEM (EVIDENCE INVENTORY)
**Aplikasi Presensi SIASEK (Sistem Kehadiran Real-time)**  
*Pemilik & Pengembang: Zahradev | Pengguna Layanan: SMP Negeri 1 Biau*  
*Skema: Sewa/Penggunaan Layanan Aplikasi (Rp1.000,-/siswa/bulan)*  
*Tanggal Inventarisasi: 26 September 2026*

---

Setiap klaim teknis dan fungsional dalam dokumen Laporan Pertanggungjawaban (LPJ) didukung oleh bukti nyata (*empirical evidence*) yang diberi kode identifikasi standar:

| ID Bukti | Kategori Bukti | Lokasi Berkas / Entitas | Kutipan / Parameter Bukti | Deskripsi Pembuktian |
|---|---|---|---|---|
| **E-01** | Framework & Runtime | [composer.json](file:///d:/laragon/www/siasek/aplikasi-absensi/composer.json#L11) & [composer.lock](file:///d:/laragon/www/siasek/aplikasi-absensi/composer.lock#L1611) | `"laravel/framework": "v12.56.0"` | Membuktikan aplikasi berjalan di atas Laravel 12.56.0 dan PHP 8.2.1 |
| **E-02** | Konfigurasi Aplikasi | [bootstrap/app.php](file:///d:/laragon/www/siasek/aplikasi-absensi/bootstrap/app.php#L8-L43) | `Application::configure()` | Pendaftaran rute, 10 middleware kustom, dan cron scheduler 10:00 |
| **E-03** | Rute & Interseptor | [routes/web.php](file:///d:/laragon/www/siasek/aplikasi-absensi/routes/web.php#L228-L300) | `middleware(['role:admin', 'sipada.redirect'])` | Bukti pengalihan CRUD master data ke SIPADA untuk integritas ekosistem |
| **E-04** | Pemindai Gerbang | [AttendanceController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/AttendanceController.php#L32-L150) | `storeAttendance()` | Validasi format QR NIS-UUID, weekend, kalender libur, dan jam pulang |
| **E-05** | Geofencing Haversine | [AttendanceController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/AttendanceController.php#L96-L108) | `haversineDistance()` | Validasi radius geolokasi perangkat scanner terhadap koordinat sekolah |
| **E-06** | Model Neural AI | Direktori [public/models/](file:///d:/laragon/www/siasek/aplikasi-absensi/public/models) | `ssd_mobilenetv1`, `face_landmark_68`, `face_recognition` | Bobot model Face-API.js tersimpan lokal untuk pengenalan wajah on-device |
| **E-07** | Dispensasi Gerbang | [PermitController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/PermitController.php#L36-L120) | `storePermit()` | Logika izin keluar `time_out` dan kembali `time_in` pada tabel `student_permits` |
| **E-08** | Presensi Mapel & Bolos | [SubjectAttendanceController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Teacher/SubjectAttendanceController.php#L53-L120) | `showScanner()`, status enum `'bolos'` | Pemindai presensi KBM kelas dan pencatatan siswa bolos di jam mapel |
| **E-09** | Jurnal Mengajar Guru | [TeachingJournalController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Teacher/TeachingJournalController.php#L33-L100) | `index()`, `store()`, `reflection()` | Buku jurnal harian mengajar guru mata pelajaran dan refleksi semester |
| **E-10** | Supervisi Pimpinan | [AdminTeachingJournalController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Admin/AdminTeachingJournalController.php#L15-L80) | `verify()`, `batchVerify()` | Fitur verifikasi supervisi jurnal guru oleh Admin, Wakasek, dan Kepala Sekolah |
| **E-11** | Catatan Anekdot | [StudentAnecdoteController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Teacher/StudentAnecdoteController.php#L40-L100) | `storeOrUpdate()`, `getForStudent()` | Catatan perkembangan karakter siswa kategori akademik, kehadiran, dan sikap |
| **E-12** | Presensi Mandiri Guru | [TeacherAttendanceController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Teacher/TeacherAttendanceController.php#L17-L80) | `GpsValidationTrait`, upload selfie | Presensi mandiri guru via smartphone dengan geofencing GPS dan foto selfie |
| **E-13** | Ekstrakurikuler | [ExtracurricularAttendanceController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Teacher/ExtracurricularAttendanceController.php#L23-L60) | `checkCoachAccess()` | Presensi pembina kegiatan ekstrakurikuler sekolah |
| **E-14** | Perizinan Orang Tua | [Parent\LeaveRequestController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Parent/LeaveRequestController.php#L45-L78) | `store()`, `mimes:jpg,jpeg,png,pdf` | Pengajuan izin online orang tua dengan lampiran surat keterangan dokter |
| **E-15** | Intervensi Manual TU | [Admin\LeaveRequestController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Admin/LeaveRequestController.php#L20-L80) | `storeManual()`, `studentsByClass()` | Input surat izin siswa fisik oleh staf TU secara cepat di sekolah |
| **E-16** | Onboarding Orang Tua | [ParentOnboardingController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Parent/ParentOnboardingController.php) & Middleware | `EnsureParentOnboardingCompleted` | Alur wajib 3 langkah bagi orang tua baru untuk verifikasi dan klaim anak |
| **E-17** | Verifikasi Hubungan | [ParentVerificationController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Admin/ParentVerificationController.php#L15-L80) | `approve()`, `parent_student` | Pengesahan klaim orang tua oleh wali kelas/admin menghubungkan relasi anak |
| **E-18** | Dasbor Kepala Sekolah | [PrincipalDashboardController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Principal/PrincipalDashboardController.php#L25-L90) | `index()`, rute `principal.diag` | Ringkasan eksekutif kehadiran harian sekolah, rasio kehadiran guru, dan sesi KBM |
| **E-19** | Cetak Laporan PDF | [ReportController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Admin/ReportController.php#L120-L200) | `Barryvdh\DomPDF\Facade\Pdf` | Cetak dokumen PDF rekap presensi harian, mingguan, bulanan, dan triwulan |
| **E-20** | Ekspor Excel XLSX | [Teacher\DashboardController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Teacher/DashboardController.php#L1556-L1663) | `AttendanceReportExport` | Ekspor matriks presensi bulanan lengkap siswa ke berkas spreadsheet XLSX |
| **E-21** | Komunikasi Dua Arah | [ChatController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/ChatController.php#L21-L70) | `Conversation`, `Message`, `AdminMessage` | Fitur obrolan privat wali kelas - ortu dan saluran aspirasi admin - ortu |
| **E-22** | Otomasi Siswa Alpa | [CheckAbsentStudents.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Console/Commands/CheckAbsentStudents.php#L12-L84) | `attendance:check-absent` | Command artisan penanda alpa otomatis pada 10:00 dan notifikasi in-app ortu |
| **E-23** | Single Sign-On (SSO) | [SSOController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/SSOController.php#L34-L96) | Token acak 60 karakter, 2 jam | Integrasi SSO login terpadu antara Presensi dengan LMS Mokopani |
| **E-24** | Rute SSO Masuk | [SsoLoginController.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Controllers/Auth/SsoLoginController.php#L13-L49) | `Auth::loginUsingId()`, delete token | Validasi token SSO sekali pakai (*one-time use*) dan regenerasi sesi browser |
| **E-25** | Multi-Semester Global | [SetAcademicPeriod.php](file:///d:/laragon/www/siasek/aplikasi-absensi/app/Http/Middleware/SetAcademicPeriod.php#L17-L36) | `session('active_semester_id')` | Manajemen periode semester aktif untuk penelusuran data historis presensi |
| **E-26** | Backfill Riwayat Kelas | Migrasi [2026_09_04_000002](file:///d:/laragon/www/siasek/aplikasi-absensi/database/migrations/2026_09_04_000002_reconstruct_historical_class_students.php) | Rekonstruksi kelas lampau | Skrip rekonstruksi kelas siswa semester ganjil/genap berbasis jejak presensi |
| **E-27** | Fallback Symlink Storage | [routes/web.php](file:///d:/laragon/www/siasek/aplikasi-absensi/routes/web.php#L534-L566) | `Route::get('/storage/{path}')` | Jalur streaming berkas publik jika symlink server hosting tidak aktif |
| **E-28** | Temuan Celah Keamanan | [routes/web.php](file:///d:/laragon/www/siasek/aplikasi-absensi/routes/web.php#L445) | `request('key') !== 'presensi123'` | Celah akses utilitas server tanpa login menggunakan query parameter publik |
| **E-29** | Database MySQL Aktif | MySQL Schema `db_absen` | 67 tabel aktif, 4.367 log mapel | Basis data bersama terverifikasi empiris via perintah artisan tinker |
| **E-30** | Eksekusi Test Runner | CLI `php artisan test` | 19 Gagal, 6 Lulus, 29 asersi | Hasil uji otomatis nyata membuktikan ketiadaan kolom soft delete di SQLite |

---

## Korelasi Bukti Visual Antarmuka (Visual Evidence Inventory)

Selain 30 bukti struktural kode sumber (E-01 s.d. E-30), operasionalitas antarmuka sistem telah diverifikasi secara visual melalui **25 berkas tangkapan layar (*screenshot*) empiris** yang tersimpan di `docs/LPJ/assets/screenshots/` dan terdokumentasi lengkap dalam [Daftar_Screenshot.md](file:///d:/laragon/www/siasek/aplikasi-absensi/docs/LPJ/Daftar_Screenshot.md):

- **Target Screenshot**: 27 item (SS-01 s.d. SS-27)
- **Aktual Berhasil Dicapture**: 25 file PNG (SS-01 s.d. SS-05, SS-07, SS-09 s.d. SS-27)
- **Status Belum Diverifikasi**: 2 item (SS-06 dan SS-08) ditandai secara objektif karena memerlukan sesi KBM dan query tanggal aktif
- **Korelasi ID**: Setiap berkas gambar memiliki ID unik 1-ke-1 tanpa duplikasi atau bentrok nama berkas.



---

## Kategori Bukti Riwayat Pengembangan Repositori (RPG-01 s.d. RPG-05)

Seluruh jejak rekam historis pengembangan teknis repositori didokumentasikan pada berkas [Riwayat_Pengembangan.md](file:///d:/laragon/www/siasek/aplikasi-absensi/docs/LPJ/Riwayat_Pengembangan.md) dan terindeks sebagai berikut:

| ID Bukti | Kategori Bukti | Sumber Data / Perintah | Deskripsi Pembuktian Teknis |
|---|---|---|---|
| **RPG-01** | Verification Script & Path Log | `verify_path_classification.py` | Membuktikan 457 commit pengembangan aplikasi teridentifikasi yang mengubah kode sumber `app/`, `routes/`, `resources/`, `database/`, dsb. |
| **RPG-02** | Riwayat Cabang / Branch | `git branch -a`, `git for-each-ref` | Membuktikan 1 cabang lokal (`main`), 1 remote tracking branch (`origin/main`), alur komit linier |
| **RPG-03** | Berkas Log Histori Lengkap | `git-history-full.txt` | Membuktikan 458 total commit pada repository origin/main dan 459 total commit pada HEAD lokal |
| **RPG-04** | Riwayat Merge Commit | `git log --all --merges` | Membuktikan 0 komit penggabungan (*merge commit*), alur pengembangan linier pada cabang `main` |
| **RPG-05** | Pemisahan Commit Aplikasi vs LPJ | `git diff origin/main..HEAD` | Last Application Development Commit: `f519ebd` (04 Sept 2026); First LPJ Tooling Commit: `b131b87` (26 Sept 2026) |
