#!/usr/bin/env python
# subtitle_collision.py — V3.4/V3.5 (Dynamic Subtitle Safe Area & Collision Engine)
# Detects intersection between focus target bounding boxes and subtitle display areas.
# Supports both Desktop (1920x1080) and Mobile Portrait (1080x1920).
# Produces: subtitle-placement.json and adapted .ass subtitle file with per-scene alignment tags ({\an8} for top, {\an2} for bottom).
import argparse, json, os, re, sys

def parse_srt(srt_path):
    """Parses SRT file into a list of subtitle entries."""
    if not os.path.isfile(srt_path):
        return []
    with open(srt_path, 'r', encoding='utf-8', errors='replace') as f:
        content = f.read()
    
    entries = []
    # Match standard SRT blocks: index, timestamp --> timestamp, text
    blocks = re.split(r'\n\s*\n', content.strip())
    for block in blocks:
        lines = [line.strip() for line in block.split('\n') if line.strip()]
        if len(lines) >= 3:
            time_line = lines[1]
            text = ' '.join(lines[2:])
            m = re.match(r'(\d+):(\d+):(\d+)[,\.](\d+)\s*-->\s*(\d+):(\d+):(\d+)[,\.](\d+)', time_line)
            if m:
                g = [int(x) for x in m.groups()]
                start_sec = g[0]*3600 + g[1]*60 + g[2] + g[3]/1000.0
                end_sec = g[4]*3600 + g[5]*60 + g[6] + g[7]/1000.0
                entries.append({
                    'start': start_sec,
                    'end': end_sec,
                    'text': text
                })
    return entries

def rects_intersect(r1, r2):
    """Check if two rectangles [x1, y1, x2, y2] intersect."""
    return not (r1[2] <= r2[0] or r1[0] >= r2[2] or r1[3] <= r2[1] or r1[1] >= r2[3])

