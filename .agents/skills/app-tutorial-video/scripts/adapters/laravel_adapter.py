#!/usr/bin/env python
"""
laravel_adapter.py — Project Adapter for Laravel applications (Blade, Inertia+React, Inertia+Vue, Livewire).
Inspects .env (APP_NAME/APP_URL), routes/web.php (with prefix & middleware group support),
controllers, Inertia pages, Blade templates, and database seeders for safe test accounts.
"""

import json
import os
import re
from typing import Any, Dict, List, Optional

from .base_adapter import BaseAdapter


# Common Indonesian route slug to human title & description mappings for cross-project clarity
ROUTE_TITLE_HINTS: Dict[str, Dict[str, str]] = {
    "dashboard": {
        "title": "Dashboard Utama & Ringkasan",
        "desc": "Menampilkan ringkasan metrik operasional, grafik statistik, dan pintasan fitur utama."
    },
    "pos": {
        "title": "Kasir (POS) & Transaksi Penjualan",
        "desc": "Melayani transaksi penjualan kasir, pencarian produk/obat, keranjang belanja, dan pembayaran."
    },
    "pos/history": {
        "title": "Riwayat Transaksi & Struk Penjualan",
        "desc": "Meninjau daftar transaksi penjualan yang telah selesai beserta cetak ulang struk."
    },
    "medicines": {
        "title": "Manajemen Katalog Obat & Stok",
        "desc": "Mengelola data master obat, harga beli/jual, batch kadaluarsa, serta penyesuaian stok."
    },
    "medicines/adjustments/history": {
        "title": "Riwayat Penyesuaian Stok (Stock Opname)",
        "desc": "Memantau log keluar-masuk dan koreksi stok obat secara teraudit."
    },
    "categories": {
        "title": "Manajemen Kategori Obat & Produk",
        "desc": "Mengelola pengelompokan kategori obat dan produk untuk kemudahan katalog."
    },
    "expiries": {
        "title": "Monitoring & Karantina Obat Kadaluarsa (FEFO)",
        "desc": "Memantau peringatan dini batch obat mendekati kadaluarsa dan proses karantina stok."
    },
    "prescriptions": {
        "title": "Pelayanan Resep Dokter & Obat Racikan",
        "desc": "Mengelola daftar resep dokter, peracikan obat, pembaruan status, dan pencetakan etiket."
    },
    "prescriptions/create": {
        "title": "Input Resep Dokter & Kalkulasi Racikan Baru",
        "desc": "Mencatat resep baru dari dokter beserta komposisi obat non-racikan maupun racikan."
    },
    "reports": {
        "title": "Laporan Penjualan & Rekapitulasi Operasional",
        "desc": "Meninjau laporan penjualan harian/bulanan serta ekspor dokumen PDF dan Excel."
    },
    "reports/profit-loss": {
        "title": "Laporan Laba Rugi (Income Statement)",
        "desc": "Menganalisis pendapatan penjualan, harga pokok (HPP), beban operasional, dan laba bersih."
    },
    "finance": {
        "title": "Manajemen Keuangan & Arus Kas",
        "desc": "Mencatat pemasukan dan pengeluaran kas operasional serta memantau saldo arus kas."
    },
    "users": {
        "title": "Manajemen Pengguna & Hak Akses Peran",
        "desc": "Mengelola akun staf, penetapan peran (Admin, Operator, Kasir), dan status keaktifan."
    },
    "settings/pharmacy": {
        "title": "Pengaturan Profil & Identitas Instansi",
        "desc": "Mengonfigurasi nama instansi/apotek, alamat, kontak, dan catatan kaki struk."
    },
    "profile": {
        "title": "Pengaturan Profil Akun Pengguna",
        "desc": "Memperbarui informasi profil pribadi dan kata sandi akun pengguna."
    },
}


