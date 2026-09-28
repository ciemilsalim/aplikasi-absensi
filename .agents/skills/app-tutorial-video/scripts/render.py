#!/usr/bin/env python
import argparse, os, shutil, subprocess, sys

p = argparse.ArgumentParser(description='Render tutorial video with FFmpeg.')
p.add_argument('--video', required=True)
p.add_argument('--audio', required=True)
p.add_argument('--output', required=True)
p.add_argument('--subtitles')
args = p.parse_args()

ffmpeg = shutil.which('ffmpeg')
if not ffmpeg:
    print('FFmpeg was not found on PATH.', file=sys.stderr)
    sys.exit(3)

os.makedirs(os.path.dirname(os.path.abspath(args.output)), exist_ok=True)
cmd = [ffmpeg, '-y', '-i', args.video, '-i', args.audio,
       '-map', '0:v:0', '-map', '1:a:0',
       '-c:v', 'libx264', '-preset', 'medium', '-crf', '20',
       '-c:a', 'aac', '-b:a', '192k', '-movflags', '+faststart']

if args.subtitles:
    # Escape Windows path for FFmpeg subtitles filter.
    sub = os.path.abspath(args.subtitles).replace('\\', '/').replace(':', '\\:')
    cmd += ['-vf', f"subtitles='{sub}'"]

cmd.append(args.output)
print('Running:', ' '.join(cmd))
subprocess.run(cmd, check=True)
print('Rendered:', args.output)
