<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'],
            ['title' => 'Data Kelas', 'url' => route('admin.classes.index')],
            ['title' => 'Atur Guru Mapel', 'url' => '#']
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Atur Guru Mata Pelajaran untuk Kelas: {{ $schoolClass->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.classes.store_teacher_assignment', $schoolClass) }}" method="POST">
                    @csrf
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <p class="mb-6 text-gray-600 dark:text-gray-400">
                            Pilih guru yang akan mengajar setiap mata pelajaran di kelas ini. Kosongkan pilihan jika tidak ada guru yang ditugaskan.
                        </p>

                        <div class="space-y-6">
                            @forelse ($subjects as $subject)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                                    <div>
                                        <label for="teacher_for_{{ $subject->id }}" class="font-medium text-sm text-gray-700 dark:text-gray-300">
                                            {{ $subject->name }}
                                        </label>
                                    </div>
                                    <div>
                                        <select name="assignments[{{ $subject->id }}][teacher_id]" id="teacher_for_{{ $subject->id }}" 
                                                class="block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm">
                                            <option value="">-- Pilih Guru --</option>
                                            @foreach ($teachers as $teacher)
                                                <option value="{{ $teacher->id }}" 
                                                    {{ (isset($assignments[$subject->id]) && $assignments[$subject->id] == $teacher->id) ? 'selected' : '' }}>
                                                    {{ $teacher->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-gray-500 dark:text-gray-400">
                                    Tidak ada data mata pelajaran. Silakan <a href="{{ route('admin.subjects.create') }}" class="text-sky-500 hover:underline">tambahkan mata pelajaran</a> terlebih dahulu.
                                </p>
                            @endforelse
                        </div>
                    </div>

                    @if($subjects->isNotEmpty())
                    <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end gap-4">
                        <a href="{{ route('admin.classes.index') }}" class="text-sm font-medium">Batal</a>
                        <x-primary-button>Simpan Pengaturan</x-primary-button>
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
