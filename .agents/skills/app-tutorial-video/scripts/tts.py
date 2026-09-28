#!/usr/bin/env python
import argparse, asyncio, os, sys

parser = argparse.ArgumentParser(description='Generate Indonesian voice-over with Edge TTS.')
parser.add_argument('--input', required=True, help='Text file')
parser.add_argument('--output', required=True, help='MP3 output')
parser.add_argument('--voice', default='id-ID-ArdiNeural', help='Voice name')
args = parser.parse_args()

try:
    import edge_tts
except ImportError:
    print('edge-tts is not installed. Install with: python -m pip install edge-tts', file=sys.stderr)
    sys.exit(3)

async def main():
    text = open(args.input, 'r', encoding='utf-8').read().strip()
    if not text:
        raise SystemExit('Input narration is empty.')
    communicate = edge_tts.Communicate(text, args.voice)
    await communicate.save(args.output)

asyncio.run(main())
