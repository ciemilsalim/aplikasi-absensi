@php
// Helper function untuk membuat link sortir
function sortable_link($title, $column, $sortBy, $sortDirection) {
    $direction = ($sortBy === $column && $sortDirection === 'asc') ? 'desc' : 'asc';
    $arrow = '';
    if ($sortBy === $column) {
        $arrow = $sortDirection === 'asc' ? '&#9650;' : '&#9660;';
    }
    $queryParams = array_merge(request()->query(), ['sort_by' => $column, 'sort_direction' => $direction]);
    return '<a href="' . route('admin.classes.index', $queryParams) . '" class="flex items-center gap-2">' . $title . ' <span class="text-sky-500">' . $arrow . '</span></a>';
}
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'],
            ['title' => 'Data Kelas', 'url' => route('admin.classes.index')]
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Manajemen Data Kelas') }}
        </h2>
    </x-slot>

    <div x-data="{ showConfirmModal: false, deleteUrl: '' }" class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <form action="{{ route('admin.classes.store') }}" method="POST">
                    @csrf
                    <div class="p-6">
                        <h3 class="font-medium text-lg text-gray-900 dark:text-gray-100 mb-4">Tambah Kelas Baru</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            <div>
                                <x-input-label for="name" value="Nama Kelas" />
                                <x-text-input id="name" class="block w-full mt-1" type="text" name="name" :value="old('name')" placeholder="Contoh: Kelas 10-A" required />
                            </div>
                            <div>
                                <x-input-label for="teacher_id" value="Wali Kelas" />
                                <select name="teacher_id" id="teacher_id" class="block w-full mt-1 border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                                    <option value="">-- Tidak ada wali kelas --</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }} {{ $teacher->homeroomClass ? 'disabled' : '' }}>
                                            {{ $teacher->name }}
                                            @if($teacher->homeroomClass)
                                                (Wali di {{ $teacher->homeroomClass->name }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-primary-button type="submit">Simpan Kelas</x-primary-button>
                            </div>
                        </div>
                         <x-input-error :messages="$errors->get('name')" class="mt-2" />
                         <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="font-medium text-lg mb-4">Daftar Kelas</h3>
                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('success') }}</p></div>
                    @endif

                    <form method="GET" action="{{ route('admin.classes.index') }}" class="mb-6">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                            <input type="hidden" name="sort_direction" value="{{ request('sort_direction') }}">
                            
                            <div class="relative flex-grow">
                                <x-text-input type="text" name="search" placeholder="Cari nama kelas atau wali kelas..." value="{{ request('search') }}" class="w-full pl-10"/>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <label for="per_page" class="text-sm text-gray-500 dark:text-gray-400 flex-shrink-0">Tampilkan:</label>
                                    <select name="per_page" id="per_page" onchange="this.form.submit()" class="border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm text-sm">
                                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>
                                <x-primary-button type="submit">Cari</x-primary-button>
                            </div>
                        </div>
                    </form>

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">{!! sortable_link('Nama Kelas', 'name', $sortBy, $sortDirection) !!}</th>
                                    <th scope="col" class="px-6 py-3">{!! sortable_link('Wali Kelas', 'teacher_name', $sortBy, $sortDirection) !!}</th>
                                    <th scope="col" class="px-6 py-3">{!! sortable_link('Jumlah Siswa', 'students_count', $sortBy, $sortDirection) !!}</th>
                                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($classes as $class)
                                <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $class->name }}</td>
                                    <td class="px-6 py-4">{{ $class->homeroomTeacher->name ?? '-' }}</td>
                                    <td class="px-6 py-4">{{ $class->students_count }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-4">
                                            <a href="{{ route('admin.classes.assign_teacher', $class) }}" class="font-medium text-green-600 dark:text-green-500 hover:underline">Atur Guru</a>
                                            <a href="{{ route('admin.classes.assign', $class) }}" class="font-medium text-green-600 dark:text-green-500 hover:underline">Atur Siswa</a>
                                            <a href="{{ route('admin.classes.edit', $class) }}" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</a>
                                            <button type="button" 
                                                    @click="showConfirmModal = true; deleteUrl = '{{ route('admin.classes.destroy', $class) }}'"
                                                    class="font-medium text-red-600 dark:text-red-500 hover:underline">
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="px-6 py-4 text-center">Belum ada data kelas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $classes->appends(request()->query())->links() }}</div>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <div x-show="showConfirmModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm" style="display: none;">
            <div @click.away="showConfirmModal = false" x-show="showConfirmModal" x-transition class="w-full max-w-md p-6 bg-white dark:bg-slate-800 rounded-2xl shadow-xl">
                <div class="text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </div>
                    <h3 class="mt-5 text-lg font-medium text-gray-900 dark:text-white">Hapus Data Kelas?</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Apakah Anda yakin ingin menghapus kelas ini? Tindakan ini akan melepaskan semua siswa dari kelas ini, namun tidak akan menghapus data siswa.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex justify-center gap-4">
                    <form :action="deleteUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">Ya, Hapus</x-danger-button>
                    </form>
                    <x-secondary-button @click="showConfirmModal = false">Batal</x-secondary-button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
