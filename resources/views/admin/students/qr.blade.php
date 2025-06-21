@php
// Definisikan data untuk breadcrumb halaman ini
$breadcrumbs = [
    ['title' => 'Data', 'url' => '#'],
    ['title' => 'Siswa', 'url' => route('admin.students.index')],
    ['title' => 'Cetak Kartu QR', 'url' => route('admin.students.qr')]
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Cetak Kartu Siswa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-breadcrumb :breadcrumbs="$breadcrumbs" />
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6 print-hidden">
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
                    <div id="card-grid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-8">
                        @forelse ($students as $student)
                            <div class="student-card bg-white dark:bg-slate-800 shadow-lg rounded-2xl overflow-hidden break-inside-avoid border">
                                <!-- Header Kartu -->
                                <div class="bg-sky-600 dark:bg-sky-700 text-white p-4 flex items-center gap-3 border">
                                    @if ($appLogoPath)
                                        <img src="{{ asset('storage/' . $appLogoPath) }}" alt="Logo" class="h-10 w-10 object-contain bg-white rounded-full p-1">
                                    @endif
                                    <div>
                                        <p class="font-bold text-lg">{{ config('app.name', 'Kartu Siswa') }}</p>
                                        <p class="text-xs opacity-80">Kartu Tanda Pengenal & Absensi</p>
                                    </div>
                                </div>
                                
                                <!-- Isi Kartu -->
                                <div class="p-5 border bg-slate-50 dark:bg-slate-700">
                                    {{-- Flexbox untuk Foto Siswa dan QR Code --}}
                                    <div class="flex items-center gap-5">
                                        {{-- Placeholder Foto Siswa --}}
                                        <div class="flex-shrink-0">
                                            <div class="w-24 h-24 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                                <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                            </div>
                                        </div>
                                        
                                        {{-- QR Code --}}
                                        <div class="flex-shrink-0">
                                            {!! QrCode::size(100)->generate($student->unique_id) !!}
                                        </div>
                                    </div>
                                    
                                    {{-- Detail Nama & NIS --}}
                                    <div class="text-center mt-4 border-t-2 border-dashed border-slate-200 dark:border-slate-700 pt-4">
                                        <p class="font-bold text-xl text-slate-800 dark:text-white truncate">{{ $student->name }}</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">NIS: {{ $student->nis }}</p>
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
            /* Sembunyikan elemen yang tidak perlu saat print */
            .print-hidden, [x-data], header, footer, x-slot {
                display: none !important;
            }
            body {
                padding: 0 !important;
                margin: 0 !important;
                background-color: #fff !important; /* Pastikan background putih */
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
            /* Atur grid untuk kertas A4 */
            #card-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 1rem !important;
            }
            .student-card {
                box-shadow: none !important;
                border: 1px solid #e2e8f0; /* Tambahkan border tipis saat print */
                 page-break-inside: avoid;
            }
        }
        .break-inside-avoid {
            break-inside: avoid;
        }
    </style>
</x-app-layout>
