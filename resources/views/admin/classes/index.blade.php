@php
// Definisikan data untuk breadcrumb halaman ini
$breadcrumbs = [
    ['title' => 'Data', 'url' => '#'], // Item tanpa link
    ['title' => 'Kelas', 'url' => route('admin.classes.index')]
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Manajemen Data Kelas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-breadcrumb :breadcrumbs="$breadcrumbs" />
            {{-- Form Tambah Kelas --}}
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <form action="{{ route('admin.classes.store') }}" method="POST">
                    @csrf
                    <div class="p-6">
                        <h3 class="font-medium text-lg text-gray-900 dark:text-gray-100 mb-4">Tambah Kelas Baru</h3>
                        <div class="flex gap-2 items-start">
                            <div class="flex-grow">
                                <x-text-input id="name" class="block w-full" type="text" name="name" placeholder="Contoh: Kelas 10-A" required />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <x-primary-button>Simpan</x-primary-button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Tabel Daftar Kelas --}}
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="font-medium text-lg mb-4">Daftar Kelas</h3>
                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('success') }}</p></div>
                    @endif
                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Kelas</th>
                                    <th scope="col" class="px-6 py-3">Jumlah Siswa</th>
                                    <th scope="col" class="px-6 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($classes as $class)
                                <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $class->name }}</td>
                                    <td class="px-6 py-4">{{ $class->students_count }}</td>
                                    <td class="px-6 py-4 flex gap-4">
                                        <a href="{{ route('admin.classes.assign', $class) }}" class="font-medium text-green-600 dark:text-green-500 hover:underline">Atur Siswa</a>
                                        {{-- Aksi Edit & Hapus bisa ditambahkan di sini --}}
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="px-6 py-4 text-center">Belum ada data kelas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $classes->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
