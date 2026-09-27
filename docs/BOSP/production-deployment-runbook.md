# PRODUCTION DEPLOYMENT RUNBOOK AKUN EVIDENCE SIASEK

**Aplikasi:** SIASEK (Sistem Informasi & Absensi Sekolah)  
**Target URL Live:** `https://presensi-smpn1biau.zahradev.id`  
**Nama Display Akun:** `SIASEK Evidence`  
**Login Email:** `siasek_evidence@example.com`  
**Role:** `viewer`  
**Tanggal Runbook:** 27 September 2026  
**Status Runbook:** APPROVED & ISOLATED PROVISIONING READY  

---

## 1. PRE-DEPLOYMENT CHECKLIST

- [ ] Repository lokal berada pada branch `main` dengan status `clean`.
- [ ] File `.env.siasek-bos` terdaftar di `.gitignore` dan **TIDAK** ter-commit ke Git.
- [ ] Database backup server produksi live sudah dibuat (MySQL dump).
- [ ] Password produksi baru untuk akun `siasek_evidence@example.com` telah disiapkan.
- [ ] Akses SSH/CLI ke server produksi live (`/home/u478110651/presensi-smpn1biau`) terverifikasi.

---

## 2. PRODUCTION ALUR DEPLOYMENT REGULER (SAFE ISOLATED FLOW)

Alur deployment dipisahkan secara ketat agar `php artisan migrate --force` bersifat 100% murni manajemen role/struktur tanpa ketergantungan password/credential:

```
BACKUP DATABASE ➔ GIT PULL ➔ PHP ARTISAN MIGRATE ➔ PHP ARTISAN SIASEK:CREATE-EVIDENCE-USER ➔ LOGIN VERIFY
```

---

## 3. MANUAL DEPLOYMENT STEPS (STEP-BY-STEP)

### Step 1: Backup Database Production Live
```bash
mysqldump -u u478110651_sipada_user -p u478110651_sipada_smpn1b > /home/u478110651/backups/siasek_pre_evidence_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Git Pull & Migration Murni
```bash
cd /home/u478110651/presensi-smpn1biau
git pull origin main

# Migrasi ini HANYA memastikan role 'viewer' tersedia di tabel roles (TIDAK membuat user)
php artisan migrate --force

# Pembersihan cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Step 3: Interactive User Provisioning (Artisan Command)
Jalankan perintah interaktif berikut di terminal SSH server live:

```bash
php artisan siasek:create-evidence-user
```
- Sistem akan meminta password secara tersembunyi (`$this->secret()`).
- Masukkan password produksi yang telah disiapkan, lalu konfirmasi.
- Perintah ini akan membuat/memperbarui user `siasek_evidence@example.com` dengan role `viewer` tanpa mengekspos password pada file log/shell history.

---

## 4. POST-DEPLOYMENT VERIFICATION STEPS

### 4.1 Verifikasi Database & User
```bash
php artisan tinker --execute="echo json_encode(App\Models\User::where('email', 'siasek_evidence@example.com')->first()->only(['id', 'name', 'email', 'role']));"
```
**Ekspektasi Output:** `{"id": ..., "name":"SIASEK Evidence", "email":"siasek_evidence@example.com", "role":"viewer"}`

### 4.2 Verifikasi Login Browser Live
1. Buka browser: `https://presensi-smpn1biau.zahradev.id/login`
2. Masukkan Email: `siasek_evidence@example.com`
3. Masukkan Password yang dibuat pada Step 3.
4. Klik **Log in**.
5. **Ekspektasi:** Login berhasil dan dialihkan ke `https://presensi-smpn1biau.zahradev.id/admin/dashboard`.

### 4.3 Verifikasi Otorisasi Read-Only
- Pastikan menu **Dasbor Utama**, **Dasbor Eksekutif**, **Laporan Presensi**, dan **Supervisi Jurnal** dapat dibaca.
- Pastikan rute mutasi (`POST`/`PUT`/`DELETE`) dan menu **Pengaturan/User** dikunci `403 Forbidden`.

---

## 5. ROLLBACK PLAN (RENCANA PEMULIHAN)

### Skenario A: Rollback Role Migration
```bash
cd /home/u478110651/presensi-smpn1biau
php artisan migrate:rollback --step=1
```

### Skenario B: Rollback Kode Aplikasi
```bash
git reset --hard HEAD~1
php artisan config:clear
php artisan route:clear
```

### Skenario C: Restore Database Penuh
```bash
mysql -u u478110651_sipada_user -p u478110651_sipada_smpn1b < /home/u478110651/backups/siasek_pre_evidence_YYYYMMDD_HHMMSS.sql
```
