# LAPORAN PRODUCTION READINESS CHECK AKUN EVIDENCE SIASEK

**Nama Proyek:** SIASEK (Sistem Informasi & Absensi Sekolah)  
**Target App URL:** `https://presensi-smpn1biau.zahradev.id`  
**Nama Display Akun:** `SIASEK Evidence`  
**Login Email:** `siasek_evidence@example.com`  
**Role:** `viewer`  
**Tanggal Evaluasi:** 27 September 2026  
**Status Readiness:** **100% PRODUCTION READY (ISOLATED PROVISIONING DESIGNED)**  

---

## 1. MIGRATION & PROVISIONING ISOLATION DESIGN

1. **Pemisahan Peran Migrasi:**  
   - Migrasi `2026_09_27_000001_create_viewer_role_and_evidence_account.php` **HANYA** bertanggung jawab memastikan role `viewer` ada pada tabel `roles`.
   - Migrasi **TIDAK** membuat user `siasek_evidence@example.com`, tidak meminta/membaca password, dan tidak mengubah akun/role existing.
   - Perintah `php artisan migrate --force` bersifat 100% independen dan aman untuk production tanpa syarat credential environment.

2. **Pemisahan User Provisioning:**  
   - Pembuatan & pengelolaan user `siasek_evidence@example.com` ditangani secara terpisah melalui perintah Artisan interaktif:
     `php artisan siasek:create-evidence-user`
   - Command meminta password secara tersembunyi (`$this->secret()`) tanpa argumen CLI (mencegah password terekspos di log/history).
   - Command bersifat idempotent: jika email `siasek_evidence@example.com` sudah ada, command mengonfirmasi peran `viewer` dan hanya mengubah password jika user secara terpisah menginput password baru.
   - Jika ada akun legacy dengan nama mirip tetapi email beda, command memberikan peringatan informatif tanpa merge/delete otomatis.

---

## 2. REKAPITULASI PENGUJIAN SKENARIO ISOLASI (TEST RESULTS)

| SKENARIO | KETERANGAN PENGUJIAN | HASIL LOCAL TEST |
| :---: | :--- | :---: |
| **A** | DB Tanpa Role Viewer $\rightarrow$ `migrate` $\rightarrow$ Role `viewer` Dibuat | **✔ PASSED** |
| **B** | DB Sudah Ada Role Viewer $\rightarrow$ `migrate` $\rightarrow$ No Duplicate Role | **✔ PASSED** |
| **C** | User Evidence Belum Ada $\rightarrow$ `migrate` $\rightarrow$ User TIDAK Dibuat | **✔ PASSED** |
| **D** | Provisioning User via `php artisan siasek:create-evidence-user` | **✔ PASSED** |
| **E** | Command Dijalankan Ulang $\rightarrow$ No Duplicate User | **✔ PASSED** |
| **F** | Akun Legacy Nama Mirip tapi Beda Email $\rightarrow$ Tidak Di-merge/Delete | **✔ PASSED** |
| **G** | Login Email `siasek_evidence@example.com` | **✔ PASSED** |
| **H** | Viewer Read-Only $\rightarrow$ GET Allowed (`200`), Mutation Forbidden (`403`) | **✔ PASSED** |

---

## 3. PRODUCTION ALUR DEPLOYMENT REGULER

```
BACKUP DATABASE ➔ GIT PULL ➔ PHP ARTISAN MIGRATE ➔ PHP ARTISAN SIASEK:CREATE-EVIDENCE-USER ➔ LOGIN LIVE VERIFY
```

1. **Backup Database:** Execute MySQL dump.
2. **Git Pull:** Pull commit terbaru ke server live.
3. **Execute Migrate:** `php artisan migrate --force` (Membuat role `viewer` di DB).
4. **Provision User:** `php artisan siasek:create-evidence-user` (Input password tersembunyi).
5. **Verify Live Login:** Uji login di browser `https://presensi-smpn1biau.zahradev.id/login`.
