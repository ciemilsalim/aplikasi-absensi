---
name: lpj-generator
description: Membuat Laporan Pertanggungjawaban (LPJ) dan dokumentasi teknis dari aplikasi yang sedang/selesai dikembangkan di Antigravity. Gunakan ketika pengguna meminta LPJ, laporan pengembangan aplikasi, dokumentasi proyek, manual teknis, bukti pengujian, atau audit fitur aplikasi. Skill ini wajib memverifikasi klaim laporan terhadap source code, konfigurasi, database, hasil testing, dan browser/UI sebelum menulis laporan.
---

# LPJ Generator

## Tujuan

Menghasilkan LPJ pengembangan aplikasi yang faktual, dapat diaudit, dan mudah dipakai sebagai dokumen resmi. Jangan mengarang fitur, hasil pengujian, teknologi, angka, atau keberhasilan yang tidak memiliki bukti.

## Prinsip utama

1. **Evidence first**: setiap klaim penting harus memiliki sumber bukti dari project.
2. **No hallucination**: bila informasi tidak ditemukan, tulis "belum ditemukan/tidak terverifikasi", bukan menebak.
3. **Code is source of truth** untuk fitur teknis.
4. **Runtime is source of truth** untuk perilaku aplikasi yang dapat diuji.
5. **Distinguish status**:
   - Terimplementasi
   - Terimplementasi sebagian
   - Direncanakan
   - Tidak ditemukan
   - Tidak dapat diverifikasi
6. **Pisahkan fakta, interpretasi, dan rekomendasi.**
7. Gunakan bahasa Indonesia formal, jelas, dan cocok untuk laporan sekolah/instansi.
8. Hindari memasukkan rahasia: API key, password, token, private key, cookie, credential, atau data pribadi sensitif.

## Workflow wajib

### Tahap 1 — Identifikasi proyek

Periksa minimal:
- `README`, dokumentasi, dan file konfigurasi
- `package.json`, `composer.json`, `requirements.txt`, atau dependency manager lain
- framework dan versinya
- struktur folder
- entry point aplikasi
- environment example (`.env.example`, bukan `.env` berisi rahasia)
- migration/schema/model database
- routes/API endpoints
- authentication/authorization
- modul/fitur utama
- test suite jika tersedia
- deployment configuration jika tersedia
- git history/status jika berguna untuk kronologi pengembangan

Buat ringkasan:
- Nama aplikasi
- Tujuan
- Sasaran pengguna
- Teknologi
- Versi/framework
- Modul
- Status saat audit
- Tanggal audit

### Tahap 2 — Pemetaan fitur

Buat tabel inventaris fitur dengan kolom:

| No | Fitur | Bukti source code | Bukti runtime/UI | Status | Catatan |
|---|---|---|---|---|---|

Status hanya boleh menggunakan:
- ✅ Terimplementasi
- ⚠️ Terimplementasi sebagian
- 🟡 Belum terverifikasi
- ❌ Tidak ditemukan

Jangan menaikkan status menjadi ✅ hanya karena nama route/menu/folder terlihat ada. Cari implementasi sebenarnya.

### Tahap 3 — Analisis arsitektur

Dokumentasikan:
- arsitektur aplikasi
- frontend
- backend
- database
- API/integrasi eksternal
- autentikasi
- otorisasi/role
- storage/file handling
- notifikasi bila ada
- deployment/runtime

Bila dapat dibuat dengan aman, buat diagram arsitektur menggunakan Mermaid.

### Tahap 4 — Analisis database

Periksa migration/schema/model dan susun:
- tabel
- tujuan tabel
- primary key
- foreign key
- relasi
- unique constraint
- field penting
- timestamp/audit field
- status data

Jangan mengklaim struktur database dari UI saja.

### Tahap 5 — Pengujian

Gunakan test suite yang ada. Jika tidak tersedia, lakukan smoke test yang aman.

Untuk aplikasi web:
- jalankan aplikasi
- buka halaman utama/login
- uji navigasi penting
- uji CRUD utama jika aman
- uji role/authorization
- uji validasi input
- uji error state yang relevan
- uji responsive dasar jika dapat diverifikasi
- gunakan Browser Agent bila tersedia
- simpan screenshot/recording sebagai bukti jika Antigravity menghasilkan artifact tersebut

Jangan menghapus atau merusak data produksi. Utamakan environment lokal/testing.

Buat matriks:

| No | Skenario | Langkah | Hasil yang diharapkan | Hasil aktual | Status | Bukti |
|---|---|---|---|---|---|---|

Status:
- Lulus
- Lulus sebagian
- Gagal
- Tidak dapat diuji

