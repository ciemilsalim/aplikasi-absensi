# LAPORAN PRODUCTION READINESS CHECK AKUN EVIDENCE SIASEK

**Nama Proyek:** SIASEK (Sistem Informasi & Absensi Sekolah)  
**Target App URL:** `https://presensi-smpn1biau.zahradev.id`  
**Nama Akun Evidence:** `siasek_evidence`  
**Role:** `viewer`  
**Tanggal Evaluasi:** 27 September 2026  
**Status Readiness:** **CONDITIONALLY READY (BUTENDED ACTION REQUIRED BEFORE DEPLOYMENT)**  

---

## 1. CURRENT STATUS & LOCAL VERIFICATION

1. **Ketersediaan Akun & Role Lokal:**  
   - Peran `viewer` telah terdaftar di database lokal (Spatie Role ID: `18`).
   - Akun `siasek_evidence` (ID: `227`) terverifikasi dengan role `viewer`.
2. **Pengujian Otorisasi Lokal:**  
   - Rute GET pembacaan dasbor, laporan, analytics, supervisi jurnal, dan daftar izin siswa dapat diakses dengan respons HTTP `200 OK` / `302 Found`.
   - Rute POST/PUT/DELETE mutasi data (simpan izin, approve/reject izin, verifikasi jurnal, manajemen user) terverifikasi **DITOLAK (HTTP `403 Forbidden`)**.

---

## 2. PRODUCTION GAP ANALYSIS

| ASPEK | STATUS LOKAL | STATUS PRODUCTION LIVE | GAP / TINDAKAN DIBUTUHKAN |
| :--- | :--- | :--- | :--- |
| **Kode Route & Sidebar** | Updated & Tested | belum ter-deploy | Wajib `git push` & `git pull` di server live. |
| **Migration Role & Account**| Migrated & Verified | belum dijalankan | Wajib jalankan `php artisan migrate --force` di server live. |
| **Akun `siasek_evidence`** | Active (ID: 227) | belum ada (`auth.failed`) | Dibuat otomatis saat migrasi server live dijalankan. |
| **Credential Password** | Sesuai `.env.siasek-bos` | Belum dikonfigurasi | **WAJIB ROTATE PASSWORD** di `.env` server live sebelum deploy. |

---

## 3. CLASSIFICATION OF CHANGES

| FILE | KATEGORI | ALASAN | RISIKO |
| :--- | :--- | :--- | :--- |
| `routes/web.php` | **A. Fitur Aplikasi SIASEK** | Membuka rute baca saja untuk role `viewer` & mengunci rute mutasi. | **RENDAH** (Sudah diuji runtime & static analysis). |
| `resources/views/layouts/sidebar.blade.php` | **A. Fitur Aplikasi SIASEK** | Menampilkan item menu Dasbor, Laporan, Supervisi Jurnal di sidebar untuk `viewer`. | **RENDAH** (Hanya visibilitas UI sidebar). |
| `database/migrations/2026_09_27_000001_...` | **B. Akun Evidence** | Membuat role `viewer` dan user `siasek_evidence` secara otomatis. | **RENDAH** (Idempotent, aman di-rollback). |
| `.gitignore` | **D. Tooling / Security** | Mencegah file credential `.env.siasek-bos` ter-commit ke Git. | **SANGAT RENDAH** (Meningkatkan keamanan repository). |
| `docs/BOSP/viewer-access-audit.md` | **C. Dokumentasi** | Dokumentasi audit otorisasi & pemetaan halaman evidence. | **TIDAK ADA** |
| `docs/BOSP/live-evidence-verification.md` | **C. Dokumentasi** | Dokumen verifikasi E2E & rekomendasi screenshot evidence. | **TIDAK ADA** |
| `docs/BOSP/viewer-production-readiness.md` | **C. Dokumentasi** | Laporan kesiapan produksi & prosedur deployment. | **TIDAK ADA** |

---

## 4. TEMUAN KEAMANAN & HARDENING CREDENTIAL (SECURITY HARDENING)

