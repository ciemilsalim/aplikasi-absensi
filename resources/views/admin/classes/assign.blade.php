<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'],
            ['title' => 'Data Kelas', 'url' => route('admin.classes.index')],
            ['title' => 'Atur Siswa', 'url' => '#']
        ]" class="mb-4" />
        {{-- PERBAIKAN: Menggunakan variabel $class --}}
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Atur Siswa untuk Kelas: <span class="text-sky-600">{{ $class->name }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 sm:rounded-lg" role="alert"><p>{{ session('success') }}</p></div>
            @endif
            
            <form x-data="{
                studentsToAdd: [],
                studentsToRemove: [],
                toggleAllToAdd(event) {
                    const checkboxes = document.querySelectorAll('input[name=\'students_to_add[]\']');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = event.target.checked;
                    });
                },
                toggleAllToRemove(event) {
                    const checkboxes = document.querySelectorAll('input[name=\'students_to_remove[]\']');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = event.target.checked;
                    });
                }
            }" action="{{ route('admin.classes.assign.students') }}" method="POST">
                @csrf
                {{-- PERBAIKAN: Menggunakan variabel $class --}}
                <input type="hidden" name="school_class_id" value="{{ $class->id }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Kolom Siswa di dalam Kelas -->
                    <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Siswa di Kelas Ini ({{ $studentsInClass->count() }})</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Centang untuk mengeluarkan siswa dari kelas ini.</p>
                        
                        @if($studentsInClass->isNotEmpty())
                        <div class="mb-2 border-b pb-2 dark:border-slate-700">
                            <label class="flex items-center">
                                <input type="checkbox" @click="toggleAllToRemove" class="rounded dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-red-600 shadow-sm focus:ring-red-500">
                                <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Semua</span>
                            </label>
                        </div>
                        @endif

                        <div class="max-h-96 overflow-y-auto space-y-2 pr-2">
                            @forelse ($studentsInClass as $student)
                                <label class="flex items-center p-3 rounded-md bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer">
                                    <input type="checkbox" name="students_to_remove[]" value="{{ $student->id }}" class="rounded dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-red-600 shadow-sm focus:ring-red-500">
                                    <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">{{ $student->name }} <span class="text-xs text-gray-400">(NIS: {{ $student->nis }})</span></span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">Belum ada siswa di kelas ini.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Kolom Siswa Tanpa Kelas -->
                    <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Siswa Tanpa Kelas ({{ $studentsWithoutClass->count() }})</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Centang untuk memasukkan siswa ke kelas ini.</p>
                         
                         @if($studentsWithoutClass->isNotEmpty())
                         <div class="mb-2 border-b pb-2 dark:border-slate-700">
                            <label class="flex items-center">
                                <input type="checkbox" @click="toggleAllToAdd" class="rounded dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-sky-600 shadow-sm focus:ring-sky-500">
                                <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Semua</span>
                            </label>
                        </div>
                        @endif

                         <div class="max-h-96 overflow-y-auto space-y-2 pr-2">
                            @forelse ($studentsWithoutClass as $student)
                                <label class="flex items-center p-3 rounded-md bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer">
                                    <input type="checkbox" name="students_to_add[]" value="{{ $student->id }}" class="rounded dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-sky-600 shadow-sm focus:ring-sky-500">
                                    <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">{{ $student->name }} <span class="text-xs text-gray-400">(NIS: {{ $student->nis }})</span></span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">Semua siswa sudah memiliki kelas.</p>
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
</x-app-layout>
