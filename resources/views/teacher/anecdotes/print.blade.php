<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Catatan Anekdot Siswa - {{ $classInfo?->name ?? 'Semua Kelas' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 landscape;
            margin: 1cm;
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

        @media screen {
            .page-container {
                max-width: 29.7cm;
                min-height: 21cm;
                margin: 20px auto;
                background: #ffffff;
                padding: 30px 40px;
                border-radius: 12px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
                border: 1px solid #e2e8f0;
            }
        }

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
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .kop-logo img {
            max-height: 70px;
            max-width: 70px;
            object-fit: contain;
        }
        .kop-text {
            text-align: center;
            width: 100%;
            padding: 0 80px;
        }
        .kop-text h1 {
            font-size: 15pt;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
            color: #0f172a;
        }
        .kop-text p {
            font-size: 8.5pt;
            color: #475569;
            margin: 2px 0 0 0;
        }
        .kop-divider {
            border-top: 2.5px solid #0f172a;
            border-bottom: 0.75px solid #0f172a;
            height: 4px;
            margin-top: 6px;
            margin-bottom: 16px;
        }

        table.custom-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        table.custom-table th, 
        table.custom-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.custom-table th {
            background-color: #f8fafc;
            color: #0f172a;
            font-weight: 700;
            text-align: center;
        }
        @media print {
            table.custom-table th {
                background-color: #f1f5f9 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Toolbar (Screen Only) -->
    <div class="no-print fixed bottom-6 right-6 z-50 flex items-center gap-3 bg-slate-900/90 backdrop-blur-md px-5 py-3 rounded-2xl shadow-2xl border border-slate-700 text-white">
        <span class="text-xs font-semibold">Mode Pratinjau Cetak</span>
        <button onclick="window.print()" class="flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-500 rounded-xl font-bold text-xs shadow-lg transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Cetak Dokumen</span>
        </button>
        <button onclick="window.close()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 rounded-xl font-bold text-xs transition-colors">
            Tutup
        </button>
    </div>

    <div class="page-container">
        
        <!-- KOP SURAT RESMI -->
        <div class="kop-wrapper">
            <div class="kop-logo">
                @if(!empty($schoolIdentity['logo']))
                    <img src="{{ asset('storage/' . $schoolIdentity['logo']) }}" alt="Logo Sekolah" onerror="this.src='{{ asset($schoolIdentity['logo']) }}'">
                @else
                    <img src="{{ asset('assets/logo.png') }}" alt="Logo Default" onerror="this.style.display='none'">
                @endif
            </div>
            <div class="kop-text">
                <h1>{{ $schoolIdentity['name'] ?? 'SMP NEGERI 1 BIAU' }}</h1>
                <p>{{ $schoolIdentity['address'] ?? 'Kabupaten Buol, Sulawesi Tengah' }}</p>
                <p>
                    @if(!empty($schoolIdentity['phone'])) Telp: {{ $schoolIdentity['phone'] }} @endif
                    @if(!empty($schoolIdentity['email'])) • Email: {{ $schoolIdentity['email'] }} @endif
                </p>
            </div>
        </div>
        <div class="kop-divider"></div>

        <!-- JUDUL LAPORAN -->
        <div class="text-center mb-5">
            <h2 class="text-base font-extrabold tracking-wide uppercase text-slate-900">
                LEMBAR REKAPITULASI CATATAN ANEKDOT SISWA
            </h2>
            <p class="text-xs text-slate-600 font-medium mt-0.5">
                Observasi Pembelajaran Aspek Akademik, Kehadiran, dan Sikap
            </p>
        </div>

        <!-- INFO DOKUMEN -->
        <div class="grid grid-cols-2 gap-4 text-xs mb-4 bg-slate-50 p-3 rounded-xl border border-slate-200">
            <div class="space-y-1">
                <p><span class="font-bold text-slate-700 w-28 inline-block">Kelas</span>: <strong class="text-slate-900">{{ $classInfo?->name ?? 'Semua Kelas' }}</strong></p>
                <p><span class="font-bold text-slate-700 w-28 inline-block">Mata Pelajaran</span>: <strong class="text-slate-900">{{ $subjectInfo?->name ?? 'Semua Mata Pelajaran' }}</strong></p>
            </div>
            <div class="space-y-1">
                <p><span class="font-bold text-slate-700 w-28 inline-block">Periode Tanggal</span>: <strong class="text-slate-900">{{ $startDate->translatedFormat('d M Y') }} s/d {{ $endDate->translatedFormat('d M Y') }}</strong></p>
                <p><span class="font-bold text-slate-700 w-28 inline-block">Guru Pengampu</span>: <strong class="text-slate-900">{{ $teacher->name }}</strong></p>
            </div>
        </div>

        <!-- TABEL DATA CATATAN ANEKDOT -->
        @if($anecdotes->isEmpty())
            <div class="py-12 text-center text-xs text-slate-500 italic border border-dashed border-slate-300 rounded-xl">
                Tidak ada data catatan anekdot siswa pada periode dan filter ini.
            </div>
        @else
            <table class="custom-table mb-6">
                <thead>
                    <tr>
                        <th class="w-8">No</th>
                        <th class="w-20">Tanggal</th>
                        <th class="w-40">Nama Siswa / NIS</th>
                        <th class="w-16">Kelas</th>
                        <th class="w-1/4">Catatan Akademik</th>
                        <th class="w-1/4">Catatan Kehadiran</th>
                        <th class="w-1/4">Catatan Sikap / Perilaku</th>
                        <th class="w-36">Tindak Lanjut</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $sentimentTags = [
                            'positive' => '[Sangat Baik]',
                            'neutral' => '[Cukup]',
                            'needs_guidance' => '[Perlu Bimbingan]',
                        ];
                    @endphp
                    @foreach($anecdotes as $idx => $an)
                        <tr>
                            <td class="text-center font-bold text-slate-600">{{ $idx + 1 }}</td>
                            <td class="text-center whitespace-nowrap">{{ $an->date->format('d/m/Y') }}</td>
                            <td>
                                <strong class="text-slate-900 block">{{ $an->student->name }}</strong>
                                <span class="text-[8pt] text-slate-500">NIS: {{ $an->student->nis ?? '-' }}</span>
                            </td>
                            <td class="text-center">{{ $an->schoolClass?->name ?? '-' }}</td>
                            <td>
                                <span class="font-bold text-[8pt] text-indigo-700">{{ $sentimentTags[$an->academic_sentiment] ?? '' }}</span>
                                <p class="mt-0.5 text-slate-800">{{ $an->academic_note ?: '-' }}</p>
                            </td>
                            <td>
                                <span class="font-bold text-[8pt] text-sky-700">{{ $sentimentTags[$an->attendance_sentiment] ?? '' }}</span>
                                <p class="mt-0.5 text-slate-800">{{ $an->attendance_note ?: '-' }}</p>
                            </td>
                            <td>
                                <span class="font-bold text-[8pt] text-emerald-700">{{ $sentimentTags[$an->attitude_sentiment] ?? '' }}</span>
                                <p class="mt-0.5 text-slate-800">{{ $an->attitude_note ?: '-' }}</p>
                            </td>
                            <td class="text-[8.5pt] text-slate-700">
                                {{ $an->follow_up ?: '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- TANDA TANGAN -->
        <div class="break-inside-avoid mt-8 grid grid-cols-2 gap-8 text-xs">
            <div class="text-center">
                <p>Mengetahui,</p>
                <p class="font-bold text-slate-800">Kepala {{ $schoolIdentity['name'] ?? 'Sekolah' }}</p>
                <div class="h-16"></div>
                <p class="font-bold underline text-slate-900">{{ $schoolIdentity['headmaster_name'] ?? '.........................................' }}</p>
                <p class="text-[10px] text-slate-500">NIP. {{ $schoolIdentity['headmaster_nip'] ?? '-' }}</p>
            </div>
            <div class="text-center">
                <p>Biau, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
                <p class="font-bold text-slate-800">Guru Mata Pelajaran</p>
                <div class="h-16"></div>
                <p class="font-bold underline text-slate-900">{{ $teacher->name }}</p>
                <p class="text-[10px] text-slate-500">NIP. {{ $teacher->nip ?? '-' }}</p>
            </div>
        </div>

    </div>

</body>
</html>
