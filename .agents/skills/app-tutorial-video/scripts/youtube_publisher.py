#!/usr/bin/env python3
"""
youtube_publisher.py — YouTube Metadata Generator & Publishing Integration for App Tutorial Video Skill.
Generates video title, chapters/timestamps, SEO description, tags, hashtags, and thumbnail
tailored specifically to the generated tutorial video. Supports direct YouTube Data API v3 upload
with OAuth 2.0 and offline YouTube Upload Kit generation.
"""

from __future__ import annotations

import argparse
from datetime import datetime, timezone
import json
import math
import os
import re
import shutil
import subprocess
import sys
from typing import Any, Dict, List, Optional, Tuple

YOUTUBE_UPLOAD_SCOPE = ["https://www.googleapis.com/auth/youtube.upload"]


def ffmpeg_exe() -> str:
    try:
        import imageio_ffmpeg
        return imageio_ffmpeg.get_ffmpeg_exe()
    except ImportError:
        return "ffmpeg"


def resolve_credentials_dir(workspace_root: Optional[str] = None, explicit_dir: Optional[str] = None) -> str:
    if explicit_dir:
        d = os.path.abspath(explicit_dir)
    elif workspace_root:
        d = os.path.abspath(os.path.join(workspace_root, ".credentials"))
    else:
        d = os.path.abspath(os.path.join(os.getcwd(), ".credentials"))
    os.makedirs(d, exist_ok=True)
    return d


def format_timestamp(ms: float) -> str:
    total_seconds = max(0, int(math.floor(ms / 1000.0)))
    hours = total_seconds // 3600
    minutes = (total_seconds % 3600) // 60
    seconds = total_seconds % 60
    if hours > 0:
        return f"{hours:02d}:{minutes:02d}:{seconds:02d}"
    return f"{minutes:02d}:{seconds:02d}"


def clean_scene_title(scene_id: str, raw_title: Optional[str] = None) -> str:
    if raw_title and raw_title.strip():
        return raw_title.strip()
    s = scene_id
    s = re.sub(r"^scene_\d+_", "", s)
    s = s.replace("_", " ").strip()
    return s.title()


def extract_scene_timestamps(run_dir: str, plan: Dict[str, Any]) -> List[Dict[str, Any]]:
    """
    Extracts high-precision scene start timestamps from events-*.json, recording-manifest,
    or narration.json, guaranteeing that the first chapter starts at 00:00.
    """
    rec_dir = os.path.join(run_dir, "recording")
    candidates = [
        os.path.join(rec_dir, "events-mobile.json"),
        os.path.join(rec_dir, "events-desktop.json"),
        os.path.join(rec_dir, "events.json"),
    ]

    events_data = None
    for c in candidates:
        if os.path.isfile(c):
            try:
                with open(c, "r", encoding="utf-8") as f:
                    events_data = json.load(f)
                    if "scenes" in events_data and len(events_data["scenes"]) > 0:
                        break
            except Exception:
                continue

    plan_scenes = plan.get("scenes", [])
    chapters: List[Dict[str, Any]] = []

    if events_data and "scenes" in events_data and len(events_data["scenes"]) > 0:
        ev_scenes = events_data["scenes"]
        for i, ev_s in enumerate(ev_scenes):
            sid = ev_s.get("scene_id", f"scene_{i+1:02d}")
            start_ms = float(ev_s.get("start_ms", 0.0))
            if i == 0:
                start_ms = 0.0
            p_scene = next((s for s in plan_scenes if s.get("scene_id") == sid), {})
            title = clean_scene_title(sid, p_scene.get("title"))
            chapters.append({
                "scene_id": sid,
                "start_ms": start_ms,
                "timestamp": format_timestamp(start_ms),
                "title": title,
                "narration": p_scene.get("narration", "")
            })
    else:
        # Fallback: calculate from narration.json
        narr_file = os.path.join(run_dir, "narration.json")
        narr_data = {}
        if os.path.isfile(narr_file):
            try:
                with open(narr_file, "r", encoding="utf-8") as f:
                    narr_data = json.load(f).get("scenes", {})
            except Exception:
                pass

        current_ms = 0.0
        for i, sc in enumerate(plan_scenes):
            sid = sc.get("scene_id", f"scene_{i+1:02d}")
            start_ms = 0.0 if i == 0 else current_ms
            title = clean_scene_title(sid, sc.get("title"))
            chapters.append({
                "scene_id": sid,
                "start_ms": start_ms,
                "timestamp": format_timestamp(start_ms),
                "title": title,
                "narration": sc.get("narration", "")
            })
            dur = narr_data.get(sid, {}).get("duration_ms", 12000.0)
            res_hold = float(sc.get("result_hold_ms", 1200.0))
            current_ms += dur + res_hold + 3000.0

    if chapters and chapters[0]["start_ms"] != 0:
        chapters[0]["start_ms"] = 0.0
        chapters[0]["timestamp"] = "00:00"

    return chapters


