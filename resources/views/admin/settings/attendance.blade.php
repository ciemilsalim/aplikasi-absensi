@extends('layouts.settings')

@section('title', 'Pengaturan Waktu Absensi')

@section('content')
    {{-- PERBAIKAN: Menambahkan notifikasi sukses dengan Alpine.js --}}
    @if (session('success'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 5000)"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-2"
             class="bg-green-500 border border-green-600 text-white px-4 py-3 rounded-lg shadow-lg relative mb-6" 
             role="alert"
             style="display: none;">
            <strong class="font-bold">Sukses!</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    {{-- FORM UNTUK MENGATUR WAKTU ABSENSI --}}
    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg">
              <div class="p-6"><h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Waktu Absensi</h3></div>
              <div class="p-6 border-t border-gray-200 dark:border-slate-700 space-y-6">
                 <div>
                    <x-input-label for="jam_masuk" :value="__('Batas Jam Masuk')" /><x-text-input id="jam_masuk" class="block mt-1 w-full" type="time" name="jam_masuk" :value="old('jam_masuk', $settings['jam_masuk'] ?? '07:30')" required /><x-input-error class="mt-2" :messages="$errors->get('jam_masuk')" />
                </div>
                <div>
                    <x-input-label for="jam_pulang" :value="__('Jadwal Jam Pulang')" /><x-text-input id="jam_pulang" class="block mt-1 w-full" type="time" name="jam_pulang" :value="old('jam_pulang', $settings['jam_pulang'] ?? '16:00')" required /><x-input-error class="mt-2" :messages="$errors->get('jam_pulang')" />
                </div>
              </div>
              <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end"><x-primary-button type="submit">Simpan Waktu</x-primary-button></div>
        </div>
        <input type="hidden" name="form_type" value="attendance">
    </form>
@endsection
