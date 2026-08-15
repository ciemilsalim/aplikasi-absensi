<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Kehadiran Bulanan Kelas {{ $class->name }} - {{ $selectedDate->translatedFormat('F Y') }}</title>
    <style>
        @page {
            @if(isset($paperSize) && in_array(strtolower($paperSize), ['folio', 'f4']))
                size: 330mm 215mm landscape;
            @else
                size: 297mm 210mm landscape;
            @endif
            margin: 8mm 10mm 10mm 10mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 7.5pt;
            line-height: 1.2;
            color: #0f172a;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* Screen Preview Floating Action Toolbar */
        .no-print-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #0f172a;
            color: #ffffff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 12px;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .no-print-toolbar select, .no-print-toolbar input {
            background: #1e293b;
            color: #ffffff;
            border: 1px solid #334155;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            outline: none;
        }

        .no-print-toolbar button {
            background: #0284c7;
            color: #ffffff;
            border: none;
            padding: 7px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .no-print-toolbar button:hover {
            background: #0369a1;
        }

        .screen-container {
            padding-top: 55px;
        }

        /* Kop Surat Resmi */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }

        .kop-logo {
            width: 70px;
            text-align: center;
            vertical-align: middle;
        }

        .kop-logo img {
            max-height: 60px;
            max-width: 65px;
            object-fit: contain;
        }

        .kop-text {
            text-align: center;
            vertical-align: middle;
            padding: 0 10px;
        }

        .kop-school-name {
            font-size: 13pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            line-height: 1.2;
        }

        .kop-school-address {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 2px;
            line-height: 1.3;
        }

        .kop-line-double {
            border-top: 2px solid #0f172a;
            border-bottom: 0.75px solid #0f172a;
            height: 2px;
            margin: 4px 0 8px 0;
        }

        /* Dokumen Title & Metadata */
        .doc-title-box {
            text-align: center;
            margin-bottom: 8px;
        }

        .doc-title {
            font-size: 10.5pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .doc-subtitle {
            font-size: 8pt;
            font-weight: 600;
            color: #334155;
            margin-top: 1px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 7.5pt;
        }

        .meta-table td {
            padding: 1.5px 0;
            vertical-align: middle;
        }

        .meta-label {
            font-weight: 700;
            color: #334155;
        }

        .meta-val {
            font-weight: 600;
            color: #0f172a;
        }

        .badge-pill {
            display: inline-block;
            padding: 1px 6px;
            font-size: 7pt;
            font-weight: 700;
            border-radius: 9999px;
            background-color: #f1f5f9;
            color: #0f172a;
            border: 0.5px solid #cbd5e1;
        }

        /* Main Attendance Matrix Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 6.8pt;
        }

        .data-table th, .data-table td {
            border: 0.5px solid #64748b;
            padding: 3px 1px;
            text-align: center;
            vertical-align: middle;
        }

        .data-table thead th {
            background-color: #f1f5f9;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            font-size: 6.5pt;
        }

        .data-table thead th.th-main {
            background-color: #e2e8f0;
            border-bottom: 1px solid #475569;
        }

        .data-table thead th.th-sub {
            font-size: 6pt;
            background-color: #f8fafc;
        }

        .data-table thead th.th-recap {
            background-color: #cbd5e1;
            font-weight: 800;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .data-table tbody td.text-left {
            text-align: left;
            padding-left: 4px;
        }

        .data-table tbody td.font-bold {
            font-weight: 700;
        }

        .data-table tbody td.col-highlight {
            background-color: #f1f5f9;
            font-weight: 700;
        }

        .status-h { color: #16a34a; font-weight: 700; }
        .status-t { color: #d97706; font-weight: 700; }
        .status-s { color: #9333ea; font-weight: 700; }
        .status-i { color: #0284c7; font-weight: 700; }
        .status-a { color: #dc2626; font-weight: 700; }
        .status-bm { color: #475569; font-style: italic; }

        .data-table tfoot th {
            background-color: #e2e8f0;
            font-weight: 800;
            border: 0.5px solid #64748b;
            padding: 3px 1px;
            font-size: 6.5pt;
        }

        /* KPI Box Summary */
        .kpi-container {
            width: 100%;
            margin-top: 8px;
            margin-bottom: 8px;
            border-collapse: collapse;
        }

        .kpi-cell {
            padding: 4px 8px;
            background-color: #f8fafc;
            border: 0.5px solid #cbd5e1;
            border-radius: 4px;
            font-size: 7.2pt;
        }

        .kpi-title {
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            font-size: 6.2pt;
        }

        .kpi-value {
            font-size: 8.5pt;
            font-weight: 800;
            color: #0f172a;
            margin-top: 1px;
        }

        /* Signatures / Tanda Tangan */
        .signature-table {
            width: 100%;
            margin-top: 12px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .signature-table td {
            vertical-align: top;
            text-align: center;
            font-size: 7.5pt;
            padding: 0 15px;
        }

        .signature-name {
            font-weight: 800;
            text-decoration: underline;
            color: #0f172a;
            margin-top: 45px;
            font-size: 8pt;
        }

        .signature-nip {
            font-size: 7pt;
            color: #334155;
            margin-top: 1px;
        }

        /* Document Footer / Timestamp */
        .doc-footer {
            margin-top: 12px;
            padding-top: 4px;
            border-top: 0.5px dashed #cbd5e1;
            font-size: 6.2pt;
            color: #64748b;
            display: table;
            width: 100%;
        }

        .doc-footer-left {
            display: table-cell;
            text-align: left;
        }

        .doc-footer-right {
            display: table-cell;
            text-align: right;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            .screen-container {
                padding-top: 0 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Toolbar for Screen Preview (Hidden on Print) -->
    <div class="no-print no-print-toolbar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-weight: 700; font-size: 13px;">📄 Rekap Presensi Kelas {{ $class->name }}</span>
            <span style="color: #94a3b8; font-size: 11px;">(Ukuran: {{ strtoupper($paperSize ?? 'A4') }} Landscape)</span>
        </div>
        <form method="GET" action="{{ route('teacher.attendance.print') }}" style="display: flex; align-items: center; gap: 10px; margin: 0;">
            <input type="month" name="month" value="{{ $selectedDate->format('Y-m') }}" onchange="this.form.submit()" title="Ganti Bulan">
            <select name="paper_size" onchange="this.form.submit()" title="Pilih Ukuran Kertas">
                <option value="a4" {{ ($paperSize ?? 'a4') === 'a4' ? 'selected' : '' }}>Kertas A4 Landscape</option>
                <option value="folio" {{ in_array(($paperSize ?? 'a4'), ['folio', 'f4']) ? 'selected' : '' }}>Kertas Folio / F4 Landscape</option>
            </select>
            <button type="button" onclick="window.print()">
                <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak / Print
            </button>
            <button type="button" onclick="window.close()" style="background: #475569;">
                Tutup
            </button>
        </form>
    </div>

    <div class="screen-container">
        <!-- ================= 1. KOP SURAT RESMI ================= -->
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo Sekolah">
                    @endif
                </td>
                <td class="kop-text">
                    <h1 class="kop-school-name">{{ $schoolName }}</h1>
                    <div class="kop-school-address">
                        {{ $schoolAddress }}
                    </div>
                </td>
                <td style="width: 70px;"></td>
            </tr>
        </table>
        <div class="kop-line-double"></div>

        <!-- ================= 2. JUDUL DOKUMEN & METADATA ================= -->
        <div class="doc-title-box">
            <h2 class="doc-title">LAPORAN REKAPITULASI PRESENSI BULANAN SISWA</h2>
            <div class="doc-subtitle">
                BULAN {{ strtoupper($selectedDate->translatedFormat('F Y')) }} &bull; KELAS {{ $class->name }}
            </div>
        </div>

        <table class="meta-table">
            <tr>
                <td style="width: 35%;">
                    <span class="meta-label">Kelas / Rombel:</span>
                    <span class="meta-val">{{ $class->name }}</span>
                </td>
                <td style="width: 35%;">
                    <span class="meta-label">Wali Kelas:</span>
                    <span class="meta-val">{{ $homeroomTeacherName ?? '-' }}</span>
                </td>
                <td style="width: 30%; text-align: right;">
                    <span class="meta-label">Hari Efektif:</span>
                    <span class="badge-pill">{{ $effectiveDaysCount ?? 0 }} Hari</span>
                    <span class="meta-label" style="margin-left: 6px;">Total Siswa:</span>
                    <span class="badge-pill">{{ $totalStudents ?? count($students) }} Siswa</span>
                </td>
            </tr>
        </table>

        <!-- ================= 3. TABEL MATRIKS PRESENSI BULANAN ================= -->
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" class="th-main" style="width: 2.2%;">NO</th>
                    <th rowspan="2" class="th-main" style="width: 7%;">NIS/NISN</th>
                    <th rowspan="2" class="th-main text-left" style="width: 17%;">NAMA LENGKAP SISWA</th>
                    <th colspan="{{ $period->count() }}" class="th-main">
                        TANGGAL HARI EFEKTIF BELAJAR ({{ $effectiveDaysCount }} HARI)
                    </th>
                    <th colspan="6" class="th-main th-recap">
                        REKAPITULASI BULANAN
                    </th>
                    <th rowspan="2" class="th-main" style="width: 4%;">KET</th>
                </tr>
                <tr>
                    @foreach ($period as $date)
                        <th class="th-sub" style="width: calc(65% / {{ max(1, $period->count()) }});">
                            {{ $date->format('d') }}
                        </th>
                    @endforeach
                    <th class="th-sub" style="width: 2.2%; color: #16a34a; font-weight: 800;">H</th>
                    <th class="th-sub" style="width: 2.2%; color: #9333ea; font-weight: 800;">S</th>
                    <th class="th-sub" style="width: 2.2%; color: #0284c7; font-weight: 800;">I</th>
                    <th class="th-sub" style="width: 2.2%; color: #dc2626; font-weight: 800;">A</th>
                    <th class="th-sub" style="width: 2.5%; font-weight: 800; background-color: #e2e8f0;">JML</th>
                    <th class="th-sub" style="width: 3.2%; font-weight: 800; background-color: #cbd5e1;">%</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $dailySumH = []; $dailySumS = []; $dailySumI = []; $dailySumA = [];
                    foreach ($period as $date) {
                        $dStr = $date->format('Y-m-d');
                        $dailySumH[$dStr] = 0;
                        $dailySumS[$dStr] = 0;
                        $dailySumI[$dStr] = 0;
                        $dailySumA[$dStr] = 0;
                    }
                    $classSumH = 0; $classSumS = 0; $classSumI = 0; $classSumA = 0; $classSumJml = 0;
                @endphp

                @forelse ($students as $index => $student)
                    @php
                        $summary = $attendanceSummary[$student->id];
                        $classSumH += $summary['hadir'];
                        $classSumS += $summary['sakit'];
                        $classSumI += $summary['izin'];
                        $classSumA += $summary['alpa'];
                        $classSumJml += $summary['jml'];
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td style="font-family: monospace; font-size: 6.2pt;">{{ $student->nis ?? '-' }}</td>
                        <td class="text-left font-bold">{{ $student->name }}</td>
                        
                        @foreach ($period as $date)
                            @php
                                $dateString = $date->format('Y-m-d');
                                $attendanceRecord = $attendances->get($student->id, collect())->get($dateString);
                                $status = $attendanceRecord ? $attendanceRecord->status : null;
                                $statusText = '-';
                                $statusClass = '';
                                
                                $isSelfStudy = \App\Models\Calendar::isDateInSelfStudy($date, $selfStudyDays);

                                if ($isSelfStudy) {
                                    $statusText = 'BM';
                                    $statusClass = 'status-bm';
                                    $dailySumH[$dateString]++;
                                } else {
                                    switch ($status) {
                                        case 'tepat_waktu': 
                                            $statusText = 'H'; 
                                            $statusClass = 'status-h'; 
                                            $dailySumH[$dateString]++;
                                            break;
                                        case 'terlambat': 
                                            $statusText = 'T'; 
                                            $statusClass = 'status-t'; 
                                            $dailySumH[$dateString]++;
                                            break;
                                        case 'sakit': 
                                            $statusText = 'S'; 
                                            $statusClass = 'status-s'; 
                                            $dailySumS[$dateString]++;
                                            break;
                                        case 'izin': 
                                            $statusText = 'I'; 
                                            $statusClass = 'status-i'; 
                                            $dailySumI[$dateString]++;
                                            break;
                                        case 'alpa': 
                                            $statusText = 'A'; 
                                            $statusClass = 'status-a'; 
                                            $dailySumA[$dateString]++;
                                            break;
                                        default:
                                            $statusText = '-';
                                            break;
                                    }
                                }
                            @endphp
                            <td class="{{ $statusClass }}">{{ $statusText }}</td>
                        @endforeach

                        {{-- Kolom Rekapitulasi Siswa --}}
                        <td style="color: #16a34a; font-weight: 700;">{{ $summary['hadir'] }}</td>
                        <td style="color: #9333ea;">{{ $summary['sakit'] == 0 ? '-' : $summary['sakit'] }}</td>
                        <td style="color: #0284c7;">{{ $summary['izin'] == 0 ? '-' : $summary['izin'] }}</td>
                        <td style="color: #dc2626;">{{ $summary['alpa'] == 0 ? '-' : $summary['alpa'] }}</td>
                        <td class="font-bold" style="background-color: #f1f5f9;">{{ $summary['jml'] == 0 ? '-' : $summary['jml'] }}</td>
                        <td class="font-bold" style="background-color: #e2e8f0;">{{ $summary['persen_str'] }}</td>

                        <td style="font-size: 5.8pt; color: #475569;">
                            @if($summary['persen'] >= 100)
                                <span style="color: #16a34a; font-weight: bold;">Sempurna</span>
                            @elseif($summary['persen'] >= 90)
                                <span style="color: #0284c7;">Baik</span>
                            @elseif($summary['persen'] >= 80)
                                <span style="color: #d97706;">Cukup</span>
                            @else
                                <span style="color: #dc2626; font-weight: bold;">Perhatian</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $period->count() + 10 }}" style="padding: 12px; color: #64748b;">
                            Tidak ada data siswa atau absensi untuk kelas dan periode bulan ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($students) > 0)
                <tfoot>
                    <tr>
                        <th colspan="3" style="text-align: right; padding-right: 6px;">TOTAL HADIR (H+T+BM):</th>
                        @foreach ($period as $date)
                            @php $dStr = $date->format('Y-m-d'); @endphp
                            <th style="color: #16a34a;">{{ $dailySumH[$dStr] }}</th>
                        @endforeach
                        <th style="color: #16a34a;">{{ $classSumH }}</th>
                        <th style="color: #9333ea;">{{ $classSumS }}</th>
                        <th style="color: #0284c7;">{{ $classSumI }}</th>
                        <th style="color: #dc2626;">{{ $classSumA }}</th>
                        <th style="background-color: #cbd5e1; font-weight: 900;">{{ $classSumJml }}</th>
                        <th style="background-color: #94a3b8; color: #ffffff; font-weight: 900;">{{ $classAverageAttendance }}%</th>
                        <th></th>
                    </tr>
                </tfoot>
            @endif
        </table>

        <!-- ================= 4. RINGKASAN STATISTIK KELAS ================= -->
        <table class="kpi-container">
            <tr>
                <td style="width: 25%;" class="kpi-cell">
                    <div class="kpi-title">Rata-rata Kehadiran Kelas</div>
                    <div class="kpi-value" style="color: #0284c7;">{{ $classAverageAttendance ?? 0 }}%</div>
                </td>
                <td style="width: 25%;" class="kpi-cell">
                    <div class="kpi-title">Kehadiran Sempurna (100%)</div>
                    <div class="kpi-value" style="color: #16a34a;">{{ $perfectAttendanceCount ?? 0 }} Siswa</div>
                </td>
                <td style="width: 25%;" class="kpi-cell">
                    <div class="kpi-title">Perlu Perhatian (&lt; 85%)</div>
                    <div class="kpi-value" style="color: #dc2626;">{{ $needsAttentionCount ?? 0 }} Siswa</div>
                </td>
                <td style="width: 25%;" class="kpi-cell">
                    <div class="kpi-title">Format Kertas Cetak</div>
                    <div class="kpi-value" style="text-transform: uppercase;">{{ strtoupper($paperSize ?? 'A4') }} Landscape</div>
                </td>
            </tr>
        </table>

        <!-- ================= 5. TANDA TANGAN RESMI ================= -->
        <table class="signature-table">
            <tr>
                <td style="width: 45%;">
                    Mengetahui,<br>
                    <strong>Kepala Sekolah</strong><br>
                    <div class="signature-name">{{ $headmasterName ?? '_________________________' }}</div>
                    <div class="signature-nip">NIP. {{ $headmasterNip ?? '_________________________' }}</div>
                </td>
                <td style="width: 10%;"></td>
                <td style="width: 45%;">
                    {{ !empty($schoolCity) ? $schoolCity : 'Buol' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                    <strong>Wali Kelas {{ $class->name }}</strong><br>
                    <div class="signature-name">{{ $homeroomTeacherName ?? '_________________________' }}</div>
                    <div class="signature-nip">NIP. {{ $homeroomTeacherNip ?? '_________________________' }}</div>
                </td>
            </tr>
        </table>

        <!-- ================= 6. DOKUMEN FOOTER / WATERMARK ================= -->
        <div class="doc-footer">
            <div class="doc-footer-left">
                Dokumen resmi dikeluarkan melalui Sistem Informasi Presensi Sekolah ({{ config('app.name', 'SIASEK') }}) &bull; Ukuran Kertas: {{ strtoupper($paperSize ?? 'A4') }} Landscape
            </div>
            <div class="doc-footer-right">
                Dicetak pada: {{ $printDate }} &bull; Oleh: Wali Kelas
            </div>
        </div>
    </div>

</body>
</html>


