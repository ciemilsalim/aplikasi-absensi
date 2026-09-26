import os
import subprocess
import time
from PIL import Image

def render_diagrams():
    assets_dir = os.path.abspath('docs/LPJ/assets')
    os.makedirs(assets_dir, exist_ok=True)

    arch_mmd_path = 'docs/LPJ/Arsitektur_Sistem.mmd'
    db_mmd_path = 'docs/LPJ/Database_Overview.mmd'

    with open(arch_mmd_path, 'r', encoding='utf-8') as f:
        arch_code = f.read()

    with open(db_mmd_path, 'r', encoding='utf-8') as f:
        db_code = f.read()

    # Create HTML file to render both diagrams
    html_content = f'''<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <style>
        body {{
            font-family: 'Times New Roman', sans-serif;
            background: #ffffff;
            margin: 0;
            padding: 20px;
        }}
        .container {{
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 40px;
            display: inline-block;
        }}
        h2 {{ color: #1e293b; margin-top: 0; }}
        .mermaid {{ background: #ffffff; }}
    </style>
</head>
<body>
    <div id="arch-box" class="container">
        <h2>Diagram Arsitektur Sistem SIASEK</h2>
        <div class="mermaid">
{arch_code}
        </div>
    </div>

    <div id="db-box" class="container">
        <h2>Diagram Database Overview SIASEK</h2>
        <div class="mermaid">
{db_code}
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

    html_file = os.path.join(assets_dir, 'diagram_render.html')
    with open(html_file, 'w', encoding='utf-8') as f:
        f.write(html_content)

    edge_bin = r'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
    html_url = 'file:///' + html_file.replace('\\', '/')
    full_png = os.path.join(assets_dir, 'full_diagrams.png')

    cmd = [
        edge_bin,
        '--headless',
        '--disable-gpu',
        f'--screenshot={full_png}',
        '--window-size=1800,2400',
        html_url
    ]

    subprocess.run(cmd, check=True)
    print(f"Full diagram screenshot generated: {os.path.exists(full_png)}")

    # Crop full screenshot into two separate diagram images
    arch_out = os.path.join(assets_dir, 'Arsitektur_Sistem.png')
    db_out = os.path.join(assets_dir, 'Database_Overview.png')

    if os.path.exists(full_png):
        img = Image.open(full_png)
        w, h = img.size
        
        # Upper half for Architecture, Lower half for Database
        arch_crop = img.crop((0, 0, w, int(h * 0.5)))
        db_crop = img.crop((0, int(h * 0.5), w, h))

        arch_crop.save(arch_out)
        db_crop.save(db_out)

        print(f"Arch diagram saved: {arch_out} ({os.path.exists(arch_out)})")
        print(f"DB diagram saved: {db_out} ({os.path.exists(db_out)})")

if __name__ == '__main__':
    render_diagrams()
