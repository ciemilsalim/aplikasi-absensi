<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kehadiran Kelas {{ $class->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
            @page { size: A4 landscape; margin: 0.5cm; }
        }
        .table-cell {
            border: 1px solid #cbd5e1;
            padding: 0.2rem;
            text-align: center;
            font-size: 9px;
            white-space: nowrap;
        }
        .header-cell {
            font-weight: 600;
            background-color: #f1f5f9;
        }
        .summary-cell {
            font-weight: 600;
            background-color: #f8fafc;
        }
        .rotated-header {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
            padding: 0.5rem 0.2rem;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 bg-white" id="print-area">
        <div class="mb-4 text-right no-print">
            <button onclick="window.print()" class="px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700">
                Cetak Laporan
            </button>
        </div>

        <header class="text-center mb-4 border-b pb-2">
            <h1 class="text-xl font-bold text-gray-800">{{ $schoolIdentity['school_name'] ?? 'Nama Sekolah' }}</h1>
            <p class="text-xs text-gray-500">{{ $schoolIdentity['school_address'] ?? 'Alamat Sekolah' }}</p>
            <h2 class="text-lg font-semibold text-gray-700 mt-2">Laporan Kehadiran Siswa</h2>
        </header>

        <div class="mb-4 text-xs">
            <div class="grid grid-cols-2 gap-2">
                <div><strong>Kelas:</strong> {{ $class->name }}</div>
                <div><strong>Wali Kelas:</strong> {{ $class->homeroomTeacher->name ?? '-' }}</div>
                <div><strong>Periode:</strong> {{ $startDate->isoFormat('D MMMM YYYY') }} - {{ $endDate->isoFormat('D MMMM YYYY') }}</div>
                <div><strong>Tanggal Cetak:</strong> {{ now()->isoFormat('D MMMM YYYY') }}</div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead>
                    <tr>
                        <th class="table-cell header-cell" rowspan="2">No</th>
                        <th class="table-cell header-cell" rowspan="2" style="width: 200px;">Nama Siswa</th>
                        <th class="table-cell header-cell" colspan="{{ $period->count() }}">Bulan: {{ $startDate->isoFormat('MMMM YYYY') }}</th>
                        <th class="table-cell header-cell" colspan="5">Rekapitulasi</th>
                    </tr>
                    <tr>
                        @foreach ($period as $date)
                            <th class="table-cell header-cell">{{ $date->format('d') }}</th>
                        @endforeach
                        <th class="table-cell header-cell rotated-header">Hadir</th>
                        <th class="table-cell header-cell rotated-header">Sakit</th>
                        <th class="table-cell header-cell rotated-header">Izin</th>
                        <th class="table-cell header-cell rotated-header">Alpa</th>
                        <th class="table-cell header-cell rotated-header">Telat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $index => $student)
                        <tr>
                            <td class="table-cell">{{ $index + 1 }}</td>
                            <td class="table-cell text-left">{{ $student->name }}</td>
                            @foreach ($period as $date)
                                @php
                                    $dateString = $date->format('Y-m-d');
                                    $attendanceRecord = $dailyAttendances->get($student->id, collect())->get($dateString);
                                    $status = $attendanceRecord ? $attendanceRecord->status : null;
                                    $statusText = '';
                                    switch ($status) {
                                        case 'tepat_waktu': $statusText = 'H'; break;
                                        case 'terlambat': $statusText = 'T'; break;
                                        case 'izin': $statusText = 'I'; break;
                                        case 'sakit': $statusText = 'S'; break;
                                        case 'alpa': $statusText = 'A'; break;
                                    }
                                @endphp
                                <td class="table-cell">{{ $statusText }}</td>
                            @endforeach
                            {{-- Kolom Rekapitulasi --}}
                            <td class="table-cell summary-cell">{{ $attendanceSummary[$student->id]['H'] }}</td>
                            <td class="table-cell summary-cell">{{ $attendanceSummary[$student->id]['S'] }}</td>
                            <td class="table-cell summary-cell">{{ $attendanceSummary[$student->id]['I'] }}</td>
                            <td class="table-cell summary-cell">{{ $attendanceSummary[$student->id]['A'] }}</td>
                            <td class="table-cell summary-cell">{{ $attendanceSummary[$student->id]['T'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $period->count() + 7 }}" class="table-cell">Tidak ada data siswa di kelas ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 text-xs text-gray-600">
            <h4 class="font-semibold mb-1">Keterangan:</h4>
            <div class="flex space-x-4">
                <span>H: Hadir</span>
                <span>T: Terlambat</span>
                <span>S: Sakit</span>
                <span>I: Izin</span>
                <span>A: Alpa</span>
            </div>
        </div>
    </div>
</body>
</html>
