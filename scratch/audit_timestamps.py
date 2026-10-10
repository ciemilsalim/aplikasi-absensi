import json
import os
import re

desktop_run_dir = '.tutorial-video/runs/run-20261003-parent-leave-v36-desktop/desktop'
mobile_run_dir = '.tutorial-video/runs/run-20261003-parent-leave-v36-mobile/mobile'
evidence_dir = '.tutorial-video/run-20261003-parent-leave-v36'

files_to_audit = [
    # Desktop
    ('Desktop canonical-timeline.json', os.path.join(desktop_run_dir, 'recording', 'canonical-timeline.json')),
    ('Desktop frame-true-av-qa.json', os.path.join(evidence_dir, 'desktop', 'frame-true-av-qa.json')),
    ('Desktop audio_meta.json', os.path.join(desktop_run_dir, 'audio', 'audio_meta.json')),
    ('Desktop audio-provenance.json', os.path.join(desktop_run_dir, 'audio', 'audio-provenance.json')),
    ('Desktop render-provenance.json', os.path.join(desktop_run_dir, 'video', 'render-provenance.json')),
    # Mobile
    ('Mobile canonical-timeline.json', os.path.join(mobile_run_dir, 'recording', 'canonical-timeline.json')),
    ('Mobile frame-true-av-qa.json', os.path.join(evidence_dir, 'mobile', 'frame-true-av-qa.json')),
    ('Mobile audio_meta.json', os.path.join(mobile_run_dir, 'audio', 'audio_meta.json')),
    ('Mobile audio-provenance.json', os.path.join(mobile_run_dir, 'audio', 'audio-provenance.json')),
    ('Mobile render-provenance.json', os.path.join(mobile_run_dir, 'video', 'render-provenance.json')),
    # Final production report
    ('final-production-report.md', '.tutorial-video/final-production-report.md'),
]

print("=== CHECKING FILES EXISTENCE ===")
for name, path in files_to_audit:
    exists = os.path.isfile(path)
    print(f"[{'OK' if exists else 'MISSING'}] {name}: {path}")

# Load and inspect frame-true-av-qa.json
print("\n=== FRAME-TRUE-AV-QA.JSON CONTENT ===")
for variant, path in [('desktop', os.path.join(evidence_dir, 'desktop', 'frame-true-av-qa.json')),
                      ('mobile', os.path.join(evidence_dir, 'mobile', 'frame-true-av-qa.json'))]:
    if os.path.isfile(path):
        data = json.load(open(path, 'r', encoding='utf-8'))
        print(f"\nVariant: {variant}")
        clicks = data.get('clicks', [])
        for c in clicks:
            print(f"  Event: {c.get('event_id')}")
            for k, v in c.items():
                print(f"    {k}: {v} (type: {type(v).__name__})")
