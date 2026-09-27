# PRODUCTION DEPLOYMENT RUNBOOK AKUN EVIDENCE SIASEK

**Aplikasi:** SIASEK (Sistem Informasi & Absensi Sekolah)  
**Target URL Live:** `https://presensi-smpn1biau.zahradev.id`  
**Nama Akun Evidence:** `siasek_evidence`  
**Role:** `viewer`  
**Tanggal Runbook:** 27 September 2026  
**Status Runbook:** APPROVED & DEPLOYMENT-READY (MANUAL EXECUTION REQUIRED)  

---

## 1. PRE-DEPLOYMENT CHECKLIST

Sebelum memulai deployment ke server produksi live, pastikan hal-hal berikut telah terpenuhi:

- [ ] Repository lokal berada pada branch `main` dengan status `clean` (semua file ter-commit).
- [ ] File `.env.siasek-bos` terdaftar di `.gitignore` dan **TIDAK** ter-commit ke Git.
- [ ] Database backup server produksi live sudah dibuat (MySQL dump).
- [ ] Credential password produksi baru telah disiapkan (minimal 24–32 karakter acak).
- [ ] Akses SSH/CLI ke server produksi live (`/home/u478110651/presensi-smpn1biau`) telah terverifikasi.

---

## 2. CREDENTIAL SETUP PROCEDURE

> [!WARNING]
> Jangan pernah menggunakan password pengujian lokal di server produksi live!

1. Buka file `.env` pada server produksi live:
   ```bash
   nano /home/u478110651/presensi-smpn1biau/.env
   ```
2. Tambahkan/perbarui baris berikut dengan password acak kuat baru:
   ```env
   SIASEK_EVIDENCE_PASSWORD=<Ganti_Dengan_Password_Acak_Kuat_24-32_Karakter>
   ```
3. Simpan file `.env`.

---

## 3. BACKUP DATABASE CHECKLIST

Sebelum mengeksekusi migrasi di server live, lakukan backup penuh database:

```bash
# SSH ke server live
mysqldump -u u478110651_sipada_user -p u478110651_sipada_smpn1b > /home/u478110651/backups/siasek_pre_evidence_$(date +%Y%m%d_%H%M%S).sql
```

- [ ] Pastikan file `.sql` hasil dump terbuat dan memiliki ukuran file > 0 bytes.

---

## 4. MANUAL DEPLOYMENT STEPS (STEP-BY-STEP)

Eksekusi langkah deployment berikut pada terminal SSH server live:

```bash
# 1. Masuk ke direktori aplikasi di server live
cd /home/u478110651/presensi-smpn1biau

# 2. Pull kode terbaru dari repository
git pull origin main

# 3. Jalankan migrasi database produksi (Idempotent & Safe)
php artisan migrate --force

# 4. Bersihkan cache Laravel
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 5. (Opsional) Re-optimize autoloader & config cache
php artisan config:cache
php artisan route:cache
```

> [!CAUTION]
> Jangan menggunakan endpoint HTTP public `/fix-storage-link` sebagai alur deployment utama. Gunakan selalu terminal SSH/CLI resmi server.

---

## 5. VERIFICATION STEPS (POST-DEPLOYMENT)

### 5.1 Verifikasi Database & User
Jalankan perintah berikut di server live untuk memastikan user `siasek_evidence` dan role `viewer` telah aktif:

```bash
php artisan tinker --execute="echo json_encode(App\Models\User::where('name', 'siasek_evidence')->first()->only(['id', 'name', 'email', 'role']));"
```
**Ekspektasi Output:** `{"id": ..., "name":"siasek_evidence", "email":"siasek_evidence@smpn1biau.sch.id", "role":"viewer"}`

### 5.2 Verifikasi Login Browser Live
1. Buka browser dan navigasi ke: `https://presensi-smpn1biau.zahradev.id/login`
2. Masukkan Email: `siasek_evidence@smpn1biau.sch.id`
3. Masukkan Password produksi yang diset pada `.env` live.
4. Klik **Log in**.
5. **Ekspektasi:** Pengguna berhasil masuk dan dialihkan ke `https://presensi-smpn1biau.zahradev.id/admin/dashboard`.

### 5.3 Verifikasi Otorisasi Read-Only
- Pastikan menu **Dasbor Utama**, **Dasbor Eksekutif**, **Laporan Presensi**, dan **Supervisi Jurnal** tampil pada sidebar.
- Pastikan menu **Pengaturan**, **Manajemen User**, dan tombol edit/hapus **TIDAK** dapat diakses.

---

## 6. ROLLBACK PLAN (RENCANA PEMULIHAN)

Jika terjadi hambatan mendesak saat atau setelah deployment, lakukan prosedur pemulihan berikut:

### Skenario A: Migrasi Gagal / Terjadi Kendala DB
```bash
cd /home/u478110651/presensi-smpn1biau
php artisan migrate:rollback --step=1
```

### Skenario B: Terjadi Kendala Kode / Routing Error
```bash
cd /home/u478110651/presensi-smpn1biau
git reset --hard HEAD~1
php artisan config:clear
php artisan route:clear
```

### Skenario C: Terjadi Inkonsistensi Data Produksi
Restore database dari file backup `.sql`:
```bash
mysql -u u478110651_sipada_user -p u478110651_sipada_smpn1b < /home/u478110651/backups/siasek_pre_evidence_YYYYMMDD_HHMMSS.sql
```

---

## 7. POST-DEPLOYMENT VERIFICATION SIGN-OFF

Setelah seluruh langkah verifikasi berhasil:
- [ ] Log in browser live berhasil dengan akun `siasek_evidence`.
- [ ] Peran `viewer` terverifikasi read-only.
- [ ] Browser automation `/siasek-bos` siap mengeksekusi pengambilan screenshot evidence BOSP.
