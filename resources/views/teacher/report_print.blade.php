<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Kehadiran {{ $subjectInfo->name }} - {{ $classInfo->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.8cm 1cm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1e293b;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Screen Preview Sheet Container */
        @media screen {
            .page-container {
                max-width: 29.7cm;
                min-height: 21cm;
                margin: 20px auto;
                background: #ffffff;
                padding: 30px 40px;
                border-radius: 12px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
                border: 1px solid #e2e8f0;
            }
        }

        /* Print Specific Styles */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .no-print {
                display: none !important;
            }
            .page-container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
                border: none !important;
            }
            .break-inside-avoid {
                page-break-inside: avoid !important;
            }
        }

        /* Kop Surat Styling */
        .kop-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding-bottom: 8px;
        }
        .kop-logo {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 75px;
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .kop-logo img {
            max-height: 75px;
            max-width: 75px;
            object-fit: contain;
        }
        .kop-text {
            text-align: center;
            width: 100%;
            padding: 0 85px;
        }
        .kop-text h1 {
            font-size: 16pt;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }
        .kop-text p {
            font-size: 8.5pt;
            margin: 2px 0 0 0;
            color: #334155;
        }
        .kop-divider {
            border-top: 2.5px solid #000000;
            border-bottom: 1px solid #000000;
            height: 4px;
            margin-top: 6px;
            margin-bottom: 12px;
        }

        /* Metadata Information Table */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 10px;
        }
        .meta-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .meta-label {
            font-weight: 600;
            color: #475569;
            width: 110px;
        }
        .meta-val {
            font-weight: 700;
            color: #0f172a;
        }

        /* Attendance Matrix Table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            table-layout: fixed;
        }
        .report-table th, 
        .report-table td {
            border: 1px solid #64748b;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
        }
        .report-table thead th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
        }
        .report-table .student-name {
            text-align: left;
            padding-left: 6px;
            padding-right: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 600;
        }
        .report-table .student-nis {
            font-family: monospace;
            font-size: 7.5pt;
            color: #334155;
        }

        .rotate-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            margin: 0 auto;
            font-size: 7.5pt;
            line-height: 1;
        }

        /* Status Colors - Print Friendly Soft Pastel */
        .st-H { background-color: #dcfce7 !important; color: #166534 !important; font-weight: 700; }
        .st-S { background-color: #fef9c3 !important; color: #854d0e !important; font-weight: 700; }
        .st-I { background-color: #e0f2fe !important; color: #075985 !important; font-weight: 700; }
        .st-A { background-color: #fee2e2 !important; color: #991b1b !important; font-weight: 700; }
        .st-B { background-color: #ffedd5 !important; color: #9a3412 !important; font-weight: 700; }
        .st-none { color: #94a3b8; }

        /* Signatures Block */
        .signature-table {
            width: 100%;
            margin-top: 14px;
            font-size: 8.5pt;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }
        .signature-space {
            height: 48px;
        }
        .signature-name {
            font-weight: 800;
            text-decoration: underline;
            text-transform: uppercase;
            color: #0f172a;
        }
    </style>
</head>
<body>

    @php
        $totalMeetings = isset($period) ? iterator_count($period) : 0;
        $totalStudents = $students->count();
        $grandSummary = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'bolos' => 0];
        $dateSummary = [];
        if (isset($period)) {
            foreach ($period as $d) {
                $dateSummary[$d->format('Y-m-d')] = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'bolos' => 0];
            }
        }

        foreach ($students as $st) {
            if (isset($period)) {
                foreach ($period as $d) {
                    $dStr = $d->format('Y-m-d');
                    $stStat = $attendanceData[$st->id][$dStr] ?? null;
                    if ($stStat && isset($grandSummary[$stStat])) {
                        $grandSummary[$stStat]++;
                        $dateSummary[$dStr][$stStat]++;
                    }
                }
            }
        }

        $totalExpectedSlots = $totalStudents * $totalMeetings;
        $attendanceRate = $totalExpectedSlots > 0 ? round(($grandSummary['hadir'] / $totalExpectedSlots) * 100, 1) : 0;
    @endphp

    <!-- Top Action Toolbar (Screen Only) -->
    <div class="no-print sticky top-0 z-50 bg-white border-b border-slate-200 px-4 py-3 shadow-sm">
        <div class="max-w-[29.7cm] mx-auto flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ isset($requestInputs) ? route('teacher.subject.attendance.preview', $requestInputs) : route('teacher.subject.attendance.report') }}" 
                   class="inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 mr-1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Kembali
                </a>
                <div>
                    <h1 class="text-sm font-bold text-slate-800 leading-tight">Pratinjau Cetak Rekap Kehadiran</h1>
                    <p class="text-[11px] text-slate-500">{{ $subjectInfo->name }} - {{ $classInfo->name }} ({{ $totalStudents }} Siswa • {{ $totalMeetings }} Pertemuan)</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button onclick="window.print()" 
                        class="inline-flex items-center px-5 py-2 rounded-lg text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 shadow-md shadow-sky-100 transition-all cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mr-1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                    </svg>
                    Cetak / Simpan PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Main Printable Sheet -->
    <div class="page-container">

        <!-- Kop Surat Resmi -->
        <div class="kop-wrapper">
            <div class="kop-logo">
                @if(isset($schoolIdentity['logo']) && $schoolIdentity['logo'])
                    <img src="{{ asset('storage/' . $schoolIdentity['logo']) }}" alt="Logo Sekolah" onerror="this.style.display='none'">
                @endif
            </div>
            <div class="kop-text">
                <h1>{{ $schoolIdentity['name'] ?? 'SMP NEGERI 1 BIAU' }}</h1>
                <p>{{ $schoolIdentity['address'] ?? 'Alamat Sekolah Belum Diset' }}</p>
                <p>
                    @if(!empty($schoolIdentity['phone'])) Telp: {{ $schoolIdentity['phone'] }} @endif
                    @if(!empty($schoolIdentity['email'])) | Email: {{ $schoolIdentity['email'] }} @endif
                </p>
            </div>
        </div>
        <div class="kop-divider"></div>

        <!-- Document Title -->
        <div style="text-align: center; margin-bottom: 8px;">
            <h2 style="font-size: 11pt; font-weight: 800; text-transform: uppercase; text-decoration: underline; margin: 0; color: #0f172a;">
                REKAPITULASI KEHADIRAN SISWA
            </h2>
            <p style="font-size: 8pt; font-weight: 600; color: #475569; margin: 2px 0 0 0;">
                Tahun Ajaran {{ date('Y') }}/{{ date('Y') + 1 }}
            </p>
        </div>

        <!-- Structured Metadata Table -->
        <table class="meta-table">
            <tr>
                <td class="meta-label">Mata Pelajaran</td>
                <td class="meta-val">: {{ $subjectInfo->name }}</td>
                <td class="meta-label" style="text-align: right; padding-right: 6px;">Kelas :</td>
                <td class="meta-val" style="width: 140px;">{{ $classInfo->name }}</td>
            </tr>
            <tr>
                <td class="meta-label">Guru Pengampu</td>
                <td class="meta-val">: {{ Auth::user()->name }}</td>
                <td class="meta-label" style="text-align: right; padding-right: 6px;">Periode :</td>
                <td class="meta-val">{{ $startDate->isoFormat('D MMM Y') }} s/d {{ $endDate->isoFormat('D MMM Y') }}</td>
            </tr>
        </table>

        <!-- Matrix Attendance Table -->
        <table class="report-table">
            <colgroup>
                <col style="width: 26px;">  <!-- No -->
                <col style="width: 78px;">  <!-- NIS -->
                <col style="width: 180px;"> <!-- Nama Siswa (Cukup lebar tanpa terpotong) -->
                @if(isset($period) && $totalMeetings > 0)
                    @foreach($period as $d)
                        <col style="width: 24px;"> <!-- Kolom Tanggal -->
                    @endforeach
                @else
                    <col style="width: 30px;">
                @endif
                <col style="width: 22px;"> <!-- H -->
                <col style="width: 22px;"> <!-- S -->
                <col style="width: 22px;"> <!-- I -->
                <col style="width: 22px;"> <!-- A -->
                <col style="width: 22px;"> <!-- B -->
                <col style="width: 42px;"> <!-- % Hadir -->
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">NIS</th>
                    <th rowspan="2" style="text-align: left; padding-left: 6px;">Nama Lengkap Siswa</th>
                    <th colspan="{{ $totalMeetings > 0 ? $totalMeetings : 1 }}">Tanggal Pertemuan</th>
                    <th colspan="5" style="background-color: #e2e8f0;">Rekapitulasi</th>
                    <th rowspan="2" style="background-color: #dcfce7; color: #166534;">% Hadir</th>
                </tr>
                <tr>
                    @if(isset($period) && $totalMeetings > 0)
                        @foreach($period as $date)
                            <th style="padding: 2px 1px;">
                                <div class="rotate-text">{{ $date->format('d/m') }}</div>
                            </th>
                        @endforeach
                    @else
                        <th>-</th>
                    @endif
                    <th class="st-H" title="Hadir">H</th>
                    <th class="st-S" title="Sakit">S</th>
                    <th class="st-I" title="Izin">I</th>
                    <th class="st-A" title="Alpa">A</th>
                    <th class="st-B" title="Bolos">B</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    @php
                        $studentSummary = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'bolos' => 0];
                    @endphp
                    <tr style="{{ $loop->iteration % 2 == 0 ? 'background-color: #f8fafc;' : 'background-color: #ffffff;' }}">
                        <td style="color: #64748b;">{{ $loop->iteration }}</td>
                        <td class="student-nis">{{ $student->nis ?? '-' }}</td>
                        <td class="student-name">{{ $student->name }}</td>
                        
                        @if(isset($period) && $totalMeetings > 0)
                            @foreach($period as $date)
                                @php
                                    $dateString = $date->format('Y-m-d');
                                    $status = $attendanceData[$student->id][$dateString] ?? '-';
                                    if (isset($studentSummary[$status])) {
                                        $studentSummary[$status]++;
                                    }
                                @endphp
                                <td class="{{ $status == 'hadir' ? 'st-H' : ($status == 'sakit' ? 'st-S' : ($status == 'izin' ? 'st-I' : ($status == 'alpa' ? 'st-A' : ($status == 'bolos' ? 'st-B' : 'st-none')))) }}">
                                    {{ $status !== '-' ? strtoupper(substr($status, 0, 1)) : '-' }}
                                </td>
                            @endforeach
                        @else
                            <td>-</td>
                        @endif

                        @php
                            $studentAttendanceRate = $totalMeetings > 0 ? round(($studentSummary['hadir'] / $totalMeetings) * 100) : 0;
                        @endphp

                        <td style="font-weight: 700; color: #166534; background-color: #f0fdf4;">{{ $studentSummary['hadir'] }}</td>
                        <td style="font-weight: 700; color: #854d0e; background-color: #fefce8;">{{ $studentSummary['sakit'] }}</td>
                        <td style="font-weight: 700; color: #075985; background-color: #f0f9ff;">{{ $studentSummary['izin'] }}</td>
                        <td style="font-weight: 700; color: #991b1b; background-color: #fef2f2;">{{ $studentSummary['alpa'] }}</td>
                        <td style="font-weight: 700; color: #9a3412; background-color: #fff7ed;">{{ $studentSummary['bolos'] }}</td>
                        <td style="font-weight: 800; font-size: 7.5pt; color: {{ $studentAttendanceRate >= 80 ? '#166534' : ($studentAttendanceRate >= 70 ? '#854d0e' : '#991b1b') }}; background-color: #f8fafc;">
                            {{ $studentAttendanceRate }}%
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 3 + ($totalMeetings > 0 ? $totalMeetings : 1) + 6 }}" style="padding: 16px; color: #94a3b8; font-style: italic;">
                            Tidak ada data siswa yang ditemukan untuk kelas ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <!-- Footer Total Row -->
            @if($students->count() > 0)
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: 800;">
                    <td colspan="3" style="text-align: right; padding-right: 8px;">Total Kehadiran Kelas :</td>
                    @if(isset($period) && $totalMeetings > 0)
                        @foreach($period as $date)
                            @php
                                $dStr = $date->format('Y-m-d');
                                $hCount = $dateSummary[$dStr]['hadir'] ?? 0;
                            @endphp
                            <td style="font-size: 7.5pt; color: #334155;">{{ $hCount }}</td>
                        @endforeach
                    @else
                        <td>-</td>
                    @endif
                    <td style="background-color: #dcfce7; color: #166534;">{{ $grandSummary['hadir'] }}</td>
                    <td style="background-color: #fef9c3; color: #854d0e;">{{ $grandSummary['sakit'] }}</td>
                    <td style="background-color: #e0f2fe; color: #075985;">{{ $grandSummary['izin'] }}</td>
                    <td style="background-color: #fee2e2; color: #991b1b;">{{ $grandSummary['alpa'] }}</td>
                    <td style="background-color: #ffedd5; color: #9a3412;">{{ $grandSummary['bolos'] }}</td>
                    <td style="background-color: #dcfce7; color: #166534; font-size: 7.5pt;">{{ $attendanceRate }}%</td>
                </tr>
            </tfoot>
            @endif
        </table>

        <!-- Legend & Signatures in 1 avoid-break block -->
        <div class="break-inside-avoid" style="margin-top: 10px;">
            <!-- Legend Status -->
            <div style="font-size: 7.5pt; color: #475569; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #cbd5e1; padding-bottom: 6px;">
                <div>
                    <strong>Keterangan:</strong> 
                    <span class="st-H" style="padding: 1px 4px; border-radius: 2px;">H: Hadir</span> &nbsp;
                    <span class="st-S" style="padding: 1px 4px; border-radius: 2px;">S: Sakit</span> &nbsp;
                    <span class="st-I" style="padding: 1px 4px; border-radius: 2px;">I: Izin</span> &nbsp;
                    <span class="st-A" style="padding: 1px 4px; border-radius: 2px;">A: Alpa</span> &nbsp;
                    <span class="st-B" style="padding: 1px 4px; border-radius: 2px;">B: Bolos</span> &nbsp;
                    <span style="color: #94a3b8;">- : Tidak Ada Data</span>
                </div>
                <div style="font-size: 7pt; color: #94a3b8;">
                    Dicetak melalui SIASEK • {{ now()->isoFormat('D MMMM YYYY, HH:mm') }}
                </div>
            </div>

            <!-- Formal Signatures -->
            <table class="signature-table">
                <tr>
                    <td>
                        <p style="margin: 0; color: #475569;">Mengetahui,</p>
                        <p style="margin: 2px 0 0 0; font-weight: 700; color: #0f172a;">Kepala Sekolah</p>
                        <div class="signature-space"></div>
                        <p class="signature-name" style="margin: 0;">{{ $schoolIdentity['headmaster_name'] ?? '......................................................' }}</p>
                        <p style="margin: 2px 0 0 0; font-size: 8pt; color: #475569;">NIP. {{ $schoolIdentity['headmaster_nip'] ?? '......................................................' }}</p>
                    </td>
                    <td>
                        <p style="margin: 0; color: #475569;">{{ $schoolIdentity['address'] ? (explode(',', $schoolIdentity['address'])[1] ?? 'Sekolah') : 'Sekolah' }}, {{ now()->isoFormat('D MMMM YYYY') }}</p>
                        <p style="margin: 2px 0 0 0; font-weight: 700; color: #0f172a;">Guru Mata Pelajaran,</p>
                        <div class="signature-space"></div>
                        <p class="signature-name" style="margin: 0;">{{ Auth::user()->name }}</p>
                        <p style="margin: 2px 0 0 0; font-size: 8pt; color: #475569;">NIP. {{ Auth::user()->teacher?->nip ?? '......................................................' }}</p>
                    </td>
                </tr>
            </table>
        </div>

    </div>

</body>
</html>
