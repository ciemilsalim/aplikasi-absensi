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
        showPromoteModal: false,
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
                    <!-- Petunjuk Singkat -->
                    <div class="mb-6 bg-blue-50 dark:bg-slate-700/50 border-l-4 border-blue-500 p-4 rounded-r-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-bold text-blue-800 dark:text-blue-300">Tips Manajemen Siswa</h3>
                                <div class="mt-1 text-sm text-blue-700 dark:text-blue-400">
                                    <p>Gunakan fitur <b>Naik Kelas</b> untuk memindahkan siswa terpilih ke tingkatan kelas baru secara kolektif di akhir tahun ajaran.</p>
                                </div>
                            </div>
                        </div>
                    </div>

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
                    <div x-show="selectedStudents.length > 0" class="mb-4 bg-slate-100 dark:bg-slate-700 p-4 rounded-lg flex flex-wrap items-center gap-6" style="display: none;">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200"><span x-text="selectedStudents.length"></span> siswa dipilih</span>
                        
                        <div class="flex items-center gap-4">
                            <!-- Tombol Naik Kelas -->
                            <button @click="showPromoteModal = true" class="inline-flex items-center text-sm font-medium text-sky-600 hover:text-sky-800 dark:text-sky-400 dark:hover:text-sky-300">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" /></svg>
                                Naik Kelas
                            </button>

                            <div class="h-4 w-px bg-slate-300 dark:bg-slate-600"></div>

                            <form action="{{ route('admin.students.bulk_destroy') }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus semua siswa yang dipilih?');">
                                @csrf
                                <template x-for="studentId in selectedStudents" :key="studentId">
                                    <input type="hidden" name="student_ids[]" :value="studentId">
                                </template>
                                <button type="submit" class="inline-flex items-center text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    Hapus yang Dipilih
                                </button>
                            </form>
                        </div>
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

        <!-- Modal Kenaikan Kelas Massal -->
        <div x-show="showPromoteModal" 
             x-transition
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm" 
             style="display: none;">
            <div @click.away="showPromoteModal = false" 
                 x-show="showPromoteModal"
                 x-transition
                 class="w-full max-w-lg p-6 bg-white dark:bg-slate-800 rounded-2xl shadow-xl">
                
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-sky-500"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" /></svg>
                        Kenaikan Kelas Kolektif
                    </h3>
                    <button @click="showPromoteModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Petunjuk Informasi -->
                <div class="mb-6 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 p-4 rounded-r-lg">
                    <h4 class="text-xs font-bold text-amber-800 dark:text-amber-400 uppercase tracking-wider mb-2">Penting: Petunjuk Kenaikan Kelas</h4>
                    <ul class="text-xs text-amber-700 dark:text-amber-500 space-y-1 list-disc ml-4">
                        <li>Gunakan fitur ini <b>hanya</b> saat periode akademik lama telah benar-benar berakhir.</li>
                        <li>Disarankan melakukan <b>Backup Data</b> melalui menu Backup sebelum pemindahan massal.</li>
                        <li>Setelah selesai, jangan lupa perbarui <b>Tahun Ajaran Aktif</b> di menu Periode Akademik.</li>
                    </ul>
                </div>

                <form action="{{ route('admin.students.bulk_promote') }}" method="POST">
                    @csrf
                    <template x-for="studentId in selectedStudents" :key="studentId">
                        <input type="hidden" name="student_ids[]" :value="studentId">
                    </template>

                    <div class="mb-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Anda akan memindahkan <span class="font-bold text-sky-600" x-text="selectedStudents.length"></span> siswa terpilih ke kelas tujuan di bawah ini:
                        </p>
                        
                        <label for="target_class_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pilih Kelas Tujuan</label>
                        <select name="target_class_id" id="target_class_id" required class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 focus:ring-sky-500 rounded-xl shadow-sm transition-colors">
                            <option value="">-- Pilih Kelas Tujuan --</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-[10px] text-gray-500 italic">* Siswa akan langsung terdaftar di kelas tujuan setelah konfirmasi.</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <x-secondary-button @click="showPromoteModal = false" type="button">
                            Batal
                        </x-secondary-button>
                        <x-primary-button type="submit" class="bg-sky-600 hover:bg-sky-700 focus:bg-sky-700 active:bg-sky-800">
                            Konfirmasi Naik Kelas
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