def calculate_subtitle_placements(focus_timeline, srt_entries, viewport=(1920, 1080), margin=48, is_mobile=False):
    """
    Evaluates subtitle collision against focus target bounding boxes.
    Desktop:
      bottom zone: x: [192..1728], y: [830..1060]
    Mobile (1080x1920 or 390x844 relative):
      bottom zone: relative bottom 22%
    """
    vw, vh = viewport
    if is_mobile or vh > vw:
        # Mobile portrait: bottom subtitle sits in lower ~18% of screen
        bottom_zone = [30, int(vh * 0.78), vw - 30, vh - 20]
        top_zone = [30, 24, vw - 30, int(vh * 0.16)]
    else:
        bottom_zone = [240 - margin, 830 - margin, 1680 + margin, vh - 20]
        top_zone = [240 - margin, margin, 1680 + margin, margin + 160]

    placements = {}
    
    for scene_id, fdata in focus_timeline.items():
        timing = fdata.get('timing', {})
        t_start = timing.get('zoom_in_start', timing.get('scene_start_sec', 0))
        t_end = timing.get('zoom_out_end', t_start + 5.0)
        
        target_box = fdata.get('target_box')
        focus_enabled = fdata.get('focus_enabled', False)
        
        collision = False
        placement = 'bottom'
        reason = 'Default bottom placement'

        if focus_enabled and target_box:
            bx = target_box.get('x', 0)
            by = target_box.get('y', 0)
            bw = target_box.get('width', 0)
            bh = target_box.get('height', 0)
            target_rect = [bx, by, bx + bw, by + bh]

            center = fdata.get('center', {})
            cx = center.get('cx', vw // 2)
            cy = center.get('cy', vh // 2)
            
            # Check collision with bottom subtitle zone
            bottom_thresh = int(vh * 0.75) if (is_mobile or vh > vw) else 800
            if rects_intersect(target_rect, bottom_zone) or (cy > bottom_thresh and by > bottom_thresh - 100):
                collision = True
                placement = 'top'
                reason = f"Focus target [{bx},{by},{bw},{bh}] intersects bottom subtitle zone. Shifted to top."
            else:
                collision = False
                placement = 'bottom'
                reason = "No intersection with bottom subtitle zone."

        placements[scene_id] = {
            'scene_id': scene_id,
            'timing': {'start': round(t_start, 2), 'end': round(t_end, 2)},
            'focus_rect': target_box,
            'placement': placement,
            'collision': collision,
            'reason': reason
        }

    return placements

def sec_to_ass_time(sec):
    """Converts seconds float to ASS timestamp format: H:MM:SS.cs"""
    h = int(sec // 3600)
    m = int((sec % 3600) // 60)
    s = int(sec % 60)
    cs = int(round((sec - int(sec)) * 100))
    if cs >= 100:
        cs = 99
    return f"{h}:{m:02d}:{s:02d}.{cs:02d}"

def generate_adaptive_ass(srt_entries, placements, out_ass_path, font_size=22, res_x=1920, res_y=1080, is_mobile=False):
    """
    Generates an Advanced Substation Alpha (.ass) file where lines with collision
    use top-center alignment (\\an8) and normal lines use bottom-center (\\an2).
    """
    if is_mobile or res_y > res_x:
        res_x = 1080
        res_y = 1920
        font_size = 38
        margin_v = 54
        margin_lr = 36
    else:
        res_x = 1920
        res_y = 1080
        font_size = font_size or 22
        margin_v = 40
        margin_lr = 48

    ass_header = f"""[Script Info]
Title: Adaptive Tutorial Subtitles (V4.1)
ScriptType: v4.00+
WrapStyle: 0
ScaledBorderAndShadow: yes
YCbCr Matrix: TV.601
PlayResX: {res_x}
PlayResY: {res_y}

[V4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
Style: BottomStyle, Arial, {font_size}, &H00FFFFFF, &H000000FF, &H00000000, &H80000000, 1, 0, 0, 0, 100, 100, 0, 0, 1, 2.5, 1.2, 2, {margin_lr}, {margin_lr}, {margin_v}, 1
Style: TopStyle, Arial, {font_size}, &H00FFFFFF, &H000000FF, &H00000000, &H80000000, 1, 0, 0, 0, 100, 100, 0, 0, 1, 2.5, 1.2, 8, {margin_lr}, {margin_lr}, {margin_v + 10}, 1

[Events]
Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text
"""
    dialogue_lines = []
    
    # Sort placements by start time
    placement_list = sorted(placements.values(), key=lambda x: x['timing']['start'])

    for entry in srt_entries:
        s_mid = (entry['start'] + entry['end']) / 2.0
        # Determine placement based on matching scene
        active_placement = 'bottom'
        for p in placement_list:
            if p['timing']['start'] - 0.5 <= s_mid <= p['timing']['end'] + 0.5:
                active_placement = p['placement']
                break

        style_name = 'TopStyle' if active_placement == 'top' else 'BottomStyle'
        pos_tag = r"{\an8}" if active_placement == 'top' else r"{\an2}"
        
        t_start = sec_to_ass_time(entry['start'])
        t_end = sec_to_ass_time(entry['end'])
        clean_text = entry['text'].replace('\n', r'\N')
        
        line = f"Dialogue: 0,{t_start},{t_end},{style_name},,0,0,0,,{pos_tag}{clean_text}"
        dialogue_lines.append(line)

    with open(out_ass_path, 'w', encoding='utf-8') as f:
        f.write(ass_header + '\n'.join(dialogue_lines) + '\n')
    
    return out_ass_path

def main():
    p = argparse.ArgumentParser(description="V3.5 Dynamic Subtitle Safe Area & Collision Engine")
    p.add_argument('--focus-timeline', required=True, help="Path to scene_focus_timeline.json")
    p.add_argument('--srt', required=True, help="Path to narration SRT file")
    p.add_argument('--out-placement', required=True, help="Path to write subtitle-placement.json")
    p.add_argument('--out-ass', help="Optional path to write adapted .ass subtitle file")
    p.add_argument('--mobile', action='store_true', help="Enable mobile portrait viewport rules")
    args = p.parse_args()

    if not os.path.isfile(args.focus_timeline):
        print(f"[COLLISION ERROR] focus timeline not found: {args.focus_timeline}", file=sys.stderr)
        sys.exit(1)

    with open(args.focus_timeline, 'r', encoding='utf-8') as f:
        focus_timeline = json.load(f)

    srt_entries = parse_srt(args.srt)
    viewport = (1080, 1920) if args.mobile else (1920, 1080)
    placements = calculate_subtitle_placements(focus_timeline, srt_entries, viewport=viewport, is_mobile=args.mobile)

    with open(args.out_placement, 'w', encoding='utf-8') as f:
        json.dump(placements, f, indent=2)
    print(f"[V3.5] subtitle-placement.json generated: {args.out_placement}")
    
    col_count = sum(1 for p in placements.values() if p['collision'])
    top_count = sum(1 for p in placements.values() if p['placement'] == 'top')
    print(f"  Total scenes evaluated : {len(placements)}")
    print(f"  Collisions detected    : {col_count}")
    print(f"  Shifted to top safe-zone: {top_count}")

    if args.out_ass:
        generate_adaptive_ass(srt_entries, placements, args.out_ass, is_mobile=args.mobile)
        print(f"[V3.5] Adaptive ASS subtitle written: {args.out_ass}")

if __name__ == '__main__':
    main()
