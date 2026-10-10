#!/usr/bin/env python
# catalog.py — V3 Tutorial Documentation Catalog Manager
# Manages .tutorial-video/catalog.json as the single source of truth for tutorials.
# Supports transactional updates via catalog.pending.json.
import json
import os
import shutil
import sys

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
WORKSPACE_ROOT = os.path.abspath(os.path.join(SCRIPT_DIR, "..", "..", "..", ".."))

DEFAULT_CATALOG_PATH = os.path.join(WORKSPACE_ROOT, ".tutorial-video", "catalog.json")
DEFAULT_PENDING_CATALOG_PATH = os.path.join(WORKSPACE_ROOT, ".tutorial-video", "catalog.pending.json")

def get_default_catalog_data(app_name: str = "Aplikasi Web", technology: list = None) -> dict:
    return {
        "version": "1.0",
        "application": {
            "name": app_name,
            "technology": technology or ["Web Framework", "HTML5", "CSS3"],
            "version": "1.0.0"
        },
        "tutorials": []
    }

def load_catalog(path: str = DEFAULT_CATALOG_PATH) -> dict:
    if os.path.isfile(path):
        try:
            with open(path, 'r', encoding='utf-8') as f:
                return json.load(f)
        except Exception as e:
            print(f"[Catalog] Warning reading {path}: {e}")
    # Initialize with default authentic data if not found
    data = get_default_catalog_data()
    save_catalog(data, path)
    return data

def save_catalog(data: dict, path: str = DEFAULT_CATALOG_PATH) -> None:
    os.makedirs(os.path.dirname(os.path.abspath(path)), exist_ok=True)
    with open(path, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=2, ensure_ascii=False)

# ──────────────────────────────────────────────────────────────────────
# TRANSACTIONAL STAGING SUPPORT (Phase 13)
# ──────────────────────────────────────────────────────────────────────
def stage_pending_catalog(data: dict, pending_path: str = DEFAULT_PENDING_CATALOG_PATH) -> None:
    """Stage modified catalog into pending file before final QA verification."""
    save_catalog(data, pending_path)

def has_pending_catalog(pending_path: str = DEFAULT_PENDING_CATALOG_PATH) -> bool:
    return os.path.isfile(pending_path)

def load_pending_catalog(pending_path: str = DEFAULT_PENDING_CATALOG_PATH) -> dict | None:
    if os.path.isfile(pending_path):
        try:
            with open(pending_path, 'r', encoding='utf-8') as f:
                return json.load(f)
        except Exception as e:
            print(f"[Catalog] Error loading pending catalog: {e}")
    return None

def commit_pending_catalog(pending_path: str = DEFAULT_PENDING_CATALOG_PATH,
                           catalog_path: str = DEFAULT_CATALOG_PATH) -> bool:
    """Commit pending catalog to production catalog.json after QA pass."""
    if not os.path.isfile(pending_path):
        return False
    try:
        shutil.copyfile(pending_path, catalog_path)
        os.remove(pending_path)
        return True
    except Exception as e:
        print(f"[Catalog] Error committing pending catalog: {e}")
        return False

def discard_pending_catalog(pending_path: str = DEFAULT_PENDING_CATALOG_PATH) -> None:
    """Discard pending catalog on failure/rollback."""
    if os.path.isfile(pending_path):
        try:
            os.remove(pending_path)
        except Exception as e:
            print(f"[Catalog] Warning discarding pending catalog: {e}")

# ──────────────────────────────────────────────────────────────────────
# QUERY & UPDATE HELPERS
# ──────────────────────────────────────────────────────────────────────
def get_tutorial_by_id(catalog: dict, tutorial_id: str) -> dict | None:
    for t in catalog.get('tutorials', []):
        if t.get('tutorial_id') == tutorial_id:
            return t
    return None

def get_tutorial_by_feature(catalog: dict, feature: str) -> dict | None:
    for t in catalog.get('tutorials', []):
        if t.get('feature') == feature:
            return t
    return None

def update_or_register_tutorial(catalog: dict, entry: dict) -> None:
    tut_id = entry.get('tutorial_id')
    for i, t in enumerate(catalog.get('tutorials', [])):
        if t.get('tutorial_id') == tut_id:
            catalog['tutorials'][i] = entry
            return
    catalog.setdefault('tutorials', []).append(entry)

def print_catalog_summary(catalog: dict, total_features: int | None = None) -> None:
    tutorials = catalog.get('tutorials', [])
    app_info = catalog.get('application', {})
    
    total = len(tutorials)
    published = sum(1 for t in tutorials if t.get('status') == 'published')
    outdated = sum(1 for t in tutorials if t.get('status') == 'outdated')
    review = sum(1 for t in tutorials if t.get('status') == 'review')
    retired = sum(1 for t in tutorials if t.get('status') == 'retired')
    draft = sum(1 for t in tutorials if t.get('status') == 'draft')

    print("=" * 65)
    print("      TUTORIAL DOCUMENTATION CATALOG (V3 INTELLIGENCE)")
    print("=" * 65)
    print(f"Application    : {app_info.get('name')} (v{app_info.get('version')})")
    print(f"Stack          : {', '.join(app_info.get('technology', []))}")
    if total_features is not None:
        print(f"Total Features : {total_features}")
    print("-" * 65)
    print(f"TOTAL TUTORIALS: {total}")
    print(f"  [PUBLISHED]  : {published}")
    print(f"  [OUTDATED]   : {outdated}")
    print(f"  [REVIEW]     : {review}")
    print(f"  [RETIRED]    : {retired}")
    print(f"  [DRAFT]      : {draft}")
    print("-" * 65)
    print(f"{'TUTORIAL ID':<26} {'VERSION':<8} {'STATUS':<12} {'TARGET USER':<12}")
    print("-" * 65)
    for t in tutorials:
        print(f"{t.get('tutorial_id', ''):<26} {t.get('version', ''):<8} {t.get('status', '').upper():<12} {t.get('target_user', ''):<12}")
        if t.get('status_reason'):
            print(f"  Reason: {t.get('status_reason')}")
    print("=" * 65)

if __name__ == '__main__':
    cat = load_catalog()
    print_catalog_summary(cat)
