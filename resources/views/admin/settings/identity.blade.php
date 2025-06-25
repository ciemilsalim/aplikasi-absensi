@extends('layouts.settings')

@section('title', 'Pengaturan Identitas & Lokasi')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')
    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        <div class="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Identitas & Lokasi Sekolah</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Atur nama, alamat, dan titik koordinat GPS untuk validasi absensi.</p>
            </div>
            <div class="p-6 border-t border-gray-200 dark:border-slate-700 space-y-6">
                <div>
                    <x-input-label for="school_name" :value="__('Nama Sekolah')" />
                    <x-text-input id="school_name" class="block mt-1 w-full" type="text" name="school_name" :value="old('school_name', $settings['school_name'] ?? config('app.name'))" required />
                </div>
                <div>
                    <x-input-label for="school_address" :value="__('Alamat Sekolah')" />
                    <textarea id="school_address" name="school_address" rows="3" class="block w-full mt-1 border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm">{{ old('school_address', $settings['school_address'] ?? '') }}</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-200 dark:border-slate-700">
                    <div>
                        <x-input-label for="school_latitude" value="Latitude" />
                        <x-text-input id="school_latitude" type="text" name="school_latitude" :value="old('school_latitude', $settings['school_latitude'] ?? -0.897424)" required />
                    </div>
                    <div>
                        <x-input-label for="school_longitude" value="Longitude" />
                        <x-text-input id="school_longitude" type="text" name="school_longitude" :value="old('school_longitude', $settings['school_longitude'] ?? 119.873335)" required />
                    </div>
                    <div>
                        <x-input-label for="attendance_radius" value="Radius Absensi (meter)" />
                        <x-text-input id="attendance_radius" type="number" name="attendance_radius" :value="old('attendance_radius', $settings['attendance_radius'] ?? 100)" required />
                    </div>
                </div>
                <div id="map" class="h-96 rounded-lg z-0"></div>
            </div>
            <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end">
                <x-primary-button type="submit">Simpan Identitas & Lokasi</x-primary-button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const latInput = document.getElementById('school_latitude');
            const lngInput = document.getElementById('school_longitude');
            const radiusInput = document.getElementById('attendance_radius');
            const mapContainer = document.getElementById('map');
            
            if (!mapContainer) return;

            let latitude = parseFloat(latInput.value);
            let longitude = parseFloat(lngInput.value);
            let radius = parseInt(radiusInput.value);

            const map = L.map('map').setView([latitude, longitude], 17);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            const marker = L.marker([latitude, longitude], { draggable: true }).addTo(map);
            const circle = L.circle([latitude, longitude], { radius: radius, color: '#0ea5e9', weight: 2 }).addTo(map);

            // PERBAIKAN UTAMA: Menggunakan ResizeObserver untuk memastikan peta dirender dengan benar
            const resizeObserver = new ResizeObserver(() => {
                map.invalidateSize();
            });
            resizeObserver.observe(mapContainer);

            map.invalidateSize();

            marker.on('dragend', function(e) {
                const latlng = e.target.getLatLng();
                latInput.value = latlng.lat.toFixed(6);
                lngInput.value = latlng.lng.toFixed(6);
                circle.setLatLng(latlng);
            });

            radiusInput.addEventListener('input', function(e) {
                circle.setRadius(parseInt(e.target.value) || 0);
            });
            
            function updateMapPosition() {
                const newLatLng = [parseFloat(latInput.value), parseFloat(lngInput.value)];
                if (!isNaN(newLatLng[0]) && !isNaN(newLatLng[1])) {
                    map.setView(newLatLng, map.getZoom()); 
                    marker.setLatLng(newLatLng); 
                    circle.setLatLng(newLatLng);
                }
            }

            latInput.addEventListener('change', updateMapPosition);
            lngInput.addEventListener('change', updateMapPosition);
        });
    </script>
@endpush
