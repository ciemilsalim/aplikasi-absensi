import re, glob, os, json

files = [
    ('.tutorial-video/runs/run-20261003-parent-leave-v36-desktop/desktop/recording/canonical-timeline.json', 'Desktop canonical-timeline.json'),
    ('.tutorial-video/runs/run-20261003-parent-leave-v36-mobile/mobile/recording/canonical-timeline.json', 'Mobile canonical-timeline.json'),
    ('.tutorial-video/run-20261003-parent-leave-v36/desktop/frame-true-av-qa.json', 'Desktop frame-true-av-qa.json'),
    ('.tutorial-video/run-20261003-parent-leave-v36/mobile/frame-true-av-qa.json', 'Mobile frame-true-av-qa.json'),
    ('.tutorial-video/runs/run-20261003-parent-leave-v36-desktop/desktop/video/render-provenance.json', 'Desktop render-provenance.json'),
    ('.tutorial-video/runs/run-20261003-parent-leave-v36-mobile/mobile/video/render-provenance.json', 'Mobile render-provenance.json'),
    ('.tutorial-video/runs/run-20261003-parent-leave-v36-desktop/desktop/audio/audio_meta.json', 'Desktop audio_meta.json'),
    ('.tutorial-video/runs/run-20261003-parent-leave-v36-mobile/mobile/audio/audio_meta.json', 'Mobile audio_meta.json'),
    ('.tutorial-video/final-production-report.md', 'final-production-report.md')
]

for fpath, name in files:
    if not os.path.isfile(fpath):
        print(f"MISSING: {fpath}")
        continue
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    decimal_ms_text = re.findall(r'(\d+\.\d+)\s*ms\b', content)
    decimal_ms_json = re.findall(r'"([a-zA-Z0-9_]*ms)"\s*:\s*(\d+\.\d+)', content)
    
    print(f"\nAudit: {name} ({fpath})")
    if decimal_ms_text:
        print(f"  [WARN] Decimal ms text found ({len(decimal_ms_text)}): {decimal_ms_text[:5]}")
    else:
        print(f"  [PASS] No decimal ms in text.")
        
    if decimal_ms_json:
        print(f"  [WARN] Decimal ms in JSON fields ({len(decimal_ms_json)}): {decimal_ms_json[:5]}")
    else:
        print(f"  [PASS] All JSON *_ms fields are integer.")

