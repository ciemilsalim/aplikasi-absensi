<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard')],
                    ['title' => 'Absen Guru', 'url' => route('teacher.attendance.dashboard')],
                    ['title' => 'Scanner Wajah', 'url' => '#']
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    {{ $hasPhoto ? 'Pemindai Wajah Guru' : 'Registrasi Wajah Biometrik' }}
                </h1>
            </div>

            <a href="{{ route('teacher.attendance.dashboard') }}" 
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-bold shadow-2xs hover:bg-slate-50 transition-all">
                <span class="material-icons text-sm">arrow_back</span>
                <span>Kembali</span>
            </a>
        </div>
    </x-slot>

    @push('styles')
    <style>
        body > footer, body > .back-to-top-button { display: none !important; }
        footer.mobile-footer { display: block !important; }
    </style>
    @endpush

    <div class="max-w-lg mx-auto py-4 sm:py-8 px-4 sm:px-0 space-y-4 pb-24 sm:pb-8">
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="p-5 sm:p-6">
                <!-- Status Messages -->
                <div id="status-message" class="hidden mb-4 p-3.5 rounded-2xl text-center text-xs font-bold shadow-2xs"></div>

                <!-- Camera Container -->
                <div class="relative aspect-[3/4] bg-slate-950 rounded-2xl overflow-hidden mb-4 shadow-inner ring-1 ring-white/10">
                    <video id="video" class="absolute inset-0 w-full h-full object-cover" autoplay muted
                        playsinline></video>
                    <canvas id="overlay" class="absolute inset-0 w-full h-full"></canvas>

                    <!-- Face Guide Frame -->
                    <div class="absolute inset-0 pointer-events-none flex items-center justify-center p-8 z-10">
                        <div class="w-full h-full max-w-[260px] max-h-[340px] relative opacity-60">
                            <!-- Sudut Kiri Atas -->
                            <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-sky-400 rounded-tl-xl animate-pulse"></div>
                            <!-- Sudut Kanan Atas -->
                            <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-sky-400 rounded-tr-xl animate-pulse"></div>
                            <!-- Sudut Kiri Bawah -->
                            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-sky-400 rounded-bl-xl animate-pulse"></div>
                            <!-- Sudut Kanan Bawah -->
                            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-sky-400 rounded-br-xl animate-pulse"></div>

                            <!-- Frame Tengah -->
                            <div class="absolute inset-4 border-2 border-dashed border-white/40 rounded-[90px]"></div>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div id="loading"
                        class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/80 backdrop-blur-xs z-20">
                        <svg class="animate-spin h-10 w-10 text-sky-500 mb-3" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <div class="w-2/3 max-w-xs bg-slate-800 rounded-full h-2 mb-2 overflow-hidden hidden"
                            id="loading-bar-container">
                            <div id="loading-bar" class="bg-sky-500 h-2 rounded-full transition-all duration-300 w-0">
                            </div>
                        </div>
                        <p id="loading-text" class="text-white text-xs font-bold text-center">Memuat Model Wajah...</p>
                    </div>
                </div>

                <div id="face-camera-switch-container" class="mb-4 text-center">
                    <button id="face-camera-switch-button"
                        class="text-xs text-sky-600 dark:text-sky-400 hover:underline font-bold inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl bg-sky-50 dark:bg-sky-950/60 border border-sky-200/60 dark:border-sky-900/40">
                        <span class="material-icons text-sm">flip_camera_ios</span>
                        <span>Ganti Kamera Depan/Belakang</span>
                    </button>
                </div>

                <!-- Controls -->
                <div class="space-y-3">
                    @if(!$hasPhoto)
                        <div class="text-center text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 border border-amber-200/60 dark:border-amber-900/40 p-3 rounded-2xl text-xs">
                            <span class="font-bold block mb-0.5">Wajah Belum Terdaftar</span>
                            Posisikan wajah Anda tepat di dalam bingkai, lalu tekan tombol di bawah untuk mendaftarkan wajah.
                        </div>
                        <button id="btn-register" disabled
                            class="w-full min-h-[48px] py-3 px-4 bg-sky-600 hover:bg-sky-500 active:bg-sky-700 text-white font-extrabold text-sm rounded-2xl shadow-md shadow-sky-600/25 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200 active:scale-95">
                            Ambil Foto & Daftarkan Wajah
                        </button>
                    @else
                        <div id="attendance-info" class="text-center text-xs text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-850 p-3 rounded-2xl border border-slate-200/60 dark:border-slate-800">
                            <span class="block font-semibold text-slate-800 dark:text-slate-200">Verifikasi Lokasi & Biometrik Wajah</span>
                            <div id="location-status" class="mt-1 font-mono text-[11px] text-amber-600 dark:text-amber-400 font-bold">Mencari lokasi GPS...</div>
                        </div>
                        <button id="btn-absent" disabled
                            class="w-full min-h-[48px] py-3 px-4 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-extrabold text-sm rounded-2xl shadow-md shadow-emerald-600/25 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200 active:scale-95">
                            Rekam Kehadiran
                        </button>
                    @endif
                </div>

                <!-- Location Distance Info -->
                <div class="mt-3 text-[11px] text-slate-400 text-center flex items-center justify-center gap-1">
                    <span class="material-icons text-xs">radar</span>
                    <span>Jarak ke sekolah: <span id="distance-debug" class="font-bold text-slate-700 dark:text-slate-300">-</span> meter</span>
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
            const cachedDescriptor = @json($face_descriptor);

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
            const faceSwitchButton = document.getElementById('face-camera-switch-button');

            let currentStream;
            let faceMatcher;
            let currentLocation = null;
            let isLocationValid = false;
            let consecutiveMatches = 0;
            let currentFacingMode = 'user';

            // --- 1. Initialization ---
            document.addEventListener('DOMContentLoaded', async () => {
                try {
                    await loadModels();
                    if (hasPhoto) {
                        loadingText.textContent = "Memproses data wajah Anda...";
                        try {
                            const labeledDescriptor = await loadLabeledImages();
                            if (labeledDescriptor) {
                                faceMatcher = new faceapi.FaceMatcher([labeledDescriptor], 0.5);
                            } else {
                                showError("Tidak ada data wajah valid untuk akun Anda. Pastikan foto profil Anda jelas (wajah terlihat).");
                                return;
                            }
                        } catch (error) {
                            showError("Terjadi kesalahan sistem saat memproses wajah.");
                            console.error(error);
                            return;
                        }
                    }
                    startVideo();
                    if (hasPhoto) startLocationTracking();
                } catch (error) {
                    if (typeof interval !== 'undefined') clearInterval(interval);
                    console.error('Error loading face models:', error);
                    showError("Gagal memuat sistem: " + error.message);
                }
            });

            // --- 2. Load Models ---
            // Memuat model dari penyimpanan lokal (menghindari lambat/diblokir oleh CDN)
            const MODEL_URL = '{{ asset('models') }}';

            async function loadModels() {
                const barContainer = document.getElementById('loading-bar-container');
                const bar = document.getElementById('loading-bar');

                if (barContainer) barContainer.classList.remove('hidden');
                loadingText.textContent = "Memuat Model: 0%";

                let progress = 0;
                const interval = setInterval(() => {
                    progress += Math.random() * 15;
                    if (progress > 90) progress = 90;
                    if (bar) bar.style.width = `${progress}%`;
                    loadingText.textContent = `Memuat Model: ${Math.round(progress)}%`;
                }, 500);

                await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
                if (bar) bar.style.width = `33%`;
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                if (bar) bar.style.width = `66%`;
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

                clearInterval(interval);
                if (bar) bar.style.width = `100%`;
                loadingText.textContent = `Memuat Model: 100%`;

                setTimeout(() => {
                    loading.classList.add('hidden');
                    if (barContainer) barContainer.classList.add('hidden');
                }, 500);
            }

            faceSwitchButton.addEventListener('click', () => {
                currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';

                if (currentStream) {
                    currentStream.getTracks().forEach(track => track.stop());
                }

                const canvasContext = overlay.getContext('2d');
                if (canvasContext) {
                    canvasContext.clearRect(0, 0, overlay.width, overlay.height);
                }

                startVideo();
            });

            // --- 3. Camera Setup ---
            function startVideo() {
                navigator.mediaDevices.getUserMedia({
                    video: { facingMode: currentFacingMode }
                })
                    .then(stream => {
                        currentStream = stream;
                        video.srcObject = stream;
                    })
                    .catch(err => {
                        showError("Gagal mengakses kamera. Berikan perizinan kamera pada browser.");

                        if (currentFacingMode !== 'user') {
                            currentFacingMode = 'user';
                            startVideo();
                        }
                    });
            }

            // --- 4. Face Processing (Registration) ---
            if (btnRegister) {
                btnRegister.addEventListener('click', async () => {
                    if (!video.srcObject) return;

                    loading.classList.remove('hidden');
                    loadingText.textContent = "Mendeteksi wajah...";

                    const options = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 });
                    const detections = await faceapi.detectSingleFace(video, options).withFaceLandmarks().withFaceDescriptor();

                    if (detections) {
                        const canvas = document.createElement('canvas');
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        canvas.getContext('2d').drawImage(video, 0, 0);
                        const imageBase64 = canvas.toDataURL('image/png');
                        const descriptorStr = JSON.stringify(Array.from(detections.descriptor));

                        registerFace(imageBase64, descriptorStr);
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

            async function registerFace(imageBase64, descriptorStr) {
                try {
                    loadingText.textContent = "Menyimpan data...";
                    const response = await fetch("{{ route('teacher.attendance.register_face') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ 
                            photo: imageBase64,
                            face_descriptor: descriptorStr
                        })
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
                if (!teacherPhotoUrl) return null;

                // JIKA SUDAH ADA DESCRIPTOR DI DATABASE, GUNAKAN LANGSUNG
                if (cachedDescriptor) {
                    try {
                        const descArray = JSON.parse(cachedDescriptor);
                        const floatArray = new Float32Array(descArray);
                        return new faceapi.LabeledFaceDescriptors('me', [floatArray]);
                    } catch (e) {
                        console.warn("Gagal parsing cached descriptor:", e);
                    }
                }

                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.src = teacherPhotoUrl;

                    img.onload = async () => {
                        try {
                            const options = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 });
                            const detections = await faceapi.detectSingleFace(img, options).withFaceLandmarks().withFaceDescriptor();
                            if (detections) {
                                resolve(new faceapi.LabeledFaceDescriptors('me', [detections.descriptor]));
                            } else {
                                console.warn("Wajah tidak terdeteksi pada foto profil Anda.");
                                resolve(null);
                            }
                        } catch (e) {
                            console.error("Gagal deteksi AI foto:", e);
                            reject(new Error("Gagal memuat data wajah tersimpan."));
                        }
                    };

                    img.onerror = () => {
                        console.error("Gagal memuat image profil guru (CORS/URL Invalid).");
                        reject(new Error("Gagal mengunduh foto profil."));
                    };
                });
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

                                consecutiveMatches++;
                                const drawBox = new faceapi.draw.DrawBox(box, { label: `Anda (${consecutiveMatches}/3)` });
                                drawBox.draw(overlay);

                                if (consecutiveMatches >= 3) {
                                    btnAbsent.disabled = false; // Enable button if face matches AND location valid
                                    btnAbsent.classList.remove('bg-gray-400');
                                    btnAbsent.classList.add('bg-green-600');
                                }
                            } else {
                                consecutiveMatches = 0;
                                btnAbsent.disabled = true;
                                btnAbsent.classList.add('bg-gray-400');
                                btnAbsent.classList.remove('bg-green-600');
                            }
                        } else {
                            consecutiveMatches = 0;
                            btnAbsent.disabled = true;
                            btnAbsent.classList.add('bg-gray-400');
                            btnAbsent.classList.remove('bg-green-600');
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
                        switch (error.code) {
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
                const maxRadius = parseFloat(settings.attendance_radius || 100);

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