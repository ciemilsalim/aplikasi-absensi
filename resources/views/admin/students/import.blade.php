@php
// Definisikan data untuk breadcrumb halaman ini
$breadcrumbs = [
    ['title' => 'Data', 'url' => '#'],
    ['title' => 'Siswa', 'url' => route('admin.students.index')],
    ['title' => 'Import Siswa', 'url' => route('admin.students.import.form')]
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Impor Data Siswa dari Excel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-10 lg:px-10">
            <x-breadcrumb :breadcrumbs="$breadcrumbs" />
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="p-6 space-y-6">
                        <div class="bg-sky-50 border-l-4 border-sky-400 text-sky-800 p-4 rounded-md" role="alert">
                            <p class="font-bold">Petunjuk Penting</p>
                            <ul class="list-disc list-inside mt-2 text-sm">
                                <li>Pastikan file Anda berformat <strong>.xlsx</strong> atau <strong>.xls</strong>.</li>
                                <li>Baris pertama file Excel harus berisi heading (judul kolom).</li>
                                <li>Pastikan heading kolom adalah <strong>nama</strong> dan <strong>nis</strong> (huruf kecil semua).</li>
                                <li>Sistem akan menolak data jika ada NIS yang sama atau data yang tidak valid.</li>
                            </ul>
                        </div>
                        
                        @if (session('import_errors'))
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative" role="alert">
                                <strong class="font-bold">Gagal Impor!</strong>
                                <ul class="mt-2 list-disc list-inside text-sm">
                                    @foreach (session('import_errors') as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div>
                            <label for="file" class="block mb-2 text-sm font-medium text-gray-900">Unggah File Excel</label>
                            <input type="file" name="file" id="file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100" required>
                            @error('file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-4">
                        <a href="{{ route('admin.students.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Mulai Proses Impor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
