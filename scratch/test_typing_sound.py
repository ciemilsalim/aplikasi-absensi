import numpy as np
import wave
import struct
import random

def generate_soft_natural_typing(duration_sec, sample_rate=44100):
    total_samples = int(duration_sec * sample_rate)
    buffer = np.zeros(total_samples, dtype=np.float32)
    
    # Keystroke cadence: average 95ms to 125ms per keystroke with natural human rhythm
    current_time = 0.050 # start after 50ms
    random.seed(42)
    
    while current_time < (duration_sec - 0.080):
        # Character keystroke
        stroke_idx = int(current_time * sample_rate)
        
        # Keystroke parameters: soft, gentle laptop chiclet / scissor keyboard
        # Duration: ~35ms
        stroke_samples = int(0.040 * sample_rate)
        t = np.linspace(0, 0.040, stroke_samples, endpoint=False)
        
        # Natural variations
        freq = random.uniform(320.0, 520.0) # warm mid-low frequency
        is_space = (random.random() < 0.18)
        if is_space:
            freq = random.uniform(220.0, 280.0)
            
        vel = random.uniform(0.6, 0.95)
        
        # 1. Warm body tone with fast exponential decay
        decay = np.exp(-t / 0.010)
        body = np.sin(2 * np.pi * freq * t) * decay
        
        # 2. Soft keycap impact (low-passed filtered gentle noise, no harsh highs)
        noise = np.random.normal(0, 1, stroke_samples)
        # Simple moving average to low-pass filter the noise
        noise_lp = np.convolve(noise, np.ones(8)/8.0, mode='same')
        noise_decay = np.exp(-t / 0.005) # very short 5ms impact
        impact = noise_lp * noise_decay * 0.45
        
        # Combined keystroke
        keystroke = (body + impact) * vel * 0.12 # soft, non-intrusive volume
        
        # Add to buffer
        end_idx = min(total_samples, stroke_idx + stroke_samples)
        add_len = end_idx - stroke_idx
        buffer[stroke_idx:end_idx] += keystroke[:add_len]
        
        # Next keystroke interval: 85ms - 135ms (realistic human typing ~40-60 WPM)
        interval = random.uniform(0.085, 0.130)
        current_time += interval
        
    # Clip and convert to 16-bit PCM
    buffer = np.clip(buffer, -1.0, 1.0)
    int_samples = (buffer * 32767.0).astype(np.int16)
    return int_samples

if __name__ == '__main__':
    samples = generate_soft_natural_typing(3.0)
    out_wav = 'scratch/test_soft_typing.wav'
    with wave.open(out_wav, 'wb') as wf:
        wf.setnchannels(1)
        wf.setsampwidth(2)
        wf.setframerate(44100)
        wf.writeframes(samples.tobytes())
    print(f"Generated {out_wav} successfully: {len(samples)} samples ({len(samples)/44100:.2f}s)")
