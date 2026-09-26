# INVENTARIS DATABASE & SKEMA (DATABASE INVENTORY)
**Aplikasi Presensi SIASEK (Sistem Kehadiran Real-time)**  
*SMP Negeri 1 Biau / Ekosistem Pendidikan Digital SIASEK*  
*Tanggal Audit: 26 September 2026*  
*Basis Data Terverifikasi: MySQL Database `db_absen`*

---

## 1. Arsitektur Basis Data Ekosistem

Aplikasi Presensi mengimplementasikan pola **Shared Database Architecture** dengan nama database terpusat `db_absen`. 
Berdasarkan investigasi runtime, database ini dihubungkan secara simultan dengan tiga sistem dalam ekosistem SIASEK:
1. **SIPADA (Sistem Pangkalan Data Sekolah)**: Sebagai *single source of truth* data induk kepegawaian, peserta didik, rombongan belajar, dan hak akses Spatie Permission.
2. **Aplikasi Presensi (Sistem Kehadiran Real-time)**: Sebagai mesin pencatat log kehadiran harian gerbang, kehadiran mapel, dispensasi gerbang, perizinan, dan jurnal mengajar.
3. **LMS Mokopani (Learning Management System)**: Sebagai platform pembelajaran daring, penugasan, dan asesmen kurikulum merdeka (menggunakan tabel berawalan `lms_*`).

### Metrik Kuantitatif Database
- **Jumlah Berkas Migrasi Lokal**: 65 berkas migrasi pada direktori `database/migrations/`.
- **Status Eksekusi Migrasi Lokal**: 65 berkas berstatus `Ran` (100% tereksekusi pada batch 1 s.d. 108).
- **Total Riwayat Migrasi di Tabel `migrations`**: 148 entri migrasi (mencakup migrasi gabungan ekosistem).
- **Jumlah Total Tabel Aktif**: 67 tabel di dalam basis data `db_absen`.

---

## 2. Inventaris Tabel Utama Aplikasi Presensi

Berikut adalah rincian empiris tabel-tabel yang digunakan secara aktif oleh fungsionalitas Aplikasi Presensi, dilengkapi dengan jumlah baris data nyata per tanggal audit:

