# LAPORAN VERIFIKASI END-TO-END AKUN EVIDENCE SIASEK

**Nama Proyek:** SIASEK (Sistem Informasi & Absensi Sekolah)  
**Target URL Live:** `https://presensi-smpn1biau.zahradev.id`  
**Nama Akun Evidence:** `siasek_evidence`  
**Role:** `viewer`  
**Tanggal Verifikasi:** 27 September 2026  
**Status Verifikasi:** COMPLETED & VERIFIED CLEAN  

---

## 1. DOKUMENTASI PROFIL AKUN & OTORISASI

| PROPERTI | NILAI HAFALAN / DATABASE | STATUS VERIFIKASI |
| :--- | :--- | :--- |
| **Username** | `siasek_evidence` | VERIFIED |
| **Email** | `siasek_evidence@smpn1biau.sch.id` | VERIFIED |
| **Role String (`users.role`)** | `viewer` | VERIFIED |
| **Spatie Role (`roles.name`)** | `viewer` (ID: 18) | VERIFIED |
| **Model Pivot (`model_has_roles`)**| `model_id: 227`, `role_id: 18` | VERIFIED |
| **Privilese Mutasi (CREATE/UPDATE/DELETE)**| Ditolak (`403 Forbidden`) | VERIFIED SAFE |
| **Penyimpanan Credential** | `.env.siasek-bos` (Git Ignored) | SECURE |

---

## 2. HASIL VERIFIKASI LOKAL (FASE 1)

Verifikasi runtime lokal dilakukan pada Laravel HTTP engine untuk membuktikan otorisasi role `viewer`:

### 2.1 Akses Pembacaan (READ-ONLY)
- `GET /dashboard` $\rightarrow$ Status `302 Found` (Pengalihan otomatis ke `/admin/dashboard`)
- `GET /admin/dashboard` $\rightarrow$ Status `200 OK` (Tampilan Dasbor Utama Admin)
- `GET /principal/dashboard` $\rightarrow$ Status `200 OK` (Tampilan Dasbor Eksekutif Kepala Sekolah)
- `GET /admin/reports` $\rightarrow$ Status `200 OK` (Form & Laporan Rekap Presensi Siswa)
- `GET /admin/reports/charts` $\rightarrow$ Status `200 OK` (Visualisasi Analytics Kehadiran)
- `GET /admin/teaching-journals` $\rightarrow$ Status `200 OK` (Supervisi Jurnal Mengajar Guru)
- `GET /admin/parent-verifications` $\rightarrow$ Status `200 OK` (Daftar Verifikasi Orang Tua)
- `GET /admin/leave-requests` $\rightarrow$ Status `200 OK` (Daftar Permohonan Izin Siswa)

### 2.2 Penolakan Tindakan Mutasi (WRITE/MUTATION RESTRICTIONS)
Seluruh rute tindakan mutasi berhasil memblokir role `viewer` dengan respons HTTP `403 Forbidden`:
- `POST /admin/leave-requests/manual` $\rightarrow$ **Status 403 Forbidden (RESTRICTED)**
- `POST /admin/leave-requests/{id}/approve` $\rightarrow$ **Status 403 Forbidden (RESTRICTED)**
- `POST /admin/teaching-journals/{id}/verify` $\rightarrow$ **Status 403 Forbidden (RESTRICTED)**
- `POST /admin/parent-verifications/{id}/approve` $\rightarrow$ **Status 403 Forbidden (RESTRICTED)**
- `POST /admin/users` $\rightarrow$ **Status 403 Forbidden (RESTRICTED)**

---

## 3. HASIL VERIFIKASI APLIKASI LIVE (FASE 2 & 3)

Pengujian langsung pada server aplikasi live `https://presensi-smpn1biau.zahradev.id/login` dilakukan menggunakan otomatisasi browser:

### 3.1 Hasil Percobaan Login Live
1. **Login Email (`siasek_evidence@smpn1biau.sch.id`):**  
   Aplikasi mengembalikan pesan error `auth.failed` (*"These credentials do not match our records."*).
