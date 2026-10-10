#!/usr/bin/env python
"""
profile.py — Project Profile Manager for App Tutorial Video Skill.
Manages project identity, framework type, viewport, URLs, auth rules, and recording defaults.
"""

import json
import os
from typing import Any, Dict, Optional


DEFAULT_VIEWPORTS = {
    "mobile": {"width": 414, "height": 736, "target_resolution": [1080, 1920]},
    "desktop": {"width": 1920, "height": 1080, "target_resolution": [1920, 1080]}
}

DEFAULT_VOICE = {
    "name": "id-ID-GadisNeural",
    "rate": "-2%",
    "pitch": "+0Hz"
}


def create_default_profile(
    project_id: str,
    project_name: str,
    base_url: str,
    framework: str = "generic_web",
    mode: str = "mobile",
    auth: Optional[Dict[str, Any]] = None,
    server_cmd: Optional[str] = None
) -> Dict[str, Any]:
    """Create a new standard Project Profile dictionary."""
    vp_cfg = DEFAULT_VIEWPORTS.get(mode, DEFAULT_VIEWPORTS["mobile"])
    return {
        "version": "1.0.0",
        "project_id": project_id,
        "project_name": project_name,
        "framework": framework,
        "app_type": "web",
        "base_url": base_url.rstrip("/"),
        "local_server_cmd": server_cmd,
        "mode": mode,
        "viewport": {"width": vp_cfg["width"], "height": vp_cfg["height"]},
        "target_resolution": vp_cfg["target_resolution"],
        "fps": 60,
        "voice": dict(DEFAULT_VOICE),
        "reaction_delay_ms": 300,
        "result_hold_ms": 1200,
        "color_scheme": "light",
        "auth": auth or {"type": "none"},
        "navigation_rules": {
            "default_wait_selector": "body",
            "page_load_timeout_ms": 20000
        },
        "workflows": []
    }


def load_profile(path: str) -> Dict[str, Any]:
    """Load and validate a Project Profile from disk."""
    if not os.path.isfile(path):
        raise FileNotFoundError(f"Project Profile not found at: {path}")
    with open(path, "r", encoding="utf-8") as f:
        data = json.load(f)

    # Basic invariant validations
    required_keys = ["project_id", "project_name", "base_url"]
    for k in required_keys:
        if k not in data or not data[k]:
            raise ValueError(f"Project Profile missing required field: '{k}' in {path}")

    return data


def save_profile(profile_data: Dict[str, Any], path: str) -> str:
    """Save Project Profile to disk."""
    os.makedirs(os.path.dirname(os.path.abspath(path)), exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        json.dump(profile_data, f, indent=2, ensure_ascii=False)
    return path


def apply_profile_to_plan(profile: Dict[str, Any], plan: Dict[str, Any]) -> Dict[str, Any]:
    """
    Apply profile defaults into plan if not explicitly overridden by plan.
    Preserves plan precedence.
    """
    merged = dict(plan)
    merged.setdefault("project_id", profile.get("project_id"))
    merged.setdefault("mode", profile.get("mode", "mobile"))
    merged.setdefault("fps", profile.get("fps", 60))
    merged.setdefault("viewport", profile.get("viewport"))
    merged.setdefault("target_resolution", profile.get("target_resolution"))
    merged.setdefault("voice", profile.get("voice", DEFAULT_VOICE))
    merged.setdefault("reaction_delay_ms", profile.get("reaction_delay_ms", 300))
    merged.setdefault("color_scheme", profile.get("color_scheme", "light"))

    # Resolve relative URLs in setup or scenes using base_url
    base_url = profile.get("base_url", "").rstrip("/")
    if base_url:
        if "setup" in merged:
            for s in merged["setup"]:
                if s.get("do") == "goto" and s.get("url", "").startswith("/"):
                    s["url"] = base_url + s["url"]
        if "scenes" in merged:
            for sc in merged["scenes"]:
                for st in sc.get("steps", []):
                    if st.get("do") == "goto" and st.get("url", "").startswith("/"):
                        st["url"] = base_url + st["url"]

    return merged
