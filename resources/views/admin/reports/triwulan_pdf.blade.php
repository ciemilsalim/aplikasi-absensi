<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Kehadiran Siswa - Triwulan {{ $trimester }} Tahun {{ $year }}</title>
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
            font-size: 8pt;
            line-height: 1.25;
            color: #0f172a;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
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

        /* Main Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 7pt;
        }

        .data-table th, .data-table td {
            border: 0.5px solid #64748b;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
        }

        .data-table thead th {
            background-color: #f1f5f9;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            font-size: 6.8pt;
            line-height: 1.2;
        }

        .data-table thead th.th-main {
            background-color: #e2e8f0;
            border-bottom: 1px solid #475569;
        }

        .data-table thead th.th-sub {
            font-size: 6.2pt;
            background-color: #f8fafc;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .data-table tbody td.text-left {
            text-align: left;
            padding-left: 5px;
        }

        .data-table tbody td.font-bold {
            font-weight: 700;
        }

        .data-table tbody td.col-highlight {
            background-color: #f1f5f9;
            font-weight: 700;
        }

        .data-table tfoot th {
            background-color: #e2e8f0;
            font-weight: 800;
            border: 0.5px solid #64748b;
            padding: 4px 2px;
            font-size: 6.8pt;
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
    </style>
</head>
<body>

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
        <h2 class="doc-title">FORM VERIFIKASI KOMITMEN PENDIDIKAN &bull; REKAPITULASI KEHADIRAN SISWA</h2>
        <div class="doc-subtitle">
            TRIWULAN {{ $trimester }} TAHUN {{ $year }} &bull; PERIODE {{ $trimesterMap[$months[0]]['name'] }} &ndash; {{ $trimesterMap[$months[2]]['name'] }} {{ $year }}
        </div>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 35%;">
                <span class="meta-label">Kelas / Rombel:</span>
                <span class="meta-val">{{ $className }}</span>
            </td>
            <td style="width: 35%;">
                <span class="meta-label">Wali Kelas:</span>
                <span class="meta-val">{{ $homeroomTeacherName ?? '-' }}</span>
            </td>
            <td style="width: 30%; text-align: right;">
                <span class="meta-label">Hari Efektif TW:</span>
                <span class="badge-pill">{{ $totalTrimesterEffectiveDays ?? 0 }} Hari</span>
                <span class="meta-label" style="margin-left: 6px;">Total Siswa:</span>
                <span class="badge-pill">{{ $totalStudents ?? count($reportData) }} Siswa</span>
            </td>
        </tr>
    </table>

    <!-- ================= 3. TABEL DATA REKAPITULASI ================= -->
    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" class="th-main" style="width: 2.5%;">NO</th>
                <th rowspan="2" class="th-main" style="width: 8%;">NIS/NISN</th>
                <th rowspan="2" class="th-main text-left" style="width: 19%;">NAMA LENGKAP SISWA</th>
                <th rowspan="2" class="th-main" style="width: 4%;">KELAS</th>
                @foreach($months as $m)
                    <th colspan="5" class="th-main" style="width: 17%;">
                        {{ $trimesterMap[$m]['name'] }}<br>
                        <span style="font-weight: normal; font-size: 5.8pt; text-transform: none; color: #475569;">(Efektif: {{ $trimesterMap[$m]['effective_days'] }} Hari)</span>
                    </th>
                @endforeach
                <th colspan="5" class="th-main" style="width: 15%; background-color: #cbd5e1;">
                    REKAP TRIWULAN {{ $trimester }}<br>
                    <span style="font-weight: normal; font-size: 5.8pt; text-transform: none; color: #334155;">(Total: {{ $totalTrimesterEffectiveDays }} Hari)</span>
                </th>
                <th rowspan="2" class="th-main" style="width: 4.5%;">KET</th>
            </tr>
            <tr>
                @foreach($months as $m)
                    <th class="th-sub" style="width: 3.4%; color: #dc2626;">A</th>
                    <th class="th-sub" style="width: 3.4%; color: #0284c7;">I</th>
                    <th class="th-sub" style="width: 3.4%; color: #9333ea;">S</th>
                    <th class="th-sub" style="width: 3.4%; font-weight: 800;">JML</th>
                    <th class="th-sub" style="width: 3.4%; font-weight: 800; background-color: #f1f5f9;">%</th>
                @endforeach
                {{-- Rekap Total Triwulan --}}
                <th class="th-sub" style="width: 3%; color: #dc2626; background-color: #f1f5f9;">A</th>
                <th class="th-sub" style="width: 3%; color: #0284c7; background-color: #f1f5f9;">I</th>
                <th class="th-sub" style="width: 3%; color: #9333ea; background-color: #f1f5f9;">S</th>
                <th class="th-sub" style="width: 3%; font-weight: 800; background-color: #e2e8f0;">JML</th>
                <th class="th-sub" style="width: 3%; font-weight: 800; background-color: #cbd5e1;">% TW</th>
            </tr>
        </thead>
        <tbody>
            @php
                $sumMonthA = []; $sumMonthI = []; $sumMonthS = []; $sumMonthJml = [];
                $sumTotalA = 0; $sumTotalI = 0; $sumTotalS = 0; $sumTotalJml = 0;
                foreach($months as $m) {
                    $sumMonthA[$m] = 0; $sumMonthI[$m] = 0; $sumMonthS[$m] = 0; $sumMonthJml[$m] = 0;
                }
            @endphp

            @forelse($reportData as $index => $student)
                @php
                    $sumTotalA += $student->total_alpa;
                    $sumTotalI += $student->total_izin;
                    $sumTotalS += $student->total_sakit;
                    $sumTotalJml += $student->total_jml;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="font-family: monospace; font-size: 6.5pt;">{{ $student->nis ?? '-' }}</td>
                    <td class="text-left font-bold">{{ $student->name }}</td>
                    <td>{{ explode(' ', $className)[0] ?? $className }}</td>
                    
                    @foreach($months as $m)
                        @php 
                            $mData = $student->monthly_data[$m];
                            $sumMonthA[$m] += $mData['alpa'];
                            $sumMonthI[$m] += $mData['izin'];
                            $sumMonthS[$m] += $mData['sakit'];
                            $sumMonthJml[$m] += $mData['jml'];
                        @endphp
                        <td>{{ $mData['alpa'] == 0 ? '-' : $mData['alpa'] }}</td>
                        <td>{{ $mData['izin'] == 0 ? '-' : $mData['izin'] }}</td>
                        <td>{{ $mData['sakit'] == 0 ? '-' : $mData['sakit'] }}</td>
                        <td class="font-bold">{{ $mData['jml'] == 0 ? '-' : $mData['jml'] }}</td>
                        <td class="col-highlight">{{ $mData['persen'] }}</td>
                    @endforeach
                    
                    {{-- Total Triwulan Siswa --}}
                    <td style="color: #dc2626;">{{ $student->total_alpa == 0 ? '-' : $student->total_alpa }}</td>
                    <td style="color: #0284c7;">{{ $student->total_izin == 0 ? '-' : $student->total_izin }}</td>
                    <td style="color: #9333ea;">{{ $student->total_sakit == 0 ? '-' : $student->total_sakit }}</td>
                    <td class="font-bold" style="background-color: #f1f5f9;">{{ $student->total_jml == 0 ? '-' : $student->total_jml }}</td>
                    <td class="font-bold" style="background-color: #e2e8f0;">{{ $student->total_persen }}</td>

                    <td style="font-size: 6pt; color: #475569;">
                        @if(($student->total_persen_num ?? 0) >= 100)
                            <span style="color: #16a34a; font-weight: bold;">Sempurna</span>
                        @elseif(($student->total_persen_num ?? 0) >= 90)
                            <span style="color: #0284c7;">Baik</span>
                        @elseif(($student->total_persen_num ?? 0) >= 80)
                            <span style="color: #d97706;">Cukup</span>
                        @else
                            <span style="color: #dc2626; font-weight: bold;">Perhatian</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 4 + (count($months) * 5) + 6 }}" style="padding: 12px; color: #64748b;">
                        Tidak ada data siswa atau absensi untuk kelas dan periode triwulan ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($reportData) > 0)
            <tfoot>
                <tr>
                    <th colspan="4" style="text-align: right; padding-right: 8px;">TOTAL REKAPITULASI KELAS:</th>
                    @foreach($months as $m)
                        <th style="color: #dc2626;">{{ $sumMonthA[$m] }}</th>
                        <th style="color: #0284c7;">{{ $sumMonthI[$m] }}</th>
                        <th style="color: #9333ea;">{{ $sumMonthS[$m] }}</th>
                        <th style="font-weight: 900;">{{ $sumMonthJml[$m] }}</th>
                        <th style="background-color: #cbd5e1;">-</th>
                    @endforeach
                    <th style="color: #dc2626;">{{ $sumTotalA }}</th>
                    <th style="color: #0284c7;">{{ $sumTotalI }}</th>
                    <th style="color: #9333ea;">{{ $sumTotalS }}</th>
                    <th style="font-weight: 900; background-color: #cbd5e1;">{{ $sumTotalJml }}</th>
                    <th style="font-weight: 900; background-color: #94a3b8; color: #ffffff;">{{ $classAverageAttendance ?? '0' }}%</th>
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
                {{ $schoolCity ?? '..................' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                <strong>Wali Kelas {{ $className }}</strong><br>
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
            Dicetak pada: {{ $printDate }} &bull; Oleh: {{ $userRole }}
        </div>
    </div>

</body>
</html>

