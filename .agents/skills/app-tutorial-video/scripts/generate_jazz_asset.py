#!/usr/bin/env python
"""
generate_jazz_asset.py — High-Fidelity Synthetic Smooth Jazz & Soul R&B Generator
Inspired by: "Morning Smooth Jazz & Soul R&B / Positive Jazz for Focus & Relaxation"
(https://www.youtube.com/watch?v=du7Vjfxv4zU)

Pure Soul Jazz Electric Piano & Pocket Groove Trio (Zero abrasive synth saxophone):
- Genre: Morning Smooth Jazz & Soul R&B / Neo-Soul Lounge Groove
- Tempo: 80 BPM (Groove: Relaxed Soul Pocket with subtle 16th-note swing)
- Harmony: 24-Bar extended smooth jazz form in F Major:
    Bars 1-4:   Gm9  -> C13   -> Fmaj9 -> Dm9 (Classic ii - V - I - vi soul progression)
    Bars 5-8:   Gm9  -> C13   -> Am7   -> D7(b9) (Turnaround variation)
    Bars 9-12:  Bbmaj9 -> Bbm9 -> Am9   -> D7(#9) (Subdominant lift & resolution)
    Bars 13-16: Gm9  -> C7sus -> Fmaj9 -> Dm9
    Bars 17-20: Bbmaj9 -> Am9 -> Gm9   -> C13 (Expressive soul groove section)
    Bars 21-24: Fmaj9 -> Dm9  -> Gm9   -> C13 (Seamless turnaround leading into Bar 1)
- Instrumentation:
    1. Soul Pocket Drums: Warm sub-punch kick (beat 1 & syncopated 2.5 bounce),
       crisp wood rimshot/snare on 2 & 4 with plate reverb, 16th swung hi-hats & organic shaker.
    2. Deep Soul Electric Bass: Melodic, round low-end pocket bass locked with the kick.
    3. Fender Rhodes Electric Piano: Lush 9th/11th/13th chords with bell-like tine attacks,
       stereo tremolo, and gentle upper-octave tine arpeggio fills.
- Output: 48,000 Hz, 16-bit Stereo PCM WAV, Seamless Musically-Aligned Loop (72.0s = exactly 3,456,000 samples).
"""

import math
import os
import wave
from typing import Tuple, List
import numpy as np

SR = 48000
BPM = 80.0
BEAT_DUR = 60.0 / BPM  # 0.75 s
BAR_DUR = BEAT_DUR * 4.0  # 3.0 s
NUM_BARS = 24  # 72.0 s (24 bars @ 80 BPM = exactly 3,456,000 samples)
TOTAL_DUR = BAR_DUR * NUM_BARS
TOTAL_SAMPLES = int(round(TOTAL_DUR * SR))


def midi_to_freq(note: float) -> float:
    return 440.0 * (2.0 ** ((note - 69.0) / 12.0))


# ── 1. DRUM SYNTHESIZERS ─────────────────────────────────────────────────────

def synth_kick(sample_rate: int = SR) -> np.ndarray:
    """Warm Soul-R&B Kick with deep round punch (68Hz -> 42Hz) and subtle beater click."""
    dur = 0.24
    n = int(dur * sample_rate)
    t = np.arange(n) / sample_rate
    f_drop = 75.0 * np.exp(-t / 0.038) + 40.0
    phase = 2 * np.pi * np.cumsum(f_drop) / sample_rate
    env = np.exp(-t / 0.070)
    click = np.exp(-t / 0.003) * np.sin(2 * np.pi * 1400 * t) * 0.28
    sig = (np.sin(phase) + click) * env
    return (sig * 0.85).astype(np.float32)


def synth_rimshot_snare(sample_rate: int = SR, rng: np.random.Generator = None) -> np.ndarray:
    """Warm wood rimshot / layered snare with acoustic body, snap, and smooth studio reverb tail."""
    if rng is None:
        rng = np.random.default_rng()
    dur = 0.30
    n = int(dur * sample_rate)
    t = np.arange(n) / sample_rate
    body = (np.sin(2 * np.pi * 320 * t) * 0.55 + np.sin(2 * np.pi * 185 * t) * 0.45) * np.exp(-t / 0.045)
    noise = rng.standard_normal(n) * np.exp(-t / 0.060) * (1.0 - np.exp(-t / 0.002))
    reverb_tail = rng.standard_normal(n) * np.exp(-t / 0.14) * 0.16
    sig = (body * 0.50 + noise * 0.38 + reverb_tail * 0.15)
    return (sig * 0.72).astype(np.float32)


