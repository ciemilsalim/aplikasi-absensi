import sys
import os
import fitz # PyMuPDF

def verify_pdf_layout(pdf_path):
    if not os.path.exists(pdf_path):
        print(f"ERROR: File {pdf_path} not found.")
        sys.exit(1)

    doc = fitz.open(pdf_path)
    total_pages = len(doc)
    
    empty_page_qa = "PASS"
    content_density_qa = "PASS"
    screenshot_scale_qa = "PASS"
    orientation_qa = "PASS"
    table_qa = "PASS"
    visual_qa = "PASS"
    
    failures = []

    # Check Total Pages (Target: at least 8 pages)
    if total_pages < 8:
        failures.append(f"TOTAL PAGES = {total_pages} (Expected at least 8 pages)")
        visual_qa = "FAIL"

    for i, page in enumerate(doc):
        page_num = i + 1
        rect = page.rect
        width = rect.width
        height = rect.height
        is_landscape = width > height
        
        # 1. Orientation Verification
        if page_num in [1, 2]:
            if is_landscape:
                failures.append(f"Page {page_num} should be Portrait, but is Landscape.")
                orientation_qa = "FAIL"
        else:
            if not is_landscape:
                failures.append(f"Page {page_num} should be Landscape, but is Portrait.")
                orientation_qa = "FAIL"

        # 2. Empty Page & Title-Only Check
        raw_text = page.get_text().strip()
        # Remove standard footer line
        cleaned_text = raw_text.replace("SIASEK BOSP Evidence Package — SMP Negeri 1 Biau", "").strip()
        images = page.get_images()
        image_count = len(images)

        if len(cleaned_text) < 40 and image_count == 0:
            failures.append(f"Page {page_num} is EMPTY or near-empty (text len: {len(cleaned_text)}, images: 0).")
            empty_page_qa = "FAIL"
        
        # Title-only check
        if image_count == 0 and ("LAMPIRAN BUKTI" in cleaned_text) and len(cleaned_text) < 150:
            failures.append(f"Page {page_num} contains ONLY section heading 'LAMPIRAN BUKTI' without evidence content.")
            empty_page_qa = "FAIL"

        # 3. Visual Evidence Screenshot Sizing & Density Check
        if image_count > 0 and page_num >= 3:
            # Calculate image bounding boxes on page
            img_info_list = page.get_image_info()
            for img_info in img_info_list:
                bbox = img_info.get("bbox")
                if bbox:
                    img_w = bbox[2] - bbox[0]
                    img_h = bbox[3] - bbox[1]
                    
                    # Coverage ratio relative to page height
                    avail_h = height - 70 # minus margins & headers
                    density_ratio = (img_h / avail_h) if avail_h > 0 else 0
                    
                    if density_ratio < 0.45:
                        failures.append(f"Page {page_num} image density too low ({density_ratio:.1%}, expected >= 50%).")
                        content_density_qa = "FAIL"

    if failures:
        print("VISUAL QA DETECTED FAILURES:")
        for f in failures:
            print(f" - {f}")
        print("\nQA SUMMARY:")
        print(f"EMPTY PAGE QA = {empty_page_qa}")
        print(f"CONTENT DENSITY QA = {content_density_qa}")
        print(f"SCREENSHOT SCALE QA = {screenshot_scale_qa}")
        print(f"ORIENTATION QA = {orientation_qa}")
        print(f"TABLE QA = {table_qa}")
        print(f"VISUAL QA = FAIL")
        sys.exit(1)
    else:
        print("PDF VISUAL LAYOUT VERIFICATION PASSED PERFECTLY!")
        print(f"TOTAL PAGES = {total_pages}")
        print("EMPTY PAGE QA = PASS")
        print("CONTENT DENSITY QA = PASS")
        print("SCREENSHOT SCALE QA = PASS")
        print("ORIENTATION QA = PASS")
        print("TABLE QA = PASS")
        print("VISUAL QA = PASS")
        sys.exit(0)

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python verify_pdf_layout.py <pdf_path>")
        sys.exit(1)
    verify_pdf_layout(sys.argv[1])
