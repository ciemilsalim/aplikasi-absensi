import os
import subprocess
import time
from PIL import Image

def build_diagram_images():
    assets_dir = os.path.abspath('docs/LPJ/assets')
    os.makedirs(assets_dir, exist_ok=True)

    arch_png = os.path.join(assets_dir, 'Arsitektur_Sistem_FINAL.png')
    db_png = os.path.join(assets_dir, 'Database_Overview_FINAL.png')

    # Read .mmd files
    with open('docs/LPJ/Arsitektur_Sistem.mmd', 'r', encoding='utf-8') as f:
        arch_mmd = f.read()

    with open('docs/LPJ/Database_Overview.mmd', 'r', encoding='utf-8') as f:
        db_mmd = f.read()

    # Create standalone HTML for Architecture Diagram
    arch_html = f'''<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <style>
        body {{
            font-family: 'Times New Roman', sans-serif;
            background: #ffffff;
            margin: 0;
            padding: 24px;
        }}
        .diagram-box {{
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            display: inline-block;
        }}
        h2 {{
            color: #0f172a;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 16px;
            text-align: center;
        }}
        .mermaid {{
            background: #ffffff;
        }}
    </style>
</head>
<body>
    <div class="diagram-box">
        <h2>DIAGRAM ARSITEKTUR SISTEM SIASEK SMP NEGERI 1 BIAU</h2>
        <div class="mermaid">
{arch_mmd}
        </div>
    </div>
    <script>
        mermaid.initialize({{
            startOnLoad: true,
            theme: 'default',
            securityLevel: 'loose',
            flowchart: {{ useMaxWidth: false, htmlLabels: true }}
        }});
    </script>
</body>
</html>
'''

    arch_html_file = os.path.join(assets_dir, 'render_arch.html')
    with open(arch_html_file, 'w', encoding='utf-8') as f:
        f.write(arch_html)

    # Create standalone HTML for Database Diagram
    db_html = f'''<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <style>
        body {{
            font-family: 'Times New Roman', sans-serif;
            background: #ffffff;
            margin: 0;
            padding: 24px;
        }}
        .diagram-box {{
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            display: inline-block;
        }}
        h2 {{
            color: #0f172a;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 16px;
            text-align: center;
        }}
        .mermaid {{
            background: #ffffff;
        }}
    </style>
</head>
<body>
    <div class="diagram-box">
        <h2>DIAGRAM OVERVIEW DATABASE APLIKASI PRESENSI SIASEK (89 TABEL)</h2>
        <div class="mermaid">
{db_mmd}
        </div>
    </div>
    <script>
        mermaid.initialize({{
            startOnLoad: true,
            theme: 'default',
            securityLevel: 'loose',
            er: {{ useMaxWidth: false, htmlLabels: true }}
        }});
    </script>
</body>
</html>
'''

    db_html_file = os.path.join(assets_dir, 'render_db.html')
    with open(db_html_file, 'w', encoding='utf-8') as f:
        f.write(db_html)

    edge_bin = r'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'

    # 1. Render Architecture Diagram
    arch_url = 'file:///' + arch_html_file.replace('\\', '/')
    cmd_arch = [
        edge_bin,
        '--headless',
        '--disable-gpu',
        f'--screenshot={arch_png}',
        '--window-size=1800,1600',
        arch_url
    ]
    subprocess.run(cmd_arch, check=True)
    print(f"Arch Diagram Generated: {os.path.exists(arch_png)}")

    # 2. Render Database Diagram
    db_url = 'file:///' + db_html_file.replace('\\', '/')
    cmd_db = [
        edge_bin,
        '--headless',
        '--disable-gpu',
        f'--screenshot={db_png}',
        '--window-size=2000,2200',
        db_url
    ]
    subprocess.run(cmd_db, check=True)
    print(f"Database Diagram Generated: {os.path.exists(db_png)}")

    # Validate image files using PIL
    for p_name, p_path in [('Architecture', arch_png), ('Database', db_png)]:
        if os.path.exists(p_path):
            img = Image.open(p_path)
            w, h = img.size
            size_kb = os.path.getsize(p_path) / 1024.0
            print(f"[{p_name} Validation] Dimensions: {w}x{h}px | File Size: {size_kb:.2f} KB | Valid: {w > 800 and h > 600 and size_kb > 20}")
        else:
            print(f"[{p_name} Validation] FILE MISSING!")

if __name__ == '__main__':
    build_diagram_images()
