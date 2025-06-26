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
                    <div id="card-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        @forelse ($students as $student)
                            <div class="student-card-container break-inside-avoid">
                                {{-- PERBAIKAN: Desain kartu dengan header berwarna --}}
                                <div class="w-full h-full bg-white dark:bg-slate-800 rounded-2xl shadow-lg flex flex-col text-center relative overflow-hidden">
                                    <!-- Header Kartu Berwarna -->
                                    <div class="bg-sky-600 dark:bg-sky-700 text-white p-4 text-center rounded-t-2xl">
                                        <p class="font-bold text-base leading-tight">{{ $appName }}</p>
                                        <p class="text-xs text-sky-200 leading-tight">{{ config('app.name', 'AbsensiSiswa') }}</p>
                                    </div>

                                    <!-- Konten Utama -->
                                    <div class="flex-grow flex flex-col items-center justify-center p-4">
                                        <div class="p-2 bg-white rounded-lg shadow-md inline-block">
                                            {!! QrCode::size(120)->generate($student->unique_id) !!}
                                        </div>
                                    </div>
                                    
                                    <!-- Footer -->
                                    <div class="w-full p-4 border-t border-gray-100 dark:border-slate-700">
                                        <p class="font-bold text-lg text-slate-800 dark:text-white truncate" title="{{ $student->name }}">{{ $student->name }}</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">NIS: {{ $student->nis }} | {{ $student->schoolClass->name ?? 'Tanpa Kelas' }}</p>
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
            body > * { visibility: hidden; }
            #card-grid, #card-grid * { visibility: visible; }
            #card-grid {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 0.5cm;
                margin: 0;
            }
            .print-hidden { display: none !important; }
            body { background-color: #fff !important; }
            
            #card-grid {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 0.5 !important;
                justify-content: flex-start;
            }
            .student-card-container {
                width: 53.98mm; /* Lebar kartu nama standar */
                height: 85.6mm; /* Tinggi kartu nama standar */
                page-break-inside: avoid;
                border: 0.25mm solid #e5e7eb;
                box-shadow: none !important;
                border-radius: 0 !important;
                background-color: #fff !important;
            }
            .dark .student-card-container, .dark .p-2 {
                background-color: #fff !important;
            }
            .dark .text-white, .dark .text-slate-400, .dark .text-slate-800, .dark .text-slate-500, .dark .text-slate-300, .dark .text-slate-700 {
                color: #000 !important;
            }
            .student-card-container .p-4 {
                padding: 0.5rem !important;
            }
            /* PERBAIKAN: Memaksa pencetakan warna background header */
            .student-card-container .bg-sky-600 {
                background-color: #0284c7 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .student-card-container .text-white, .student-card-container .text-sky-200 {
                color: #fff !important;
            }
        }
        .break-inside-avoid {
            break-inside: avoid;
        }
    </style>
</x-app-layout>
