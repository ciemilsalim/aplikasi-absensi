<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'],
            ['title' => 'Siswa', 'url' => route('admin.students.index')]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Manajemen Data Siswa') }}
        </h2>
    </x-slot>

    <div x-data="{ showConfirmModal: false, deleteUrl: '' }" class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-wrap gap-2 justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">Daftar Siswa</h3>
                        <div class="flex gap-2">
                            {{-- PERBAIKAN: Menambahkan parameter filter ke link cetak QR --}}
                            <a href="{{ route('admin.students.qr', request()->query()) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-700 text-white rounded-md hover:bg-gray-700 dark:hover:bg-gray-600 text-sm font-medium">
                                Cetak Semua QR
                            </a>
                            <a href="{{ route('admin.students.import.form') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                                Impor dari Excel
                            </a>
                            <a href="{{ route('admin.students.create') }}" class="inline-flex items-center px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 text-sm font-medium">
                                Tambah Siswa
                            </a>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif

                    {{-- Form Pencarian & Filter --}}
                    <form method="GET" action="{{ route('admin.students.index') }}" class="mb-6 flex flex-col sm:flex-row gap-4">
                        <div class="relative flex-grow">
                            <x-text-input type="text" name="search" placeholder="Cari berdasarkan nama atau NIS..." value="{{ request('search') }}" class="w-full pl-10"/>
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <select name="school_class_id" class="border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm text-sm">
                                <option value="">Semua Kelas</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ request('school_class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                @endforeach
                            </select>
                            <x-primary-button type="submit">Filter</x-primary-button>
                        </div>
                    </form>

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Siswa</th>
                                    <th scope="col" class="px-6 py-3">NIS</th>
                                    <th scope="col" class="px-6 py-3">Kelas</th>
                                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($students as $student)
                                    <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
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
                                        <td colspan="4" class="px-6 py-4 text-center">
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
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
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
