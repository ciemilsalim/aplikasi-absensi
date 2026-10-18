#!/usr/bin/env python
"""
evaluate_ardi_lab.py — Ardi Expression Lab Harness (V4.5 Humanized Narration)

Evaluates id-ID-ArdiNeural across 4 distinct pedagogical instructor profiles:
  Profile A: Warm Instructor (-2% rate, +0Hz, gentle cadence, warm pauses)
  Profile B: Conversational Instructor (+0% rate, +0Hz, natural transitions & direct address)
  Profile C: Expressive Instructor (-1% rate, +1Hz, dynamic emphasis & expressive pauses)
  Profile D: Natural Baseline (+0% rate, +0Hz, formal unsegmented reference)
  Reference: Gadis Reference (-2% rate, +0Hz, conversational comparison)

Evaluates 5 standardized instructional scenarios:
  1. Sapaan dan pembukaan tutorial
  2. Penjelasan langkah
  3. Instruksi tindakan pada antarmuka
  4. Penekanan terhadap informasi penting
  5. Transisi menuju langkah berikutnya

Saves all artifacts in isolated directory: .tutorial-video/voice-evaluations/ardi-lab/
Maintains human_listening: "NOT_VERIFIED" until explicit human confirmation.
"""

import argparse
import asyncio
import hashlib
import json
import math
import os
import shutil
import subprocess
import sys
import wave
import numpy as np

try:
    import edge_tts
except ImportError:
    print("Error: edge-tts is not installed.", file=sys.stderr)
    sys.exit(1)

SR = 48000


def get_ffmpeg():
    ffmpeg = shutil.which("ffmpeg")
    if not ffmpeg:
        try:
            import imageio_ffmpeg
            ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
        except Exception:
            pass
    return ffmpeg or "ffmpeg"


