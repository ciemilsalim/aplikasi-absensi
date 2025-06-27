<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Pengumuman', 'url' => route('admin.announcements.index')],
            ['title' => 'Edit', 'url' => '#']
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Pengumuman') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.announcements.update', $announcement) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="p-6 space-y-6">
                        <div>
                            <x-input-label for="title" :value="__('Judul Pengumuman')" />
                            <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title', $announcement->title)" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('title')" />
                        </div>

                        <div>
                            <x-input-label for="content" :value="__('Isi Konten')" />
                            <textarea id="content" name="content" rows="10" class="block w-full mt-1 border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">{{ old('content', $announcement->content) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('content')" />
                        </div>

                        <div class="flex items-center">
                            <input id="publish_now" name="publish_now" type="checkbox" value="1" class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-600 dark:bg-slate-800 dark:border-slate-600"
                                {{ $announcement->published_at ? 'checked' : '' }}>
                            <label for="publish_now" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Publikasikan sekarang juga</label>
                        </div>
                        @if($announcement->published_at)
                            <p class="text-xs text-gray-500 dark:text-gray-400">Saat ini sudah dipublikasikan pada: {{ $announcement->published_at->translatedFormat('d F Y, H:i') }}</p>
                        @else
                             <p class="text-xs text-gray-500 dark:text-gray-400">Saat ini disimpan sebagai draft.</p>
                        @endif
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end gap-4">
                        <a href="{{ route('admin.announcements.index') }}" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                            Batal
                        </a>
                        <x-primary-button>
                            {{ __('Perbarui Pengumuman') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
