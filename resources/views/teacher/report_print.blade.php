<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Kehadiran {{ $subjectInfo->name }} - {{ $classInfo->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table {
                font-size: 10px;
            }
            th, td {
                padding: 4px 6px;
            }
        }
        .rotate-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            text-align: center;
        }
        .status-hadir { background-color: #d1fae5; }
        .status-sakit { background-color: #fef3c7; }
        .status-izin { background-color: #dbeafe; }
        .status-alpa { background-color: #fee2e2; }
        .status-bolos { background-color: #fef9c3; } /* Warna baru untuk bolos */
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 md:p-8 bg-white">
        
        <div class="print:hidden mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Pratinjau Cetak Rekap Kehadiran</h1>
            <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Cetak Halaman Ini
            </button>
        </div>

        <div class="text-center mb-6">
            <h2 class="text-xl font-bold uppercase">REKAPITULASI KEHADIRAN SISWA</h2>
            <h3 class="text-lg font-semibold">Mata Pelajaran: {{ $subjectInfo->name }}</h3>
            <h4 class="text-md">Kelas: {{ $classInfo->name }}</h4>
            <p class="text-sm">Periode: {{ $startDate->isoFormat('D MMMM Y') }} s/d {{ $endDate->isoFormat('D MMMM Y') }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border border-collapse border-gray-400">
                <thead class="bg-gray-200 text-center">
                    <tr>
                        <th rowspan="2" class="border border-gray-400 p-2">No</th>
                        <th rowspan="2" class="border border-gray-400 p-2">NIS</th>
                        <th rowspan="2" class="border border-gray-400 p-2 min-w-[200px] text-left">Nama Siswa</th>
                        <th colspan="{{ count($dates) }}" class="border border-gray-400 p-2">Tanggal</th>
                        {{-- PERUBAHAN: Colspan menjadi 5 --}}
                        <th colspan="5" class="border border-gray-400 p-2">Jumlah</th>
                    </tr>
                    <tr>
                        @foreach($dates as $date)
                            <th class="border border-gray-400 p-1">
                                <div class="rotate-text">{{ \Carbon\Carbon::parse($date)->format('d/m') }}</div>
                            </th>
                        @endforeach
                        <th class="border border-gray-400 p-1 bg-green-200"><div class="rotate-text">Hadir</div></th>
                        <th class="border border-gray-400 p-1 bg-yellow-200"><div class="rotate-text">Sakit</div></th>
                        <th class="border border-gray-400 p-1 bg-blue-200"><div class="rotate-text">Izin</div></th>
                        <th class="border border-gray-400 p-1 bg-red-200"><div class="rotate-text">Alpa</div></th>
                        {{-- PENAMBAHAN: Kolom header Bolos --}}
                        <th class="border border-gray-400 p-1 bg-yellow-300"><div class="rotate-text">Bolos</div></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            // PERUBAHAN: Inisialisasi summary dengan 'bolos'
                            $studentSummary = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'bolos' => 0];
                        @endphp
                        <tr class="text-center">
                            <td class="border border-gray-400">{{ $loop->iteration }}</td>
                            <td class="border border-gray-400">{{ $student->nis }}</td>
                            <td class="border border-gray-400 text-left p-2">{{ $student->name }}</td>
                            @foreach($dates as $date)
                                @php
                                    $status = $attendanceData[$student->id][$date] ?? '-';
                                    // PERUBAHAN: Logika penghitungan disederhanakan
                                    if (isset($studentSummary[$status])) {
                                        $studentSummary[$status]++;
                                    }
                                @endphp
                                <td class="border border-gray-400 font-semibold 
                                    @if($status == 'hadir') status-hadir @endif
                                    @if($status == 'sakit') status-sakit @endif
                                    @if($status == 'izin') status-izin @endif
                                    @if($status == 'alpa') status-alpa @endif
                                    @if($status == 'bolos') status-bolos @endif
                                ">
                                    {{ strtoupper(substr($status, 0, 1)) }}
                                </td>
                            @endforeach
                            <td class="border border-gray-400 font-bold">{{ $studentSummary['hadir'] }}</td>
                            <td class="border border-gray-400 font-bold">{{ $studentSummary['sakit'] }}</td>
                            <td class="border border-gray-400 font-bold">{{ $studentSummary['izin'] }}</td>
                            {{-- PERUBAHAN: Hanya menampilkan jumlah alpa --}}
                            <td class="border border-gray-400 font-bold">{{ $studentSummary['alpa'] }}</td>
                            {{-- PENAMBAHAN: Kolom data Bolos --}}
                            <td class="border border-gray-400 font-bold">{{ $studentSummary['bolos'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            {{-- PERUBAHAN: Colspan disesuaikan --}}
                            <td colspan="{{ 3 + count($dates) + 5 }}" class="text-center p-4 border border-gray-400">Tidak ada data siswa di kelas ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-8 text-xs print:hidden">
            <p class="font-bold">Keterangan:</p>
            <ul class="list-disc list-inside">
                <li>H: Hadir</li>
                <li>S: Sakit</li>
                <li>I: Izin</li>
                {{-- PERUBAHAN: Memisahkan Alpa dan Bolos --}}
                <li>A: Alpa</li>
                <li>B: Bolos</li>
                <li>- : Tidak ada jadwal / data</li>
            </ul>
        </div>

        <div class="hidden print:block mt-12 text-xs text-gray-600">
            <div class="flex justify-between">
                <div>
                    Cetak Tanggal: {{ now()->isoFormat('D MMMM YYYY, HH:mm') }}
                </div>
                <div class="text-right">
                    Generate By SIASEK | diCetak oleh: {{ Auth::user()->name }}
                </div>
            </div>
        </div>

    </div>
</body>
</html>
