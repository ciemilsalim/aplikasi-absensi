<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Pengaturan Aplikasi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.settings.update') }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Pengaturan Waktu Absensi</h3>

                        @if (session('success'))
                            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
                                <p>{{ session('success') }}</p>
                            </div>
                        @endif

                        {{-- Form Pengaturan Waktu --}}
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

                        {{-- Pengaturan Tampilan Baru --}}
                        <div class="border-t border-gray-200 dark:border-slate-700 pt-6 mt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Pengaturan Tampilan</h3>
                            <label for="dark_mode" class="flex items-center justify-between cursor-pointer mt-4">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-300">Aktifkan Mode Gelap</span>
                                <div class="relative">
                                    <input type="checkbox" id="dark_mode" name="dark_mode" class="sr-only peer"
                                           {{ ($settings['dark_mode'] ?? 'off') === 'on' ? 'checked' : '' }}
                                           onchange="toggleDarkMode(this.checked)">
                                    <div class="block bg-gray-200 peer-checked:bg-sky-600 w-14 h-8 rounded-full"></div>
                                    <div class="absolute left-1 top-1 bg-white border-gray-300 border peer-checked:border-white w-6 h-6 rounded-full transition-transform peer-checked:translate-x-full"></div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end">
                        <x-primary-button>
                            {{ __('Simpan Pengaturan') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        // Fungsi ini mengontrol toggle switch dan menyimpan preferensi ke localStorage
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
