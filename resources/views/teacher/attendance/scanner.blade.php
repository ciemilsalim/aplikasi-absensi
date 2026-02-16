<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Scanner Absensi Guru
        </h2>
    </x-slot>

    <div class="py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-6">
                    {{ $hasPhoto ? 'Absensi Kehadiran Guru' : 'Registrasi Wajah Guru' }}
                </h2>

                <!-- Status Messages -->
                <div id="status-message" class="hidden mb-4 p-4 rounded-lg text-center text-sm font-medium"></div>

                <!-- Camera Container -->
                <div class="relative aspect-[3/4] bg-black rounded-lg overflow-hidden mb-6">
                    <video id="video" class="absolute inset-0 w-full h-full object-cover" autoplay muted playsinline></video>
                    <canvas id="overlay" class="absolute inset-0 w-full h-full"></canvas>
                    
                    <!-- Loading Indicator -->
                    <div id="loading" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 z-20">
                        <div class="text-white text-center">
                            <svg class="animate-spin h-8 w-8 mx-auto mb-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p id="loading-text">Memuat Model Wajah...</p>
                        </div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="space-y-4">
                    @if(!$hasPhoto)
                        <div class="text-center text-yellow-600 dark:text-yellow-400 text-sm mb-4">
                            Anda belum mendaftarkan wajah. Silakan ambil foto selfie untuk registrasi.
                        </div>
                        <button id="btn-register" disabled class="w-full py-3 px-4 bg-sky-600 hover:bg-sky-700 text-white font-semibold rounded-lg shadow disabled:opacity-50 disabled:cursor-not-allowed transition duration-200">
                            Ambil Foto & Daftar
                        </button>
                    @else
                        <div id="attendance-info" class="text-center text-sm text-gray-600 dark:text-gray-400 mb-2">
                            Pastikan Anda berada di area sekolah.
                            <div id="location-status" class="mt-1 font-mono text-xs text-orange-500">Mencari lokasi...</div>
                        </div>
                        <button id="btn-absent" disabled class="w-full py-3 px-4 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow disabled:opacity-50 disabled:cursor-not-allowed transition duration-200">
                            Rekam Kehadiran
                        </button>
                    @endif
                </div>

                <!-- Location Debug (Optional, hidden by default) -->
                <div class="mt-4 text-xs text-gray-400 text-center">
                    Jarak ke sekolah: <span id="distance-debug">-</span> meter
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
        const settings = @json($settings);
        const hasPhoto = @json($hasPhoto);
        const teacherPhotoUrl = "{{ $teacher->photo ? asset('storage/' . $teacher->photo) : '' }}";
        
        // Elements
        const video = document.getElementById('video');
        const overlay = document.getElementById('overlay');
        const loading = document.getElementById('loading');
        const loadingText = document.getElementById('loading-text');
        const statusMessage = document.getElementById('status-message');
        const btnRegister = document.getElementById('btn-register');
        const btnAbsent = document.getElementById('btn-absent');
        const locationStatus = document.getElementById('location-status');
        const distanceDebug = document.getElementById('distance-debug');

        let currentStream;
        let faceMatcher;
        let currentLocation = null;
        let isLocationValid = false;

        // --- 1. Initialization ---
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                await loadModels();
                if (hasPhoto) {
                    loadingText.textContent = "Memproses data wajah Anda...";
                    await loadLabeledImages();
                }
                startVideo();
                if (hasPhoto) startLocationTracking();
            } catch (error) {
                console.error(error);
                showError("Gagal memuat sistem: " + error.message);
            }
        });

        // --- 2. Load Models ---
        async function loadModels() {
            const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
            await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
            await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
            await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
            loading.classList.add('hidden');
        }

        // --- 3. Camera Setup ---
        function startVideo() {
            navigator.mediaDevices.getUserMedia({ video: {} })
                .then(stream => {
                    currentStream = stream;
                    video.srcObject = stream;
                })
                .catch(err => showError("Gagal mengakses kamera. Izinkan akses kamera."));
        }

        // --- 4. Face Processing (Registration) ---
        if (btnRegister) {
            btnRegister.addEventListener('click', async () => {
                if (!video.srcObject) return;
                
                loading.classList.remove('hidden');
                loadingText.textContent = "Mendeteksi wajah...";

                const detections = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();

                if (detections) {
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    const imageBase64 = canvas.toDataURL('image/png');

                    registerFace(imageBase64);
                } else {
                    loading.classList.add('hidden');
                    showError("Wajah tidak terdeteksi. Pastikan pencahayaan cukup dan wajah terlihat jelas.");
                }
            });
            
            // Enable register button when video plays
            video.addEventListener('playing', () => {
                btnRegister.disabled = false;
            });
        }

        async function registerFace(imageBase64) {
            try {
                loadingText.textContent = "Menyimpan data...";
                const response = await fetch("{{ route('teacher.attendance.register_face') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ photo: imageBase64 })
                });

                const data = await response.json();
                loading.classList.add('hidden');

                if (data.success) {
                    showSuccess("Registrasi berhasil! Memuat ulang...");
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showError(data.message);
                }
            } catch (error) {
                loading.classList.add('hidden');
                showError("Terjadi kesalahan server.");
            }
        }

        // --- 5. Face Processing (Attendance) ---
        async function loadLabeledImages() {
            if (!teacherPhotoUrl) return;
            try {
                const img = await faceapi.fetchImage(teacherPhotoUrl);
                const detections = await faceapi.detectSingleFace(img).withFaceLandmarks().withFaceDescriptor();
                if (detections) {
                    faceMatcher = new faceapi.FaceMatcher([
                        new faceapi.LabeledFaceDescriptors('me', [detections.descriptor])
                    ], 0.6);
                } else {
                    showError("Foto profil Anda tidak valid untuk pengenalan wajah. Silakan hubungi admin atau daftar ulang.");
                }
            } catch (e) {
                console.error(e);
                showError("Gagal memuat data wajah tersimpan.");
            }
        }

        if (btnAbsent) {
            // Run continuous detection
            video.addEventListener('play', () => {
                const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
                faceapi.matchDimensions(overlay, displaySize);

                setInterval(async () => {
                    if (!faceMatcher || !isLocationValid) {
                         btnAbsent.disabled = true;
                         return;
                    }

                    const detections = await faceapi.detectAllFaces(video).withFaceLandmarks().withFaceDescriptors();
                    const resizedDetections = faceapi.resizeResults(detections, displaySize);
                    overlay.getContext('2d').clearRect(0, 0, overlay.width, overlay.height);

                    if (detections.length > 0) {
                        const bestMatch = faceMatcher.findBestMatch(detections[0].descriptor);
                        if (bestMatch.label === 'me') {
                            const box = resizedDetections[0].detection.box;
                            const drawBox = new faceapi.draw.DrawBox(box, { label: "Anda" });
                            drawBox.draw(overlay);
                            
                            btnAbsent.disabled = false; // Enable button if face matches AND location valid
                            btnAbsent.classList.remove('bg-gray-400');
                            btnAbsent.classList.add('bg-green-600');
                        } else {
                            btnAbsent.disabled = true;
                        }
                    } else {
                        btnAbsent.disabled = true;
                    }
                }, 500);
            });

            btnAbsent.addEventListener('click', async () => {
                loading.classList.remove('hidden');
                loadingText.textContent = "Merekam kehadiran...";

                // Capture photo
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                const imageBase64 = canvas.toDataURL('image/png');

                submitAttendance(imageBase64);
            });
        }

        async function submitAttendance(imageBase64) {
            try {
                const response = await fetch("{{ route('teacher.attendance.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        status: 'hadir',
                        latitude: currentLocation.latitude,
                        longitude: currentLocation.longitude,
                        photo: imageBase64
                    })
                });

                const data = await response.json();
                loading.classList.add('hidden');

                if (data.success) {
                    showSuccess("Absensi Berhasil!");
                    setTimeout(() => window.location.href = "{{ route('teacher.attendance.dashboard') }}", 2000);
                } else {
                    showError(data.message);
                }
            } catch (error) {
                loading.classList.add('hidden');
                showError("Gagal merekam kehadiran.");
            }
        }

        // --- 6. Geolocation Logic ---
        function startLocationTracking() {
            if (!navigator.geolocation) {
                locationStatus.textContent = "Geolokasi tidak didukung browser ini.";
                locationStatus.className = "mt-1 font-mono text-xs text-red-500";
                return;
            }

            navigator.geolocation.watchPosition(
                (position) => {
                    currentLocation = position.coords;
                    validateLocation(position.coords);
                },
                (error) => {
                    let msg = "Gagal mendapatkan lokasi.";
                    switch(error.code) {
                        case error.PERMISSION_DENIED: msg = "Izin lokasi ditolak."; break;
                        case error.POSITION_UNAVAILABLE: msg = "Informasi lokasi tidak tersedia."; break;
                        case error.TIMEOUT: msg = "Waktu permintaan lokasi habis."; break;
                    }
                    locationStatus.textContent = msg;
                    locationStatus.className = "mt-1 font-mono text-xs text-red-500";
                    isLocationValid = false;
                },
                { enableHighAccuracy: true }
            );
        }

        function validateLocation(coords) {
            const schoolLat = parseFloat(settings.school_latitude);
            const schoolLng = parseFloat(settings.school_longitude);
            const maxRadius = parseFloat(settings.school_radius);

            const distance = calculateDistance(coords.latitude, coords.longitude, schoolLat, schoolLng);
            distanceDebug.textContent = Math.round(distance);

            if (distance <= maxRadius) {
                isLocationValid = true;
                locationStatus.innerHTML = `<span class="text-green-600 font-bold">✓ Di dalam area sekolah (${Math.round(distance)}m)</span>`;
            } else {
                isLocationValid = false;
                locationStatus.innerHTML = `<span class="text-red-600 font-bold">✗ Di luar jangkauan (${Math.round(distance)}m > ${maxRadius}m)</span>`;
            }
        }

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // metres
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;

            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                      Math.cos(φ1) * Math.cos(φ2) *
                      Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c;
        }

        // --- Helper Functions ---
        function showError(msg) {
            statusMessage.textContent = msg;
            statusMessage.className = "mb-4 p-4 rounded-lg text-center text-sm font-medium bg-red-100 text-red-700 block";
        }

        function showSuccess(msg) {
            statusMessage.textContent = msg;
            statusMessage.className = "mb-4 p-4 rounded-lg text-center text-sm font-medium bg-green-100 text-green-700 block";
        }

    </script>
    @endpush
</x-app-layout>