def select_best_thumbnail(run_dir: str, target_aspect: str = "9:16") -> Optional[str]:
    """
    Selects or extracts the best thumbnail image from the run directory.
    Prioritizes perceptual contact sheet, clean frame evidence, or ffmpeg extraction.
    """
    ev_dir = os.path.join(run_dir, "perceptual-evidence")
    if os.path.isdir(ev_dir):
        sheet = os.path.join(ev_dir, "perceptual_contact_sheet.png")
        if os.path.isfile(sheet):
            return sheet
        # Check sampled frames
        frames = sorted([f for f in os.listdir(ev_dir) if f.startswith("frame_") and f.endswith(".png")])
        if len(frames) >= 3:
            return os.path.join(ev_dir, frames[1])  # Frame around scene 1-2 interaction
        elif frames:
            return os.path.join(ev_dir, frames[0])

    # Check evidence directory
    for mode in ["mobile", "desktop"]:
        mode_ev = os.path.join(run_dir, "evidence", mode)
        if os.path.isdir(mode_ev):
            imgs = sorted([f for f in os.listdir(mode_ev) if f.endswith(".png")])
            if imgs:
                return os.path.join(mode_ev, imgs[0])

    # Fallback to ffmpeg extraction from final mp4
    final_dir = os.path.join(run_dir, "final")
    if os.path.isdir(final_dir):
        mp4s = [f for f in os.listdir(final_dir) if f.endswith(".mp4")]
        if mp4s:
            chosen_mp4 = os.path.join(final_dir, mp4s[0])
            thumb_out = os.path.join(run_dir, "youtube", "thumbnail.png")
            os.makedirs(os.path.dirname(thumb_out), exist_ok=True)
            cmd = [
                ffmpeg_exe(), "-y", "-ss", "00:00:03", "-i", chosen_mp4,
                "-vframes", "1", "-q:v", "2", thumb_out
            ]
            try:
                subprocess.run(cmd, capture_output=True, check=True)
                if os.path.isfile(thumb_out):
                    return thumb_out
            except Exception:
                pass
    return None


