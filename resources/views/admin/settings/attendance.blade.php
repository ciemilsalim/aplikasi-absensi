@extends('layouts.settings')

@section('title', 'Pengaturan Waktu Absensi')

@section('content')
    
    {{-- FORM UNTUK MENGATUR WAKTU ABSENSI --}}
     <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        {{-- Input tersembunyi untuk menandai form mana yang disubmit --}}
        <input type="hidden" name="form_type" value="attendance">

        <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Waktu & Notifikasi Absensi</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Atur jadwal absensi dan notifikasi otomatis untuk siswa yang tidak hadir.</p>
              </div>
              <div class="p-6 border-t border-gray-200 dark:border-slate-700 space-y-6">
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
                {{-- Toggle Notifikasi --}}
                <label for="send_absent_notification" class="flex items-center justify-between cursor-pointer pt-4 border-t border-gray-200 dark:border-slate-700">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-300">Notifikasi Siswa Alpa Otomatis</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Kirim notifikasi jika siswa belum hadir setelah jam masuk.</p>
                    </div>
                    <div class="relative">
                        <input type="checkbox" id="send_absent_notification" name="send_absent_notification" class="sr-only peer" {{ ($settings['send_absent_notification'] ?? 'off') === 'on' ? 'checked' : '' }}>
                        <div class="block bg-gray-200 peer-checked:bg-sky-600 w-14 h-8 rounded-full"></div>
                        <div class="absolute left-1 top-1 bg-white border-gray-300 border peer-checked:border-white w-6 h-6 rounded-full transition-transform peer-checked:translate-x-full"></div>
                    </div>
                </label>
              </div>
              <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end">
                  <x-primary-button type="submit">Simpan Pengaturan</x-primary-button>
              </div>
        </div>
    </form>
@endsection
