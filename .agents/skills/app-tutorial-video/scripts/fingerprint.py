#!/usr/bin/env python
# fingerprint.py — V3 Deterministic Feature & Workflow Fingerprinter
# Extracts semantic structures (routes, controllers, blade forms, validation, actions)
# Ignores cosmetic tokens (whitespace, comments, arbitrary styling classes) to prevent false positives.
import hashlib
import json
import os
import re
import sys

def sha256_short(data: str, length: int = 16) -> str:
    """Compute deterministic SHA256 hex digest."""
    return hashlib.sha256(data.encode('utf-8')).hexdigest()[:length]

def normalize_text(text: str) -> str:
    """Strip comments and redundant whitespace."""
    # Remove single line comments
    text = re.sub(r'//.*$', '', text, flags=re.MULTILINE)
    # Remove multi-line comments /* ... */ and {{-- ... --}}
    text = re.sub(r'/\*.*?\*/', '', text, flags=re.DOTALL)
    text = re.sub(r'\{\{--.*?--\}\}', '', text, flags=re.DOTALL)
    # Collapse multiple whitespaces
    text = re.sub(r'\s+', ' ', text).strip()
    return text

# ──────────────────────────────────────────────────────────────────────
# ROUTE FINGERPRINT
# ──────────────────────────────────────────────────────────────────────
def extract_route_signature(route_code_snippet: str) -> dict:
    """
    Extract deterministic semantic properties of a route group or definition:
    HTTP methods, URI path pattern, Route name, Controller action, Middleware.
    """
    clean = normalize_text(route_code_snippet)
    
    # Extract route methods
    methods = sorted(set(re.findall(r"Route::(get|post|put|patch|delete|resource)\s*\(", clean, re.IGNORECASE)))
    # Extract route paths
    paths = sorted(set(re.findall(r"['\"]([a-zA-Z0-9_\-\/\{\}\.]+?)['\"]", clean)))
    # Filter meaningful paths
    paths = [p for p in paths if '/' in p or '{' in p or p.startswith('admin') or p.startswith('parent') or p.startswith('teacher')]
    
    # Extract names
    names = sorted(set(re.findall(r"->name\(\s*['\"]([a-zA-Z0-9_\-\.]+)['\"]\s*\)", clean)))
    
    # Extract middleware names
    middlewares = sorted(set(re.findall(r"middleware\(\s*\[?['\"]([a-zA-Z0-9_\-,\.:]+)['\"]\]?\s*\)", clean)))

    return {
        'methods': methods,
        'paths': paths,
        'names': names,
        'middlewares': middlewares
    }

def compute_route_fingerprint(route_snippets: list[str]) -> str:
    combined = []
    for snippet in route_snippets:
        sig = extract_route_signature(snippet)
        combined.append(sig)
    serialized = json.dumps(combined, sort_keys=True)
    return sha256_short(serialized)

# ──────────────────────────────────────────────────────────────────────
# UI & BLADE FORM FINGERPRINT
# ──────────────────────────────────────────────────────────────────────
def extract_ui_form_signature(blade_content: str) -> dict:
    """
    Extract form fields, inputs, names, types, buttons, action URLs.
    Deliberately ignores Tailwind styling classes, spacing, comments, or decorative icons
    so cosmetic CSS changes do NOT trigger a modified fingerprint.
    """
    clean = normalize_text(blade_content)

    # 1. Forms: action pattern and method
    forms = []
    for m in re.finditer(r'<form\b([^>]*)>', clean, re.IGNORECASE):
        attrs = m.group(1)
        action_match = re.search(r'action=[\'"]([^\'"]*)[\'"]', attrs)
        method_match = re.search(r'method=[\'"]([^\'"]*)[\'"]', attrs)
        forms.append({
            'action': action_match.group(1) if action_match else '',
            'method': (method_match.group(1) if method_match else 'GET').upper()
        })

    # 2. Input fields: name, type, required attribute
    inputs = []
    for m in re.finditer(r'<input\b([^>]*)>', clean, re.IGNORECASE):
        attrs = m.group(1)
        name_m = re.search(r'name=[\'"]([^\'"]*)[\'"]', attrs)
        type_m = re.search(r'type=[\'"]([^\'"]*)[\'"]', attrs)
        req = bool(re.search(r'\brequired\b', attrs, re.IGNORECASE))
        if name_m:
            inputs.append({
                'name': name_m.group(1),
                'type': type_m.group(1).lower() if type_m else 'text',
                'required': req
            })

    # 3. Select fields
    selects = []
    for m in re.finditer(r'<select\b([^>]*)>', clean, re.IGNORECASE):
        attrs = m.group(1)
        name_m = re.search(r'name=[\'"]([^\'"]*)[\'"]', attrs)
        req = bool(re.search(r'\brequired\b', attrs, re.IGNORECASE))
        if name_m:
            selects.append({'name': name_m.group(1), 'required': req})

    # 4. Textarea fields
    textareas = []
    for m in re.finditer(r'<textarea\b([^>]*)>', clean, re.IGNORECASE):
        attrs = m.group(1)
        name_m = re.search(r'name=[\'"]([^\'"]*)[\'"]', attrs)
        req = bool(re.search(r'\brequired\b', attrs, re.IGNORECASE))
        if name_m:
            textareas.append({'name': name_m.group(1), 'required': req})

    # 5. Submit buttons and primary actions
    buttons = []
    for m in re.finditer(r'<(?:button|a)\b([^>]*)>(.*?)</(?:button|a)>', clean, re.IGNORECASE):
        attrs, label = m.group(1), m.group(2)
        btn_type = re.search(r'type=[\'"]([^\'"]*)[\'"]', attrs)
        if btn_type and btn_type.group(1).lower() == 'submit':
            # Clean inner text of label
            plain_label = re.sub(r'<[^>]+>', '', label).strip()
            buttons.append({'type': 'submit', 'label': plain_label})

    # Sort to ensure determinism
    inputs.sort(key=lambda x: (x['name'], x['type']))
    selects.sort(key=lambda x: x['name'])
    textareas.sort(key=lambda x: x['name'])
    buttons.sort(key=lambda x: x['label'])

    return {
        'forms': forms,
        'inputs': inputs,
        'selects': selects,
        'textareas': textareas,
        'buttons': buttons
    }

