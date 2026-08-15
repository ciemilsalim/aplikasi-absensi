@extends('layouts.public')

@section('title', 'Daftar Siswa & Kartu QR')

@section('content')
<div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-6 border-b border-slate-100 dark:border-slate-800">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Daftar Siswa & Kartu QR</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Cetak kartu fisik QR Code siswa untuk pemindaian presensi otomatis.</p>
        </div>
        <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-md shadow-sky-600/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
            <span class="material-icons text-base">print</span>
            <span>Cetak Semua Kartu</span>
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse ($students as $student)
            <div class="card-container p-5 border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-850/40 rounded-3xl text-center flex flex-col items-center justify-center break-inside-avoid shadow-2xs hover:shadow-md transition-shadow">
                <div class="qr-code mb-3 p-3 bg-white rounded-2xl border border-slate-100 shadow-2xs">
                    {!! QrCode::size(120)->generate($student->unique_id) !!}
                </div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white line-clamp-1">{{ $student->name }}</h3>
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-0.5">NIS: {{ $student->nis }}</span>
                @if($student->schoolClass)
                    <span class="mt-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300">
                        {{ $student->schoolClass->name }}
                    </span>
                @endif
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400">
                <span class="material-icons text-4xl mb-2 text-slate-300">people_outline</span>
                <p class="text-xs">Belum ada data siswa terdaftar.</p>
            </div>
        @endforelse
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        .card-container, .card-container * {
            visibility: visible;
        }
        .grid {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 1rem !important;
        }
        main, .bg-white {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
        }
        header, button {
            display: none !important;
        }
    }
    .break-inside-avoid {
        break-inside: avoid;
    }
</style>
@endsection