def generate_youtube_metadata(
    run_dir: str,
    plan_path: Optional[str] = None,
    profile_path: Optional[str] = None,
    privacy: str = "private",
    category_id: str = "27",
    prefer_mode: Optional[str] = None
) -> Dict[str, Any]:
    """
    Generates tailored YouTube metadata (Title, Description with Chapters, Tags,
    Hashtags, Shorts detection, and thumbnail) matching the exact generated tutorial video.
    """
    eff_plan_path = plan_path or os.path.join(run_dir, "effective-plan.json")
    if not os.path.isfile(eff_plan_path):
        raise FileNotFoundError(f"Plan file not found: {eff_plan_path}")

    with open(eff_plan_path, "r", encoding="utf-8") as f:
        plan = json.load(f)

    project_id = plan.get("project_id", "aplikasi")
    raw_title = plan.get("title", f"Tutorial Aplikasi {project_id.upper()}").strip()
    mode = prefer_mode or plan.get("mode", "mobile")

    final_dir = os.path.join(run_dir, "final")
    mp4_mobile = os.path.join(final_dir, "final-tutorial-mobile.mp4")
    mp4_desktop = os.path.join(final_dir, "final-tutorial-desktop.mp4")
    mp4_generic = os.path.join(final_dir, "final-tutorial.mp4")

    chosen_video = ""
    is_portrait = False
    if mode == "mobile" and os.path.isfile(mp4_mobile):
        chosen_video = mp4_mobile
        is_portrait = True
    elif mode == "desktop" and os.path.isfile(mp4_desktop):
        chosen_video = mp4_desktop
        is_portrait = False
    elif os.path.isfile(mp4_mobile):
        chosen_video = mp4_mobile
        is_portrait = True
    elif os.path.isfile(mp4_desktop):
        chosen_video = mp4_desktop
        is_portrait = False
    elif os.path.isfile(mp4_generic):
        chosen_video = mp4_generic
        is_portrait = mode == "mobile"
    else:
        # Fallback to any mp4 in final_dir
        if os.path.isdir(final_dir):
            mp4s = [f for f in os.listdir(final_dir) if f.endswith(".mp4")]
            if mp4s:
                chosen_video = os.path.join(final_dir, mp4s[0])
                is_portrait = "mobile" in mp4s[0] or mode == "mobile"

    # Measure duration
    dur_sec = 0.0
    rec_manifest = os.path.join(run_dir, "recording", f"recording-manifest-{mode}.json")
    if not os.path.isfile(rec_manifest):
        rec_manifest = os.path.join(run_dir, "recording", "recording-manifest.json")
    if os.path.isfile(rec_manifest):
        try:
            with open(rec_manifest, "r", encoding="utf-8") as f:
                dur_sec = float(json.load(f).get("duration_ms", 0.0)) / 1000.0
        except Exception:
            pass

    if dur_sec <= 0.0 and chosen_video and os.path.isfile(chosen_video):
        cmd = [ffmpeg_exe(), "-i", chosen_video]
        res = subprocess.run(cmd, capture_output=True, text=True)
        m = re.search(r"Duration:\s*(\d+):(\d+):(\d+\.\d+)", res.stderr)
        if m:
            dur_sec = int(m.group(1)) * 3600 + int(m.group(2)) * 60 + float(m.group(3))

    is_shorts_candidate = is_portrait and (dur_sec <= 180.0)

    # ── 1. SEO Title Formulation ──────────────────────────────────────
    yt_title = raw_title
    if is_shorts_candidate:
        # Ensure #Shorts is present in Shorts candidate title
        if "#shorts" not in yt_title.lower():
            if len(yt_title) <= 91:
                yt_title = f"{yt_title} #Shorts"
            else:
                yt_title = f"{yt_title[:90].strip()} #Shorts"
    else:
        if not yt_title.lower().startswith("tutorial") and not yt_title.lower().startswith("panduan"):
            yt_title = f"Tutorial: {yt_title}"
    yt_title = yt_title[:100].strip()

    # ── 2. Timestamps / Chapters Extraction ───────────────────────────
    chapters = extract_scene_timestamps(run_dir, plan)

    # ── 3. SEO Tags & Keywords Formulation ────────────────────────────
    tags: List[str] = []
    base_keywords = [
        project_id.lower(),
        f"tutorial {project_id.lower()}",
        f"panduan {project_id.lower()}",
        "tutorial aplikasi",
        "panduan sistem",
    ]
    if "siasek" in project_id.lower() or "presensi" in raw_title.lower() or "absensi" in raw_title.lower():
        base_keywords.extend([
            "siasek presensi",
            "presensi sekolah",
            "absensi online",
            "portal orang tua",
            "wali murid",
            "aplikasi sekolah",
            "kehadiran siswa",
            "tutorial absensi",
        ])
    elif "apotek" in project_id.lower():
        base_keywords.extend([
            "apotek sehat",
            "sistem kasir apotek",
            "manajemen obat",
            "pos apotek",
            "tutorial apotek",
        ])

    # Extract words from title & scenes
    title_words = [w.lower() for w in re.findall(r"\b[A-Za-z0-9_-]{3,}\b", raw_title)]
    for w in title_words:
        if w not in ["panduan", "cara", "dengan", "dan", "untuk", "pada"]:
            base_keywords.append(w)

    for ch in chapters:
        c_title = ch["title"].lower()
        if len(c_title) > 3 and c_title not in base_keywords:
            base_keywords.append(c_title)

    # Deduplicate while preserving order & limiting to 500 characters
    seen = set()
    total_tag_chars = 0
    for kw in base_keywords:
        kw_clean = kw.strip()
        if kw_clean and kw_clean not in seen and total_tag_chars + len(kw_clean) + 1 <= 480:
            seen.add(kw_clean)
            tags.append(kw_clean)
            total_tag_chars += len(kw_clean) + 1

    # ── 4. Hashtags ───────────────────────────────────────────────────
    hashtags = [f"#{re.sub(r'[^A-Za-z0-9]', '', project_id.upper())}", "#TutorialAplikasi"]
    if "siasek" in project_id.lower() or "presensi" in raw_title.lower():
        hashtags.extend(["#SIASEK", "#PresensiSekolah", "#OrangTuaSiswa", "#AplikasiPendidikan"])
    elif "apotek" in project_id.lower():
        hashtags.extend(["#ApotekSehat", "#ManajemenApotek", "#AplikasiKasir"])
    if is_shorts_candidate:
        hashtags.insert(0, "#Shorts")
    hashtags_str = " ".join(dict.fromkeys(hashtags))

    # ── 5. Description Formulation with Chapters ─────────────────────
    desc_lines = []
    desc_lines.append(f"{raw_title}\n")
    desc_lines.append(f"Video panduan resmi cara penggunaan fitur pada aplikasi {project_id.upper()}. Ikuti langkah-langkah mudah di bawah ini untuk memahami alur penggunaan secara lengkap.\n")

    if chapters:
        desc_lines.append("🕒 DAFTAR ISI & TIMESTAMPS:")
        for ch in chapters:
            desc_lines.append(f"{ch['timestamp']} - {ch['title']}")
        desc_lines.append("")

    desc_lines.append("📌 RINGKASAN LANGKAH-LANGKAH:")
    for i, ch in enumerate(chapters, 1):
        narr_snippet = ch.get("narration", "")
        if narr_snippet:
            # First sentence of narration
            first_sent = narr_snippet.split(".")[0].strip()
            desc_lines.append(f"{i}. {ch['title']}: {first_sent}.")
        else:
            desc_lines.append(f"{i}. {ch['title']}")
    desc_lines.append("")

    desc_lines.append("✨ FITUR UTAMA & KEUNGGULAN:")
    desc_lines.append(f"- Panduan interaktif visual beresolusi tinggi (60 FPS).")
    desc_lines.append(f"- Navigasi responsif dan ramah pengguna.")
    desc_lines.append(f"- Terintegrasi langsung dengan database sistem.")
    desc_lines.append("")

    desc_lines.append(f"🌐 Akses Sistem: http://localhost:8000")
    desc_lines.append("Jangan lupa Like, Comment, dan Subscribe untuk mendapatkan panduan fitur terbaru lainnya!")
    desc_lines.append("")
    desc_lines.append(hashtags_str)

    yt_description = "\n".join(desc_lines)[:5000].strip()

    # ── 6. Thumbnail Selection / Export ───────────────────────────────
    yt_dir = os.path.join(run_dir, "youtube")
    os.makedirs(yt_dir, exist_ok=True)
    raw_thumb = select_best_thumbnail(run_dir, target_aspect="9:16" if is_portrait else "16:9")
    dest_thumb = os.path.join(yt_dir, "thumbnail.png")
    if raw_thumb and os.path.isfile(raw_thumb) and os.path.abspath(raw_thumb) != os.path.abspath(dest_thumb):
        try:
            shutil.copy2(raw_thumb, dest_thumb)
        except Exception:
            pass

    # ── 7. YouTube Data API v3 Payload Structure ──────────────────────
    payload = {
        "snippet": {
            "title": yt_title,
            "description": yt_description,
            "tags": tags,
            "categoryId": str(category_id),
            "defaultLanguage": "id",
            "defaultAudioLanguage": "id"
        },
        "status": {
            "privacyStatus": privacy,
            "selfDeclaredMadeForKids": False,
            "embeddable": True,
            "license": "youtube"
        },
        "_metadata": {
            "project_id": project_id,
            "video_path": chosen_video,
            "duration_sec": round(dur_sec, 2),
            "is_shorts": is_shorts_candidate,
            "aspect_ratio": "9:16" if is_portrait else "16:9",
            "thumbnail_path": dest_thumb if os.path.isfile(dest_thumb) else (raw_thumb or ""),
            "generated_at": datetime.now(timezone.utc).isoformat()
        }
    }

    # Save JSON payload
    payload_file = os.path.join(yt_dir, "youtube-metadata.json")
    with open(payload_file, "w", encoding="utf-8") as f:
        json.dump(payload, f, indent=2, ensure_ascii=False)

    # ── 8. Markdown Copy-Paste Upload Kit ──────────────────────────────
    kit_lines = [
        f"# YouTube Upload Kit — `{plan.get('project_id', 'siasek')}`",
        f"- **Tanggal Dibuat**: `{datetime.now(timezone.utc).strftime('%Y-%m-%d %H:%M:%S UTC')}`",
        f"- **Durasi Video**: `{dur_sec:.2f} detik` ({'YouTube Shorts' if is_shorts_candidate else 'Standard Video'})",
        f"- **File Video**: `{chosen_video}`",
        f"- **Thumbnail**: `{dest_thumb if os.path.isfile(dest_thumb) else 'Otomatis dari video'}`",
        "",
        "---",
        "",
        "## 1. Judul Video (Title)",
        "```text",
        yt_title,
        "```",
        f"*(Karakter: {len(yt_title)}/100)*",
        "",
        "## 2. Deskripsi Video (Description with Timestamps)",
        "```text",
        yt_description,
        "```",
        f"*(Karakter: {len(yt_description)}/5000)*",
        "",
        "## 3. Tag Video (Tags / Keywords)",
        "```text",
        ", ".join(tags),
        "```",
        "",
        "## 4. Pengaturan Upload yang Disarankan",
        f"- **Kategori**: `27` (Pendidikan / Education) atau `28` (Sains & Teknologi)",
        f"- **Visibilitas Awal**: `{privacy}` (disarankan private/unlisted untuk review akhir)",
        f"- **Dibuat untuk Anak-Anak**: `Tidak` (`selfDeclaredMadeForKids: false`)",
        f"- **Bahasa Video**: `Indonesia (id)`",
        "",
        "---",
        "",
        "## 5. Perintah CLI Upload Otomatis (YouTube Data API v3)",
        "```powershell",
        "# Langkah A: Autentikasi OAuth Google YouTube (hanya sekali):",
        "python .agents/skills/app-tutorial-video/scripts/cli.py youtube-auth --client-secrets .credentials/client_secret.json",
        "",
        "# Langkah B: Upload video langsung ke channel YouTube:",
        f"python .agents/skills/app-tutorial-video/scripts/cli.py youtube-upload --run-dir \"{run_dir}\" --privacy {privacy} --confirm-upload",
        "```",
    ]

    kit_file = os.path.join(yt_dir, "youtube-upload-kit.md")
    with open(kit_file, "w", encoding="utf-8") as f:
        f.write("\n".join(kit_lines) + "\n")

    return {
        "status": "OK",
        "youtube_dir": yt_dir,
        "payload_json": payload_file,
        "upload_kit_md": kit_file,
        "thumbnail": dest_thumb if os.path.isfile(dest_thumb) else None,
        "title": yt_title,
        "is_shorts": is_shorts_candidate,
        "video_path": chosen_video,
        "chapters_count": len(chapters)
    }