def compute_ui_fingerprint(view_contents: list[str]) -> str:
    combined = []
    for c in view_contents:
        combined.append(extract_ui_form_signature(c))
    serialized = json.dumps(combined, sort_keys=True)
    return sha256_short(serialized)

# ──────────────────────────────────────────────────────────────────────
# WORKFLOW & CONTROLLER FINGERPRINT
# ──────────────────────────────────────────────────────────────────────
def extract_controller_signature(controller_content: str) -> dict:
    """
    Extract methods, validation rules, input parameter constraints, redirect targets.
    """
    clean = normalize_text(controller_content)

    # 1. Public action methods
    methods = sorted(set(re.findall(r'public\s+function\s+([a-zA-Z0-9_]+)\s*\(', clean)))

    # 2. Validation rules: capture keys and constraint arrays
    validation_rules = {}
    val_blocks = re.findall(r'validate\s*\(\s*\[(.*?)\]\s*\)', clean, re.DOTALL)
    for block in val_blocks:
        pairs = re.findall(r'[\'"]([a-zA-Z0-9_\-]+)[\'"]\s*=>\s*(\[[^\]]*\]|[\'"][^\'"]*[\'"])', block)
        for field, rules in pairs:
            # Normalize rules to list of strings
            rule_tokens = re.findall(r'[\'"]([a-zA-Z0-9_\-:\.,]+)[\'"]', rules)
            validation_rules[field] = sorted(rule_tokens)

    # 3. View renders: view('...')
    view_renders = sorted(set(re.findall(r"view\(\s*['\"]([a-zA-Z0-9_\-\.]+)['\"]", clean)))

    # 4. Redirect targets: route('...')
    redirects = sorted(set(re.findall(r"route\(\s*['\"]([a-zA-Z0-9_\-\.]+)['\"]", clean)))

    return {
        'methods': methods,
        'validation_rules': validation_rules,
        'view_renders': view_renders,
        'redirects': redirects
    }

def compute_workflow_fingerprint(controller_contents: list[str]) -> str:
    combined = []
    for c in controller_contents:
        combined.append(extract_controller_signature(c))
    serialized = json.dumps(combined, sort_keys=True)
    return sha256_short(serialized)

# ──────────────────────────────────────────────────────────────────────
# COMPOUND FEATURE FINGERPRINT
# ──────────────────────────────────────────────────────────────────────
def compute_feature_fingerprints(route_snippets: list[str], view_contents: list[str], controller_contents: list[str]) -> dict:
    """
    Generates deterministic fingerprints for a feature workflow:
    - route_fingerprint
    - ui_fingerprint
    - workflow_fingerprint
    - feature_fingerprint (compound)
    """
    r_fp = compute_route_fingerprint(route_snippets)
    u_fp = compute_ui_fingerprint(view_contents)
    w_fp = compute_workflow_fingerprint(controller_contents)

    compound_payload = f"route:{r_fp}|ui:{u_fp}|workflow:{w_fp}"
    f_fp = sha256_short(compound_payload)

    return {
        'route_fingerprint': r_fp,
        'ui_fingerprint': u_fp,
        'workflow_fingerprint': w_fp,
        'feature_fingerprint': f_fp
    }

if __name__ == '__main__':
    # Quick self-test
    sample_route = "Route::get('/parent/leave-requests/create', [LeaveRequestController::class, 'create'])->name('leave-requests.create');"
    sample_view = "<form action='/submit' method='POST'><input name='student_id' type='text' required><button type='submit'>Kirim</button></form>"
    sample_controller = "public function store(Request $request) { $request->validate(['student_id' => ['required']]); return redirect()->route('home'); }"

    fps = compute_feature_fingerprints([sample_route], [sample_view], [sample_controller])
    print("[Fingerprint Self-Test] Fingerprints generated successfully:")
    for k, v in fps.items():
        print(f"  {k}: {v}")
