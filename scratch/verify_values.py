import json, os

desktop_qa_path = '.tutorial-video/run-20261003-parent-leave-v36/desktop/frame-true-av-qa.json'
mobile_qa_path = '.tutorial-video/run-20261003-parent-leave-v36/mobile/frame-true-av-qa.json'

desktop_ct_path = '.tutorial-video/runs/run-20261003-parent-leave-v36-desktop/desktop/recording/canonical-timeline.json'
mobile_ct_path = '.tutorial-video/runs/run-20261003-parent-leave-v36-mobile/mobile/recording/canonical-timeline.json'

def verify_variant(variant, qa_path, ct_path):
    qa_data = json.load(open(qa_path, 'r', encoding='utf-8'))
    ct_data = json.load(open(ct_path, 'r', encoding='utf-8'))
    
    events_table = []
    clicks = qa_data.get('click_events', [])
    
    for c in clicks:
        eid = c['event_id']
        canon_ms = int(round(c['canonical_event_ms']))
        canon_sec = round(canon_ms / 1000.0, 3)
        
        pts_ms = int(round(c['visual_frame_pts_ms']))
        pts_sec = round(pts_ms / 1000.0, 3)
        
        audio_ms = int(round(c['audio_onset_ms']))
        audio_sec = round(audio_ms / 1000.0, 3)
        
        offset_ms = int(round(c['visual_audio_offset_ms']))
        
        # Verify conversions
        c_conv_diff = abs(canon_sec * 1000 - canon_ms)
        p_conv_diff = abs(pts_sec * 1000 - pts_ms)
        a_conv_diff = abs(audio_sec * 1000 - audio_ms)
        
        conv_ok = (c_conv_diff <= 1.0) and (p_conv_diff <= 1.0) and (a_conv_diff <= 1.0)
        
        calc_offset = abs(audio_ms - pts_ms)
        offset_match = (calc_offset == offset_ms)
        
        events_table.append({
            'event_id': eid,
            'canonical_event_ms': canon_ms,
            'canonical_event_sec': canon_sec,
            'visual_frame_pts_ms': pts_ms,
            'visual_frame_pts_sec': pts_sec,
            'actual_audio_onset_ms': audio_ms,
            'actual_audio_onset_sec': audio_sec,
            'visual_audio_offset_ms': offset_ms,
            'conversion_diff_max_ms': max(c_conv_diff, p_conv_diff, a_conv_diff),
            'conversion_pass': conv_ok,
            'offset_match': offset_match,
            'tier': c.get('tier', 'IDEAL'),
            'status': c.get('status', 'PASS')
        })
    return events_table

def verify_typing(variant, qa_path):
    qa_data = json.load(open(qa_path, 'r', encoding='utf-8'))
    typing_table = []
    for t in qa_data.get('typing_events', []):
        eid = t['event_id']
        target = t['target']
        
        canon_start_ms = int(round(t['canonical_start_ms']))
        canon_start_sec = round(canon_start_ms / 1000.0, 3)
        canon_end_ms = int(round(t['canonical_end_ms']))
        canon_end_sec = round(canon_end_ms / 1000.0, 3)
        
        vis_start_ms = int(round(t['visual_start_frame_pts_ms']))
        vis_start_sec = round(vis_start_ms / 1000.0, 3)
        vis_end_ms = int(round(t['visual_end_frame_pts_ms']))
        vis_end_sec = round(vis_end_ms / 1000.0, 3)
        
        act_start_ms = int(round(t['actual_audio_start_ms']))
        act_start_sec = round(act_start_ms / 1000.0, 3)
        act_end_ms = int(round(t['actual_audio_end_ms']))
        act_end_sec = round(act_end_ms / 1000.0, 3)
        
        start_off = int(round(t['start_offset_ms']))
        end_off = int(round(t['end_offset_ms']))
        
        calc_start_off = abs(act_start_ms - vis_start_ms)
        calc_end_off = abs(act_end_ms - vis_end_ms)
        
        typing_table.append({
            'event_id': eid,
            'target': target,
            'canonical_start_ms': canon_start_ms,
            'canonical_start_sec': canon_start_sec,
            'canonical_end_ms': canon_end_ms,
            'canonical_end_sec': canon_end_sec,
            'visual_start_pts_ms': vis_start_ms,
            'visual_start_pts_sec': vis_start_sec,
            'visual_end_pts_ms': vis_end_ms,
            'visual_end_pts_sec': vis_end_sec,
            'actual_audio_start_ms': act_start_ms,
            'actual_audio_start_sec': act_start_sec,
            'actual_audio_end_ms': act_end_ms,
            'actual_audio_end_sec': act_end_sec,
            'start_offset_ms': start_off,
            'end_offset_ms': end_off,
            'start_offset_match': (calc_start_off == start_off),
            'end_offset_match': (calc_end_off == end_off),
            'status': t.get('status', 'PASS')
        })
    return typing_table

desktop_events = verify_variant('desktop', desktop_qa_path, desktop_ct_path)
mobile_events = verify_variant('mobile', mobile_qa_path, mobile_ct_path)
desktop_typing = verify_typing('desktop', desktop_qa_path)
mobile_typing = verify_typing('mobile', mobile_qa_path)

print("=== DESKTOP EVENTS AUDIT ===")
for e in desktop_events:
    print(e)

print("\n=== MOBILE EVENTS AUDIT ===")
for e in mobile_events:
    print(e)

print("\n=== DESKTOP TYPING AUDIT ===")
for t in desktop_typing:
    print(t)

print("\n=== MOBILE TYPING AUDIT ===")
for t in mobile_typing:
    print(t)