### Tahap 6 — Keamanan dan kualitas

Audit secara praktis:
- authentication
- authorization
- validation
- CSRF/CORS bila relevan
- secret exposure
- file upload
- SQL injection risk
- XSS risk
- access control
- password handling
- logging/error exposure
- dependency risk
- backup/recovery bila ada

Jangan menyatakan "aman" secara absolut. Gunakan formulasi seperti:
- "Tidak ditemukan indikasi ..."
- "Pada pemeriksaan terbatas ini ..."
- "Belum dapat diverifikasi ..."

### Tahap 7 — Bukti implementasi

Kumpulkan bukti yang paling relevan:
- screenshot halaman penting
- browser recording bila ada
- struktur database
- test output
- route/module evidence
- diagram
- konfigurasi non-rahasia
- commit/version information bila tersedia

Setiap bukti harus diberi label:
- Bukti E-01
- Bukti E-02
- dst.

### Tahap 8 — Penyusunan LPJ

Gunakan struktur berikut:

1. Halaman Judul
2. Identitas Aplikasi
3. Latar Belakang
4. Dasar/Tujuan Pengembangan
5. Permasalahan
6. Sasaran Pengguna
7. Ruang Lingkup
8. Metodologi Pengembangan
9. Teknologi yang Digunakan
10. Arsitektur Sistem
11. Modul dan Fitur
12. Desain/Struktur Database
13. Implementasi
14. Pengujian dan Hasil
15. Keamanan dan Kualitas
16. Kendala yang Ditemukan
17. Solusi/Perbaikan yang Dilakukan
18. Status Akhir Aplikasi
19. Rencana Pengembangan Lanjutan
20. Kesimpulan
21. Lampiran Bukti

Tambahkan tabel ringkas:
- Ringkasan fitur
- Ringkasan pengujian
- Risiko/kendala
- Status pekerjaan

### Tahap 9 — Output

Default output:
- `docs/LPJ/LPJ_Pengembangan_Aplikasi.md`
- `docs/LPJ/Matriks_Fitur.md`
- `docs/LPJ/Matriks_Pengujian.md`
- `docs/LPJ/Daftar_Bukti.md`
- `docs/LPJ/Arsitektur_Sistem.mmd` bila diagram Mermaid relevan
- `docs/LPJ/assets/` untuk bukti visual yang benar-benar tersedia

Jika pengguna meminta format dokumen lain, buat versi tersebut bila tool/kemampuan lingkungan memungkinkan. Jangan menghapus file project yang sudah ada.

## Aturan khusus untuk aplikasi Laravel

Jika project Laravel terdeteksi, periksa setidaknya:
- `artisan`
- `composer.json`
- `routes/web.php`
- `routes/api.php` bila ada
- `app/Http/Controllers`
- `app/Models`
- `database/migrations`
- `database/seeders` dan factories bila ada
- `resources/views`
- `resources/js` dan `resources/css`
- middleware
- policies/gates
- `config`
- `storage` dan `public`
- `tests`
- `vite.config.*`
- queue/job/event/listener bila digunakan

Jangan membaca atau memasukkan nilai rahasia dari `.env`.

## Aturan khusus untuk aplikasi mobile

Periksa framework dan entry point, package/dependency, screen/navigation, state management, API integration, local storage, permission, build configuration, dan test bila tersedia.

## Aturan khusus untuk aplikasi desktop/non-web

Identifikasi executable entry point, UI layer, business logic, persistence, dependencies, packaging/build, dan mekanisme update/configuration bila ada.

## Format penulisan

Gunakan:
- judul yang konsisten
- nomor bab
- tabel untuk informasi terstruktur
- diagram bila berguna
- bahasa Indonesia formal
- istilah teknis Inggris dalam kurung bila membantu

Hindari:
- klaim promosi
- superlatif tanpa bukti
- angka performa yang tidak diukur
- klaim "100% berhasil"
- klaim keamanan absolut
- menyatakan semua fitur selesai jika bukti tidak mendukung

## Final audit sebelum menyelesaikan tugas

Sebelum menyatakan LPJ selesai, lakukan pemeriksaan:

[ ] Nama aplikasi konsisten
[ ] Teknologi dan versi diverifikasi
[ ] Fitur dicocokkan dengan source code
[ ] Pengujian memiliki hasil aktual
[ ] Klaim penting memiliki bukti
[ ] Tidak ada secret/credential dalam laporan
[ ] Tidak ada fitur fiktif
[ ] Status akhir menjelaskan keterbatasan
[ ] Lampiran/bukti diberi ID
[ ] File output tersimpan di `docs/LPJ/`
