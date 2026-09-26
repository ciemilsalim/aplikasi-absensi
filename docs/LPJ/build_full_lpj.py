import os
import sys
import re
import docx
from docx.shared import Inches, Pt, Cm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_TAB_ALIGNMENT
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.section import WD_SECTION_START
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import nsdecls, qn
from PIL import Image

def main():
    print("=== MEMULAI GENERASI DOKUMEN LPJ RESMI ===")
    
    output_dir = os.path.abspath('docs/LPJ/final')
    os.makedirs(output_dir, exist_ok=True)
    docx_path = os.path.join(output_dir, 'LPJ_Pengembangan_Aplikasi_FINAL.docx')
    pdf_path = os.path.join(output_dir, 'LPJ_Pengembangan_Aplikasi_FINAL.pdf')
    
    doc = docx.Document()
    
    # -------------------------------------------------------------
    # 1. BASE STYLING SETUP
    # -------------------------------------------------------------
    normal_style = doc.styles['Normal']
    normal_style.font.name = 'Times New Roman'
    normal_style.font.size = Pt(12)
    normal_style.font.color.rgb = RGBColor(0, 0, 0)
    normal_style.paragraph_format.line_spacing = 1.15
    normal_style.paragraph_format.space_after = Pt(4)
    normal_style.paragraph_format.space_before = Pt(0)
    normal_style.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY

    # Heading 1
    h1_style = doc.styles['Heading 1']
    h1_style.font.name = 'Times New Roman'
    h1_style.font.size = Pt(14)
    h1_style.font.bold = True
    h1_style.font.color.rgb = RGBColor(0, 0, 0)
    h1_style.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
    h1_style.paragraph_format.space_before = Pt(18)
    h1_style.paragraph_format.space_after = Pt(12)
    h1_style.paragraph_format.keep_with_next = True

    # Heading 2
    h2_style = doc.styles['Heading 2']
    h2_style.font.name = 'Times New Roman'
    h2_style.font.size = Pt(12)
    h2_style.font.bold = True
    h2_style.font.color.rgb = RGBColor(0, 0, 0)
    h2_style.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.LEFT
    h2_style.paragraph_format.space_before = Pt(12)
    h2_style.paragraph_format.space_after = Pt(4)
    h2_style.paragraph_format.keep_with_next = True

    # Heading 3
    h3_style = doc.styles['Heading 3']
    h3_style.font.name = 'Times New Roman'
    h3_style.font.size = Pt(12)
    h3_style.font.bold = True
    h3_style.font.color.rgb = RGBColor(0, 0, 0)
    h3_style.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.LEFT
    h3_style.paragraph_format.space_before = Pt(8)
    h3_style.paragraph_format.space_after = Pt(2)
    h3_style.paragraph_format.keep_with_next = True
    
    def apply_page_setup(section):
        section.page_width = Cm(21.0)
        section.page_height = Cm(29.7)
        section.left_margin = Cm(3.5)
        section.right_margin = Cm(3.0)
        section.top_margin = Cm(3.0)
        section.bottom_margin = Cm(3.0)
    
    # XML Helpers
    def set_cell_border(cell, top="CCCCCC", bottom="CCCCCC", left="CCCCCC", right="CCCCCC", sz="4"):
        tcPr = cell._tc.get_or_add_tcPr()
        tcBorders = parse_xml(f'''
            <w:tcBorders {nsdecls("w")}>
                <w:top w:val="single" w:sz="{sz}" w:space="0" w:color="{top}"/>
                <w:left w:val="single" w:sz="{sz}" w:space="0" w:color="{left}"/>
                <w:bottom w:val="single" w:sz="{sz}" w:space="0" w:color="{bottom}"/>
                <w:right w:val="single" w:sz="{sz}" w:space="0" w:color="{right}"/>
            </w:tcBorders>
        ''')
        tcPr.append(tcBorders)

    def set_cell_shading(cell, color_hex):
        shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{color_hex}"/>')
        cell._tc.get_or_add_tcPr().append(shd)

    def set_cell_margins(cell, top=80, bottom=80, left=100, right=100):
        tcMar = parse_xml(f'''
            <w:tcMar {nsdecls("w")}>
                <w:top w:w="{top}" w:type="dxa"/>
                <w:bottom w:w="{bottom}" w:type="dxa"/>
                <w:left w:w="{left}" w:type="dxa"/>
                <w:right w:w="{right}" w:type="dxa"/>
            </w:tcMar>
        ''')
        cell._tc.get_or_add_tcPr().append(tcMar)

    def add_page_number_field(p):
        run = p.add_run()
        fldChar1 = parse_xml(f'<w:fldChar {nsdecls("w")} w:fldCharType="begin"/>')
        instrText = parse_xml(f'<w:instrText {nsdecls("w")} xml:space="preserve"> PAGE </w:instrText>')
        fldChar2 = parse_xml(f'<w:fldChar {nsdecls("w")} w:fldCharType="separate"/>')
        fldChar3 = parse_xml(f'<w:fldChar {nsdecls("w")} w:fldCharType="end"/>')
        run._r.append(fldChar1)
        run._r.append(instrText)
        run._r.append(fldChar2)
        run._r.append(fldChar3)
        run.font.name = 'Times New Roman'
        run.font.size = Pt(9)
        run.font.color.rgb = RGBColor(100, 116, 139)

    def set_table_header_and_split(table):
        header_tr = table.rows[0]._tr.get_or_add_trPr()
        header_tr.append(parse_xml(f'<w:tblHeader {nsdecls("w")}/>'))
        for row in table.rows:
            trPr = row._tr.get_or_add_trPr()
            trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))

    def add_styled_paragraph(text, is_bold=False, is_italic=False, align=WD_ALIGN_PARAGRAPH.JUSTIFY, space_before=0, space_after=4, indent=1.0):
        p = doc.add_paragraph()
        p.alignment = align
        p.paragraph_format.space_before = Pt(space_before)
        p.paragraph_format.space_after = Pt(space_after)
        p.paragraph_format.line_spacing = 1.15
        if indent > 0:
            p.paragraph_format.first_line_indent = Cm(indent)
        add_runs_with_inline_formatting(p, text)
        return p

    def add_runs_with_inline_formatting(p, text):
        pattern = re.compile(r'(\*\*[^*]+\*\*|\*[^*]+\*|`[^`]+`)')
        parts = pattern.split(text)
        for part in parts:
            if not part:
                continue
            run = p.add_run()
            run.font.name = 'Times New Roman'
            run.font.size = Pt(12)
            run.font.color.rgb = RGBColor(0, 0, 0)
            if part.startswith('**') and part.endswith('**'):
                run.text = part[2:-2]
                run.bold = True
            elif part.startswith('*') and part.endswith('*'):
                run.text = part[1:-1]
                run.italic = True
            elif part.startswith('`') and part.endswith('`'):
                run.text = part[1:-1]
                run.font.name = 'Consolas'
                run.font.size = Pt(10)
            else:
                run.text = part

    # -------------------------------------------------------------
    # 2. SECTION 1: PRELIMINARIES (i, ii, iii...)
    # -------------------------------------------------------------
    sec1 = doc.sections[0]
    apply_page_setup(sec1)
    sec1.different_first_page_header_footer = True
    
    # Roman lowercase numbering
    sectPr1 = sec1._sectPr
    pgNumType1 = parse_xml(f'<w:pgNumType {nsdecls("w")} w:fmt="lowerRoman" w:start="1"/>')
    sectPr1.append(pgNumType1)

    # Header for Section 1 (shown on pages ii onwards)
    hdr1 = sec1.header
    hdr_p1 = hdr1.paragraphs[0]
    hdr_p1.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    hdr_p1.paragraph_format.space_after = Pt(2)
    r_hdr1 = hdr_p1.add_run("APLIKASI PRESENSI SIASEK - SMP NEGERI 1 BIAU")
    r_hdr1.font.name = 'Times New Roman'
    r_hdr1.font.size = Pt(8.5)
    r_hdr1.font.italic = True
    r_hdr1.font.color.rgb = RGBColor(100, 116, 139)

    # Footer for Section 1 (Right tab stop at 14.5 cm)
    ftr1 = sec1.footer
    ftr_p1 = ftr1.paragraphs[0]
    ftr_p1.alignment = WD_ALIGN_PARAGRAPH.LEFT
    ftr_p1.paragraph_format.tab_stops.add_tab_stop(Cm(14.5), WD_TAB_ALIGNMENT.RIGHT)
    r_ftr_lbl1 = ftr_p1.add_run("LAPORAN PERTANGGUNGJAWABAN\t")
    r_ftr_lbl1.font.name = 'Times New Roman'
    r_ftr_lbl1.font.size = Pt(9)
    r_ftr_lbl1.font.color.rgb = RGBColor(100, 116, 139)
    add_page_number_field(ftr_p1)

    # --- COVER PAGE ---
    p_logo = doc.add_paragraph()
    p_logo.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_logo.paragraph_format.space_before = Pt(36)
    p_logo.paragraph_format.space_after = Pt(18)
    logo_path = 'storage/app/public/logos/ggBZk507zzGdNA41DzZ29CwbXYWjorSmrIn93j6u.png'
    if os.path.exists(logo_path):
        p_logo.add_run().add_picture(logo_path, width=Cm(3.2))

    p_t1 = doc.add_paragraph()
    p_t1.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_t1.paragraph_format.space_before = Pt(12)
    p_t1.paragraph_format.space_after = Pt(4)
    r_t1 = p_t1.add_run("LAPORAN PERTANGGUNGJAWABAN\nPENGEMBANGAN APLIKASI")
    r_t1.bold = True
    r_t1.font.name = 'Times New Roman'
    r_t1.font.size = Pt(16)

    p_t2 = doc.add_paragraph()
    p_t2.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_t2.paragraph_format.space_before = Pt(12)
    p_t2.paragraph_format.space_after = Pt(4)
    r_t2 = p_t2.add_run("APLIKASI PRESENSI SIASEK\n(SISTEM KEHADIRAN REAL-TIME)")
    r_t2.bold = True
    r_t2.font.name = 'Times New Roman'
    r_t2.font.size = Pt(14)

    p_t3 = doc.add_paragraph()
    p_t3.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_t3.paragraph_format.space_before = Pt(16)
    p_t3.paragraph_format.space_after = Pt(4)
    r_t3 = p_t3.add_run("SMP NEGERI 1 BIAU")
    r_t3.bold = True
    r_t3.font.name = 'Times New Roman'
    r_t3.font.size = Pt(14)

    p_meta = doc.add_paragraph()
    p_meta.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_meta.paragraph_format.space_before = Pt(110)
    p_meta.paragraph_format.space_after = Pt(0)
    r_meta = p_meta.add_run(
        "Periode Pengembangan: Tahun Ajaran 2025/2026\n"
        "Identitas Repositori: siasek/aplikasi-absensi\n"
        "Framework: Laravel 12.56.0 (PHP 8.2.1) | MySQL db_absen\n\n"
        "Tahun: 2026"
    )
    r_meta.font.name = 'Times New Roman'
    r_meta.font.size = Pt(11)

    doc.add_page_break()

    # --- LEMBAR PENGESAHAN ---
    doc.add_heading("LEMBAR PENGESAHAN", level=1)
    
    p_peng = doc.add_paragraph()
    p_peng.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    p_peng.paragraph_format.space_before = Pt(12)
    p_peng.paragraph_format.space_after = Pt(16)
    p_peng.paragraph_format.first_line_indent = Cm(1.0)
    r_peng = p_peng.add_run(
        "Dokumen Laporan Pertanggungjawaban (LPJ) Rekayasa Perangkat Lunak dengan judul:\n"
        "\"APLIKASI PRESENSI SIASEK (SISTEM KEHADIRAN REAL-TIME) SMP NEGERI 1 BIAU\"\n"
        "telah disusun, diperiksa, dan diverifikasi berdasarkan kondisi faktual kode sumber (source code), "
        "basis data aktif, hasil pengujian sistem, dan bukti antarmuka operasional. "
        "Dokumen ini disahkan sebagai laporan resmi pertanggungjawaban pengembangan sistem digitalisasi sekolah."
    )
    r_peng.font.name = 'Times New Roman'
    r_peng.font.size = Pt(12)

    # Signatures table
    sig_table = doc.add_table(rows=5, cols=2)
    sig_table.alignment = WD_TABLE_ALIGNMENT.CENTER
    sig_table.autofit = False

    col_widths = [Cm(7.2), Cm(7.2)]
    for row in sig_table.rows:
        for idx, width in enumerate(col_widths):
            row.cells[idx].width = width

    # Row 0: Headers
    c0 = sig_table.rows[0].cells[0].paragraphs[0]
    c0.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = c0.add_run("Disusun oleh,\nKetua Tim Pengembang / Pengelola Sistem")
    r.bold = True
    r.font.size = Pt(11)

    c1 = sig_table.rows[0].cells[1].paragraphs[0]
    c1.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = c1.add_run("Mengetahui,\nWakil Kepala Sekolah Bidang Kurikulum")
    r.bold = True
    r.font.size = Pt(11)

    # Row 1: Space for signatures
    sig_table.rows[1].cells[0].paragraphs[0].paragraph_format.space_before = Pt(50)
    sig_table.rows[1].cells[1].paragraphs[0].paragraph_format.space_before = Pt(50)

    # Row 2: Names and NIP placeholders
    c0 = sig_table.rows[2].cells[0].paragraphs[0]
    c0.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = c0.add_run("( _____________________________ )\nNIP. _________________________")
    r.font.size = Pt(11)

    c1 = sig_table.rows[2].cells[1].paragraphs[0]
    c1.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = c1.add_run("( _____________________________ )\nNIP. _________________________")
    r.font.size = Pt(11)

    # Row 3: Space before Kepala Sekolah
    sig_table.rows[3].cells[0].paragraphs[0].paragraph_format.space_before = Pt(20)

    # Row 4: Mengesahkan Kepala Sekolah (Merged across both columns)
    sig_table.rows[4].cells[0].merge(sig_table.rows[4].cells[1])
    c_kepsek = sig_table.rows[4].cells[0].paragraphs[0]
    c_kepsek.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = c_kepsek.add_run(
        "Mengesahkan,\n"
        "Kepala SMP Negeri 1 Biau\n\n\n\n\n"
        "( _____________________________ )\n"
        "NIP. _________________________\n\n"
        "Tanggal Pengesahan: ____________________"
    )
    r.bold = True
    r.font.size = Pt(11)

    doc.add_page_break()

    # --- KATA PENGANTAR ---
    doc.add_heading("KATA PENGANTAR", level=1)
    add_styled_paragraph(
        "Puji syukur kami panjatkan ke hadirat Tuhan Yang Maha Esa atas terselesaikannya proses perancangan, "
        "pengembangan, pengintegrasian, dan audit teknis terhadap **Aplikasi Presensi SIASEK (Sistem Kehadiran Real-time)** "
        "di SMP Negeri 1 Biau."
    )
    add_styled_paragraph(
        "Dokumen Laporan Pertanggungjawaban (LPJ) ini disusun sebagai bentuk transparansi, akuntabilitas, dan dokumentasi "
        "rekayasa perangkat lunak resmi atas seluruh pekerjaan pengembangan sistem yang telah direalisasikan. Laporan ini tidak memuat "
        "klaim sepihak atau generalisasi fiktif; setiap bab, matriks fitur, evaluasi arsitektur, dan hasil pengujian didasarkan secara ketat "
        "pada **bukti empiris kode sumber (*source code*)**, catatan pengujian otomatis, pemeriksaan transaksi basis data aktif, serta observasi runtime server."
    )
    add_styled_paragraph(
        "Aplikasi Presensi ini dihadirkan sebagai instrumen strategis tata kelola kedisiplinan dan administrasi digital sekolah guna "
        "mengatasi inefisiensi pencatatan kehadiran konvensional, meningkatkan ketertiban peserta didik di gerbang maupun di ruang kelas mata pelajaran, "
        "memfasilitasi jurnal harian mengajar guru, serta menghadirkan transparansi pemantauan kehadiran anak secara *real-time* kepada orang tua murid."
    )
    add_styled_paragraph(
        "Kami menyampaikan apresiasi dan terima kasih yang sebesar-besarnya kepada pimpinan sekolah, tim kurikulum, petugas piket, "
        "dewan guru, dan staf tata usaha SMP Negeri 1 Biau atas kolaborasi yang terbangun selama pengembangan ekosistem ini. "
        "Semoga dokumen ini dapat menjadi landasan audit teknologi yang valid serta panduan operasional jangka panjang yang bermanfaat bagi kemajuan tata kelola institusi."
    )
    
    p_kp_ttd = doc.add_paragraph()
    p_kp_ttd.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    p_kp_ttd.paragraph_format.space_before = Pt(24)
    r = p_kp_ttd.add_run(
        "Biau, 26 September 2026\n"
        "Tim Pengembang & Rekayasa Perangkat Lunak SIASEK\n"
        "SMP Negeri 1 Biau"
    )
    r.font.name = 'Times New Roman'
    r.font.size = Pt(11)
    r.italic = True

    doc.add_page_break()

    # --- DAFTAR ISI ---
    doc.add_heading("DAFTAR ISI", level=1)
    p_toc = doc.add_paragraph("<<TOC_PLACEHOLDER>>")
    p_toc.paragraph_format.space_after = Pt(12)
    doc.add_page_break()

    # --- DAFTAR TABEL ---
    doc.add_heading("DAFTAR TABEL", level=1)
    tables_list = [
        ("Tabel 2.1", "Identitas Teknis Aplikasi Presensi SIASEK"),
        ("Tabel 2.2", "Komposisi Arsitektur dan Pola Aliran Data"),
        ("Tabel 2.3", "Matriks Teknologi Utama dan Versi Runtime"),
        ("Tabel 2.4", "Rincian Pustaka Vendor PHP (Composer Dependencies)"),
        ("Tabel 2.5", "Rincian Pustaka Vendor JavaScript (NPM Dependencies)"),
        ("Tabel 2.6", "Integrasi Sistem Eksternal dan Antarmuka Antar-Aplikasi"),
        ("Tabel 3.1", "Rekapitulasi Rute dan Pengendali per Modul Fungsional"),
        ("Tabel 3.2", "Pemetaan Role Pengguna dan Hak Akses Sistem"),
        ("Tabel 3.3", "Pengelompokan Tabel Basis Data Aktif (MySQL db_absen)"),
        ("Tabel 3.4", "Spesifikasi Endpoint API Internal Aplikasi"),
        ("Tabel 4.1", "Ringkasan Eksekusi Pengujian Otomatis (PHPUnit/Pest)"),
        ("Tabel 4.2", "Rincian Hasil Pengujian Otomatis per Berkas Uji"),
        ("Tabel 4.3", "Hasil Pengujian Fungsional Alur Kunci Aplikasi"),
        ("Tabel 4.4", "Matriks Evaluasi Bukti Visual Antarmuka (Screenshot)"),
        ("Tabel A.1", "Rekapitulasi Statistik Status Kelengkapan Fitur"),
        ("Tabel A.2", "Matriks Kelengkapan 47 Fitur Berdasarkan Domain Sistem"),
        ("Tabel B.1", "Ringkasan Hasil Eksekusi Uji Otomatis"),
        ("Tabel B.2", "Tabel Rinci Hasil Uji Otomatis per Test Case"),
        ("Tabel B.3", "Matriks Pengujian Manual & Verifikasi Antarmuka"),
        ("Tabel C.1", "Korelasi Bukti Faktual Repositori dan Runtime (E-01 s.d. E-30)"),
        ("Tabel D.1", "Rekapitulasi Target dan Realisasi Bukti Screenshot (SS-01 s.d. SS-27)"),
    ]
    for num, title in tables_list:
        p = doc.add_paragraph()
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.line_spacing = 1.15
        p.paragraph_format.tab_stops.add_tab_stop(Cm(14.5), WD_TAB_ALIGNMENT.RIGHT)
        r_num = p.add_run(f"{num}   {title}")
        r_num.font.name = 'Times New Roman'
        r_num.font.size = Pt(11)

    doc.add_page_break()

    # --- DAFTAR GAMBAR ---
    doc.add_heading("DAFTAR GAMBAR", level=1)
    figures_list = [
        ("Gambar 3.1", "Alur Kerja Presensi Siswa pada Gerbang Sekolah"),
        ("Gambar 3.2", "Skema Presensi Mata Pelajaran oleh Guru Kelas"),
        ("Gambar 3.3", "Arsitektur Sinkronisasi Service Worker dan Cache PWA"),
        ("Gambar F.1", "Arsitektur Sistem SIASEK SMP Negeri 1 Biau"),
        ("Gambar G.1", "Diagram Database Tingkat Tinggi (ERD Overview)"),
        ("Gambar E.01", "Halaman Login Multi-Role (Bukti: SS-01)"),
        ("Gambar E.02", "Scanner Presensi Gerbang Kamera & QR (Bukti: SS-02)"),
        ("Gambar E.03", "Scanner Validasi Izin Siswa Keluar/Masuk Gerbang (Bukti: SS-03)"),
        ("Gambar E.04", "Dashboard Administrator Sistem (Bukti: SS-04)"),
        ("Gambar E.05", "Dashboard Guru & Rekap Jadwal Mengajar (Bukti: SS-05)"),
        ("Gambar E.07", "Rekapitulasi Presensi Mata Pelajaran (Bukti: SS-07)"),
        ("Gambar E.09", "Analitik Kehadiran Siswa per Mata Pelajaran (Bukti: SS-09)"),
        ("Gambar E.10", "Antarmuka Presensi Guru Berbasis Geolocation GPS (Bukti: SS-10)"),
        ("Gambar E.11", "Form Pengisian Jurnal Mengajar Harian Guru (Bukti: SS-11)"),
        ("Gambar E.12", "Rekapitulasi Refleksi Pembelajaran Semester Guru (Bukti: SS-12)"),
        ("Gambar E.13", "Pencatatan Catatan Anekdot & Sikap Peserta Didik (Bukti: SS-13)"),
        ("Gambar E.14", "Dashboard Monitoring Kegiatan Kokurikuler (Bukti: SS-14)"),
        ("Gambar E.15", "Riwayat Kehadiran Siswa pada Kegiatan Kokurikuler (Bukti: SS-15)"),
        ("Gambar E.16", "Laporan Rekapitulasi Kegiatan Kokurikuler (Bukti: SS-16)"),
        ("Gambar E.17", "Dashboard Portal Orang Tua Siswa (Bukti: SS-17)"),
        ("Gambar E.18", "Form Pengajuan Surat Izin oleh Wali Murid (Bukti: SS-18)"),
        ("Gambar E.19", "Panduan Penggunaan Portal untuk Orang Tua (Bukti: SS-19)"),
        ("Gambar E.20", "Fitur Komunikasi & Pesan Orang Tua ke Guru (Bukti: SS-20)"),
        ("Gambar E.21", "Panel Intervensi & Disposisi Izin oleh Admin (Bukti: SS-21)"),
        ("Gambar E.22", "Dashboard Eksekutif Kepala Sekolah (Bukti: SS-22)"),
        ("Gambar E.23", "Pusat Generator Laporan Presensi Lengkap (Bukti: SS-23)"),
        ("Gambar E.24", "Supervisi Ketercapaian Jurnal Mengajar Guru (Bukti: SS-24)"),
        ("Gambar E.25", "Verifikasi Hubungan Akun Orang Tua dan Siswa (Bukti: SS-25)"),
        ("Gambar E.26", "Pengaturan Tampilan Sistem & Branding Sekolah (Bukti: SS-26)"),
        ("Gambar E.27", "Halaman Fallback Mode Offline PWA (Bukti: SS-27)"),
    ]
    for num, title in figures_list:
        p = doc.add_paragraph()
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.line_spacing = 1.15
        p.paragraph_format.tab_stops.add_tab_stop(Cm(14.5), WD_TAB_ALIGNMENT.RIGHT)
        r_num = p.add_run(f"{num}   {title}")
        r_num.font.name = 'Times New Roman'
        r_num.font.size = Pt(11)

    doc.add_page_break()

    # --- RINGKASAN EKSEKUTIF ---
    doc.add_heading("RINGKASAN EKSEKUTIF", level=1)
    add_styled_paragraph(
        "Laporan Pertanggungjawaban ini menyajikan evaluasi komprehensif atas rekayasa dan implementasi **Aplikasi Presensi SIASEK** "
        "pada SMP Negeri 1 Biau. Proyek rekayasa perangkat lunak ini dibangun berbasis kerangka kerja **Laravel 12.56.0** dengan runtime **PHP 8.2.1**, "
        "memanfaatkan pendekatan arsitektur *Server-Side Rendering* (SSR) Blade teroptimasi dan *on-device computer vision* melalui **Face-API.js**."
    )
    add_styled_paragraph(
        "Berdasarkan hasil audit menyeluruh terhadap repositori, basis data MySQL `db_absen`, dan antarmuka aktif lokal (`http://127.0.0.1:8002`):"
    )
    
    rekap_poin = [
        "**Status Implementasi Fungsional**: Dari 47 fitur teridentifikasi, sebanyak **39 fitur terimplementasi penuh (83.0%)**, "
        "**6 fitur terimplementasi sebagian / dialihkan ke SIPADA (12.8%)**, **0 fitur belum terverifikasi (0.0%)**, "
        "dan **2 fitur berada di luar ruang lingkup presensi (4.2% - Gateway WhatsApp/SMS Eksternal & Modul Finansial/SPP)**.",
        
        "**Kesiapan Basis Data**: Beroperasi pada basis data bersama (*shared database*) `db_absen` yang terdiri dari **89 tabel fisik aktif** "
        "(mencakup 43 tabel presensi/akademik, 29 tabel LMS Mokopani, 7 tabel Spatie, dan 10 tabel framework), **65 berkas migrasi lokal** "
        "(seluruhnya berstatus *Ran*), serta **148 riwayat migrasi gabungan** di tabel `migrations`. Basis data telah memuat catatan transaksi nyata (4.367 log presensi mapel dan 215 catatan gerbang).",
        
        "**Hasil Pengujian Otomatis**: Eksekusi pengujian otomatis `php artisan test` mencatat **25 kasus uji, 6 passed, 19 failed, 29 assertions, 19.37 detik**. "
        "Analisis membuktikan kegagalan disebabkan perbedaan skema SQLite in-memory pengujian bawaan Breeze terhadap tabel MySQL (ketiadaan kolom soft delete di repositori lokal), bukan kerusakan logika fungsional aplikasi.",
        
        "**Verifikasi Bukti Visual**: Sebanyak **25 berkas tangkapan layar (*screenshot*) beresolusi tinggi** berhasil dicapture secara riil melalui otomatisasi peramban multi-peran (Admin, Guru, Orang Tua) tanpa rekayasa palsu. "
        "Dua target (SS-06 dan SS-08) dicatat secara transparan sebagai belum dapat diverifikasi karena memerlukan sesi jadwal aktif pada jam berjalan.",
        
        "**Evaluasi Keamanan**: Seluruh mekanisme autentikasi Bcrypt, proteksi CSRF, sanitasi unggahan berkas, dan pembatasan rute multi-role berjalan stabil, dengan satu temuan teknis pada endpoint `/fix-storage-link` yang direkomendasikan untuk diperketat."
    ]
    for pt in rekap_poin:
        p = doc.add_paragraph()
        p.paragraph_format.left_indent = Cm(1.0)
        p.paragraph_format.first_line_indent = Cm(-0.5)
        p.paragraph_format.space_after = Pt(4)
        add_runs_with_inline_formatting(p, f"• {pt}")

    add_styled_paragraph(
        "Secara keseluruhan, aplikasi dinyatakan **berfungsi secara operasional (*operationally ready*)** dan telah memenuhi mandat utama digitalisasi presensi sekolah."
    )

    # -------------------------------------------------------------
    # 3. SECTION 2: CHAPTERS AND APPENDICES (Arabic 1, 2, 3...)
    # -------------------------------------------------------------
    sec2 = doc.add_section(WD_SECTION_START.NEW_PAGE)
    apply_page_setup(sec2)
    sec2.different_first_page_header_footer = False
    
    # Arabic numbering starting at 1
    sectPr2 = sec2._sectPr
    pgNumType2 = parse_xml(f'<w:pgNumType {nsdecls("w")} w:fmt="decimal" w:start="1"/>')
    sectPr2.append(pgNumType2)

    # Unlink headers/footers to prevent duplication
    sec2.header.is_linked_to_previous = False
    sec2.footer.is_linked_to_previous = False

    # Header for Section 2
    hdr2 = sec2.header
    hdr_p2 = hdr2.paragraphs[0]
    hdr_p2.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    hdr_p2.paragraph_format.space_after = Pt(2)
    r_hdr2 = hdr_p2.add_run("APLIKASI PRESENSI SIASEK - SMP NEGERI 1 BIAU")
    r_hdr2.font.name = 'Times New Roman'
    r_hdr2.font.size = Pt(8.5)
    r_hdr2.font.italic = True
    r_hdr2.font.color.rgb = RGBColor(100, 116, 139)

    # Footer for Section 2
    ftr2 = sec2.footer
    ftr_p2 = ftr2.paragraphs[0]
    ftr_p2.alignment = WD_ALIGN_PARAGRAPH.LEFT
    ftr_p2.paragraph_format.tab_stops.add_tab_stop(Cm(14.5), WD_TAB_ALIGNMENT.RIGHT)
    r_ftr_lbl2 = ftr_p2.add_run("LAPORAN PERTANGGUNGJAWABAN\t")
    r_ftr_lbl2.font.name = 'Times New Roman'
    r_ftr_lbl2.font.size = Pt(9)
    r_ftr_lbl2.font.color.rgb = RGBColor(100, 116, 139)
    add_page_number_field(ftr_p2)

    # Read master content
    with open('docs/LPJ/LPJ_Pengembangan_Aplikasi.md', 'r', encoding='utf-8') as f:
        master_content = f.read()

    # Parse BAB I through BAB V from master_content
    bab1_pos = master_content.find('## BAB I PENDAHULUAN')
    lampiran_pos = master_content.find('## LAMPIRAN')
    chapters_content = master_content[bab1_pos:lampiran_pos]

    # Process chapters content lines
    lines = chapters_content.split('\n')
    i = 0
    table_lines = []

    def flush_table(t_lines):
        if not t_lines:
            return
        rows_data = []
        for l in t_lines:
            if not l.strip() or re.match(r'^\|[-: |]+\|$', l.strip()):
                continue
            cols = [c.strip() for c in l.strip().split('|')[1:-1]]
            rows_data.append(cols)
        
        if not rows_data:
            return
            
        num_cols = len(rows_data[0])
        table = doc.add_table(rows=len(rows_data), cols=num_cols)
        table.alignment = WD_TABLE_ALIGNMENT.CENTER
        table.autofit = False

        # Calculate col widths
        avail_width = 14.5 # cm
        col_w = avail_width / num_cols
        for r_idx, r_data in enumerate(rows_data):
            row = table.rows[r_idx]
            for c_idx, val in enumerate(r_data):
                if c_idx < num_cols:
                    cell = row.cells[c_idx]
                    cell.width = Cm(col_w)
                    set_cell_margins(cell, top=70, bottom=70, left=90, right=90)
                    p = cell.paragraphs[0]
                    p.paragraph_format.line_spacing = 1.05
                    p.paragraph_format.space_before = Pt(0)
                    p.paragraph_format.space_after = Pt(0)
                    
                    if r_idx == 0:
                        set_cell_border(cell, top="475569", bottom="475569", left="CCCCCC", right="CCCCCC", sz="8")
                        set_cell_shading(cell, "F1F5F9")
                        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                        run = p.add_run(val)
                        run.bold = True
                        run.font.name = 'Times New Roman'
                        run.font.size = Pt(9.5)
                    else:
                        set_cell_border(cell, top="E2E8F0", bottom="E2E8F0", left="E2E8F0", right="E2E8F0", sz="4")
                        if r_idx % 2 == 1:
                            set_cell_shading(cell, "FFFFFF")
                        else:
                            set_cell_shading(cell, "F8FAFC")
                        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
                        add_runs_with_inline_formatting(p, val)
                        for r_item in p.runs:
                            r_item.font.size = Pt(9)
        
        set_table_header_and_split(table)
        p_space = doc.add_paragraph()
        p_space.paragraph_format.space_before = Pt(2)
        p_space.paragraph_format.space_after = Pt(4)

    print("Building Chapters BAB I - BAB V...")
    while i < len(lines):
        line = lines[i]
        stripped = line.strip()

        # Check for table line
        if stripped.startswith('|') and stripped.endswith('|'):
            table_lines.append(stripped)
            i += 1
            continue
        elif table_lines:
            flush_table(table_lines)
            table_lines = []

        if not stripped:
            i += 1
            continue

        if stripped.startswith('## BAB '):
            # Page break before each BAB except BAB I if it's already first
            if not stripped.startswith('## BAB I '):
                doc.add_page_break()
            doc.add_heading(stripped[3:].upper(), level=1)
        elif stripped.startswith('### '):
            level_text = stripped[4:]
            if re.match(r'^\d+\.\d+\.\d+', level_text):
                doc.add_heading(level_text, level=3)
            else:
                doc.add_heading(level_text, level=2)
        elif stripped.startswith('#### '):
            doc.add_heading(stripped[5:], level=3)
        elif stripped.startswith('- ') or stripped.startswith('* '):
            p = doc.add_paragraph()
            p.paragraph_format.left_indent = Cm(1.0)
            p.paragraph_format.first_line_indent = Cm(-0.5)
            p.paragraph_format.space_after = Pt(3)
            add_runs_with_inline_formatting(p, f"• {stripped[2:]}")
        elif re.match(r'^\d+\.\s', stripped):
            p = doc.add_paragraph()
            p.paragraph_format.left_indent = Cm(1.0)
            p.paragraph_format.first_line_indent = Cm(-0.5)
            p.paragraph_format.space_after = Pt(3)
            add_runs_with_inline_formatting(p, stripped)
        elif stripped.startswith('> '):
            p = doc.add_paragraph()
            p.paragraph_format.left_indent = Cm(1.2)
            p.paragraph_format.right_indent = Cm(0.8)
            p.paragraph_format.space_before = Pt(4)
            p.paragraph_format.space_after = Pt(6)
            add_runs_with_inline_formatting(p, stripped[2:])
            for r_item in p.runs:
                r_item.italic = True
                r_item.font.size = Pt(11)
        elif stripped.startswith('---'):
            pass
        else:
            add_styled_paragraph(stripped, indent=1.0)

        i += 1

    if table_lines:
        flush_table(table_lines)
        table_lines = []

    print("Chapters BAB I - BAB V built successfully.")

    # -------------------------------------------------------------
    # 4. APPENDICES (LAMPIRAN A s.d. G)
    # -------------------------------------------------------------
    print("Building Appendices...")

    # --- LAMPIRAN A: MATRIKS FITUR ---
    doc.add_page_break()
    doc.add_heading("LAMPIRAN A - MATRIKS FITUR", level=1)
    add_styled_paragraph(
        "Berikut merupakan matriks lengkap 47 fitur Aplikasi Presensi SIASEK yang telah direkonsiliasi secara faktual "
        "terhadap kode sumber (*source code*), konfigurasi rute, pengendali (*controller*), model Eloquent, dan skema basis data aktif."
    )
    
    with open('docs/LPJ/Matriks_Fitur.md', 'r', encoding='utf-8') as f:
        mf_text = f.read()

    mf_lines = mf_text.split('\n')
    t_lines = []
    for l in mf_lines:
        st = l.strip()
        if st.startswith('|') and st.endswith('|'):
            t_lines.append(st)
        elif t_lines:
            flush_table(t_lines)
            t_lines = []
            if st.startswith('### '):
                doc.add_heading(st[4:], level=3)
            elif st.startswith('## '):
                doc.add_heading(st[3:], level=2)
            elif st and not st.startswith('---') and not st.startswith('#'):
                add_styled_paragraph(st, indent=0.5)
        else:
            if st.startswith('### '):
                doc.add_heading(st[4:], level=3)
            elif st.startswith('## '):
                doc.add_heading(st[3:], level=2)
            elif st and not st.startswith('---') and not st.startswith('#'):
                add_styled_paragraph(st, indent=0.5)
    if t_lines:
        flush_table(t_lines)

    # --- LAMPIRAN B: MATRIKS PENGUJIAN ---
    doc.add_page_break()
    doc.add_heading("LAMPIRAN B - MATRIKS PENGUJIAN", level=1)
    add_styled_paragraph(
        "Dokumentasi pengujian otomatis dan pengujian antarmuka manual secara menyeluruh. Pengujian otomatis dieksekusi "
        "menggunakan framework bawaan Pest/PHPUnit, mencatat **25 kasus uji (6 lulus, 19 gagal, 29 asersi)** dengan durasi **19.37 detik**."
    )
    
    with open('docs/LPJ/Matriks_Pengujian.md', 'r', encoding='utf-8') as f:
        mp_text = f.read()

    mp_lines = mp_text.split('\n')
    t_lines = []
    for l in mp_lines:
        st = l.strip()
        if st.startswith('|') and st.endswith('|'):
            t_lines.append(st)
        elif t_lines:
            flush_table(t_lines)
            t_lines = []
            if st.startswith('### '):
                doc.add_heading(st[4:], level=3)
            elif st.startswith('## '):
                doc.add_heading(st[3:], level=2)
            elif st and not st.startswith('---') and not st.startswith('#'):
                add_styled_paragraph(st, indent=0.5)
        else:
            if st.startswith('### '):
                doc.add_heading(st[4:], level=3)
            elif st.startswith('## '):
                doc.add_heading(st[3:], level=2)
            elif st and not st.startswith('---') and not st.startswith('#'):
                add_styled_paragraph(st, indent=0.5)
    if t_lines:
        flush_table(t_lines)

    # --- LAMPIRAN C: DAFTAR BUKTI ---
    doc.add_page_break()
    doc.add_heading("LAMPIRAN C - DAFTAR BUKTI", level=1)
    add_styled_paragraph(
        "Daftar artefak bukti faktual (E-01 s.d. E-30) yang mencakup berkas konfigurasi, migrasi, rute, kontroler, "
        "antarmuka Blade, dan repositori pengujian yang digunakan sebagai dasar verifikasi."
    )
    
    with open('docs/LPJ/Daftar_Bukti.md', 'r', encoding='utf-8') as f:
        db_text = f.read()

    db_lines = db_text.split('\n')
    t_lines = []
    for l in db_lines:
        st = l.strip()
        if st.startswith('|') and st.endswith('|'):
            t_lines.append(st)
        elif t_lines:
            flush_table(t_lines)
            t_lines = []
            if st.startswith('### '):
                doc.add_heading(st[4:], level=3)
            elif st.startswith('## '):
                doc.add_heading(st[3:], level=2)
            elif st and not st.startswith('---') and not st.startswith('#'):
                add_styled_paragraph(st, indent=0.5)
        else:
            if st.startswith('### '):
                doc.add_heading(st[4:], level=3)
            elif st.startswith('## '):
                doc.add_heading(st[3:], level=2)
            elif st and not st.startswith('---') and not st.startswith('#'):
                add_styled_paragraph(st, indent=0.5)
    if t_lines:
        flush_table(t_lines)

    # --- LAMPIRAN D: DAFTAR SCREENSHOT ---
    doc.add_page_break()
    doc.add_heading("LAMPIRAN D - DAFTAR SCREENSHOT", level=1)
    add_styled_paragraph(
        "Daftar target dan realisasi bukti visual antarmuka pengguna (SS-01 s.d. SS-27). Sebanyak 25 tangkapan layar telah "
        "terverifikasi secara riil pada server lokal, sedangkan 2 target (SS-06 dan SS-08) dinyatakan belum terverifikasi "
        "karena ketergantungan pada jadwal aktif harian."
    )
    
    with open('docs/LPJ/Daftar_Screenshot.md', 'r', encoding='utf-8') as f:
        ds_text = f.read()

    ds_lines = ds_text.split('\n')
    t_lines = []
    for l in ds_lines:
        st = l.strip()
        if st.startswith('|') and st.endswith('|'):
            t_lines.append(st)
        elif t_lines:
            flush_table(t_lines)
            t_lines = []
            if st.startswith('### '):
                doc.add_heading(st[4:], level=3)
            elif st.startswith('## '):
                doc.add_heading(st[3:], level=2)
            elif st and not st.startswith('---') and not st.startswith('#'):
                add_styled_paragraph(st, indent=0.5)
        else:
            if st.startswith('### '):
                doc.add_heading(st[4:], level=3)
            elif st.startswith('## '):
                doc.add_heading(st[3:], level=2)
            elif st and not st.startswith('---') and not st.startswith('#'):
                add_styled_paragraph(st, indent=0.5)
    if t_lines:
        flush_table(t_lines)

    # --- LAMPIRAN E: BUKTI SCREENSHOT ---
    doc.add_page_break()
    doc.add_heading("LAMPIRAN E - BUKTI SCREENSHOT", level=1)
    add_styled_paragraph(
        "Galeri bukti visual antarmuka pengguna yang diambil langsung dari lingkungan operasional aktif server lokal "
        "(`http://127.0.0.1:8002`). Setiap tangkapan layar mencerminkan implementasi riil fitur tanpa rekayasa grafis."
    )

    screenshot_meta = [
        ("SS-01", "SS-01-login.png", "Halaman Login Multi-Role", "Antarmuka otentikasi tunggal dengan deteksi otomatis role pengguna."),
        ("SS-02", "SS-02-scanner-gerbang.png", "Scanner Presensi Gerbang Kamera & QR", "Antarmuka pemindaian kamera gerbang terintegrasi Face-API.js."),
        ("SS-03", "SS-03-permit-scanner.png", "Scanner Validasi Izin Keluar/Masuk Siswa", "Modul pembacaan barcode kartu izin siswa keluar sekolah."),
        ("SS-04", "SS-04-dashboard-admin.png", "Dashboard Administrator Sistem", "Ringkasan metrik statistik kehadiran, pengguna aktif, dan status perangkat."),
        ("SS-05", "SS-05-dashboard-guru.png", "Dashboard Guru & Jadwal Mengajar", "Tampilan jadwal mengajar hari ini dan status jurnal mengajar guru."),
        ("SS-06", None, "Form Presensi Siswa Real-time per Sesi", "Status: BELUM DAPAT DIVERIFIKASI SECARA VISUAL. Memerlukan sesi jadwal aktif pada jam berjalan; tidak direkayasa."),
        ("SS-07", "SS-07-presensi-mapel-report.png", "Rekapitulasi Presensi Mata Pelajaran", "Laporan riwayat presensi siswa per kelas dan mata pelajaran."),
        ("SS-08", None, "Monitoring Presensi Mapel Hari Ini", "Status: BELUM DAPAT DIVERIFIKASI SECARA VISUAL. Memerlukan sesi jadwal aktif pada jam berjalan; tidak direkayasa."),
        ("SS-09", "SS-09-analitik-mapel.png", "Analitik Kehadiran Siswa per Mapel", "Grafik persentase kehadiran siswa per pertemuan mata pelajaran."),
        ("SS-10", "SS-10-presensi-guru-gps.png", "Presensi Guru Berbasis Geolocation GPS", "Validasi radius GPS dan radius koordinat sekolah SMP Negeri 1 Biau."),
        ("SS-11", "SS-11-jurnal-mengajar-guru.png", "Form Pengisian Jurnal Mengajar Harian", "Pencatatan materi ajar, capaian pembelajaran, dan kendala kelas."),
        ("SS-12", "SS-12-refleksi-semester.png", "Rekapitulasi Refleksi Pembelajaran Semester", "Evaluasi berkala pelaksanaan pembelajaran guru selama satu semester."),
        ("SS-13", "SS-13-catatan-anekdot.png", "Pencatatan Catatan Anekdot Siswa", "Modul observasi perilaku dan catatan perkembangan karakter siswa."),
        ("SS-14", "SS-14-kokurikuler-dashboard.png", "Dashboard Monitoring Kegiatan Kokurikuler", "Pemantauan kegiatan ekstrakurikuler dan kokurikuler sekolah."),
        ("SS-15", "SS-15-kokurikuler-riwayat.png", "Riwayat Presensi Kegiatan Kokurikuler", "Daftar kehadiran siswa pada kegiatan kokurikuler mingguan."),
        ("SS-16", "SS-16-kokurikuler-laporan.png", "Laporan Rekapitulasi Kegiatan Kokurikuler", "Ringkasan partisipasi siswa pada kegiatan ekstrakurikuler."),
        ("SS-17", "SS-17-dashboard-orangtua.png", "Dashboard Portal Orang Tua Siswa", "Informasi kehadiran harian anak yang dapat dipantau langsung oleh wali murid."),
        ("SS-18", "SS-18-pengajuan-izin-ortu.png", "Form Pengajuan Surat Izin oleh Wali Murid", "Pengajuan izin sakit atau keperluan keluarga disertai unggahan surat bukti."),
        ("SS-19", "SS-19-panduan-orangtua.png", "Panduan Penggunaan Portal untuk Orang Tua", "Modul petunjuk operasional tata cara pemantauan presensi bagi wali."),
        ("SS-20", "SS-20-chat-ortu-guru.png", "Fitur Komunikasi & Pesan Orang Tua ke Guru", "Saluran pesan langsung antara wali murid dan wali kelas/guru."),
        ("SS-21", "SS-21-intervensi-izin-admin.png", "Panel Intervensi & Disposisi Izin oleh Admin", "Manajemen verifikasi surat izin siswa oleh petugas piket dan admin."),
        ("SS-22", "SS-22-dashboard-kepsek.png", "Dashboard Eksekutif Kepala Sekolah", "Ikhtisar kehadiran menyeluruh tingkat sekolah untuk pimpinan."),
        ("SS-23", "SS-23-generator-laporan.png", "Pusat Generator Laporan Presensi Lengkap", "Filter cetak laporan presensi harian, bulanan, dan semester format PDF/Excel."),
        ("SS-24", "SS-24-supervisi-jurnal.png", "Supervisi Ketercapaian Jurnal Mengajar Guru", "Pemantauan administrasi dan kedisiplinan pengisian jurnal guru oleh pimpinan."),
        ("SS-25", "SS-25-verifikasi-orangtua.png", "Verifikasi Hubungan Akun Orang Tua & Siswa", "Pengaturan pemetaan ID orang tua terhadap NISN peserta didik."),
        ("SS-26", "SS-26-pengaturan-tampilan.png", "Pengaturan Tampilan Sistem & Branding Sekolah", "Pengaturan identitas sekolah, nama aplikasi, logo, dan preferensi tema."),
        ("SS-27", "SS-27-pwa-offline.png", "Halaman Fallback Mode Offline PWA", "Layanan Service Worker ketika perangkat kehilangan koneksi internet."),
    ]

    for id_code, fname, label, desc in screenshot_meta:
        if fname:
            img_path = os.path.join('docs/LPJ/assets/screenshots', fname)
            if os.path.exists(img_path):
                p_img = doc.add_paragraph()
                p_img.alignment = WD_ALIGN_PARAGRAPH.CENTER
                p_img.paragraph_format.space_before = Pt(12)
                p_img.paragraph_format.space_after = Pt(4)
                p_img.paragraph_format.keep_with_next = True
                
                p_img.add_run().add_picture(img_path, width=Cm(13.5))

                p_cap = doc.add_paragraph()
                p_cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
                p_cap.paragraph_format.space_before = Pt(2)
                p_cap.paragraph_format.space_after = Pt(14)
                p_cap.paragraph_format.keep_with_next = False
                
                num_part = id_code.replace("SS-", "")
                r_cap = p_cap.add_run(f"Gambar E.{num_part} - {label}\nBukti: {id_code} — {desc}")
                r_cap.font.name = 'Times New Roman'
                r_cap.font.size = Pt(10)
                r_cap.italic = True
                r_cap.font.color.rgb = RGBColor(51, 65, 85)
        else:
            p_call = doc.add_paragraph()
            p_call.alignment = WD_ALIGN_PARAGRAPH.CENTER
            p_call.paragraph_format.space_before = Pt(12)
            p_call.paragraph_format.space_after = Pt(14)
            
            call_table = doc.add_table(rows=1, cols=1)
            call_table.alignment = WD_TABLE_ALIGNMENT.CENTER
            call_cell = call_table.rows[0].cells[0]
            call_cell.width = Cm(13.5)
            set_cell_shading(call_cell, "FFFBEB")
            set_cell_border(call_cell, top="F59E0B", bottom="F59E0B", left="F59E0B", right="F59E0B", sz="8")
            set_cell_margins(call_cell, top=140, bottom=140, left=180, right=180)
            
            p_box = call_cell.paragraphs[0]
            p_box.alignment = WD_ALIGN_PARAGRAPH.CENTER
            r_box = p_box.add_run(
                f"BUKTI VISUAL: {id_code} — {label.upper()}\n"
                f"Status: BELUM DAPAT DIVERIFIKASI SECARA VISUAL PADA SAAT PENGUJIAN\n"
                f"Keterangan: {desc}\n"
                f"(Sesuai protokol integritas data, tidak disertakan screenshot rekayasa palsu)"
            )
            r_box.font.name = 'Times New Roman'
            r_box.font.size = Pt(10)
            r_box.bold = True
            r_box.font.color.rgb = RGBColor(146, 64, 14)

    # --- LAMPIRAN F: DIAGRAM ARSITEKTUR ---
    doc.add_page_break()
    doc.add_heading("LAMPIRAN F - DIAGRAM ARSITEKTUR", level=1)
    add_styled_paragraph(
        "Diagram arsitektur sistem tingkat tinggi yang memetakan interaksi komponen klien (peramban guru, admin, orang tua, dan kamera scanner gerbang), "
        "lapisan aplikasi Laravel 12 SSR Blade, middleware keamanan, antarmuka REST API, serta integrasi shared database dengan ekosistem SIPADA dan LMS Mokopani."
    )
    
    arch_path = 'docs/LPJ/assets/Arsitektur_Sistem.png'
    if os.path.exists(arch_path):
        p_arch = doc.add_paragraph()
        p_arch.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_arch.paragraph_format.space_before = Pt(12)
        p_arch.paragraph_format.space_after = Pt(4)
        p_arch.paragraph_format.keep_with_next = True
        p_arch.add_run().add_picture(arch_path, width=Cm(14.0))

        p_cap = doc.add_paragraph()
        p_cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_cap.paragraph_format.space_before = Pt(2)
        p_cap.paragraph_format.space_after = Pt(14)
        r_cap = p_cap.add_run("Gambar F.1 - Arsitektur Sistem SIASEK SMP Negeri 1 Biau")
        r_cap.font.name = 'Times New Roman'
        r_cap.font.size = Pt(10)
        r_cap.italic = True
        r_cap.font.color.rgb = RGBColor(51, 65, 85)

    # --- LAMPIRAN G: DIAGRAM DATABASE ---
    doc.add_page_break()
    doc.add_heading("LAMPIRAN G - DIAGRAM DATABASE", level=1)
    add_styled_paragraph(
        "Diagram Entity Relationship (ERD Overview) yang mengelompokkan 89 tabel fisik aktif pada basis data MySQL `db_absen` "
        "ke dalam domain modul presensi, akademik, manajemen pengguna & RBAC, kegiatan kokurikuler, serta modul ekosistem pendukung."
    )

    db_dia_path = 'docs/LPJ/assets/Database_Overview.png'
    if os.path.exists(db_dia_path):
        p_db = doc.add_paragraph()
        p_db.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_db.paragraph_format.space_before = Pt(12)
        p_db.paragraph_format.space_after = Pt(4)
        p_db.paragraph_format.keep_with_next = True
        p_db.add_run().add_picture(db_dia_path, width=Cm(14.0))

        p_cap = doc.add_paragraph()
        p_cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_cap.paragraph_format.space_before = Pt(2)
        p_cap.paragraph_format.space_after = Pt(14)
        r_cap = p_cap.add_run("Gambar G.1 - Diagram Database Tingkat Tinggi (ERD Overview)")
        r_cap.font.name = 'Times New Roman'
        r_cap.font.size = Pt(10)
        r_cap.italic = True
        r_cap.font.color.rgb = RGBColor(51, 65, 85)

    # -------------------------------------------------------------
    # 5. SAVE DOCX
    # -------------------------------------------------------------
    print(f"Menyimpan file DOCX ke: {docx_path}...")
    doc.save(docx_path)
    print("DOCX berhasil disimpan!")

    # -------------------------------------------------------------
    # 6. WORD COM CONVERSION & NATIVE TOC
    # -------------------------------------------------------------
    print("Menjalankan Microsoft Word COM untuk menghasilkan Table of Contents dan mengekspor PDF...")
    try:
        import win32com.client
        word = win32com.client.Dispatch('Word.Application')
        word.Visible = False
        word.DisplayAlerts = 0 # wdAlertsNone
        
        wdoc = word.Documents.Open(docx_path)
        
        # Locate placeholder and add native TOC
        toc_added = False
        for p in wdoc.Paragraphs:
            if '<<TOC_PLACEHOLDER>>' in p.Range.Text:
                p.Range.Text = ''
                wdoc.TablesOfContents.Add(
                    Range=p.Range,
                    RightAlignPageNumbers=True,
                    UseHeadingStyles=True,
                    UpperHeadingLevel=1,
                    LowerHeadingLevel=3,
                    IncludePageNumbers=True
                )
                toc_added = True
                print("Table of Contents berhasil disisipkan secara native via Word COM.")
                break

        print("Memperbarui seluruh field dokumen...")
        wdoc.Fields.Update()
        for toc in wdoc.TablesOfContents:
            toc.Update()
        
        # Save updated DOCX
        wdoc.Save()
        print("DOCX berhasil diperbarui.")

        # Export to PDF (17 = wdFormatPDF)
        print(f"Mengekspor ke PDF: {pdf_path}...")
        wdoc.SaveAs2(pdf_path, FileFormat=17)
        print("PDF berhasil diekspor!")

        wdoc.Close(False)
        word.Quit()
        print("Word automation selesai dengan sukses.")
    except Exception as e:
        print(f"Peringatan / Gagal ekspor Word COM: {e}")

    # -------------------------------------------------------------
    # 7. QA VERIFIKASI DOKUMEN
    # -------------------------------------------------------------
    print("\n=== VERIFIKASI HASIL AKHIR ===")
    if os.path.exists(docx_path):
        print(f"1. Lokasi DOCX: {docx_path} ({os.path.getsize(docx_path)} bytes)")
    if os.path.exists(pdf_path):
        print(f"2. Lokasi PDF:  {pdf_path} ({os.path.getsize(pdf_path)} bytes)")
        
        try:
            import pymupdf
            pdf_doc = pymupdf.open(pdf_path)
            print(f"3. Jumlah Halaman PDF: {len(pdf_doc)} halaman")
            pdf_doc.close()
        except Exception as e:
            print(f"Gagal membaca info PDF: {e}")

    print("\nDokumen LPJ Resmi Berhasil Diproduksi!")

if __name__ == '__main__':
    main()
