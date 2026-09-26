import os
import subprocess
from PIL import Image

def generate_clean_diagrams():
    assets_dir = os.path.abspath('docs/LPJ/assets')
    os.makedirs(assets_dir, exist_ok=True)

    arch_png = os.path.join(assets_dir, 'Arsitektur_Sistem_FINAL.png')
    db_png = os.path.join(assets_dir, 'Database_Overview_FINAL.png')

    # 1. Clean HTML Architecture Diagram
    arch_html_content = '''<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Diagram Arsitektur Sistem SIASEK</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            padding: 30px;
            width: 1600px;
        }
        .container {
            background: #ffffff;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
        .title {
            text-align: center;
            font-size: 26px;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .subtitle {
            text-align: center;
            font-size: 15px;
            color: #64748b;
            margin-bottom: 25px;
        }
        .layer {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 18px 22px;
            background: #ffffff;
            position: relative;
        }
        .layer-header {
            display: flex;
            align-items: center;
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f1f5f9;
        }
        .layer-badge {
            background: #1e40af;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
            margin-right: 12px;
        }
        .layer-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }
        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }
        .card {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 14px;
        }
        .card-user { background: #eff6ff; border-color: #bfdbfe; }
        .card-web { background: #f0fdf4; border-color: #bbf7d0; }
        .card-feature { background: #faf5ff; border-color: #e9d5ff; }
        .card-integ { background: #fff7ed; border-color: #fed7aa; }
        .card-data { background: #fef2f2; border-color: #fecaca; }
        .card-bg { background: #fdf4ff; border-color: #f5d0fe; }

        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .card-desc {
            font-size: 13px;
            color: #475569;
            line-height: 1.4;
        }
        .flow-arrow {
            text-align: center;
            font-size: 20px;
            color: #94a3b8;
            margin: -10px 0 10px 0;
            font-weight: bold;
        }
        .footer-note {
            text-align: right;
            font-size: 12px;
            color: #94a3b8;
            margin-top: 15px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">DIAGRAM ARSITEKTUR SISTEM SIASEK</div>
        <div class="subtitle">Sistem Kehadiran Real-time SMP Negeri 1 Biau (Laravel 12, Blade, Tailwind, Alpine.js, MySQL)</div>

        <!-- LAYER 1: USER / CLIENT -->
        <div class="layer" style="border-left: 6px solid #3b82f6;">
            <div class="layer-header">
                <span class="layer-badge" style="background: #2563eb;">Lapis 1</span>
                <span class="layer-title">USER / CLIENT LAYER</span>
            </div>
            <div class="grid-cards" style="grid-template-columns: repeat(4, 1fr);">
                <div class="card card-user">
                    <div class="card-title">👨‍💼 Admin & TU</div>
                    <div class="card-desc">Manajemen Master Data, User Roles (Spatie RBAC), Verifikasi Izin, Monitoring System & Rekap LPJ.</div>
                </div>
                <div class="card card-user">
                    <div class="card-title">👨‍🏫 Guru</div>
                    <div class="card-desc">Presensi Mapel, Jurnal KBM, Catatan Anekdot, Presensi Kokurikuler, Presensi Mandiri GPS Selfie.</div>
                </div>
                <div class="card card-user">
                    <div class="card-title">👨‍👩‍👧 Orang Tua</div>
                    <div class="card-desc">Portal Monitoring Kehadiran Real-time, Pengajuan Surat Izin/Sakit, Notifikasi Absensi Anak.</div>
                </div>
                <div class="card card-user">
                    <div class="card-title">👮 Piket / Satpam</div>
                    <div class="card-desc">Kios Pemindai Gerbang (QR Scanner & Face Recognition), Verifikasi Surat Jalan Permit Siswa.</div>
                </div>
            </div>
        </div>

        <div class="flow-arrow">⬇ ⬆ (HTTP/HTTPS REST & Web Requests)</div>

        <!-- LAYER 2: WEB APPLICATION CORE -->
        <div class="layer" style="border-left: 6px solid #16a34a;">
            <div class="layer-header">
                <span class="layer-badge" style="background: #16a34a;">Lapis 2</span>
                <span class="layer-title">WEB APPLICATION CORE</span>
            </div>
            <div class="grid-cards" style="grid-template-columns: repeat(4, 1fr);">
                <div class="card card-web">
                    <div class="card-title">🚀 Framework & Logic</div>
                    <div class="card-desc">Laravel 12 (PHP 8.2+), 68 Controllers, 32 Eloquent Models, 268 Rute Terdaftar.</div>
                </div>
                <div class="card card-web">
                    <div class="card-title">🎨 Presentation & UI</div>
                    <div class="card-desc">Blade Templating, Tailwind CSS, Alpine.js (Reaktivitas Client), PWA Web App.</div>
                </div>
                <div class="card card-web">
                    <div class="card-title">🛡️ Middleware Security</div>
                    <div class="card-desc">10 Middleware: CheckRole, ScannerAccess, CSP, AcademicPeriod, ParentOnboarding, SipadaRedirect.</div>
                </div>
                <div class="card card-web">
                    <div class="card-title">⚙️ ORM & Services</div>
                    <div class="card-desc">Eloquent ORM Data Abstraction, Sanctum API Tokens, Service Pattern (DomPDF & Excel Export).</div>
                </div>
            </div>
        </div>

        <div class="flow-arrow">⬇ ⬆ (Internal Feature Dispatch & AI Processing)</div>

        <!-- LAYER 3: FEATURE SERVICES -->
        <div class="layer" style="border-left: 6px solid #9333ea;">
            <div class="layer-header">
                <span class="layer-badge" style="background: #9333ea;">Lapis 3</span>
                <span class="layer-title">FEATURE SERVICES LAYER</span>
            </div>
            <div class="grid-cards" style="grid-template-columns: repeat(4, 1fr);">
                <div class="card card-feature">
                    <div class="card-title">📌 Presensi Gerbang</div>
                    <div class="card-desc">Clock-in/out Real-time, Barcode QR Scanner & Face Recognition (Face-API.js Client AI).</div>
                </div>
                <div class="card card-feature">
                    <div class="card-title">📚 Presensi Mapel & Jurnal</div>
                    <div class="card-desc">Kehadiran Jam Pelajaran, Jurnal Pembelajaran KBM, Catatan Anekdot Karakter Siswa.</div>
                </div>
                <div class="card card-feature">
                    <div class="card-title">🎫 Permit & Perizinan</div>
                    <div class="card-desc">Surat Izin Keluar/Masuk Gerbang, Pengajuan Izin/Sakit Orang Tua dengan Lampiran Foto Surat.</div>
                </div>
                <div class="card card-feature">
                    <div class="card-title">📊 Reporting & Chat</div>
                    <div class="card-desc">Laporan Eksekutif PDF/Excel, Two-way Chat Messaging Orang Tua-Guru & Admin.</div>
                </div>
            </div>
        </div>

        <div class="flow-arrow">↔ (SSO Exchange & Shared Database Mapping)</div>

        <!-- LAYER 4: INTEGRATION LAYER -->
        <div class="layer" style="border-left: 6px solid #ea580c;">
            <div class="layer-header">
                <span class="layer-badge" style="background: #ea580c;">Lapis 4</span>
                <span class="layer-title">INTEGRATION LAYER</span>
            </div>
            <div class="grid-cards" style="grid-template-columns: repeat(2, 1fr);">
                <div class="card card-integ">
                    <div class="card-title">🌐 LMS Mokopani SSO</div>
                    <div class="card-desc">Single Sign-On Integration via One-Time Token (sso_tokens) untuk Seamless Login antara SIASEK dan Platform Pembelajaran LMS Mokopani.</div>
                </div>
                <div class="card card-integ">
                    <div class="card-title">🔗 SIPADA Integration</div>
                    <div class="card-desc">Interseptor Redirect & Shared Master Data Database (Sistem Pangkalan Data Sekolah) sebagai Single Source of Truth Siswa & Guru.</div>
                </div>
            </div>
        </div>

        <!-- LAYER 5: DATA LAYER -->
        <div class="layer" style="border-left: 6px solid #dc2626;">
            <div class="layer-header">
                <span class="layer-badge" style="background: #dc2626;">Lapis 5</span>
                <span class="layer-title">DATA LAYER</span>
            </div>
            <div class="grid-cards" style="grid-template-columns: repeat(2, 1fr);">
                <div class="card card-data">
                    <div class="card-title">🗄️ Database MySQL ('db_absen')</div>
                    <div class="card-desc">89 Tabel Database (Tabel Presensi, Akademik, Users, Spatie RBAC, SSO Tokens, Chat Messages, Jurnal, Audit Logs).</div>
                </div>
                <div class="card card-data">
                    <div class="card-title">📁 File Storage System</div>
                    <div class="card-desc">storage/app/public/ (Foto Profil, Surat Dokter, Presensi GPS Selfie, Foto Pengguna, Path Traversal Sanitized).</div>
                </div>
            </div>
        </div>

        <!-- LAYER 6: BACKGROUND SCHEDULER -->
        <div class="layer" style="border-left: 6px solid #c026d3; margin-bottom: 0;">
            <div class="layer-header">
                <span class="layer-badge" style="background: #c026d3;">Lapis 6</span>
                <span class="layer-title">BACKGROUND & AUTOMATION LAYER</span>
            </div>
            <div class="grid-cards" style="grid-template-columns: repeat(1, 1fr);">
                <div class="card card-bg">
                    <div class="card-title">⏰ Laravel Task Scheduler</div>
                    <div class="card-desc">Otomasi Perintah <code>attendance:check-absent</code> (Diperiksa setiap hari kerja pukul 10:00 WITA): Otomatis menandai Alpa bagi siswa tanpa keterangan dan mengirim Notifikasi Real-time ke Orang Tua.</div>
                </div>
            </div>
        </div>

        <div class="footer-note">Dokumentasi Arsitektur Resmi - Aplikasi Presensi SIASEK SMP Negeri 1 Biau</div>
    </div>
</body>
</html>'''

    arch_html_path = os.path.join(assets_dir, 'clean_arch_diagram.html')
    with open(arch_html_path, 'w', encoding='utf-8') as f:
        f.write(arch_html_content)

    # 2. Clean HTML Database Overview Diagram
    db_html_content = '''<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Diagram Overview Database SIASEK</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            padding: 30px;
            width: 1700px;
        }
        .container {
            background: #ffffff;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
        .title {
            text-align: center;
            font-size: 26px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .subtitle {
            text-align: center;
            font-size: 15px;
            color: #64748b;
            margin-bottom: 25px;
        }
        .schema-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .module-box {
            border: 2px solid #cbd5e1;
            border-radius: 10px;
            padding: 16px;
            background: #ffffff;
        }
        .mod-title {
            font-size: 16px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 6px;
            color: #ffffff;
            margin-bottom: 12px;
        }
        .mod-presensi { background: #2563eb; }
        .mod-akademik { background: #059669; }
        .mod-users { background: #7c3aed; }
        .mod-ijin { background: #d97706; }
        .mod-chat { background: #0891b2; }
        .mod-spatie { background: #dc2626; }

        .table-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 10px;
        }
        .tbl-name {
            font-family: 'Consolas', monospace;
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .tbl-field {
            font-size: 12px;
            color: #475569;
            font-family: 'Consolas', monospace;
            line-height: 1.5;
        }
        .pk { color: #b91c1c; font-weight: bold; }
        .fk { color: #1d4ed8; font-weight: bold; }
        .footer-note {
            text-align: right;
            font-size: 12px;
            color: #94a3b8;
            margin-top: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">DIAGRAM OVERVIEW SCHEMAS DATABASE (89 TABEL db_absen)</div>
        <div class="subtitle">Struktur Hubungan Tabel Utama - Aplikasi Presensi SIASEK SMP Negeri 1 Biau</div>

        <div class="schema-grid">
            <!-- MODUL 1: PRESENSI INTI -->
            <div class="module-box">
                <div class="mod-title mod-presensi">📌 MODUL PRESENSI INTI</div>
                <div class="table-card">
                    <div class="tbl-name">attendances (Presensi Gerbang)</div>
                    <div class="tbl-field"><span class="pk">PK</span> id | <span class="fk">FK</span> student_id, semester_id</div>
                    <div class="tbl-field">attendance_time, checkout_time, status</div>
                </div>
                <div class="table-card">
                    <div class="tbl-name">subject_attendances (Presensi Mapel)</div>
                    <div class="tbl-field"><span class="pk">PK</span> id | <span class="fk">FK</span> student_id, schedule_id</div>
                    <div class="tbl-field">status, notes, created_at</div>
                </div>
                <div class="table-card">
                    <div class="tbl-name">teacher_attendances (Presensi Guru)</div>
                    <div class="tbl-field"><span class="pk">PK</span> id | <span class="fk">FK</span> teacher_id</div>
                    <div class="tbl-field">status, latitude, longitude, photo_path</div>
                </div>
            </div>

            <!-- MODUL 2: AKADEMIK & KBM -->
            <div class="module-box">
                <div class="mod-title mod-akademik">🎓 MODUL AKADEMIK & KBM</div>
                <div class="table-card">
                    <div class="tbl-name">students (Siswa)</div>
                    <div class="tbl-field"><span class="pk">PK</span> id | <span class="fk">FK</span> school_class_id</div>
                    <div class="tbl-field">nis, name, unique_id, face_descriptor</div>
                </div>
                <div class="table-card">
                    <div class="tbl-name">teachers (Guru)</div>
                    <div class="tbl-field"><span class="pk">PK</span> id | <span class="fk">FK</span> user_id</div>
                    <div class="tbl-field">nip, nuptk, name, face_descriptor</div>
                </div>
                <div class="table-card">
                    <div class="tbl-name">school_classes, schedules, journals</div>
                    <div class="tbl-field"><span class="pk">PK</span> id | <span class="fk">FK</span> teacher_id, level_id, subject_id</div>
                    <div class="tbl-field">materi_pokok, kegiatan_pembelajaran</div>
                </div>
            </div>

            <!-- MODUL 3: USERS & AUTH -->
            <div class="module-box">
                <div class="mod-title mod-users">🔐 USERS & SINGLE SIGN-ON</div>
                <div class="table-card">
                    <div class="tbl-name">users (Pengguna Sistem)</div>
                    <div class="tbl-field"><span class="pk">PK</span> id | name, email, password, role</div>
                    <div class="tbl-field">last_seen_at, deleted_at</div>
                </div>
                <div class="table-card">
                    <div class="tbl-name">parents (Orang Tua Siswa)</div>
                    <div class="tbl-field"><span class="pk">PK</span> id | <span class="fk">FK</span> user_id</div>
                    <div class="tbl-field">phone_number, is_onboarding_completed</div>
                </div>
                <div class="table-card">
                    <div class="tbl-name">sso_tokens (Token SSO Mokopani)</div>
                    <div class="tbl-field"><span class="pk">PK</span> id | <span class="fk">FK</span> user_id</div>
                    <div class="tbl-field">token, expires_at, is_used</div>
                </div>
            </div>

            <!-- MODUL 4: PERIZINAN & PERMIT -->
            <div class="module-box">
                <div class="mod-title mod-ijin">📑 PERIZINAN & PERMIT</div>
                <div class="table-card">
                    <div class="tbl-name">leave_requests (Pengajuan Izin/Sakit)</div>
                    <div class="tbl-field"><span class="pk">PK</span> id | <span class="fk">FK</span> student_id, parent_id, approved_by</div>
                    <div class="tbl-field">start_date, end_date, type, status, attachment</div>
                </div>
                <div class="table-card">
                    <div class="tbl-name">student_permits (Surat Permit Gerbang)</div>
                    <div class="tbl-field"><span class="pk">PK</span> id | <span class="fk">FK</span> student_id, attendance_id</div>
                    <div class="tbl-field">time_out, time_in, reason</div>
                </div>
            </div>

            <!-- MODUL 5: PESAN & NOTIFIKASI -->
            <div class="module-box">
                <div class="mod-title mod-chat">💬 PESAN & NOTIFIKASI</div>
                <div class="table-card">
                    <div class="tbl-name">conversations & messages</div>
                    <div class="tbl-field"><span class="pk">PK</span> id | <span class="fk">FK</span> parent_id, teacher_id</div>
                    <div class="tbl-field">body, is_read, attachment_path</div>
                </div>
                <div class="table-card">
                    <div class="tbl-name">notifications</div>
                    <div class="tbl-field"><span class="pk">PK</span> id | <span class="fk">FK</span> user_id</div>
                    <div class="tbl-field">type, data, read_at</div>
                </div>
            </div>

            <!-- MODUL 6: SPATIE RBAC & LAINNYA -->
            <div class="module-box">
                <div class="mod-title mod-spatie">🛡️ SPATIE RBAC & LOGS</div>
                <div class="table-card">
                    <div class="tbl-name">roles & permissions (Spatie RBAC)</div>
                    <div class="tbl-field">17 Roles, 23 Permissions</div>
                    <div class="tbl-field">model_has_roles, role_has_permissions</div>
                </div>
                <div class="table-card">
                    <div class="tbl-name">89 Tabel Database Total</div>
                    <div class="tbl-field">db_absen shared database dengan SIPADA</div>
                    <div class="tbl-field">Full relational integrity & foreign keys</div>
                </div>
            </div>
        </div>

        <div class="footer-note">Dokumentasi Skema Database Resmi - Aplikasi Presensi SIASEK SMP Negeri 1 Biau (Total 89 Tabel)</div>
    </div>
</body>
</html>'''

    db_html_path = os.path.join(assets_dir, 'clean_db_diagram.html')
    with open(db_html_path, 'w', encoding='utf-8') as f:
        f.write(db_html_content)

    edge_bin = r'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'

    # Render Architecture Diagram
    arch_url = 'file:///' + arch_html_path.replace('\\', '/')
    cmd_arch = [
        edge_bin,
        '--headless',
        '--disable-gpu',
        f'--screenshot={arch_png}',
        '--window-size=1650,1450',
        arch_url
    ]
    subprocess.run(cmd_arch, check=True)
    print(f"Clean Architecture Diagram Generated: {os.path.exists(arch_png)}")

    # Render Database Diagram
    db_url = 'file:///' + db_html_path.replace('\\', '/')
    cmd_db = [
        edge_bin,
        '--headless',
        '--disable-gpu',
        f'--screenshot={db_png}',
        '--window-size=1750,1100',
        db_url
    ]
    subprocess.run(cmd_db, check=True)
    print(f"Clean Database Diagram Generated: {os.path.exists(db_png)}")

    # Validate image size
    for name, p in [('Architecture', arch_png), ('Database', db_png)]:
        img = Image.open(p)
        w, h = img.size
        size_kb = os.path.getsize(p) / 1024.0
        print(f"[{name} Diagram Validation] Dimensions: {w}x{h}px | File Size: {size_kb:.2f} KB")

if __name__ == '__main__':
    generate_clean_diagrams()