| No | Nama Tabel | Jumlah Data (Rows) | Tujuan & Fungsi Entitas | Primary Key | Foreign Key & Relasi | Audit Fields / Constraint |
|---|---|---|---|---|---|---|
| 1 | `users` | **191** | Akun pengguna sistem (Admin, Guru, Ortu, Siswa, Kepala Sekolah) | `id` (bigint) | `parent_id` (opsional) | `email` (unique), `password`, `role`, `last_seen_at`, `deleted_at` (soft deletes) |
| 2 | `students` | **163** | Data induk siswa sekolah | `id` (bigint) | `user_id` -> `users.id`, `school_class_id` -> `school_classes.id` | `nis` (unique), `unique_id` (UUID), `photo`, `face_descriptor` (LONGTEXT JSON), `status` ('aktif', 'lulus', dll) |
| 3 | `teachers` | **14** | Data profil pendidik / guru | `id` (bigint) | `user_id` -> `users.id` | `nip`, `nuptk`, `photo`, `face_descriptor` (LONGTEXT JSON) |
| 4 | `parents` | **7** | Data profil orang tua / wali murid | `id` (bigint) | `user_id` -> `users.id` | `phone_number`, `photo`, `is_onboarding_completed` (boolean) |
| 5 | `school_classes` | **19** | Data rombongan belajar / kelas (7A s.d. 9F) | `id` (bigint) | `teacher_id` -> `teachers.id` (Wali Kelas), `level_id` -> `levels.id` | `name`, `academic_year_id` |
| 6 | `levels` | **6** | Tingkatan kelas (Kelas 7, 8, 9) | `id` (bigint) | - | `name` |
| 7 | `academic_years` | **2** | Tahun ajaran (contoh: 2024/2025, 2025/2026) | `id` (bigint) | - | `name`, `is_active` (boolean) |
| 8 | `semesters` | **3** | Semester akademik (Ganjil / Genap) | `id` (bigint) | `academic_year_id` -> `academic_years.id` | `name`, `is_active` (boolean) |
| 9 | `class_student` | **303** | Tabel pivot penetapan siswa ke dalam kelas | `id` (bigint) | `school_class_id`, `student_id`, `academic_year_id` | `is_active` |
| 10 | `student_class_histories` | **28** | Jejak riwayat mutasi / kenaikan kelas siswa antar semester | `id` (bigint) | `student_id`, `school_class_id`, `semester_id` | Audit jejak historis |
| 11 | `parent_student` | **8** | Hubungan orang tua dengan anak / siswa | `id` (bigint) | `parent_id` -> `parents.id`, `student_id` -> `students.id` | `relationship` |
| 12 | `parent_student_requests` | **0** | Antrean pengajuan klaim anak oleh akun orang tua baru | `id` (bigint) | `parent_id`, `student_id`, `verified_by` -> `users.id` | `status` ('pending', 'approved', 'rejected') |
| 13 | `attendances` | **215** | Transaksi presensi harian gerbang (Masuk & Pulang Siswa) | `id` (bigint) | `student_id` -> `students.id`, `semester_id` -> `semesters.id` | `attendance_time`, `checkout_time`, `status` ('tepat_waktu', 'terlambat', 'izin', 'sakit', 'alpa', 'izin_keluar') |
| 14 | `subject_attendances` | **4,367** | Transaksi presensi siswa per mata pelajaran / kokurikuler di kelas | `id` (bigint) | `student_id` -> `students.id`, `schedule_id` -> `schedules.id` | `status` ('hadir', 'sakit', 'izin', 'alpa', 'bolos'), `notes`, `created_at` |
| 15 | `teacher_attendances` | **1** | Transaksi presensi mandiri guru via GPS & Deteksi Wajah | `id` (bigint) | `teacher_id` -> `teachers.id` | `status`, `latitude`, `longitude`, `photo_path` (bukti selfie), `checkout_time`, `device_info` |
| 16 | `student_permits` | **10** | Pencatatan izin keluar & kembali gerbang sekolah selama KBM | `id` (bigint) | `student_id` -> `students.id`, `attendance_id` -> `attendances.id` | `time_out`, `time_in`, `reason` |
| 17 | `leave_requests` | **14** | Permohonan izin & sakit siswa (dari Orang Tua atau input manual TU) | `id` (bigint) | `student_id`, `parent_id`, `approved_by` -> `users.id`, `created_by` | `start_date`, `end_date`, `type` ('sakit', 'izin'), `reason`, `attachment`, `status` ('pending', 'approved', 'rejected') |
| 18 | `schedules` | **14** | Jadwal pelajaran mingguan kelas | `id` (bigint) | `teaching_assignment_id`, `school_class_id`, `cocurricular_id`, `teacher_id` | `day_of_week` (1-7), `start_time`, `end_time` |
| 19 | `subjects` | **6** | Mata pelajaran (Matematika, IPA, Bahasa Indonesia, dll.) | `id` (bigint) | - | `name`, `code` |
| 20 | `subject_teacher` | **6** | Pivot keterkaitan kompetensi guru mengajar mata pelajaran | `id` (bigint) | `subject_id`, `teacher_id` | Pivot standard |
| 21 | `teaching_assignments` | **13** | Surat tugas mengajar guru per mata pelajaran dan rombel kelas | `id` (bigint) | `teacher_id`, `school_class_id`, `subject_id`, `academic_year_id` | Penugasan akademik |
| 22 | `teaching_journals` | **0** | Buku catatan jurnal harian mengajar guru mata pelajaran | `id` (bigint) | `teacher_id`, `schedule_id`, `school_class_id`, `subject_id`, `verified_by` | `date`, `jp`, `materi_pokok`, `kegiatan_pembelajaran`, `hambatan`, `solusi`, `is_verified` (boolean) |
| 23 | `teacher_semester_reflections` | **0** | Refleksi akhir semester guru atas pelaksanaan KBM | `id` (bigint) | `teacher_id`, `academic_year_id`, `semester_id` | Catatan evaluasi pembelajaran |
| 24 | `student_anecdotes` | **0** | Catatan anekdot perilaku, akademik, dan kedisiplinan siswa | `id` (bigint) | `student_id`, `teacher_id`, `schedule_id` | `date`, `category` ('akademik', 'kehadiran', 'sikap'), `notes`, `follow_up` |
| 25 | `teacher_notes` | **9** | Catatan mikro / evaluasi ringkas guru terhadap siswa tertentu | `id` (bigint) | `student_id` -> `students.id`, `teacher_id` -> `teachers.id` | `notes`, timestamps |
| 26 | `extracurriculars` | **1** | Program kegiatan ekstrakurikuler sekolah | `id` (bigint) | `teacher_id` -> `teachers.id` (Pembina utama) | `name`, `description`, `day`, `start_time`, `end_time` |
| 27 | `extracurricular_teacher` | **2** | Pembina pendamping kegiatan ekstrakurikuler | `id` (bigint) | `extracurricular_id`, `teacher_id` | Pivot multi-pembina |
| 28 | `extracurricular_student` | **3** | Anggota siswa peserta ekstrakurikuler | `id` (bigint) | `extracurricular_id`, `student_id` | Pivot keanggotaan siswa |
| 29 | `extracurricular_attendances` | **3** | Log absensi kehadiran siswa pada sesi ekstrakurikuler | `id` (bigint) | `extracurricular_id`, `student_id`, `teacher_id` | `attendance_date`, `status` ('hadir', 'izin', 'sakit', 'alpa') |
| 30 | `cocurriculars` | **1** | Kegiatan kokurikuler terstruktur (P5 / Proyek Penguatan Profil Pelajar) | `id` (bigint) | `academic_year_id` | `title`, `description`, `day_of_week` |
| 31 | `cocurricular_teacher` | **4** | Fasilitator pendidik kegiatan kokurikuler | `id` (bigint) | `cocurricular_id`, `teacher_id` | Pivot fasilitator |
| 32 | `conversations` | **8** | Sesi obrolan pesan dua arah antara Guru dan Orang Tua | `id` (bigint) | `parent_id` -> `parents.id`, `teacher_id` -> `teachers.id` | Timestamps interaksi |
| 33 | `messages` | **31** | Riwayat isi pesan pada sesi percakapan Guru - Ortu | `id` (bigint) | `conversation_id`, `user_id` -> `users.id` | `body` (TEXT), `read_at` (nullable) |
| 34 | `admin_conversations` | **12** | Sesi percakapan antara Petugas Admin/TU dan Orang Tua | `id` (bigint) | `parent_id` -> `parents.id`, `admin_id` -> `users.id` | Jalur aspirasi langsung |
| 35 | `admin_messages` | **42** | Riwayat pesan pada sesi Admin - Ortu | `id` (bigint) | `admin_conversation_id`, `user_id` | `message` (TEXT), `read_at` |
| 36 | `calendars` | **2** | Kalender akademik (hari libur nasional, jeda semester, mandiri) | `id` (bigint) | - | `title`, `start_date`, `end_date`, `is_holiday` (bool), `is_self_study` (bool) |
| 37 | `settings` | **22** | Konfigurasi sistem (Geolokasi sekolah, radius GPS, jam masuk/pulang) | `id` (bigint) | - | `key` (unique string), `value` (TEXT) |
| 38 | `sso_tokens` | **9** | Token pertukaran sesi terenkripsi untuk Single Sign-On (SSO) | `id` (bigint) | `user_id` -> `users.id` | `token` (string 60 char, unique), `expires_at` (timestamp) |
| 39 | `notifications` | **8** | Notifikasi in-app untuk pengguna (Orang Tua & Guru) | `id` (bigint) | `user_id` -> `users.id` | `title`, `message`, `is_read` (boolean) |
| 40 | `app_notifications` | **456** | Log notifikasi push/broadcast sistem | `id` (bigint) | `user_id` | Pesan sistem terpusat |
| 41 | `personal_access_tokens` | **5** | Token REST API Laravel Sanctum untuk aplikasi mobile | `id` (bigint) | Polymorphic (`tokenable_type`, `tokenable_id`) | `name`, `token` (hash), `abilities`, `last_used_at` |

