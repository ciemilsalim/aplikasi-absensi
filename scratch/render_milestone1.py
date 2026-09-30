import imageio_ffmpeg
import os
import shutil
import subprocess
import sys

def main():
    ffmpeg = shutil.which('ffmpeg') or imageio_ffmpeg.get_ffmpeg_exe()
    
    recording_video = os.path.abspath('.tutorial-video/recording/raw-recording.webm')
    narration_audio = os.path.abspath('.tutorial-video/audio/narration.mp3')
    subtitles_srt = os.path.abspath('.tutorial-video/subtitle/tutorial.srt')
    
    render_dir = os.path.abspath('.tutorial-video/render')
    final_dir = os.path.abspath('.tutorial-video/final')
    os.makedirs(render_dir, exist_ok=True)
    os.makedirs(final_dir, exist_ok=True)
    
    final_mp4 = os.path.join(final_dir, 'tutorial-jurnal-guru-v1.mp4')
    
    sub_filter_path = subtitles_srt.replace('\\', '/').replace(':', '\\:')
    
    # Filter complex: tpad clones final frame for 8s to match 53s audio, then overlay subtitle
    filter_complex = f"[0:v]tpad=stop_mode=clone:stop_duration=8,subtitles='{sub_filter_path}':force_style='FontSize=20,PrimaryColour=&H00FFFFFF,OutlineColour=&H00000000,BorderStyle=1,Outline=2,Shadow=1,MarginV=30'[vout]"
    
    cmd = [
        ffmpeg, '-y',
        '-i', recording_video,
        '-i', narration_audio,
        '-filter_complex', filter_complex,
        '-map', '[vout]',
        '-map', '1:a:0',
        '-c:v', 'libx264',
        '-preset', 'medium',
        '-crf', '20',
        '-pix_fmt', 'yuv420p',
        '-c:a', 'aac',
        '-b:a', '192k',
        '-shortest',
        '-movflags', '+faststart',
        final_mp4
    ]
    
    print('Executing FFmpeg render command...')
    print('Command:', ' '.join(cmd))
    res = subprocess.run(cmd, capture_output=True, text=True)
    
    if res.returncode != 0:
        print('FFmpeg Error output:')
        print(res.stderr)
        sys.exit(1)
        
    print('Successfully rendered final video:', final_mp4)

if __name__ == '__main__':
    main()