def _read_safe_env_Metadata(workspace_root: str) -> Dict[str, str]:
    """Read only non-sensitive APP_NAME and APP_URL from .env or .env.example."""
    result: Dict[str, str] = {}
    for fname in [".env", ".env.example"]:
        fpath = os.path.join(workspace_root, fname)
        if not os.path.isfile(fpath):
            continue
        try:
            with open(fpath, "r", encoding="utf-8", errors="ignore") as f:
                for line in f:
                    line = line.strip()
                    if not line or line.startswith("#") or "=" not in line:
                        continue
                    k, v = line.split("=", 1)
                    k = k.strip()
                    v = v.strip().strip('"').strip("'")
                    if k in ("APP_NAME", "APP_URL") and v and "${" not in v:
                        result.setdefault(k, v)
        except Exception:
            pass
    return result


def discover_laravel_test_accounts(workspace_root: str) -> List[Dict[str, str]]:
    """
    Inspect database/seeders/*.php to discover seeded test accounts (email, password, role)
    without querying or mutating any database.
    """
    seeders_dir = os.path.join(workspace_root, "database", "seeders")
    if not os.path.isdir(seeders_dir):
        return []

    accounts: List[Dict[str, str]] = []
    seen_emails = set()

    for root, _, files in os.walk(seeders_dir):
        for fname in sorted(files):
            if not fname.endswith(".php"):
                continue
            fpath = os.path.join(root, fname)
            try:
                with open(fpath, "r", encoding="utf-8", errors="ignore") as f:
                    content = f.read()
            except Exception:
                continue

            # Look for User::updateOrCreate / create / firstOrCreate blocks
            blocks = re.findall(
                r"User::(?:updateOrCreate|firstOrCreate|create)\s*\((.*?)\)\s*;",
                content,
                flags=re.DOTALL
            )
            for blk in blocks:
                email_m = re.search(r"['\"]email['\"]\s*=>\s*['\"]([^'\"]+@[^'\"]+)['\"]", blk)
                if not email_m:
                    continue
                email = email_m.group(1).strip()
                if email in seen_emails:
                    continue
                pw_m = re.search(r"Hash::make\(\s*['\"]([^'\"]+)['\"]\s*\)", blk)
                password = pw_m.group(1).strip() if pw_m else "password"
                role_m = re.search(r"['\"]role['\"]\s*=>\s*['\"]([^'\"]+)['\"]", blk)
                role = role_m.group(1).strip() if role_m else "user"
                name_m = re.search(r"['\"]name['\"]\s*=>\s*['\"]([^'\"]+)['\"]", blk)
                name = name_m.group(1).strip() if name_m else email.split("@")[0].title()

                seen_emails.add(email)
                accounts.append({
                    "email": email,
                    "password": password,
                    "role": role,
                    "name": name
                })

    # Prioritize admin role first for full-access overview tutorials, followed by other roles
    role_priority = {"admin": 0, "superadmin": 0, "operator": 1, "kasir": 2, "guru": 2, "user": 3}
    accounts.sort(key=lambda a: role_priority.get(a.get("role", "user").lower(), 5))
    return accounts


