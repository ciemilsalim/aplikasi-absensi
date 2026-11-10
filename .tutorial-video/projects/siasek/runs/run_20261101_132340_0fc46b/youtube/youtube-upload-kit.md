# YouTube Upload Kit — `siasek`
- **Tanggal Dibuat**: `2026-11-10 05:46:20 UTC`
- **Durasi Video**: `74.37 detik` (YouTube Shorts)
- **File Video**: `D:\laragon\www\siasek\aplikasi-absensi\.tutorial-video\projects\siasek\runs\run_20261101_132340_0fc46b\final\final-tutorial-mobile.mp4`
- **Thumbnail**: `D:\laragon\www\siasek\aplikasi-absensi\.tutorial-video\projects\siasek\runs\run_20261101_132340_0fc46b\youtube\thumbnail.png`

---

## 1. Judul Video (Title)
```text
Panduan Registrasi Akun Orang Tua — SIASEK Presensi #Shorts
```
*(Karakter: 59/100)*

## 2. Deskripsi Video (Description with Timestamps)
```text
Panduan Registrasi Akun Orang Tua — SIASEK Presensi

Video panduan resmi cara penggunaan fitur pada aplikasi SIASEK. Ikuti langkah-langkah mudah di bawah ini untuk memahami alur penggunaan secara lengkap.

🕒 DAFTAR ISI & TIMESTAMPS:
00:00 - Buka Halaman Registrasi
00:18 - Isi Nama Dan Email
00:38 - Isi Kata Sandi
00:57 - Daftarkan Akun

📌 RINGKASAN LANGKAH-LANGKAH:
1. Buka Halaman Registrasi: Halo Bapak dan Ibu Orang Tua Wali Siswa, selamat datang di panduan aplikasi SIASEK Presensi.
2. Isi Nama Dan Email: Pada formulir pendaftaran, masukkan Nama Lengkap Anda secara jelas dan benar.
3. Isi Kata Sandi: Buatlah Kata Sandi yang aman, dengan panjang minimal delapan karakter.
4. Daftarkan Akun: Setelah seluruh data terisi dengan lengkap, ketuk tombol Daftarkan Akun.

✨ FITUR UTAMA & KEUNGGULAN:
- Panduan interaktif visual beresolusi tinggi (60 FPS).
- Navigasi responsif dan ramah pengguna.
- Terintegrasi langsung dengan database sistem.

🌐 Akses Sistem: http://localhost:8000
Jangan lupa Like, Comment, dan Subscribe untuk mendapatkan panduan fitur terbaru lainnya!

#Shorts #SIASEK #TutorialAplikasi #PresensiSekolah #OrangTuaSiswa #AplikasiPendidikan
```
*(Karakter: 1161/5000)*

## 3. Tag Video (Tags / Keywords)
```text
siasek, tutorial siasek, panduan siasek, tutorial aplikasi, panduan sistem, siasek presensi, presensi sekolah, absensi online, portal orang tua, wali murid, aplikasi sekolah, kehadiran siswa, tutorial absensi, registrasi, akun, orang, tua, presensi, buka halaman registrasi, isi nama dan email, isi kata sandi, daftarkan akun
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
python .agents/skills/app-tutorial-video/scripts/cli.py youtube-upload --run-dir "D:\laragon\www\siasek\aplikasi-absensi\.tutorial-video\projects\siasek\runs\run_20261101_132340_0fc46b" --privacy private --confirm-upload
```
