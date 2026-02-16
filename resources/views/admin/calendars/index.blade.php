<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Kalender Pendidikan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="mb-6 flex justify-between items-center">
             <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Kalender Pendidikan') }}
            </h2>
            <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'import-calendar')" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                </svg>
                Import Excel
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Form Tambah Agenda -->
                <div class="md:col-span-1">
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Tambah Agenda</h3>
                            <form action="{{ route('admin.calendars.store') }}" method="POST">
                                @csrf
                                <div class="space-y-4">
                                    <div>
                                        <x-input-label for="title" :value="__('Judul Agenda')" />
                                        <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title')" required />
                                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="start_date" :value="__('Tanggal Mulai')" />
                                        <x-text-input id="start_date" class="block mt-1 w-full" type="date" name="start_date" :value="old('start_date')" required />
                                        <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="end_date" :value="__('Tanggal Selesai (Opsional)')" />
                                        <x-text-input id="end_date" class="block mt-1 w-full" type="date" name="end_date" :value="old('end_date')" />
                                        <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="description" :value="__('Deskripsi')" />
                                        <textarea id="description" name="description" class="block mt-1 w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm" rows="3">{{ old('description') }}</textarea>
                                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                    </div>

                                    <div class="flex items-center">
                                        <input id="is_holiday" type="checkbox" name="is_holiday" class="w-4 h-4 text-sky-600 bg-gray-100 border-gray-300 rounded focus:ring-sky-500 dark:focus:ring-sky-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" checked>
                                        <label for="is_holiday" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Tandai sebagai Hari Libur</label>
                                    </div>

                                    <div class="flex justify-end">
                                        <x-primary-button>Simpan</x-primary-button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Daftar Agenda -->
                <div class="md:col-span-2">
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Daftar Agenda & Libur</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                    <thead class="bg-gray-50 dark:bg-slate-700">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Agenda</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                        @forelse ($calendars as $calendar)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ \Carbon\Carbon::parse($calendar->start_date)->format('d M Y') }}
                                                    @if($calendar->end_date)
                                                        - {{ \Carbon\Carbon::parse($calendar->end_date)->format('d M Y') }}
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $calendar->title }}</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $calendar->description }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($calendar->is_holiday)
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Libur</span>
                                                    @else
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Kegiatan</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <form action="{{ route('admin.calendars.destroy', $calendar) }}" method="POST" onsubmit="return confirm('Hapus agenda ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Hapus</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada agenda.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Import -->
    <x-modal name="import-calendar" focusable>
        <form method="post" action="{{ route('admin.calendars.import') }}" class="p-6" enctype="multipart/form-data">
            @csrf
            
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Import Data Agenda') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Silakan unggah file Excel (.xlsx, .xls) yang berisi data agenda. Pastikan format kolom sesuai: title, start_date, end_date, description, is_holiday.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="file" value="{{ __('File Excel') }}" class="sr-only" />
                <input id="file" name="file" type="file" class="block w-full text-sm text-slate-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-sky-50 file:text-sky-700
                    hover:file:bg-sky-100 dark:file:bg-slate-700 dark:file:text-slate-300
                " accept=".xlsx, .xls, .csv" required />
                <x-input-error :messages="$errors->get('file')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Batal') }}
                </x-secondary-button>

                <x-primary-button class="ml-3">
                    {{ __('Import') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