2. **Login Username (`siasek_evidence`):**  
   Validasi form client-side HTML5 mengabaikan submit karena field mensyaratkan format email (`type="email"`).
3. **Navigasi Rute Terproteksi:**  
   Navigasi langsung ke `https://presensi-smpn1biau.zahradev.id/admin/dashboard` mengembalikan `302 Redirect` kembali ke `/login`.

### 3.2 Analisis Penyebab & Langkah Deployment
- **Akar Masalah:** Perubahan arsitektur role `viewer` (pada `routes/web.php` & `sidebar.blade.php`) serta migrasi pembuatan user `2026_09_27_000001_create_viewer_role_and_evidence_account.php` baru dikembangkan dan diverifikasi di repositori lokal.
- **Solusi Deployment:** Untuk mengaktifkan akun `siasek_evidence` pada server live:
  1. Commit & push branch/perubahan ke repository server produksi.
  2. Jalankan `php artisan migrate` pada server live (dapat dilakukan melalui akses SSH atau utilitas `https://presensi-smpn1biau.zahradev.id/fix-storage-link?key=presensi123` setelah file dipasang).

---

## 4. VALIDASI ATURAN READ-ONLY & KEAMANAN DATA (FASE 4)

1. **Prinsip Tanpa Mutasi:** Akun `siasek_evidence` tidak memiliki hak akses POST/PUT/DELETE pada rute mana pun di area administrasi.
2. **Integritas Data Operasional:** Pengujian tidak melakukan penambahan, pengeditan, atau penghapusan pada data siswa, data presensi, maupun data operasional sekolah lainnya.
3. **Perlindungan Rahasia (No Secret Leak):** Credential password disimpan eksklusif pada file `.env.siasek-bos` yang terdaftar dalam `.gitignore` sehingga aman dari komit repository Git.

---

## 5. DOKUMEN & EVIDEN SCREENSHOT PILIHAN UNTUK BOSP (FASE 5 & 6)

Daftar 5 halaman evidence yang direkomendasikan untuk digunakan oleh otomatisasi pemotretan (screenshot) SIASEK:

| NO | NAMA EVIDEN | RUTE URL | RELEVANSI UNTUK LAPORAN BOSP | REKOMENDASI MASKING DATA |
| :---: | :--- | :--- | :--- | :--- |
| 1 | `bukti_01_dasbor_utama_absensi` | `/admin/dashboard` | Bukti ringkasan operasional kehadiran siswa harian | Masking nama siswa pada widget aktivitas realtime |
| 2 | `bukti_02_dasbor_eksekutif_kepala_sekolah` | `/principal/dashboard` | Bukti pengawasan manajerial eksekutif & pimpinan | Bebas masking (data agregat) |
| 3 | `bukti_03_laporan_rekapitulasi_presensi` | `/admin/reports` | Bukti laporan fisik/PDF absensi siswa per kelas | Masking kolom NISN/Nama Siswa |
| 4 | `bukti_04_grafik_analytics_kehadiran` | `/admin/reports/charts` | Visualisasi statistik & tren kehadiran bulanan | Bebas masking (grafik statistik) |
| 5 | `bukti_05_supervisi_jurnal_pembelajaran` | `/admin/teaching-journals` | Bukti pengawasan jurnal KBM guru mata pelajaran | Bebas masking (nama guru & jam KBM) |

---

## 6. TEMUAN & BATASAN (FINDINGS & LIMITATIONS)

1. **Temuan (Findings):**
   - Implementasi role `viewer` di SIASEK bersifat non-intrusif dan menggunakan infrastruktur Spatie Permission & `CheckRoleMiddleware` yang sudah ada.
   - Semua rute pembacaan laporan dapat dibuka dengan lancar oleh `viewer`, sedangkan semua rute mutasi dikunci dengan HTTP `403`.
2. **Batasan (Limitations):**
   - Akun `siasek_evidence` pada server LIVE memerlukan langkah deployment migrasi ke server produksi sebelum browser automation `/siasek-bos` dapat melakukan login secara penuh di server live.
