<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Data', 'url' => '#'],
            ['title' => 'Mata Pelajaran', 'url' => route('admin.subjects.index')],
            ['title' => 'Edit', 'url' => '#']
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit Mata Pelajaran: {{ $subject->name }}</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.subjects.update', $subject) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="p-6 space-y-6">
                        <div>
                            <x-input-label for="name" :value="__('Nama Mata Pelajaran')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $subject->name)" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <x-input-label for="code" :value="__('Kode Mata Pelajaran (Opsional)')" />
                            <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" :value="old('code', $subject->code)" />
                            <x-input-error class="mt-2" :messages="$errors->get('code')" />
                        </div>
                         <div>
                            <x-input-label for="description" :value="__('Deskripsi (Opsional)')" />
                            <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm">{{ old('description', $subject->description) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end gap-4">
                        <a href="{{ route('admin.subjects.index') }}" class="text-sm font-medium">Batal</a>
                        <x-primary-button>Perbarui</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
