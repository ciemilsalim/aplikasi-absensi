@php
    $schoolName = \App\Models\Setting::where('key', 'school_name')->value('value') ?? config('app.name', 'SMP NEGERI 1 BIAU');
    $schoolAddress = \App\Models\Setting::where('key', 'school_address')->value('value') ?? 'Jl. Syarif Mansur No. 2, Biau, Buol, Sulawesi Tengah';
    $passCode = (string) ($dispensation->pass_code ?: ('PASS-' . str_pad((string) $dispensation->id, 6, '0', STR_PAD_LEFT)));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pass Card Dispensasi - {{ $passCode }} - {{ $dispensation->student?->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .print-card {
                border: 2px solid #000000 !important;
                box-shadow: none !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="p-4 sm:p-8 flex flex-col items-center justify-center min-h-screen">

    {{-- Top Action Bar (No-Print) --}}
    <div class="no-print max-w-md w-full mb-4 flex items-center justify-between gap-3">
        <a href="{{ route('admin.dispensations.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 transition shadow-xs">
            <span>&larr; Kembali ke Daftar</span>
        </a>
        <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-md shadow-indigo-600/20">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.05.65-2.079 1.72-2.079h7.12c1.07 0 1.96 1.029 1.72 2.079M6.72 13.829a2.25 2.25 0 0 0-2.22 1.875l-.47 2.115A2.25 2.25 0 0 0 6.22 20.25h11.56a2.25 2.25 0 0 0 2.19-2.431l-.47-2.115a2.25 2.25 0 0 0-2.22-1.875M6.72 13.829h10.56M6 9V4.5A1.5 1.5 0 0 1 7.5 3h9A1.5 1.5 0 0 1 18 4.5V9m-12 0h12" />
            </svg>
            <span>Cetak Pass Card (Print)</span>
        </button>
    </div>

    {{-- Pass Card Container --}}
    <div class="print-card max-w-md w-full bg-white rounded-3xl border-2 border-slate-800 p-6 shadow-xl relative overflow-hidden">
        
        {{-- Header Kartu --}}
        <div class="text-center border-b-2 border-slate-800 pb-3 mb-4">
            <h3 class="font-extrabold text-xs uppercase tracking-wider text-slate-800">{{ $schoolName }}</h3>
            <p class="text-[9px] text-slate-500 leading-tight mt-0.5">{{ $schoolAddress }}</p>
            <div class="mt-2 inline-block px-3 py-1 rounded-full bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest">
                SURAT IZIN KELUAR / PASS CARD
            </div>
        </div>

        {{-- Pass Code Badge & Date --}}
        <div class="flex items-center justify-between pb-3 border-b border-dashed border-slate-300 mb-3 text-xs">
            <div>
                <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold block">KODE OTORISASI:</span>
                <span class="font-mono font-extrabold text-sm text-indigo-700">{{ $passCode }}</span>
            </div>
            <div class="text-right">
                <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold block">TANGGAL:</span>
                <span class="font-bold text-slate-700">{{ $dispensation->created_at ? $dispensation->created_at->format('d/m/Y H:i') : date('d/m/Y') }}</span>
            </div>
        </div>

        {{-- Detail Siswa --}}
        <div class="space-y-1.5 text-xs mb-4">
            <div class="grid grid-cols-3">
                <span class="text-slate-500 font-medium">Nama Siswa</span>
                <span class="col-span-2 font-bold text-slate-900">: {{ $dispensation->student?->name }}</span>
            </div>
            <div class="grid grid-cols-3">
                <span class="text-slate-500 font-medium">NIS / Kelas</span>
                <span class="col-span-2 font-semibold text-slate-800">: {{ $dispensation->student?->nis }} / {{ $dispensation->student?->schoolClass?->name ?? 'Kelas -' }}</span>
            </div>
            <div class="grid grid-cols-3">
                <span class="text-slate-500 font-medium">Jenis Izin</span>
                <span class="col-span-2 font-bold text-indigo-700">: {{ $dispensation->permit_type_label }}</span>
            </div>
            <div class="grid grid-cols-3">
                <span class="text-slate-500 font-medium">Keperluan</span>
                <span class="col-span-2 text-slate-800 leading-snug">: {{ $dispensation->reason }}</span>
            </div>
            @if($dispensation->valid_until)
            <div class="grid grid-cols-3">
                <span class="text-slate-500 font-medium">Berlaku Sampai</span>
                <span class="col-span-2 font-bold text-rose-600">: Pukul {{ \Carbon\Carbon::parse($dispensation->valid_until)->format('H:i') }} WITA</span>
            </div>
            @endif
            @if($dispensation->notes)
            <div class="grid grid-cols-3">
                <span class="text-slate-500 font-medium">Catatan Gerbang</span>
                <span class="col-span-2 text-amber-700 italic">: {{ $dispensation->notes }}</span>
            </div>
            @endif
        </div>

        {{-- QR Code Box for Security Scanner --}}
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col items-center justify-center text-center mb-4">
            <div class="p-2 bg-white rounded-xl border border-slate-200 shadow-2xs mb-1.5">
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->generate($passCode) !!}
            </div>
            <p class="text-[10px] font-bold text-slate-700">Tunjukkan QR Code ini kepada Satpam di Pintu Gerbang</p>
            <p class="text-[9px] text-slate-400">Pemindaian otomatis mencatat waktu keluar & mengirim notifikasi ke orang tua</p>
        </div>

        {{-- Signature Section --}}
        <div class="grid grid-cols-2 gap-4 text-center text-[10px] pt-2 border-t border-slate-200">
            <div>
                <p class="text-slate-500 font-medium">Diberikan Oleh (Guru/Piket):</p>
                <div class="h-12 flex items-end justify-center font-bold text-slate-800 underline">
                    {{ $dispensation->approved_by_name ?? ($dispensation->approver?->name ?? 'Guru Piket') }}
                </div>
            </div>
            <div>
                <p class="text-slate-500 font-medium">Verifikasi Satpam Gerbang:</p>
                <div class="h-12 flex items-end justify-center font-bold text-slate-800 underline">
                    {{ $dispensation->security_name ?? ($dispensation->security?->name ?? '( Petugas Satpam )') }}
                </div>
            </div>
        </div>

    </div>

</body>
</html>
