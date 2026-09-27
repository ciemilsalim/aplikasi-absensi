import sys
import os
import fitz

def merge_pdf_files(pdf_inputs, output_pdf):
    merged_doc = fitz.open()
    for pdf_path in pdf_inputs:
        if os.path.exists(pdf_path):
            doc = fitz.open(pdf_path)
            merged_doc.insert_pdf(doc)
            doc.close()
        else:
            print(f"WARNING: File {pdf_path} does not exist, skipping.")

    os.makedirs(os.path.dirname(output_pdf), exist_ok=True)
    merged_doc.save(output_pdf)
    merged_doc.close()
    print(f"Successfully merged {len(pdf_inputs)} files into {output_pdf}")

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python merge_pdf_packages.py <output_pdf> <input_pdf_1> <input_pdf_2> ...")
        sys.exit(1)
    
    output_pdf = sys.argv[1]
    input_pdfs = sys.argv[2:]
    merge_pdf_files(input_pdfs, output_pdf)
