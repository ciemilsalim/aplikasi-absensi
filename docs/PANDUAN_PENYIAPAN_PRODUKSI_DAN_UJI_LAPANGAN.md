# 🚀 Panduan Penyiapan Lingkungan Produksi & Uji Coba Lapangan SIASEK

Dokumen ini berisi panduan teknis langkah demi langkah untuk men-deploy, mengonfigurasi layanan latar belakang (*background services*), dan melaksanakan skenario uji coba lapangan (*Field Testing*) pada Sistem Presensi SIASEK.

---

## 📋 DAFTAR ISI
1. [Spesifikasi & Persyaratan Server Produksi](#1-spesifikasi--persyaratan-server-produksi)
2. [Konfigurasi Lingkungan (`.env`)](#2-konfigurasi-lingkungan-env)
3. [Langkah Deployment & Optimasi Performa](#3-langkah-deployment--optimasi-performa)
4. [Konfigurasi Background Queue Worker (Supervisor / Systemd)](#4-konfigurasi-background-queue-worker-supervisor--systemd)
5. [Konfigurasi Cron Task Scheduler](#5-konfigurasi-cron-task-scheduler)
6. [Protokol Uji Coba Lapangan (Field Testing Protocol)](#6-protokol-uji-coba-lapangan-field-testing-protocol)
   - [Uji Coba 1: Kiosk Scanner Offline Buffer & Auto-Sync](#uji-coba-1-kiosk-scanner-offline-buffer--auto-sync)
   - [Uji Coba 2: Pengiriman Notifikasi WhatsApp Real-time](#uji-coba-2-pengiriman-notifikasi-whatsapp-real-time)
   - [Uji Coba 3: Presensi Mandiri Guru & Validasi Anti-Fake GPS](#uji-coba-3-presensi-mandiri-guru--validasi-anti-fake-gps)
   - [Uji Coba 4: Otomasi Cutoff Alpha Harian (08:30 WITA)](#uji-coba-4-otomasi-cutoff-alpha-harian-0830-wita)
   - [Uji Coba 5: Dashboard Deteksi Dini Siswa Berisiko](#uji-coba-5-dashboard-deteksi-dini-siswa-berisiko)
7. [Rencana Pemulihan Masalah (Troubleshooting & Disaster Recovery)](#7-rencana-pemulihan-masalah-troubleshooting--disaster-recovery)

---

## 1. Spesifikasi & Persyaratan Server Produksi

- **OS**: Ubuntu 22.04 LTS / 24.04 LTS atau VPS/CPanel kompatibel PHP 8.2+.
- **PHP**: PHP 8.2+ dengan ekstensi wajib:
  - `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `zip`, `curl`.
- **Database**: MySQL 8.0+ atau MariaDB 10.6+.
- **Web Server**: Nginx atau Apache (dengan modul `mod_rewrite`).
- **Memory**: Minimum 2 GB RAM (Rekomendasi 4 GB untuk penanganan beban puncak pagi hari).
- **Storage**: SSD dengan symlink direktori `storage/app/public` ke `public/storage`.

---

## 2. Konfigurasi Lingkungan (`.env`)

Pastikan parameter berikut disesuaikan pada file `.env` di server produksi:

```env
APP_NAME="SIASEK Presensi"
APP_ENV=production
APP_KEY=base64:... # Generate via php artisan key:generate
APP_DEBUG=false
APP_URL=https://presensi.sekolah.sch.id

# DATABASE
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siasek_production
DB_USERNAME=siasek_user
DB_PASSWORD=PasswordKuatProduksi123!

# QUEUE & CACHE
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_LIFETIME=120

# STORAGE
FILESYSTEM_DISK=public

# BACKUP ENCRYPTION PASSWORD (OPSIONAL)
BACKUP_ARCHIVE_PASSWORD=PasswordBackupKuat456!
```

---

## 3. Langkah Deployment & Optimasi Performa

Jalankan perintah berikut di direktori root aplikasi pada server produksi:

```bash
# 1. Update kode terbaru & dependensi produksi
git pull origin main
composer install --no-dev --optimize-autoloader

# 2. Pastikan symbolic link storage aktif
php artisan storage:link

# 3. Jalankan migrasi database
php artisan migrate --force

# 4. Optimasi & Kompilasi Cache Produksi
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> ⚠️ **Catatan Penting:** Kapan pun ada perubahan file `.env` atau rute di masa depan, jalankan ulang `php artisan config:cache` dan `php artisan route:cache`.

---

## 4. Konfigurasi Background Queue Worker (Supervisor / Systemd)

Queue Worker bertugas memproses antrean pesan WhatsApp (`SendWhatsAppNotificationJob`) secara asinkron agar scanner kiosk tidak terhambat (*zero blocking*).

### A. Menggunakan Supervisor (Rekomendasi VPS Linux)

1. Pasang supervisor:
   ```bash
   sudo apt-get install supervisor
   ```

2. Buat file konfigurasi `/etc/supervisor/conf.d/siasek-worker.conf`:
   ```ini
   [program:siasek-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /var/www/siasek/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   stopasgroup=true
   killasgroup=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/var/www/siasek/storage/logs/worker.log
   stopwaitsecs=3600
   ```

3. Terapkan konfigurasi:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start siasek-worker:*
   ```

### B. Mode CPanel / Shared Hosting (Alternatif Cron Fallback)

Jika hosting tidak memiliki akses root/supervisor, jalankan antrean secara periodik via Cron (tiap 1 atau 5 menit):
```bash
* * * * * cd /home/user/public_html && php artisan queue:work database --stop-when-empty >> /dev/null 2>&1
```

---

## 5. Konfigurasi Cron Task Scheduler

Sistem presensi memiliki tugas otomatis terjadwal:
1. **01:00 WITA**: Pembersihan arsip backup lama (`backup:clean`).
2. **01:30 WITA**: Pembuatan backup database otomatis (`backup:run`).
3. **08:30 WITA (Senin - Jumat)**: Pengecekan siswa tanpa kehadiran & pemberian status Alpha otomatis (`attendance:check-absent`).

Tambahkan baris berikut ke Crontab server (`crontab -e`):

```bash
* * * * * cd /var/www/siasek && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. Protokol Uji Coba Lapangan (Field Testing Protocol)

Berikut adalah 5 skenario pengujian lapangan yang dapat dipraktikkan langsung oleh operator dan teknisi sekolah:

### Uji Coba 1: Kiosk Scanner Offline Buffer & Auto-Sync
- **Tujuan**: Memastikan siswa tetap dapat scan saat internet sekolah padam/gangguan.
- **Langkah Pengujian**:
  1. Buka halaman scanner kiosk (`/scanner`) pada browser tablet/laptop kiosk.
  2. Matikan koneksi WiFi/kabel LAN pada perangkat kiosk.
  3. Lakukan scan kartu QR siswa.
  4. Amati layar: badge `Offline (1)` akan muncul, dan suara "Presensi berhasil direkam secara lokal" terdengar.
  5. Nyalakan kembali koneksi internet.
  6. Amati layar: sistem otomatis memproses antrean (*auto-sync*), badge berubah menjadi `Online`, dan data masuk ke server dengan waktu scan asli (`scanned_at`).

### Uji Coba 2: Pengiriman Notifikasi WhatsApp Real-time
- **Tujuan**: Memverifikasi orang tua menerima notifikasi instan begitu anak scan masuk/pulang.
- **Langkah Pengujian**:
  1. Masuk ke Dashboard Admin -> **Pengaturan** -> **Gateway WhatsApp**.
  2. Pilih provider (misal: `Fonnte` atau `Wablas`), masukkan token API resmi sekolah.
  3. Aktifkan saklar: **Notifikasi Jam Masuk**, **Notifikasi Jam Pulang**, dan **Notifikasi Alpha**.
  4. Klik tombol **Uji Koneksi** dengan nomor HP penguji. Pastikan pesan masuk ke WhatsApp.
  5. Minta seorang siswa melakukan scan masuk di kiosk.
  6. Periksa WhatsApp nomor orang tua siswa tersebut, pastikan pesan konfirmasi masuk beserta jam hadir.

### Uji Coba 3: Presensi Mandiri Guru & Validasi Anti-Fake GPS
- **Tujuan**: Memastikan guru hanya dapat presensi dari dalam area sekolah dan tidak dapat memanipulasi lokasi.
- **Langkah Pengujian**:
  1. Buka aplikasi di HP guru di luar radius sekolah (> 100 meter). Klik presensi -> sistem harus menolak dengan pesan *"Anda berada di luar radius sekolah"*.
  2. Gunakan aplikasi Mock Location / Fake GPS -> sistem harus mendeteksi dan menolak presensi dengan pesan error manipulasi lokasi.
  3. Lakukan presensi saat berada di dalam area sekolah dengan akurasi GPS baik -> presensi berhasil tersimpan.

### Uji Coba 4: Otomasi Cutoff Alpha Harian (08:30 WITA)
- **Tujuan**: Memastikan siswa tanpa keterangan otomatis ditandai Alpha pada pukul 08:30.
- **Langkah Pengujian**:
  1. Di luar jam 08:30, uji coba manual dapat dipicu via terminal:
     ```bash
     php artisan attendance:check-absent
     ```
  2. Sistem akan memverifikasi:
     - Akhir pekan (Sabtu/Minggu): otomatis dilewati.
     - Hari libur / belajar mandiri di Kalender Pendidikan: otomatis dilewati.
     - Siswa yang mengajukan izin/sakit valid: disinkronkan sesuai surat izin.
     - Siswa tanpa keterangan: otomatis dibuatkan record `status = alpa`.
  3. Periksa notifikasi ke orang tua: jika diaktifkan, orang tua siswa bersangkutan akan menerima notifikasi otomatis.

### Uji Coba 5: Dashboard Deteksi Dini Siswa Berisiko
- **Tujuan**: Memberikan peringatan cepat bagi Wali Kelas & Guru BK terhadap siswa yang sering terlambat atau membolos.
- **Langkah Pengujian**:
  1. Login sebagai Admin atau Wali Kelas.
  2. Periksa widget **Deteksi Dini / Siswa Butuh Perhatian**:
     - Siswa dengan Alpa ≥ 3 hari: level risiko `HIGH` (Merah).
     - Siswa dengan Alpa 1-2 hari atau Terlambat ≥ 3 kali: level risiko `MEDIUM` (Kuning).
  3. Pastikan tombol tindak lanjut WhatsApp / catatan guru dapat diakses langsung dari widget.

---

## 7. Rencana Pemulihan Masalah (Troubleshooting & Disaster Recovery)

| Masalah | Kemungkinan Penyebab | Tindakan Solusi |
| :--- | :--- | :--- |
| Pesan WhatsApp tidak terkirim | Worker mati atau token kedaluwarsa | Cek `storage/logs/worker.log`. Jalankan `supervisorctl restart siasek-worker:*`. Periksa sisa kuota di dashboard vendor (Fonnte/Wablas). |
| Foto siswa / guru tidak muncul | Symbolic link storage terputus | Jalankan `php artisan storage:link` di terminal server. |
| Perubahan kode / rute tidak berefek | Cache aplikasi masih menahan versi lama | Jalankan `php artisan optimize:clear` lalu `php artisan optimize`. |
| Backup gagal dieksekusi | Permission folder `storage/app/backup` | Pastikan direktori backup memiliki izin tulis untuk user `www-data` (`chmod -R 775 storage/app`). |
