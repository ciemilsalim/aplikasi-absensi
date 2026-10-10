import asyncio
import json
import edge_tts

async def test_measure():
    with open('.tutorial-video/plans/parent-leave-request-v36-desktop.json', 'r', encoding='utf-8') as f:
        plan = json.load(f)
    print("=== MEASURING NARRATION DURATIONS ===")
    for sc in plan['scenes']:
        narr = sc.get('narration', '')
        comm = edge_tts.Communicate(narr, 'id-ID-ArdiNeural', boundary='WordBoundary')
        dur_ticks = 0
        async for chunk in comm.stream():
            if chunk.get('type') == 'WordBoundary':
                dur_ticks = chunk['offset'] + chunk['duration']
        dur_ms = dur_ticks / 10000.0
        print(f"{sc['scene_id']}: {dur_ms:.1f}ms -> '{narr[:50]}'")

if __name__ == '__main__':
    asyncio.run(test_measure())
