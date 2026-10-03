#!/usr/bin/env python
# timeline.py — V3.5.2 Canonical Event Timeline & Continuous Motion Engine
# Single Source of Truth for Video, Audio SFX, Narration, Subtitles, and Cursor
import hashlib
import json
import math
import os
import sys

TIMELINE_VERSION = "v3.5.2.5"

def compute_hash(data) -> str:
    """Compute deterministic SHA256 hex digest of dictionary or string (first 16 chars)."""
    if isinstance(data, dict) or isinstance(data, list):
        serialized = json.dumps(data, sort_keys=True, separators=(',', ':')).encode('utf-8')
    elif isinstance(data, str):
        serialized = data.encode('utf-8')
    else:
        serialized = str(data).encode('utf-8')
    return hashlib.sha256(serialized).hexdigest()[:16]


def snap_event_to_video_frame(event_time_ms: float, fps: int = 30) -> tuple[int, float]:
    """
    V3.5.2.3 Frame-Quantization Compensation.
    Snaps an event time in milliseconds to the nearest video frame.
    Returns (frame_index: int, visual_frame_pts_ms: float).
    """
    frame_dur_ms = 1000.0 / float(fps)
    frame_idx = int(round(float(event_time_ms) / frame_dur_ms))
    pts_ms = frame_idx * frame_dur_ms
    return frame_idx, round(pts_ms, 1)


def smoothstep(t: float) -> float:
    """Smoothstep easing: 3*t^2 - 2*t^3 with acceleration, cruising, and deceleration."""
    t = max(0.0, min(1.0, t))
    return t * t * (3.0 - 2.0 * t)

def cubic_ease_in_out(t: float) -> float:
    """Cubic Ease In-Out: 4*t^3 for t < 0.5, else 1 - (-2*t + 2)^3 / 2."""
    t = max(0.0, min(1.0, t))
    if t < 0.5:
        return 4.0 * t * t * t
    else:
        return 1.0 - math.pow(-2.0 * t + 2.0, 3) / 2.0

