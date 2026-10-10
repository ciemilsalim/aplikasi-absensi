@extends('layouts.settings')

@section('title', 'Pengaturan Gateway WhatsApp')

@section('content')
    <div class="space-y-6">
        {{-- FORM PENGATURAN GATEWAY WHATSAPP --}}
        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            <input type="hidden" name="form_type" value="whatsapp">

            <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-2xl border border-slate-200/80 dark:border-slate-700/80 overflow-hidden">
                <div class="p-6 border-b border-slate-200/80 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                            <span class="material-icons text-xl">chat</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Gateway WhatsApp & Notifikasi Wali Murid</h3>
                            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Hubungkan gateway WhatsApp untuk mengirim notifikasi kehadiran otomatis ke nomor orang tua siswa.</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Provider Gateway --}}
                    <div>
                        <x-input-label for="wa_gateway_provider" :value="__('Penyedia Layanan (Provider) Gateway')" />
                        <select id="wa_gateway_provider" name="wa_gateway_provider"
                            class="block mt-1 w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm font-semibold">
                            <option value="log" {{ ($settings['wa_gateway_provider'] ?? 'log') === 'log' ? 'selected' : '' }}>Mode Simulasi / Laravel Log (Tanpa API Eksternal)</option>
                            <option value="fonnte" {{ ($settings['wa_gateway_provider'] ?? '') === 'fonnte' ? 'selected' : '' }}>Fonnte (api.fonnte.com)</option>
                            <option value="wablas" {{ ($settings['wa_gateway_provider'] ?? '') === 'wablas' ? 'selected' : '' }}>Wablas (phone.wablas.com)</option>
                            <option value="generic" {{ ($settings['wa_gateway_provider'] ?? '') === 'generic' ? 'selected' : '' }}>Generic HTTP Webhook (Custom Gateway)</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Pilih penyedia WhatsApp Gateway. Gunakan mode simulasi jika belum memiliki lisensi gateway berbayar.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('wa_gateway_provider')" />
                    </div>

                    {{-- Token & URL --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="wa_gateway_token" :value="__('API Token / Secret Key Gateway')" />
                            <x-text-input id="wa_gateway_token" class="block mt-1 w-full" type="password" name="wa_gateway_token"
                                :value="old('wa_gateway_token', $settings['wa_gateway_token'] ?? '')" placeholder="Masukkan API Key / Token" />
                            <p class="mt-1 text-[11px] text-slate-400">Token otentikasi dari dasbor provider Fonnte / Wablas.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('wa_gateway_token')" />
                        </div>
                        <div>
                            <x-input-label for="wa_gateway_url" :value="__('Custom Endpoint URL (Opsional)')" />
                            <x-text-input id="wa_gateway_url" class="block mt-1 w-full" type="url" name="wa_gateway_url"
                                :value="old('wa_gateway_url', $settings['wa_gateway_url'] ?? '')" placeholder="https://api.gateway.com/send" />
                            <p class="mt-1 text-[11px] text-slate-400">Biarkan kosong untuk menggunakan endpoint default Fonnte/Wablas.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('wa_gateway_url')" />
                        </div>
                    </div>

                    {{-- Event Notifikasi Toggles --}}
                    <div class="border-t border-slate-200 dark:border-slate-700/80 pt-6 space-y-4">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider">Pemicu Notifikasi WhatsApp Otomatis</h4>

                        {{-- Toggle Clock-In --}}
                        <label for="wa_notify_clock_in" class="flex items-center justify-between cursor-pointer p-3.5 rounded-xl bg-slate-50 dark:bg-slate-850 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <div class="pr-4">
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-200">Notifikasi Presensi Masuk (Datang)</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Kirim pesan WhatsApp instan saat siswa berhasil scan masuk (tepat waktu / terlambat).</p>
                            </div>
                            <div class="relative shrink-0">
                                <input type="checkbox" id="wa_notify_clock_in" name="wa_notify_clock_in" class="sr-only peer"
                                    {{ ($settings['wa_notify_clock_in'] ?? 'off') === 'on' ? 'checked' : '' }}>
                                <div class="block bg-slate-300 dark:bg-slate-700 peer-checked:bg-emerald-600 w-12 h-7 rounded-full transition"></div>
                                <div class="absolute left-1 top-1 bg-white w-5 h-5 rounded-full transition-transform peer-checked:translate-x-5 shadow"></div>
                            </div>
                        </label>

                        {{-- Toggle Clock-Out --}}
                        <label for="wa_notify_clock_out" class="flex items-center justify-between cursor-pointer p-3.5 rounded-xl bg-slate-50 dark:bg-slate-850 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <div class="pr-4">
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-200">Notifikasi Presensi Pulang</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Kirim pesan WhatsApp saat siswa melakukan scan pulang setelah jam sekolah selesai.</p>
                            </div>
                            <div class="relative shrink-0">
                                <input type="checkbox" id="wa_notify_clock_out" name="wa_notify_clock_out" class="sr-only peer"
                                    {{ ($settings['wa_notify_clock_out'] ?? 'off') === 'on' ? 'checked' : '' }}>
                                <div class="block bg-slate-300 dark:bg-slate-700 peer-checked:bg-emerald-600 w-12 h-7 rounded-full transition"></div>
                                <div class="absolute left-1 top-1 bg-white w-5 h-5 rounded-full transition-transform peer-checked:translate-x-5 shadow"></div>
                            </div>
                        </label>

                        {{-- Toggle Absent / Alpa --}}
                        <label for="wa_notify_absent" class="flex items-center justify-between cursor-pointer p-3.5 rounded-xl bg-slate-50 dark:bg-slate-850 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <div class="pr-4">
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-200">Pemberitahuan Siswa Alpa (Cutoff Pagi)</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Kirim pesan WhatsApp jika siswa belum hadir dan tidak memiliki surat izin saat batas waktu cutoff tercapai.</p>
                            </div>
                            <div class="relative shrink-0">
                                <input type="checkbox" id="wa_notify_absent" name="wa_notify_absent" class="sr-only peer"
                                    {{ ($settings['wa_notify_absent'] ?? 'off') === 'on' ? 'checked' : '' }}>
                                <div class="block bg-slate-300 dark:bg-slate-700 peer-checked:bg-emerald-600 w-12 h-7 rounded-full transition"></div>
                                <div class="absolute left-1 top-1 bg-white w-5 h-5 rounded-full transition-transform peer-checked:translate-x-5 shadow"></div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-850/60 px-6 py-4 flex items-center justify-end border-t border-slate-200/80 dark:border-slate-700">
                    <x-primary-button type="submit">Simpan Pengaturan Gateway</x-primary-button>
                </div>
            </div>
        </form>

        {{-- FORM UJI COBA KONEKSI WHATSAPP (PING TEST) --}}
        <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-2xl border border-slate-200/80 dark:border-slate-700/80 overflow-hidden">
            <div class="p-6 border-b border-slate-200/80 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold">
                        <span class="material-icons text-xl">send</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Uji Coba Pengiriman Pesan</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Kirim pesan WhatsApp percobaan ke nomor ponsel Anda untuk memastikan konfigurasi token dan provider telah terhubung.</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.settings.whatsapp.test') }}" method="POST" class="p-6">
                @csrf
                <div class="flex flex-col sm:flex-row items-end gap-4 max-w-xl">
                    <div class="w-full">
                        <x-input-label for="test_phone" :value="__('Nomor WhatsApp Penerima Uji Coba')" />
                        <x-text-input id="test_phone" class="block mt-1 w-full" type="text" name="test_phone"
                            placeholder="Contoh: 081234567890 atau 6281234567890" required />
                        <x-input-error class="mt-2" :messages="$errors->get('test_phone')" />
                    </div>
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 dark:hover:bg-white text-white dark:text-slate-900 font-bold text-xs shadow transition whitespace-nowrap">
                        <span class="material-icons text-sm">send</span>
                        <span>Kirim Pesan Uji Coba</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
