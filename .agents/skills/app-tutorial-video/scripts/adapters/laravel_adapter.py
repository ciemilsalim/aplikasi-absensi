#!/usr/bin/env python
"""
laravel_adapter.py — Project Adapter for Laravel applications.
Inspects routes/web.php, controllers, and Blade templates.
"""

import json
import os
import re
from typing import Any, Dict, List, Optional

from .base_adapter import BaseAdapter


class LaravelAdapter(BaseAdapter):
    """Adapter for Laravel applications."""

    def __init__(self):
        super().__init__(name="laravel", framework="Laravel")

    def detect(self, workspace_root: str) -> Optional[Dict[str, Any]]:
        artisan_path = os.path.join(workspace_root, "artisan")
        composer_path = os.path.join(workspace_root, "composer.json")

        if not os.path.isfile(artisan_path) and not os.path.isfile(composer_path):
            return None

        is_laravel = False
        details: Dict[str, Any] = {}

        if os.path.isfile(composer_path):
            try:
                with open(composer_path, "r", encoding="utf-8", errors="ignore") as f:
                    cdata = json.load(f)
                    reqs = cdata.get("require", {})
                    if "laravel/framework" in reqs:
                        is_laravel = True
                        details["laravel_version"] = reqs["laravel/framework"]
                        details["project_name"] = cdata.get("name", "laravel-app")
            except Exception:
                pass

        if os.path.isfile(artisan_path):
            is_laravel = True

        if not is_laravel:
            return None

        # Check for auth scaffolding (Breeze, Jetstream, Fortify, custom)
        routes_web = os.path.join(workspace_root, "routes", "web.php")
        has_login = False
        if os.path.isfile(routes_web):
            try:
                with open(routes_web, "r", encoding="utf-8", errors="ignore") as f:
                    content = f.read()
                    if "login" in content.lower():
                        has_login = True
            except Exception:
                pass

        return {
            "framework": "laravel",
            "confidence": 0.95,
            "entry_points": ["public/index.php", "routes/web.php"],
            "package_manifest": "composer.json",
            "server_recommendation": "php artisan serve --port 8000",
            "default_port": 8000,
            "has_login_route": has_login,
            "details": details
        }

    def discover_pages(self, workspace_root: str, profile: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        pages = []
        routes_web = os.path.join(workspace_root, "routes", "web.php")
        if not os.path.isfile(routes_web):
            return pages

        try:
            with open(routes_web, "r", encoding="utf-8", errors="ignore") as f:
                content = f.read()

            # Parse simple Route::get('path', ...)
            route_matches = re.findall(r"Route::get\(\s*['\"]([^'\"]+)['\"]", content)
            for r in set(route_matches):
                clean_r = r.strip("/")
                requires_auth = "auth" in content and ("middleware('auth')" in content or "middleware(['auth'" in content)
                pages.append({
                    "id": f"page_{clean_r.replace('/', '_') or 'home'}",
                    "title": clean_r.replace("-", " ").replace("_", " ").title() or "Home",
                    "path_or_url": f"/{clean_r}",
                    "source_file": "routes/web.php",
                    "requires_auth": requires_auth,
                    "key_selectors": ["main", "body", "nav"]
                })
        except Exception:
            pass

        return pages

    def discover_workflows(self, workspace_root: str, profile: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        workflows = []
        pages = self.discover_pages(workspace_root, profile)

        # Check for profile-specified or existing workflows
        if profile and "workflows" in profile:
            return profile["workflows"]

        # Default heuristic workflows from discovered pages
        for p in pages:
            workflows.append({
                "workflow_id": f"flow_{p['id']}",
                "name": f"Panduan Navigasi {p['title']}",
                "target_users": ["user"],
                "description": f"Membuka halaman {p['title']} dan meninjau informasi antarmuka.",
                "path": p["path_or_url"],
                "steps": [
                    {"do": "goto", "url": p["path_or_url"]},
                    {"do": "wait_for", "selector": "body"}
                ]
            })

        return workflows
