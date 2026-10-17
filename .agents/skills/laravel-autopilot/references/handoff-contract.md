# Cross-Skill Handoff Contract Reference (`handoff-contract.md`)

This document defines the formal integration contract between `laravel-autopilot` and other workspace skills (such as `app-tutorial-video`, `lpj-generator`, and `siasek-bos`).

---

## 1. Scope & Isolation Boundaries

- **No Automatic Invocation**: `laravel-autopilot` does NOT automatically execute or mutate other skills.
- **Contract as Source of Truth**: When a feature is implemented and verified, `laravel-autopilot` can emit a structured handoff artifact that downstream skills can ingest.
- **Strict Distinction**:
  - **VERIFIED FACTS**: Proven by active tests, route inspection, or rendered DOM checks.
  - **ASSUMPTIONS / INVENTIONS**: Strictly forbidden in handoff manifests. If a UI selector was not inspected in a real render, mark it as `NOT INSPECTED`.

---

## 2. Handoff Contract for `app-tutorial-video`

The video tutorial engine (`app-tutorial-video`) requires exact, verified routes, user roles, interaction workflows, and DOM anchors to generate tutorial scenes and recording scripts.

### Schema: `tutorial-handoff.json`

```json
{
  "$schema": "https://siasek.dev/schemas/tutorial-handoff.json",
  "handoff_version": "1.0.0",
  "generator_skill": "laravel-autopilot",
  "generated_at": "YYYY-MM-DDTHH:MM:SSZ",
  "feature": {
    "name": "Feature Name (e.g. Teacher Journal Reflection)",
    "purpose": "Clear 1-sentence description of the feature and user benefit",
    "target_audience": "Role name (e.g. Guru / Teacher, Admin, Siswa)",
    "module": "Academic / Attendance / Reporting"
  },
  "verified_routes": [
    {
      "name": "teacher.journals.reflection",
      "uri": "teacher/journals/reflection",
      "method": "GET",
      "middleware": ["web", "auth", "teacher"]
    },
    {
      "name": "teacher.journals.reflection.store",
      "uri": "teacher/journals/reflection",
      "method": "POST",
      "middleware": ["web", "auth", "teacher"]
    }
  ],
  "actor_preconditions": {
    "required_role": "teacher",
    "seed_user_email": "guru@siasek.test",
    "precondition_notes": "User must have active teaching schedule in current academic year"
  },
  "verified_workflow_steps": [
    {
      "step_order": 1,
      "page_url": "/teacher/journals/reflection",
      "action_type": "navigate",
      "target_element": "h1:has-text('Refleksi Pembelajaran')",
      "interaction_anchor_status": "VERIFIED_IN_BLADE",
      "narration_context": "Buka menu Refleksi Pembelajaran pada portal Guru."
    },
    {
      "step_order": 2,
      "page_url": "/teacher/journals/reflection",
      "action_type": "input",
      "target_element": "textarea[name='reflection_notes']",
      "interaction_anchor_status": "VERIFIED_IN_BLADE",
      "narration_context": "Ketik catatan evaluasi dan tindak lanjut pembelajaran siswa."
    },
    {
      "step_order": 3,
      "page_url": "/teacher/journals/reflection",
      "action_type": "click",
      "target_element": "button[type='submit']",
      "interaction_anchor_status": "VERIFIED_IN_BLADE",
      "narration_context": "Simpan refleksi jurnal pembelajaran."
    }
  ],
  "verification_evidence": {
    "feature_test_file": "tests/Feature/TeachingJournalTest.php",
    "test_status": "PASSED",
    "assertions_count": 8,
    "known_limitations": []
  },
  "git_checkpoint": {
    "commit_hash": "a1b2c3d4",
    "branch": "main",
    "diff_summary": "3 files changed, 85 insertions(+)"
  }
}
```

### Contract Fields Guide

1. **`verified_routes`**: Must match exact output from `laravel_inspector.py --routes`.
2. **`actor_preconditions`**: Must accurately reflect middleware constraints (e.g. `CheckRoleMiddleware:teacher`).
3. **`interaction_anchor_status`**:
   - `VERIFIED_IN_DOM`: Anchor verified in active rendered browser session.
   - `VERIFIED_IN_BLADE`: Anchor found by inspecting the Blade template file.
   - `NOT_INSPECTED`: Element not verified; downstream must verify before recording.
4. **`known_limitations`**: Document any edge cases or features requiring manual configuration.

---

## 3. Integration Alignment with `lpj-generator`

The `lpj-generator` skill creates formal accountability reports (Laporan Pertanggungjawaban).
`laravel-autopilot` provides audit-ready evidence for LPJ generation via:
1. Standardized implementation report (`templates/implementation-report.md`).
2. Verified feature inventory items with classification:
   - Status: `Terimplementasi` (Tested & verified), `Terimplementasi sebagian`, or `Direncanakan`.
3. Verifiable test metrics (passed test count, assertion count, test runner duration).

---

## 4. Boundary with `siasek-bos`

The `siasek-bos` skill is an independent billing reconciliation and BOSP support document engine.
- `laravel-autopilot` does NOT modify BOSP billing logic or frozen snapshots.
- Any school-billing or BOSP-specific rules belong exclusively to `siasek-bos`.
- `laravel-autopilot` maintains clean separation of concerns and never interferes with BOSP snapshot directories (`evidence/bosp/`).
