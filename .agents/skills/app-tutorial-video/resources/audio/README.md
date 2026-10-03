# SPESIFIKASI DAN PANDUAN ASET AUDIO INTERAKTIF (PATCH V3.5.1)

Direktori ini berisi aset audio lokal yang digunakan oleh engine `app-tutorial-video` untuk menghasilkan efek umpan balik interaktif (SFX) yang sinkron dan nyaman pada video tutorial.

---

## 1. Kebijakan Keterbukaan Sumber Suara (Audio Authenticity Policy)

Sesuai standar kejujuran teknis V3.5.1, engine **TIDAK PERNAH** mengklaim bahwa aset bawaan adalah rekaman mikrofon fisik nyata jika file tersebut dihasilkan melalui pemodelan fisika/sintesis.

### Status Metadata Aset Bawaan (`metadata.json`):

| File Audio | `source_type` | Karakteristik Akustik | Status Rekaman Fisik |
|:---|:---:|:---|:---:|
| `mouse-click.wav` | **`synthetic`** | Pemodelan transien saklar mekanik mikro (*dual-snap spring*) | ❌ Bukan rekaman fisik |
| `tap.wav` | **`synthetic`** | Pemodelan hentakan frekuensi rendah layar sentuh kapasitif (*haptic thud*) | ❌ Bukan rekaman fisik |
| `click.wav` | **`synthetic`** | Suara digital klasik UI pop V3.4 | ❌ Bukan rekaman fisik |
| `typing.wav` | **`synthetic`** | Sampel ketukan tuts keyboard membran | ❌ Bukan rekaman fisik |

---

## 2. Kebijakan & Opsi `click_source`

Engine mendukung konfigurasi sumber klik:
- **`recorded_mouse`** (Default): Menggunakan rekaman mikrofon mouse fisik nyata jika disediakan oleh pengguna. Jika pengguna belum menyediakan rekaman fisik nyata (`source_type: synthetic`), engine otomatis melakukan **fallback transparan**:
  ```
  [WARN] REAL MOUSE AUDIO NOT AVAILABLE - USING SYNTHETIC FALLBACK (source_type=synthetic)
  ```
  dan mencatat metadata provenance secara jujur tanpa manipulasi klaim.
- **`synthetic_ui`**: Menggunakan suara digital sintetis UI pop (`click.wav`).
- **`off`**: Menonaktifkan seluruh efek klik (tetap mempertahankan narasi 100%).

---

## 3. Dukungan File Rekaman Pengguna (User-Provided Physical Recording)

Pengguna dipersilakan mengganti `mouse-click.wav` atau `tap.wav` dengan rekaman fisik studio miliknya sendiri. Engine **TIDAK AKAN** mengubah atau menimpa file pengguna tanpa izin.

### Panduan Teknis File Pengganti:
1. **Format Kontainer**: WAV (PCM Linier uncompressed, 16-bit atau 24-bit).
2. **Kanal**: Mono (disarankan) atau Stereo (otomatis di-downmix ke mono).
3. **Sampling Rate**: `44,100 Hz` (44.1 kHz) atau `48,000 Hz` (48 kHz).
4. **Peak Level**: Berada di rentang `-6.0 dBFS` hingga `-3.0 dBFS` (tidak boleh clipping pada 0 dBFS).
5. **Durasi Efektif**:
   - Klik mouse fisik: `40 ms – 90 ms`.
   - Ketukan layar ponsel: `30 ms – 60 ms`.
   - Tombol keyboard: `50 ms – 120 ms`.
6. **Noise Floor**: Bebas desis latar (*clean silence noise gate*).

### Mengaktifkan Flag `recorded`:
Setelah menyalin rekaman fisik Anda ke `resources/audio/mouse-click.wav`, buka `resources/audio/metadata.json` dan ubah:
```json
"mouse-click.wav": {
  "source_type": "recorded",
  "is_physical_recording": true,
  "user_customized": true
}
```
Engine akan segera mendeteksi sumber sebagai rekaman fisik asli tanpa menampilkan pesan fallback warning.

---

## 4. Standar Keseimbangan Volume (Audio Mixing Standards)

Pencampuran multi-track engine V3.5.1 mematuhi standar rasio volume relatif terhadap suara narator:
- **Narration (Voice-Over)**: **`100%`** (Master priority, selalu dominan dan jernih).
- **Typing SFX (Keyboard)**: **`5% – 10%`** (Target default: `0.08`, halus di latar belakang, berhenti tepat saat input selesai).
- **Click / Tap SFX**: **`10% – 20%`** (Target default: `0.16` – `0.18`, tidak tajam/menusuk telinga).
- **Background Music**: **`0% – 5%`** (Hanya jika diaktifkan secara eksplisit pada plan).