class LaravelAdapter(BaseAdapter):
    """Adapter for Laravel applications (Blade, Inertia+React, Inertia+Vue, Livewire)."""

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
                        composer_name = cdata.get("name", "laravel-app")
                        details["composer_name"] = composer_name
                    if "inertiajs/inertia-laravel" in reqs:
                        details["inertia"] = True
                    if "livewire/livewire" in reqs:
                        details["livewire"] = True
            except Exception:
                pass

        if os.path.isfile(artisan_path):
            is_laravel = True

        if not is_laravel:
            return None

        # Inspect package.json for frontend stack (React, Vue, Tailwind, Vite)
        pkg_path = os.path.join(workspace_root, "package.json")
        frontend_stack = "Blade"
        if os.path.isfile(pkg_path):
            try:
                with open(pkg_path, "r", encoding="utf-8", errors="ignore") as f:
                    pdata = json.load(f)
                    deps = {**pdata.get("dependencies", {}), **pdata.get("devDependencies", {})}
                    if "@inertiajs/react" in deps or "react" in deps:
                        frontend_stack = "Inertia + React" if details.get("inertia") else "React + Blade"
                    elif "@inertiajs/vue3" in deps or "vue" in deps:
                        frontend_stack = "Inertia + Vue" if details.get("inertia") else "Vue + Blade"
            except Exception:
                pass
        elif details.get("livewire"):
            frontend_stack = "Livewire + Blade"
        details["frontend_stack"] = frontend_stack

        # Read safe APP_NAME and APP_URL from .env
        env_meta = _read_safe_env_Metadata(workspace_root)
        folder_slug = os.path.basename(os.path.abspath(workspace_root)).lower().replace(" ", "-").replace("_", "-")
        app_name = env_meta.get("APP_NAME")
        if not app_name or app_name.lower() == "laravel":
            comp_name = details.get("composer_name", "")
            if comp_name and comp_name != "laravel/laravel":
                app_name = comp_name.split("/")[-1].replace("-", " ").title()
            else:
                app_name = os.path.basename(os.path.abspath(workspace_root)).replace("-", " ").replace("_", " ").title()

        details["name"] = app_name
        details["project_name"] = app_name
        details["project_id"] = folder_slug
        if env_meta.get("APP_URL"):
            details["app_url"] = env_meta["APP_URL"]

        # Check for auth scaffolding (routes/web.php and routes/auth.php)
        routes_web = os.path.join(workspace_root, "routes", "web.php")
        routes_auth = os.path.join(workspace_root, "routes", "auth.php")
        has_login = os.path.isfile(routes_auth)
        if os.path.isfile(routes_web):
            try:
                with open(routes_web, "r", encoding="utf-8", errors="ignore") as f:
                    content = f.read()
                    if "login" in content.lower() or "auth.php" in content.lower() or "middleware(['auth" in content or "middleware('auth" in content:
                        has_login = True
            except Exception:
                pass

        # Discover seeded test accounts safely
        test_accounts = discover_laravel_test_accounts(workspace_root)
        if test_accounts:
            details["test_accounts"] = test_accounts

        entry_points = ["public/index.php", "routes/web.php"]
        if os.path.isdir(os.path.join(workspace_root, "resources", "js", "Pages")):
            entry_points.append("resources/js/Pages")
        elif os.path.isdir(os.path.join(workspace_root, "resources", "views")):
            entry_points.append("resources/views")

        return {
            "framework": "laravel",
            "framework_title": f"Laravel ({frontend_stack})",
            "confidence": 0.98,
            "entry_points": entry_points,
            "package_manifest": "composer.json",
            "server_recommendation": "php artisan serve --port 8000",
            "default_port": 8000,
            "has_login_route": has_login,
            "details": details
        }

    def _parse_routes_web(self, workspace_root: str) -> List[Dict[str, Any]]:
        """
        Parse routes/web.php with prefix-group, middleware-group, and resource route awareness.
        """
        routes_web = os.path.join(workspace_root, "routes", "web.php")
        if not os.path.isfile(routes_web):
            return []

        try:
            with open(routes_web, "r", encoding="utf-8", errors="ignore") as f:
                content = f.read()
        except Exception:
            return []

        lines = content.splitlines()
        discovered: List[Dict[str, Any]] = []
        seen_paths = set()

        # Stack of (brace_depth, prefix_str, roles_list, requires_auth_bool)
        scope_stack: List[Dict[str, Any]] = []
        brace_depth = 0

        # Check if root '/' is just a redirect to login
        root_is_login_redirect = bool(re.search(
            r"Route::get\(\s*['\"]/['\"].*?redirect\(\)->route\(\s*['\"]login['\"]\s*\)",
            content,
            flags=re.DOTALL
        ))

        for line in lines:
            stripped = line.strip()
            if not stripped or stripped.startswith("//") or stripped.startswith("*"):
                continue

            # Detect group opening with prefix / middleware
            if "->group(" in stripped or "Route::group(" in stripped:
                prefix_m = re.search(r"prefix\(\s*['\"]([^'\"]+)['\"]\s*\)", stripped)
                if not prefix_m:
                    prefix_m = re.search(r"['\"]prefix['\"]\s*=>\s*['\"]([^'\"]+)['\"]", stripped)
                prefix_val = prefix_m.group(1).strip("/") if prefix_m else ""

                roles_m = re.search(r"role:([a-zA-Z0-9_,]+)", stripped)
                roles_val = [r.strip() for r in roles_m.group(1).split(",") if r.strip()] if roles_m else []

                auth_val = ("auth" in stripped and "middleware" in stripped) or bool(roles_val)

                open_braces = stripped.count("{")
                close_braces = stripped.count("}")
                new_depth = brace_depth + open_braces - close_braces
                if open_braces > close_braces:
                    scope_stack.append({
                        "depth": new_depth,
                        "prefix": prefix_val,
                        "roles": roles_val,
                        "auth": auth_val
                    })
                brace_depth = new_depth
                continue

            # Update brace depth for normal lines
            open_b = stripped.count("{")
            close_b = stripped.count("}")

            # Gather active prefix, roles, and auth from scope_stack
            active_prefixes = [s["prefix"] for s in scope_stack if s.get("prefix")]
            active_roles = []
            for s in scope_stack:
                for r in s.get("roles", []):
                    if r not in active_roles:
                        active_roles.append(r)
            active_auth = any(s.get("auth") for s in scope_stack)

            # Inline prefix or role on the Route statement itself
            inline_prefix_m = re.search(r"prefix\(\s*['\"]([^'\"]+)['\"]\s*\)", stripped)
            inline_prefix = inline_prefix_m.group(1).strip("/") if inline_prefix_m else ""
            inline_role_m = re.search(r"role:([a-zA-Z0-9_,]+)", stripped)
            inline_roles = [r.strip() for r in inline_role_m.group(1).split(",") if r.strip()] if inline_role_m else []

            # Match Route::get(...) or Route::resource(...)
            get_m = re.search(r"Route::get\(\s*['\"]([^'\"]+)['\"](?:,\s*\[?\s*([A-Za-z0-9_\\]+)::class(?:\s*,\s*['\"]([A-Za-z0-9_]+)['\"])?)?", stripped)
            res_m = re.search(r"Route::resource\(\s*['\"]([^'\"]+)['\"](?:,\s*([A-Za-z0-9_\\]+)::class)?", stripped)

            if get_m or res_m:
                raw_subpath = (get_m.group(1) if get_m else res_m.group(1)).strip("/")
                controller_cls = (get_m.group(2) if get_m else res_m.group(2)) or ""
                action_name = (get_m.group(3) if get_m else "index") or "index"

                path_parts = [p for p in (active_prefixes + ([inline_prefix] if inline_prefix else []) + ([raw_subpath] if raw_subpath else [])) if p]
                full_slug = "/".join(path_parts)
                full_path = "/" + full_slug if full_slug else "/"

                # Skip parameterized routes (e.g. /{medicine}, /{prescription}/etiket) and file download/export routes
                if "{" in full_path or "}" in full_path:
                    pass
                elif any(full_path.endswith(sfx) for sfx in ("/export", "/export/pdf", "/export/excel", "/import/template")):
                    pass
                elif full_path == "/" and root_is_login_redirect:
                    pass
                elif full_path not in seen_paths:
                    seen_paths.add(full_path)
                    roles = list(active_roles)
                    for r in inline_roles:
                        if r not in roles:
                            roles.append(r)
                    discovered.append({
                        "path": full_path,
                        "slug": full_slug or "home",
                        "controller": controller_cls.split("\\")[-1] if controller_cls else "",
                        "action": action_name,
                        "requires_auth": active_auth or ("middleware('auth')" in content) or ("middleware(['auth'" in content),
                        "roles": roles or ["user"]
                    })

            brace_depth += (open_b - close_b)
            while scope_stack and brace_depth < scope_stack[-1]["depth"]:
                scope_stack.pop()

        return discovered

    def _resolve_source_files(self, workspace_root: str, slug: str, controller: str) -> Dict[str, Any]:
        """Resolve controller file and Inertia/Blade view files for a route."""
        controllers: List[str] = []
        if controller:
            for root, _, files in os.walk(os.path.join(workspace_root, "app", "Http", "Controllers")):
                for f in files:
                    if f == f"{controller}.php":
                        controllers.append(os.path.relpath(os.path.join(root, f), workspace_root).replace("\\", "/"))

        views: List[str] = []
        # Check Inertia Pages (resources/js/Pages)
        inertia_dir = os.path.join(workspace_root, "resources", "js", "Pages")
        if os.path.isdir(inertia_dir):
            slug_parts = [p for p in slug.split("/") if p]
            if slug_parts:
                top_folder = slug_parts[0].replace("-", " ").title().replace(" ", "")
                cand_dir = os.path.join(inertia_dir, top_folder)
                if os.path.isdir(cand_dir):
                    views.append(os.path.relpath(cand_dir, workspace_root).replace("\\", "/"))
                else:
                    cand_file = os.path.join(inertia_dir, f"{top_folder}.jsx")
                    if os.path.isfile(cand_file):
                        views.append(os.path.relpath(cand_file, workspace_root).replace("\\", "/"))

        # Check Blade Views (resources/views)
        blade_dir = os.path.join(workspace_root, "resources", "views")
        if os.path.isdir(blade_dir):
            slug_parts = [p for p in slug.split("/") if p]
            if slug_parts:
                cand_bdir = os.path.join(blade_dir, slug_parts[0])
                if os.path.isdir(cand_bdir):
                    views.append(os.path.relpath(cand_bdir, workspace_root).replace("\\", "/"))

        primary_source = views[0] if views else (controllers[0] if controllers else "routes/web.php")
        return {
            "source_file": primary_source,
            "controllers": controllers,
            "views": views
        }

    def discover_pages(self, workspace_root: str, profile: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        parsed_routes = self._parse_routes_web(workspace_root)
        pages: List[Dict[str, Any]] = []

        for r in parsed_routes:
            slug = r["slug"]
            hint = ROUTE_TITLE_HINTS.get(slug, {})
            title = hint.get("title") or slug.replace("/", " - ").replace("-", " ").replace("_", " ").title() or "Halaman Utama"
            desc = hint.get("desc") or f"Membuka halaman {title} dan meninjau informasi antarmuka."
            src_info = self._resolve_source_files(workspace_root, slug, r.get("controller", ""))

            pages.append({
                "id": f"page_{slug.replace('/', '_').replace('-', '_') or 'home'}",
                "slug": slug,
                "title": title,
                "description": desc,
                "path_or_url": r["path"],
                "source_file": src_info["source_file"],
                "controllers": src_info["controllers"],
                "views": src_info["views"],
                "requires_auth": r["requires_auth"],
                "target_users": r["roles"],
                "key_selectors": ["main", "nav", "table", "form", "button", "body"]
            })

        return pages

    def discover_workflows(self, workspace_root: str, profile: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        # Check for profile-specified workflows
        if profile and profile.get("workflows"):
            return profile["workflows"]

        pages = self.discover_pages(workspace_root, profile)
        workflows: List[Dict[str, Any]] = []

        for p in pages:
            slug_id = p.get("slug", p["id"]).replace("/", "_").replace("-", "_")
            workflows.append({
                "workflow_id": f"flow_{slug_id}",
                "name": p["title"],
                "target_users": p.get("target_users", ["user"]),
                "description": p.get("description", f"Membuka halaman {p['title']} dan meninjau fitur utamanya."),
                "path": p["path_or_url"],
                "controllers": p.get("controllers", []),
                "views": p.get("views", []),
                "steps": [
                    {"do": "goto", "url": p["path_or_url"]},
                    {"do": "wait_for", "selector": "body"}
                ]
            })

        return workflows
