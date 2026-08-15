<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi Ekskul {{ $extracurricular->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
            color: #0f172a;
        }
    </style>
</head>
<body>

    @php
        $totalMeetings = count($dates);
        $totalStudents = $students->count();
        $grandSummary = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0];
        $dateSummary = [];
        foreach ($dates as $d) {
            $dateSummary[$d] = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0];
        }

        foreach ($students as $st) {
            foreach ($dates as $d) {
                $stStat = $attendanceData[$st->id][$d] ?? null;
                if ($stStat && isset($grandSummary[$stStat])) {
                    $grandSummary[$stStat]++;
                    $dateSummary[$d][$stStat]++;
                }
            }
        }

        $totalPossibleAttendances = $totalStudents * $totalMeetings;
        $attendanceRate = $totalPossibleAttendances > 0 
            ? round(($grandSummary['hadir'] / $totalPossibleAttendances) * 100, 1) 
            : 0;
    @endphp

    <!-- Floating Top Action Bar (Screen Only) -->
    <div class="no-print sticky top-0 z-50 bg-slate-900 text-white shadow-xl border-b border-slate-700">
        <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('teacher.extracurricular-attendance.index') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-600 transition-colors">
                    <span class="material-icons text-sm">arrow_back</span>
                    Kembali
                </a>
                <div>
                    <h1 class="text-sm font-bold text-white flex items-center gap-2">
                        Pratinjau Cetak Rekap Absensi Ekstrakurikuler
                    </h1>
                    <p class="text-xs text-slate-400">Pastikan pengaturan ukuran kertas pada dialog cetak adalah <strong>A4 Landscape</strong>.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <button onclick="window.print()" 
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold shadow-lg shadow-sky-600/30 transition-all cursor-pointer">
                    <span class="material-icons text-base">print</span>
                    Cetak / Simpan PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Printable Paper Sheet -->
    <div class="page-container">
        
        <!-- Official Kop Surat -->
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
                REKAPITULASI KEHADIRAN EKSTRAKURIKULER
            </h2>
            <p style="font-size: 8pt; font-weight: 600; color: #475569; margin: 2px 0 0 0;">
                Tahun Ajaran {{ date('Y') }}/{{ date('Y') + 1 }}
            </p>
        </div>

        <!-- Structured Metadata Table -->
        <table class="meta-table">
            <colgroup>
                <col style="width: 140px;">
                <col style="width: auto;">
                <col style="width: 80px;">
                <col style="width: 260px;">
            </colgroup>
            <tr>
                <td class="meta-label">Nama Kegiatan</td>
                <td class="meta-val">: {{ $extracurricular->name }}</td>
                <td class="meta-label" style="text-align: right; padding-right: 8px;">Total Anggota :</td>
                <td class="meta-val" style="white-space: nowrap;">{{ $totalStudents }} Siswa</td>
            </tr>
            <tr>
                <td class="meta-label">Pembina Ekstrakurikuler</td>
                <td class="meta-val">: {{ $teacher->name }}</td>
                <td class="meta-label" style="text-align: right; padding-right: 8px;">Periode :</td>
                <td class="meta-val" style="white-space: nowrap;">{{ $startDate->isoFormat('D MMM Y') }} s/d {{ $endDate->isoFormat('D MMM Y') }}</td>
            </tr>
        </table>

        <!-- Main Attendance Matrix Table -->
        <table class="report-table">
            <colgroup>
                <col style="width: 26px;">  <!-- No -->
                <col style="width: 78px;">  <!-- NIS -->
                <col style="width: 180px;"> <!-- Nama Siswa -->
                <col style="width: 55px;">  <!-- Kelas -->
                @if($totalMeetings > 0)
                    @foreach($dates as $date)
                        <col style="width: 24px;">
                    @endforeach
                @else
                    <col style="width: 60px;">
                @endif
                <col style="width: 22px;"> <!-- H -->
                <col style="width: 22px;"> <!-- S -->
                <col style="width: 22px;"> <!-- I -->
                <col style="width: 22px;"> <!-- A -->
                <col style="width: 42px;"> <!-- % Hadir -->
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">NIS</th>
                    <th rowspan="2" style="text-align: left; padding-left: 6px;">Nama Lengkap Siswa</th>
                    <th rowspan="2">Kelas</th>
                    <th colspan="{{ $totalMeetings > 0 ? $totalMeetings : 1 }}" style="background-color: #e2e8f0;">
                        Pertemuan Tanggal Kegiatan
                    </th>
                    <th colspan="4" style="background-color: #e2e8f0;">Rekapitulasi</th>
                    <th rowspan="2" style="background-color: #dcfce7; color: #166534; font-size: 7.5pt;">% Hadir</th>
                </tr>
                <tr>
                    @if($totalMeetings > 0)
                        @foreach($dates as $date)
                            <th style="padding: 2px 0;">
                                <div class="rotate-text">{{ \Carbon\Carbon::parse($date)->format('d/m') }}</div>
                            </th>
                        @endforeach
                    @else
                        <th>-</th>
                    @endif
                    <th style="background-color: #dcfce7; color: #166534;">H</th>
                    <th style="background-color: #fef9c3; color: #854d0e;">S</th>
                    <th style="background-color: #e0f2fe; color: #075985;">I</th>
                    <th style="background-color: #fee2e2; color: #991b1b;">A</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    @php
                        $studentSummary = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0];
                    @endphp
                    <tr>
                        <td style="color: #64748b;">{{ $loop->iteration }}</td>
                        <td class="student-nis">{{ $student->nis ?? '-' }}</td>
                        <td class="student-name" title="{{ $student->name }}">{{ $student->name }}</td>
                        <td style="font-size: 7.5pt; color: #334155;">{{ $student->schoolClass->name ?? '-' }}</td>
                        
                        @if($totalMeetings > 0)
                            @foreach($dates as $date)
                                @php
                                    $status = $attendanceData[$student->id][$date] ?? '-';
                                    if (isset($studentSummary[$status])) {
                                        $studentSummary[$status]++;
                                    }
                                @endphp
                                <td class="{{ $status == 'hadir' ? 'st-H' : ($status == 'sakit' ? 'st-S' : ($status == 'izin' ? 'st-I' : ($status == 'alpa' ? 'st-A' : 'st-none'))) }}">
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
                        <td style="font-weight: 800; font-size: 7.5pt; color: {{ $studentAttendanceRate >= 80 ? '#166534' : ($studentAttendanceRate >= 70 ? '#854d0e' : '#991b1b') }}; background-color: #f8fafc;">
                            {{ $studentAttendanceRate }}%
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 4 + ($totalMeetings > 0 ? $totalMeetings : 1) + 5 }}" style="padding: 16px; color: #94a3b8; font-style: italic;">
                            Tidak ada data anggota yang terdaftar pada kegiatan ekstrakurikuler ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <!-- Footer Total Row -->
            @if($students->count() > 0)
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: 800;">
                    <td colspan="4" style="text-align: right; padding-right: 8px;">Total Kehadiran :</td>
                    @if($totalMeetings > 0)
                        @foreach($dates as $date)
                            @php
                                $hCount = $dateSummary[$date]['hadir'] ?? 0;
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
                    <span style="color: #94a3b8;">- : Tidak Ada Data</span>
                </div>
                <div style="font-size: 7pt; color: #94a3b8;">
                    Dicetak melalui SIASEK • {{ now()->isoFormat('D MMMM YYYY, HH:mm') }}
                </div>
            </div>

            <!-- Formal Dual Signatures -->
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
                        <p style="margin: 0; color: #475569;">Buol, {{ now()->isoFormat('D MMMM YYYY') }}</p>
                        <p style="margin: 2px 0 0 0; font-weight: 700; color: #0f172a;">Pembina Ekstrakurikuler,</p>
                        <div class="signature-space"></div>
                        <p class="signature-name" style="margin: 0;">{{ $teacher->name }}</p>
                        <p style="margin: 2px 0 0 0; font-size: 8pt; color: #475569;">NIP. {{ $teacher->nip ?? '......................................................' }}</p>
                    </td>
                </tr>
            </table>
        </div>

    </div>

</body>
</html>