def synth_hihat(open_hh: bool = False, sample_rate: int = SR, rng: np.random.Generator = None) -> np.ndarray:
    """Crisp swung hi-hat with metallic ring and sizzle."""
    if rng is None:
        rng = np.random.default_rng()
    dur = 0.24 if open_hh else 0.06
    n = int(dur * sample_rate)
    t = np.arange(n) / sample_rate
    decay = 0.085 if open_hh else 0.018
    env = np.exp(-t / decay)
    freqs = [5800, 7200, 8900, 10500]
    metal = sum(np.sin(2 * np.pi * f * t) * 0.2 for f in freqs)
    noise = rng.standard_normal(n) * 0.8
    sig = (metal + noise) * env * (0.24 if open_hh else 0.15)
    return sig.astype(np.float32)


def synth_shaker(sample_rate: int = SR, rng: np.random.Generator = None) -> np.ndarray:
    """Soft 16th-note organic shaker stroke."""
    if rng is None:
        rng = np.random.default_rng()
    dur = 0.05
    n = int(dur * sample_rate)
    t = np.arange(n) / sample_rate
    env = np.sin(np.pi * t / dur) ** 2
    noise = rng.standard_normal(n) * env * 0.08
    return noise.astype(np.float32)


# ── 2. RHODES ELECTRIC PIANO & SOUL BASS ──────────────────────────────────────

def synth_rhodes_chord(notes: List[int], duration_s: float, velocity: float = 0.8, sample_rate: int = SR) -> Tuple[np.ndarray, np.ndarray]:
    """Lush Fender Rhodes electric piano chord with bell tine attack, warm body, and stereo auto-pan."""
    n = int(duration_s * sample_rate)
    t = np.arange(n) / sample_rate
    left = np.zeros(n, dtype=np.float32)
    right = np.zeros(n, dtype=np.float32)
    
    trem_rate = 4.2
    pan_l = 0.5 + 0.25 * np.sin(2 * np.pi * trem_rate * t)
    pan_r = 1.0 - pan_l

    for idx, midi_n in enumerate(notes):
        f = midi_to_freq(midi_n)
        tine_env = np.exp(-t / 0.09) * (1.0 - np.exp(-t / 0.002))
        tine_tone = np.sin(2 * np.pi * (f * 3.98) * t) * 0.25 * tine_env
        
        body_decay = 1.4 + (150.0 / max(60.0, f)) * 0.7
        body_env = np.exp(-t / body_decay) * (1.0 - np.exp(-t / 0.005))
        detune = 0.18
        body_tone = (
            np.sin(2 * np.pi * (f - detune) * t) * 0.65 +
            np.sin(2 * np.pi * (f + detune) * t) * 0.65 +
            np.sin(2 * np.pi * 2.0 * f * t) * 0.22 * np.exp(-t / (body_decay * 0.6)) +
            np.sin(2 * np.pi * 3.0 * f * t) * 0.08 * np.exp(-t / (body_decay * 0.4))
        ) * body_env
        
        note_sig = (body_tone + tine_tone) * velocity * 0.35
        left += (note_sig * pan_l).astype(np.float32)
        right += (note_sig * pan_r).astype(np.float32)
        
    return left, right


def synth_rhodes_bell_accent(midi_n: int, duration_s: float, velocity: float = 0.5, pan: float = 0.5, sample_rate: int = SR) -> Tuple[np.ndarray, np.ndarray]:
    """Delicate high-register Rhodes bell tine accent with natural stereo placement."""
    f = midi_to_freq(midi_n)
    n = int(duration_s * sample_rate)
    t = np.arange(n) / sample_rate
    
    # Pure singing bell tine
    env = np.exp(-t / 0.95) * (1.0 - np.exp(-t / 0.003))
    tine = (
        np.sin(2 * np.pi * f * t) * 0.70 +
        np.sin(2 * np.pi * (f * 2.001) * t) * 0.25 * np.exp(-t / 0.45) +
        np.sin(2 * np.pi * (f * 3.99) * t) * 0.12 * np.exp(-t / 0.18)
    ) * env * velocity * 0.32
    
    left = (tine * (1.0 - pan)).astype(np.float32)
    right = (tine * pan).astype(np.float32)
    return left, right


