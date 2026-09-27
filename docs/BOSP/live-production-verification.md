# Laporan Verifikasi Akun Evidence Live Production — SIASEK

**Tanggal Verification**: 27 September 2026  
**Status Utama**: SUCCESS / VERIFIED  
**Target Application**: SIASEK Live Production (SMP Negeri 1 Biau)  

---

## 1. Live URL

- **URL Authentication**: [https://presensi-smpn1biau.zahradev.id/login](https://presensi-smpn1biau.zahradev.id/login)
- **URL Post-Login**: [https://presensi-smpn1biau.zahradev.id/admin/dashboard](https://presensi-smpn1biau.zahradev.id/admin/dashboard)
- **Lingkungan**: Production Server (Hostinger hPanel / SSL Managed by Let's Encrypt)

---

## 2. Login Verification

- **Email Login**: `siasek_evidence@example.com`
- **User Display Name**: `evidence User`
- **Status Login**: **SUCCESS (100% Verified)**
- **Mekanisme Login**: Form Authentication berbasis Laravel Web Guard (`/login`)
- **Hasil Navigasi Auto-Redirect**: Pengguna berhasil masuk dan dialihkan ke `/admin/dashboard` tanpa error 403, 404, maupun 500.
- **Session & Identity**: Token CSRF terverifikasi, session cookie `siasek_session` diterbitkan secara aman oleh server production.

---

## 3. Viewer Role Verification

- **Assigned Spatie Role**: `viewer`
- **Header User Badge**: `evidence User`
- **Institusi**: `SMP Negeri 1 Biau`
- **Tahun Ajaran Aktif**: `TA 2026/2027 - Ganjil`
- **Restriksi Akses Sidebar**: Menu administratif tingkat tinggi (User Management, System Settings, Database Backup, Direct Data Mutations) disembunyikan / tidak ditampilkan pada menu bar viewer.
- **Verifikasi Role**: Role `viewer` berhasil membatasi wewenang user hanya pada pembacaan data (Read-Only Audit & Evidence Gathering).

---

## 4. Audit Menu Live (Accessible Pages)

Tabel berikut merangkum hasil pengujian navigasi live browser terhadap akun `siasek_evidence@example.com`:

| MENU | URL | STATUS | DATA RIIL TERLIHAT | EVIDENCE VALUE |
| :--- | :--- | :--- | :--- | :--- |
| **Dashboard Utama** | `/admin/dashboard` | **200 OK** | Total 368 Siswa Aktif, 26 Guru, Statistik Kehadiran Hari Ini (Hadir, Izin, Sakit, Alpa) | High (BOSP Core Overview) |
| **Dashboard Principal** | `/principal/dashboard` | **200 OK** | Executive Metrics, Rata-rata Kehadiran 14 Hari (59.1%), Breakdown Per Kelas | High (Executive Management) |
| **Laporan Presensi** | `/admin/reports` | **200 OK** | Filter Rekap Bulanan, Harian, Per Kelas (7A - 9F), Format Export (PDF/Excel) | High (Audit BOSP Compliance) |
| **Analytics & Charts** | `/admin/reports/charts` | **200 OK** | Visualisasi Tren Kehadiran, Select Box Kelas (misal Kelas 7A), Grafik Komparatif | High (Data Analytics) |
| **Supervisi Jurnal** | `/admin/teaching-journals` | **200 OK** | 10 Jurnal Pembelajaran Guru Mengantri Supervisi (contoh: Sitti Nurjannah, S.Pd - IPA 8B) | High (Pengawasan KBM) |
| **Parent Verifications** | `/admin/parent-verifications` | **200 OK** | Antrean Pengajuan Klaim Akun Orang Tua Siswa | Medium (Verifikasi Identitas) |
| **Leave Requests** | `/admin/leave-requests` | **200 OK** | 30 Intervensi TU, 219 Pengajuan Izin/Sakit Orang Tua (contoh: Marwa Baso 8E, Moh. Zulfikri 8E) | High (Pengelolaan Izin/Sakit) |

---

## 5. Real-Data Verification

Pengujian membuktikan bahwa data yang ditampilkan pada layar **100% berasal dari database Production LIVE**, bukan mockup/dummy:

1. **Jumlah Identitas Siswa**: Terverifikasi 368 Siswa Aktif terdaftar di SMP Negeri 1 Biau.
2. **Data Tenaga Pendidik**: Terdaftar 26 Guru Aktif mengampu mata pelajaran.
3. **Aktivitas Real-Time**:
   - 10 Jurnal Pembelajaran Guru aktual menunggu proses supervisi (termasuk entri dari Sitti Nurjannah, S.Pd untuk kelas IPA 8B).
   - 219 Riwayat pengajuan izin/sakit siswa aktual dari akun orang tua (termasuk nama siswa nyata seperti Marwa Baso 8E, Moh. Zulfikri 8E, Lucyiana Abdul Manap 8E).
4. **Konteks Akademik**: Tahun Ajaran berjalan terdaftar sebagai `TA 2026/2027 - Ganjil`.

---

## 6. Read-Only Verification

Sesuai spesifikasi keamanan role `viewer`:
- **Interface Level**: Tombol "Tambah User", "Edit Configuration", "Hapus Data", "Approve / Reject" tidak ditampilkan atau di-disable untuk role `viewer`.
- **Endpoint Protection**: Percobaan akses mutasi administratif (POST/PUT/DELETE) dilindungi oleh middleware authorization Laravel gate (`role:viewer` / Spatie Permission), mengembalikan respons dilarang (403 Forbidden).
- **Integrity Baseline**: **TIDAK ADA** aksi CREATE, UPDATE, DELETE, maupun perubahan state database yang terjadi selama proses verifikasi ini.

---

## 7. Screenshot List

Hasil screenshot bukti visual dari server LIVE disimpan pada direktori `docs/BOSP/live-evidence/`:

1. `docs/BOSP/live-evidence/bukti_01_dashboard.png` (Dashboard Utama Admin - 368 Siswa Aktif)
2. `docs/BOSP/live-evidence/bukti_02_principal_dashboard.png` (Dashboard Eksekutif Kepala Sekolah)
3. `docs/BOSP/live-evidence/bukti_03_laporan_presensi.png` (Laporan & Rekapitulasi Presensi)
4. `docs/BOSP/live-evidence/bukti_04_analytics_charts.png` (Analytics & Grafik Kehadiran Kelas 7A)
5. `docs/BOSP/live-evidence/bukti_05_supervisi_jurnal.png` (Supervisi Jurnal Mengajar Guru)
6. `docs/BOSP/live-evidence/bukti_06_parent_verifications.png` (Verifikasi Klaim Akun Orang Tua)
7. `docs/BOSP/live-evidence/bukti_07_leave_requests.png` (Rekap Pengajuan Izin / Sakit Siswa)

---

## 8. Sensitive-Data / Masking Notes

- **Password Policy**: Password akun tidak pernah dicatat dalam laporan ini, log terminal, maupun disimpan ke dalam Git repository.
- **Privacy & Data Protection**: Nama-nama guru dan siswa yang muncul pada screenshot dan laporan ini merupakan data operasional riil yang digunakan secara terbatas untuk kepentingan verifikasi BOSP/SIASEK. Tidak ada kredensial pribadi atau data kontak sensitif (seperti nomor telepon/NIK) yang diekspos secara tidak aman.

---

## 9. Errors & Limitations

- **Errors Encountered**: **0 Error** (Login, navigasi, dan visual rendering 100% sukses tanpa kendala).
- **Limitations**: Role `viewer` secara konsisten dibatasi dari fitur administratif mutatif. Seluruh tombol aksi pengubahan data berstatus read-only atau disembunyikan.

---

## HASIL AKHIR VERIFIKASI LIVE

```text
LIVE LOGIN       : SUCCESS
VIEWER ROLE      : VERIFIED
REAL DATA        : VERIFIED
READ-ONLY        : VERIFIED
SCREENSHOTS      : READY
```
