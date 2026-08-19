<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Mengajar - {{ $teacher->name }} - {{ $subject?->name ?? 'Mata Pelajaran' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            font-size: 11pt;
            color: #1e293b;
            background-color: #f8fafc;
            padding: 20px;
        }
        .print-container {
            max-width: 1100px;
            margin: 0 auto;
            background: #ffffff;
            padding: 35px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .kop-header {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            border-bottom: 3px double #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
            position: relative;
        }
        .kop-header .logo {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 70px;
            height: auto;
        }
        .kop-title h1 {
            font-size: 14pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }
        .kop-title h2 {
            font-size: 16pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0f172a;
            margin: 2px 0;
        }
        .kop-title p {
            font-size: 9pt;
            color: #475569;
            font-weight: 500;
        }
        .doc-title {
            text-align: center;
            margin: 20px 0 25px 0;
        }
        .doc-title h3 {
            font-size: 13pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: underline;
            color: #0f172a;
        }
        .doc-title span {
            font-size: 10pt;
            font-weight: 600;
            color: #334155;
        }
        .section-title {
            font-size: 11pt;
            font-weight: 800;
            text-transform: uppercase;
            margin: 25px 0 10px 0;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9.5pt;
        }
        table.border-table th, table.border-table td {
            border: 1px solid #334155;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.border-table th {
            background-color: #f1f5f9;
            font-weight: 800;
            text-align: center;
            color: #0f172a;
        }
        table.identity-table td {
            padding: 4px 8px;
            border: none;
        }
        table.identity-table tr td:first-child {
            width: 220px;
            font-weight: 600;
            color: #334155;
        }
        table.identity-table tr td:nth-child(2) {
            width: 20px;
            text-align: center;
        }
        table.identity-table tr td:last-child {
            font-weight: 700;
            color: #0f172a;
        }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .sig-box {
            text-align: center;
            width: 320px;
        }
        .sig-box .name {
            margin-top: 75px;
            font-weight: 800;
            text-decoration: underline;
            color: #0f172a;
        }
        .sig-box .nip {
            font-size: 9pt;
            font-weight: 600;
            color: #475569;
        }
        .no-print-bar {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            background: #ffffff;
            padding: 12px 18px;
            border-radius: 50px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
            border: 1px solid #e2e8f0;
            z-index: 9999;
        }
        .btn-print {
            background: #0284c7;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-close {
            background: #f1f5f9;
            color: #475569;
            border: none;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        @media print {
            @page {
                size: A4 landscape;
                margin: 12mm 15mm;
            }
            body {
                background: #ffffff;
                padding: 0;
                font-size: 9pt;
            }
            .print-container {
                max-width: 100%;
                padding: 0;
                border: none;
                box-shadow: none;
            }
            .no-print-bar {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Bar -->
    <div class="no-print-bar">
        <a href="{{ route('teacher.journals.index') }}" class="btn-close">
            <span class="material-icons" style="font-size: 16px;">arrow_back</span>
            <span>Kembali</span>
        </a>
        <button onclick="window.print()" class="btn-print">
            <span class="material-icons" style="font-size: 16px;">print</span>
            <span>Cetak Dokumen Resmi</span>
        </button>
    </div>

    <div class="print-container">
        <!-- KOP SEKOLAH -->
        <div class="kop-header">
            <div class="kop-title">
                <h1>PEMERINTAH KABUPATEN BUOL</h1>
                <h2>DINAS PENDIDIKAN DAN KEBUDAYAAN</h2>
                <h2>{{ $schoolName }}</h2>
                <p>Alamat: Jl. Syarif Mansur No. 1, Kec. Biau, Kab. Buol, Sulawesi Tengah 94563</p>
            </div>
        </div>

        <!-- JUDUL DOKUMEN -->
        <div class="doc-title">
            <h3>JURNAL MENGAJAR GURU MATA PELAJARAN</h3>
            <span>Tahun Pelajaran 2026/2027 &bull; Fase D</span>
        </div>

        <!-- A. IDENTITAS GURU -->
        <div class="section-title">A. IDENTITAS GURU</div>
        <table class="identity-table">
            <tr>
                <td>Nama Satuan Pendidikan</td>
                <td>:</td>
                <td><strong>{{ $schoolName }}</strong></td>
            </tr>
            <tr>
                <td>Nama Guru Mata Pelajaran</td>
                <td>:</td>
                <td><strong>{{ $teacher->name }}</strong></td>
            </tr>
            <tr>
                <td>NIP / NUPTK</td>
                <td>:</td>
                <td>{{ $teacher->nip ?: '-' }}</td>
            </tr>
            <tr>
                <td>Mata Pelajaran</td>
                <td>:</td>
                <td>{{ $subject?->name ?? 'Semua Mata Pelajaran' }}</td>
            </tr>
            <tr>
                <td>Fase / Jenjang</td>
                <td>:</td>
                <td><strong>D (SMP)</strong></td>
            </tr>
            <tr>
                <td>Kelas / Rombel</td>
                <td>:</td>
                <td>{{ $schoolClass?->name ?? 'Kelas VII / VIII / IX' }}</td>
            </tr>
            <tr>
                <td>Semester</td>
                <td>:</td>
                <td>Ganjil / Genap</td>
            </tr>
            <tr>
                <td>Tahun Pelajaran</td>
                <td>:</td>
                <td><strong>2026/2027</strong></td>
            </tr>
        </table>

        <!-- B. JURNAL PELAKSANAAN PEMBELAJARAN -->
        <div class="section-title">B. JURNAL PELAKSANAAN PEMBELAJARAN</div>
        <table class="border-table">
            <thead>
                <tr>
                    <th style="width: 30px;">No.</th>
                    <th style="width: 85px;">Hari / Tanggal</th>
                    <th style="width: 55px;">Kelas</th>
                    <th style="width: 35px;">JP</th>
                    <th style="width: 170px;">Tujuan Pembelajaran (TP)</th>
                    <th style="width: 110px;">Topik / Materi</th>
                    <th style="width: 180px;">Kegiatan Pembelajaran</th>
                    <th style="width: 85px;">Asesmen</th>
                    <th style="width: 130px;">Hasil / Refleksi</th>
                    <th style="width: 110px;">Tindak Lanjut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($journals as $index => $j)
                    <tr>
                        <td style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $j->date->translatedFormat('l') }}</strong><br>
                            <span style="font-size: 8pt; color: #475569;">{{ $j->date->translatedFormat('d/m/Y') }}</span>
                        </td>
                        <td style="text-align: center; font-weight: bold;">{{ $j->schoolClass?->name ?? $j->schedule?->getTargetClass()?->name ?? '-' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $j->jp }}</td>
                        <td>{{ $j->learning_objective }}</td>
                        <td><strong>{{ $j->topic }}</strong></td>
                        <td style="font-size: 8.5pt;">{{ $j->activity }}</td>
                        <td style="text-align: center; font-weight: 600;">{{ $j->assessment }}</td>
                        <td style="font-size: 8.5pt;">{{ $j->reflection }}</td>
                        <td style="font-size: 8.5pt;">{{ $j->follow_up }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 20px; color: #64748b;">
                            Belum ada rekaman jurnal pelaksanaan pembelajaran pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- C. REKAP MINGGUAN (JIKA ADA) -->
        @if(!empty($weeklyData))
            <div class="page-break"></div>
            <div class="section-title">C. REKAP JURNAL MENGAJAR MINGGUAN</div>
            <table class="border-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">Minggu Ke-</th>
                        <th style="width: 110px;">Periode</th>
                        <th style="width: 60px;">Kelas</th>
                        <th style="width: 60px;">Jml Pertemuan</th>
                        <th style="width: 50px;">Jml JP</th>
                        <th>TP yang Dilaksanakan</th>
                        <th style="width: 95px;">Asesmen Dominan</th>
                        <th style="width: 140px;">Catatan / Tindak Lanjut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($weeklyData as $w)
                        <tr>
                            <td style="text-align: center; font-weight: bold;">{{ $w['week_number'] }}</td>
                            <td>{{ $w['period'] }}</td>
                            <td style="text-align: center; font-weight: bold;">{{ $w['class_name'] }}</td>
                            <td style="text-align: center; font-weight: bold;">{{ $w['meeting_count'] }}</td>
                            <td style="text-align: center; font-weight: bold;">{{ $w['total_jp'] }}</td>
                            <td style="font-size: 8.5pt;">{{ $w['tp_conducted'] }}</td>
                            <td style="text-align: center; font-weight: 600;">{{ $w['dominant_assessment'] }}</td>
                            <td style="font-size: 8.5pt;">{{ $w['notes_follow_up'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- F. REFLEKSI GURU SEMESTER (JIKA DIISI) -->
        @if($reflection)
            <div class="section-title">F. REFLEKSI GURU SEMESTER</div>
            <table class="border-table">
                <thead>
                    <tr>
                        <th style="width: 240px;">Aspek Evaluatif</th>
                        <th>Refleksi Guru</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>1. Pembelajaran yang Berjalan Baik</strong></td>
                        <td>{{ $reflection->good_aspects }}</td>
                    </tr>
                    <tr>
                        <td><strong>2. Kendala Utama yang Dihadapi</strong></td>
                        <td>{{ $reflection->challenges }}</td>
                    </tr>
                    <tr>
                        <td><strong>3. Peserta Didik yang Memerlukan Perhatian</strong></td>
                        <td>{{ $reflection->attention_students }}</td>
                    </tr>
                    <tr>
                        <td><strong>4. Strategi yang Efektif</strong></td>
                        <td>{{ $reflection->effective_strategies }}</td>
                    </tr>
                    <tr>
                        <td><strong>5. Perbaikan Pembelajaran Berikutnya</strong></td>
                        <td>{{ $reflection->future_improvements }}</td>
                    </tr>
                    <tr>
                        <td><strong>6. Rencana Tindak Lanjut</strong></td>
                        <td>{{ $reflection->follow_up_plan }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        <!-- G. PENGESAHAN -->
        <div class="signature-section">
            <div class="sig-box">
                <div>Mengetahui,</div>
                <div>Kepala {{ $schoolName }}</div>
                <div class="name">{{ $principalName }}</div>
                <div class="nip">NIP. {{ $principalNip }}</div>
            </div>

            <div class="sig-box">
                <div>Biau, {{ \Carbon\Carbon::today()->translatedFormat('d F Y') }}</div>
                <div>Guru Mata Pelajaran</div>
                <div class="name">{{ $teacher->name }}</div>
                <div class="nip">NIP. {{ $teacher->nip ?: '.....................................................' }}</div>
            </div>
        </div>
    </div>

</body>
</html>
