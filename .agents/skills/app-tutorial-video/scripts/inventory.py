#!/usr/bin/env python
# inventory.py — V3 Feature Inventory Discovery Engine
# Discovers SIASEK features and groups routes into user workflows.
# Computes deterministic fingerprints and writes .tutorial-video/feature-inventory.json
import json
import os
import re
import sys

# Ensure scripts dir is on sys.path
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
if SCRIPT_DIR not in sys.path:
    sys.path.insert(0, SCRIPT_DIR)

from fingerprint import compute_feature_fingerprints

# ──────────────────────────────────────────────────────────────────────
# WORKFLOW REGISTRY DEFINITIONS (Ground Truth for SIASEK)
# ──────────────────────────────────────────────────────────────────────
# Each workflow represents an authentic, coherent user journey with:
# - feature_id: unique identifier slug
# - name: user-facing feature title
# - target_users: list of user roles
# - route_patterns: regex patterns matching routes in routes/web.php
# - controller_paths: relative paths to controller PHP files
# - view_dirs: relative paths to blade template directories
# - default_tutorial_id: mapped tutorial_id if one already exists
# ──────────────────────────────────────────────────────────────────────

FEATURE_WORKFLOW_DEFINITIONS = [
    {
        "feature_id": "pengajuan_izin_orang_tua",
        "name": "Pengajuan Izin / Sakit Anak oleh Orang Tua",
        "target_users": ["orang_tua"],
        "default_tutorial_id": "parent-leave-request",
        "route_patterns": [
            r"Route::resource\(['\"]leave-requests['\"],\s*ParentLeaveRequestController::class\)",
            r"prefix\(['\"]parent['\"]\)"
        ],
        "controller_paths": [
            "app/Http/Controllers/Parent/LeaveRequestController.php"
        ],
        "view_dirs": [
            "resources/views/parent/leave_requests"
        ]
    },
    {
        "feature_id": "onboarding_orang_tua_baru",
        "name": "Onboarding & Penautan Anak oleh Orang Tua Baru",
        "target_users": ["orang_tua"],
        "default_tutorial_id": None,
        "route_patterns": [
            r"prefix\(['\"]onboarding['\"]\)",
            r"ParentOnboardingController::class"
        ],
        "controller_paths": [
            "app/Http/Controllers/Parent/ParentOnboardingController.php"
        ],
        "view_dirs": [
            "resources/views/parent/onboarding"
        ]
    },
    {
        "feature_id": "jurnal_mengajar_guru",
        "name": "Pengisian & Pengelolaan Jurnal Mengajar Guru",
        "target_users": ["guru"],
        "default_tutorial_id": "teacher-journal-entry",
        "route_patterns": [
            r"Route::get\(['\"]/journals['\"],\s*\[TeachingJournalController::class",
            r"Route::post\(['\"]/journals['\"],\s*\[TeachingJournalController::class"
        ],
        "controller_paths": [
            "app/Http/Controllers/Teacher/TeachingJournalController.php"
        ],
        "view_dirs": [
            "resources/views/teacher/journals"
        ]
    },
    {
        "feature_id": "presensi_mata_pelajaran",
        "name": "Presensi & Rekapitulasi Pembelajaran Mata Pelajaran",
        "target_users": ["guru"],
        "default_tutorial_id": None,
        "route_patterns": [
            r"Route::get\(['\"]/subject-attendance/scanner",
            r"SubjectAttendanceController::class"
        ],
        "controller_paths": [
            "app/Http/Controllers/Teacher/SubjectAttendanceController.php"
        ],
        "view_dirs": [
            "resources/views/teacher/subject_attendance"
        ]
    },
    {
        "feature_id": "catatan_anekdot_siswa",
        "name": "Pencatatan Jurnal Sikap & Anekdot Siswa",
        "target_users": ["guru"],
        "default_tutorial_id": None,
        "route_patterns": [
            r"Route::get\(['\"]/anecdotes['\"],\s*\[StudentAnecdoteController::class",
            r"StudentAnecdoteController::class"
        ],
        "controller_paths": [
            "app/Http/Controllers/Teacher/StudentAnecdoteController.php"
        ],
        "view_dirs": [
            "resources/views/teacher/anecdotes"
        ]
    },
    {
        "feature_id": "monitoring_log_scan_absensi",
        "name": "Monitoring & Audit Log Scan Absensi Siswa",
        "target_users": ["admin"],
        "default_tutorial_id": "admin-scan-log-monitoring",
        "route_patterns": [
            r"Route::get\(['\"]/scan-logs['\"],\s*\[ScanLogController::class",
            r"ScanLogController::class"
        ],
        "controller_paths": [
            "app/Http/Controllers/Admin/ScanLogController.php"
        ],
        "view_dirs": [
            "resources/views/admin/scan-logs"
        ]
    },
    {
        "feature_id": "verifikasi_izin_siswa_admin",
        "name": "Verifikasi & Persetujuan Permohonan Izin Siswa oleh Admin/TU",
        "target_users": ["admin", "operator"],
        "default_tutorial_id": None,
        "route_patterns": [
            r"Route::get\(['\"]/leave-requests['\"],\s*\[AdminLeaveRequestController::class",
            r"Route::post\(['\"]/leave-requests/\{leaveRequest\}/approve"
        ],
        "controller_paths": [
            "app/Http/Controllers/Admin/LeaveRequestController.php"
        ],
        "view_dirs": [
            "resources/views/admin/leave_requests"
        ]
    },
    {
        "feature_id": "supervisi_jurnal_mengajar_kepsek",
        "name": "Supervisi & Verifikasi Jurnal Mengajar oleh Waka/Kepala Sekolah",
        "target_users": ["kepala_sekolah", "admin"],
        "default_tutorial_id": None,
        "route_patterns": [
            r"Route::get\(['\"]/teaching-journals['\"],\s*\[AdminTeachingJournalController::class",
            r"AdminTeachingJournalController::class"
        ],
        "controller_paths": [
            "app/Http/Controllers/Admin/AdminTeachingJournalController.php"
        ],
        "view_dirs": [
            "resources/views/admin/teaching_journals"
        ]
    },
    {
        "feature_id": "rekap_laporan_absensi_admin",
        "name": "Rekapitulasi, Analisis Grafik & Ekspor Laporan Presensi",
        "target_users": ["admin", "operator"],
        "default_tutorial_id": None,
        "route_patterns": [
            r"Route::get\(['\"]/reports['\"],\s*\[ReportController::class",
            r"ReportController::class"
        ],
        "controller_paths": [
            "app/Http/Controllers/Admin/ReportController.php"
        ],
        "view_dirs": [
            "resources/views/admin/reports"
        ]
    },
    {
        "feature_id": "kiosk_scanner_rfid_wajah",
        "name": "Terminal Kiosk Pemindai RFID & Deteksi Wajah Siswa",
        "target_users": ["satpam", "operator"],
        "default_tutorial_id": None,
        "route_patterns": [
            r"Route::get\(['\"]/scanner['\"],\s*\[AttendanceController::class",
            r"Route::post\(['\"]/attendance['\"],\s*\[AttendanceController::class"
        ],
        "controller_paths": [
            "app/Http/Controllers/AttendanceController.php"
        ],
        "view_dirs": [
            "resources/views/attendance"
        ]
    }
]

