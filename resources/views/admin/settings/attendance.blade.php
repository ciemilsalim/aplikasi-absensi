@extends('layouts.settings')

@section('title', 'Pengaturan Waktu Absensi')

@section('content')
    
    {{-- FORM UNTUK MENGATUR WAKTU ABSENSI --}}
     <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        {{-- Input tersembunyi untuk menandai form mana yang disubmit --}}
        <input type="hidden" name="form_type" value="attendance">
        <input type="hidden" name="effective_year" value="{{ $selectedYear }}">

        <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Waktu & Notifikasi Absensi</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Atur jadwal absensi dan notifikasi otomatis untuk siswa yang tidak hadir.</p>
              </div>
              <div class="p-6 border-t border-gray-200 dark:border-slate-700 space-y-6">
                <div>
                    <h4 class="text-md font-medium text-gray-900 dark:text-gray-200 mb-4">Pengaturan Jadwal Siswa</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="jam_masuk" :value="__('Jam Masuk Siswa')" />
                            <x-text-input id="jam_masuk" class="block mt-1 w-full" type="time" name="jam_masuk" :value="old('jam_masuk', $settings['jam_masuk'] ?? '07:30')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('jam_masuk')" />
                        </div>
                        <div>
                            <x-input-label for="jam_pulang" :value="__('Jam Pulang Siswa')" />
                            <x-text-input id="jam_pulang" class="block mt-1 w-full" type="time" name="jam_pulang" :value="old('jam_pulang', $settings['jam_pulang'] ?? '16:00')" required />
                             <x-input-error class="mt-2" :messages="$errors->get('jam_pulang')" />
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-slate-700 pt-6">
                    <h4 class="text-md font-medium text-gray-900 dark:text-gray-200 mb-4">Pengaturan Jadwal Guru</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="jam_masuk_guru" :value="__('Jam Masuk Guru')" />
                            <x-text-input id="jam_masuk_guru" class="block mt-1 w-full" type="time" name="jam_masuk_guru" :value="old('jam_masuk_guru', $settings['jam_masuk_guru'] ?? '07:00')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('jam_masuk_guru')" />
                        </div>
                        <div>
                            <x-input-label for="jam_pulang_guru" :value="__('Jam Pulang Guru')" />
                            <x-text-input id="jam_pulang_guru" class="block mt-1 w-full" type="time" name="jam_pulang_guru" :value="old('jam_pulang_guru', $settings['jam_pulang_guru'] ?? '16:00')" required />
                             <x-input-error class="mt-2" :messages="$errors->get('jam_pulang_guru')" />
                        </div>
                    </div>
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

                {{-- Pengaturan Hari Efektif Belajar --}}
                <div class="border-t border-gray-200 dark:border-slate-700 pt-6">
                    <div class="flex flex-col sm:flex-row items-center justify-between mb-4">
                        <div>
                            <h4 class="text-md font-medium text-gray-900 dark:text-gray-200">Pengaturan Hari Efektif Sekolah</h4>
                            <p class="text-sm text-gray-500 mt-1">Masukkan jumlah hari efektif per bulan untuk kalkulasi persentase pada Laporan Kelas Triwulan. Pastikan tahun sesuai.</p>
                        </div>
                        <div class="mt-4 sm:mt-0">
                            <select onchange="window.location.href='?year=' + this.value" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-semibold cursor-pointer">
                                @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                    <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @php
                            $months = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ];
                        @endphp
                        @foreach($months as $monthNum => $monthName)
                            <div>
                                <x-input-label for="effective_days_{{ $selectedYear }}_{{ $monthNum }}" :value="__($monthName)" />
                                <x-text-input id="effective_days_{{ $selectedYear }}_{{ $monthNum }}" class="block mt-1 w-full" type="number" min="0" max="31" name="effective_days_{{ $selectedYear }}_{{ $monthNum }}" :value="old('effective_days_'.$selectedYear.'_'.$monthNum, $settings['effective_days_'.$selectedYear.'_'.$monthNum] ?? 0)" />
                            </div>
                        @endforeach
                    </div>
                </div>
              </div>
              <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end">
                  <x-primary-button type="submit">Simpan Pengaturan</x-primary-button>
              </div>
        </div>
    </form>
@endsection
