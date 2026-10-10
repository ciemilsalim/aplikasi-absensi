#!/usr/bin/env python
"""
generic_web_adapter.py — Universal Fallback Web Adapter.
Handles any web application by inspecting HTML structures, forms, links, and buttons.
"""

import os
import re
from typing import Any, Dict, List, Optional

from .base_adapter import BaseAdapter


class GenericWebAdapter(BaseAdapter):
    """Universal fallback adapter for HTML / general web projects."""

    def __init__(self):
        super().__init__(name="generic_web", framework="Generic Web Application")

    def detect(self, workspace_root: str) -> Optional[Dict[str, Any]]:
        # Always detects if at least one HTML, PHP, or web asset exists
        has_web = False
        html_files = []
        for root, _, files in os.walk(workspace_root):
            # Do not traverse vendor or node_modules
            if any(p in root for p in ["node_modules", "vendor", ".git", ".tutorial-video"]):
                continue
            for f in files:
                if f.endswith((".html", ".htm", ".php")):
                    has_web = True
                    html_files.append(os.path.relpath(os.path.join(root, f), workspace_root))
                    if len(html_files) >= 5:
                        break
            if len(html_files) >= 5:
                break

        if not has_web:
            return None

        return {
            "framework": "generic_web",
            "confidence": 0.50,
            "entry_points": html_files[:3],
            "package_manifest": None,
            "server_recommendation": "python -m http.server 8080",
            "default_port": 8080,
            "details": {"discovered_files": html_files}
        }

    def discover_pages(self, workspace_root: str, profile: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        pages = []
        for root, _, files in os.walk(workspace_root):
            if any(p in root for p in ["node_modules", "vendor", ".git", ".tutorial-video"]):
                continue
            for f in files:
                if f.endswith((".html", ".htm")):
                    rel_p = os.path.relpath(os.path.join(root, f), workspace_root).replace("\\", "/")
                    name = os.path.splitext(f)[0].replace("-", " ").title()
                    pages.append({
                        "id": f"page_{f.replace('.', '_')}",
                        "title": name,
                        "path_or_url": f"/{rel_p}",
                        "source_file": rel_p,
                        "requires_auth": False,
                        "key_selectors": ["body", "main", "h1", "button"]
                    })
        return pages

    def discover_workflows(self, workspace_root: str, profile: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        if profile and "workflows" in profile:
            return profile["workflows"]

        pages = self.discover_pages(workspace_root, profile)
        workflows = []
        for p in pages:
            workflows.append({
                "workflow_id": f"flow_{p['id']}",
                "name": f"Panduan Menggunakan {p['title']}",
                "target_users": ["user"],
                "description": f"Membuka dan menggunakan halaman {p['title']}.",
                "path": p["path_or_url"],
                "steps": [
                    {"do": "goto", "url": p["path_or_url"]},
                    {"do": "wait_for", "selector": "body"}
                ]
            })
        return workflows
