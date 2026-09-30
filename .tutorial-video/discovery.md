# Discovery App Tutorial Video V2.1 — Pengajuan Izin/Sakit Anak oleh Orang Tua

## 1. Ringkasan Aplikasi & Stack Context
- **Nama Aplikasi**: SIASEK (Sistem Informasi & Absensi Sekolah)
- **Target User**: Orang Tua / Wali Murid (`orang_tua`)
- **Mode**: Feature (`pengajuan_izin_orang_tua`)
- **Framework & UI**: Laravel PHP, Blade Templates, Tailwind CSS
- **URL Base**: `http://127.0.0.1:8000`

## 2. Peta Fitur & Alur Pengajuan Izin Anak
1. **Otentikasi Akun Orang Tua**:
   - URL: `http://127.0.0.1:8000/login`
   - Kredensial Demo: `emilsalimramadhan@gmail.com` / `password`
2. **Navigasi Riwayat Izin/Sakit**:
   - URL: `http://127.0.0.1:8000/parent/leave-requests`
   - Tombol Aksi: `Ajukan Izin / Sakit` (`a[href*='leave-requests/create']`)
3. **Pengisian Formulir Permohonan Izin**:
   - URL: `http://127.0.0.1:8000/parent/leave-requests/create`
   - Data Input:
     - Select Anak (`#student_id`): `Emil Salim`
     - Kategori Izin (`#type`): `Sakit` / `Izin`
     - Tanggal Mulai (`#start_date`): Tanggal hari ini
     - Tanggal Selesai (`#end_date`): Tanggal hari ini
     - Alasan Lengkap (`#reason`): "Anak sedang demam dan perlu istirahat berobat."
     - Tombol Submit: `button[type='submit']` ("Kirim Permohonan Izin")
4. **Hasil Verifikasi & Notifikasi Sukses**:
   - Halaman tujuan: `http://127.0.0.1:8000/parent/leave-requests`
   - Notifikasi sukses hijau: "Permohonan izin berhasil diajukan dan menunggu verifikasi sekolah."

## 3. Sasaran & Parameter Tutorial Video
- **Target Output**: `.tutorial-video/final/tutorial-pengajuan-izin-orang-tua-v2.mp4`
- **Target Durasi**: 60–90 Detik (Target 75 Detik)
- **Voice-over**: Edge-TTS Bahasa Indonesia (`id-ID-GitaNeural` atau `id-ID-ArdiNeural`) dengan nada ramah, sopan, dan instruktif bagi orang tua.
- **Subtitle**: SRT UTF-8 ter-burn-in secara sinkron.
- **Visual Enhancement**: Titlecard Intro/Outro, smooth mouse movement, ripple click, element highlight, dan zoom halus.
