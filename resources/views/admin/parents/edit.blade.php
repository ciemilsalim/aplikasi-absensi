<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data Ortu', 'url' => route('admin.parents.index')],
            ['title' => 'Hubungkan Siswa', 'url' => '#']
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Hubungkan Siswa ke Akun: ') }} <span class="text-sky-600">{{ $parent->name }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 sm:rounded-lg" role="alert"><p>{{ session('success') }}</p></div>
            @endif
            
            {{-- Menambahkan Alpine.js untuk mengelola state pencarian --}}
            <div x-data="{ searchLinked: '', searchNotLinked: '' }">
                <form action="{{ route('admin.parents.update', $parent) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Kolom Siswa Terhubung -->
                        <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Siswa Terhubung ({{ $studentsLinked->count() }})</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Centang untuk memutuskan hubungan siswa dari akun ini.</p>
                            
                            {{-- Form Pencarian BARU --}}
                            <div class="mb-4">
                                <x-text-input x-model.debounce.300ms="searchLinked" type="text" class="w-full" placeholder="Cari siswa terhubung..."/>
                            </div>

                            <div class="max-h-96 overflow-y-auto space-y-2 pr-2">
                                @forelse ($studentsLinked as $student)
                                    <label x-show="searchLinked === '' || '{{ strtolower($student->name) }}'.includes(searchLinked.toLowerCase())" class="flex items-center p-3 rounded-md bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer">
                                        <input type="checkbox" name="students_to_remove[]" value="{{ $student->id }}" class="rounded dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-red-600 shadow-sm focus:ring-red-500">
                                        <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $student->name }} 
                                            <span class="text-xs text-gray-400">({{ $student->schoolClass->name ?? 'Tanpa Kelas' }})</span>
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">Belum ada siswa yang terhubung.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Kolom Siswa Belum Terhubung -->
                        <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Siswa Belum Terhubung ({{ $studentsNotLinked->count() }})</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Centang untuk menghubungkan siswa ke akun ini.</p>
                            
                            {{-- Form Pencarian BARU --}}
                            <div class="mb-4">
                                <x-text-input x-model.debounce.300ms="searchNotLinked" type="text" class="w-full" placeholder="Cari siswa belum terhubung..."/>
                            </div>

                             <div class="max-h-96 overflow-y-auto space-y-2 pr-2">
                                @forelse ($studentsNotLinked as $student)
                                    <label x-show="searchNotLinked === '' || '{{ strtolower($student->name) }}'.includes(searchNotLinked.toLowerCase())" class="flex items-center p-3 rounded-md bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer">
                                        <input type="checkbox" name="students_to_add[]" value="{{ $student->id }}" class="rounded dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-sky-600 shadow-sm focus:ring-sky-500">
                                        <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $student->name }}
                                            <span class="text-xs text-gray-400">({{ $student->schoolClass->name ?? 'Tanpa Kelas' }})</span>
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">Semua siswa sudah terhubung dengan orang tua.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="mt-6 flex justify-end">
                        <x-primary-button type="submit">
                            Simpan Perubahan
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
