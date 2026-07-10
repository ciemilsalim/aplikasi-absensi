# ⏱️ Aplikasi Presensi SIASEK — Sistem Kehadiran Real-time

Aplikasi Presensi ini merupakan bagian dari ekosistem **SIASEK (Sistem Informasi Akademik Ekosistem)**. Didedikasikan khusus untuk mesin pencatat kehadiran siswa dan guru di gerbang sekolah, serta dasbor pemantauan real-time bagi petugas piket.

Aplikasi ini menggunakan pendekatan Blade tradisional (Server-Side Rendering murni) untuk menjamin kecepatan *loading* maksimal dan kompatibilitas penuh dengan perangkat *barcode scanner* / mesin kasir ringan yang biasa digunakan di lobi sekolah.

---

## 🛠️ Tech Stack

* **Backend Framework**: [Laravel 11](https://laravel.com) (PHP 8.2+)
* **Frontend View Engine**: [Blade Templating](https://laravel.com/docs/11.x/blade)
* **Styling**: [Tailwind CSS](https://tailwindcss.com) (Melalui CDN / Vite)
* **Database**: MySQL (Menggunakan arsitektur *Shared Database* `db_absen` yang terintegrasi dengan ekosistem SIASEK lainnya)

---

## ✨ Fitur Utama

* **Mesin Pemindai Cepat**: Antarmuka responsif untuk diintegrasikan dengan pemindai kartu RFID / *Barcode* siswa.
* **Dashboard Piket Real-time**: Layar pemantauan yang menampilkan daftar siswa yang masuk, terlambat, atau izin pada detik itu juga.
* **Filter Global Berbasis Semester**: Mengambil data jadwal, siswa, dan rombongan belajar secara akurat sesuai Buku Induk dari database SIPADA berdasarkan semester aktif.
* **Laporan PDF**: Fitur cetak laporan ketidakhadiran per hari, minggu, dan bulan untuk dilaporkan ke Kepala Sekolah.

---

## 🚀 Panduan Instalasi Cepat

Aplikasi ini **tidak berdiri sendiri**. Aplikasi ini bergantung pada *database* yang dikelola oleh aplikasi **SIPADA**. 
**PENTING:** Pastikan Anda telah menginstal SIPADA terlebih dahulu dan telah menjalankan migrasi *database*-nya.

Ikuti langkah-langkah di bawah ini untuk menjalankan Aplikasi Presensi di lingkungan lokal Anda:

### 1. Kloning & Masuk ke Folder Proyek
```bash
git clone <url-repo-presensi> aplikasi-absensi
cd aplikasi-absensi
```

### 2. Instal Dependensi Backend (Composer)
```bash
composer install
```

### 3. Salin & Konfigurasi File Lingkungan (`.env`)
Salin file `.env.example` menjadi file `.env`:
```bash
copy .env.example .env
```

Buka file `.env` di editor Anda dan pastikan koneksi database **sama persis** dengan yang digunakan pada SIPADA (`db_absen`):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_absen
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generate Application Key
```bash
php artisan key:generate
```

*(Catatan: Anda **tidak perlu** dan dilarang menjalankan `php artisan migrate` di sini, karena tabel database diurus dan dikelola secara sentral oleh SIPADA).*

---

## 🖥️ Menjalankan Aplikasi secara Lokal

Jalankan perintah berikut di terminal:

```bash
php artisan serve --port=8002
```
*Catatan: Kami menyarankan menggunakan port 8002 untuk Aplikasi Presensi agar dapat berjalan berdampingan bersama SIPADA (8001) atau LMS Mokopani (8000).*

Sekarang, buka browser Anda dan akses `http://127.0.0.1:8002`.

---

## 📖 Cara Kerja Sistem Waktu Terpusat

Aplikasi Presensi memiliki tuas (*Dropdown*) di *header* untuk berpindah "Semester". Jika petugas Piket mengubah tuas ini ke semester lampau, maka layar laporan presensi akan secara cerdas menelusuri sejarah letak kelas siswa di semester tersebut melalui catatan "Buku Induk" yang tersentralisasi di SIPADA.

---
*Dibuat dengan dedikasi penuh untuk kemajuan ekosistem pendidikan digital Indonesia.*
