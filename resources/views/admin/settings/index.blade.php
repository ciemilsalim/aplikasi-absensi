<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Pengaturan', 'url' => route('admin.settings.index')]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Pengaturan Aplikasi') }}
        </h2>
    </x-slot>

    {{-- Memuat CSS untuk Peta Leaflet.js --}}
    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    @endpush

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition class="bg-green-500 border border-green-600 text-white px-4 py-3 rounded-lg shadow-lg relative mb-6" role="alert" style="display: none;">
                    <strong class="font-bold">Sukses!</strong>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Kolom Kiri: Pengaturan Identitas & Lokasi -->
                <div class="lg:col-span-2 space-y-6">
                    <form x-data="mapSettings()" x-init="initMap()" action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Identitas & Lokasi Sekolah</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Atur nama, alamat, dan titik koordinat GPS.</p>
                            </div>
                            <div class="p-6 border-t border-gray-200 dark:border-slate-700 space-y-6">
                                <div>
                                    <x-input-label for="school_name" :value="__('Nama Sekolah')" />
                                    <x-text-input id="school_name" class="block mt-1 w-full" type="text" name="school_name" :value="old('school_name', $settings['school_name'] ?? config('app.name', 'AbsensiSiswa'))" required />
                                </div>
                                <div>
                                    <x-input-label for="school_address" :value="__('Alamat Sekolah')" />
                                    <textarea id="school_address" name="school_address" rows="3" class="block w-full mt-1 border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">{{ old('school_address', $settings['school_address'] ?? '') }}</textarea>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-200 dark:border-slate-700">
                                    <div><x-input-label for="school_latitude" value="Latitude" /><x-text-input id="school_latitude" type="text" name="school_latitude" x-model="latitude" /></div>
                                    <div><x-input-label for="school_longitude" value="Longitude" /><x-text-input id="school_longitude" type="text" name="school_longitude" x-model="longitude" /></div>
                                    <div><x-input-label for="attendance_radius" value="Radius Absensi (m)" /><x-text-input id="attendance_radius" type="number" name="attendance_radius" x-model="radius" /></div>
                                </div>
                                <div id="map" class="h-80 rounded-lg mt-4 z-0"></div>
                            </div>
                            <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end">
                                <x-primary-button type="submit">Simpan Identitas</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Kolom Kanan: Pengaturan Tambahan -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Card Tampilan & Logo -->
                    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg">
                             <div class="p-6"><h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Tampilan & Logo</h3></div>
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
                                    <x-input-label for="app_logo" :value="__('Unggah Logo Baru')" /><x-text-input id="app_logo" class="block mt-1 w-full" type="file" name="app_logo" /><x-input-error class="mt-2" :messages="$errors->get('app_logo')" />
                                </div>
                                <label for="dark_mode" class="flex items-center justify-between cursor-pointer pt-4 border-t border-gray-200 dark:border-slate-700">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-300">Mode Gelap</span>
                                    <div class="relative"><input type="checkbox" id="dark_mode" name="dark_mode" class="sr-only peer" {{ ($settings['dark_mode'] ?? 'off') === 'on' ? 'checked' : '' }} onchange="toggleDarkMode(this.checked)"><div class="block bg-gray-200 peer-checked:bg-sky-600 w-14 h-8 rounded-full"></div><div class="absolute left-1 top-1 bg-white border-gray-300 border peer-checked:border-white w-6 h-6 rounded-full transition-transform peer-checked:translate-x-full"></div></div>
                                </label>
                             </div>
                             <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end"><x-primary-button type="submit">Simpan Tampilan</x-primary-button></div>
                        </div>
                    </form>
                    <!-- Card Waktu Absensi -->
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
                    </form>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        function mapSettings() {
            return {
                latitude: "{{ old('school_latitude', $settings['school_latitude'] ?? -0.897424) }}",
                longitude: "{{ old('school_longitude', $settings['school_longitude'] ?? 119.873335) }}",
                radius: "{{ old('attendance_radius', $settings['attendance_radius'] ?? 100) }}",
                map: null,
                marker: null,
                circle: null,
                initMap() {
                    this.$nextTick(() => {
                        this.map = L.map('map').setView([this.latitude, this.longitude], 17);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
                        this.marker = L.marker([this.latitude, this.longitude], { draggable: true }).addTo(this.map);
                        this.circle = L.circle([this.latitude, this.longitude], { radius: this.radius, color: '#0284c7' }).addTo(this.map);
                        this.marker.on('dragend', (e) => {
                            const latlng = e.target.getLatLng();
                            this.latitude = latlng.lat.toFixed(6);
                            this.longitude = latlng.lng.toFixed(6);
                            this.circle.setLatLng(latlng);
                        });
                        this.$watch('radius', (newRadius) => this.circle.setRadius(parseInt(newRadius) || 0));
                    });
                }
            }
        }
        function toggleDarkMode(isChecked) { /* ... */ }
    </script>
    @endpush
</x-app-layout>
