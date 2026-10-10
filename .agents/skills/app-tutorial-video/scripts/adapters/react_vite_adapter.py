#!/usr/bin/env python
"""
react_vite_adapter.py — Project Adapter for React + Vite / SPA applications.
Inspects package.json, vite.config.js, index.html, and src/ components.
"""

import json
import os
import re
from typing import Any, Dict, List, Optional

from .base_adapter import BaseAdapter


class ReactViteAdapter(BaseAdapter):
    """Adapter for React + Vite and modern JavaScript/TypeScript SPAs."""

    def __init__(self):
        super().__init__(name="react_vite", framework="React + Vite")

    def detect(self, workspace_root: str) -> Optional[Dict[str, Any]]:
        pkg_path = os.path.join(workspace_root, "package.json")
        if not os.path.isfile(pkg_path):
            return None

        is_react = False
        is_vite = False
        details: Dict[str, Any] = {}

        try:
            with open(pkg_path, "r", encoding="utf-8", errors="ignore") as f:
                data = json.load(f)
                deps = {**data.get("dependencies", {}), **data.get("devDependencies", {})}

                if "react" in deps or "react-dom" in deps:
                    is_react = True
                    details["react_version"] = deps.get("react")
                if "vite" in deps or os.path.isfile(os.path.join(workspace_root, "vite.config.js")) or os.path.isfile(os.path.join(workspace_root, "vite.config.ts")):
                    is_vite = True
                    details["vite_version"] = deps.get("vite", "detected")

                details["name"] = data.get("name", "vite-app")
        except Exception:
            return None

        if not (is_react or is_vite):
            return None

        framework_desc = "React + Vite" if (is_react and is_vite) else ("React" if is_react else "Vite SPA")

        return {
            "framework": "react_vite",
            "framework_title": framework_desc,
            "confidence": 0.95,
            "entry_points": ["index.html", "src/main.jsx", "src/main.tsx", "src/App.jsx", "src/App.tsx"],
            "package_manifest": "package.json",
            "server_recommendation": "npm run preview -- --port 4173",
            "dev_server_command": "npm run dev -- --port 5173",
            "default_port": 4173,
            "details": details
        }

    def discover_pages(self, workspace_root: str, profile: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        pages = []
        src_dir = os.path.join(workspace_root, "src")

        # Heuristic 1: Inspect index.html
        pages.append({
            "id": "home",
            "title": "Halaman Utama",
            "path_or_url": "/",
            "source_file": "index.html",
            "requires_auth": False,
            "key_selectors": ["#root", "main", "nav", "header", "button"]
        })

        # Heuristic 2: Discover component sections inside src/
        if os.path.isdir(src_dir):
            comp_dir = os.path.join(src_dir, "components")
            target_dirs = [comp_dir] if os.path.isdir(comp_dir) else [src_dir]
            for cdir in target_dirs:
                for fname in sorted(os.listdir(cdir)):
                    if fname.endswith((".jsx", ".tsx", ".js", ".ts")):
                        base_name = os.path.splitext(fname)[0]
                        if base_name in ["main", "index", "AnimatedBackground"]:
                            continue
                        section_id = base_name.lower()
                        pages.append({
                            "id": f"section_{section_id}",
                            "title": f"Bagian {base_name}",
                            "path_or_url": f"/#{section_id}",
                            "source_file": os.path.relpath(os.path.join(cdir, fname), workspace_root),
                            "requires_auth": False,
                            "key_selectors": [f"#{section_id}", f"[data-section='{section_id}']", "button", "a"]
                        })

        return pages

    def discover_workflows(self, workspace_root: str, profile: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        workflows = []

        if profile and "workflows" in profile:
            return profile["workflows"]

        pages = self.discover_pages(workspace_root, profile)
        for p in pages:
            workflows.append({
                "workflow_id": f"flow_{p['id']}",
                "name": f"Panduan Interaksi {p['title']}",
                "target_users": ["visitor", "user"],
                "description": f"Mengeksplorasi antarmuka dan interaksi pada {p['title']}.",
                "path": p["path_or_url"],
                "steps": [
                    {"do": "goto", "url": p["path_or_url"]},
                    {"do": "wait_for", "selector": "#root"}
                ]
            })

        return workflows