def run_youtube_oauth(client_secrets_path: str, creds_dir: str) -> Dict[str, Any]:
    from google_auth_oauthlib.flow import InstalledAppFlow

    if not os.path.isfile(client_secrets_path):
        return {
            "status": "CREDENTIALS_REQUIRED",
            "message": f"File OAuth Client Secret tidak ditemukan di: {client_secrets_path}",
            "setup_guide": (
                "1. Buka Google Cloud Console -> APIs & Services -> Credentials.\n"
                "2. Buat OAuth 2.0 Client ID jenis 'Desktop app'.\n"
                "3. Pastikan YouTube Data API v3 sudah diaktifkan di Google Cloud Project Anda.\n"
                "4. Unduh file JSON dan simpan ke: .credentials/client_secret.json\n"
            ),
        }

    flow = InstalledAppFlow.from_client_secrets_file(client_secrets_path, YOUTUBE_UPLOAD_SCOPE)
    creds = flow.run_local_server(port=0)
    token_path = os.path.join(creds_dir, "youtube_token.json")
    with open(token_path, "w", encoding="utf-8") as f:
        f.write(creds.to_json())

    return {
        "status": "OK",
        "token_saved": token_path,
        "scopes": YOUTUBE_UPLOAD_SCOPE,
    }


def upload_to_youtube(
    run_dir: str,
    privacy: str = "private",
    creds_dir: Optional[str] = None,
    confirm_upload: bool = False,
    prefer_mode: Optional[str] = None,
    client_secrets_path: Optional[str] = None
) -> Dict[str, Any]:
    """
    Performs video upload to YouTube using official Google API client library
    with resumable chunks, thumbnail upload, and human confirmation gate.
    """
    from google.auth.transport.requests import Request
    from google.oauth2.credentials import Credentials
    from googleapiclient.discovery import build
    from googleapiclient.http import MediaFileUpload

    # Human confirmation gate
    if not confirm_upload:
        return {
            "status": "NEEDS_HUMAN_CONFIRMATION",
            "message": (
                "Pencegahan upload tak sengaja aktif. Harap tambahkan flag `--confirm-upload` "
                "untuk mengonfirmasi upload video ke Channel YouTube resmi."
            )
        }

    c_dir = creds_dir or resolve_credentials_dir()
    token_path = os.path.join(c_dir, "youtube_token.json")

    if not os.path.isfile(token_path):
        sec_path = client_secrets_path or os.path.join(c_dir, "client_secret.json")
        return {
            "status": "CREDENTIALS_REQUIRED",
            "message": f"Token OAuth YouTube belum ditemukan di {token_path}.",
            "setup_command": f"python .agents/skills/app-tutorial-video/scripts/cli.py youtube-auth --client-secrets \"{sec_path}\""
        }

    creds = Credentials.from_authorized_user_file(token_path, YOUTUBE_UPLOAD_SCOPE)
    if creds and creds.expired and creds.refresh_token:
        creds.refresh(Request())
        with open(token_path, "w", encoding="utf-8") as f:
            f.write(creds.to_json())

    # Build or reload metadata payload
    yt_meta_path = os.path.join(run_dir, "youtube", "youtube-metadata.json")
    if not os.path.isfile(yt_meta_path):
        res_meta = generate_youtube_metadata(run_dir, privacy=privacy, prefer_mode=prefer_mode)
        yt_meta_path = res_meta["payload_json"]

    with open(yt_meta_path, "r", encoding="utf-8") as f:
        meta_payload = json.load(f)

    meta_payload["status"]["privacyStatus"] = privacy
    video_path = meta_payload.get("_metadata", {}).get("video_path")

    if not video_path or not os.path.isfile(video_path):
        raise FileNotFoundError(f"Video file to upload does not exist: {video_path}")

    youtube = build("youtube", "v3", credentials=creds)
    media = MediaFileUpload(video_path, mimetype="video/mp4", chunksize=4 * 1024 * 1024, resumable=True)

    body = {
        "snippet": meta_payload["snippet"],
        "status": meta_payload["status"]
    }

    print(f"[YOUTUBE] Uploading video: {video_path}...")
    print(f"[YOUTUBE] Title: {body['snippet']['title']}")
    print(f"[YOUTUBE] Privacy: {body['status']['privacyStatus']}")

    req = youtube.videos().insert(part="snippet,status", body=body, media_body=media)
    response = None
    while response is None:
        status, response = req.next_chunk()
        if status:
            prog = int(status.progress() * 100)
            print(f"[YOUTUBE] Upload progress: {prog}%")

    video_id = response.get("id")
    is_shorts = meta_payload.get("_metadata", {}).get("is_shorts", False)
    watch_url = f"https://www.youtube.com/shorts/{video_id}" if is_shorts else f"https://youtu.be/{video_id}"

    print(f"[YOUTUBE] Upload successful! Video ID: {video_id}")
    print(f"[YOUTUBE] URL: {watch_url}")

    # Set custom thumbnail if available
    thumb_path = meta_payload.get("_metadata", {}).get("thumbnail_path")
    thumb_uploaded = False
    if thumb_path and os.path.isfile(thumb_path):
        try:
            print(f"[YOUTUBE] Uploading custom thumbnail from {thumb_path}...")
            youtube.thumbnails().set(
                videoId=video_id,
                media_body=MediaFileUpload(thumb_path, mimetype="image/png")
            ).execute()
            thumb_uploaded = True
            print("[YOUTUBE] Thumbnail uploaded successfully.")
        except Exception as e:
            print(f"[YOUTUBE] Warning: Thumbnail upload failed ({e})")

    receipt = {
        "status": "UPLOADED",
        "video_id": video_id,
        "watch_url": watch_url,
        "privacy": privacy,
        "is_shorts": is_shorts,
        "thumbnail_uploaded": thumb_uploaded,
        "uploaded_at": datetime.now(timezone.utc).isoformat(),
        "api_response": response
    }

    receipt_path = os.path.join(run_dir, "youtube", "upload-receipt.json")
    with open(receipt_path, "w", encoding="utf-8") as f:
        json.dump(receipt, f, indent=2, ensure_ascii=False)

    return receipt

