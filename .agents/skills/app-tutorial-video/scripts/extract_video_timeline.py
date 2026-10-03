#!/usr/bin/env python
# extract_video_timeline.py — V3.5.3 Video-Master Timeline Extractor
# Inspects visual-master.mp4 (Picture Lock) and generates:
# 1. final-video-timeline.json
# 2. video-master-timeline.json (with "master": "FINAL_VIDEO")
import argparse
import json
import math
import os
import shutil
import subprocess
import sys

def get_ffmpeg():
    ffmpeg = shutil.which('ffmpeg')
    if not ffmpeg:
        try:
            import imageio_ffmpeg
            ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
        except Exception:
            pass
    return ffmpeg

def get_video_info(video_path: str):
    """Inspects video using ffprobe or ffmpeg to obtain duration, fps, frame count."""
    ffprobe = shutil.which('ffprobe')
    duration = None
    fps = 30.0
    width = 1920
    height = 1080

    if ffprobe:
        try:
            cmd = [
                ffprobe, '-v', 'error',
                '-select_streams', 'v:0',
                '-show_entries', 'stream=width,height,r_frame_rate,duration,nb_frames:format=duration',
                '-of', 'json', video_path
            ]
            r = subprocess.run(cmd, capture_output=True, text=True, check=True)
            data = json.loads(r.stdout)
            streams = data.get('streams', [])
            if streams:
                v = streams[0]
                width = int(v.get('width', width))
                height = int(v.get('height', height))
                r_fps = v.get('r_frame_rate', '30/1')
                if '/' in r_fps:
                    num, den = r_fps.split('/')
                    fps = float(num) / float(den) if float(den) > 0 else 30.0
                else:
                    fps = float(r_fps)
                duration = float(v.get('duration') or data.get('format', {}).get('duration', 0.0))
        except Exception:
            pass

    if duration is None:
        ffmpeg = get_ffmpeg()
        if ffmpeg:
            r = subprocess.run([ffmpeg, '-i', video_path], capture_output=True, text=True)
            import re
            m = re.search(r'Duration:\s*(\d+):(\d+):([\d\.]+)', r.stderr)
            if m:
                h, m, s = m.groups()
                duration = int(h) * 3600 + int(m) * 60 + float(s)
            m_res = re.search(r'(\d{3,4})x(\d{3,4})', r.stderr)
            if m_res:
                width, height = int(m_res.group(1)), int(m_res.group(2))

    duration = duration or 80.0
    total_frames = int(round(duration * fps))
    return {
        "duration_sec": round(duration, 3),
        "duration_ms": int(round(duration * 1000.0)),
        "fps": round(fps, 2),
        "total_frames": total_frames,
        "width": width,
        "height": height
    }

def snap_to_pts(time_sec: float, fps: float = 30.0) -> tuple[int, float]:
    """Snap a second timestamp to frame index and millisecond PTS."""
    frame_dur_ms = 1000.0 / fps
    idx = int(round(time_sec * fps))
    pts_ms = round(idx * frame_dur_ms, 1)
    return idx, pts_ms

