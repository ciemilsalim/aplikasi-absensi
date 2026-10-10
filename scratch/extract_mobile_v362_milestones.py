import json, os, subprocess
import imageio_ffmpeg
ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()

video = '.tutorial-video/runs/run-20261003-parent-leave-v362-mobile/final/final-tutorial-mobile.mp4'
timeline_file = '.tutorial-video/runs/run-20261003-parent-leave-v362-mobile/video-master-timeline.json'
out_dir = '.tutorial-video/runs/run-20261003-parent-leave-v362-mobile/visual-playback-audit'
os.makedirs(out_dir, exist_ok=True)

with open(timeline_file, 'r', encoding='utf-8') as f:
    tl = json.load(f)

milestones_summary = []

for idx, scene in enumerate(tl['scenes'], start=1):
    sid = scene['scene_id']
    s_start = scene['visual_start_ms'] / 1000.0
    s_end = scene['visual_end_ms'] / 1000.0
    n_start = scene['narration_start_ms'] / 1000.0
    n_end = scene['narration_end_ms'] / 1000.0
    react_ms = scene['reaction_delay_ms'] / 1000.0
    act_start = scene['action_start_ms'] / 1000.0
    click_ms = scene.get('click_frame_pts_ms', 0)
    if click_ms and click_ms > 0:
        t_click = click_ms / 1000.0
    else:
        t_click = act_start + 0.8

    milestones = [
        ('1_narr_start', n_start + 0.1),
        ('2_narr_mid_sub', (n_start + n_end) / 2.0),
        ('3_reaction_pause', n_end + react_ms * 0.5),
        ('4_camera_target', max(s_start + 0.1, act_start - 0.05)),
        ('5_cursor_settle', max(act_start, t_click - 0.2)),
        ('6_action_tap', t_click + 0.05),
        ('7_result_hold', s_end - 0.2)
    ]

    for name, ts in milestones:
        out_name = f"{sid}_{name}.png"
        out_path = os.path.join(out_dir, out_name)
        cmd = [
            ffmpeg, '-y', '-ss', f"{ts:.3f}", '-i', video,
            '-vframes', '1', '-q:v', '2', out_path
        ]
        subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        milestones_summary.append({
            'scene_id': sid,
            'milestone': name,
            'timestamp_sec': round(ts, 3),
            'frame_path': out_path
        })

with open(os.path.join(out_dir, 'playback-audit-summary.json'), 'w', encoding='utf-8') as f:
    json.dump(milestones_summary, f, indent=2)

print(f"Extracted {len(milestones_summary)} milestone frames into {out_dir}")
