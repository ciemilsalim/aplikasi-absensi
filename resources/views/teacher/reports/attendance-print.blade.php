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
        }
        .table-cell {
            border: 1px solid #e2e8f0;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.75rem;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-8 bg-white" id="print-area">
        <div class="mb-6 text-right no-print">
            <button onclick="window.print()" class="px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700">
                Cetak Laporan
            </button>
        </div>

        <header class="text-center mb-8 border-b pb-4">
            {{-- PERBAIKAN: Menggunakan nama kunci yang benar dari database --}}
            <h1 class="text-2xl font-bold text-gray-800">{{ $schoolIdentity['school_name'] ?? 'Nama Sekolah Tidak Ditemukan' }}</h1>
            <p class="text-sm text-gray-500">{{ $schoolIdentity['school_address'] ?? 'Alamat Sekolah Tidak Ditemukan' }}</p>
            <h2 class="text-xl font-semibold text-gray-700 mt-4">Laporan Kehadiran Siswa</h2>
        </header>

        <div class="mb-6 text-sm">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <strong class="text-gray-600">Kelas:</strong>
                    <span class="text-gray-800">{{ $class->name }}</span>
                </div>
                <div>
                    <strong class="text-gray-600">Wali Kelas:</strong>
                    <span class="text-gray-800">{{ $class->homeroomTeacher->name ?? '-' }}</span>
                </div>
                <div>
                    <strong class="text-gray-600">Periode:</strong>
                    <span class="text-gray-800">{{ $startDate->isoFormat('D MMMM YYYY') }} - {{ $endDate->isoFormat('D MMMM YYYY') }}</span>
                </div>
                <div>
                    <strong class="text-gray-600">Tanggal Cetak:</strong>
                    <span class="text-gray-800">{{ now()->isoFormat('D MMMM YYYY') }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead>
                    <tr>
                        <th class="table-cell bg-gray-100 font-semibold">Nama Siswa</th>
                        @foreach ($period as $date)
                            <th class="table-cell bg-gray-100 font-semibold">{{ $date->format('d/m') }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td class="table-cell text-left">{{ $student->name }}</td>
                            @foreach ($period as $date)
                                @php
                                    $dateString = $date->format('Y-m-d');
                                    $attendanceRecord = $attendances->get($student->id, collect())->get($dateString);
                                    $status = $attendanceRecord ? $attendanceRecord->status : null;
                                    $statusText = '-';
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $period->count() + 1 }}" class="table-cell">Tidak ada data siswa di kelas ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6 text-xs text-gray-600">
            <h4 class="font-semibold mb-2">Keterangan:</h4>
            <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
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
