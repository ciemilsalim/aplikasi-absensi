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
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-bold shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-750 transition-all">
                <span class="material-icons text-sm">arrow_back</span>
                <span>Kembali</span>
            </a>
        </div>
    </x-slot>

    @push('styles')
    <style>
        body > footer, body > .back-to-top-button { display: none !important; }
        footer.mobile-footer { display: block !important; }
        
        @keyframes scanline {
            0% { top: 10%; opacity: 0; }
            50% { opacity: 0.8; }
            100% { top: 90%; opacity: 0; }
        }
        .scanline-effect {
            animation: scanline 2.5s ease-in-out infinite;
        }
    </style>
    @endpush

    <div class="max-w-lg mx-auto py-3 sm:py-6 px-4 sm:px-0 space-y-4 pb-24 sm:pb-8">
        
        <!-- Status Messages Alert -->
        <div id="status-message" class="hidden p-4 rounded-2xl text-center text-xs font-bold shadow-sm transition-all"></div>

        <!-- Main Card Pemindai -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            
            <!-- Card Header Mini -->
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                        {{ $hasPhoto ? 'Kamera Presensi Aktif' : 'Mode Perekaman Wajah' }}
                    </span>
                </div>
                
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                    SMP Negeri 1 Biau
                </span>
            </div>

            <div class="p-4 sm:p-5 space-y-4">
                
                <!-- Camera Container Viewport -->
                <div class="relative aspect-[3/4] bg-slate-950 rounded-2xl overflow-hidden shadow-inner ring-1 ring-white/10">
                    <video id="video" class="absolute inset-0 w-full h-full object-cover" autoplay muted playsinline></video>
                    <canvas id="overlay" class="absolute inset-0 w-full h-full pointer-events-none"></canvas>

                    <!-- Face Guide Frame -->
                    <div class="absolute inset-0 pointer-events-none flex items-center justify-center p-6 z-10">
                        <div class="w-full h-full max-w-[260px] max-h-[340px] relative">
                            <!-- Sudut Kiri Atas -->
                            <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-sky-400 rounded-tl-2xl shadow-sm"></div>
                            <!-- Sudut Kanan Atas -->
                            <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-sky-400 rounded-tr-2xl shadow-sm"></div>
                            <!-- Sudut Kiri Bawah -->
                            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-sky-400 rounded-bl-2xl shadow-sm"></div>
                            <!-- Sudut Kanan Bawah -->
                            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-sky-400 rounded-br-2xl shadow-sm"></div>

                            <!-- Frame Oval Tengah -->
                            <div class="absolute inset-3 border-2 border-dashed border-white/30 rounded-[100px]"></div>

                            <!-- Garis Laser Scanline -->
                            <div class="absolute inset-x-4 h-0.5 bg-gradient-to-r from-transparent via-sky-400 to-transparent scanline-effect shadow-lg shadow-sky-400/50 pointer-events-none"></div>
                        </div>
                    </div>

                    <!-- Floating Camera Flip Button -->
                    <div class="absolute top-3 right-3 z-20">
                        <button id="face-camera-switch-button" 
                                class="w-10 h-10 rounded-full bg-slate-900/70 hover:bg-slate-900 text-white backdrop-blur-md border border-white/20 flex items-center justify-center shadow-lg active:scale-90 transition-transform" 
                                title="Ganti Kamera">
                            <span class="material-icons text-lg">flip_camera_ios</span>
                        </button>
                    </div>

                    <!-- Floating Top Info Status -->
                    <div class="absolute top-3 left-3 z-20">
                        <div id="floating-match-badge" class="px-2.5 py-1 rounded-full bg-slate-900/70 backdrop-blur-md border border-white/20 text-white text-[10px] font-bold flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping"></span>
                            <span id="match-status-text">Mendeteksi Wajah...</span>
                        </div>
                    </div>

                    <!-- Loading Overlay Indicator -->
                    <div id="loading"
                        class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/85 backdrop-blur-sm z-30">
                        <svg class="animate-spin h-10 w-10 text-sky-500 mb-3" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <div class="w-2/3 max-w-xs bg-slate-800 rounded-full h-2 mb-2 overflow-hidden hidden"
                            id="loading-bar-container">
                            <div id="loading-bar" class="bg-sky-500 h-2 rounded-full transition-all duration-300 w-0"></div>
                        </div>
                        <p id="loading-text" class="text-white text-xs font-bold text-center">Memuat Model Wajah Biometrik...</p>
                    </div>
                </div>

                <!-- Status Geolocation & Radius Card -->
                <div class="bg-slate-50 dark:bg-slate-850 p-3.5 rounded-2xl border border-slate-200/60 dark:border-slate-800 space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            <span class="material-icons text-sky-500 text-base">near_me</span>
                            <span>Verifikasi Lokasi Sekolah</span>
                        </span>
                        <span class="text-[11px] text-slate-400">Radius: {{ $settings['attendance_radius'] ?? 100 }}m</span>
                    </div>
                    
                    <div class="flex items-center justify-between pt-1 border-t border-slate-200/40 dark:border-slate-800/80">
                        <div id="location-status" class="text-xs font-bold text-amber-600 dark:text-amber-400 flex items-center gap-1">
                            <span class="material-icons text-xs animate-spin">sync</span>
                            <span>Mencari koordinat GPS...</span>
                        </div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">
                            Jarak: <span id="distance-debug" class="font-extrabold text-slate-800 dark:text-slate-200">-</span> m
                        </div>
                    </div>
                </div>

                <!-- Action CTA Controls -->
                <div class="pt-1 space-y-2">
                    @if(!$hasPhoto)
                        <div class="text-center text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 border border-amber-200/60 dark:border-amber-900/40 p-3 rounded-2xl text-xs">
                            <span class="font-bold block mb-0.5">Wajah Belum Terdaftar</span>
                            Posisikan wajah Anda tepat di dalam bingkai, lalu tekan tombol di bawah untuk mendaftarkan wajah.
                        </div>
                        <button id="btn-register" disabled
                            class="w-full min-h-[48px] py-3 px-4 bg-sky-600 hover:bg-sky-500 active:bg-sky-700 text-white font-extrabold text-sm rounded-2xl shadow-md shadow-sky-600/25 disabled:opacity-40 disabled:cursor-not-allowed transition-all duration-200 active:scale-95 flex items-center justify-center gap-2">
                            <span class="material-icons text-lg">face_retouching_natural</span>
                            <span>Ambil Foto & Daftarkan Wajah</span>
                        </button>
                    @else
                        <button id="btn-absent" disabled
                            class="w-full min-h-[50px] py-3.5 px-4 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-extrabold text-sm rounded-2xl shadow-md shadow-emerald-600/25 disabled:opacity-40 disabled:cursor-not-allowed transition-all duration-200 active:scale-95 flex items-center justify-center gap-2">
                            <span class="material-icons text-lg">verified</span>
                            <span>REKAM KEHADIRAN SEKARANG</span>
                        </button>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 text-center">
                            Tombol akan aktif otomatis saat wajah terverifikasi dan berada di dalam area sekolah.
                        </p>
                    @endif
                </div>

            </div>
        </div>

        <!-- Petunjuk Singkat -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 space-y-1">
            <p class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                <span class="material-icons text-sm text-sky-500">info</span>
                <span>Petunjuk Pemindaian</span>
            </p>
            <p>1. Lepaskan kacamata hitam atau masker yang menutupi wajah.</p>
            <p>2. Pastikan pencahayaan cukup terang dan menghadap langsung ke kamera.</p>
            <p>3. Pastikan izin lokasi GPS dan kamera telah diaktifkan pada peramban web.</p>
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
            const matchStatusText = document.getElementById('match-status-text');

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
                                showError("Tidak ada data wajah valid untuk akun Anda. Silakan daftarkan wajah ulang.");
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
                    showError("Gagal memuat sistem biometrik: " + error.message);
                }
            });

            // --- 2. Load Models ---
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
                }, 400);

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
                }, 400);
            }

            if (faceSwitchButton) {
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
            }

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
                        showError("Gagal mengakses kamera. Berikan izin kamera pada peramban web.");

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
                        showError("Wajah tidak terdeteksi. Pastikan pencahayaan cukup dan wajah terlihat jelas di bingkai.");
                    }
                });

                video.addEventListener('playing', () => {
                    btnRegister.disabled = false;
                });
            }

            async function registerFace(imageBase64, descriptorStr) {
                try {
                    loadingText.textContent = "Menyimpan data biometrik...";
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
                        showSuccess("Registrasi wajah berhasil! Memuat ulang...");
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showError(data.message);
                    }
                } catch (error) {
                    loading.classList.add('hidden');
                    showError("Terjadi kesalahan koneksi saat menyimpan wajah.");
                }
            }

            // --- 5. Face Processing (Attendance) ---
            async function loadLabeledImages() {
                if (!teacherPhotoUrl) return null;

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
                                console.warn("Wajah tidak terdeteksi pada foto profil tersimpan.");
                                resolve(null);
                            }
                        } catch (e) {
                            console.error("Gagal deteksi AI foto:", e);
                            reject(new Error("Gagal memuat data wajah tersimpan."));
                        }
                    };

                    img.onerror = () => {
                        console.error("Gagal memuat image profil guru.");
                        reject(new Error("Gagal mengunduh foto profil."));
                    };
                });
            }

            if (btnAbsent) {
                video.addEventListener('play', () => {
                    const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
                    faceapi.matchDimensions(overlay, displaySize);

                    setInterval(async () => {
                        if (!faceMatcher || !isLocationValid) {
                            btnAbsent.disabled = true;
                            if (matchStatusText) {
                                matchStatusText.textContent = !isLocationValid ? "Menunggu GPS Valid..." : "Mencari Wajah...";
                            }
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
                                const drawBox = new faceapi.draw.DrawBox(box, { 
                                    label: `Terverifikasi (${consecutiveMatches}/3)`,
                                    boxColor: '#10B981'
                                });
                                drawBox.draw(overlay);

                                if (matchStatusText) {
                                    matchStatusText.textContent = `Wajah Cocok (${consecutiveMatches}/3)`;
                                }

                                if (consecutiveMatches >= 3) {
                                    btnAbsent.disabled = false;
                                    if (matchStatusText) matchStatusText.textContent = "Wajah Terverifikasi ✓";
                                }
                            } else {
                                consecutiveMatches = 0;
                                btnAbsent.disabled = true;
                                if (matchStatusText) matchStatusText.textContent = "Wajah Tidak Cocok";
                            }
                        } else {
                            consecutiveMatches = 0;
                            btnAbsent.disabled = true;
                            if (matchStatusText) matchStatusText.textContent = "Arahkan Wajah ke Kamera";
                        }
                    }, 500);
                });

                btnAbsent.addEventListener('click', async () => {
                    loading.classList.remove('hidden');
                    loadingText.textContent = "Merekam kehadiran...";

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
                        showSuccess("Absensi Berhasil Dicatat!");
                        setTimeout(() => window.location.href = "{{ route('teacher.attendance.dashboard') }}", 1500);
                    } else {
                        showError(data.message);
                    }
                } catch (error) {
                    loading.classList.add('hidden');
                    showError("Gagal merekam kehadiran. Periksa koneksi internet Anda.");
                }
            }

            // --- 6. Geolocation Logic ---
            function startLocationTracking() {
                if (!navigator.geolocation) {
                    locationStatus.innerHTML = '<span class="text-rose-600">Geolokasi tidak didukung peramban ini.</span>';
                    return;
                }

                navigator.geolocation.watchPosition(
                    (position) => {
                        currentLocation = position.coords;
                        validateLocation(position.coords);
                    },
                    (error) => {
                        let msg = "Gagal mendapatkan lokasi GPS.";
                        switch (error.code) {
                            case error.PERMISSION_DENIED: msg = "Izin lokasi ditolak."; break;
                            case error.POSITION_UNAVAILABLE: msg = "Lokasi tidak tersedia."; break;
                            case error.TIMEOUT: msg = "Permintaan lokasi habis waktu."; break;
                        }
                        locationStatus.innerHTML = `<span class="text-rose-600">${msg}</span>`;
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
                    locationStatus.innerHTML = `<span class="text-emerald-600 font-bold flex items-center gap-1"><span class="material-icons text-xs">check_circle</span> Di dalam area sekolah (${Math.round(distance)}m)</span>`;
                } else {
                    isLocationValid = false;
                    locationStatus.innerHTML = `<span class="text-rose-600 font-bold flex items-center gap-1"><span class="material-icons text-xs">cancel</span> Di luar jangkauan (${Math.round(distance)}m > ${maxRadius}m)</span>`;
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
                statusMessage.className = "p-4 rounded-2xl text-center text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 block shadow-xs";
            }

            function showSuccess(msg) {
                statusMessage.textContent = msg;
                statusMessage.className = "p-4 rounded-2xl text-center text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 block shadow-xs";
            }
        </script>
    @endpush
</x-app-layout>