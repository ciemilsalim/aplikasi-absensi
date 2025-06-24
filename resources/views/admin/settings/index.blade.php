<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Pengaturan', 'url' => route('admin.settings.index')]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Pengaturan Aplikasi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div x-data="{ activeTab: 'identitas' }" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                
                <!-- Sidebar Navigasi Pengaturan -->
                <div class="md:col-span-1">
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                        <ul class="space-y-1">
                            <li>
                                <button @click="activeTab = 'identitas'" :class="{ 'bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-400': activeTab === 'identitas', 'hover:bg-gray-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300': activeTab !== 'identitas' }" class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm font-medium rounded-md transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18" /></svg>
                                    Identitas
                                </button>
                            </li>
                            <li>
                                <button @click="activeTab = 'tampilan'" :class="{ 'bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-400': activeTab === 'tampilan', 'hover:bg-gray-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300': activeTab !== 'tampilan' }" class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm font-medium rounded-md transition">
                                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.158 0a.225.225 0 0 1 .225.225V8.7a.225.225 0 0 1-.225.225h-.008a.225.225 0 0 1-.225-.225V8.475a.225.225 0 0 1 .225-.225h.008Z" /></svg>
                                    Tampilan & Logo
                                </button>
                            </li>
                             <li>
                                <button @click="activeTab = 'absensi'" :class="{ 'bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-400': activeTab === 'absensi', 'hover:bg-gray-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300': activeTab !== 'absensi' }" class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm font-medium rounded-md transition">
                                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    Absensi
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Konten Form -->
                <div class="md:col-span-3">
                     <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                @if (session('success'))
                                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p>{{ session('success') }}</p></div>
                                @endif
                                
                                <!-- Tab Identitas Sekolah -->
                                <div x-show="activeTab === 'identitas'" x-transition class="space-y-6">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Identitas Sekolah</h3>
                                    <div>
                                        <x-input-label for="school_name" :value="__('Nama Sekolah')" />
                                        <x-text-input id="school_name" class="block mt-1 w-full" type="text" name="school_name" :value="old('school_name', $settings['school_name'] ?? config('app.name', 'AbsensiSiswa'))" required />
                                        <x-input-error class="mt-2" :messages="$errors->get('school_name')" />
                                    </div>
                                    <div>
                                        <x-input-label for="school_address" :value="__('Alamat Sekolah')" />
                                        <textarea id="school_address" name="school_address" rows="3" class="block w-full mt-1 border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">{{ old('school_address', $settings['school_address'] ?? '') }}</textarea>
                                        <x-input-error class="mt-2" :messages="$errors->get('school_address')" />
                                    </div>
                                </div>
        
                                <!-- Tab Tampilan & Logo -->
                                <div x-show="activeTab === 'tampilan'" x-transition class="space-y-6">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Tampilan & Logo</h3>
                                    <div>
                                        <x-input-label :value="__('Logo Saat Ini')" />
                                        @if (isset($settings['app_logo']))
                                            <img src="{{ asset('storage/' . $settings['app_logo']) }}" alt="Logo saat ini" class="h-16 w-auto mt-2 rounded-md bg-white p-1 shadow">
                                        @else
                                            <div class="mt-2 flex items-center justify-center h-16 w-16 bg-slate-100 dark:bg-slate-700 rounded-md"><svg class="w-8 h-8 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.158 0a.225.225 0 0 1 .225.225V8.7a.225.225 0 0 1-.225.225h-.008a.225.225 0 0 1-.225-.225V8.475a.225.225 0 0 1 .225-.225h.008Z" /></svg></div>
                                        @endif
                                    </div>
                                    <div>
                                        <x-input-label for="app_logo" :value="__('Unggah Logo Baru (Opsional)')" />
                                        <x-text-input id="app_logo" class="block mt-1 w-full" type="file" name="app_logo" />
                                        <x-input-error class="mt-2" :messages="$errors->get('app_logo')" />
                                    </div>
                                    <label for="dark_mode" class="flex items-center justify-between cursor-pointer pt-4 border-t border-gray-200 dark:border-slate-700">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-300">Aktifkan Mode Gelap</span>
                                        <div class="relative"><input type="checkbox" id="dark_mode" name="dark_mode" class="sr-only peer" {{ ($settings['dark_mode'] ?? 'off') === 'on' ? 'checked' : '' }} onchange="toggleDarkMode(this.checked)"><div class="block bg-gray-200 peer-checked:bg-sky-600 w-14 h-8 rounded-full"></div><div class="absolute left-1 top-1 bg-white border-gray-300 border peer-checked:border-white w-6 h-6 rounded-full transition-transform peer-checked:translate-x-full"></div></div>
                                    </label>
                                </div>
        
                                <!-- Tab Waktu Absensi -->
                                <div x-show="activeTab === 'absensi'" x-transition class="space-y-6">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Waktu Absensi</h3>
                                     <div>
                                        <x-input-label for="jam_masuk" :value="__('Batas Jam Masuk')" />
                                        <x-text-input id="jam_masuk" class="block mt-1 w-full" type="time" name="jam_masuk" :value="old('jam_masuk', $settings['jam_masuk'] ?? '07:30')" required />
                                        <x-input-error class="mt-2" :messages="$errors->get('jam_masuk')" />
                                    </div>
                                    <div>
                                        <x-input-label for="jam_pulang" :value="__('Jadwal Jam Pulang')" />
                                        <x-text-input id="jam_pulang" class="block mt-1 w-full" type="time" name="jam_pulang" :value="old('jam_pulang', $settings['jam_pulang'] ?? '16:00')" required />
                                        <x-input-error class="mt-2" :messages="$errors->get('jam_pulang')" />
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end mt-6">
                                <x-primary-button>Simpan Pengaturan</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
    <script>
        function toggleDarkMode(isChecked) {
            if (isChecked) {
                localStorage.setItem('darkMode', 'on');
                document.documentElement.classList.add('dark');
            } else {
                localStorage.setItem('darkMode', 'off');
                document.documentElement.classList.remove('dark');
            }
        }
    </script>
    @endpush
</x-app-layout>
