#!/usr/bin/env python
# intelligence.py — V3 Tutorial Intelligence, Change Detection & Impact Analysis Engine
# Detects differences between cataloged tutorials and current application codebase.
# Outputs: .tutorial-video/change-report.json
import json
import os
import sys

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
if SCRIPT_DIR not in sys.path:
    sys.path.insert(0, SCRIPT_DIR)

from catalog import load_catalog, save_catalog, DEFAULT_CATALOG_PATH
from inventory import build_feature_inventory, save_feature_inventory

def analyze_changes(catalog_data: dict, inventory_data: dict) -> dict:
    """
    Performs deterministic Change Detection, Impact Analysis, and Decision Formulation.
    
    States:
      - NEW: Feature exists in inventory, no tutorial in catalog
      - UNCHANGED: Fingerprints match perfectly
      - MODIFIED: Fingerprint changed
      - REMOVED: Tutorial exists in catalog, but feature is no longer in inventory
      - UNCERTAIN: Ambiguous state / parsing anomaly
      
    Decisions:
      - CREATE: Build new tutorial using V2.3 engine
      - UPDATE: Regenerate impacted scenes / narration / 5-gate QA
      - KEEP: No material changes; retain active tutorial
      - REVIEW: Flagged for human/agent review
      - RETIRE: Archive / deprecate tutorial
    """
    tutorials_by_feature = {t.get('feature'): t for t in catalog_data.get('tutorials', [])}
    tutorials_by_id = {t.get('tutorial_id'): t for t in catalog_data.get('tutorials', [])}
    features_by_id = {f.get('feature_id'): f for f in inventory_data.get('features', [])}

    changes = []
    
    # 1. Evaluate all inventory features
    for f in inventory_data.get('features', []):
        fid = f.get('feature_id')
        name = f.get('name')
        current_fps = f.get('fingerprints', {})
        target_users = f.get('target_users', [])
        default_tut_id = f.get('tutorial_id')

        # Find matching tutorial in catalog
        matching_tut = tutorials_by_feature.get(fid) or (tutorials_by_id.get(default_tut_id) if default_tut_id else None)

        if not matching_tut:
            # NEW FEATURE
            changes.append({
                "feature_id": fid,
                "feature_name": name,
                "target_users": target_users,
                "tutorial_id": default_tut_id or f"tut-{fid.replace('_', '-')}",
                "status": "NEW",
                "impact": "HIGH",
                "decision": "CREATE",
                "reason": "Fitur baru terdeteksi dalam inventaris aplikasi dan belum memiliki tutorial dalam katalog.",
                "fingerprint_diff": {
                    "current": current_fps,
                    "catalog": None
                }
            })
        else:
            tut_id = matching_tut.get('tutorial_id')
            cat_feature_fp = matching_tut.get('feature_fingerprint')
            cat_route_fp = matching_tut.get('route_fingerprint')
            cat_content_fp = matching_tut.get('content_fingerprint')
            tut_status = matching_tut.get('status', 'published')

            cur_feature_fp = current_fps.get('feature_fingerprint')
            cur_route_fp = current_fps.get('route_fingerprint')
            cur_ui_fp = current_fps.get('ui_fingerprint')
            cur_wf_fp = current_fps.get('workflow_fingerprint')

            # Check if tutorial is already flagged as outdated
            if tut_status == 'outdated':
                changes.append({
                    "feature_id": fid,
                    "feature_name": name,
                    "target_users": target_users,
                    "tutorial_id": tut_id,
                    "status": "MODIFIED",
                    "impact": "HIGH",
                    "decision": "UPDATE",
                    "reason": f"Tutorial berstatus '{tut_status}': {matching_tut.get('status_reason', 'Memerlukan upgrade ke standar V2.3/V3 5-Gate QA.')}",
                    "fingerprint_diff": {
                        "current": current_fps,
                        "catalog": {
                            "feature_fingerprint": cat_feature_fp,
                            "route_fingerprint": cat_route_fp,
                            "content_fingerprint": cat_content_fp
                        }
                    }
                })
            elif cat_feature_fp and cur_feature_fp == cat_feature_fp:
                # UNCHANGED
                changes.append({
                    "feature_id": fid,
                    "feature_name": name,
                    "target_users": target_users,
                    "tutorial_id": tut_id,
                    "status": "UNCHANGED",
                    "impact": "NONE",
                    "decision": "KEEP",
                    "reason": "Fingerprint rute, UI formulir, dan workflow controller identik dengan rilisan aktif yang terverifikasi.",
                    "fingerprint_diff": None
                })
            else:
                # MODIFIED: Determine sub-impact
                impact = "HIGH"
                reason_parts = []
                if cur_ui_fp != cat_content_fp:
                    reason_parts.append("Perubahan struktur UI/formulir input")
                if cur_route_fp != cat_route_fp:
                    reason_parts.append("Perubahan konfigurasi rute/URL navigasi")

                reason_str = " dan ".join(reason_parts) if reason_parts else "Perubahan semantik workflow controller/view"
                
                changes.append({
                    "feature_id": fid,
                    "feature_name": name,
                    "target_users": target_users,
                    "tutorial_id": tut_id,
                    "status": "MODIFIED",
                    "impact": impact,
                    "decision": "UPDATE",
                    "reason": f"Perubahan materiil terdeteksi: {reason_str}. Scene dan narasi tutorial harus diregenerasi.",
                    "fingerprint_diff": {
                        "current": current_fps,
                        "catalog": {
                            "feature_fingerprint": cat_feature_fp,
                            "route_fingerprint": cat_route_fp,
                            "content_fingerprint": cat_content_fp
                        }
                    }
                })

    # 2. Check for REMOVED features (in catalog but no longer in inventory)
    for tut in catalog_data.get('tutorials', []):
        cat_fid = tut.get('feature')
        if cat_fid and cat_fid not in features_by_id:
            changes.append({
                "feature_id": cat_fid,
                "feature_name": tut.get('feature'),
                "target_users": [tut.get('target_user')],
                "tutorial_id": tut.get('tutorial_id'),
                "status": "REMOVED",
                "impact": "HIGH",
                "decision": "RETIRE",
                "reason": "Fitur tidak lagi ditemukan dalam codebase aktif aplikasi.",
                "fingerprint_diff": None
            })

    # Summary statistics
    summary = {
        "total_analyzed": len(changes),
        "status_breakdown": {
            "NEW": sum(1 for c in changes if c['status'] == 'NEW'),
            "MODIFIED": sum(1 for c in changes if c['status'] == 'MODIFIED'),
            "UNCHANGED": sum(1 for c in changes if c['status'] == 'UNCHANGED'),
            "REMOVED": sum(1 for c in changes if c['status'] == 'REMOVED'),
            "UNCERTAIN": sum(1 for c in changes if c['status'] == 'UNCERTAIN')
        },
        "decision_breakdown": {
            "CREATE": sum(1 for c in changes if c['decision'] == 'CREATE'),
            "UPDATE": sum(1 for c in changes if c['decision'] == 'UPDATE'),
            "KEEP": sum(1 for c in changes if c['decision'] == 'KEEP'),
            "REVIEW": sum(1 for c in changes if c['decision'] == 'REVIEW'),
            "RETIRE": sum(1 for c in changes if c['decision'] == 'RETIRE')
        }
    }

    report = {
        "version": "1.0",
        "generated_at": __import__('datetime').datetime.now().isoformat(),
        "summary": summary,
        "changes": changes
    }

    return report

