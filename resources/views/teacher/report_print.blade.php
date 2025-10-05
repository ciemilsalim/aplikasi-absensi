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
            margin: 0.8cm;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .container {
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
            .print-header {
                margin-bottom: 1rem;
            }
            .print-overflow-container {
                overflow: visible !important;
            }
            table {
                font-size: 8px;
                table-layout: fixed; /* Penting untuk layout yang konsisten */
                width: 100%;
            }
            th, td {
                padding: 2px 3px;
                overflow-wrap: break-word; /* Memecah teks jika terlalu panjang */
            }
            .print-hidden {
                display: none;
            }
            .print-block {
                display: block;
            }
            .kop-surat {
                border-bottom: 3px solid #000;
                padding-bottom: 1rem;
            }
        }

        .rotate-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            text-align: center;
        }
        .status-hadir { background-color: #d1fae5 !important; }
        .status-sakit { background-color: #fef3c7 !important; }
        .status-izin { background-color: #dbeafe !important; }
        .status-alpa { background-color: #fee2e2 !important; }
        .status-bolos { background-color: #fff2b2 !important; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 md:p-8 bg-white">
        
        <div class="print-hidden mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Pratinjau Cetak Rekap Kehadiran</h1>
            <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Cetak Halaman Ini
            </button>
        </div>

        <header class="print-header">
            <div class="kop-surat flex items-center gap-4">
                @if(isset($schoolIdentity['logo']) && $schoolIdentity['logo'])
                    {{-- Path logo harus tersimpan di database sebagai 'logos/namafile.png' --}}
                    <img src="{{ asset('storage/' . $schoolIdentity['logo']) }}" alt="Logo Sekolah" class="h-20" 
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    {{-- Fallback ini akan muncul jika path gambar di atas gagal dimuat --}}
                    <div class="h-20 w-20 bg-gray-200 items-center justify-center text-center p-1" style="display: none;">
                        <span class="text-xs text-gray-500">Logo Gagal Dimuat. Path: {{ 'storage/' . $schoolIdentity['logo'] }}</span>
                    </div>
                @else
                    <!-- Fallback jika tidak ada logo di database -->
                    <div class="h-20 w-20 bg-gray-200 flex items-center justify-center">
                        <span class="text-xs text-gray-500">Logo Tidak Ada</span>
                    </div>
                @endif
                <div class="text-center flex-grow">
                    <h1 class="text-xl font-bold uppercase">{{ $schoolIdentity['name'] ?? 'NAMA SEKOLAH' }}</h1>
                    <p class="text-sm">{{ $schoolIdentity['address'] ?? 'Alamat Sekolah, Kota, Kode Pos' }}</p>
                    <p class="text-sm">
                        @if(isset($schoolIdentity['phone']) && $schoolIdentity['phone']) Telp: {{ $schoolIdentity['phone'] }} @endif
                        @if(isset($schoolIdentity['email']) && $schoolIdentity['email']) | Email: {{ $schoolIdentity['email'] }} @endif
                    </p>
                </div>
            </div>
            <div class="text-center mt-4">
                <h2 class="text-lg font-bold uppercase underline">REKAPITULASI KEHADIRAN SISWA</h2>
                <p class="text-sm">Tahun Pelajaran: {{ date('Y') }}/{{ date('Y') + 1 }}</p>
            </div>
        </header>
        
        <div class="text-sm mb-4">
            <table class="w-auto">
                <tbody>
                    <tr>
                        <td class="font-semibold pr-4">Mata Pelajaran</td>
                        <td>: {{ $subjectInfo->name }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold pr-4">Kelas</td>
                        <td>: {{ $classInfo->name }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold pr-4">Periode</td>
                        <td>: {{ $startDate->isoFormat('D MMMM YYYY') }} s/d {{ $endDate->isoFormat('D MMMM YYYY') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>


        <div class="overflow-x-auto print-overflow-container">
            <table class="min-w-full border border-collapse border-gray-400">
                <colgroup>
                    <col style="width: 3%;">  <!-- No -->
                    <col style="width: 7%;">  <!-- NIS -->
                    <col style="width: 15%;"> <!-- Nama Siswa -->
                    @if(isset($period))
                        @foreach($period as $date)
                            <col> <!-- Kolom tanggal akan berbagi sisa ruang -->
                        @endforeach
                    @endif
                    <col style="width: 3%;"> <!-- H -->
                    <col style="width: 3%;"> <!-- S -->
                    <col style="width: 3%;"> <!-- I -->
                    <col style="width: 3%;"> <!-- A -->
                    <col style="width: 3%;"> <!-- B -->
                </colgroup>
                <thead class="bg-gray-200 text-center">
                    <tr>
                        <th rowspan="2" class="border border-gray-400 p-2">No</th>
                        <th rowspan="2" class="border border-gray-400 p-2">NIS</th>
                        <th rowspan="2" class="border border-gray-400 p-2 text-left">Nama Siswa</th>
                        <th colspan="{{ isset($period) ? iterator_count($period) : 0 }}" class="border border-gray-400 p-2">Tanggal</th>
                        <th colspan="5" class="border border-gray-400 p-2">Jumlah</th>
                    </tr>
                    <tr>
                        @if(isset($period))
                            @foreach($period as $date)
                                <th class="border border-gray-400 p-1">
                                    <div class="rotate-text">{{ $date->format('d/m') }}</div>
                                </th>
                            @endforeach
                        @endif
                        <th class="border border-gray-400 p-1 bg-green-400"><div class="rotate-text">Hadir</div></th>
                        <th class="border border-gray-400 p-1 bg-yellow-400"><div class="rotate-text">Sakit</div></th>
                        <th class="border border-gray-400 p-1 bg-blue-400"><div class="rotate-text">Izin</div></th>
                        <th class="border border-gray-400 p-1 bg-red-400"><div class="rotate-text">Alpa</div></th>
                        <th class="border border-gray-400 p-1 bg-orange-400"><div class="rotate-text">Bolos</div></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            $studentSummary = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'bolos' => 0];
                        @endphp
                        <tr class="text-center">
                            <td class="border border-gray-400">{{ $loop->iteration }}</td>
                            <td class="border border-gray-400">{{ $student->nis ?? '-' }}</td>
                            <td class="border border-gray-400 text-left p-2">{{ $student->name }}</td>
                            @if(isset($period))
                                @foreach($period as $date)
                                    @php
                                        $dateString = $date->format('Y-m-d');
                                        $status = $attendanceData[$student->id][$dateString] ?? '-';
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
                            @endif
                            <td class="border border-gray-400 font-bold">{{ $studentSummary['hadir'] }}</td>
                            <td class="border border-gray-400 font-bold">{{ $studentSummary['sakit'] }}</td>
                            <td class="border border-gray-400 font-bold">{{ $studentSummary['izin'] }}</td>
                            <td class="border border-gray-400 font-bold">{{ $studentSummary['alpa'] }}</td>
                            <td class="border border-gray-400 font-bold">{{ $studentSummary['bolos'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + (isset($period) ? iterator_count($period) : 0) + 5 }}" class="text-center p-4 border border-gray-400">Tidak ada data siswa di kelas ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-8 flex justify-end">
            <div class="text-center text-sm">
                <p>Guru Mata Pelajaran,</p>
                <br><br><br>
                <p class="font-bold underline">{{ Auth::user()->name }}</p>
                <p>NIP. ............................</p>
            </div>
        </div>

        <div class="mt-8 text-xs print-hidden">
            <p class="font-bold">Keterangan:</p>
            <ul class="list-disc list-inside">
                <li>H: Hadir</li>
                <li>S: Sakit</li>
                <li>I: Izin</li>
                <li>A: Alpa</li>
                <li>B: Bolos</li>
                <li>- : Tidak ada jadwal / data</li>
            </ul>
        </div>

        <div class="hidden print-block mt-12 text-xs text-gray-600">
            <div class="flex justify-between">
                <div>
                    Cetak Tanggal: {{ now()->isoFormat('D MMMM YYYY, HH:mm') }}
                </div>
                <div class="text-right">
                    Generate By SIASEK | dicetak oleh: {{ Auth::user()->name }}
                </div>
            </div>
        </div>

    </div>
</body>
</html>

