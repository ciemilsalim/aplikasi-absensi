#!/usr/bin/env python
import argparse, asyncio, json, math, os, sys
import subprocess, shutil

try:
    import edge_tts
except ImportError:
    print('edge-tts is not installed. Install with: python -m pip install edge-tts', file=sys.stderr)
    sys.exit(3)

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
    # Fallback to ffmpeg
    ffmpeg = shutil.which('ffmpeg')
    if not ffmpeg:
        try:
            import imageio_ffmpeg
            ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
        except Exception:
            pass
    if ffmpeg:
        r = subprocess.run([ffmpeg, '-i', audio_path], capture_output=True, text=True)
        import re
        match = re.search(r'Duration:\s*(\d+):(\d+):([\d\.]+)', r.stderr)
        if match:
            h, m, s = match.groups()
            return int(h) * 3600 + int(m) * 60 + float(s)
    return 3.0 # fallback default

def split_narration_to_subtitles(text, start_time, duration):
    # Split narration into 1-2 sentence lines max ~80 chars
    text = text.strip()
    if not text:
        return []
    import re
    sentences = [s.strip() for s in re.split(r'(?<=[.!?])\s+', text) if s.strip()]
    if not sentences:
        sentences = [text]
        
    total_chars = sum(len(s) for s in sentences)
    if total_chars == 0:
        return []
        
    subs = []
    curr_time = start_time
    for sentence in sentences:
        ratio = len(sentence) / total_chars
        s_dur = max(1.5, duration * ratio)
        end_time = curr_time + s_dur
        subs.append({
            'start': curr_time,
            'end': end_time,
            'text': sentence
        })
        curr_time = end_time
    return subs

async def generate_scene_audio(scene, out_dir, voice):
    scene_id = scene.get('scene_id') or scene.get('id', 'scene')
    text = scene.get('narration', '').strip()
    audio_file = os.path.join(out_dir, f"{scene_id}.mp3")
    if not text:
        # Create silent 1 second mp3 if empty
        text = "..."
    communicate = edge_tts.Communicate(text, voice)
    await communicate.save(audio_file)
    duration = get_audio_duration(audio_file)
    return {
        'scene_id': scene_id,
        'narration': text,
        'audio_file': audio_file,
        'duration': duration
    }

async def main():
    parser = argparse.ArgumentParser(description='Professional Indonesian Narration & Subtitle Generator.')
    parser.add_argument('--plan', help='Path to tutorial-plan.json')
    parser.add_argument('--input', help='Direct text file input fallback')
    parser.add_argument('--audio-out', required=True, help='Path for output merged audio file')
    parser.add_argument('--subtitle-out', help='Path for output SRT subtitle file')
    parser.add_argument('--meta-out', help='Path for audio timing metadata JSON')
    parser.add_argument('--voice', default='id-ID-ArdiNeural', help='TTS Voice name')
    args = parser.parse_args()

    audio_dir = os.path.dirname(os.path.abspath(args.audio_out))
    os.makedirs(audio_dir, exist_ok=True)
    if args.subtitle_out:
        os.makedirs(os.path.dirname(os.path.abspath(args.subtitle_out)), exist_ok=True)

    scenes_meta = []
    
    if args.plan and os.path.isfile(args.plan):
        plan = json.load(open(args.plan, 'r', encoding='utf-8'))
        voice = plan.get('voice', args.voice)
        scenes = plan.get('scenes', [])
        
        intro = plan.get('intro', {})
        intro_dur = intro.get('duration', 4.0) if intro.get('enabled', True) else 0.0
        
        current_time = intro_dur # Start subtitles after intro titlecard
        srt_entries = []
        srt_index = 1
        
        audio_files_to_concat = []

        # If intro is enabled, add silence for intro duration
        ffmpeg = shutil.which('ffmpeg')
        if not ffmpeg:
            import imageio_ffmpeg
            ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()

        for scene in scenes:
            meta = await generate_scene_audio(scene, audio_dir, voice)
            scenes_meta.append(meta)
            audio_files_to_concat.append(meta['audio_file'])
            
            # Subtitles for scene
            subs = split_narration_to_subtitles(meta['narration'], current_time, meta['duration'])
            for s in subs:
                srt_entries.append(
                    f"{srt_index}\n{format_srt_timestamp(s['start'])} --> {format_srt_timestamp(s['end'])}\n{s['text']}\n"
                )
                srt_index += 1
            current_time += meta['duration']

        # Concatenate audio files using FFmpeg concat filter / file list
        concat_list_path = os.path.join(audio_dir, 'audio_concat.txt')
        with open(concat_list_path, 'w', encoding='utf-8') as f:
            if intro_dur > 0:
                # generate silent audio for intro if ffmpeg can
                intro_silent = os.path.join(audio_dir, 'intro_silence.mp3')
                cmd_silence = [ffmpeg, '-y', '-f', 'lavfi', '-i', f'anullsrc=r=44100:cl=stereo', '-t', str(intro_dur), '-q:a', '9', intro_silent]
                subprocess.run(cmd_silence, capture_output=True)
                f.write(f"file '{intro_silent.replace('\\', '/')}'\n")
            for af in audio_files_to_concat:
                f.write(f"file '{af.replace('\\', '/')}'\n")

        cmd_concat = [ffmpeg, '-y', '-f', 'concat', '-safe', '0', '-i', concat_list_path, '-c', 'copy', args.audio_out]
        subprocess.run(cmd_concat, check=True)

        if args.subtitle_out:
            with open(args.subtitle_out, 'w', encoding='utf-8') as f:
                f.write('\n'.join(srt_entries))
            print(f"Subtitles written: {args.subtitle_out}")

        if args.meta_out:
            with open(args.meta_out, 'w', encoding='utf-8') as f:
                json.dump(scenes_meta, f, indent=2)

    elif args.input and os.path.isfile(args.input):
        text = open(args.input, 'r', encoding='utf-8').read().strip()
        communicate = edge_tts.Communicate(text, args.voice)
        await communicate.save(args.audio_out)
        duration = get_audio_duration(args.audio_out)
        if args.subtitle_out:
            subs = split_narration_to_subtitles(text, 0.0, duration)
            srt_entries = [f"{i+1}\n{format_srt_timestamp(s['start'])} --> {format_srt_timestamp(s['end'])}\n{s['text']}\n" for i, s in enumerate(subs)]
            with open(args.subtitle_out, 'w', encoding='utf-8') as f:
                f.write('\n'.join(srt_entries))
    else:
        raise SystemExit("Error: Must provide either --plan or --input")

    print(f"Narration generated successfully: {args.audio_out}")

if __name__ == '__main__':
    asyncio.run(main())