def decode_pcm_bytes(mp3_bytes: bytes, ffmpeg_exe: str) -> np.ndarray:
    """Decodes MP3 bytes directly into 48kHz mono float32 numpy array."""
    cmd = [
        ffmpeg_exe, "-v", "error", "-i", "pipe:0",
        "-f", "s16le", "-ac", "1", "-ar", str(SR), "pipe:1"
    ]
    p = subprocess.Popen(cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    raw, err = p.communicate(input=mp3_bytes)
    if p.returncode != 0:
        raise RuntimeError(f"FFmpeg audio decode error: {err.decode('utf-8', errors='ignore')}")
    pcm_int16 = np.frombuffer(raw, dtype=np.int16)
    return pcm_int16.astype(np.float32) / 32768.0


def compute_audio_metrics(pcm: np.ndarray):
    dur_s = len(pcm) / float(SR)
    peak = float(np.max(np.abs(pcm))) if len(pcm) > 0 else 0.0
    rms = float(np.sqrt(np.mean(pcm ** 2))) if len(pcm) > 0 else 0.0
    peak_dbfs = round(20.0 * math.log10(peak), 2) if peak > 1e-9 else -100.0
    rms_dbfs = round(20.0 * math.log10(rms), 2) if rms > 1e-9 else -100.0
    return {
        "duration_s": round(dur_s, 3),
        "duration_ms": round(dur_s * 1000.0, 1),
        "sample_rate": SR,
        "peak_dbfs": peak_dbfs,
        "rms_dbfs": rms_dbfs,
        "clipping": peak >= 0.999
    }


# ── SCENARIO DEFINITIONS ──────────────────────────────────────────────────────
# 5 distinct instructional functions with Formal vs Conversational variations
SCENARIOS = [
    {
        "id": "scenario_01_sapaan",
        "title": "1. Sapaan & Pembukaan Tutorial",
        "function": "greeting_and_intro",
        "formal_text": "Selamat datang di panduan aplikasi SIASEK. Pada sesi ini, kita akan mempelajari cara melihat rekapitulasi kehadiran siswa kelas binaan.",
        "conversational_segments": [
            {"text": "Halo Bapak dan Ibu Guru Wali Kelas, selamat datang di panduan aplikasi SIASEK.", "pause_after_ms": 300},
            {"text": "Hari ini kita akan melihat rekap kehadiran siswa kelas binaan secara praktis dan menyeluruh.", "pause_after_ms": 0}
        ],
        "conversational_text": "Halo Bapak dan Ibu Guru Wali Kelas, selamat datang di panduan aplikasi SIASEK. Hari ini kita akan melihat rekap kehadiran siswa kelas binaan secara praktis dan menyeluruh."
    },
    {
        "id": "scenario_02_penjelasan_langkah",
        "title": "2. Penjelasan Langkah Antarmuka",
        "function": "step_explanation",
        "formal_text": "Pada halaman rekapitulasi, sistem menampilkan tabel riwayat kehadiran beserta kolom filter periode bulan.",
        "conversational_segments": [
            {"text": "Pada halaman rekapitulasi ini, kita dapat memantau data kehadiran seluruh siswa binaan.", "pause_after_ms": 280},
            {"text": "Di bagian atas, tersedia kolom filter untuk memilih periode bulan yang ingin kita periksa.", "pause_after_ms": 0}
        ],
        "conversational_text": "Pada halaman rekapitulasi ini, kita dapat memantau data kehadiran seluruh siswa binaan. Di bagian atas, tersedia kolom filter untuk memilih periode bulan yang ingin kita periksa."
    },
    {
        "id": "scenario_03_instruksi_tindakan",
        "title": "3. Instruksi Tindakan & Aksi Pengguna",
        "function": "action_instruction",
        "formal_text": "Pilih periode bulan yang diinginkan pada kolom dropdown, kemudian klik tombol Filter untuk memperbarui data kehadiran.",
        "conversational_segments": [
            {"text": "Silakan pilih periode bulan yang diinginkan pada kolom pilihan ini,", "pause_after_ms": 220},
            {"text": "lalu klik tombol Filter untuk memperbarui seluruh data kehadiran.", "pause_after_ms": 0}
        ],
        "conversational_text": "Silakan pilih periode bulan yang diinginkan pada kolom pilihan ini, lalu klik tombol Filter untuk memperbarui seluruh data kehadiran."
    },
    {
        "id": "scenario_04_penekanan_informasi",
        "title": "4. Penekanan Informasi Penting",
        "function": "key_information_emphasis",
        "formal_text": "Perhatikan empat indikator kartu pada bagian atas yaitu persentase kehadiran, hari efektif, serta akumulasi izin, sakit, dan alpa.",
        "conversational_segments": [
            {"text": "Perhatikan empat kartu indikator utama di bagian atas halaman.", "pause_after_ms": 300},
            {"text": "Melalui indikator ini, kita dapat memantau persentase kehadiran, jumlah hari efektif belajar, serta akumulasi izin, sakit, dan alpa secara akurat.", "pause_after_ms": 0}
        ],
        "conversational_text": "Perhatikan empat kartu indikator utama di bagian atas halaman. Melalui indikator ini, kita dapat memantau persentase kehadiran, jumlah hari efektif belajar, serta akumulasi izin, sakit, dan alpa secara akurat."
    },
    {
        "id": "scenario_05_transisi_langkah",
        "title": "5. Transisi Menuju Langkah Berikutnya",
        "function": "transition_to_next",
        "formal_text": "Setelah memeriksa data pada tabel matriks, pengguna dapat mencetak laporan atau mengekspor data ke dalam dokumen Excel.",
        "conversational_segments": [
            {"text": "Setelah memeriksa data pada tabel matriks kehadiran,", "pause_after_ms": 250},
            {"text": "kita dapat langsung mencetak laporan ke format A4 atau Folio, maupun mengekspornya ke dokumen Excel.", "pause_after_ms": 0}
        ],
        "conversational_text": "Setelah memeriksa data pada tabel matriks kehadiran, kita dapat langsung mencetak laporan ke format A4 atau Folio, maupun mengekspornya ke dokumen Excel."
    }
]

# ── PROFILES TO EVALUATE ──────────────────────────────────────────────────────
PROFILES = [
    {
        "profile_id": "warm_instructor",
        "profile_name": "Profile A: Warm Instructor",
        "voice_id": "id-ID-ArdiNeural",
        "gender": "Male",
        "rate": "-2%",
        "pitch": "+0Hz",
        "use_segments": True,
        "description": "Hangat, tenang, dan ramah. Tempo percakapan alami dengan penekanan lembut pada jeda."
    },
    {
        "profile_id": "conversational_instructor",
        "profile_name": "Profile B: Conversational Instructor",
        "voice_id": "id-ID-ArdiNeural",
        "gender": "Male",
        "rate": "+0%",
        "pitch": "+0Hz",
        "use_segments": True,
        "description": "Menjelaskan langsung secara lugas dan terarah tanpa filler informal."
    },
    {
        "profile_id": "expressive_instructor",
        "profile_name": "Profile C: Expressive Instructor",
        "voice_id": "id-ID-ArdiNeural",
        "gender": "Male",
        "rate": "-1%",
        "pitch": "+1Hz",
        "use_segments": True,
        "description": "Dinamika intonasi sedikit lebih cerah (+1Hz) dengan penekanan terarah tanpa kata pengisi."
    },
    {
        "profile_id": "natural_baseline",
        "profile_name": "Profile D: Natural Baseline",
        "voice_id": "id-ID-ArdiNeural",
        "gender": "Male",
        "rate": "+0%",
        "pitch": "+0Hz",
        "use_segments": False,
        "description": "Teks dan pengaturan formal baseline V4.4 (Preferensi Pengguna: lugas, berwibawa, jernih, dan tanpa filler 'Nah')."
    },
    {
        "profile_id": "gadis_reference",
        "profile_name": "Reference: Gadis Reference",
        "voice_id": "id-ID-GadisNeural",
        "gender": "Female",
        "rate": "-2%",
        "pitch": "+0Hz",
        "use_segments": True,
        "description": "Pembanding referensi wanita dengan naskah yang sama persis."
    }
]


async def synthesize_segment(text: str, voice: str, rate: str, pitch: str):
    """Synthesize one segment of speech returning word boundaries and MP3 bytearray."""
    comm = edge_tts.Communicate(text, voice, rate=rate, pitch=pitch, boundary="WordBoundary")
    audio_data = bytearray()
    words = []
    async for chunk in comm.stream():
        if chunk["type"] == "audio":
            audio_data.extend(chunk["data"])
        elif chunk["type"] == "WordBoundary":
            words.append({
                "word": chunk["text"],
                "start_ms": round(chunk["offset"] / 10000.0, 1),
                "duration_ms": round(chunk["duration"] / 10000.0, 1)
            })
    if not audio_data:
        raise RuntimeError(f"Empty audio data received from edge-tts for '{text}'")
    return words, audio_data


async def synthesize_scenario(sc: dict, prof: dict, ffmpeg_exe: str):
    """Synthesizes one scenario under a specific profile."""
    voice = prof["voice_id"]
    rate = prof["rate"]
    pitch = prof["pitch"]
    use_segments = prof["use_segments"]

    if use_segments:
        segments = sc["conversational_segments"]
        scene_pcm_chunks = []
        words = []
        current_offset_ms = 0.0

        for seg_idx, seg in enumerate(segments):
            seg_text = seg["text"]
            pause_ms = seg.get("pause_after_ms", 250)
            seg_words, seg_mp3 = await synthesize_segment(seg_text, voice, rate, pitch)
            seg_pcm = decode_pcm_bytes(bytes(seg_mp3), ffmpeg_exe)

            # Micro-fade (5ms) to prevent clicks
            fade_len = min(int(SR * 0.005), len(seg_pcm) // 4)
            if fade_len > 0:
                fade_in = np.linspace(0.0, 1.0, fade_len, dtype=np.float32)
                fade_out = np.linspace(1.0, 0.0, fade_len, dtype=np.float32)
                seg_pcm[:fade_len] *= fade_in
                seg_pcm[-fade_len:] *= fade_out

            scene_pcm_chunks.append(seg_pcm)

            # Adjust word timestamps to cumulative time
            for w in seg_words:
                words.append({
                    "word": w["word"],
                    "start_ms": round(w["start_ms"] + current_offset_ms, 1),
                    "duration_ms": w["duration_ms"]
                })

            seg_dur_ms = len(seg_pcm) / SR * 1000.0
            if seg_idx < len(segments) - 1 and pause_ms > 0:
                pause_samples = int(round(pause_ms / 1000.0 * SR))
                scene_pcm_chunks.append(np.zeros(pause_samples, dtype=np.float32))
                current_offset_ms += seg_dur_ms + pause_ms
            else:
                current_offset_ms += seg_dur_ms

        combined_pcm = np.concatenate(scene_pcm_chunks) if scene_pcm_chunks else np.zeros(0, dtype=np.float32)
        script_text = sc["conversational_text"]
    else:
        # Formal baseline single synthesis
        script_text = sc["formal_text"]
        words_raw, mp3_raw = await synthesize_segment(script_text, voice, rate, pitch)
        combined_pcm = decode_pcm_bytes(bytes(mp3_raw), ffmpeg_exe)
        words = words_raw

    # Convert to 16-bit PCM for WAV
    pcm_int16 = (np.clip(combined_pcm, -1.0, 1.0) * 32767).astype(np.int16)
    audio_metrics = compute_audio_metrics(combined_pcm)

    return {
        "text": script_text,
        "pcm_int16": pcm_int16,
        "words": words,
        "metrics": audio_metrics
    }


def write_wav(pcm_int16: np.ndarray, wav_path: str):
    with wave.open(wav_path, "wb") as wf:
        wf.setnchannels(1)
        wf.setsampwidth(2)
        wf.setframerate(SR)
        wf.writeframes(pcm_int16.tobytes())


def encode_mp3(wav_path: str, mp3_path: str, ffmpeg_exe: str):
    cmd = [
        ffmpeg_exe, "-y", "-v", "error", "-i", wav_path,
        "-codec:a", "libmp3lame", "-b:a", "128k", mp3_path
    ]
    subprocess.run(cmd, check=True)


async def run_lab(output_dir: str):
    os.makedirs(output_dir, exist_ok=True)
    ffmpeg = get_ffmpeg()

    print("\n" + "=" * 78)
    print("V4.5 ARDI EXPRESSION LAB — EVALUASI EKSPRESI & DINAMIKA PERCAKAPAN")
    print(f"Target Output: {output_dir}")
    print("=" * 78 + "\n")

    catalog = []
    failures = []

    for prof in PROFILES:
        pid = prof["profile_id"]
        pname = prof["profile_name"]
        pdir = os.path.join(output_dir, pid)
        os.makedirs(pdir, exist_ok=True)

        print(f"\n[PROFILE] {pname} ({prof['voice_id']}, rate={prof['rate']}, pitch={prof['pitch']})")
        print(f"          Description: {prof['description']}")

        for sc in SCENARIOS:
            sid = sc["id"]
            stitle = sc["title"]
            rate_tag = prof["rate"].replace("+", "p").replace("-", "m").replace("%", "pct")
            pitch_tag = prof["pitch"].replace("+", "p").replace("-", "m").replace("Hz", "hz")
            base_filename = f"{pid}_{sid}_{rate_tag}_{pitch_tag}"

            wav_file = os.path.join(pdir, f"{base_filename}.wav")
            mp3_file = os.path.join(pdir, f"{base_filename}.mp3")

            try:
                result = await synthesize_scenario(sc, prof, ffmpeg)
                pcm_data = result["pcm_int16"]
                metrics = result["metrics"]
                words = result["words"]

                write_wav(pcm_data, wav_file)
                encode_mp3(wav_file, mp3_file, ffmpeg)

                entry = {
                    "scenario_id": sid,
                    "scenario_title": stitle,
                    "instructional_function": sc["function"],
                    "profile_id": pid,
                    "profile_name": pname,
                    "voice_id": prof["voice_id"],
                    "gender": prof["gender"],
                    "rate": prof["rate"],
                    "pitch": prof["pitch"],
                    "use_segments": prof["use_segments"],
                    "formal_source_text": sc["formal_text"],
                    "evaluated_text": result["text"],
                    "wav_file": os.path.relpath(wav_file, output_dir),
                    "mp3_file": os.path.relpath(mp3_file, output_dir),
                    "duration_s": metrics["duration_s"],
                    "duration_ms": metrics["duration_ms"],
                    "peak_dbfs": metrics["peak_dbfs"],
                    "rms_dbfs": metrics["rms_dbfs"],
                    "clipping": metrics["clipping"],
                    "word_count": len(result["text"].split()),
                    "word_boundaries_count": len(words),
                    "words_data": words,
                    "wav_sha256": hashlib.sha256(pcm_data.tobytes()).hexdigest(),
                    "synthesis_status": "SUCCESS"
                }
                catalog.append(entry)
                print(f"  [OK] {sid}: {metrics['duration_s']:.2f}s | RMS: {metrics['rms_dbfs']} dBFS | Peak: {metrics['peak_dbfs']} dBFS")
            except Exception as ex:
                fail_entry = {
                    "scenario_id": sid,
                    "profile_id": pid,
                    "voice_id": prof["voice_id"],
                    "error": str(ex)
                }
                failures.append(fail_entry)
                print(f"  [FAIL] {sid} under {pid}: ERROR: {ex}", file=sys.stderr)

    report_path = os.path.join(output_dir, "lab_report.json")
    report_data = {
        "engine": "edge-tts",
        "sample_rate": SR,
        "total_profiles_tested": len(PROFILES),
        "total_scenarios_tested": len(SCENARIOS),
        "total_samples_planned": len(PROFILES) * len(SCENARIOS),
        "total_samples_generated": len(catalog),
        "failures_count": len(failures),
        "failures": failures,
        "human_listening_status": "NOT_VERIFIED",
        "candidate_primary_voice": "id-ID-ArdiNeural",
        "decision_policy": "NO_AUTOMATIC_WINNER_AWAITING_HUMAN_LISTENING",
        "samples": catalog
    }

    with open(report_path, "w", encoding="utf-8") as f:
        json.dump(report_data, f, indent=2, ensure_ascii=False)

    print("\n" + "=" * 78)
    print(f"LAB COMPLETE: {len(catalog)} samples generated, {len(failures)} failures.")
    print(f"Lab Report saved to: {report_path}")
    print(f"Human Listening Status: {report_data['human_listening_status']}")
    print("=" * 78 + "\n")
    return report_path, report_data


def main():
    ap = argparse.ArgumentParser(description="Ardi Expression Lab Voice Evaluation")
    ap.add_argument("--output-dir", default=".tutorial-video/voice-evaluations/ardi-lab",
                    help="Directory to store expression lab artifacts")
    args = ap.parse_args()

    out_dir = os.path.abspath(args.output_dir)
    asyncio.run(run_lab(out_dir))


if __name__ == "__main__":
    main()