def load_file_content(workspace_root: str, rel_path: str) -> str:
    abs_path = os.path.join(workspace_root, rel_path)
    if os.path.isfile(abs_path):
        try:
            with open(abs_path, 'r', encoding='utf-8', errors='ignore') as f:
                return f.read()
        except Exception:
            pass
    return ""

def load_directory_blade_contents(workspace_root: str, rel_dir: str) -> list[str]:
    abs_dir = os.path.join(workspace_root, rel_dir)
    contents = []
    if os.path.isdir(abs_dir):
        for root, _, files in os.walk(abs_dir):
            for file in sorted(files):
                if file.endswith('.blade.php'):
                    p = os.path.join(root, file)
                    try:
                        with open(p, 'r', encoding='utf-8', errors='ignore') as f:
                            contents.append(f.read())
                    except Exception:
                        pass
    return contents

def extract_matching_route_snippets(web_routes: str, patterns: list[str]) -> list[str]:
    snippets = []
    lines = web_routes.splitlines()
    for pattern in patterns:
        for i, line in enumerate(lines):
            if re.search(pattern, line):
                # Grab surrounding context (up to 4 lines)
                start = max(0, i - 1)
                end = min(len(lines), i + 3)
                snippets.append("\n".join(lines[start:end]))
    return snippets or ["// default route snippet"]

