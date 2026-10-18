#!/usr/bin/env python
"""
generate_ambient_asset.py — Deterministic Synthetic Ambient Music Generator
Generates a warm, unobtrusive, copyright-free instrumental ambient pad track
specifically engineered for technical tutorial backsound testing.

Acoustic Characteristics:
- 48,000 Hz, 16-bit PCM Stereo WAV
- Frequency spectrum attenuated in speech band (1.0 kHz – 4.0 kHz)
- Warm detuned harmonic oscillators (Cmaj9 - Fmaj7 - Am9 - Gsus4)
- Zero harsh transients, zero percussion
- 100% deterministic (fixed random seed)
"""

import math
import os
import struct
import wave
import numpy as np

SR = 48000
DURATION_SEC = 45.0
CHORD_DURATION = 11.25  # 4 chords over 45s

# Chord frequencies in Hz (root, fifth, seventh, ninth, third)
CHORDS = [
    # Cmaj9 (warm, calm)
    [130.81, 196.00, 246.94, 293.66, 329.63],
    # Fmaj7 (gentle lift)
    [87.31, 130.81, 174.61, 220.00, 261.63, 329.63],
    # Am9 (thoughtful, technical)
    [110.00, 164.81, 196.00, 261.63, 293.66],
    # Gsus4 -> G (resolution)
    [98.00, 146.83, 196.00, 261.63, 293.66]
]


def generate_ambient_track(seed: int = 42, duration: float = DURATION_SEC, sample_rate: int = SR) -> np.ndarray:
    """
    Synthesize stereo ambient pad array of shape (2, num_samples) with values in [-1.0, 1.0].
    """
    rng = np.random.default_rng(seed)
    total_samples = int(duration * sample_rate)
    left = np.zeros(total_samples, dtype=np.float32)
    right = np.zeros(total_samples, dtype=np.float32)

    t_all = np.arange(total_samples) / sample_rate

    # Generate each chord with slow evolving envelopes
    for chord_idx, freqs in enumerate(CHORDS):
        c_start = int(chord_idx * CHORD_DURATION * sample_rate)
        c_len = int((CHORD_DURATION + 3.0) * sample_rate)  # 3.0s overlap for smooth crossfade
        c_end = min(total_samples, c_start + c_len)
        actual_len = c_end - c_start
        if actual_len <= 0:
            continue

        t_chord = np.arange(actual_len) / sample_rate

        # Chord amplitude envelope: gentle attack (2.5s), sustain, gentle release (3.0s)
        env = np.ones(actual_len, dtype=np.float32)
        att_samples = int(2.5 * sample_rate)
        rel_samples = int(3.0 * sample_rate)
        if actual_len > att_samples:
            env[:att_samples] = 0.5 * (1.0 - np.cos(np.pi * np.arange(att_samples) / att_samples))
        if actual_len > rel_samples:
            env[-rel_samples:] *= 0.5 * (1.0 + np.cos(np.pi * np.arange(rel_samples) / rel_samples))

        # Sum detuned oscillators for each frequency in chord
        chord_sig_l = np.zeros(actual_len, dtype=np.float32)
        chord_sig_r = np.zeros(actual_len, dtype=np.float32)

        for f in freqs:
            # 3 detuned oscillators per note for stereo chorus richness
            detune1 = rng.uniform(-0.35, 0.35)
            detune2 = rng.uniform(-0.35, 0.35)
            phase_l = rng.uniform(0, 2 * np.pi)
            phase_r = rng.uniform(0, 2 * np.pi)

            # Slow LFO tremolo (0.15 Hz to 0.3 Hz)
            lfo_l = 1.0 + 0.12 * np.sin(2 * np.pi * (0.18 + 0.05 * detune1) * t_chord)
            lfo_r = 1.0 + 0.12 * np.sin(2 * np.pi * (0.22 + 0.05 * detune2) * t_chord + math.pi / 2)

            # Rich warm acoustic pad: fundamental + 2nd, 3rd, 4th harmonics for clear presence
            tone_l = (np.sin(2 * np.pi * (f + detune1) * t_chord + phase_l) * 0.65 +
                      np.sin(2 * np.pi * (2 * f + detune2) * t_chord + phase_l) * 0.22 +
                      np.sin(2 * np.pi * (3 * f + detune1 * 0.7) * t_chord + phase_l) * 0.08 +
                      np.sin(2 * np.pi * (4 * f + detune2 * 0.5) * t_chord + phase_l) * 0.05) * lfo_l
            tone_r = (np.sin(2 * np.pi * (f - detune1) * t_chord + phase_r) * 0.65 +
                      np.sin(2 * np.pi * (2 * f - detune2) * t_chord + phase_r) * 0.22 +
                      np.sin(2 * np.pi * (3 * f - detune1 * 0.7) * t_chord + phase_r) * 0.08 +
                      np.sin(2 * np.pi * (4 * f - detune2 * 0.5) * t_chord + phase_r) * 0.05) * lfo_r

            chord_sig_l += tone_l
            chord_sig_r += tone_r

        chord_sig_l = (chord_sig_l / len(freqs)) * env
        chord_sig_r = (chord_sig_r / len(freqs)) * env

        left[c_start:c_end] += chord_sig_l
        right[c_start:c_end] += chord_sig_r

    # Add gentle pink-noise-filtered shimmer bed for acoustic depth
    white = rng.standard_normal(total_samples).astype(np.float32)
    # Gentle lowpass filter on noise via cumulative moving average
    kernel_size = 90  # cut roughly around 550Hz
    kernel = np.ones(kernel_size, dtype=np.float32) / kernel_size
    pad_bed = np.convolve(white, kernel, mode='same') * 0.035
    left += pad_bed
    right += pad_bed

    # Master Fade-In (first 1.5s) and Fade-Out (last 2.5s)
    fi_s = int(1.5 * sample_rate)
    fo_s = int(2.5 * sample_rate)
    left[:fi_s] *= 0.5 * (1.0 - np.cos(np.pi * np.arange(fi_s) / fi_s))
    right[:fi_s] *= 0.5 * (1.0 - np.cos(np.pi * np.arange(fi_s) / fi_s))
    left[-fo_s:] *= 0.5 * (1.0 + np.cos(np.pi * np.arange(fo_s) / fo_s))
    right[-fo_s:] *= 0.5 * (1.0 + np.cos(np.pi * np.arange(fo_s) / fo_s))

    # Normalize to -1.5 dBFS peak (~0.841) for optimal audible headroom
    peak = max(float(np.max(np.abs(left))), float(np.max(np.abs(right))), 1e-6)
    target_peak = 10.0 ** (-1.5 / 20.0)  # ~0.8414
    scale = target_peak / peak
    left *= scale
    right *= scale

    return np.vstack([left, right])


