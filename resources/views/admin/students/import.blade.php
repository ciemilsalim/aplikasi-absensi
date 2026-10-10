<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
                ['title' => 'Data', 'url' => '#'],
                ['title' => 'Manajemen Siswa', 'url' => route('admin.students.index')],
                ['title' => 'Impor Data Siswa', 'url' => '#']
            ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Impor Data Siswa dari Excel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="p-6 space-y-6">
                        <div class="bg-sky-50 border-l-4 border-sky-400 text-sky-800 p-4 rounded-md dark:bg-sky-900/50 dark:text-sky-300" role="alert">
                            <p class="font-bold">Petunjuk Impor Excel Siswa</p>
                            <ul class="list-disc list-inside mt-2 text-sm space-y-1">
                                <li>Pastikan heading kolom pertama memuat <strong>nama</strong>, <strong>nis</strong>, dan opsional <strong>kelas</strong> (nama kelas).</li>
                                <li>NIS harus unik dan tidak boleh sama dengan siswa yang sudah terdaftar.</li>
                                <li>Kode unik QR Code (UUID) akan otomatis dibuatkan oleh sistem untuk setiap siswa.</li>
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

                        @if (session('error'))
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative" role="alert">
                                <strong class="font-bold">Gagal!</strong>
                                <p class="text-sm mt-1">{{ session('error') }}</p>
                            </div>
                        @endif

                        <div>
                            <x-input-label for="file" :value="__('Unggah File Excel (.xlsx, .xls, .csv)')" />
                            <x-text-input id="file" name="file" type="file" class="block w-full mt-1" required />
                            <x-input-error :messages="$errors->get('file')" class="mt-2" />
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end gap-4">
                        <a href="{{ route('admin.students.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-800 dark:text-slate-400">Batal</a>
                        <x-primary-button>Mulai Proses Impor</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
