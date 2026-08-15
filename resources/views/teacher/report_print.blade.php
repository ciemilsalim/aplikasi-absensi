<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Kehadiran {{ $subjectInfo->name }} - {{ $classInfo->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 0.8cm;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background-color: white !important;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                background: white !important;
            }
            .print-overflow-container {
                overflow: visible !important;
            }
            table {
                font-size: 8.5px !important;
                table-layout: fixed;
                width: 100%;
            }
            th, td {
                padding: 3px 2px !important;
            }
            .kop-double-line {
                border-bottom: 3px double #000000 !important;
            }
        }

        .rotate-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            text-align: center;
        }

        .status-hadir { background-color: #dcfce7 !important; color: #15803d !important; }
        .status-sakit { background-color: #fef9c3 !important; color: #a16207 !important; }
        .status-izin { background-color: #e0f2fe !important; color: #0369a1 !important; }
        .status-alpa { background-color: #fee2e2 !important; color: #b91c1c !important; }
        .status-bolos { background-color: #ffedd5 !important; color: #c2410c !important; }

        @media screen {
            .sheet-preview {
                max-width: 29.7cm;
                min-height: 21cm;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased print:bg-white print:text-black">

    @php
        $totalMeetings = isset($period) ? iterator_count($period) : 0;
        $totalStudents = $students->count();
        $grandSummary = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'bolos' => 0];
        $dateSummary = [];
        if (isset($period)) {
            foreach ($period as $d) {
                $dateSummary[$d->format('Y-m-d')] = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'bolos' => 0, 'total' => 0];
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
                        $dateSummary[$dStr]['total']++;
                    }
                }
            }
        }

        $totalExpectedSlots = $totalStudents * $totalMeetings;
        $attendanceRate = $totalExpectedSlots > 0 ? round(($grandSummary['hadir'] / $totalExpectedSlots) * 100, 1) : 0;
    @endphp

    <!-- Top Action Bar (Hanya tampil di layar) -->
    <div class="no-print sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm px-4 py-3 sm:px-8">
        <div class="max-w-[29.7cm] mx-auto flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-sky-50 text-sky-600 rounded-xl border border-sky-100">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-800 leading-tight">Pratinjau Cetak Rekap Kehadiran</h1>
                    <p class="text-xs text-slate-500 font-medium">Format Dokumen Resmi A4 Landscape • {{ $subjectInfo->name }} ({{ $classInfo->name }})</p>
                </div>
            </div>

            <!-- Quick Metrics -->
            <div class="hidden lg:flex items-center gap-3">
                <div class="bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg text-center">
                    <p class="text-[10px] text-slate-500 font-semibold uppercase">Total Siswa</p>
                    <p class="text-sm font-bold text-slate-800">{{ $totalStudents }}</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg text-center">
                    <p class="text-[10px] text-slate-500 font-semibold uppercase">Pertemuan</p>
                    <p class="text-sm font-bold text-slate-800">{{ $totalMeetings }} Kali</p>
                </div>
                <div class="bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-lg text-center">
                    <p class="text-[10px] text-emerald-600 font-semibold uppercase">% Hadir Rata-rata</p>
                    <p class="text-sm font-bold text-emerald-700">{{ $attendanceRate }}%</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2.5 w-full md:w-auto">
                <a href="{{ isset($requestInputs) ? route('teacher.subject.attendance.preview', $requestInputs) : route('teacher.subject.attendance.report') }}" 
                   class="flex-1 md:flex-initial inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mr-1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Kembali
                </a>
                <button onclick="window.print()" 
                        class="flex-1 md:flex-initial inline-flex items-center justify-center px-5 py-2 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 shadow-md shadow-sky-200 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mr-1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                    </svg>
                    Cetak Rekap / PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Main Sheet Container (Simulasi Kertas A4 Landscape) -->
    <div class="sheet-preview print-container mx-auto my-6 bg-white p-8 sm:p-12 shadow-2xl rounded-2xl border border-slate-200 print:shadow-none print:border-none print:m-0 print:p-0">
        
        <!-- Kop Surat Resmi -->
        <header class="mb-5">
            <div class="flex items-center gap-5 pb-3">
                @if(isset($schoolIdentity['logo']) && $schoolIdentity['logo'])
                    <img src="{{ asset('storage/' . $schoolIdentity['logo']) }}" alt="Logo Sekolah" class="h-20 w-20 object-contain flex-shrink-0" onerror="this.style.display='none'">
                @else
                    <div class="h-20 w-20 bg-slate-100 border-2 border-dashed border-slate-300 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-[10px] font-bold text-slate-400">LOGO</span>
                    </div>
                @endif
                <div class="text-center flex-grow">
                    <h1 class="text-xl font-extrabold uppercase tracking-tight text-slate-900">{{ $schoolIdentity['name'] ?? 'SMP NEGERI 1 BIAU' }}</h1>
                    <p class="text-xs text-slate-600 mt-0.5">{{ $schoolIdentity['address'] ?? 'Alamat Sekolah Belum Diset' }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        @if(!empty($schoolIdentity['phone'])) Telp: {{ $schoolIdentity['phone'] }} @endif
                        @if(!empty($schoolIdentity['email'])) | Email: {{ $schoolIdentity['email'] }} @endif
                    </p>
                </div>
            </div>
            {{-- Garis Ganda Standar Surat Resmi Kedinasan --}}
            <div class="w-full border-b-2 border-black"></div>
            <div class="w-full border-b border-black mt-0.5"></div>
        </header>

        <!-- Judul Laporan & Metadata Dokumen -->
        <div class="mb-4 text-center">
            <h2 class="text-base font-bold uppercase tracking-wider text-slate-900 underline">REKAPITULASI KEHADIRAN SISWA</h2>
            <p class="text-xs font-semibold text-slate-600 mt-0.5">Tahun Ajaran {{ date('Y') }}/{{ date('Y') + 1 }}</p>
        </div>

        <div class="mb-4 text-xs">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 bg-slate-50 p-3 rounded-lg border border-slate-200 print:bg-transparent print:border-none print:p-0">
                <div>
                    <span class="font-semibold text-slate-500">Mata Pelajaran:</span>
                    <p class="font-bold text-slate-800">{{ $subjectInfo->name }}</p>
                </div>
                <div>
                    <span class="font-semibold text-slate-500">Kelas:</span>
                    <p class="font-bold text-slate-800">{{ $classInfo->name }}</p>
                </div>
                <div>
                    <span class="font-semibold text-slate-500">Guru Pengampu:</span>
                    <p class="font-bold text-slate-800">{{ Auth::user()->name }}</p>
                </div>
                <div>
                    <span class="font-semibold text-slate-500">Periode:</span>
                    <p class="font-bold text-slate-800">{{ $startDate->isoFormat('D MMM Y') }} s/d {{ $endDate->isoFormat('D MMM Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Tabel Matrix Presensi -->
        <div class="overflow-x-auto print-overflow-container mb-4">
            <table class="min-w-full border-collapse border border-slate-400 text-center text-xs">
                <thead>
                    <tr class="bg-slate-100 text-slate-800 font-bold">
                        <th rowspan="2" class="border border-slate-400 px-2 py-1.5 w-8">No</th>
                        <th rowspan="2" class="border border-slate-400 px-2 py-1.5 w-20">NIS</th>
                        <th rowspan="2" class="border border-slate-400 px-3 py-1.5 text-left min-w-[160px]">Nama Siswa</th>
                        <th colspan="{{ $totalMeetings > 0 ? $totalMeetings : 1 }}" class="border border-slate-400 px-2 py-1">Tanggal Pertemuan</th>
                        <th colspan="5" class="border border-slate-400 px-2 py-1 bg-slate-200">Rekapitulasi</th>
                        <th rowspan="2" class="border border-slate-400 px-2 py-1.5 w-12 bg-emerald-50 text-emerald-800">% Hadir</th>
                    </tr>
                    <tr class="bg-slate-50 text-[11px]">
                        @if(isset($period) && $totalMeetings > 0)
                            @foreach($period as $date)
                                <th class="border border-slate-400 p-1 w-7 font-medium">
                                    <div class="rotate-text text-[10px]">{{ $date->format('d/m') }}</div>
                                </th>
                            @endforeach
                        @else
                            <th class="border border-slate-400 p-1">-</th>
                        @endif
                        <th class="border border-slate-400 p-1 w-7 bg-green-100 text-green-800 font-bold" title="Hadir">H</th>
                        <th class="border border-slate-400 p-1 w-7 bg-yellow-100 text-yellow-800 font-bold" title="Sakit">S</th>
                        <th class="border border-slate-400 p-1 w-7 bg-blue-100 text-blue-800 font-bold" title="Izin">I</th>
                        <th class="border border-slate-400 p-1 w-7 bg-red-100 text-red-800 font-bold" title="Alpa">A</th>
                        <th class="border border-slate-400 p-1 w-7 bg-orange-100 text-orange-800 font-bold" title="Bolos">B</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            $studentSummary = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'bolos' => 0];
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors {{ $loop->iteration % 2 == 0 ? 'bg-slate-50/40' : 'bg-white' }}">
                            <td class="border border-slate-400 px-1 py-1 text-slate-500 font-medium">{{ $loop->iteration }}</td>
                            <td class="border border-slate-400 px-2 py-1 text-slate-600 font-mono text-[11px]">{{ $student->nis ?? '-' }}</td>
                            <td class="border border-slate-400 px-3 py-1 text-left font-semibold text-slate-800">{{ $student->name }}</td>
                            
                            @if(isset($period) && $totalMeetings > 0)
                                @foreach($period as $date)
                                    @php
                                        $dateString = $date->format('Y-m-d');
                                        $status = $attendanceData[$student->id][$dateString] ?? '-';
                                        if (isset($studentSummary[$status])) {
                                            $studentSummary[$status]++;
                                        }
                                    @endphp
                                    <td class="border border-slate-400 font-bold text-[11px]
                                        @if($status == 'hadir') status-hadir @endif
                                        @if($status == 'sakit') status-sakit @endif
                                        @if($status == 'izin') status-izin @endif
                                        @if($status == 'alpa') status-alpa @endif
                                        @if($status == 'bolos') status-bolos @endif
                                    ">
                                        {{ $status !== '-' ? strtoupper(substr($status, 0, 1)) : '-' }}
                                    </td>
                                @endforeach
                            @else
                                <td class="border border-slate-400">-</td>
                            @endif

                            @php
                                $studentAttendanceRate = $totalMeetings > 0 ? round(($studentSummary['hadir'] / $totalMeetings) * 100) : 0;
                            @endphp

                            <td class="border border-slate-400 font-bold text-green-700 bg-green-50/50">{{ $studentSummary['hadir'] }}</td>
                            <td class="border border-slate-400 font-bold text-yellow-700 bg-yellow-50/50">{{ $studentSummary['sakit'] }}</td>
                            <td class="border border-slate-400 font-bold text-blue-700 bg-blue-50/50">{{ $studentSummary['izin'] }}</td>
                            <td class="border border-slate-400 font-bold text-red-700 bg-red-50/50">{{ $studentSummary['alpa'] }}</td>
                            <td class="border border-slate-400 font-bold text-orange-700 bg-orange-50/50">{{ $studentSummary['bolos'] }}</td>
                            <td class="border border-slate-400 font-extrabold text-[11px] {{ $studentAttendanceRate >= 85 ? 'text-emerald-700 bg-emerald-50/70' : ($studentAttendanceRate >= 75 ? 'text-amber-700 bg-amber-50/70' : 'text-rose-700 bg-rose-50/70') }}">
                                {{ $studentAttendanceRate }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 4 + ($totalMeetings > 0 ? $totalMeetings : 1) + 5 }}" class="text-center p-6 border border-slate-400 text-slate-500 font-medium">
                                Tidak ada data siswa yang ditemukan untuk kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <!-- Footer Baris Total Rekap Kelas -->
                @if($students->count() > 0)
                <tfoot class="bg-slate-100 font-bold text-[11px] text-slate-800">
                    <tr>
                        <td colspan="3" class="border border-slate-400 px-3 py-1.5 text-right font-extrabold">Total Kehadiran Kelas :</td>
                        @if(isset($period) && $totalMeetings > 0)
                            @foreach($period as $date)
                                @php
                                    $dStr = $date->format('Y-m-d');
                                    $hCount = $dateSummary[$dStr]['hadir'] ?? 0;
                                @endphp
                                <td class="border border-slate-400 p-1 text-[10px] text-slate-700 font-bold">{{ $hCount }}</td>
                            @endforeach
                        @else
                            <td class="border border-slate-400">-</td>
                        @endif
                        <td class="border border-slate-400 p-1 text-green-800 bg-green-100">{{ $grandSummary['hadir'] }}</td>
                        <td class="border border-slate-400 p-1 text-yellow-800 bg-yellow-100">{{ $grandSummary['sakit'] }}</td>
                        <td class="border border-slate-400 p-1 text-blue-800 bg-blue-100">{{ $grandSummary['izin'] }}</td>
                        <td class="border border-slate-400 p-1 text-red-800 bg-red-100">{{ $grandSummary['alpa'] }}</td>
                        <td class="border border-slate-400 p-1 text-orange-800 bg-orange-100">{{ $grandSummary['bolos'] }}</td>
                        <td class="border border-slate-400 p-1 bg-emerald-100 text-emerald-900 font-extrabold">{{ $attendanceRate }}%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- Legend & Catatan -->
        <div class="flex flex-col sm:flex-row justify-between items-start text-[11px] text-slate-600 gap-4 mb-8">
            <div class="flex items-center flex-wrap gap-x-4 gap-y-1">
                <span class="font-bold text-slate-700">Keterangan:</span>
                <span class="inline-flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-green-200 inline-block font-bold text-[10px] text-center text-green-800 leading-none">H</span> Hadir</span>
                <span class="inline-flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-yellow-200 inline-block font-bold text-[10px] text-center text-yellow-800 leading-none">S</span> Sakit</span>
                <span class="inline-flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-blue-200 inline-block font-bold text-[10px] text-center text-blue-800 leading-none">I</span> Izin</span>
                <span class="inline-flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-red-200 inline-block font-bold text-[10px] text-center text-red-800 leading-none">A</span> Alpa</span>
                <span class="inline-flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-orange-200 inline-block font-bold text-[10px] text-center text-orange-800 leading-none">B</span> Bolos</span>
            </div>
            <div class="text-right text-slate-400 text-[10px]">
                Dokumen Rekapitulasi Presensi Mata Pelajaran
            </div>
        </div>

        <!-- Kolom Pengesahan Tanda Tangan Formal Dual-Column -->
        <div class="grid grid-cols-2 gap-8 text-xs text-slate-800 mt-6">
            <div class="text-center">
                <p class="font-medium text-slate-600">Mengetahui,</p>
                <p class="font-semibold text-slate-800">Kepala Sekolah</p>
                <div class="h-20"></div>
                <p class="font-bold text-slate-900 underline uppercase">{{ $schoolIdentity['headmaster_name'] ?? '......................................................' }}</p>
                <p class="text-slate-600 mt-0.5">NIP. {{ $schoolIdentity['headmaster_nip'] ?? '......................................................' }}</p>
            </div>
            <div class="text-center">
                <p class="text-slate-600">{{ $schoolIdentity['address'] ? (explode(',', $schoolIdentity['address'])[1] ?? 'Sekolah') : 'Sekolah' }}, {{ now()->isoFormat('D MMMM YYYY') }}</p>
                <p class="font-semibold text-slate-800">Guru Mata Pelajaran,</p>
                <div class="h-20"></div>
                <p class="font-bold text-slate-900 underline uppercase">{{ Auth::user()->name }}</p>
                <p class="text-slate-600 mt-0.5">NIP. {{ Auth::user()->teacher?->nip ?? '......................................................' }}</p>
            </div>
        </div>

        <!-- Watermark Footer Cetak -->
        <div class="mt-8 pt-3 border-t border-slate-200 text-[10px] text-slate-400 flex justify-between items-center">
            <span>Dicetak secara otomatis melalui Sistem SIASEK pada {{ now()->isoFormat('dddd, D MMMM YYYY • HH:mm') }} WITA</span>
            <span>Halaman 1 dari 1</span>
        </div>

    </div>

</body>
</html>
