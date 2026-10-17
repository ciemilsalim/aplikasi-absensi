#!/usr/bin/env python3
"""
laravel_inspector.py - Laravel Autopilot Project & Route Inspector
Inspects project metadata, installed packages, frontend stack,
and registered routes using Artisan JSON without mutating any code or state.
Security hardened: strict shell=False, UTF-8 decoding, deep redaction.
"""

import argparse
import json
import os
import re
import shutil
import subprocess
import sys
from pathlib import Path

# Unified deep redaction patterns
REDACTION_PATTERNS = [
    (r'-----BEGIN [A-Z\s]+ PRIVATE KEY-----[\s\S]+?-----END [A-Z\s]+ PRIVATE KEY-----', '[REDACTED_PRIVATE_KEY]'),
    (r'(?i)(password|secret|key|token|api_key|db_password|auth_token|client_secret)=(?!\[REDACTED)([^\s&;\'"]+)', r'\1=[REDACTED]'),
    (r'(?i)bearer\s+[a-zA-Z0-9_\-\.]{10,}', 'Bearer [REDACTED]'),
    (r'\b(AKIA[0-9A-Z]{16})\b', '[REDACTED_AWS_KEY]'),
    (r'\b(gh[pous]_[a-zA-Z0-9]{36,})\b', '[REDACTED_GITHUB_TOKEN]'),
    (r'base64:[a-zA-Z0-9+/=]{20,}', 'base64:[REDACTED]'),
    (r'(?i)([a-z]+://[^:]+):([^@]+)@', r'\1:[REDACTED]@'),
    (r'(?i)(password\s*=\s*)([^\s;]+)', r'\1[REDACTED]'),
]


def run_cmd(cmd, cwd=None, timeout=20):
    """Run an argument array command safely with shell=False."""
    try:
        proc = subprocess.run(
            cmd,
            cwd=cwd,
            capture_output=True,
            timeout=timeout,
            shell=False  # Strict invariant
        )
        out = proc.stdout.decode("utf-8", errors="replace").strip()
        err = proc.stderr.decode("utf-8", errors="replace").strip()
        return proc.returncode, out, err
    except subprocess.TimeoutExpired:
        return -1, "", f"Command timed out after {timeout}s"
    except Exception as e:
        return -1, "", str(e)


def sanitize_output(text):
    """Mask common sensitive patterns such as tokens, keys, passwords."""
    if not text:
        return ""
    sanitized = text
    for pat, repl in REDACTION_PATTERNS:
        sanitized = re.sub(pat, repl, sanitized)
    return sanitized


def inspect_composer(project_root):
    """Inspect composer.json and composer.lock for versions and packages."""
    root_path = Path(project_root).resolve()
    composer_json_path = root_path / "composer.json"
    composer_lock_path = root_path / "composer.lock"

    metadata = {
        "laravel_version_constraint": "unknown",
        "laravel_version_installed": "unknown",
        "php_constraint": "unknown",
        "packages": [],
        "dev_packages": [],
    }

    if composer_json_path.is_file():
        try:
            with open(composer_json_path, "r", encoding="utf-8") as f:
                cjson = json.load(f)
            req = cjson.get("require", {})
            dev_req = cjson.get("require-dev", {})
            metadata["laravel_version_constraint"] = req.get("laravel/framework", "not found")
            metadata["php_constraint"] = req.get("php", "not specified")
            metadata["packages"] = list(req.keys())
            metadata["dev_packages"] = list(dev_req.keys())
        except Exception as e:
            metadata["error"] = f"Failed to parse composer.json: {e}"

    if composer_lock_path.is_file():
        try:
            with open(composer_lock_path, "r", encoding="utf-8") as f:
                clock = json.load(f)
            for pkg in clock.get("packages", []):
                if pkg.get("name") == "laravel/framework":
                    metadata["laravel_version_installed"] = pkg.get("version", "unknown")
                    break
        except Exception:
            pass

    return metadata


def detect_frontend_stack(project_root, composer_meta):
    """Detect frontend technologies (Blade, Inertia, Livewire, React, Vue, Alpine, Tailwind)."""
    root_path = Path(project_root).resolve()
    package_json_path = root_path / "package.json"

    frontend = {
        "primary_template": "Blade",
        "is_inertia": False,
        "is_livewire": False,
        "spa_framework": None,
        "css_engine": None,
        "client_libraries": [],
    }

    all_composer = composer_meta.get("packages", []) + composer_meta.get("dev_packages", [])
    if any("livewire" in p.lower() for p in all_composer):
        frontend["is_livewire"] = True
        frontend["primary_template"] = "Livewire / Blade"
    if any("inertia" in p.lower() for p in all_composer):
        frontend["is_inertia"] = True
        frontend["primary_template"] = "Inertia SPA"

    if package_json_path.is_file():
        try:
            with open(package_json_path, "r", encoding="utf-8") as f:
                pkg = json.load(f)
            deps = {**pkg.get("dependencies", {}), **pkg.get("devDependencies", {})}
            if "@inertiajs/react" in deps or "react" in deps:
                frontend["spa_framework"] = "React"
            elif "@inertiajs/vue3" in deps or "vue" in deps:
                frontend["spa_framework"] = "Vue"
            elif "svelte" in deps:
                frontend["spa_framework"] = "Svelte"

            if "tailwindcss" in deps or "@tailwindcss/vite" in deps:
                frontend["css_engine"] = "Tailwind CSS"
            elif "bootstrap" in deps:
                frontend["css_engine"] = "Bootstrap"

            if "alpinejs" in deps:
                frontend["client_libraries"].append("Alpine.js")
            if "axios" in deps:
                frontend["client_libraries"].append("Axios")
            if "html5-qrcode" in deps:
                frontend["client_libraries"].append("html5-qrcode")
        except Exception:
            pass

    return frontend


