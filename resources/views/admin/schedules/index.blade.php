<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Administrasi', 'url' => '#'],
            ['title' => 'Jadwal Pelajaran', 'url' => route('admin.schedules.index')]
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Jadwal Pelajaran') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">Pilih Kelas</h3>
                    <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                        Silakan pilih kelas untuk melihat, menambah, atau mengelola jadwal pelajaran.
                    </p>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        @forelse ($classes as $class)
                            <a href="{{ route('admin.schedules.show', $class->id) }}"
                               class="block p-4 text-center bg-slate-50 dark:bg-slate-700/50 rounded-lg shadow-sm hover:bg-sky-100 dark:hover:bg-slate-700 transition-colors duration-200">
                                <span class="font-semibold text-sky-600 dark:text-sky-400">{{ $class->name }}</span>
                                <!-- PERBAIKAN FINAL: Gunakan null coalescing operator untuk keamanan data -->
                                <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $class->level->name ?? 'Tanpa Tingkat' }}</span>
                            </a>
                        @empty
                            <div class="col-span-full text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400">
                                    Tidak ada data kelas. Silakan <a href="{{ route('admin.classes.create') }}" class="text-sky-500 hover:underline">tambahkan kelas</a> terlebih dahulu.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