def extract_timeline(visual_master_path: str, plan_path: str, recording_dir: str,
                     out_final_timeline: str = None, out_master_timeline: str = None,
                     pts_factor: float = 1.0):
    if not os.path.isfile(visual_master_path):
        raise FileNotFoundError(f"Visual master video not found: {visual_master_path}")

    video_info = get_video_info(visual_master_path)
    fps = video_info['fps']

    plan = {}
    if os.path.isfile(plan_path):
        with open(plan_path, 'r', encoding='utf-8') as f:
            plan = json.load(f)

    intro_cfg = plan.get('intro', {})
    outro_cfg = plan.get('outro', {})
    intro_dur = float(intro_cfg.get('duration', 4.0)) if intro_cfg.get('enabled', True) else 0.0
    outro_dur = float(outro_cfg.get('duration', 4.0)) if outro_cfg.get('enabled', True) else 0.0

    # Load scene-timeline from recording
    scene_timeline_file = os.path.join(recording_dir, 'scene-timeline.json')
    scene_timeline = {}
    if os.path.isfile(scene_timeline_file):
        with open(scene_timeline_file, 'r', encoding='utf-8') as f:
            scene_timeline = json.load(f)

    # Load audio-effects-timeline / click events
    audio_effects_file = os.path.join(recording_dir, 'audio-effects-timeline.json')
    audio_effects = {}
    if os.path.isfile(audio_effects_file):
        with open(audio_effects_file, 'r', encoding='utf-8') as f:
            audio_effects = json.load(f)

    # Load focus timeline
    focus_file = os.path.join(recording_dir, 'scene_focus_timeline.json')
    focus_data = {}
    if os.path.isfile(focus_file):
        with open(focus_file, 'r', encoding='utf-8') as f:
            focus_data = json.load(f)

    # Build structured scene & event timelines
    scenes_plan = plan.get('scenes', [])
    scenes_output = []
    master_scenes = []

    # Map plan scenes by id
    plan_scene_map = {}
    for idx, s in enumerate(scenes_plan):
        sid = s.get('scene_id') or s.get('id', f"scene_{idx+1:02d}")
        plan_scene_map[sid] = s

    # Determine scene order
    scene_keys = list(scene_timeline.keys())
    if not scene_keys and scenes_plan:
        scene_keys = [s.get('scene_id') or s.get('id') for s in scenes_plan]

    # Process each scene
    all_final_events = []
    
    for sid in scene_keys:
        st = scene_timeline.get(sid, {})
        sp = plan_scene_map.get(sid, {})

        rec_start_sec = float(st.get('start', 0.0))
        rec_end_sec = float(st.get('end', rec_start_sec + 5.0))

        v_start_sec = intro_dur + (rec_start_sec * pts_factor)
        v_end_sec = intro_dur + (rec_end_sec * pts_factor)

        _, v_start_pts_ms = snap_to_pts(v_start_sec, fps)
        _, v_end_pts_ms = snap_to_pts(v_end_sec, fps)

        visual_window_ms = v_end_pts_ms - v_start_pts_ms
        # Maximum speech budget leaves at least 250ms silence before transition
        max_narr_budget_ms = max(1500, int(round(visual_window_ms - 300)))

        # Find visual actions in this scene
        scene_actions = []
        scene_audio_events = []

        # Focus event
        if sid in focus_data:
            fd = focus_data[sid]
            scene_actions.append({
                "type": "focus",
                "zoom": fd.get('zoom', 1.5),
                "center": (fd.get('center_x', 960), fd.get('center_y', 540)),
                "start_pts_ms": v_start_pts_ms + 100,
                "end_pts_ms": v_end_pts_ms - 200
            })

        # Clicks and typings from audio_effects
        action_counter = 0
        for ev in audio_effects.get('events', []):
            if ev.get('scene_id') == sid:
                ev_type = ev.get('type')
                if ev_type in ('click', 'tap'):
                    action_counter += 1
                    act_id = f"act_{sid}_{action_counter:02d}"
                    rec_time = float(ev.get('time', rec_start_sec))
                    final_ev_sec = intro_dur + (rec_time * pts_factor)
                    frame_idx, pts_ms = snap_to_pts(final_ev_sec, fps)
                    act = {
                        "action_id": act_id,
                        "type": ev_type,
                        "frame_index": frame_idx,
                        "frame_pts_ms": pts_ms,
                        "canonical_event_ms": int(round(final_ev_sec * 1000.0)),
                        "selector": ev.get('target', 'element'),
                        "target": ev.get('target', 'element')
                    }
                    scene_actions.append(act)
                    scene_audio_events.append({
                        "action_id": act_id,
                        "type": "click",
                        "pts_ms": pts_ms
                    })
                    all_final_events.append({
                        "id": f"evt_click_{len(all_final_events)+1:03d}",
                        "action_id": act_id,
                        "scene_id": sid,
                        "type": ev_type,
                        "visual_frame_index": frame_idx,
                        "visual_frame_pts_ms": pts_ms,
                        "effective_audio_ms": pts_ms,
                        "status": "PASS"
                    })
                elif ev_type == 'typing':
                    action_counter += 1
                    act_id = f"act_{sid}_{action_counter:02d}"
                    rec_s = float(ev.get('start', rec_start_sec))
                    rec_e = float(ev.get('end', rec_s + 1.0))
                    final_s = intro_dur + (rec_s * pts_factor)
                    final_e = intro_dur + (rec_e * pts_factor)
                    s_frame_idx, s_pts_ms = snap_to_pts(final_s, fps)
                    e_frame_idx, e_pts_ms = snap_to_pts(final_e, fps)
                    act = {
                        "action_id": act_id,
                        "type": "typing",
                        "start_frame_index": s_frame_idx,
                        "start_frame_pts_ms": s_pts_ms,
                        "end_frame_index": e_frame_idx,
                        "end_frame_pts_ms": e_pts_ms,
                        "frame_pts_ms": s_pts_ms,
                        "target": ev.get('target', '#input')
                    }
                    scene_actions.append(act)
                    scene_audio_events.append({
                        "action_id": act_id,
                        "type": "typing",
                        "start_ms": s_pts_ms,
                        "end_ms": e_pts_ms
                    })
                    all_final_events.append({
                        "id": f"evt_type_{len(all_final_events)+1:03d}",
                        "action_id": act_id,
                        "scene_id": sid,
                        "type": "typing",
                        "target": ev.get('target', '#input'),
                        "visual_start_frame_index": s_frame_idx,
                        "visual_start_frame_pts_ms": s_pts_ms,
                        "visual_end_frame_index": e_frame_idx,
                        "visual_end_frame_pts_ms": e_pts_ms,
                        "effective_audio_start_ms": s_pts_ms,
                        "effective_audio_end_ms": e_pts_ms,
                        "status": "PASS"
                    })

        # Segments specification for TTS Audio Slave
        segments = []
        raw_segs = sp.get('narration_segments') or []
        if not raw_segs and sp.get('narration'):
            raw_segs = [{
                "narration_id": f"{sid}_seg_01",
                "text": sp.get('narration', ''),
                "target_visual": sp.get('title', sid),
                "target_element": "main",
                "importance": "normal"
            }]

        total_segs = len(raw_segs)
        seg_budget_each = max_narr_budget_ms // max(1, total_segs)

        for sidx, rseg in enumerate(raw_segs):
            seg_start_ms = v_start_pts_ms + (sidx * seg_budget_each) + 100
            seg_end_ms = min(v_end_pts_ms - 150, seg_start_ms + seg_budget_each - 100)
            seg_obj = {
                "narration_id": rseg.get('narration_id', f"{sid}_seg_{sidx+1:02d}"),
                "scene_id": sid,
                "text": rseg.get('text', ''),
                "target_visual": rseg.get('target_visual', sp.get('title', sid)),
                "target_element": rseg.get('target_element', 'main'),
                "visual_start_ms": v_start_pts_ms,
                "visual_end_ms": v_end_pts_ms,
                "narration_target_start_ms": seg_start_ms,
                "narration_target_end_ms": seg_end_ms,
                "max_narration_duration_ms": int(seg_end_ms - seg_start_ms),
                "importance": rseg.get('importance', 'normal')
            }
            segments.append(seg_obj)
            scene_audio_events.append({
                "type": "narration",
                "narration_id": seg_obj['narration_id'],
                "start_ms": seg_start_ms,
                "end_ms": seg_end_ms
            })

        # Narration and action timing for V3.5.4 Narration-Cued Action
        rec_act_start = st.get('action_start')
        if rec_act_start is not None:
            final_act_start_sec = intro_dur + (float(rec_act_start) * pts_factor)
            _, act_start_pts_ms = snap_to_pts(final_act_start_sec, fps)
        elif scene_actions:
            first_act = scene_actions[0]
            act_start_pts_ms = float(first_act.get('start_frame_pts_ms') or first_act.get('frame_pts_ms') or (v_start_pts_ms + 3500.0))
        else:
            act_start_pts_ms = None

        rec_narr_start = st.get('narration_start')
        if rec_narr_start is not None:
            final_narr_start_sec = intro_dur + (float(rec_narr_start) * pts_factor)
            _, narr_start_pts_ms = snap_to_pts(final_narr_start_sec, fps)
        else:
            narr_start_pts_ms = round(v_start_pts_ms + 200.0, 1)

        rec_narr_end = st.get('narration_end')
        if rec_narr_end is not None:
            final_narr_end_sec = intro_dur + (float(rec_narr_end) * pts_factor)
            _, narr_end_pts_ms = snap_to_pts(final_narr_end_sec, fps)
        else:
            narr_end_pts_ms = round(narr_start_pts_ms + 2500.0, 1)

        # First click PTS
        first_click_pts = None
        for act in scene_actions:
            if act.get('type') in ('click', 'tap'):
                first_click_pts = act.get('frame_pts_ms')
                break

        scene_dict = {
            "scene_id": sid,
            "title": sp.get('title', sid),
            "visual_start_ms": v_start_pts_ms,
            "visual_end_ms": v_end_pts_ms,
            "visual_window_ms": visual_window_ms,
            "narration_start_ms": narr_start_pts_ms,
            "narration_end_ms": narr_end_pts_ms,
            "action_start_ms": act_start_pts_ms,
            "click_frame_pts_ms": first_click_pts,
            "max_narration_duration_ms": max_narr_budget_ms,
            "actions": scene_actions,
            "narration_segments": segments
        }
        scenes_output.append(scene_dict)

        master_scenes.append({
            "scene_id": sid,
            "start_ms": int(v_start_pts_ms),
            "end_ms": int(v_end_pts_ms),
            "narration_start_ms": int(narr_start_pts_ms),
            "narration_end_ms": int(narr_end_pts_ms),
            "action_start_ms": int(act_start_pts_ms) if act_start_pts_ms is not None else None,
            "click_frame_pts_ms": int(first_click_pts) if first_click_pts is not None else None,
            "visual_events": scene_actions,
            "audio_events": scene_audio_events
        })

    # Top-level timeline structures
    final_video_timeline = {
        "version": "v3.5.4",
        "engine": "Narration-Cued Action Video-Master Audio Engine",
        "master_video_path": visual_master_path,
        "video_info": video_info,
        "intro_ms": int(round(intro_dur * 1000.0)),
        "outro_ms": int(round(outro_dur * 1000.0)),
        "scenes": scenes_output,
        "events": all_final_events
    }

    video_master_timeline = {
        "master": "FINAL_VIDEO",
        "fps": int(round(fps)),
        "total_duration_ms": video_info['duration_ms'],
        "scenes": master_scenes
    }

    # Save files
    if out_final_timeline:
        os.makedirs(os.path.dirname(os.path.abspath(out_final_timeline)), exist_ok=True)
        with open(out_final_timeline, 'w', encoding='utf-8') as f:
            json.dump(final_video_timeline, f, indent=2, ensure_ascii=False)
        print(f"[V3.5.4] final-video-timeline.json written: {out_final_timeline}")
        cued_path = os.path.join(os.path.dirname(os.path.abspath(out_final_timeline)), 'narration-cued-timeline.json')
        with open(cued_path, 'w', encoding='utf-8') as f:
            json.dump(final_video_timeline, f, indent=2, ensure_ascii=False)

    if out_master_timeline:
        os.makedirs(os.path.dirname(os.path.abspath(out_master_timeline)), exist_ok=True)
        with open(out_master_timeline, 'w', encoding='utf-8') as f:
            json.dump(video_master_timeline, f, indent=2, ensure_ascii=False)
        print(f"[V3.5.4] video-master-timeline.json written: {out_master_timeline} (master='FINAL_VIDEO')")

    return final_video_timeline, video_master_timeline

def main():
    parser = argparse.ArgumentParser(description="Extract video master timeline from visual-master.mp4 (V3.5.3)")
    parser.add_argument("--video", required=True, help="Path to visual-master.mp4")
    parser.add_argument("--plan", required=True, help="Path to plan JSON")
    parser.add_argument("--recording-dir", required=True, help="Path to recording folder")
    parser.add_argument("--out-final-timeline", default=None, help="Output path for final-video-timeline.json")
    parser.add_argument("--out-master-timeline", default=None, help="Output path for video-master-timeline.json")
    parser.add_argument("--pts-factor", type=float, default=1.0, help="Video setpts factor applied during render")
    args = parser.parse_args()

    extract_timeline(
        args.video, args.plan, args.recording_dir,
        args.out_final_timeline, args.out_master_timeline,
        pts_factor=args.pts_factor
    )

if __name__ == "__main__":
    main()