---

## 3. Skema Hak Akses Spatie Permission (Shared SIPADA)

Aplikasi membaca hak akses Spatie Permission yang tersimpan di dalam database:

| Nama Tabel | Jumlah Data | Peran / Hubungan |
|---|---|---|
| `roles` | **17** | Daftar role sistem: `admin`, `operator`, `satpam`, `teacher`, `parent`, `student`, `kepala_sekolah`, `kepala sekolah`, `headmaster`, `wakasek_kurikulum`, `tu`, `tata_usaha`, dll. |
| `permissions` | **23** | Hak akses granular sistem |
| `model_has_roles` | **186** | Relasi asosiasi pengguna (`App\Models\User`) dengan ID role |
| `role_has_permissions` | **34** | Hak akses yang diberikan kepada setiap role |

---

## 4. Tabel Terkait LMS Mokopani (Shared Database Ecosystem)

Ditemukan 20 tabel dengan prefiks `lms_*` yang dimiliki oleh platform LMS Mokopani dalam database `db_absen`:
`lms_ai_caches` (22), `lms_ai_prompts` (6), `lms_announcements` (1), `lms_capaian_pembelajaran` (1), `lms_class_sessions` (2), `lms_learning_objectives` (3), `lms_material_resources` (2), `lms_material_school_class` (2), `lms_materials` (2), `lms_modul_ajar_classes` (1), `lms_modul_ajars` (1), `lms_p5_dimensi` (6), `lms_p5_elements` (20), `lms_p5_project_scores` (7), `lms_p5_projects` (1), `lms_p5_sub_elements` (43), `lms_sessions` (3), `lms_comments` (0), `lms_submissions` (0), `lms_remedial_records` (0).
*Catatan*: Tabel-tabel LMS ini tidak diakses langsung oleh controller presensi, namun membuktikan integritas arsitektur satu database terpadu (*single educational datastore*).

