#!/usr/bin/env python
import argparse, json, os, shutil, subprocess, sys

p = argparse.ArgumentParser(description='Validate tutorial final video and required artifacts.')
p.add_argument('--video', required=True)
p.add_argument('--plan')
p.add_argument('--subtitles')
args = p.parse_args()

errors = []
if not os.path.isfile(args.video): errors.append('Final video does not exist.')
if args.plan and not os.path.isfile(args.plan): errors.append('Tutorial plan does not exist.')
if args.subtitles and not os.path.isfile(args.subtitles): errors.append('Subtitle file does not exist.')

ffprobe = shutil.which('ffprobe')
if ffprobe and os.path.isfile(args.video):
    r = subprocess.run([ffprobe, '-v', 'error', '-show_entries', 'format=duration', '-show_entries', 'stream=codec_type', '-of', 'json', args.video], capture_output=True, text=True)
    if r.returncode != 0: errors.append('ffprobe could not read the final video.')
    else:
        try:
            data = json.loads(r.stdout)
            types = {s.get('codec_type') for s in data.get('streams', [])}
            if 'video' not in types: errors.append('No video stream found.')
            if 'audio' not in types: errors.append('No audio stream found.')
        except Exception as e: errors.append(f'Unable to parse ffprobe output: {e}')
else:
    print('Warning: ffprobe not available; basic file validation only.')

if errors:
    print('FAIL')
    for e in errors: print('-', e)
    sys.exit(1)
print('PASS')
