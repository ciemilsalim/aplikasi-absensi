<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cetak Kartu QR Code Siswa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6 print-hidden">
                        <h3 class="text-lg font-medium">Kartu QR Code Siswa</h3>
                        <button onclick="window.print()" class="px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                            Cetak Halaman Ini
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        @forelse ($students as $student)
                            <div class="card-container p-4 border border-slate-200 rounded-lg text-center flex flex-col items-center justify-center break-inside-avoid">
                                <div class="qr-code mb-3">
                                    {!! QrCode::size(120)->generate($student->unique_id) !!}
                                </div>
                                <h3 class="font-semibold text-slate-800">{{ $student->name }}</h3>
                                <p class="text-sm text-slate-500">NIS: {{ $student->nis }}</p>
                            </div>
                        @empty
                            <p class="text-slate-500 col-span-full">Belum ada data siswa untuk dicetak.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .print-hidden, [x-data], header, footer {
                display: none !important;
            }
            body {
                padding: 1rem;
                background-color: #fff;
            }
            main {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 0 !important;
                margin: 0 !important;
            }
            .py-12 {
                padding: 0 !important;
            }
            .bg-white, .max-w-7xl {
                box-shadow: none !important;
                border: none !important;
            }
            .grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 1rem !important;
                page-break-inside: auto;
            }
            .card-container {
                 page-break-inside: avoid;
            }
        }
        .break-inside-avoid {
            break-inside: avoid;
        }
    </style>
</x-app-layout>