---

## 5. Parameter Konfigurasi Nyata pada Tabel `settings`

Berdasarkan pembacaan tabel `settings` (22 baris data):
- `school_latitude` & `school_longitude`: Titik koordinat GPS acuan gerbang sekolah untuk validasi formula Haversine.
- `attendance_radius`: Radius geofencing toleransi absensi (meter).
- `jam_masuk`: Batas waktu awal presensi masuk siswa.
- `jam_pulang`: Batas waktu siswa diizinkan melakukan presensi kepulangan.
- `jam_masuk_guru` & `jam_pulang_guru`: Batas waktu jam kerja dinas bagi guru.
- `send_absent_notification`: Status toggle otomatisasi pengiriman pesan ke orang tua jika siswa belum hadir saat pengecekan pukul 10:00 (`on` / `off`).
- `app_logo`: Path penyimpanan berkas logo institusi di storage publik.
- `school_headmaster_name` & `school_headmaster_nip`: Identitas Kepala Sekolah untuk pengesahan otomatis pada dokumen cetak PDF dan Excel.

---

## 6. Temuan Audit Integritas Data & Skema

> [!IMPORTANT]
> **Temuan Kritis Skema Database**:
> 1. **Dukungan Soft Deletes pada Model User**:
>    - Model `App\Models\User` memuat *trait* `Illuminate\Database\Eloquent\SoftDeletes`.
>    - Pada database MySQL aktif `db_absen`, kolom `deleted_at` terverifikasi ada.
>    - Namun berkas migrasi awal di repositori `0001_01_01_000000_create_users_table.php` tidak memiliki `$table->softDeletes()`. Kolom ini ditambahkan oleh migrasi SIPADA di lingkungan bersama, sehingga jika unit test dijalankan dengan SQLite `:memory:` lokal tanpa migrasi eksternal, akan terjadi *error* ketiadaan kolom `users.deleted_at`.
> 2. **Penyimpanan Descriptor Wajah (Face Descriptor)**:
>    - Kolom `face_descriptor` pada tabel `students` dan `teachers` bertipe `LONGTEXT`. Kolom ini menyimpan serialisasi JSON array float 128 elemen yang dihasilkan oleh model neural network Face-API.js pada saat pendaftaran wajah.
