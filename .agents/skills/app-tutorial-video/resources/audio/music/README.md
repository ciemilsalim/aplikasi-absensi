# INSTRUMENTAL AMBIENT TEST ASSETS

Direktori ini berisi aset audio instrumental untuk pengujian background music (BGM) pada engine `app-tutorial-video`.

## Status Lisensi & Keaslian (Provenance)

| File | Tipe Sumber | Status Lisensi | Deskripsi |
|:---|:---:|:---:|:---|
| `ambient-test-v1.wav` | **Synthetic (Python/NumPy)** | **CC-0 / Dedicated Public Domain Test Fixture** | Dihasilkan secara deterministik oleh `scripts/generate_ambient_asset.py` (seed=42). Bebas royalti, tanpa hak cipta pihak ketiga, bukan musik komersial. |

### Karakteristik Akustik `ambient-test-v1.wav`:
- Format: WAV PCM 16-bit stereo, 48,000 Hz
- Durasi: 45.00 detik
- Progresi Harmonis: Cmaj9 - Fmaj7 - Am9 - Gsus4
- Karakteristik: Warm low-pass filtered pad, tanpa perkusi/drum agresif, attenuasi pada rentang frekuensi vokal 1.0 kHz – 4.0 kHz.
- Peak: -3.0 dBFS
- RMS: -15.1 dBFS

### Penggantian Aset Produksi:
Untuk video tutorial publik, pengguna dipersilakan mengganti atau mengarahkan path konfigurasi `music.file` ke file audio instrumental berlisensi yang dimiliki (misal MP3 atau WAV). Engine akan membaca file secara aman dan menghitung hash SHA-256 untuk dicatat di `provenance-manifest.json`.
