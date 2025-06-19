<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pengaturan Aplikasi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.settings.update') }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-6">
                        <h3 class="text-lg font-medium text-gray-900">Pengaturan Waktu Absensi</h3>

                        @if (session('success'))
                            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
                                <p>{{ session('success') }}</p>
                            </div>
                        @endif

                        <div>
                            <x-input-label for="jam_masuk" :value="__('Batas Jam Masuk (Terlambat jika melewati jam ini)')" />
                            <x-text-input id="jam_masuk" class="block mt-1 w-full" type="time" name="jam_masuk" :value="old('jam_masuk', $settings['jam_masuk'] ?? '07:30')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('jam_masuk')" />
                        </div>

                        <div>
                            <x-input-label for="jam_pulang" :value="__('Jadwal Jam Pulang')" />
                            <x-text-input id="jam_pulang" class="block mt-1 w-full" type="time" name="jam_pulang" :value="old('jam_pulang', $settings['jam_pulang'] ?? '16:00')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('jam_pulang')" />
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end">
                        <x-primary-button>
                            {{ __('Simpan Pengaturan') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
