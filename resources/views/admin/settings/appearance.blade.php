@extends('layouts.settings')

@section('title', 'Pengaturan Tampilan & Logo')

@section('content')
    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        {{-- Input tersembunyi untuk menandai form mana yang disubmit --}}
        <input type="hidden" name="form_type" value="appearance">

        <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg">
             <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Tampilan & Logo</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Kelola logo aplikasi dan preferensi tema mode gelap.</p>
            </div>

             <div class="p-6 border-t border-gray-200 dark:border-slate-700 space-y-6">
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

                {{-- PERBAIKAN: Menambahkan 'dark_mode_checkbox' sebagai penanda bahwa form ini dikirim --}}
                <input type="hidden" name="dark_mode_checkbox" value="1">
                <label for="dark_mode" class="flex items-center justify-between cursor-pointer pt-4 border-t border-gray-200 dark:border-slate-700">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-300">Aktifkan Mode Gelap</span>
                    <div class="relative">
                        <input type="checkbox" id="dark_mode" name="dark_mode" class="sr-only peer" {{ ($settings['dark_mode'] ?? 'off') === 'on' ? 'checked' : '' }} onchange="toggleDarkMode(this.checked)">
                        <div class="block bg-gray-200 peer-checked:bg-sky-600 w-14 h-8 rounded-full"></div>
                        <div class="absolute left-1 top-1 bg-white border-gray-300 border peer-checked:border-white w-6 h-6 rounded-full transition-transform peer-checked:translate-x-full"></div>
                    </div>
                </label>
             </div>
             <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end">
                 <x-primary-button type="submit">Simpan Tampilan</x-primary-button>
             </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    // Fungsi ini akan mengubah tema secara real-time dan menyimpan preferensi
    // ke localStorage di browser pengguna.
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
