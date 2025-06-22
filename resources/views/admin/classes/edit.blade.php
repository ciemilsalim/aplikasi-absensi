<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'],
            ['title' => 'Data Kelas', 'url' => route('admin.classes.index')],
            ['title' => 'Edit Kelas', 'url' => '#']
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Kelas: ' . $schoolClass->name) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                {{-- PERBAIKAN: Secara eksplisit memberikan parameter 'class' pada rute --}}
                <form action="{{ route('admin.classes.update', ['class' => $schoolClass->id]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="p-6">
                        <div class="space-y-6">
                            <div>
                                <x-input-label for="name" value="Nama Kelas" />
                                <x-text-input id="name" class="block w-full mt-1" type="text" name="name" :value="old('name', $schoolClass->name)" required />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="teacher_id" value="Wali Kelas" />
                                <select name="teacher_id" id="teacher_id" class="block w-full mt-1 border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">
                                    <option value="">-- Tidak ada wali kelas --</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" 
                                            {{ old('teacher_id', $schoolClass->teacher_id) == $teacher->id ? 'selected' : '' }} 
                                            {{-- Nonaktifkan jika guru ini sudah menjadi wali kelas lain --}}
                                            {{ $teacher->homeroomClass && $teacher->homeroomClass->id != $schoolClass->id ? 'disabled' : '' }}>
                                            {{ $teacher->name }} 
                                            @if($teacher->homeroomClass && $teacher->homeroomClass->id != $schoolClass->id)
                                                (Wali di {{ $teacher->homeroomClass->name }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end gap-4">
                        <a href="{{ route('admin.classes.index') }}" class="text-sm font-medium">Batal</a>
                        <x-primary-button>Perbarui Kelas</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