def detect_auth_system(composer_meta, project_root):
    """Identify authentication and authorization packages and structure."""
    all_packages = composer_meta.get("packages", []) + composer_meta.get("dev_packages", [])
    root_path = Path(project_root).resolve()

    auth = {
        "starter_kit": "Custom / None",
        "api_guard": "Session / Default",
        "has_policies": (root_path / "app" / "Policies").is_dir(),
        "has_middleware": (root_path / "app" / "Http" / "Middleware").is_dir(),
    }

    if "laravel/breeze" in all_packages:
        auth["starter_kit"] = "Laravel Breeze"
    elif "laravel/jetstream" in all_packages:
        auth["starter_kit"] = "Laravel Jetstream"
    elif "laravel/fortify" in all_packages:
        auth["starter_kit"] = "Laravel Fortify"

    if "laravel/sanctum" in all_packages:
        auth["api_guard"] = "Laravel Sanctum"
    elif "laravel/passport" in all_packages:
        auth["api_guard"] = "Laravel Passport"

    return auth


def inspect_routes(project_root, filter_pattern=None, limit=None):
    """
    Execute `php artisan route:list --json` to get exact routing topology.
    Uses argument array with shell=False.
    """
    root_path = Path(project_root).resolve()
    php_path = shutil.which("php")
    if not php_path:
        return {
            "success": False,
            "error": "PHP executable not found in PATH",
            "routes": [],
            "total_count": 0
        }

    artisan_file = str(root_path / "artisan")
    if not os.path.isfile(artisan_file):
        return {
            "success": False,
            "error": "artisan file not found at project root",
            "routes": [],
            "total_count": 0
        }

    cmd = [php_path, artisan_file, "route:list", "--json"]
    code, out, err = run_cmd(cmd, cwd=str(root_path), timeout=30)

    if code != 0:
        return {
            "success": False,
            "error": sanitize_output(err or out or "Failed to execute route:list"),
            "routes": [],
            "total_count": 0
        }

    try:
        raw_routes = json.loads(out)
    except Exception as e:
        return {
            "success": False,
            "error": f"Failed to parse artisan route:list output: {e}",
            "routes": [],
            "total_count": 0
        }

    formatted_routes = []
    for r in raw_routes:
        method = r.get("method", "")
        uri = r.get("uri", "")
        name = r.get("name") or ""
        action = r.get("action", "")
        middleware = r.get("middleware", [])

        # Filter if requested
        if filter_pattern:
            pat = filter_pattern.lower()
            if pat not in uri.lower() and pat not in name.lower() and pat not in action.lower():
                continue

        formatted_routes.append({
            "method": method,
            "uri": uri,
            "name": name if name else None,
            "action": action,
            "middleware": middleware if isinstance(middleware, list) else [middleware],
        })

    total = len(formatted_routes)
    routes_slice = formatted_routes[:limit] if limit else formatted_routes

    return {
        "success": True,
        "total_count": total,
        "filtered_count": len(routes_slice),
        "routes": routes_slice
    }


def main():
    parser = argparse.ArgumentParser(description="Laravel Autopilot Project & Route Inspector")
    parser.add_argument("--project-path", default=".", help="Path to Laravel project root")
    parser.add_argument("--routes", action="store_true", help="Include registered route list")
    parser.add_argument("--filter-route", default=None, help="Filter routes by keyword (URI, name, or controller)")
    parser.add_argument("--limit-routes", type=int, default=50, help="Max routes to return (default: 50)")
    parser.add_argument("--json", action="store_true", help="Output results in JSON format")

    args = parser.parse_args()
    root = Path(args.project_path).resolve()

    composer_meta = inspect_composer(root)
    frontend = detect_frontend_stack(root, composer_meta)
    auth = detect_auth_system(composer_meta, root)

    report = {
        "project_root": str(root),
        "laravel_framework": {
            "constraint": composer_meta.get("laravel_version_constraint"),
            "installed": composer_meta.get("laravel_version_installed"),
            "php_constraint": composer_meta.get("php_constraint"),
        },
        "frontend_stack": frontend,
        "auth_system": auth,
        "packages_summary": {
            "total_require": len(composer_meta.get("packages", [])),
            "total_require_dev": len(composer_meta.get("dev_packages", [])),
        }
    }

    if args.routes:
        routes_data = inspect_routes(root, filter_pattern=args.filter_route, limit=args.limit_routes)
        report["routes_inspection"] = routes_data

    if args.json:
        print(json.dumps(report, indent=2))
    else:
        print("=== Laravel Project Inspection ===")
        print(f"Project Root:    {report['project_root']}")
        print(f"Laravel Version: {report['laravel_framework']['installed']} (constraint: {report['laravel_framework']['constraint']})")
        print(f"PHP Constraint:  {report['laravel_framework']['php_constraint']}")
        print(f"Frontend Stack:  {frontend['primary_template']} (CSS: {frontend['css_engine'] or 'Standard'})")
        print(f"Auth System:     {auth['starter_kit']} | API: {auth['api_guard']}")
        if args.routes and "routes_inspection" in report:
            rdata = report["routes_inspection"]
            print(f"\nRoutes ({rdata.get('total_count', 0)} total, showing up to {args.limit_routes}):")
            for r in rdata.get("routes", []):
                print(f"  [{r['method']}] /{r['uri']} -> {r['name'] or 'unnamed'} ({r['action']})")
        print("==================================")


if __name__ == "__main__":
    main()
