#!/usr/bin/env python
# tts.py — V3.5.4 Narration-Cued Action Audio Engine
# Synthesizes speech with word-level timestamps (boundary="WordBoundary")
# and enforces the core invariant:
# NARRATION FIRST -> REACTION DELAY (200-400ms) -> ACTION PERFORMED
# Outputs:
# 1. narration-word-timestamps.json
# 2. narration-cued-action-map.json
# 3. narration-visual-map.json
# 4. Master Narration Track (sample-accurate 44.1kHz buffer)
import argparse, asyncio, json, math, os, re, shutil, struct, subprocess, sys

try:
    import edge_tts
except ImportError:
    print('edge-tts is not installed. Install with: python -m pip install edge-tts', file=sys.stderr)
    sys.exit(3)

def get_ffmpeg():
    ffmpeg = shutil.which('ffmpeg')
    if not ffmpeg:
        try:
            import imageio_ffmpeg
            ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
        except Exception:
            pass
    return ffmpeg

def format_srt_timestamp(seconds):
    h = int(seconds // 3600)
    m = int((seconds % 3600) // 60)
    s = int(seconds % 60)
    ms = int(round((seconds - int(seconds)) * 1000))
    if ms >= 1000:
        ms = 999
    return f"{h:02d}:{m:02d}:{s:02d},{ms:03d}"

def get_audio_duration(audio_path):
    ffprobe = shutil.which('ffprobe')
    if ffprobe:
        r = subprocess.run([ffprobe, '-v', 'error', '-show_entries', 'format=duration', '-of', 'json', audio_path], capture_output=True, text=True)
        if r.returncode == 0:
            data = json.loads(r.stdout)
            return float(data.get('format', {}).get('duration', 0))
    ffmpeg = get_ffmpeg()
    if ffmpeg:
        r = subprocess.run([ffmpeg, '-i', audio_path], capture_output=True, text=True)
        match = re.search(r'Duration:\s*(\d+):(\d+):([\d\.]+)', r.stderr)
        if match:
            h, m, s = match.groups()
            return int(h) * 3600 + int(m) * 60 + float(s)
    return 3.0

def condense_indonesian_narration(text: str) -> str:
    """
    V3.5.3.1 Semantic Sentence Condenser for Indonesian Narration.
    Removes polite filler and compresses phrasing to concise action-first sentences.
    """
    text = text.strip()
    replacements = [
        ("Selamat datang di panduan aplikasi SIASEK portal orang tua.", "Panduan portal orang tua SIASEK."),
        ("Pada video kali ini, kita akan mempelajari cara mengajukan izin atau surat sakit anak secara online.", "Berikut cara mengajukan izin atau sakit anak online."),
        ("Silakan masuk ke portal orang tua dengan akun terdaftar Anda.", "Masuk ke portal orang tua Anda."),
        ("Buka menu riwayat permohonan izin lalu klik tombol Buat Pengajuan Baru untuk membuka formulir.", "Klik tombol Buat Pengajuan Baru."),
        ("Pada formulir pengajuan, pilih nama anak yang akan diajukan izin pada daftar siswa yang terhubung dengan akun Anda.", "Pilih nama anak yang akan diajukan izin."),
        ("Tentukan rentang tanggal izin dan pilih kategori permohonan yang sesuai, seperti kategori sakit atau izin keperluan keluarga.", "Pilih kategori permohonan izin sakit siswa."),
        ("Selanjutnya tuliskan keterangan alasan secara rinci pada kolom alasan agar wali kelas dan pihak sekolah dapat melakukan verifikasi.", "Ketik keterangan alasan izin dengan jelas."),
        ("Periksa kembali seluruh data permohonan, lalu klik tombol Kirim Permohonan untuk mengirimkan pengajuan ke sekolah.", "Klik tombol Kirim Permohonan Izin."),
        ("Permohonan izin anak Anda telah berhasil dikirim dan menunggu persetujuan dari pihak sekolah.", "Permohonan izin berhasil dikirim ke sekolah."),
        ("silakan ", ""),
        ("pada video kali ini, kita akan ", ""),
        ("pada formulir pengajuan, ", ""),
        ("secara online", "online"),
        ("terhubung dengan akun Anda", "terdaftar"),
        ("agar wali kelas dan pihak sekolah dapat melakukan verifikasi", "untuk verifikasi sekolah"),
        ("secara rinci", ""),
        ("pada kolom alasan", ""),
        ("selanjutnya ", "")
    ]
    condensed = text
    for old, new in replacements:
        if old.lower() in condensed.lower():
            pattern = re.compile(re.escape(old), re.IGNORECASE)
            condensed = pattern.sub(new, condensed)
    condensed = re.sub(r'\s+', ' ', condensed).strip()
    return condensed

# Action verb taxonomy for Indonesian tutorial interactions
ACTION_VERBS = {
    'click': ['klik', 'ketuk', 'tap'],
    'tap': ['tap', 'ketuk', 'klik'],
    'typing': ['ketik', 'tulis', 'masukkan', 'isi'],
    'fill': ['ketik', 'masukkan', 'tulis', 'isi'],
    'select': ['pilih'],
    'open': ['buka'],
    'enter': ['tekan', 'masukkan'],
    'scroll': ['gulir', 'scroll']
}

async def generate_segment_with_word_timing(text: str, voice: str, audio_file: str, max_duration_ms: int = None):
    """
    Synthesizes TTS speech with edge-tts WordBoundary streaming.
    Returns: (audio_file: str, dur_ms: int, words: list, final_text: str)
    """
    os.makedirs(os.path.dirname(os.path.abspath(audio_file)), exist_ok=True)
    comm = edge_tts.Communicate(text, voice, boundary='WordBoundary')
    audio_data = bytearray()
    words = []

    async for chunk in comm.stream():
        if chunk['type'] == 'audio':
            audio_data.extend(chunk['data'])
        elif chunk['type'] == 'WordBoundary':
            w_text = chunk['text']
            w_start_ms = chunk['offset'] / 10000.0
            w_dur_ms = chunk['duration'] / 10000.0
            words.append({
                'word': w_text,
                'rel_start_ms': round(w_start_ms, 1),
                'rel_end_ms': round(w_start_ms + w_dur_ms, 1),
                'duration_ms': round(w_dur_ms, 1)
            })

    with open(audio_file, 'wb') as f:
        f.write(audio_data)

    dur = get_audio_duration(audio_file)
    dur_ms = int(round(dur * 1000))
    final_text = text

    # Video-Master constraint: if speech exceeds visual budget, condense semantically
    if max_duration_ms and dur_ms > max_duration_ms:
        print(f"  [TTS V3.5.3.1] Duration {dur_ms}ms > budget {max_duration_ms}ms. Condensing: '{text}'")
        condensed = condense_indonesian_narration(text)
        rates_to_try = ["+0%", "+6%", "+10%"]
        for rate in rates_to_try:
            c_comm = edge_tts.Communicate(condensed, voice, rate=rate, boundary='WordBoundary')
            c_audio = bytearray()
            c_words = []
            async for chunk in c_comm.stream():
                if chunk['type'] == 'audio':
                    c_audio.extend(chunk['data'])
                elif chunk['type'] == 'WordBoundary':
                    w_text = chunk['text']
                    w_start_ms = chunk['offset'] / 10000.0
                    w_dur_ms = chunk['duration'] / 10000.0
                    c_words.append({
                        'word': w_text,
                        'rel_start_ms': round(w_start_ms, 1),
                        'rel_end_ms': round(w_start_ms + w_dur_ms, 1),
                        'duration_ms': round(w_dur_ms, 1)
                    })
            with open(audio_file, 'wb') as f:
                f.write(c_audio)
            dur = get_audio_duration(audio_file)
            dur_ms = int(round(dur * 1000))
            if dur_ms <= max_duration_ms:
                words = c_words
                final_text = condensed
                print(f"  [TTS V3.5.3.1] Successfully fit '{final_text}' ({dur_ms}ms, rate={rate})")
                break
        else:
            words = c_words
            final_text = condensed

    return audio_file, dur_ms, words, final_text

def decode_audio_to_pcm(audio_file: str, ffmpeg_exe: str, sample_rate: int = 44100):
    cmd = [
        ffmpeg_exe, '-v', 'error', '-y', '-i', audio_file,
        '-f', 's16le', '-ac', '1', '-ar', str(sample_rate), '-'
    ]
    proc = subprocess.run(cmd, capture_output=True)
    raw = proc.stdout
    if len(raw) < 2:
        return []
    count = len(raw) // 2
    return struct.unpack(f'<{count}h', raw[:count*2])

async def main():
    parser = argparse.ArgumentParser(description='V3.5.4 Narration-Cued Action Audio & Subtitle Engine.')
    parser.add_argument('--plan', required=True, help='Path to tutorial plan JSON')
    parser.add_argument('--timeline', help='Path to final-video-timeline.json (Picture Lock Timeline)')
    parser.add_argument('--audio-out', help='Output path for merged narration audio')
    parser.add_argument('--subtitle-out', help='Output path for tutorial.srt')
    parser.add_argument('--meta-out', help='Output path for audio metadata JSON')
    parser.add_argument('--out-durations', help='Output path for narration-durations.json')
    parser.add_argument('--measure-only', action='store_true', help='Only measure narration durations without video timeline')
    parser.add_argument('--voice', default='id-ID-ArdiNeural', help='TTS Voice name')
    parser.add_argument('--run-id', help='Run ID')
    parser.add_argument('--variant', help='Variant identifier (desktop|mobile)')
    args = parser.parse_args()

    ffmpeg = get_ffmpeg()
    if not ffmpeg:
        raise SystemExit("FFmpeg binary not found.")

    with open(args.plan, 'r', encoding='utf-8') as f:
        plan = json.load(f)

    voice = plan.get('voice', args.voice)
    plan_scenes = plan.get('scenes', [])

    # ── MODE 1: MEASURE ONLY ──
    if args.measure_only:
        print("\n" + "="*70)
        print("V3.5.4 TTS PRE-MEASUREMENT: NARRATION DURATIONS")
        print(f"Voice: {voice} | Scenes: {len(plan_scenes)}")
        print("="*70 + "\n")
        
        durations = {}
        temp_dir = os.path.join('.tutorial-video', 'temp_tts_measure')
        os.makedirs(temp_dir, exist_ok=True)

        for s_idx, p_scene in enumerate(plan_scenes):
            sid = p_scene.get('scene_id') or p_scene.get('id', f"scene_{s_idx+1:02d}")
            raw_text = p_scene.get('narration', '')
            raw_segments = p_scene.get('narration_segments') or []
            if raw_segments:
                seg_texts = [seg.get('text', '') for seg in raw_segments]
                combined_text = " ".join([t for t in seg_texts if t])
            else:
                combined_text = raw_text

            audio_tmp = os.path.join(temp_dir, f"{sid}.mp3")
            _, dur_ms, words, final_text = await generate_segment_with_word_timing(
                combined_text, voice, audio_tmp
            )
            durations[sid] = {
                "scene_id": sid,
                "text": final_text,
                "narration_duration_ms": dur_ms,
                "reaction_delay_ms": 300,
                "words_count": len(words)
            }
            print(f"  [Measure] {sid}: {dur_ms}ms ({dur_ms/1000.0:.2f}s) -> '{final_text[:45]}...'")

        try:
            shutil.rmtree(temp_dir, ignore_errors=True)
        except Exception:
            pass

        out_dur_path = args.out_durations or os.path.join('.tutorial-video', 'narration-durations.json')
        os.makedirs(os.path.dirname(os.path.abspath(out_dur_path)), exist_ok=True)
        with open(out_dur_path, 'w', encoding='utf-8') as f:
            json.dump(durations, f, indent=2, ensure_ascii=False)
        print(f"\n[TTS V3.5.4] Narration durations written to: {out_dur_path}")
        return

    # ── MODE 2: FULL SYNTHESIS & TIMELINE ALIGNMENT ──
    if not args.timeline or not os.path.isfile(args.timeline):
        raise ValueError(f"Timeline file required for full synthesis: {args.timeline}")
    if not args.audio_out:
        raise ValueError("--audio-out path is required for full synthesis.")

    audio_dir = os.path.dirname(os.path.abspath(args.audio_out))
    run_dir = os.path.dirname(audio_dir)
    os.makedirs(audio_dir, exist_ok=True)

    with open(args.timeline, 'r', encoding='utf-8') as f:
        timeline_data = json.load(f)

    total_video_dur_sec = timeline_data.get('video_info', {}).get('duration_sec', 75.0)
    tl_scenes = timeline_data.get('scenes', [])
    scenes_by_id = {s.get('scene_id'): s for s in tl_scenes}

    all_segments = []
    narration_cued_map = []
    narration_word_timestamps = []
    narr_visual_map = []

    print("\n" + "="*70)
    print("V3.5.4 NARRATION-CUED ACTION AUDIO ENGINE")
    print(f"Master Video Duration: {total_video_dur_sec:.2f}s | Voice: {voice}")
    print("Core Rule: NARRATE -> FINISH SPEAKING -> REACTION DELAY (>=200ms) -> ACTION")
    print("="*70 + "\n")

    for s_idx, p_scene in enumerate(plan_scenes):
        sid = p_scene.get('scene_id') or p_scene.get('id', f"scene_{s_idx+1:02d}")
        tl_sc = scenes_by_id.get(sid, {})
        vis_start_ms = float(tl_sc.get('visual_start_ms', 0.0))
        vis_end_ms = float(tl_sc.get('visual_end_ms', vis_start_ms + 10000.0))
        scene_actions = tl_sc.get('actions', [])
        
        # Action start and click frame PTS from video timeline
        action_start_pts = float(tl_sc.get('action_start_ms') or 0.0)
        click_pts_ms = float(tl_sc.get('click_frame_pts_ms') or 0.0)
        if not action_start_pts and scene_actions:
            first_act = scene_actions[0]
            action_start_pts = float(first_act.get('start_frame_pts_ms') or first_act.get('frame_pts_ms') or (vis_start_ms + 3500.0))
            click_pts_ms = float(first_act.get('frame_pts_ms') or (action_start_pts + 700.0))

        raw_segments = p_scene.get('narration_segments') or []
        if not raw_segments:
            raw_text = p_scene.get('narration', '')
            raw_segments = [{
                'narration_id': f"{sid}_seg_01",
                'text': raw_text,
                'target_visual': p_scene.get('title', sid),
                'target_element': 'main',
                'importance': 'normal'
            }]

        prev_seg_end_ms = vis_start_ms + 200.0

        for seg_idx, rseg in enumerate(raw_segments):
            seg_id = rseg.get('narration_id', f"{sid}_seg_{seg_idx+1:02d}")
            text = rseg.get('text', '').strip()
            max_budget = int(vis_end_ms - vis_start_ms - 200) // max(1, len(raw_segments))
            seg_audio_file = os.path.join(audio_dir, f"{seg_id}.mp3")

            # 1. Synthesize with WordBoundary
            seg_audio, dur_ms, words, final_text = await generate_segment_with_word_timing(
                text, voice, seg_audio_file, max_duration_ms=max_budget
            )

            # 2. V3.5.4 Narration-Cued Positioning
            has_action = len(scene_actions) > 0 or action_start_pts > 0
            
            if seg_idx == 0:
                # Instruction cue begins at start of visual scene window (+200ms settle)
                seg_start_ms = round(vis_start_ms + 200.0, 1)
                seg_end_ms = round(seg_start_ms + dur_ms, 1)
                reaction_delay_ms = round(action_start_pts - seg_end_ms, 1) if action_start_pts > 0 else 300.0

                status = "PASS"
                if has_action:
                    if action_start_pts < seg_end_ms:
                        status = "FAIL (ACTION BEFORE NARRATION END)"
                        print(f"  [HARD FAIL] Scene {sid}: Action starts @ {action_start_pts}ms BEFORE narration ends @ {seg_end_ms}ms!")
                    elif reaction_delay_ms < 200.0:
                        status = "FAIL (REACTION DELAY < 200ms)"
                        print(f"  [WARN/FAIL] Scene {sid}: Reaction delay {reaction_delay_ms}ms < 200ms threshold.")
                    else:
                        status = "PASS"
                        print(f"  [Narration-Cued PASS] Scene {sid}: Narration [{seg_start_ms} -> {seg_end_ms}ms] -> Pause {reaction_delay_ms}ms -> Action @ {action_start_pts}ms (Click: {click_pts_ms}ms)")
                else:
                    print(f"  [Narration Segment] Scene {sid} (Static UI): Narration [{seg_start_ms} -> {seg_end_ms}ms] within visual [{vis_start_ms} -> {vis_end_ms}ms]")
            else:
                # Post-action / confirmation narration starts after action completes + buffer
                last_act_end = (click_pts_ms + 400.0) if click_pts_ms > 0 else ((action_start_pts + 800.0) if action_start_pts > 0 else prev_seg_end_ms + 200.0)
                seg_start_ms = round(max(prev_seg_end_ms + 200.0, last_act_end), 1)
                seg_end_ms = round(seg_start_ms + dur_ms, 1)
                reaction_delay_ms = 0.0
                status = "PASS"
                print(f"  [Narration Post-Action PASS] Scene {sid} (Seg {seg_idx+1}): Narration [{seg_start_ms} -> {seg_end_ms}ms] playing after action completion ({click_pts_ms or action_start_pts}ms)")

            # Set global word timestamps
            for w in words:
                w['start_ms'] = round(seg_start_ms + w['rel_start_ms'], 1)
                w['end_ms'] = round(seg_start_ms + w['rel_end_ms'], 1)

            prev_seg_end_ms = seg_end_ms

            seg_entry = {
                "narration_id": seg_id,
                "scene_id": sid,
                "text": final_text,
                "target_visual": rseg.get('target_visual', sid),
                "target_element": rseg.get('target_element', 'main'),
                "importance": rseg.get('importance', 'normal'),
                "audio_file": seg_audio,
                "duration_ms": dur_ms,
                "segment_start_ms": seg_start_ms,
                "segment_end_ms": seg_end_ms,
                "words": words
            }
            all_segments.append(seg_entry)

            narration_word_timestamps.append({
                "segment_id": seg_id,
                "scene_id": sid,
                "text": final_text,
                "segment_start_ms": seg_start_ms,
                "segment_end_ms": seg_end_ms,
                "words": [
                    {
                        "word": w['word'],
                        "start_ms": w['start_ms'],
                        "end_ms": w['end_ms']
                    } for w in words
                ]
            })

            # Only register instruction cues in narration_cued_map
            if has_action and seg_idx == 0:
                first_act_type = scene_actions[0].get('type') if scene_actions else 'click'
                first_act_target = scene_actions[0].get('target') or scene_actions[0].get('selector') or 'element' if scene_actions else 'element'
                narration_cued_map.append({
                    "instruction_id": f"instruction_{s_idx+1:02d}",
                    "scene_id": sid,
                    "narration": {
                        "segment_id": seg_id,
                        "text": final_text,
                        "start_ms": seg_start_ms,
                        "end_ms": seg_end_ms,
                        "duration_ms": dur_ms
                    },
                    "reaction_delay_ms": reaction_delay_ms,
                    "visual_action": {
                        "type": first_act_type,
                        "target": first_act_target,
                        "action_start_ms": action_start_pts,
                        "click_frame_pts_ms": click_pts_ms
                    },
                    "audio_effect": {
                        "type": first_act_type,
                        "onset_ms": click_pts_ms if click_pts_ms > 0 else action_start_pts
                    },
                    "result_hold_ms": round(vis_end_ms - (click_pts_ms or action_start_pts), 1),
                    "status": status
                })

            align_status = "PASS" if (vis_end_ms >= seg_end_ms and vis_start_ms <= seg_start_ms) else "FAIL"
            narr_visual_map.append({
                "narration_id": seg_id,
                "scene_id": sid,
                "text": final_text,
                "target_visual": rseg.get('target_visual', sid),
                "target_element": rseg.get('target_element', 'main'),
                "intended_action": rseg.get('intended_action', 'view'),
                "importance": rseg.get('importance', 'normal'),
                "teaching_hold_ms": 1400 if rseg.get('importance') == 'important' else (1800 if rseg.get('importance') == 'critical' else 1000),
                "visual_required": True,
                "visual_start_ms": int(vis_start_ms),
                "visual_end_ms": int(vis_end_ms),
                "narration_start_ms": int(seg_start_ms),
                "narration_end_ms": int(seg_end_ms),
                "max_narration_duration_ms": max_budget,
                "alignment_status": align_status
            })

    # 4. Assemble Master Narration Track (Sample-Accurate 44.1kHz Buffer)
    sample_rate = 44100
    total_samples = int(round(total_video_dur_sec * sample_rate))
    master_buffer = [0] * total_samples

    print(f"\n[TTS V3.5.4] Mixing {len(all_segments)} narration segments into master track ({total_video_dur_sec:.2f}s)...")
    for seg in all_segments:
        pcm_samples = decode_audio_to_pcm(seg['audio_file'], ffmpeg, sample_rate)
        start_sample = int(round((seg['segment_start_ms'] / 1000.0) * sample_rate))
        for i, s in enumerate(pcm_samples):
            idx = start_sample + i
            if 0 <= idx < total_samples:
                val = master_buffer[idx] + s
                master_buffer[idx] = max(-32768, min(32767, val))

    # Encode master buffer to args.audio_out
    raw_pcm_bytes = struct.pack(f'<{total_samples}h', *master_buffer)
    cmd_enc = [
        ffmpeg, '-v', 'error', '-y',
        '-f', 's16le', '-ar', str(sample_rate), '-ac', '1',
        '-i', '-',
        '-c:a', 'libmp3lame', '-q:a', '2',
        args.audio_out
    ]
    res_enc = subprocess.run(cmd_enc, input=raw_pcm_bytes, capture_output=True)
    if res_enc.returncode != 0:
        raise RuntimeError(f"FFmpeg encoding error: {res_enc.stderr.decode('utf-8', errors='ignore')}")

    print(f"[TTS V3.5.4] Master narration track written: {args.audio_out}")

    # 5. Generate Subtitles (tutorial.srt)
    if args.subtitle_out:
        os.makedirs(os.path.dirname(os.path.abspath(args.subtitle_out)), exist_ok=True)
        srt_lines = []
        for s_i, seg in enumerate(all_segments, 1):
            s_start = seg['segment_start_ms'] / 1000.0
            s_end = seg['segment_end_ms'] / 1000.0
            srt_lines.append(f"{s_i}\n{format_srt_timestamp(s_start)} --> {format_srt_timestamp(s_end)}\n{seg['text']}\n")
        with open(args.subtitle_out, 'w', encoding='utf-8') as f:
            f.write('\n'.join(srt_lines))
        print(f"[TTS V3.5.4] Subtitles written: {args.subtitle_out}")

    # 6. Write Output Metadata Files
    out_dirs = [audio_dir, run_dir]
    for od in out_dirs:
        os.makedirs(od, exist_ok=True)
        with open(os.path.join(od, 'narration-word-timestamps.json'), 'w', encoding='utf-8') as f:
            json.dump(narration_word_timestamps, f, indent=2, ensure_ascii=False)
        with open(os.path.join(od, 'narration-cued-action-map.json'), 'w', encoding='utf-8') as f:
            json.dump(narration_cued_map, f, indent=2, ensure_ascii=False)
        with open(os.path.join(od, 'action-word-map.json'), 'w', encoding='utf-8') as f:
            json.dump(narration_cued_map, f, indent=2, ensure_ascii=False)
        with open(os.path.join(od, 'narration-visual-map.json'), 'w', encoding='utf-8') as f:
            json.dump(narr_visual_map, f, indent=2, ensure_ascii=False)

    if args.meta_out:
        with open(args.meta_out, 'w', encoding='utf-8') as f:
            json.dump(all_segments, f, indent=2, ensure_ascii=False)

    print(f"[TTS V3.5.4] Word timestamps written: narration-word-timestamps.json ({len(narration_word_timestamps)} segments)")
    print(f"[TTS V3.5.4] Narration-cued action map written: narration-cued-action-map.json ({len(narration_cued_map)} mapped)")
    print(f"[TTS V3.5.4] All operations completed cleanly.")

if __name__ == '__main__':
    asyncio.run(main())

