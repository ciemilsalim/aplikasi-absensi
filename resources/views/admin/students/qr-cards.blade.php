@php
    $schoolName = \App\Models\Setting::where('key', 'school_name')->value('value') ?? config('app.name', 'SIASEK');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Data Siswa', 'url' => route('admin.students.index')],
                    ['title' => 'Cetak Kartu QR Siswa', 'url' => '#']
                ]" />
                <h2 class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Cetak Kartu Presensi & QR Code Siswa') }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Format kartu fisik siap cetak untuk pemindaian presensi di Kiosk & Scanner Kelas.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.students.index') }}" class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 transition-all">
                    Kembali
                </a>
                <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-md shadow-sky-600/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.05.65-2.079 1.72-2.079h7.12c1.07 0 1.96 1.029 1.72 2.079M6.72 13.829a2.25 2.25 0 0 0-2.22 1.875l-.47 2.115A2.25 2.25 0 0 0 6.22 20.25h11.56a2.25 2.25 0 0 0 2.19-2.431l-.47-2.115a2.25 2.25 0 0 0-2.22-1.875M6.72 13.829h10.56M6 9V4.5A1.5 1.5 0 0 1 7.5 3h9A1.5 1.5 0 0 1 18 4.5V9m-12 0h12" />
                    </svg>
                    <span>Cetak Halaman Ini (Print)</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Filter Bar (Tidak dicetak saat window.print) --}}
            <div class="mb-6 p-4 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs no-print">
                <form method="GET" action="{{ route('admin.students.qr') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Filter Kelas</label>
                        <select name="school_class_id" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-sky-500 focus:border-sky-500" onchange="this.form.submit()">
                            <option value="">Semua Kelas ({{ $students->count() }} Siswa)</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ request('school_class_id') == $c->id ? 'selected' : '' }}>
                                    Kelas {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Cari Nama / NIS</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama atau NIS..." class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-sky-500 focus:border-sky-500" />
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="w-full py-2 px-4 rounded-xl bg-slate-800 dark:bg-slate-700 text-white text-xs font-semibold hover:bg-slate-700 transition">
                            Terapkan Filter
                        </button>
                        @if(request()->hasAny(['school_class_id', 'search']))
                            <a href="{{ route('admin.students.qr') }}" class="py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Info Cetak Bar --}}
            <div class="mb-4 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 no-print">
                <p>Menampilkan <strong>{{ $students->count() }}</strong> kartu siswa {{ $selectedClass ? 'Kelas ' . $selectedClass->name : '' }}</p>
                <p class="text-[11px] italic">Gunakan kertas A4 dengan orientasi Portrait atau Landscape untuk hasil cetak optimal.</p>
            </div>

            {{-- Grid Kartu Siswa --}}
            <div class="cards-grid grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @forelse($students as $student)
                    <div class="student-card bg-white dark:bg-slate-850 border-2 border-slate-200 dark:border-slate-700 rounded-2xl p-4 flex flex-col items-center justify-between text-center shadow-xs transition hover:shadow-md relative overflow-hidden break-inside-avoid">
                        
                        {{-- Header Kartu --}}
                        <div class="w-full border-b border-slate-100 dark:border-slate-750 pb-2 mb-3">
                            <h4 class="font-extrabold text-[11px] text-sky-700 dark:text-sky-400 uppercase tracking-wider truncate">
                                {{ $schoolName }}
                            </h4>
                            <p class="text-[9px] text-slate-400 uppercase font-semibold">KARTU PRESENSI DIGITAL</p>
                        </div>

                        {{-- QR Code Box --}}
                        <div class="qr-box p-2 bg-white rounded-xl border border-slate-100 shadow-2xs mb-3 flex items-center justify-center">
                            {!! QrCode::size(110)->generate($student->unique_id) !!}
                        </div>

                        {{-- Detail Siswa --}}
                        <div class="w-full">
                            <h3 class="font-bold text-xs text-slate-900 dark:text-white line-clamp-1 leading-snug">
                                {{ $student->name }}
                            </h3>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-0.5">
                                NIS: {{ $student->nis }}
                            </p>
                            <div class="mt-2 flex items-center justify-center gap-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300">
                                    {{ $student->schoolClass ? 'Kelas ' . $student->schoolClass->name : 'Tanpa Kelas' }}
                                </span>
                            </div>
                        </div>

                        {{-- Footer Kartu --}}
                        <div class="w-full border-t border-slate-100 dark:border-slate-750 pt-1.5 mt-2.5">
                            <p class="text-[8px] text-slate-400 font-mono tracking-tighter truncate">
                                ID: {{ substr($student->unique_id, 0, 18) }}...
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-16 text-center text-slate-400 bg-white dark:bg-slate-800 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto mb-2 text-slate-300 dark:text-slate-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        <p class="text-sm font-semibold">Tidak ada siswa yang ditemukan.</p>
                        <p class="text-xs mt-1 text-slate-400">Silakan ubah filter kelas atau kata kunci pencarian.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Styling Khusus Print (window.print) --}}
    <style>
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            header, nav, .no-print, [role="navigation"] {
                display: none !important;
            }
            .cards-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 12px !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .student-card {
                border: 1.5px solid #cbd5e1 !important;
                border-radius: 12px !important;
                padding: 10px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                background: #ffffff !important;
                box-shadow: none !important;
            }
            .qr-box {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            .qr-box svg {
                width: 95px !important;
                height: 95px !important;
            }
        }
    </style>
</x-app-layout>
