<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'], // Item tanpa link
            ['title' => 'Wali', 'url' => route('admin.parents.index')],
            ['title' => 'Hubungkan Siswa', 'url' => route('admin.parents.edit', $parent)]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Hubungkan Siswa ke: <span class="text-sky-600">{{ $parent->name }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 sm:rounded-lg" role="alert"><p>{{ session('success') }}</p></div>
            @endif
            <form action="{{ route('admin.parents.update', $parent) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Kolom Siswa yang sudah terhubung -->
                    <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Siswa Terhubung ({{ $studentsLinked->count() }})</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Centang untuk memutuskan hubungan.</p>
                        <div class="max-h-96 overflow-y-auto space-y-2 pr-2">
                            @forelse ($studentsLinked as $student)
                                <label class="flex items-center p-2 rounded-md bg-slate-50 dark:bg-slate-700">
                                    <input type="checkbox" name="students_to_remove[]" value="{{ $student->id }}" class="rounded dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-red-600 shadow-sm focus:ring-red-500">
                                    <span class="ml-3 text-sm text-gray-600 dark:text-gray-300">{{ $student->name }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada siswa yang terhubung.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Kolom Siswa yang belum terhubung -->
                    <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Siswa Belum Terhubung ({{ $studentsNotLinked->count() }})</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Centang untuk menghubungkan.</p>
                         <div class="max-h-96 overflow-y-auto space-y-2 pr-2">
                            @forelse ($studentsNotLinked as $student)
                                <label class="flex items-center p-2 rounded-md bg-slate-50 dark:bg-slate-700">
                                    <input type="checkbox" name="students_to_add[]" value="{{ $student->id }}" class="rounded dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-sky-600 shadow-sm focus:ring-sky-500">
                                    <span class="ml-3 text-sm text-gray-600 dark:text-gray-300">{{ $student->name }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">Semua siswa sudah terhubung dengan wali.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="mt-6 flex justify-end">
                    <x-primary-button type="submit">
                        Simpan Perubahan Hubungan
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
