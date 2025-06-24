<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'],
            ['title' => 'Data Guru', 'url' => route('admin.teachers.index')],
            ['title' => 'Impor Data', 'url' => '#']
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Impor Data Guru dari Excel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.teachers.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="p-6 space-y-6">
                        <div class="bg-sky-50 border-l-4 border-sky-400 text-sky-800 p-4 rounded-md dark:bg-sky-900/50 dark:text-sky-300" role="alert">
                            <p class="font-bold">Petunjuk Penting</p>
                            <ul class="list-disc list-inside mt-2 text-sm">
                                <li>Pastikan heading kolom adalah <strong>nama</strong>, <strong>email</strong>, dan <strong>password</strong>.</li>
                                <li>Heading <strong>nip</strong> dan <strong>nomor_hp</strong> bersifat opsional.</li>
                                <li>Email dan NIP tidak boleh duplikat dengan data yang sudah ada.</li>
                            </ul>
                        </div>
                        
                        @if (session('import_errors'))
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative" role="alert">
                                <strong class="font-bold">Gagal Impor! Harap perbaiki kesalahan berikut:</strong>
                                <ul class="mt-2 list-disc list-inside text-sm">
                                    @foreach (session('import_errors') as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div>
                            <x-input-label for="file" :value="__('Unggah File Excel')" />
                            <x-text-input id="file" name="file" type="file" class="block w-full mt-1 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-sky-50 dark:file:bg-slate-700 file:text-sky-700 dark:file:text-slate-300 hover:file:bg-sky-100 dark:hover:file:bg-slate-600" required />
                            <x-input-error :messages="$errors->get('file')" class="mt-2" />
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end gap-4">
                        <a href="{{ route('admin.teachers.index') }}" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Batal</a>
                        <x-primary-button>Mulai Proses Impor</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