> [!TIP]
> **HARDENING 1: METODE PROVISIONING AMAN DENGAN FAIL-FAST & INTERAKTIF**  
> 1. **Migration Fail-Fast:** Migrasi `2026_09_27_000001_create_viewer_role_and_evidence_account.php` telah diperketat. Jika variabel `SIASEK_EVIDENCE_PASSWORD` tidak diset di `.env`, migrasi akan **FAIL FAST** (melempar `RuntimeException`) dan menolak membuat user dengan password default.  
> 2. **Artisan Command Interaktif:** Disediakan perintah `php artisan siasek:create-evidence-user` yang meminta password secara tersembunyi (`$this->secret()`) tanpa argumen CLI (mencegah password masuk ke shell history atau process list).

> [!IMPORTANT]
> **HARDENING 2: PEMBATASAN METODE DEPLOYMENT HTTP `/fix-storage-link`**  
> Endpoint HTTP public `/fix-storage-link` tidak boleh digunakan sebagai saluran deployment utama. Deployment wajib dilakukan melalui terminal SSH/CLI resmi server.

---

## 5. PROSEDUR DEPLOYMENT LIVE (SAFE MANUAL PROVISIONING)

### Skenario A: Provisioning via Migration (`SIASEK_EVIDENCE_PASSWORD` di `.env`)
1. Tambahkan password acak kuat pada file `.env` server live:
   ```env
   SIASEK_EVIDENCE_PASSWORD=<Ganti_Dengan_Password_Acak_Kuat_24-32_Karakter>
   ```
2. Jalankan `php artisan migrate --force` di server live.

### Skenario B: Provisioning Interaktif via Artisan Command (Tanpa Password di `.env`)
1. Jalankan `php artisan siasek:create-evidence-user` di terminal SSH server live.
2. Masukkan password tersembunyi secara interaktif saat diminta.

### Step 2: Backup Database Live
Lakukan backup dump MySQL database production (`u478110651_sipada_smpn1b`) sebelum mengeksekusi migrasi.

### Step 3: Git Commit & Push
```bash
git add routes/web.php resources/views/layouts/sidebar.blade.php database/migrations/2026_09_27_000001_create_viewer_role_and_evidence_account.php .gitignore docs/BOSP/
git commit -m "feat(auth): add viewer role and evidence account for BOSP evidence"
git push origin main
```

### Step 4: Pull & Migrate pada Server Production (via SSH/CLI)
```bash
cd /path/to/production/aplikasi-absensi
git pull origin main
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Step 5: Post-Deployment Verification
Log in ke `https://presensi-smpn1biau.zahradev.id/login` menggunakan email `siasek_evidence@smpn1biau.sch.id` dan password baru, lalu pastikan dasbor dibuka dengan lancar dan rute mutasi tetap menghasilkan `403 Forbidden`.

---

## 6. PROSEDUR ROLLBACK (ROLLBACK PLAN)

Jika terjadi hambatan setelah deployment:
1. **Rollback Kode:** `git reset --hard HEAD~1` di server live.
2. **Rollback Migration:** `php artisan migrate:rollback --step=1`
3. **Restore Database:** Restore file `.sql` backup jika terjadi inkonsistensi.
4. **Clear Cache:** `php artisan config:clear && php artisan route:clear`

---

## 7. REKOMENDASI FINAL GO / NO-GO

- **STATUS SOURCE CODE & ROUTING:** **GO (READY)**
- **STATUS MIGRATION IDEMPOTENCY:** **GO (READY)**
- **STATUS AUTHORIZATION & READ-ONLY:** **GO (READY)**
- **STATUS CREDENTIAL SECURITY:** **NO-GO UNTIL PASSWORD ROTATED ON LIVE `.env`**

**REKOMENDASI FINAL:**  
Perubahan source code dan migrasi secara teknis **SIAP (READY)** untuk dideploy. Namun, deployment **HANYA BOLEH DILAKUKAN** setelah password `SIASEK_EVIDENCE_PASSWORD` diubah menjadi password acak kuat pada environment server live.
