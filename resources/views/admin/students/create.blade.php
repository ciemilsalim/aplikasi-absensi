@php
// Definisikan data untuk breadcrumb halaman ini
$breadcrumbs = [
    ['title' => 'Data', 'url' => '#'], // Item tanpa link
    ['title' => 'Siswa', 'url' => route('admin.students.index')],
    ['title' => 'Tambah Baru', 'url' => route('admin.students.create')]
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Tambah Siswa Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
             {{-- Panggil komponen breadcrumb di sini --}}
            <x-breadcrumb :breadcrumbs="$breadcrumbs" />
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.students.store') }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-6">
                        
                        <div>
                            <x-input-label for="name" :value="__('Nama Siswa')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="nis" :value="__('Nomor Induk Siswa (NIS)')" />
                            <x-text-input id="nis" class="block mt-1 w-full" type="text" name="nis" :value="old('nis')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('nis')" />
                        </div>

                        {{-- Dropdown Pilihan Kelas BARU --}}
                        <div>
                            <x-input-label for="school_class_id" :value="__('Kelas')" />
                            <select name="school_class_id" id="school_class_id" class="block mt-1 w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" {{ old('school_class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('school_class_id')" />
                        </div>

                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end gap-4">
                        <a href="{{ route('admin.students.index') }}" class="text-sm font-medium">Batal</a>
                        <x-primary-button>Simpan Siswa</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