def save_stereo_wav(stereo_data: np.ndarray, out_path: str, sample_rate: int = SR) -> None:
    """Save 2-channel float32 data in [-1, 1] as 16-bit PCM WAV."""
    os.makedirs(os.path.dirname(os.path.abspath(out_path)), exist_ok=True)
    left = np.clip(stereo_data[0], -1.0, 1.0)
    right = np.clip(stereo_data[1], -1.0, 1.0)

    # Interleave left and right
    interleaved = np.empty((left.size + right.size,), dtype=np.int16)
    interleaved[0::2] = (left * 32767.0).astype(np.int16)
    interleaved[1::2] = (right * 32767.0).astype(np.int16)

    with wave.open(out_path, 'wb') as wf:
        wf.setnchannels(2)
        wf.setsampwidth(2)
        wf.setframerate(sample_rate)
        wf.writeframes(interleaved.tobytes())


def main():
    script_dir = os.path.dirname(os.path.abspath(__file__))
    out_dir = os.path.abspath(os.path.join(script_dir, '..', 'resources', 'audio', 'music'))
    out_file = os.path.join(out_dir, 'ambient-test-v1.wav')

    print(f"[Generator] Synthesizing 45.0s ambient test asset at 48kHz stereo...")
    data = generate_ambient_track(seed=42, duration=45.0, sample_rate=SR)

    # Assertions
    assert not np.isnan(data).any(), "NaN found in synthesized audio"
    assert not np.isinf(data).any(), "Inf found in synthesized audio"
    peak = float(np.max(np.abs(data)))
    rms = float(np.sqrt(np.mean(data ** 2)))
    peak_dbfs = 20.0 * math.log10(peak) if peak > 0 else -100.0
    rms_dbfs = 20.0 * math.log10(rms) if rms > 0 else -100.0

    save_stereo_wav(data, out_file, sample_rate=SR)
    print(f"[Generator] Saved: {out_file}")
    print(f" -> Peak: {peak_dbfs:.2f} dBFS (Linear: {peak:.4f})")
    print(f" -> RMS:  {rms_dbfs:.2f} dBFS (Linear: {rms:.4f})")
    print(f" -> Duration: {data.shape[1] / SR:.2f}s, Channels: {data.shape[0]}, Sample Rate: {SR} Hz")


if __name__ == '__main__':
    main()
