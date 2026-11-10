# YouTube Upload Kit — `siasek`
- **Tanggal Dibuat**: `2026-11-10 05:49:21 UTC`
- **Durasi Video**: `108.87 detik` (YouTube Shorts)
- **File Video**: `D:\laragon\www\siasek\aplikasi-absensi\.tutorial-video\projects\siasek\runs\run_20261101_135451_0d1728\final\final-tutorial-mobile.mp4`
- **Thumbnail**: `D:\laragon\www\siasek\aplikasi-absensi\.tutorial-video\projects\siasek\runs\run_20261101_135451_0d1728\youtube\thumbnail.png`

---

## 1. Judul Video (Title)
```text
Panduan Menghubungkan Data Siswa dengan Akun Orang Tua — SIASEK Presensi #Shorts
```
*(Karakter: 80/100)*

## 2. Deskripsi Video (Description with Timestamps)
```text
Panduan Menghubungkan Data Siswa dengan Akun Orang Tua — SIASEK Presensi

Video panduan resmi cara penggunaan fitur pada aplikasi SIASEK. Ikuti langkah-langkah mudah di bawah ini untuk memahami alur penggunaan secara lengkap.

🕒 DAFTAR ISI & TIMESTAMPS:
00:00 - Masukkan Nomor Whatsapp
00:31 - Pilih Kelas Dan Nama Anak
00:57 - Masukkan Nis Dan Hubungkan
01:21 - Konfirmasi Dan Ke Dasbor

📌 RINGKASAN LANGKAH-LANGKAH:
1. Masukkan Nomor Whatsapp: Selamat datang di panduan aplikasi SIASEK Presensi.
2. Pilih Kelas Dan Nama Anak: Pada langkah kedua, silakan pilih kelas siswa tempat anak Anda belajar saat ini.
3. Masukkan Nis Dan Hubungkan: Selanjutnya, cantumkan Nomor Induk Siswa atau NIS anak Anda agar akun terverifikasi dan langsung aktif secara otomatis.
4. Konfirmasi Dan Ke Dasbor: Data anak Anda kini telah berhasil terhubung dan tercatat aktif.

✨ FITUR UTAMA & KEUNGGULAN:
- Panduan interaktif visual beresolusi tinggi (60 FPS).
- Navigasi responsif dan ramah pengguna.
- Terintegrasi langsung dengan database sistem.

🌐 Akses Sistem: http://localhost:8000
Jangan lupa Like, Comment, dan Subscribe untuk mendapatkan panduan fitur terbaru lainnya!

#Shorts #SIASEK #TutorialAplikasi #PresensiSekolah #OrangTuaSiswa #AplikasiPendidikan
```
*(Karakter: 1243/5000)*

## 3. Tag Video (Tags / Keywords)
```text
siasek, tutorial siasek, panduan siasek, tutorial aplikasi, panduan sistem, siasek presensi, presensi sekolah, absensi online, portal orang tua, wali murid, aplikasi sekolah, kehadiran siswa, tutorial absensi, menghubungkan, data, siswa, akun, orang, tua, presensi, masukkan nomor whatsapp, pilih kelas dan nama anak, masukkan nis dan hubungkan, konfirmasi dan ke dasbor
```

## 4. Pengaturan Upload yang Disarankan
- **Kategori**: `27` (Pendidikan / Education) atau `28` (Sains & Teknologi)
- **Visibilitas Awal**: `private` (disarankan private/unlisted untuk review akhir)
- **Dibuat untuk Anak-Anak**: `Tidak` (`selfDeclaredMadeForKids: false`)
- **Bahasa Video**: `Indonesia (id)`

---

## 5. Perintah CLI Upload Otomatis (YouTube Data API v3)
```powershell
# Langkah A: Autentikasi OAuth Google YouTube (hanya sekali):
python .agents/skills/app-tutorial-video/scripts/cli.py youtube-auth --client-secrets .credentials/client_secret.json

# Langkah B: Upload video langsung ke channel YouTube:
python .agents/skills/app-tutorial-video/scripts/cli.py youtube-upload --run-dir "D:\laragon\www\siasek\aplikasi-absensi\.tutorial-video\projects\siasek\runs\run_20261101_135451_0d1728" --privacy private --confirm-upload
```
