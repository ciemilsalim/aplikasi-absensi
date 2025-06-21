@php
// Definisikan data untuk breadcrumb halaman ini
$breadcrumbs = [
    ['title' => 'Laporan', 'url' => route('admin.reports.create')]
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Cetak Laporan Kehadiran') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-breadcrumb :breadcrumbs="$breadcrumbs" />
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                {{-- Arahkan form untuk membuka hasil di tab baru --}}
                <form action="{{ route('admin.reports.generate') }}" method="POST" target="_blank">
                    @csrf
                    <div class="p-6 space-y-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Filter Laporan</h3>
                        
                        <div>
                            <x-input-label for="school_class_id" :value="__('Pilih Kelas')" />
                            <select name="school_class_id" id="school_class_id" class="block mt-1 w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm" required>
                                <option value="">-- Semua Kelas --</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="month" :value="__('Pilih Bulan dan Tahun')" />
                            <x-text-input id="month" class="block mt-1 w-full" type="month" name="month" :value="date('Y-m')" required />
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end">
                        <x-primary-button>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.34.057-.68.1-1.02.1C3.584 13.93 2.25 12.597 2.25 11V3c0-1.036.84-1.875 1.875-1.875h15.75c1.036 0 1.875.84 1.875 1.875v8.25c0 1.597-1.333 2.927-2.927 2.927-.34 0-.68-.043-1.02-.127a4.526 4.526 0 0 1-4.496 2.454c-1.849 0-3.483-.93-4.496-2.454Z" /></svg>
                            Cetak Laporan
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
