import json

plan_data = {
  "version": "3.7.0",
  "run_id": "run-v37-parent-connect-student-mobile",
  "title": "Tutorial Cara Menghubungkan Akun Siswa dengan Orang Tua (Mobile)",
  "mode": "mobile",
  "variant": "mobile",
  "target_resolution": [1080, 1920],
  "viewport": {
    "width": 414,
    "height": 736,
    "device_scale_factor": 2.608695652,
    "is_mobile": True,
    "has_touch": True
  },
  "mobile_viewport": {
    "width": 414,
    "height": 736
  },
  "voice": {
    "name": "id-ID-GadisNeural",
    "rate": "-2%",
    "pitch": "+0Hz",
    "persona": "human_friendly_guide",
    "prosody": "conversational_natural"
  },
  "reaction_delay_ms": 300,
  "timing_rule": "NARRATION_FIRST_REACTION_PAUSE",
  "expected_visual_keywords": ["portal", "orang tua", "whatsapp", "kelas", "siswa", "nis", "hubungkan", "dasbor"],
  "scenes": [
    {
      "scene_id": "scene_01_intro_portal",
      "title": "Pembukaan Portal SIASEK Mobile",
      "target_url": "http://127.0.0.1:8000/login",
      "narration": "Selamat datang di portal presensi SIASEK versi mobile. Pada tutorial kali ini, kita akan mempelajari cara menghubungkan akun siswa dengan akun orang tua.",
      "target_element": "body",
      "target_visual": "Portal SIASEK Mobile",
      "zoom": 1.00,
      "highlight": False,
      "camera_target": { "x": 540, "y": 960, "zoom": 1.00 },
      "cursor_target": { "x": 540, "y": 960 },
      "actions": [
        { "type": "wait", "ms": 500 }
      ],
      "expected_result": "Halaman login mobile SIASEK tampil rapi",
      "expected_ui": ["SIASEK", "Masuk ke Akun Anda"],
      "verification_required": True
    },
    {
      "scene_id": "scene_02_login_wali",
      "title": "Masuk ke Akun Orang Tua",
      "target_url": "http://127.0.0.1:8000/login",
      "narration": "Silakan masukkan alamat email dan kata sandi akun orang tua Anda, lalu ketuk tombol Masuk ke Akun.",
      "target_element": "button[type='submit']",
      "target_visual": "Formulir Masuk Akun Orang Tua",
      "zoom": 1.00,
      "highlight": False,
      "camera_target": { "x": 540, "y": 960, "zoom": 1.00 },
      "cursor_start": { "x": 540, "y": 980 },
      "cursor_target": { "x": 540, "y": 1070 },
      "actions": [
        { "type": "fill", "selector": "#email", "value": "cici@gmail.com" },
        { "type": "fill", "selector": "#password", "value": "password" },
        { "type": "click", "selector": "button[type='submit']" },
        { "type": "wait", "ms": 1500 }
      ],
      "expected_result": "Berhasil login dan laman aktivasi orang tua terbuka",
      "expected_ui": ["Portal Orang Tua", "Selamat Datang"],
      "verification_required": True
    },
    {
      "scene_id": "scene_03_isi_kontak_wa",
      "title": "Langkah 1: Mengisi Nomor WhatsApp",
      "target_url": "http://127.0.0.1:8000/parent/onboarding",
      "narration": "Pada langkah pertama, masukkan nomor WhatsApp aktif Anda agar menerima laporan presensi harian anak secara otomatis.",
      "target_element": "input[x-model='phoneNumber']",
      "target_visual": "Input Nomor WhatsApp",
      "zoom": 1.00,
      "highlight": False,
      "camera_target": { "x": 540, "y": 960, "zoom": 1.00 },
      "cursor_start": { "x": 540, "y": 1050 },
      "cursor_target": { "x": 540, "y": 1101 },
      "actions": [
        { "type": "fill", "selector": "input[x-model='phoneNumber']", "value": "081234567890" },
        { "type": "wait", "ms": 600 }
      ],
      "expected_result": "Nomor WhatsApp orang tua berhasil terisi",
      "expected_ui": ["081234567890"],
      "verification_required": True
    },
    {
      "scene_id": "scene_04_lanjut_ke_step2",
      "title": "Melanjutkan ke Langkah Hubungkan Anak",
      "target_url": "http://127.0.0.1:8000/parent/onboarding",
      "narration": "Kemudian ketuk tombol Lanjut ke Langkah 2 untuk membuka formulir pencarian dan penghubung data anak Anda.",
      "target_element": "button:has-text('Lanjut ke Langkah 2')",
      "target_visual": "Tombol Lanjut ke Langkah 2",
      "zoom": 1.00,
      "highlight": False,
      "camera_target": { "x": 540, "y": 960, "zoom": 1.00 },
      "cursor_start": { "x": 540, "y": 1480 },
      "cursor_target": { "x": 540, "y": 1586 },
      "actions": [
        { "type": "click", "selector": "button:has-text('Lanjut ke Langkah 2')" },
        { "type": "wait", "ms": 1200 }
      ],
      "expected_result": "Langkah 2 formulir pencarian anak terbuka",
      "expected_ui": ["Hubungkan Data Anak", "Pilih Kelas Siswa"],
      "verification_required": True
    },
    {
      "scene_id": "scene_05_pilih_kelas_anak",
      "title": "Langkah 2: Memilih Kelas Siswa",
      "target_url": "http://127.0.0.1:8000/parent/onboarding",
      "narration": "Pada langkah kedua, pilih kelas siswa tempat anak Anda bersekolah, misalnya Kelas 8A.",
      "target_element": "select[x-model='selectedClassId']",
      "target_visual": "Dropdown Pilih Kelas Siswa",
      "zoom": 1.00,
      "highlight": False,
      "camera_target": { "x": 540, "y": 960, "zoom": 1.00 },
      "cursor_start": { "x": 540, "y": 1450 },
      "cursor_target": { "x": 540, "y": 1379 },
      "actions": [
        { "type": "select", "selector": "select[x-model='selectedClassId']", "value": "10" },
        { "type": "wait", "ms": 800 }
      ],
      "expected_result": "Kelas 8A terpilih",
      "expected_ui": ["Kelas 8A"],
      "verification_required": True
    },
    {
      "scene_id": "scene_06_ketik_nama_siswa",
      "title": "Mengetik Nama Anak Anda",
      "target_url": "http://127.0.0.1:8000/parent/onboarding",
      "narration": "Ketik nama lengkap anak Anda. Sistem cerdas akan langsung mencocokkan data siswa yang terdaftar di sekolah.",
      "target_element": "input[x-model='typedStudentName']",
      "target_visual": "Input Nama Anak dan Deteksi Cerdas",
      "zoom": 1.00,
      "highlight": False,
      "camera_target": { "x": 540, "y": 960, "zoom": 1.00 },
      "cursor_start": { "x": 540, "y": 1480 },
      "cursor_target": { "x": 540, "y": 1568 },
      "actions": [
        { "type": "fill", "selector": "input[x-model='typedStudentName']", "value": "Najwa Dayani" },
        { "type": "wait", "ms": 1000 }
      ],
      "expected_result": "Kartu pencocokan siswa Najwa Dayani muncul",
      "expected_ui": ["Najwa Dayani", "Siswa Ditemukan di Database"],
      "verification_required": True
    },
    {
      "scene_id": "scene_07_pilih_siswa_ini",
      "title": "Memilih Siswa dari Database Rekomendasi",
      "target_url": "http://127.0.0.1:8000/parent/onboarding",
      "narration": "Data siswa ditemukan di database sekolah. Ketuk tombol Pilih Siswa Ini untuk mengonfirmasi pilihan Anda.",
      "target_element": "button:has-text('Pilih Siswa Ini')",
      "target_visual": "Tombol Pilih Siswa Ini",
      "zoom": 1.00,
      "highlight": False,
      "camera_target": { "x": 540, "y": 960, "zoom": 1.00 },
      "cursor_start": { "x": 720, "y": 1670 },
      "cursor_target": { "x": 795, "y": 1732 },
      "actions": [
        { "type": "click", "selector": "button:has-text('Pilih Siswa Ini')" },
        { "type": "wait", "ms": 500 },
        { "type": "scroll", "delta": 320 },
        { "type": "wait", "ms": 500 }
      ],
      "expected_result": "Siswa terpilih dan layar bergeser ke kolom NIS",
      "expected_ui": ["NIS (Nomor Induk Siswa)"],
      "verification_required": True
    },
    {
      "scene_id": "scene_08_verifikasi_nis_hubungkan",
      "title": "Verifikasi NIS dan Hubungkan Siswa",
      "target_url": "http://127.0.0.1:8000/parent/onboarding",
      "narration": "Ketik NIS siswa yaitu 1219 untuk verifikasi instan, lalu ketuk Hubungkan Siswa Ini.",
      "target_element": "button:has-text('Hubungkan Siswa Ini')",
      "target_visual": "Tombol Hubungkan Siswa Ini",
      "zoom": 1.00,
      "highlight": False,
      "camera_target": { "x": 540, "y": 960, "zoom": 1.00 },
      "cursor_start": { "x": 540, "y": 1420 },
      "cursor_target": { "x": 540, "y": 1493 },
      "actions": [
        { "type": "fill", "selector": "input[x-model='verificationCode']", "value": "1219" },
        { "type": "wait", "ms": 500 },
        { "type": "click", "selector": "button:has-text('Hubungkan Siswa Ini')" },
        { "type": "wait", "ms": 1500 }
      ],
      "expected_result": "Siswa berhasil terhubung dan muncul di daftar anak",
      "expected_ui": ["Najwa Dayani", "Terverifikasi Aktif"],
      "verification_required": True
    },
    {
      "scene_id": "scene_09_lanjut_ke_step3",
      "title": "Siswa Terhubung dan Lanjut ke Konfirmasi",
      "target_url": "http://127.0.0.1:8000/parent/onboarding",
      "narration": "Data anak telah terverifikasi aktif dengan badge hijau. Ketuk Lanjut ke Langkah 3 untuk konfirmasi panduan.",
      "target_element": "button:has-text('Lanjut ke Langkah 3')",
      "target_visual": "Tombol Lanjut ke Langkah 3",
      "zoom": 1.00,
      "highlight": False,
      "camera_target": { "x": 540, "y": 960, "zoom": 1.00 },
      "cursor_start": { "x": 600, "y": 1500 },
      "cursor_target": { "x": 654, "y": 1558 },
      "actions": [
        { "type": "click", "selector": "button:has-text('Lanjut ke Langkah 3')" },
        { "type": "wait", "ms": 1200 },
        { "type": "scroll", "delta": -400 },
        { "type": "wait", "ms": 600 }
      ],
      "expected_result": "Langkah 3 ringkasan panduan aplikasi tampil",
      "expected_ui": ["Pendaftaran Siap Digunakan", "Pantau Presensi Harian"],
      "verification_required": True
    },
    {
      "scene_id": "scene_10_selesai_ke_dasbor",
      "title": "Selesai dan Masuk ke Dasbor Utama",
      "target_url": "http://127.0.0.1:8000/parent/onboarding",
      "narration": "Terakhir, ketuk Selesai dan Masuk ke Dasbor Utama untuk mulai memantau kehadiran anak Anda.",
      "target_element": "button:has-text('Selesai & Masuk ke Dasbor Utama')",
      "target_visual": "Tombol Selesai & Masuk ke Dasbor Utama",
      "zoom": 1.00,
      "highlight": False,
      "camera_target": { "x": 540, "y": 960, "zoom": 1.00 },
      "cursor_start": { "x": 540, "y": 1490 },
      "cursor_target": { "x": 540, "y": 1550 },
      "actions": [
        { "type": "click", "selector": "button:has-text('Selesai & Masuk ke Dasbor Utama')" },
        { "type": "wait", "ms": 1800 }
      ],
      "expected_result": "Dasbor Orang Tua terbuka dengan profil siswa aktif",
      "expected_ui": ["Dasbor Orang Tua/Wali", "Najwa Dayani", "Kelas 8A"],
      "expected_url_contains": "dashboard",
      "verification_required": True
    },
    {
      "scene_id": "scene_11_penutup_dasbor",
      "title": "Profil Siswa Berhasil Terhubung di Dasbor",
      "target_url": "http://127.0.0.1:8000/parent/dashboard",
      "narration": "Selamat! Akun siswa berhasil terhubung dengan akun orang tua. Anda siap memantau presensi dan informasi belajar anak dengan nyaman di portal SIASEK.",
      "target_element": "body",
      "target_visual": "Dasbor Utama Orang Tua Mobile",
      "zoom": 1.00,
      "highlight": False,
      "camera_target": { "x": 540, "y": 960, "zoom": 1.00 },
      "cursor_start": { "x": 540, "y": 980 },
      "cursor_target": { "x": 540, "y": 960 },
      "actions": [
        { "type": "wait", "ms": 1500 }
      ],
      "expected_result": "Dasbor Orang Tua menampilkan anak terhubung siap pakai",
      "expected_ui": ["Najwa Dayani", "NIS: 1219", "Kelas 8A"],
      "verification_required": True
    }
  ]
}

target_path = '.tutorial-video/plans/parent-connect-student-v37-mobile.json'
with open(target_path, 'w', encoding='utf-8') as f:
    json.dump(plan_data, f, indent=2, ensure_ascii=False)
print("Plan JSON written successfully!")
