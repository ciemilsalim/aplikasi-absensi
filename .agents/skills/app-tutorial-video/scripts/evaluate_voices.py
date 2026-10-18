#!/usr/bin/env python
"""
evaluate_voices.py — Voice Evaluation Harness for Indonesian Neural TTS (V4.5 Humanized Narration)

Generates identical audio samples for:
- id-ID-ArdiNeural (Male)
- id-ID-GadisNeural (Female)

Evaluates 5 standardized Indonesian script scenarios across baseline (+0%) and rate variations.
Saves all artifacts isolated in .tutorial-video/voice-evaluations/.
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


def get_ffmpeg():
    ffmpeg = shutil.which("ffmpeg")
    if not ffmpeg:
        try:
            import imageio_ffmpeg
            ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
        except Exception:
            pass
    return ffmpeg or "ffmpeg"


SAMPLE_SCRIPTS = [
    {
        "id": "sample_01_sapaan",
        "title": "1. Sapaan Pembuka Hangat",
        "category": "greeting",
        "text": "Halo Bapak dan Ibu Guru Wali Kelas, selamat datang di panduan aplikasi SIASEK. Hari ini kita akan melihat rekap kehadiran siswa kelas binaan secara praktis dan menyeluruh."
    },
    {
        "id": "sample_02_filter_periode",
        "title": "2. Instruksi Filter Periode",
        "category": "instruction",
        "text": "Untuk memeriksa kehadiran pada bulan tertentu, silakan pilih periode pada kolom bulan, lalu tekan tombol Filter untuk memperbarui data."
    },
    {
        "id": "sample_03_ringkasan_kpi",
        "title": "3. Penjelasan Ringkasan KPI Kehadiran",
        "category": "explanation",
        "text": "Di bagian atas, kita dapat memantau empat indikator utama: persentase kehadiran kelas, jumlah hari efektif belajar, serta akumulasi izin, sakit, dan alpa."
    },
    {
        "id": "sample_04_istilah_teknis",
        "title": "4. Istilah Teknis & Fitur SIASEK",
        "category": "technical",
        "text": "Aplikasi ini menyediakan dua mode tampilan: Tabel Matriks harian serta kartu Ringkasan Siswa, lengkap dengan opsi cetak format A4, Folio, dan ekspor dokumen Excel."
    },
    {
        "id": "sample_05_penutup",
        "title": "5. Penutup Tutorial Natural",
        "category": "closing",
        "text": "Dengan data rekapitulasi yang selalu diperbarui, pemantauan kedisiplinan siswa menjadi lebih mudah, transparan, dan teratur. Selamat bertugas dan sukses selalu."
    }
]

VOICES_TO_EVALUATE = [
    {
        "short_name": "id-ID-ArdiNeural",
        "gender": "Male",
        "description": "Microsoft Ardi Online (Natural) - Indonesian (Indonesia)",
        "rates": ["+0%", "-2%"]
    },
    {
        "short_name": "id-ID-GadisNeural",
        "gender": "Female",
        "description": "Microsoft Gadis Online (Natural) - Indonesian (Indonesia)",
        "rates": ["+0%", "-2%"]
    }
]


async def synthesize_sample(text: str, voice: str, rate: str, pitch: str, out_mp3: str):
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
        raise RuntimeError(f"No audio received from TTS service for voice {voice}")

    with open(out_mp3, "wb") as f:
        f.write(audio_data)

    return words, audio_data


def convert_mp3_to_wav(ffmpeg_exe: str, mp3_path: str, wav_path: str, target_sr: int = 48000):
    cmd = [
        ffmpeg_exe, "-y", "-v", "error", "-i", mp3_path,
        "-f", "wav", "-ac", "1", "-ar", str(target_sr), wav_path
    ]
    subprocess.run(cmd, check=True)


def analyze_audio_pcm(wav_path: str):
    with wave.open(wav_path, "rb") as wf:
        sr = wf.getframerate()
        n_frames = wf.getnframes()
        raw = wf.readframes(n_frames)
        samples = np.frombuffer(raw, dtype=np.int16).astype(np.float32) / 32768.0

    dur_s = n_frames / max(1, sr)
    peak = float(np.max(np.abs(samples))) if len(samples) else 0.0
    rms = float(np.sqrt(np.mean(samples ** 2))) if len(samples) else 0.0
    peak_dbfs = round(20.0 * math.log10(peak), 2) if peak > 1e-9 else -100.0
    rms_dbfs = round(20.0 * math.log10(rms), 2) if rms > 1e-9 else -100.0

    return {
        "duration_s": round(dur_s, 3),
        "duration_ms": round(dur_s * 1000.0, 1),
        "sample_rate": sr,
        "peak_dbfs": peak_dbfs,
        "rms_dbfs": rms_dbfs,
        "clipping": peak >= 0.999
    }


async def run_evaluation(output_dir: str):
    os.makedirs(output_dir, exist_ok=True)
    ffmpeg = get_ffmpeg()

    print("\n" + "=" * 76)
    print("V4.5 HUMANIZED NARRATION — VOICE EVALUATION HARNESS")
    print("Testing voices: id-ID-ArdiNeural (Male) vs id-ID-GadisNeural (Female)")
    print(f"Output directory: {output_dir}")
    print("=" * 76 + "\n")

    catalog = []
    failures = []

    for v_info in VOICES_TO_EVALUATE:
        voice_id = v_info["short_name"]
        gender = v_info["gender"]
        voice_clean_id = voice_id.replace("id-ID-", "").replace("Neural", "").lower()
        v_dir = os.path.join(output_dir, voice_clean_id)
        os.makedirs(v_dir, exist_ok=True)

        print(f"--- Evaluating Voice: {voice_id} ({gender}) ---")

        for rate in v_info["rates"]:
            rate_clean = rate.replace("+", "p").replace("-", "m").replace("%", "pct")
            sub_dir = os.path.join(v_dir, f"rate_{rate_clean}")
            os.makedirs(sub_dir, exist_ok=True)

            for item in SAMPLE_SCRIPTS:
                sid = item["id"]
                text = item["text"]
                mp3_file = os.path.join(sub_dir, f"{sid}.mp3")
                wav_file = os.path.join(sub_dir, f"{sid}.wav")

                try:
                    words, raw_bytes = await synthesize_sample(
                        text=text,
                        voice=voice_id,
                        rate=rate,
                        pitch="+0Hz",
                        out_mp3=mp3_file
                    )
                    convert_mp3_to_wav(ffmpeg, mp3_file, wav_file, target_sr=48000)
                    audio_stats = analyze_audio_pcm(wav_file)

                    entry = {
                        "script_id": sid,
                        "script_title": item["title"],
                        "category": item["category"],
                        "text": text,
                        "voice_id": voice_id,
                        "gender": gender,
                        "rate": rate,
                        "pitch": "+0Hz",
                        "mp3_path": os.path.relpath(mp3_file, output_dir),
                        "wav_path": os.path.relpath(wav_file, output_dir),
                        "mp3_sha256": hashlib.sha256(raw_bytes).hexdigest(),
                        "word_count": len(text.split()),
                        "word_boundaries_count": len(words),
                        "duration_s": audio_stats["duration_s"],
                        "duration_ms": audio_stats["duration_ms"],
                        "peak_dbfs": audio_stats["peak_dbfs"],
                        "rms_dbfs": audio_stats["rms_dbfs"],
                        "words_data": words
                    }
                    catalog.append(entry)
                    print(f"  [OK] {voice_clean_id} | rate {rate} | {sid} -> {audio_stats['duration_s']}s (RMS: {audio_stats['rms_dbfs']} dBFS)")
                except Exception as ex:
                    failures.append({
                        "voice_id": voice_id,
                        "rate": rate,
                        "script_id": sid,
                        "error": str(ex)
                    })
                    print(f"  [FAIL] {voice_clean_id} | rate {rate} | {sid} -> ERROR: {ex}", file=sys.stderr)

    report_path = os.path.join(output_dir, "evaluation_report.json")
    summary = {
        "engine": "edge-tts",
        "sample_rate": 48000,
        "total_samples_planned": len(VOICES_TO_EVALUATE) * sum(len(v["rates"]) for v in VOICES_TO_EVALUATE) * len(SAMPLE_SCRIPTS),
        "total_samples_generated": len(catalog),
        "failures_count": len(failures),
        "failures": failures,
        "human_listening_status": "NOT_VERIFIED",
        "samples": catalog
    }

    with open(report_path, "w", encoding="utf-8") as f:
        json.dump(summary, f, indent=2, ensure_ascii=False)

    print("\n" + "=" * 76)
    print(f"Evaluation complete! {len(catalog)} samples generated.")
    print(f"Report saved to: {report_path}")
    print(f"HUMAN_LISTENING status: {summary['human_listening_status']}")
    print("=" * 76 + "\n")
    return summary


def main():
    parser = argparse.ArgumentParser(description="Voice evaluation harness for V4.5 Humanized Narration")
    parser.add_argument(
        "--output-dir",
        default=".tutorial-video/voice-evaluations",
        help="Directory to save evaluation samples and report"
    )
    args = parser.parse_args()
    asyncio.run(run_evaluation(args.output_dir))


if __name__ == "__main__":
    main()