def interpolate_cursor_trajectory(waypoints: list, total_duration_ms: int, fps: int = 30, vwidth: int = 1920, vheight: int = 1080) -> list:
    """
    Interpolates a continuous frame-by-frame cursor trajectory across the entire video.
    Avoids discrete jumps or sudden teleports.
    waypoints is a list of:
      {"t_ms": int, "x": int, "y": int, "state": "move"|"click"|"type"|"idle"}
    """
    if not waypoints:
        waypoints = [{"t_ms": 0, "x": vwidth // 2, "y": vheight // 2, "state": "idle"}]

    # Ensure sorted by t_ms
    waypoints = sorted(waypoints, key=lambda w: w["t_ms"])
    if waypoints[0]["t_ms"] > 0:
        waypoints.insert(0, {"t_ms": 0, "x": waypoints[0]["x"], "y": waypoints[0]["y"], "state": "idle"})
    if waypoints[-1]["t_ms"] < total_duration_ms:
        waypoints.append({"t_ms": total_duration_ms, "x": waypoints[-1]["x"], "y": waypoints[-1]["y"], "state": "idle"})

    frame_interval_ms = 1000.0 / float(fps)
    total_frames = int(math.ceil(total_duration_ms / frame_interval_ms))
    frames = []

    wp_idx = 0
    num_wp = len(waypoints)

    last_x = waypoints[0]["x"]
    last_y = waypoints[0]["y"]

    for frame_num in range(total_frames + 1):
        frame_time_ms = frame_num * frame_interval_ms
        if frame_time_ms > total_duration_ms:
            frame_time_ms = float(total_duration_ms)

        # Advance wp_idx until frame_time_ms is between wp_idx and wp_idx+1
        while wp_idx < num_wp - 1 and waypoints[wp_idx + 1]["t_ms"] <= frame_time_ms:
            wp_idx += 1

        if wp_idx >= num_wp - 1:
            curr_x = waypoints[-1]["x"]
            curr_y = waypoints[-1]["y"]
            state = waypoints[-1].get("state", "idle")
        else:
            w1 = waypoints[wp_idx]
            w2 = waypoints[wp_idx + 1]
            seg_dur = float(w2["t_ms"] - w1["t_ms"])
            if seg_dur <= 0:
                curr_x = w2["x"]
                curr_y = w2["y"]
                state = w2.get("state", "idle")
            else:
                raw_t = (frame_time_ms - w1["t_ms"]) / seg_dur
                ease_t = smoothstep(raw_t)
                curr_x = round(w1["x"] + (w2["x"] - w1["x"]) * ease_t)
                curr_y = round(w1["y"] + (w2["y"] - w1["y"]) * ease_t)
                state = w2.get("state", "move") if raw_t < 0.95 else w2.get("state", "idle")

        dist_from_last = math.sqrt((curr_x - last_x)**2 + (curr_y - last_y)**2)
        frames.append({
            "frame": frame_num,
            "t_ms": round(frame_time_ms, 1),
            "x": int(curr_x),
            "y": int(curr_y),
            "state": state,
            "jump_px": round(dist_from_last, 1)
        })
        last_x = curr_x
        last_y = curr_y

    return frames

def build_canonical_timeline(plan: dict, audio_meta: list = None, variant: str = "desktop", run_id: str = None) -> dict:
    """
    V3.5.2.5 Video-Master Audio Sync Engine:
    FINAL VIDEO IS THE ABSOLUTE MASTER CLOCK.
    Visual choreography and planned instruction determine visual scene bounds.
    Audio never extends the video duration.
    Produces both canonical timeline and unified video-timeline.json.
    """
    is_mobile = variant == "mobile" or plan.get("tutorial_mode") == "mobile" or plan.get("mode") == "mobile"
    vwidth = 1080 if is_mobile else 1920
    vheight = 1920 if is_mobile else 1080

    intro_cfg = plan.get("intro", {})
    outro_cfg = plan.get("outro", {})
    intro_ms = int(round(float(intro_cfg.get("duration", 4.0) if intro_cfg.get("enabled", True) else 0.0) * 1000))
    outro_ms = int(round(float(outro_cfg.get("duration", 4.0) if outro_cfg.get("enabled", True) else 0.0) * 1000))

    audio_meta_map = {}
    if audio_meta:
        for a in audio_meta:
            sid = a.get("scene_id") or a.get("id")
            if sid:
                audio_meta_map[sid] = a

    scenes = plan.get("scenes", [])
    current_time_ms = intro_ms
    timeline_scenes = []
    video_timeline_scenes = []
    canonical_events = []
    narration_visual_map = []
    all_narration_segments = []
    waypoints = [{"t_ms": 0, "x": vwidth // 2, "y": vheight // 2, "state": "idle"}]

    cursor_pos = {"x": vwidth // 2, "y": vheight // 2}
    event_counter = 1

    for idx, scene in enumerate(scenes):
        scene_id = scene.get("scene_id") or scene.get("id", f"scene_{idx+1:02d}")
        title = scene.get("title") or scene.get("name", scene_id)
        narration = scene.get("narration", "").strip()

        # 1. Resolve semantic narration segments for this scene
        scene_segments = []
        am = audio_meta_map.get(scene_id)
        if am and "segments" in am and am["segments"]:
            for seg in am["segments"]:
                scene_segments.append(dict(seg))
        elif scene.get("narration_segments"):
            for sidx, seg in enumerate(scene.get("narration_segments")):
                txt = seg.get("text", "")
                dur_ms = seg.get("duration_ms") or int(round(max(2.0, len(txt.split()) / 2.8) * 1000))
                scene_segments.append({
                    "narration_id": seg.get("narration_id", f"{scene_id}_seg_{sidx+1:02d}"),
                    "scene_id": scene_id,
                    "text": txt,
                    "duration_ms": dur_ms,
                    "duration_sec": round(dur_ms / 1000.0, 3),
                    "target_visual": seg.get("target_visual", title),
                    "target_element": seg.get("target_element", "main"),
                    "intended_action": seg.get("intended_action", "view"),
                    "importance": seg.get("importance", "normal"),
                    "visual_required": seg.get("visual_required", True)
                })
        else:
            word_count = len(narration.split()) if narration else 10
            dur_ms = max(3000, int(round((word_count / 2.8) * 1000)))
            scene_segments.append({
                "narration_id": f"{scene_id}_seg_01",
                "scene_id": scene_id,
                "text": narration,
                "duration_ms": dur_ms,
                "duration_sec": round(dur_ms / 1000.0, 3),
                "target_visual": title,
                "target_element": "main",
                "intended_action": "view",
                "importance": "normal",
                "visual_required": True
            })

        # Calculate importance for visual instructional holding
        importance_levels = [s.get("importance", "normal") for s in scene_segments]
        if "critical" in importance_levels:
            scene_importance = "critical"
            default_hold_ms = 1800
        elif "important" in importance_levels:
            scene_importance = "important"
            default_hold_ms = 1400
        else:
            scene_importance = "normal"
            default_hold_ms = 1000

        teaching_hold_ms = int(scene.get("teaching_hold_ms") or default_hold_ms)

        # Adaptive Narration Lead: 300–600 ms lead before visual cursor action
        first_seg_text = scene_segments[0].get("text", "")
        narration_lead_ms = min(600, max(300, int(len(first_seg_text) * 7.5)))

        scene_start_ms = current_time_ms
        scene_action_cursor = scene_start_ms + 100 + narration_lead_ms
        scene_actions_pts = [
            {"type": "focus", "pts_ms": scene_start_ms + 150}
        ]

        actions = scene.get("actions") or []
        if not actions and (scene.get("action") or scene.get("cursor_action")):
            actions = [{"type": scene.get("action") or scene.get("cursor_action"), "selector": scene.get("locator")}]

        for act in actions:
            atype = act.get("type", "wait")
            target_selector = act.get("selector") or act.get("target")

            target_x = act.get("x")
            target_y = act.get("y")
            if target_x is None or target_y is None:
                if is_mobile:
                    target_x = vwidth // 2
                    target_y = int(vheight * 0.45)
                else:
                    target_x = int(vwidth * 0.52)
                    target_y = int(vheight * 0.42)

            if atype in ("click", "tap", "move-and-click"):
                move_dur_ms = 650
                move_start_ms = scene_action_cursor
                move_end_ms = move_start_ms + move_dur_ms
                waypoints.append({"t_ms": move_start_ms, "x": cursor_pos["x"], "y": cursor_pos["y"], "state": "move"})
                waypoints.append({"t_ms": move_end_ms, "x": target_x, "y": target_y, "state": "hover"})
                cursor_pos = {"x": target_x, "y": target_y}

                hover_ms = 300
                click_time_ms = move_end_ms + hover_ms

                # V3.5.2.5: Snap click to exact video frame PTS (single source of truth)
                canonical_ms = click_time_ms
                frame_idx, visual_pts_ms = snap_event_to_video_frame(canonical_ms, fps=30)
                waypoints.append({"t_ms": visual_pts_ms, "x": target_x, "y": target_y, "state": "click"})

                evt_id = f"evt_click_{event_counter:03d}"
                canonical_events.append({
                    "id": evt_id,
                    "scene_id": scene_id,
                    "type": "tap" if is_mobile else "click",
                    "canonical_event_ms": canonical_ms,
                    "visual_frame_index": frame_idx,
                    "visual_frame_pts_ms": visual_pts_ms,
                    "requested_audio_ms": visual_pts_ms,
                    "effective_audio_ms": visual_pts_ms,
                    "codec_compensation_ms": 0,
                    "time_ms": int(round(visual_pts_ms)),
                    "time_sec": round(visual_pts_ms / 1000.0, 3),
                    "x": target_x,
                    "y": target_y,
                    "audio_start_ms": int(round(visual_pts_ms)),
                    "audio_offset_ms": 0,
                    "source_type": "synthetic_fallback" if not is_mobile else "synthetic",
                    "mode": "mobile_tap" if is_mobile else "physical_mouse",
                    "volume": 0.22 if is_mobile else 0.20
                })
                scene_actions_pts.append({
                    "type": "tap" if is_mobile else "click",
                    "pts_ms": int(round(visual_pts_ms))
                })
                event_counter += 1
                scene_action_cursor = int(round(visual_pts_ms)) + 700

            elif atype in ("type", "fill"):
                move_dur_ms = 600
                move_start_ms = scene_action_cursor
                move_end_ms = move_start_ms + move_dur_ms
                waypoints.append({"t_ms": move_start_ms, "x": cursor_pos["x"], "y": cursor_pos["y"], "state": "move"})
                waypoints.append({"t_ms": move_end_ms, "x": target_x, "y": target_y, "state": "hover"})
                cursor_pos = {"x": target_x, "y": target_y}

                type_start_ms = move_end_ms + 200
                val = act.get("value", "sample")
                type_dur_ms = max(800, len(val) * 75)
                type_end_ms = type_start_ms + type_dur_ms

                # V3.5.2.5: Snap typing start & end to exact video frame PTS bounds
                canonical_start_ms = type_start_ms
                canonical_end_ms = type_end_ms
                start_frame_idx, visual_start_pts_ms = snap_event_to_video_frame(canonical_start_ms, fps=30)
                end_frame_idx, visual_end_pts_ms = snap_event_to_video_frame(canonical_end_ms, fps=30)

                waypoints.append({"t_ms": visual_start_pts_ms, "x": target_x, "y": target_y, "state": "type"})
                waypoints.append({"t_ms": visual_end_pts_ms, "x": target_x, "y": target_y, "state": "idle"})

                evt_id = f"evt_type_{event_counter:03d}"
                canonical_events.append({
                    "id": evt_id,
                    "scene_id": scene_id,
                    "type": "typing",
                    "canonical_start_ms": canonical_start_ms,
                    "canonical_end_ms": canonical_end_ms,
                    "visual_start_frame_index": start_frame_idx,
                    "visual_start_frame_pts_ms": visual_start_pts_ms,
                    "visual_end_frame_index": end_frame_idx,
                    "visual_end_frame_pts_ms": visual_end_pts_ms,
                    "requested_audio_start_ms": visual_start_pts_ms,
                    "effective_audio_start_ms": visual_start_pts_ms,
                    "requested_audio_end_ms": visual_end_pts_ms,
                    "effective_audio_end_ms": visual_end_pts_ms,
                    "codec_compensation_ms": 0,
                    "start_ms": int(round(visual_start_pts_ms)),
                    "end_ms": int(round(visual_end_pts_ms)),
                    "start_sec": round(visual_start_pts_ms / 1000.0, 3),
                    "end_sec": round(visual_end_pts_ms / 1000.0, 3),
                    "duration_ms": int(round(visual_end_pts_ms - visual_start_pts_ms)),
                    "x": target_x,
                    "y": target_y,
                    "target": target_selector or "input",
                    "characters": len(val),
                    "audio_start_ms": int(round(visual_start_pts_ms)),
                    "audio_end_ms": int(round(visual_end_pts_ms)),
                    "volume": 0.08
                })
                scene_actions_pts.append({
                    "type": "typing",
                    "start_pts_ms": int(round(visual_start_pts_ms)),
                    "end_pts_ms": int(round(visual_end_pts_ms))
                })
                event_counter += 1
                scene_action_cursor = int(round(visual_end_pts_ms)) + 700

            elif atype == "wait":
                scene_action_cursor += int(act.get("ms", 1000))

        # ── V3.5.2.5 VIDEO-MASTER PRINCIPLE: VIDEO DETERMINES DURATION ──
        # Audio does NOT expand the scene. Visual choreography + visual teaching hold
        # or planned duration_target sets the definitive visual window.
        planned_dur_ms = int(round(float(scene.get("duration_target", 0)) * 1000))
        computed_visual_end_ms = scene_action_cursor + teaching_hold_ms
        if planned_dur_ms > 0:
            scene_end_ms = max(scene_start_ms + planned_dur_ms, computed_visual_end_ms)
        else:
            scene_end_ms = computed_visual_end_ms

        # Quantize to 100ms
        scene_dur_ms = int(math.ceil((scene_end_ms - scene_start_ms) / 100.0) * 100)
        scene_end_ms = scene_start_ms + scene_dur_ms

        # ── Compute Narration Window strictly inside Visual Scene ──
        # Narration must completely finish at least 250ms BEFORE scene transition!
        scene_narr_start_ms = scene_start_ms + 100
        max_scene_narration_ms = max(1000, scene_dur_ms - 100 - 250)

        # Distribute available speech budget across segments
        num_segs = len(scene_segments)
        total_initial_speech = sum(s["duration_ms"] for s in scene_segments)
        narr_cursor_ms = scene_narr_start_ms

        for seg in scene_segments:
            # Segment duration cannot exceed its allocated share of visual window
            if total_initial_speech > 0:
                seg_ratio = float(seg["duration_ms"]) / float(total_initial_speech)
            else:
                seg_ratio = 1.0 / float(num_segs)
            max_seg_dur_ms = max(500, int(round(max_scene_narration_ms * seg_ratio)))

            # If TTS audio already available, respect it as long as it fits
            seg_actual_dur = min(seg["duration_ms"], max_seg_dur_ms)
            seg["duration_ms"] = seg_actual_dur
            seg["duration_sec"] = round(seg_actual_dur / 1000.0, 3)
            seg["max_narration_duration_ms"] = max_seg_dur_ms

            seg["narration_start_ms"] = narr_cursor_ms
            seg["narration_end_ms"] = min(scene_end_ms - 250, narr_cursor_ms + seg_actual_dur)
            narr_cursor_ms = seg["narration_end_ms"] + 150

            vis_start_ms = scene_start_ms
            vis_end_ms = scene_end_ms
            narr_start_ms = seg["narration_start_ms"]
            narr_end_ms = seg["narration_end_ms"]
            align_status = "PASS" if (vis_end_ms >= narr_end_ms and vis_start_ms <= narr_start_ms) else "FAIL"

            map_entry = {
                "narration_id": seg["narration_id"],
                "scene_id": scene_id,
                "text": seg["text"],
                "target_visual": seg.get("target_visual", title),
                "target_element": seg.get("target_element", "main"),
                "intended_action": seg.get("intended_action", "view"),
                "importance": seg.get("importance", "normal"),
                "teaching_hold_ms": teaching_hold_ms,
                "visual_start_ms": vis_start_ms,
                "visual_end_ms": vis_end_ms,
                "narration_start_ms": narr_start_ms,
                "narration_end_ms": narr_end_ms,
                "max_narration_duration_ms": max_seg_dur_ms,
                "lead_time_ms": narration_lead_ms,
                "alignment_status": align_status
            }
            narration_visual_map.append(map_entry)
            all_narration_segments.append(dict(seg))

        total_speech_ms = sum(s["duration_ms"] for s in scene_segments)
        silence_pad_ms = max(0, scene_dur_ms - total_speech_ms)
        scene_narr_end_ms = max(scene_narr_start_ms + total_speech_ms, scene_segments[-1]["narration_end_ms"])

        timeline_scenes.append({
            "scene_id": scene_id,
            "title": title,
            "start_ms": scene_start_ms,
            "end_ms": scene_end_ms,
            "start_sec": round(scene_start_ms / 1000.0, 3),
            "end_sec": round(scene_end_ms / 1000.0, 3),
            "duration_ms": scene_dur_ms,
            "speech_duration_ms": total_speech_ms,
            "silence_pad_ms": silence_pad_ms,
            "teaching_hold_ms": teaching_hold_ms,
            "narration_lead_ms": narration_lead_ms,
            "max_narration_duration_ms": max_scene_narration_ms,
            "importance": scene_importance,
            "narration_segments": scene_segments,
            "narration": narration
        })

        # V3.5.2.5 Unified Event Model: video-timeline.json entry
        video_timeline_scenes.append({
            "scene_id": scene_id,
            "visual_start_ms": scene_start_ms,
            "visual_end_ms": scene_end_ms,
            "duration_ms": scene_dur_ms,
            "actions": scene_actions_pts,
            "narration": {
                "start_pts_ms": scene_narr_start_ms,
                "end_pts_ms": scene_narr_end_ms,
                "max_duration_ms": max_scene_narration_ms
            }
        })

        current_time_ms = scene_end_ms

    total_duration_ms = current_time_ms + outro_ms
    waypoints.append({"t_ms": total_duration_ms, "x": cursor_pos["x"], "y": cursor_pos["y"], "state": "idle"})

    # Compute Continuous Frame-by-Frame Trajectory
    cursor_trajectory = interpolate_cursor_trajectory(
        waypoints,
        total_duration_ms=total_duration_ms,
        fps=30,
        vwidth=vwidth,
        vheight=vheight
    )

    timeline_data = {
        "timeline_version": TIMELINE_VERSION,
        "engine": "Video-Master Audio Sync Engine",
        "variant": variant,
        "run_id": run_id or "run-deterministic-v3525",
        "tutorial_id": plan.get("tutorial_id", "unknown-tutorial"),
        "resolution": f"{vwidth}x{vheight}",
        "fps": 30,
        "total_duration_ms": total_duration_ms,
        "total_duration_sec": round(total_duration_ms / 1000.0, 3),
        "intro_duration_ms": intro_ms,
        "outro_duration_ms": outro_ms,
        "scenes": timeline_scenes,
        "video_timeline": video_timeline_scenes,
        "narration_segments": all_narration_segments,
        "narration_visual_map": narration_visual_map,
        "events": canonical_events,
        "cursor_trajectory_sample_count": len(cursor_trajectory),
        "cursor_trajectory": cursor_trajectory
    }

    timeline_data["timeline_hash"] = compute_hash(timeline_data)
    return timeline_data

def validate_variant_provenance(video_variant: str, audio_variant: str, video_timeline_hash: str = None, audio_timeline_hash: str = None) -> tuple[bool, str]:
    """
    Hard Gate: Enforces strict isolation between Desktop and Mobile assets.
    Returns (is_valid: bool, reason: str).
    """
    if video_variant != audio_variant:
        return False, f"Variant Mismatch: Video is '{video_variant}' but Audio is '{audio_variant}' (CROSS-VARIANT CONTAMINATION FORBIDDEN)"
    if video_timeline_hash and audio_timeline_hash and video_timeline_hash != audio_timeline_hash:
        return False, f"Timeline Hash Mismatch: Video timeline ({video_timeline_hash}) != Audio timeline ({audio_timeline_hash})"
    return True, f"Variant Provenance Verified: '{video_variant}' == '{audio_variant}'"

def main():
    if len(sys.argv) < 2:
        print("Usage: python timeline.py --plan <path> --out <path> [--variant desktop|mobile]")
        sys.exit(1)

    import argparse
    p = argparse.ArgumentParser(description="Canonical Timeline Generator (V3.5.2.5 Video-Master)")
    p.add_argument("--plan", required=True, help="Path to tutorial plan JSON")
    p.add_argument("--audio-meta", help="Path to audio_meta.json from TTS")
    p.add_argument("--variant", default="desktop", choices=["desktop", "mobile"])
    p.add_argument("--run-id", help="Run ID")
    p.add_argument("--map-out", help="Path to narration-visual-map.json")
    p.add_argument("--video-timeline-out", help="Path to video-timeline.json")
    p.add_argument("--out", required=True, help="Output canonical-timeline.json path")
    args = p.parse_args()

    plan = json.load(open(args.plan, "r", encoding="utf-8"))
    audio_meta = None
    if args.audio_meta and os.path.isfile(args.audio_meta):
        audio_meta = json.load(open(args.audio_meta, "r", encoding="utf-8"))

    tl = build_canonical_timeline(plan, audio_meta, variant=args.variant, run_id=args.run_id)
    os.makedirs(os.path.dirname(os.path.abspath(args.out)), exist_ok=True)
    with open(args.out, "w", encoding="utf-8") as f:
        json.dump(tl, f, indent=2)

    video_tl_path = args.video_timeline_out or os.path.join(os.path.dirname(os.path.abspath(args.out)), 'video-timeline.json')
    with open(video_tl_path, "w", encoding="utf-8") as f:
        json.dump(tl.get("video_timeline", []), f, indent=2)

    map_path = args.map_out or os.path.join(os.path.dirname(os.path.abspath(args.out)), 'narration-visual-map.json')
    with open(map_path, "w", encoding="utf-8") as f:
        json.dump(tl.get("narration_visual_map", []), f, indent=2)

    print(f"[Timeline V3.5.2.5] Canonical timeline written: {args.out} (Hash: {tl['timeline_hash']}, Total: {tl['total_duration_sec']}s)")
    print(f"[Timeline V3.5.2.5] Unified video-timeline written: {video_tl_path} ({len(tl.get('video_timeline', []))} scenes)")
    print(f"[Timeline V3.5.2.5] Narration-visual map written: {map_path} ({len(tl.get('narration_visual_map', []))} segments)")

if __name__ == "__main__":
    main()

