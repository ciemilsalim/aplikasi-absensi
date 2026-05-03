<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Edit Ekstrakurikuler') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100 dark:border-slate-700">
                <div class="p-8">
                    <form action="{{ route('admin.extracurriculars.update', $extracurricular) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')
                        
                        <div>
                            <x-input-label for="name" value="Nama Ekstrakurikuler" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-xl" :value="old('name', $extracurricular->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" value="Deskripsi" />
                            <textarea id="description" name="description" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm h-32">{{ old('description', $extracurricular->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="teacher_id" value="Pembina (Guru)" />
                            <select id="teacher_id" name="teacher_id" class="mt-1 block w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
                                <option value="">Pilih Pembina...</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ $extracurricular->teacher_id == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100 dark:border-slate-700">
                            <a href="{{ route('admin.extracurriculars.index') }}" class="px-6 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 transition-colors">Batal</a>
                            <x-primary-button class="px-8 py-3 rounded-xl shadow-lg shadow-blue-100 dark:shadow-none">
                                Update Ekstrakurikuler
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
