<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi Ekskul {{ $extracurricular->name }}</title>
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
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                background: white !important;
            }
            .print-header {
                margin-bottom: 1rem;
            }
            .print-overflow-container {
                overflow: visible !important;
            }
            table {
                font-size: 9px;
                table-layout: fixed;
                width: 100%;
            }
            th, td {
                padding: 2px 3px;
                overflow-wrap: break-word;
            }
            .print-hidden {
                display: none !important;
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

        /* Simulasi Landscape di Web */
        @media screen {
            .container {
                max-width: 29.7cm !important; /* Lebar A4 Landscape */
                min-height: 21cm;
            }
        }
    </style>
</head>
<body class="bg-slate-200 font-sans print:bg-white">
    <div class="container mx-auto p-4 md:p-12 bg-white print:p-0 print:shadow-none">
        
        <div class="print-hidden mb-6 flex justify-between items-center bg-slate-50 p-4 rounded-xl border border-slate-200">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Pratinjau Cetak Rekap Absensi</h1>
                <p class="text-xs text-slate-500">Gunakan tombol di samping untuk mencetak atau simpan sebagai PDF.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('teacher.extracurricular-attendance.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-2 px-6 rounded-lg transition-all">
                    Kembali
                </a>
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-lg shadow-blue-100 transition-all flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.844 2.4 12c1.914-1.12 4.145-1.76 6.523-1.76 1.157 0 2.27.153 3.327.437M19.8 19.8l-4.184-4.183a1.14 1.14 0 0 1-.778-.332 48.294 48.294 0 0 0-5.83-.498c-1.585-.233-2.708-1.626-2.708-3.228V6.741c0-1.602 1.123-2.995 2.707-3.228A48.397 48.397 0 0 0 12 3c2.392 0 4.744.175 7.043.513C20.627 3.746 21.75 5.14 21.75 6.741v6.018a3.228 3.228 0 0 1-2.707 3.228 48.3 48.3 0 0 0-4.184 1.14" />
                    </svg>
                    Cetak Rekap
                </button>
            </div>
        </div>

        <header class="print-header">
            <div class="kop-surat flex items-center gap-6">
                @if(isset($schoolIdentity['logo']) && $schoolIdentity['logo'])
                    <img src="{{ asset('storage/' . $schoolIdentity['logo']) }}" alt="Logo Sekolah" class="h-24">
                @else
                    <div class="h-24 w-24 bg-gray-100 flex items-center justify-center border-2 border-dashed border-gray-300">
                        <span class="text-[10px] text-gray-400">LOGO</span>
                    </div>
                @endif
                <div class="text-center flex-grow">
                    <h1 class="text-2xl font-black uppercase text-gray-900 tracking-tight">{{ $schoolIdentity['name'] ?? 'NAMA SEKOLAH' }}</h1>
                    <p class="text-sm font-medium text-gray-700">{{ $schoolIdentity['address'] ?? 'Alamat Sekolah Belum Diset' }}</p>
                    <p class="text-sm text-gray-600">
                        @if(isset($schoolIdentity['phone']) && $schoolIdentity['phone']) Telp: {{ $schoolIdentity['phone'] }} @endif
                        @if(isset($schoolIdentity['email']) && $schoolIdentity['email']) | Email: {{ $schoolIdentity['email'] }} @endif
                    </p>
                </div>
            </div>
            <div class="text-center mt-6">
                <h2 class="text-xl font-bold uppercase underline tracking-widest">REKAPITULASI ABSENSI EKSTRAKURIKULER</h2>
                <p class="text-sm font-semibold mt-1 text-gray-700">Periode: {{ $startDate->isoFormat('D MMMM YYYY') }} s/d {{ $endDate->isoFormat('D MMMM YYYY') }}</p>
            </div>
        </header>
        
        <div class="mt-8 mb-4">
            <table class="w-auto text-sm">
                <tbody>
                    <tr>
                        <td class="font-bold pr-6 py-1">Nama Kegiatan</td>
                        <td class="py-1">: <span class="font-black text-gray-900">{{ $extracurricular->name }}</span></td>
                    </tr>
                    <tr>
                        <td class="font-bold pr-6 py-1">Pembina</td>
                        <td class="py-1">: {{ $teacher->name }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="overflow-x-auto print-overflow-container mt-4">
            <table class="min-w-full border-2 border-collapse border-black text-center">
                <colgroup>
                    <col style="width: 40px;">  <!-- No -->
                    <col style="width: 100px;"> <!-- NIS -->
                    <col style="width: 250px;"> <!-- Nama Siswa -->
                    <col style="width: 80px;">  <!-- Kelas -->
                    @foreach($dates as $date)
                        <col style="width: 35px;">
                    @endforeach
                    <col style="width: 35px;"> <!-- H -->
                    <col style="width: 35px;"> <!-- S -->
                    <col style="width: 35px;"> <!-- I -->
                    <col style="width: 35px;"> <!-- A -->
                </colgroup>
                <thead class="bg-gray-100 font-bold border-b-2 border-black">
                    <tr>
                        <th rowspan="2" class="border border-black p-2">No</th>
                        <th rowspan="2" class="border border-black p-2">NIS</th>
                        <th rowspan="2" class="border border-black p-2 text-left">Nama Lengkap Siswa</th>
                        <th rowspan="2" class="border border-black p-2">Kelas</th>
                        <th colspan="{{ count($dates) }}" class="border border-black p-2">Pertemuan Tanggal</th>
                        <th colspan="4" class="border border-black p-2">Total</th>
                    </tr>
                    <tr>
                        @foreach($dates as $date)
                            <th class="border border-black p-1">
                                <div class="rotate-text text-[8px]">{{ \Carbon\Carbon::parse($date)->format('d/m') }}</div>
                            </th>
                        @endforeach
                        <th class="border border-black p-1 bg-emerald-50 text-[8px]"><div class="rotate-text">Hadir</div></th>
                        <th class="border border-black p-1 bg-amber-50 text-[8px]"><div class="rotate-text">Sakit</div></th>
                        <th class="border border-black p-1 bg-blue-50 text-[8px]"><div class="rotate-text">Izin</div></th>
                        <th class="border border-black p-1 bg-rose-50 text-[8px]"><div class="rotate-text">Alpa</div></th>
                    </tr>
                </thead>
                <tbody class="font-medium">
                    @forelse($students as $student)
                        @php
                            $summary = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0];
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="border border-black py-2">{{ $loop->iteration }}</td>
                            <td class="border border-black font-mono text-[8px]">{{ $student->nis ?? '-' }}</td>
                            <td class="border border-black text-left px-3 font-bold uppercase text-[10px]">{{ $student->name }}</td>
                            <td class="border border-black text-[9px]">{{ $student->schoolClass->name ?? '-' }}</td>
                            @foreach($dates as $date)
                                @php
                                    $status = $attendanceData[$student->id][$date] ?? '-';
                                    if (isset($summary[$status])) {
                                        $summary[$status]++;
                                    }
                                @endphp
                                <td class="border border-black font-black
                                    @if($status == 'hadir') status-hadir @endif
                                    @if($status == 'sakit') status-sakit @endif
                                    @if($status == 'izin') status-izin @endif
                                    @if($status == 'alpa') status-alpa @endif
                                ">
                                    {{ $status != '-' ? strtoupper(substr($status, 0, 1)) : '-' }}
                                </td>
                            @endforeach
                            <td class="border border-black font-bold bg-emerald-50/30">{{ $summary['hadir'] }}</td>
                            <td class="border border-black font-bold bg-amber-50/30">{{ $summary['sakit'] }}</td>
                            <td class="border border-black font-bold bg-blue-50/30">{{ $summary['izin'] }}</td>
                            <td class="border border-black font-bold bg-rose-50/30">{{ $summary['alpa'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 4 + count($dates) + 4 }}" class="p-8 text-center italic text-gray-400 border border-black">Belum ada data anggota ekstrakurikuler.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-12 flex justify-between px-10">
            <div class="text-center text-sm">
                <p class="mb-16">Mengetahui,<br>Kepala Sekolah</p>
                <p class="font-bold underline uppercase">{{ $schoolIdentity['headmaster_name'] }}</p>
                <p>NIP. {{ $schoolIdentity['headmaster_nip'] }}</p>
            </div>
            <div class="text-center text-sm">
                <p class="mb-16">{{ now()->isoFormat('D MMMM YYYY') }}<br>Pembina Ekstrakurikuler,</p>
                <p class="font-bold underline uppercase">{{ Auth::user()->name }}</p>
                <p>NIP. {{ Auth::user()->teacher->nip ?? '..........................................' }}</p>
            </div>
        </div>

        <div class="mt-12 text-[10px] print-hidden border-t-2 border-dashed border-slate-200 pt-6">
            <p class="font-black text-slate-800 uppercase tracking-widest mb-2">Keterangan Status:</p>
            <div class="flex gap-6">
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded status-hadir border border-emerald-200"></span> <span class="font-bold">H: Hadir</span></div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded status-sakit border border-amber-200"></span> <span class="font-bold">S: Sakit</span></div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded status-izin border border-blue-200"></span> <span class="font-bold">I: Izin</span></div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded status-alpa border border-rose-200"></span> <span class="font-bold">A: Alpa</span></div>
            </div>
        </div>

        <div class="hidden print-block mt-16 text-[8px] text-gray-500 border-t border-gray-100 pt-4">
            <div class="flex justify-between">
                <div>Dicetak pada: {{ now()->isoFormat('D MMMM YYYY, HH:mm') }}</div>
                <div class="text-right italic underline">Sistem Informasi Absensi Sekolah (SIASEK) - {{ $schoolIdentity['name'] ?? '' }}</div>
            </div>
        </div>
    </div>
</body>
</html>
