@php
// Helper function untuk membuat link sortir
function sortable_link($title, $column, $sortBy, $sortDirection) {
    $direction = ($sortBy === $column && $sortDirection === 'asc') ? 'desc' : 'asc';
    $arrow = '';
    if ($sortBy === $column) {
        $arrow = $sortDirection === 'asc' ? '&#9650;' : '&#9660;';
    }
    $queryParams = array_merge(request()->query(), ['sort_by' => $column, 'sort_direction' => $direction]);
    return '<a href="' . route('admin.students.index', $queryParams) . '" class="flex items-center gap-2">' . $title . ' <span class="text-sky-500">' . $arrow . '</span></a>';
}
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'],
            ['title' => 'Siswa', 'url' => route('admin.students.index')]
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Manajemen Data Siswa') }}
        </h2>
    </x-slot>

    <div x-data="{ 
        showConfirmModal: false, 
        deleteUrl: '',
        selectedStudents: [],
        toggleAll(event) {
            const checkboxes = document.querySelectorAll('input[name=\'student_ids[]\']');
            this.selectedStudents = event.target.checked ? Array.from(checkboxes).map(cb => cb.value) : [];
        }
    }" class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-wrap gap-2 justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">Daftar Siswa</h3>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.students.qr', request()->query()) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-700 text-white rounded-md hover:bg-gray-700 dark:hover:bg-gray-600 text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h4.5a1.875 1.875 0 0 1 1.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 0 1-.937-.938v-3.375c0-.517.42-.938.938-.938z" /></svg>
                                Cetak Semua QR
                            </a>
                            <a href="{{ route('admin.students.import.form') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" /></svg>
                                Impor dari Excel
                            </a>
                            <a href="{{ route('admin.students.create') }}" class="inline-flex items-center px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Tambah Siswa
                            </a>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('admin.students.index') }}" class="mb-6">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                            <input type="hidden" name="sort_direction" value="{{ request('sort_direction') }}">
                            
                            <div class="relative flex-grow">
                                <x-text-input type="text" name="search" placeholder="Cari berdasarkan nama atau NIS..." value="{{ request('search') }}" class="w-full pl-10"/>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <select name="school_class_id" class="border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm text-sm">
                                    <option value="">Semua Kelas</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ request('school_class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                    @endforeach
                                </select>
                                
                                <div class="flex items-center gap-2">
                                    <label for="per_page" class="text-sm text-gray-500 dark:text-gray-400 flex-shrink-0">Tampilkan:</label>
                                    <select name="per_page" id="per_page" onchange="this.form.submit()" class="border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm text-sm">
                                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>
                                <x-primary-button type="submit">Filter</x-primary-button>
                            </div>
                        </div>
                    </form>

                    <!-- Tindakan Massal -->
                    <div x-show="selectedStudents.length > 0" class="mb-4 bg-slate-100 dark:bg-slate-700 p-4 rounded-lg flex items-center gap-4" style="display: none;">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200"><span x-text="selectedStudents.length"></span> siswa dipilih</span>
                        <form action="{{ route('admin.students.bulk_destroy') }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus semua siswa yang dipilih?');">
                            @csrf
                            <template x-for="studentId in selectedStudents" :key="studentId">
                                <input type="hidden" name="student_ids[]" :value="studentId">
                            </template>
                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">Hapus yang Dipilih</button>
                        </form>
                    </div>

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="p-4"><input type="checkbox" @click="toggleAll($event)" class="rounded border-gray-300 dark:border-slate-600 text-sky-600 focus:ring-sky-500"></th>
                                    <th scope="col" class="px-6 py-3">{!! sortable_link('Nama Siswa', 'name', $sortBy, $sortDirection) !!}</th>
                                    <th scope="col" class="px-6 py-3">{!! sortable_link('NIS', 'nis', $sortBy, $sortDirection) !!}</th>
                                    <th scope="col" class="px-6 py-3">{!! sortable_link('Kelas', 'class_name', $sortBy, $sortDirection) !!}</th>
                                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($students as $student)
                                    <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                                        <td class="p-4"><input type="checkbox" name="student_ids[]" x-model="selectedStudents" value="{{ $student->id }}" class="rounded border-gray-300 dark:border-slate-600 text-sky-600 focus:ring-sky-500"></td>
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ $student->name }}
                                        </th>
                                        <td class="px-6 py-4">
                                            {{ $student->nis }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ $student->schoolClass->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-4">
                                                <a href="{{ route('admin.students.edit', $student) }}" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</a>
                                                <button type="button" 
                                                        @click="showConfirmModal = true; deleteUrl = '{{ route('admin.students.destroy', $student) }}'"
                                                        class="font-medium text-red-600 dark:text-red-500 hover:underline">
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700">
                                        <td colspan="5" class="px-6 py-4 text-center">
                                            Tidak ada data siswa yang cocok dengan filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                     <div class="mt-4">
                        {{ $students->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <div x-show="showConfirmModal" 
             x-transition
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm" 
             style="display: none;">
            <div @click.away="showConfirmModal = false" 
                 x-show="showConfirmModal"
                 x-transition
                 class="w-full max-w-md p-6 bg-white dark:bg-slate-800 rounded-2xl shadow-xl">
                <div class="text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </div>
                    <h3 class="mt-5 text-lg font-medium text-gray-900 dark:text-white">Hapus Data Siswa?</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Apakah Anda yakin ingin menghapus data siswa ini? Semua data yang terhubung (termasuk data absensi) akan dihapus secara permanen.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex justify-center gap-4">
                    <form :action="deleteUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">
                            Ya, Hapus
                        </x-danger-button>
                    </form>
                    <x-secondary-button @click="showConfirmModal = false">
                        Batal
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