def build_feature_inventory(workspace_root: str, catalog_data: dict = None) -> dict:
    """
    Builds the complete feature inventory for the SIASEK workspace.
    Cross-references with catalog_data to mark tutorial_exists & tutorial_id.
    """
    web_routes_path = os.path.join(workspace_root, "routes", "web.php")
    web_routes = ""
    if os.path.isfile(web_routes_path):
        with open(web_routes_path, 'r', encoding='utf-8', errors='ignore') as f:
            web_routes = f.read()

    # Build catalog lookup map: feature -> tutorial entry
    catalog_tutorials = {}
    if catalog_data and 'tutorials' in catalog_data:
        for tut in catalog_data['tutorials']:
            catalog_tutorials[tut.get('feature')] = tut
            if tut.get('tutorial_id'):
                catalog_tutorials[tut.get('tutorial_id')] = tut

    features = []

    # Check if current workspace matches SIASEK workflow controllers
    is_siasek = any(os.path.isfile(os.path.join(workspace_root, c)) for defn in FEATURE_WORKFLOW_DEFINITIONS for c in defn.get('controller_paths', []))

    if not is_siasek:
        # Cross-project dynamic adapter discovery
        try:
            from adapters import AdapterRegistry
            reg = AdapterRegistry()
            adp, _ = reg.detect(workspace_root)
            discovered_flows = adp.discover_workflows(workspace_root)
            for df in discovered_flows:
                fid = df['workflow_id']
                cat_entry = catalog_tutorials.get(fid)
                features.append({
                    "feature_id": fid,
                    "name": df['name'],
                    "target_users": df.get('target_users', ['user']),
                    "routes": [df.get('path', '/')],
                    "controllers": [],
                    "views": [],
                    "status": "active",
                    "tutorial_exists": bool(cat_entry),
                    "tutorial_id": cat_entry.get('tutorial_id') if cat_entry else None,
                    "fingerprints": {"feature_fingerprint": fid}
                })
        except Exception as e:
            print(f"[Inventory] Dynamic adapter fallback warning: {e}")

        inventory_data = {
            "version": "1.0",
            "generated_at": __import__('datetime').datetime.now().isoformat(),
            "total_features": len(features),
            "tutorial_coverage": sum(1 for f in features if f['tutorial_exists']),
            "features": features
        }
        return inventory_data

    for defn in FEATURE_WORKFLOW_DEFINITIONS:
        fid = defn['feature_id']
        name = defn['name']
        target_users = defn['target_users']
        default_tut_id = defn['default_tutorial_id']

        # Extract route snippets
        route_snippets = extract_matching_route_snippets(web_routes, defn['route_patterns'])

        # Load controller contents
        controller_contents = []
        for cpath in defn['controller_paths']:
            c = load_file_content(workspace_root, cpath)
            if c:
                controller_contents.append(c)

        # Load blade view contents
        view_contents = []
        for vdir in defn['view_dirs']:
            vc = load_directory_blade_contents(workspace_root, vdir)
            view_contents.extend(vc)

        # Compute deterministic fingerprints
        fps = compute_feature_fingerprints(route_snippets, view_contents, controller_contents)

        # Cross reference with catalog
        cat_entry = catalog_tutorials.get(fid) or (catalog_tutorials.get(default_tut_id) if default_tut_id else None)
        tutorial_exists = bool(cat_entry)
        tutorial_id = cat_entry.get('tutorial_id') if cat_entry else default_tut_id

        feature_item = {
            "feature_id": fid,
            "name": name,
            "target_users": target_users,
            "routes": defn['route_patterns'],
            "controllers": defn['controller_paths'],
            "views": defn['view_dirs'],
            "status": "active",
            "tutorial_exists": tutorial_exists,
            "tutorial_id": tutorial_id,
            "fingerprints": fps
        }
        features.append(feature_item)

    inventory_data = {
        "version": "1.0",
        "generated_at": __import__('datetime').datetime.now().isoformat(),
        "total_features": len(features),
        "tutorial_coverage": sum(1 for f in features if f['tutorial_exists']),
        "features": features
    }

    return inventory_data

def save_feature_inventory(workspace_root: str, inventory_data: dict, out_path: str = None) -> str:
    if not out_path:
        out_path = os.path.join(workspace_root, ".tutorial-video", "feature-inventory.json")
    os.makedirs(os.path.dirname(os.path.abspath(out_path)), exist_ok=True)
    with open(out_path, 'w', encoding='utf-8') as f:
        json.dump(inventory_data, f, indent=2, ensure_ascii=False)
    return out_path

if __name__ == '__main__':
    ws = os.path.abspath(os.path.join(SCRIPT_DIR, "..", "..", "..", ".."))
    from catalog import load_catalog
    cat = load_catalog()
    inv = build_feature_inventory(ws, cat)
    p = save_feature_inventory(ws, inv)
    print(f"[Inventory] Successfully generated feature inventory with {inv['total_features']} workflows: {p}")
    print(f"  Tutorial coverage: {inv['tutorial_coverage']}/{inv['total_features']}")