def save_change_report(workspace_root: str, report_data: dict, out_path: str = None) -> str:
    if not out_path:
        out_path = os.path.join(workspace_root, ".tutorial-video", "change-report.json")
    os.makedirs(os.path.dirname(os.path.abspath(out_path)), exist_ok=True)
    with open(out_path, 'w', encoding='utf-8') as f:
        json.dump(report_data, f, indent=2, ensure_ascii=False)
    return out_path

if __name__ == '__main__':
    ws = os.path.abspath(os.path.join(SCRIPT_DIR, "..", "..", "..", ".."))
    cat = load_catalog(os.path.join(ws, ".tutorial-video", "catalog.json"))
    inv = build_feature_inventory(ws, cat)
    rep = analyze_changes(cat, inv)
    p = save_change_report(ws, rep)
    print(f"[Intelligence] Change report saved: {p}")
    print(f"  Status   : NEW={rep['summary']['status_breakdown']['NEW']}, MODIFIED={rep['summary']['status_breakdown']['MODIFIED']}, UNCHANGED={rep['summary']['status_breakdown']['UNCHANGED']}")
    print(f"  Decisions: CREATE={rep['summary']['decision_breakdown']['CREATE']}, UPDATE={rep['summary']['decision_breakdown']['UPDATE']}, KEEP={rep['summary']['decision_breakdown']['KEEP']}")
