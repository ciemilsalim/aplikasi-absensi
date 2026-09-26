# DAFTAR BUKTI VISUAL SCREENSHOT APLIKASI
**Aplikasi Presensi SIASEK (Sistem Kehadiran Real-time)**  
*Pemilik & Pengembang: Zahradev | Pengguna Layanan: SMP Negeri 1 Biau*  
*Skema: Sewa/Penggunaan Layanan Aplikasi (Rp1.000,-/siswa/bulan)*  
*Tanggal Inventarisasi: 26 September 2026*  
*Direktori Penyimpanan: `docs/LPJ/assets/screenshots/`*

---

Dokumen ini memuat katalog seluruh bukti tangkapan layar (*screenshot*) yang diperoleh secara langsung (*empirically captured*) melalui peramban web pada lingkungan lokal aktif (`http://127.0.0.1:8002`), membuktikan operasionalitas antarmuka sistem secara faktual:

| ID | Fitur | Halaman | Bukti Visual | Status |
|---|---|---|---|---|
| **SS-01** | Halaman Masuk Sistem (Login Multi-Peran & Logo Sekolah) | `/login` | `docs/LPJ/assets/screenshots/SS-01-login.png` | Tersedia (Captured) |
| **SS-02** | Kios Pemindai Presensi Masuk Gerbang (Webcam QR & Wajah) | `/scanner` | `docs/LPJ/assets/screenshots/SS-02-scanner-gerbang.png` | Tersedia (Captured) |
| **SS-03** | Pemindai Dispensasi Izin Gerbang (Permit Scanner Siswa) | `/permit-scanner` | `docs/LPJ/assets/screenshots/SS-03-permit-scanner.png` | Tersedia (Captured) |
| **SS-04** | Dasbor Pemantauan Petugas Piket & Admin Real-Time | `/admin/dashboard` | `docs/LPJ/assets/screenshots/SS-04-dashboard-admin.png` | Tersedia (Captured) |
| **SS-05** | Dasbor Guru & Wali Kelas (Kelas Bimbingan & Sesi KBM) | `/teacher/dashboard` | `docs/LPJ/assets/screenshots/SS-05-dashboard-guru.png` | Tersedia (Captured) |
| **SS-06** | Pemindai Presensi Mata Pelajaran Kelas | `/teacher/subject-attendance/scanner/{schedule}` | `resources/views/teacher/subject_attendance_scanner.blade.php` | Tidak dapat diverifikasi (Memerlukan sesi jadwal dinamis hari ini) |
| **SS-07** | Parameter Filter & Rekapitulasi Presensi Mapel | `/teacher/subject-attendance/report` | `docs/LPJ/assets/screenshots/SS-07-presensi-mapel-report.png` | Tersedia (Captured) |
| **SS-08** | Dokumen Cetak Presensi Mapel Berkop Resmi | `/teacher/subject-attendance/report/print` | `resources/views/teacher/report_print.blade.php` | Tidak dapat diverifikasi (Memerlukan query tanggal spesifik) |
| **SS-09** | Analitik Visual Grafik Kehadiran Mapel (Chart.js) | `/teacher/subject-attendance/charts` | `docs/LPJ/assets/screenshots/SS-09-analitik-mapel.png` | Tersedia (Captured) |
| **SS-10** | Presensi Mandiri Guru (Geofence GPS & Verifikasi Wajah) | `/teacher/attendance/dashboard` | `docs/LPJ/assets/screenshots/SS-10-presensi-guru-gps.png` | Tersedia (Captured) |
| **SS-11** | Buku Jurnal Harian Mengajar Guru Mata Pelajaran | `/teacher/journals` | `docs/LPJ/assets/screenshots/SS-11-jurnal-mengajar-guru.png` | Tersedia (Captured) |
| **SS-12** | Formulir Evaluasi Refleksi Pembelajaran Semester Guru | `/teacher/journals/reflection` | `docs/LPJ/assets/screenshots/SS-12-refleksi-semester.png` | Tersedia (Captured) |
| **SS-13** | Buku Catatan Anekdot Sikap & Disiplin Karakter Siswa | `/teacher/anecdotes` | `docs/LPJ/assets/screenshots/SS-13-catatan-anekdot.png` | Tersedia (Captured) |
| **SS-14** | Dasbor Fasilitator Proyek Kokurikuler (P5) | `/teacher/dashboard?view=fasilitator_kokurikuler` | `docs/LPJ/assets/screenshots/SS-14-kokurikuler-dashboard.png` | Tersedia (Captured) |
| **SS-15** | Log Riwayat Sesi Presensi Proyek Kokurikuler | `/teacher/subject-attendance/history?type=cocurricular` | `docs/LPJ/assets/screenshots/SS-15-kokurikuler-riwayat.png` | Tersedia (Captured) |
| **SS-16** | Formulir Laporan Presensi Proyek Kokurikuler | `/teacher/subject-attendance/report?type=cocurricular` | `docs/LPJ/assets/screenshots/SS-16-kokurikuler-laporan.png` | Tersedia (Captured) |
| **SS-17** | Dasbor Pemantauan Orang Tua (Monitoring Anak) | `/parent/dashboard` | `docs/LPJ/assets/screenshots/SS-17-dashboard-orangtua.png` | Tersedia (Captured) |
| **SS-18** | Formulir Pengajuan Permohonan Izin Siswa Daring | `/parent/leave-requests/create` | `docs/LPJ/assets/screenshots/SS-18-pengajuan-izin-ortu.png` | Tersedia (Captured) |
| **SS-19** | Buku Panduan Penggunaan Aplikasi untuk Orang Tua | `/parent/guide` | `docs/LPJ/assets/screenshots/SS-19-panduan-orangtua.png` | Tersedia (Captured) |
| **SS-20** | Ruang Obrolan Interaktif Orang Tua <-> Guru Wali Kelas | `/chat` | `docs/LPJ/assets/screenshots/SS-20-chat-ortu-guru.png` | Tersedia (Captured) |
| **SS-21** | Layar Intervensi Manual Surat Izin oleh Petugas TU | `/admin/leave-requests` | `docs/LPJ/assets/screenshots/SS-21-intervensi-izin-admin.png` | Tersedia (Captured) |
| **SS-22** | Dasbor Pengawasan Eksekutif Kepala Sekolah | `/principal/dashboard` | `docs/LPJ/assets/screenshots/SS-22-dashboard-kepsek.png` | Tersedia (Captured) |
| **SS-23** | Layar Generator Laporan Presensi PDF & Rekapitulasi | `/admin/reports` | `docs/LPJ/assets/screenshots/SS-23-generator-laporan.png` | Tersedia (Captured) |
| **SS-24** | Supervisi & Pengesahan Jurnal Mengajar oleh Pimpinan | `/admin/teaching-journals` | `docs/LPJ/assets/screenshots/SS-24-supervisi-jurnal.png` | Tersedia (Captured) |
| **SS-25** | Verifikasi & Pengesahan Klaim Orang Tua - Siswa | `/admin/parent-verifications` | `docs/LPJ/assets/screenshots/SS-25-verifikasi-orangtua.png` | Tersedia (Captured) |
| **SS-26** | Panel Konfigurasi Logo & Mode Gelap (*Dark Mode*) | `/admin/settings/appearance` | `docs/LPJ/assets/screenshots/SS-26-pengaturan-tampilan.png` | Tersedia (Captured) |
| **SS-27** | Antarmuka Mode Luring PWA (*Offline Fallback Screen*) | `/offline` | `docs/LPJ/assets/screenshots/SS-27-pwa-offline.png` | Tersedia (Captured) |

---

## Rekapitulasi & Validasi Bukti Visual
- **Target Tangkapan Layar (Target ID)**: 27 ID (SS-01 s.d. SS-27).
- **Tangkapan Layar Aktual Berhasil Terverifikasi**: 25 Berkas PNG Beresolusi Tinggi (~5.5 MB total).
- **Tangkapan Layar Belum Dapat Diverifikasi**: 2 ID (SS-06 dan SS-08) karena kondisi pengujian sesi KBM aktif dan riwayat tanggal dinamis belum tersedia pada lingkungan lokal saat audit berlangsung. Sesuai prinsip *research-first* dan *zero hallucination*, tidak dilakukan rekayasa visual palsu.
- **Standar Format & Integritas File**:
  - Format penyimpanan: Portable Network Graphics (PNG) 24-bit sRGB.
  - Setiap ID bersifat unik (1 ID = tepat 1 file aktual atau status verifikasi).
  - Setiap file screenshot di disk memiliki tepat satu ID referensi yang berkesesuaian.
- **Metode Pengujian**: Diambil langsung via emulasi peramban web (*browser automation*) pada lingkungan lokal aktif (`http://127.0.0.1:8002`) menggunakan akun uji terdaftar (Admin, Guru/Wali Kelas, Orang Tua).

