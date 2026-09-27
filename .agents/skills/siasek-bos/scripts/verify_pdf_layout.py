import sys
import os
import fitz # PyMuPDF

def verify_pdf(pdf_path):
    if not os.path.exists(pdf_path):
        print(f"ERROR: File not found: {pdf_path}")
        return False

    doc = fitz.open(pdf_path)
    page_count = len(doc)
    errors = []

    for page_idx in range(page_count):
        page = doc[page_idx]
        rect = page.rect
        width = rect.width
        height = rect.height

        # Define boundary limits
        right_boundary = width - 20.0 # 20pt buffer inside page edge
        left_boundary = 20.0

        # Extract text blocks
        blocks = page.get_text("blocks")
        for b in blocks:
            x0, y0, x1, y1, text, bno, btype = b
            text_clean = text.strip().replace("\n", " ")
            if not text_clean:
                continue

            # Skip footer text if it spans close to edge
            if "SIASEK BOSP Evidence Package" in text_clean:
                continue

            if x1 > right_boundary:
                errors.append(f"Page {page_idx+1}: Text x1={x1:.1f} exceeds right boundary {right_boundary:.1f} -> '{text_clean[:40]}...'")
            if x0 < left_boundary:
                errors.append(f"Page {page_idx+1}: Text x0={x0:.1f} is less than left boundary {left_boundary:.1f} -> '{text_clean[:40]}...'")

    if errors:
        print(f"[FAIL] {os.path.basename(pdf_path)} ({page_count} pages):")
        for err in errors:
            print(f"  - {err}")
        return False
    else:
        print(f"[PASS] {os.path.basename(pdf_path)} ({page_count} pages) - No clipping/overflow detected.")
        return True

def render_pages_to_png(pdf_path, output_dir):
    os.makedirs(output_dir, exist_ok=True)
    doc = fitz.open(pdf_path)
    base_name = os.path.splitext(os.path.basename(pdf_path))[0]
    png_paths = []
    for page_idx in range(len(doc)):
        page = doc[page_idx]
        pix = page.get_pixmap(dpi=150)
        out_path = os.path.join(output_dir, f"{base_name}_page_{page_idx+1}.png")
        pix.save(out_path)
        png_paths.append(out_path)
    return png_paths

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python verify_pdf_layout.py <pdf_path_or_dir> [--render-dir <dir>]")
        sys.exit(1)

    path_arg = sys.argv[1]
    render_dir = None
    if "--render-dir" in sys.argv:
        r_idx = sys.argv.index("--render-dir")
        if r_idx + 1 < len(sys.argv):
            render_dir = sys.argv[r_idx + 1]

    if os.path.isdir(path_arg):
        pdf_files = []
        for root, dirs, files in os.walk(path_arg):
            if "blocked" in root or "previous-invalid-artifacts" in root:
                continue
            for f in files:
                if f.lower().endswith(".pdf"):
                    pdf_files.append(os.path.join(root, f))
        
        all_passed = True
        for pdf_file in sorted(pdf_files):
            passed = verify_pdf(pdf_file)
            if not passed:
                all_passed = False
            if render_dir:
                render_pages_to_png(pdf_file, render_dir)
        sys.exit(0 if all_passed else 1)
    else:
        passed = verify_pdf(path_arg)
        if render_dir:
            render_pages_to_png(path_arg, render_dir)
        sys.exit(0 if passed else 1)
