<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'],
            ['title' => 'Data Siswa', 'url' => route('admin.students.index')],
            ['title' => 'Cetak Kartu', 'url' => '#']
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Cetak Kartu Siswa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-wrap gap-4 justify-between items-center mb-6 print-hidden">
                        <div>
                            <h3 class="text-lg font-medium">Pratinjau Kartu Siswa</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Gunakan Ctrl+P atau tombol di bawah untuk mencetak.</p>
                        </div>
                        <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.34.057-.68.1-1.02.1C3.584 13.93 2.25 12.597 2.25 11V3c0-1.036.84-1.875 1.875-1.875h15.75c1.036 0 1.875.84 1.875 1.875v8.25c0 1.597-1.333 2.927-2.927 2.927-.34 0-.68-.043-1.02-.127a4.526 4.526 0 0 1-4.496 2.454c-1.849 0-3.483-.93-4.496-2.454Z" /></svg>
                            Cetak Halaman
                        </button>
                    </div>

                    {{-- Grid untuk menampilkan kartu-kartu siswa --}}
                    <div id="card-grid" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @forelse ($students as $student)
                            <div class="student-card-container break-inside-avoid">
                                <!-- Sisi Depan Kartu -->
                                <div class="student-card-front bg-gradient-to-br from-sky-500 to-indigo-600 rounded-2xl shadow-lg p-4 text-white relative overflow-hidden">
                                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full"></div>
                                    <div class="absolute -bottom-12 -left-8 w-40 h-40 bg-white/10 rounded-full"></div>
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-bold text-base">{{ config('app.name', 'AbsensiSiswa') }}</p>
                                            <p class="text-xs opacity-80">Kartu Pelajar & Absensi</p>
                                        </div>
                                        @if ($appLogoPath)
                                            <img src="{{ asset('storage/' . $appLogoPath) }}" alt="Logo" class="h-10 w-10 object-contain bg-white rounded-full p-1">
                                        @endif
                                    </div>
                                    <div class="mt-6 flex items-center gap-4">
                                        <div class="w-20 h-20 bg-white/30 rounded-lg flex items-center justify-center">
                                            <svg class="w-12 h-12 text-white/70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-base leading-tight">{{ $student->name }}</p>
                                            <p class="text-xs opacity-90">NIS: {{ $student->nis }}</p>
                                            <p class="text-xs opacity-90">Kelas: {{ $student->schoolClass->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Sisi Belakang Kartu -->
                                <div class="student-card-back bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-4 mt-2">
                                    <div class="flex flex-col items-center justify-center h-full">
                                        <div class="p-1.5 bg-white rounded-lg shadow-inner">
                                            {!! QrCode::size(85)->generate($student->unique_id) !!}
                                        </div>
                                        <p class="text-[10px] text-center text-gray-500 dark:text-gray-400 mt-2">Gunakan QR Code untuk absensi masuk dan pulang.</p>
                                        <div class="border-t border-dashed w-full my-2 dark:border-slate-700"></div>
                                        <p class="text-[9px] text-center text-gray-500 dark:text-gray-400">Jika kartu hilang, segera lapor ke pihak sekolah.</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-500 col-span-full text-center">Belum ada data siswa untuk dicetak.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .print-hidden, [x-data], header, footer, x-slot, nav {
                display: none !important;
            }
            body {
                padding: 0 !important;
                margin: 0 !important;
                background-color: #fff !important;
            }
            main {
                padding: 0.5rem !important;
            }
            .py-12 {
                padding: 0 !important;
            }
            .bg-white, .dark\:bg-slate-800, .max-w-7xl {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                background-color: transparent !important;
            }
            
            /* PERBAIKAN UKURAN KARTU NAMA */
            #card-grid {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 5mm !important;
                justify-content: flex-start;
            }
            .student-card-container {
                width: 85.6mm;
                height: 108mm; /* Kira-kira 2x tinggi kartu nama + margin */
                page-break-inside: avoid;
                border: 1px dashed #ccc; /* PERBAIKAN: Menambahkan garis tepi untuk panduan potong */
                border-radius: 1rem; /* Menyesuaikan radius dengan kartu di dalamnya */
                overflow: hidden; /* Memastikan konten tidak keluar dari border */
            }
            .student-card-front, .student-card-back {
                width: 100%;
                height: 53.98mm; /* Ukuran tinggi kartu nama standar */
                box-sizing: border-box;
                border-radius: 0; /* Hapus radius dari kartu individu saat print */
                box-shadow: none !important;
            }
            .student-card-back {
                margin-top: 0 !important;
            }
            .student-card-front {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        .break-inside-avoid {
            break-inside: avoid;
        }
    </style>
</x-app-layout>
