# Storyboard Tutorial Video V2.1: Pengajuan Izin/Sakit Anak oleh Orang Tua

**Target User**: Orang Tua / Wali Murid (`orang_tua`)  
**Mode**: Feature (`pengajuan_izin_orang_tua`)  
**Target Durasi**: 60–90 Detik (Target 75 Detik)  
**Output Target**: `.tutorial-video/final/tutorial-pengajuan-izin-orang-tua-v2.mp4`

---

## Titlecard Intro (00:00 - 00:04 | 4.0 Detik)
- **Visual**: Titlecard Elegan Slate Blue Dark (`#0f172a`), Judul "SIASEK", Subjudul "Panduan Pengajuan Izin / Sakit Anak".
- **Audio**: Intro Smooth Tone.

---

## Scene 1: Pembukaan Portal Orang Tua (00:04 - 00:14 | 10.0 Detik)
- **Visual**: Halaman Login Portal Utama SIASEK. Kursor di posisi netral.
- **Narasi Spoken**: "Selamat datang di panduan aplikasi SIASEK. Pada video kali ini, kita akan mempelajari cara mengajukan izin atau surat sakit anak secara online melalui portal orang tua."
- **Aksi Visual**: Membuka URL `http://127.0.0.1:8000/login` dan mempersiapkan login.

---

## Scene 2: Masuk Portal & Navigasi Permohonan Izin (00:14 - 00:28 | 14.0 Detik)
- **Visual**: Pengisian email orang tua (`emilsalimramadhan@gmail.com`) & password, klik "Masuk", lalu mengklik menu "Riwayat Izin/Sakit" -> "Ajukan Izin / Sakit".
- **Visual Highlight**: Outline neon biru pada tombol login dan link ajukan izin. Ripple effect saat klik.
- **Narasi Spoken**: "Silakan masuk ke portal orang tua menggunakan akun Anda. Selanjutnya, buka menu Riwayat Izin dan klik tombol Ajukan Izin atau Sakit."
- **Zoom Level**: 1.15x.

---

## Scene 3: Memilih Anak & Kategori Izin (00:28 - 00:41 | 13.0 Detik)
- **Visual**: Formulir Pengajuan Izin. Memilih nama siswa ("Emil Salim") pada dropdown `#student_id` dan kategori izin ("Sakit") pada dropdown `#type`.
- **Visual Highlight**: Highlight bidang dropdown siswa & kategori.
- **Narasi Spoken**: "Pada formulir pengajuan, pilih nama putra-putri Anda yang diajukan izin, kemudian tentukan kategori pengajuan seperti sakit atau izin acara keluarga."
- **Zoom Level**: 1.20x.

---

## Scene 4: Mengisi Rentang Tanggal & Alasan Lengkap (00:41 - 00:57 | 16.0 Detik)
- **Visual**: Pengisian tanggal mulai, tanggal selesai, dan pengetikan alasan rinci pada textarea `#reason`.
- **Visual Highlight**: Highlight bidang tanggal dan textarea keterangan.
- **Narasi Spoken**: "Tentukan rentang tanggal pelaksanaan izin, lalu tuliskan alasan serta keterangan kondisi anak secara jelas agar memudahkan pihak sekolah melakukan verifikasi."
- **Zoom Level**: 1.20x.

---

## Scene 5: Mengirim Permohonan Izin (00:57 - 01:09 | 12.0 Detik)
- **Visual**: Kursor mengklik tombol "Kirim Permohonan Izin" (`button[type='submit']`).
- **Visual Highlight**: Highlight tombol kirim permohonan. Ripple efek klik jelas.
- **Narasi Spoken**: "Setelah seluruh data terisi dengan benar, klik tombol Kirim Permohonan Izin untuk mengirimkan dokumen ke pihak sekolah."
- **Zoom Level**: 1.25x.

---

## Scene 6: Konfirmasi Sukses & Pengawasan Status (01:09 - 01:19 | 10.0 Detik)
- **Visual**: Notifikasi sukses berwarna hijau dan status permohonan tercatat di tabel riwayat.
- **Narasi Spoken**: "Permohonan izin anak Anda telah berhasil terkirim dan tercatat pada sistem sekolah. Terima kasih dan semoga sehat selalu."

---

## Titlecard Outro (01:19 - 01:23 | 4.0 Detik)
- **Visual**: Titlecard Penutup "Pengajuan Izin Selesai", Subjudul "Terima kasih atas kerja sama Anda."
- **Total Durasi Est**: **~79 detik** (Terverifikasi di rentang 60–90 detik).