def synth_soul_bass(midi_n: int, duration_s: float, sample_rate: int = SR) -> np.ndarray:
    """Deep, singing electric soul bass (Fender Jazz Bass tone) with warm low-mid punch."""
    freq = midi_to_freq(midi_n)
    n = int(duration_s * sample_rate)
    t = np.arange(n) / sample_rate
    env = np.exp(-t / 0.75) * (1.0 - np.exp(-t / 0.008))
    drift = 1.0 + 0.02 * np.exp(-t / 0.025)
    sig = (
        np.sin(2 * np.pi * freq * drift * t) * 0.80 +
        np.sin(2 * np.pi * 2.0 * freq * t) * 0.28 +
        np.sin(2 * np.pi * 3.0 * freq * t) * 0.09 +
        np.sin(2 * np.pi * 4.0 * freq * t) * 0.03
    )
    sig = np.tanh(sig * 1.3) * env * 0.65
    return sig.astype(np.float32)


# ── 3. FULL TRACK ARRANGEMENT ────────────────────────────────────────────────

def generate_upbeat_jazz_track(out_wav_path: str) -> dict:
    """
    Synthesizes the complete 24-bar Morning Smooth Jazz & Soul R&B track
    (Rhodes Electric Piano, Deep Electric Soul Bass, Soul Pocket Drums)
    completely free of saxophone or any harsh brass sounds.
    """
    rng = np.random.default_rng(20261018)
    
    left = np.zeros(TOTAL_SAMPLES, dtype=np.float32)
    right = np.zeros(TOTAL_SAMPLES, dtype=np.float32)
    
    # ── A. DRUMS: SOUL POCKET BEAT ───────────────────────────────────────────
    # Swing ratio: 16th-note shuffle (downbeat = 58%, upbeat = 42%)
    step_16th = BEAT_DUR / 4.0  # 0.1875s
    
    for bar in range(NUM_BARS):
        bar_start_s = bar * BAR_DUR
        
        # 1. Kick Drum: Beat 1 (downbeat) + Beat 2.5 (syncopated soul bounce)
        # Occasionally also on Beat 3.5 on alternating bars
        kick_times = [
            bar_start_s,                      # Beat 1 (0.0s)
            bar_start_s + 1.5 * BEAT_DUR,     # Beat 2.5 (1.125s)
        ]
        if bar % 2 == 1:
            kick_times.append(bar_start_s + 3.5 * BEAT_DUR)  # Beat 4.5 pickup
            
        for kt in kick_times:
            k_sig = synth_kick()
            idx = int(kt * SR)
            n_len = min(len(k_sig), TOTAL_SAMPLES - idx)
            if n_len > 0:
                left[idx:idx + n_len] += k_sig[:n_len] * 0.50
                right[idx:idx + n_len] += k_sig[:n_len] * 0.50
                
        # 2. Snare / Rimshot: Beat 2 and Beat 4 (Solid backbeat)
        snare_times = [
            bar_start_s + 1.0 * BEAT_DUR,     # Beat 2 (0.75s)
            bar_start_s + 3.0 * BEAT_DUR,     # Beat 4 (2.25s)
        ]
        for st in snare_times:
            s_sig = synth_rimshot_snare(rng=rng)
            idx = int(st * SR)
            n_len = min(len(s_sig), TOTAL_SAMPLES - idx)
            if n_len > 0:
                left[idx:idx + n_len] += s_sig[:n_len] * 0.55
                right[idx:idx + n_len] += s_sig[:n_len] * 0.55
                
        # 3. Hi-Hat & Shaker Pattern (16th-note groove with laid-back pocket)
        for s16 in range(16):
            t_s = bar_start_s + s16 * step_16th
            # Add slight swing delay to odd 16th notes
            if s16 % 2 == 1:
                t_s += step_16th * 0.16
                
            idx = int(t_s * SR)
            if idx >= TOTAL_SAMPLES:
                continue
                
            # Shaker on every 16th
            shk = synth_shaker(rng=rng)
            n_shk = min(len(shk), TOTAL_SAMPLES - idx)
            if n_shk > 0:
                left[idx:idx + n_shk] += shk[:n_shk] * 0.40
                right[idx:idx + n_shk] += shk[:n_shk] * 0.70
                
            # Hi-hat on 8th notes (and open hi-hat on beat 4.75)
            if s16 % 2 == 0:
                is_open = (s16 == 14) and (bar % 2 == 1)
                hh = synth_hihat(open_hh=is_open, rng=rng)
                n_hh = min(len(hh), TOTAL_SAMPLES - idx)
                if n_hh > 0:
                    vol = 0.85 if (s16 in [0, 4, 8, 12]) else 0.50
                    left[idx:idx + n_hh] += hh[:n_hh] * vol * 0.40
                    right[idx:idx + n_hh] += hh[:n_hh] * vol * 0.80

    # ── B. HARMONIC FORM & RHODES ELECTRIC PIANO ─────────────────────────────
    # Extended 24-bar progression in F Major / D Minor:
    # Voicings in MIDI note numbers:
    # Gm9:    G2(43), F3(53), Bb3(58), D4(62), A4(69)
    # C13:    C2(36), E3(52), Bb3(58), D4(62), A4(69)
    # Fmaj9:  F2(41), E3(52), A3(57),  C4(60), G4(67)
    # Dm9:    D2(38), F3(53), A3(57),  C4(60), E4(64)
    # Am7:    A2(45), G3(55), C4(60),  E4(64)
    # D7(b9): D2(38), F#3(54), C4(60), Eb4(63), A4(69)
    # Bbmaj9: Bb2(46), A3(57), D4(62), F4(65), C5(72)
    # Bbm9:   Bb2(46), Ab3(56), Db4(61), F4(65), C5(72)
    # Am9:    A2(45), G3(55), C4(60),  E4(64), B4(71)
    # D7(#9): D2(38), F#3(54), C4(60), F4(65)
    # C7sus:  C2(36), F3(53), Bb3(58), D4(62)
    
    chord_chart = [
        # Bars 1-4 (Intro Theme)
        [43, 53, 58, 62, 69],  # Gm9
        [36, 52, 58, 62, 69],  # C13
        [41, 52, 57, 60, 67],  # Fmaj9
        [38, 53, 57, 60, 64],  # Dm9
        # Bars 5-8 (Turnaround Theme)
        [43, 53, 58, 62, 69],  # Gm9
        [36, 52, 58, 62, 69],  # C13
        [45, 55, 60, 64],      # Am7
        [38, 54, 60, 63, 69],  # D7(b9)
        # Bars 9-12 (Subdominant Lift)
        [46, 57, 62, 65, 72],  # Bbmaj9
        [46, 56, 61, 65, 72],  # Bbm9
        [45, 55, 60, 64, 71],  # Am9
        [38, 54, 60, 65],      # D7(#9)
        # Bars 13-16 (Resolution)
        [43, 53, 58, 62, 69],  # Gm9
        [36, 53, 58, 62],      # C7sus
        [41, 52, 57, 60, 67],  # Fmaj9
        [38, 53, 57, 60, 64],  # Dm9
        # Bars 17-20 (B-Section Groove)
        [46, 57, 62, 65, 72],  # Bbmaj9
        [45, 55, 60, 64, 71],  # Am9
        [43, 53, 58, 62, 69],  # Gm9
        [36, 52, 58, 62, 69],  # C13
        # Bars 21-24 (Turnaround back to Bar 1)
        [41, 52, 57, 60, 67],  # Fmaj9
        [38, 53, 57, 60, 64],  # Dm9
        [43, 53, 58, 62, 69],  # Gm9
        [36, 52, 58, 62, 69],  # C13
    ]

    for bar, chord in enumerate(chord_chart):
        bar_start_s = bar * BAR_DUR
        # Comping rhythm: Hit on beat 1, offbeat stab on beat 2.5, sustain through beat 4
        # Beat 1 chord
        cl, cr = synth_rhodes_chord(chord, duration_s=1.8, velocity=0.80)
        idx1 = int(bar_start_s * SR)
        n_l1 = min(len(cl), TOTAL_SAMPLES - idx1)
        if n_l1 > 0:
            left[idx1:idx1 + n_l1] += cl[:n_l1]
            right[idx1:idx1 + n_l1] += cr[:n_l1]
            
        # Beat 2.5 syncopated stab
        cl_stab, cr_stab = synth_rhodes_chord(chord, duration_s=1.2, velocity=0.65)
        idx2 = int((bar_start_s + 1.5 * BEAT_DUR) * SR)
        n_l2 = min(len(cl_stab), TOTAL_SAMPLES - idx2)
        if n_l2 > 0:
            left[idx2:idx2 + n_l2] += cl_stab[:n_l2]
            right[idx2:idx2 + n_l2] += cr_stab[:n_l2]

    # ── C. DEEP SOUL ELECTRIC BASSLINE ────────────────────────────────────────
    # Melodic, syncopated bassline locked in the pocket
    bass_lines = [
        # Bar 1: G
        [(0.0, 43, 0.9), (1.5 * BEAT_DUR, 55, 0.4), (2.5 * BEAT_DUR, 43, 0.6)],
        # Bar 2: C
        [(0.0, 36, 0.9), (1.5 * BEAT_DUR, 48, 0.4), (2.5 * BEAT_DUR, 41, 0.6)],
        # Bar 3: F
        [(0.0, 41, 0.9), (1.5 * BEAT_DUR, 53, 0.4), (2.5 * BEAT_DUR, 41, 0.6)],
        # Bar 4: D
        [(0.0, 38, 0.9), (1.5 * BEAT_DUR, 50, 0.4), (2.5 * BEAT_DUR, 42, 0.6)],
        # Bar 5: G
        [(0.0, 43, 0.9), (1.5 * BEAT_DUR, 55, 0.4), (2.5 * BEAT_DUR, 43, 0.6)],
        # Bar 6: C
        [(0.0, 36, 0.9), (1.5 * BEAT_DUR, 48, 0.4), (2.5 * BEAT_DUR, 44, 0.6)],
        # Bar 7: A
        [(0.0, 45, 0.9), (1.5 * BEAT_DUR, 57, 0.4), (2.5 * BEAT_DUR, 45, 0.6)],
        # Bar 8: D
        [(0.0, 38, 0.9), (1.5 * BEAT_DUR, 50, 0.4), (2.5 * BEAT_DUR, 45, 0.6)],
        # Bar 9: Bb
        [(0.0, 46, 0.9), (1.5 * BEAT_DUR, 58, 0.4), (2.5 * BEAT_DUR, 46, 0.6)],
        # Bar 10: Bbm
        [(0.0, 46, 0.9), (1.5 * BEAT_DUR, 56, 0.4), (2.5 * BEAT_DUR, 45, 0.6)],
        # Bar 11: A
        [(0.0, 45, 0.9), (1.5 * BEAT_DUR, 57, 0.4), (2.5 * BEAT_DUR, 43, 0.6)],
        # Bar 12: D
        [(0.0, 38, 0.9), (1.5 * BEAT_DUR, 50, 0.4), (2.5 * BEAT_DUR, 42, 0.6)],
        # Bar 13: G
        [(0.0, 43, 0.9), (1.5 * BEAT_DUR, 55, 0.4), (2.5 * BEAT_DUR, 43, 0.6)],
        # Bar 14: C
        [(0.0, 36, 0.9), (1.5 * BEAT_DUR, 48, 0.4), (2.5 * BEAT_DUR, 40, 0.6)],
        # Bar 15: F
        [(0.0, 41, 0.9), (1.5 * BEAT_DUR, 53, 0.4), (2.5 * BEAT_DUR, 41, 0.6)],
        # Bar 16: D
        [(0.0, 38, 0.9), (1.5 * BEAT_DUR, 50, 0.4), (2.5 * BEAT_DUR, 42, 0.6)],
        # Bar 17: Bb
        [(0.0, 46, 0.9), (1.5 * BEAT_DUR, 58, 0.4), (2.5 * BEAT_DUR, 46, 0.6)],
        # Bar 18: A
        [(0.0, 45, 0.9), (1.5 * BEAT_DUR, 57, 0.4), (2.5 * BEAT_DUR, 45, 0.6)],
        # Bar 19: G
        [(0.0, 43, 0.9), (1.5 * BEAT_DUR, 55, 0.4), (2.5 * BEAT_DUR, 43, 0.6)],
        # Bar 20: C
        [(0.0, 36, 0.9), (1.5 * BEAT_DUR, 48, 0.4), (2.5 * BEAT_DUR, 40, 0.6)],
        # Bar 21: F
        [(0.0, 41, 0.9), (1.5 * BEAT_DUR, 53, 0.4), (2.5 * BEAT_DUR, 41, 0.6)],
        # Bar 22: D
        [(0.0, 38, 0.9), (1.5 * BEAT_DUR, 50, 0.4), (2.5 * BEAT_DUR, 42, 0.6)],
        # Bar 23: G
        [(0.0, 43, 0.9), (1.5 * BEAT_DUR, 55, 0.4), (2.5 * BEAT_DUR, 43, 0.6)],
        # Bar 24: C
        [(0.0, 36, 0.9), (1.5 * BEAT_DUR, 48, 0.4), (2.5 * BEAT_DUR, 42, 0.6)],
    ]

    for bar, notes in enumerate(bass_lines):
        bar_start_s = bar * BAR_DUR
        for offset_s, midi_n, dur_s in notes:
            b_sig = synth_soul_bass(midi_n, dur_s)
            idx = int((bar_start_s + offset_s) * SR)
            n_len = min(len(b_sig), TOTAL_SAMPLES - idx)
            if n_len > 0:
                left[idx:idx + n_len] += b_sig[:n_len] * 0.55
                right[idx:idx + n_len] += b_sig[:n_len] * 0.55

    # ── D. DELICATE RHODES UPPER TINE CHIMES ─────────────────────────────────
    # Gentle, sweet acoustic Rhodes bell chimes on phrase endings (Zero saxophone)
    bell_chimes = [
        # Bar 4 turnaround chime (F5 -> E5 -> C5)
        (3.0 * BAR_DUR + 2.0 * BEAT_DUR, 77, 1.2, 0.65),  # F5
        (3.0 * BAR_DUR + 3.0 * BEAT_DUR, 76, 1.0, 0.70),  # E5
        (4.0 * BAR_DUR + 0.0 * BEAT_DUR, 72, 1.8, 0.60),  # C5
        
        # Bar 8 turnaround chime (D5 -> C5 -> A4)
        (7.0 * BAR_DUR + 2.0 * BEAT_DUR, 74, 1.2, 0.35),  # D5
        (7.0 * BAR_DUR + 3.0 * BEAT_DUR, 72, 1.0, 0.30),  # C5
        (8.0 * BAR_DUR + 0.0 * BEAT_DUR, 69, 1.8, 0.40),  # A4
        
        # Bar 12 turnaround chime (Eb5 -> D5 -> C5)
        (11.0 * BAR_DUR + 2.0 * BEAT_DUR, 75, 1.2, 0.65), # Eb5
        (11.0 * BAR_DUR + 3.0 * BEAT_DUR, 74, 1.0, 0.60), # D5
        (12.0 * BAR_DUR + 0.0 * BEAT_DUR, 72, 1.8, 0.55), # C5

        # Bar 16 turnaround chime (A5 -> G5 -> F5)
        (15.0 * BAR_DUR + 2.0 * BEAT_DUR, 81, 1.2, 0.35), # A5
        (15.0 * BAR_DUR + 3.0 * BEAT_DUR, 79, 1.0, 0.30), # G5
        (16.0 * BAR_DUR + 0.0 * BEAT_DUR, 77, 1.8, 0.40), # F5

        # Bar 20 turnaround chime (C5 -> D5 -> E5)
        (19.0 * BAR_DUR + 2.0 * BEAT_DUR, 72, 1.2, 0.65), # C5
        (19.0 * BAR_DUR + 3.0 * BEAT_DUR, 74, 1.0, 0.60), # D5
        (20.0 * BAR_DUR + 0.0 * BEAT_DUR, 76, 1.8, 0.55), # E5

        # Bar 24 final turnaround chime leading into Bar 1 (G5 -> A5 -> C6)
        (23.0 * BAR_DUR + 2.0 * BEAT_DUR, 79, 1.2, 0.40), # G5
        (23.0 * BAR_DUR + 3.0 * BEAT_DUR, 81, 1.0, 0.45), # A5
        (24.0 * BAR_DUR - 0.02,          84, 1.8, 0.50),  # C6
    ]

    for start_s, midi_n, dur_s, pan in bell_chimes:
        idx = int(start_s * SR)
        if idx < TOTAL_SAMPLES:
            bl, br = synth_rhodes_bell_accent(midi_n, dur_s, velocity=0.45, pan=pan)
            n_len = min(len(bl), TOTAL_SAMPLES - idx)
            if n_len > 0:
                left[idx:idx + n_len] += bl[:n_len]
                right[idx:idx + n_len] += br[:n_len]

    # ── E. MASTERING & SEAMLESS LOOP NORMALIZATION ───────────────────────────
    # Mixdown peak measurement
    peak_l = np.max(np.abs(left))
    peak_r = np.max(np.abs(right))
    max_peak = max(peak_l, peak_r, 1e-6)
    
    # Target headroom: -1.50 dBFS = 0.8414 linear
    target_peak = 0.8414
    gain = target_peak / max_peak
    left *= gain
    right *= gain
    
    # Clean zero-crossing micro fade at boundaries (10ms) for seamless looping
    n_fade = int(0.010 * SR)
    fade_curve = np.sin(np.linspace(0, np.pi / 2, n_fade)) ** 2
    left[:n_fade] *= fade_curve
    right[:n_fade] *= fade_curve
    left[-n_fade:] *= fade_curve[::-1]
    right[-n_fade:] *= fade_curve[::-1]

    # Interleave into stereo 16-bit PCM
    stereo = np.zeros(TOTAL_SAMPLES * 2, dtype=np.int16)
    stereo[0::2] = (np.clip(left, -1.0, 1.0) * 32767).astype(np.int16)
    stereo[1::2] = (np.clip(right, -1.0, 1.0) * 32767).astype(np.int16)

    # Write WAV file
    os.makedirs(os.path.dirname(os.path.abspath(out_wav_path)), exist_ok=True)
    with wave.open(out_wav_path, "wb") as wf:
        wf.setnchannels(2)
        wf.setsampwidth(2)
        wf.setframerate(SR)
        wf.writeframes(stereo.tobytes())

    rms_l = float(np.sqrt(np.mean(left ** 2)))
    rms_r = float(np.sqrt(np.mean(right ** 2)))
    rms_avg = (rms_l + rms_r) * 0.5
    peak_dbfs = 20.0 * math.log10(max_peak * gain)
    rms_dbfs = 20.0 * math.log10(rms_avg)

    return {
        "file": os.path.abspath(out_wav_path),
        "genre": "upbeat_jazz",
        "style": "Morning Smooth Jazz & Soul R&B (Rhodes Soul Trio)",
        "reference": "https://www.youtube.com/watch?v=du7Vjfxv4zU",
        "sample_rate": SR,
        "channels": 2,
        "tempo_bpm": BPM,
        "num_bars": NUM_BARS,
        "duration_seconds": round(TOTAL_DUR, 3),
        "total_samples": TOTAL_SAMPLES,
        "peak_linear": round(float(target_peak), 4),
        "peak_dbfs": round(peak_dbfs, 2),
        "rms_dbfs": round(rms_dbfs, 2),
        "clipping": False
    }


def main():
    skill_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    target_path = os.path.join(skill_root, "resources", "audio", "music", "upbeat-jazz-v1.wav")
    print(f"[Smooth Jazz Generator] Generating Pure Rhodes Soul Trio track (No Saxophone)...")
    print(f" -> Target: {target_path}")
    print(f" -> Tempo:  {BPM} BPM | Bars: {NUM_BARS} | Dur: {TOTAL_DUR}s")
    
    meta = generate_upbeat_jazz_track(target_path)
    print("=" * 65)
    print("[Smooth Jazz Generator] Generation Complete!")
    print(f" -> Path:        {meta['file']}")
    print(f" -> Style:       {meta['style']}")
    print(f" -> Reference:   {meta['reference']}")
    print(f" -> Duration:    {meta['duration_seconds']}s ({meta['total_samples']} samples)")
    print(f" -> Peak:        {meta['peak_dbfs']} dBFS (linear: {meta['peak_linear']})")
    print(f" -> RMS:         {meta['rms_dbfs']} dBFS")
    print(f" -> Clipping:    {'YES' if meta['clipping'] else 'NO (Headroom Clean)'}")
    print("=" * 65)


if __name__ == "__main__":
    main()
