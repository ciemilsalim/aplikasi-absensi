@extends('layouts.public')

@section('title', 'Pemindai QR Absensi')

@push('styles')
    <style>
        /* Mengatasi UI bawaan html5-qrcode agar lebih rapi */
        #reader {
            border: none !important;
            background: transparent !important;
            border-radius: 0.75rem;
            overflow: hidden;
        }

        #reader video {
            object-fit: cover !important;
            width: 100% !important;
            height: 100% !important;
            border-radius: 0.75rem !important;
        }

        /* Sembunyikan tulisan text bawaan qr scanner */
        #reader__dashboard_section_csr span,
        #reader__dashboard_section_swaplink {
            display: none !important;
        }

        #reader__scan_region {
            background: transparent !important;
        }
    </style>
@endpush

@section('content')
    <div class="relative min-h-[calc(100vh-128px)] flex items-center justify-center overflow-hidden px-4">
        <!-- Latar Belakang Abstrak -->
        <div class="absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-white dark:bg-slate-900"></div>
            <div class="absolute bottom-0 left-0 right-0 h-1/2 bg-slate-50 dark:bg-slate-800/50"
                style="clip-path: polygon(0 100%, 100% 100%, 100% 0, 0 100%);"></div>
            <div
                class="absolute top-0 left-1/4 w-96 h-96 bg-sky-200/50 dark:bg-sky-900/50 rounded-full blur-3xl animate-pulse">
            </div>
            <div
                class="absolute bottom-0 right-1/4 w-96 h-96 bg-indigo-200/50 dark:bg-indigo-900/50 rounded-full blur-3xl animate-pulse [animation-delay:-2s]">
            </div>
        </div>

        <div class="w-full max-w-xl text-center">
            <!-- Jam Digital dan Tanggal -->
            <div class="mb-6 animate-[fade-in-up_0.8s_ease-out_forwards]">
                <p id="current-date" class="text-lg text-slate-600 dark:text-slate-400"></p>
                <p id="current-time" class="text-5xl font-bold text-sky-600 dark:text-sky-400 tracking-tight"></p>
            </div>

            <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-lg p-6 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 animate-[fade-in-up_0.8s_ease-out_forwards]"
                style="animation-delay: 0.4s;">

                <div id="scanner-choice">
                    <h1 class="text-3xl font-bold text-slate-800 dark:text-white mb-2">Pilih Tipe Pemindai</h1>
                    <p class="text-slate-600 dark:text-slate-400 mb-8">Pilih metode yang akan Anda gunakan untuk mencatat
                        kehadiran.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button id="use-camera-button"
                            class="w-full inline-flex flex-col items-center justify-center p-6 border border-transparent text-base font-medium rounded-md text-white bg-sky-600 hover:bg-sky-700 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-10 h-10 mb-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.776 48.776 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                            </svg>
                            Pindai dengan Kamera
                        </button>
                        <button id="use-manual-button"
                            class="w-full inline-flex flex-col items-center justify-center p-6 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-10 h-10 mb-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875-1.875h-4.5A1.875 1.875 0 013.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875-1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875-1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 15.75h4.5a1.875 1.875 0 011.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 01-.937-.938v-3.375c0-.517.42-.938.938-.938z" />
                            </svg>
                            Input Manual / Eksternal
                        </button>
                        <button id="use-face-button"
                            class="w-full inline-flex flex-col items-center justify-center p-6 border border-transparent text-base font-medium rounded-md text-white bg-emerald-600 hover:bg-emerald-700 transition-all duration-300 md:col-span-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-10 h-10 mb-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                            </svg>
                            Pindai dengan Wajah
                        </button>
                    </div>
                </div>

                <div id="camera-scanner" class="hidden">
                    <div id="reader"
                        class="w-full max-w-sm mx-auto aspect-square bg-slate-100 dark:bg-slate-700 rounded-lg overflow-hidden">
                    </div>
                    <div id="camera-switch-container" class="mt-4 text-center hidden">
                        <button id="camera-switch-button"
                            class="text-sm text-sky-600 dark:text-sky-400 hover:underline">Ganti Kamera</button>
                    </div>
                </div>

                <div id="manual-scanner" class="hidden">
                    <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">Input ID Manual</h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Arahkan pemindai eksternal ke kolom di bawah atau
                        ketik ID siswa.</p>
                    <form id="manual-form" onsubmit="return false;">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor" class="w-5 h-5 text-slate-400">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875-1.875h-4.5A1.875 1.875 0 013.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875-1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875-1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 15.75h4.5a1.875 1.875 0 011.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 01-.937-.938v-3.375c0-.517.42-.938.938-.938z" />
                                </svg>
                            </div>
                            <x-text-input id="manual_input_id" class="block w-full text-center text-lg pl-10" type="text"
                                name="manual_input_id" placeholder="ID Siswa" required autofocus />
                        </div>
                    </form>
                </div>

                <div id="face-scanner" class="hidden">
                    <div
                        class="relative w-full max-w-sm mx-auto aspect-square bg-slate-100 dark:bg-slate-700 rounded-lg overflow-hidden">
                        <video id="face-video" class="w-full h-full object-cover" autoplay muted playsinline></video>
                        <canvas id="face-canvas" class="absolute inset-0 w-full h-full"></canvas>

                        <!-- Face Guide Frame -->
                        <div class="absolute inset-0 pointer-events-none flex items-center justify-center p-6 z-10">
                            <div class="w-full h-full max-w-[250px] max-h-[250px] relative opacity-50">
                                <!-- Sudut Kiri Atas -->
                                <div
                                    class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-sky-400 rounded-tl-lg animate-pulse">
                                </div>
                                <!-- Sudut Kanan Atas -->
                                <div
                                    class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-sky-400 rounded-tr-lg animate-pulse">
                                </div>
                                <!-- Sudut Kiri Bawah -->
                                <div
                                    class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-sky-400 rounded-bl-lg animate-pulse">
                                </div>
                                <!-- Sudut Kanan Bawah -->
                                <div
                                    class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-sky-400 rounded-br-lg animate-pulse">
                                </div>

                                <!-- Frame Tengah -->
                                <div class="absolute inset-4 border-2 border-dashed border-white/40 rounded-[100%]"></div>
                            </div>
                        </div>

                        <!-- Loading overlay -->
                        <div id="face-loading-overlay"
                            class="absolute inset-0 flex flex-col items-center justify-center bg-black/60 backdrop-blur-sm z-20 hidden">
                            <svg class="animate-spin h-10 w-10 text-sky-500 mb-4" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <div class="w-3/4 bg-slate-700 rounded-full h-2.5 mb-2 overflow-hidden">
                                <div id="face-loading-bar"
                                    class="bg-sky-500 h-2.5 rounded-full transition-all duration-300 w-0"></div>
                            </div>
                            <p id="face-loading-text" class="text-white font-medium">Memuat Model: 0%</p>
                        </div>
                    </div>
                    <p id="face-status" class="mt-4 text-center text-sm text-slate-600 dark:text-slate-400">Menyiapkan
                        kamera...</p>
                    <div id="face-camera-switch-container" class="mt-4 text-center">
                        <button id="face-camera-switch-button"
                            class="text-sm text-sky-600 dark:text-sky-400 hover:underline flex items-center justify-center mx-auto gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            Ganti Kamera
                        </button>
                    </div>
                </div>

                <div id="reader-error" class="text-red-500 text-sm mt-4 text-center hidden"></div>
                <button id="back-to-choice" class="mt-4 text-sm text-slate-500 dark:text-slate-400 hover:underline hidden">
                    &larr; Kembali ke Pilihan
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Pop-up -->
    <div id="attendance-modal"
        class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0 hidden z-50">
        <div id="modal-content"
            class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm p-8 text-center transform scale-95 transition-all duration-300">
            <div id="modal-icon-container" class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5">
                <svg id="modal-icon-svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor"></svg>
            </div>
            <h2 id="modal-title" class="text-2xl font-bold text-slate-800 dark:text-white mb-2"></h2>
            <div class="mt-4 mb-4">
                <span id="modal-student-image-container"
                    class="inline-block h-24 w-24 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-700">
                    <img id="modal-student-image" src="" alt="Foto Siswa" class="h-full w-full object-cover hidden">
                    <svg id="modal-student-placeholder" class="h-full w-full text-slate-300 dark:text-slate-500"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </span>
            </div>
            <p id="modal-student-name" class="text-xl font-semibold text-sky-700 dark:text-sky-400"></p>
            <p id="modal-student-nis" class="text-md text-slate-500 dark:text-slate-400 mb-6"></p>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Library untuk Face Recognition --}}
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    {{-- Library untuk memindai QR Code dari kamera --}}
    <script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // === VARIABEL GLOBAL & ELEMENT DOM ===
            let userCoordinates = null;
            let lastScanTime = 0;
            const scanCooldown = 3000; // Jeda 3 detik antar scan

            // Data Siswa untuk Face Recognition
            const studentsWithPhotos = @json($students);
            let faceMatcher = null;
            let isModelsLoaded = false;
            let faceScanInterval = null;
            let consecutiveMatches = 0;
            let currentFacingMode = 'user'; // 'user' (depan) atau 'environment' (belakang)

            const scannerChoiceDiv = document.getElementById('scanner-choice');
            const cameraScannerDiv = document.getElementById('camera-scanner');
            const manualScannerDiv = document.getElementById('manual-scanner');
            const faceScannerDiv = document.getElementById('face-scanner'); // NEW

            const useCameraButton = document.getElementById('use-camera-button');
            const useManualButton = document.getElementById('use-manual-button');
            const useFaceButton = document.getElementById('use-face-button'); // NEW
            const backButton = document.getElementById('back-to-choice');
            const manualInput = document.getElementById('manual_input_id');

            const readerDiv = document.getElementById('reader');
            const readerError = document.getElementById('reader-error');
            const switchContainer = document.getElementById('camera-switch-container');
            const switchButton = document.getElementById('camera-switch-button');

            const faceVideo = document.getElementById('face-video'); // NEW
            const faceCanvas = document.getElementById('face-canvas'); // NEW
            const faceStatus = document.getElementById('face-status'); // NEW
            const faceSwitchButton = document.getElementById('face-camera-switch-button');

            // Objek untuk library scanner
            let html5QrCode = null;
            let cameras = [];
            let currentCameraIndex = 0;

            // Objek untuk mengelola modal pop-up
            const modal = {
                element: document.getElementById('attendance-modal'),
                content: document.getElementById('modal-content'),
                iconContainer: document.getElementById('modal-icon-container'),
                iconSvg: document.getElementById('modal-icon-svg'),
                title: document.getElementById('modal-title'),
                studentName: document.getElementById('modal-student-name'),
                studentNis: document.getElementById('modal-student-nis'),
                studentImage: document.getElementById('modal-student-image'),
                studentPlaceholder: document.getElementById('modal-student-placeholder'),
            };

            // === FUNGSI UTAMA ===

            // Fungsi untuk mengupdate jam digital
            function updateClock() {
                const now = new Date();
                document.getElementById('current-time').textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                document.getElementById('current-date').textContent = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }

            // Fungsi untuk memulai alur pemindaian (kamera atau manual)
            function showScannerView(type) {
                scannerChoiceDiv.classList.add('hidden');
                backButton.classList.remove('hidden');
                if (type === 'camera') {
                    cameraScannerDiv.classList.remove('hidden');
                    startScanFlow();
                } else if (type === 'face') {
                    faceScannerDiv.classList.remove('hidden');
                    startFaceScanFlow();
                } else {
                    manualScannerDiv.classList.remove('hidden');
                    manualInput.focus();
                }
            }

            // Fungsi untuk kembali ke menu pilihan
            function resetToChoiceView() {
                // Stop QR Scanner
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().catch(err => console.error("Failed to stop QR", err));
                }
                html5QrCode = null;

                // Stop Face Scanner
                if (faceVideo.srcObject) {
                    faceVideo.srcObject.getTracks().forEach(track => track.stop());
                    faceVideo.srcObject = null;
                }
                if (faceScanInterval) {
                    clearInterval(faceScanInterval);
                    faceScanInterval = null;
                }

                cameraScannerDiv.classList.add('hidden');
                manualScannerDiv.classList.add('hidden');
                faceScannerDiv.classList.add('hidden'); // NEW
                scannerChoiceDiv.classList.remove('hidden');
                backButton.classList.add('hidden');
                readerError.classList.add('hidden');
            }

            // Fungsi utama untuk memulai kamera
            function startScanFlow() {
                readerError.classList.add('hidden');
                // Cek jika scanner sudah diinisialisasi sebelumnya
                if (html5QrCode) {
                    // Jika sudah ada, cukup lanjutkan (resume)
                    html5QrCode.resume();
                    return;
                }

                // Jika belum, mulai dari awal (hanya terjadi sekali)
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userCoordinates = { latitude: position.coords.latitude, longitude: position.coords.longitude };
                        initializeScanner();
                    },
                    (error) => {
                        readerError.textContent = 'Gagal mendapatkan lokasi GPS. Izinkan akses lokasi dan coba lagi.';
                        readerError.classList.remove('hidden');
                    }
                );
            }

            // Inisialisasi library scanner dan dapatkan kamera
            function initializeScanner() {
                html5QrCode = new Html5Qrcode("reader");
                Html5Qrcode.getCameras().then(devices => {
                    if (devices && devices.length) {
                        cameras = devices;
                        // Prioritaskan kamera belakang
                        let backCameraIndex = cameras.findIndex(camera => camera.label.toLowerCase().includes('back'));
                        currentCameraIndex = backCameraIndex !== -1 ? backCameraIndex : 0;
                        startScannerWithCamera(cameras[currentCameraIndex].id);
                        if (cameras.length > 1) {
                            switchContainer.classList.remove('hidden');
                        }
                    } else { throw new Error("Tidak ada kamera yang ditemukan."); }
                }).catch(err => {
                    readerError.textContent = "Gagal mengakses kamera: " + err.message;
                    readerError.classList.remove('hidden');
                });
            }

            // Memulai pemindaian dengan kamera yang dipilih
            function startScannerWithCamera(cameraId) {
                html5QrCode.start(
                    cameraId,
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    onScanSuccess,
                    (errorMessage) => { } // Error callback, bisa diabaikan
                ).catch((err) => {
                    readerError.textContent = "Gagal memulai kamera yang dipilih.";
                    readerError.classList.remove('hidden');
                });
            }

            // Fungsi yang dijalankan saat QR code berhasil dipindai
            function onScanSuccess(decodedText, decodedResult) {
                if (Date.now() - lastScanTime < scanCooldown) return;
                lastScanTime = Date.now();

                // --- PERUBAHAN UTAMA ADA DI SINI ---
                // Alih-alih .stop(), kita gunakan .pause()
                // Ini akan menjeda feed video tapi tidak melepaskan kamera.
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.pause();
                }

                processAttendance(decodedText);
            }

            // Mengirim data absensi ke server
            function processAttendance(studentId) {
                // Tampilkan loading spinner atau sejenisnya jika perlu
                fetch("{{ route('attendance.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        student_unique_id: studentId,
                        latitude: userCoordinates.latitude,
                        longitude: userCoordinates.longitude
                    })
                }).then(response => response.json().then(data => ({ status: response.status, body: data })))
                    .then(({ status, body }) => {
                        // Normalisasi status error untuk kemudahan
                        if (status === 403) body.status = 'location_error';
                        if (status === 409) body.status = body.status || 'completed';
                        if (status === 404) body.status = 'not_found';
                        showModal(body);
                    }).catch(error => {
                        showModal({ status: 'error', message: 'Tidak dapat terhubung ke server.' });
                    });
            }

            // Menampilkan modal pop-up dengan hasil absensi
            function showModal(data) {
                // Reset tampilan modal
                modal.iconContainer.className = 'mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5';
                modal.iconSvg.className = 'h-12 w-12';
                modal.iconSvg.innerHTML = '';

                // Atur ikon, warna, dan judul berdasarkan status
                switch (data.status) {
                    case 'clock_in':
                        modal.iconContainer.classList.add('bg-green-100', 'dark:bg-green-900');
                        modal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />`;
                        modal.iconSvg.classList.add('text-green-600', 'dark:text-green-400');
                        modal.title.textContent = data.attendance_status === 'terlambat' ? 'Anda Terlambat!' : 'Selamat Datang!';
                        playSound('success');
                        break;
                    case 'clock_out':
                        modal.iconContainer.classList.add('bg-blue-100', 'dark:bg-blue-900');
                        modal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />`;
                        modal.iconSvg.classList.add('text-blue-600', 'dark:text-blue-400');
                        modal.title.textContent = 'Sampai Jumpa!';
                        playSound('success');
                        break;
                    case 'completed':
                    case 'already_clocked_in':
                        modal.iconContainer.classList.add('bg-yellow-100', 'dark:bg-yellow-900');
                        modal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />`;
                        modal.iconSvg.classList.add('text-yellow-600', 'dark:text-yellow-400');
                        modal.title.textContent = 'Peringatan';
                        playSound('warning');
                        break;
                    case 'not_found':
                        data.message = 'ID Siswa tidak ditemukan di database.';
                    case 'on_leave':
                    case 'location_error':
                    default:
                        modal.iconContainer.classList.add('bg-red-100', 'dark:bg-red-900');
                        modal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />`;
                        modal.iconSvg.classList.add('text-red-600', 'dark:text-red-400');
                        modal.title.textContent = 'Gagal!';
                        playSound('error');
                        break;
                }

                // Isi data siswa
                modal.studentName.textContent = data.student_name || 'Error';
                modal.studentNis.textContent = data.message || (data.student_nis ? 'NIS: ' + data.student_nis : '');

                // Tampilkan foto siswa jika ada
                if (data.student_photo_url) {
                    modal.studentImage.src = data.student_photo_url;
                    modal.studentImage.classList.remove('hidden');
                    modal.studentPlaceholder.classList.add('hidden');
                } else {
                    modal.studentImage.classList.add('hidden');
                    modal.studentPlaceholder.classList.remove('hidden');
                }

                // Tampilkan modal dengan animasi
                modal.element.classList.remove('hidden');
                setTimeout(() => {
                    modal.element.classList.remove('opacity-0');
                    modal.content.classList.remove('scale-95');
                }, 10);

                // Sembunyikan modal setelah beberapa detik
                setTimeout(hideModal, scanCooldown);
            }

            // Menyembunyikan modal dan melanjutkan pemindaian
            function hideModal() {
                modal.element.classList.add('opacity-0');
                modal.content.classList.add('scale-95');
                setTimeout(() => {
                    modal.element.classList.add('hidden');

                    // --- PERUBAHAN UTAMA ADA DI SINI ---
                    // Cek apakah kita sedang dalam mode kamera, manual, atau wajah
                    if (!manualScannerDiv.classList.contains('hidden')) {
                        manualInput.focus();
                    } else if (!cameraScannerDiv.classList.contains('hidden')) {
                        if (html5QrCode) html5QrCode.resume();
                    } else if (!faceScannerDiv.classList.contains('hidden')) {
                        // Resume logic handled by interval automatically if video is playing
                        // Buffer cooldown handled by lastScanTime
                    }
                }, 300);
            }

            // Memainkan suara notifikasi
            function playSound(type) {
                // Pastikan Anda memiliki file suara di folder public/sounds/
                let audioFile;
                if (type === 'success') { audioFile = "{{ asset('sounds/success.mp3') }}"; }
                else if (type === 'warning') { audioFile = "{{ asset('sounds/warning.mp3') }}"; }
                else { audioFile = "{{ asset('sounds/error.mp3') }}"; }
                try { new Audio(audioFile).play(); } catch (e) { console.error("Gagal memainkan suara:", e); }
            }

            // === EVENT LISTENERS ===

            // Inisialisasi jam saat halaman dimuat
            updateClock();
            setInterval(updateClock, 1000);

            // Tombol pilih mode kamera
            useCameraButton.addEventListener('click', () => showScannerView('camera'));
            // Tombol pilih mode manual
            useManualButton.addEventListener('click', () => showScannerView('manual'));
            // Tombol kembali ke menu
            backButton.addEventListener('click', resetToChoiceView);
            // Tombol ganti kamera
            switchButton.addEventListener('click', () => {
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().then(() => {
                        currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
                        startScannerWithCamera(cameras[currentCameraIndex].id);
                    });
                }
            });

            // === FACE RECOGNITION LOGIC ===
            useFaceButton.addEventListener('click', () => showScannerView('face'));

            async function loadFaceModels() {
                if (isModelsLoaded) return true;

                const overlay = document.getElementById('face-loading-overlay');
                const bar = document.getElementById('face-loading-bar');
                const text = document.getElementById('face-loading-text');
                if (overlay) overlay.classList.remove('hidden');

                faceStatus.textContent = 'Memuat model wajah (ini mungkin memakan waktu)...';
                try {
                    // Memuat model dari penyimpanan lokal (menghindari lambat/diblokir oleh CDN)
                    const MODEL_URL = '{{ asset('models') }}';

                    let progress = 0;
                    const interval = setInterval(() => {
                        progress += Math.random() * 15;
                        if (progress > 90) progress = 90;
                        if (bar) bar.style.width = `${progress}%`;
                        if (text) text.textContent = `Memuat Model: ${Math.round(progress)}%`;
                    }, 500);

                    await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
                    if (bar) bar.style.width = `33%`;
                    await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                    if (bar) bar.style.width = `66%`;
                    await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

                    clearInterval(interval);
                    if (bar) bar.style.width = `100%`;
                    if (text) text.textContent = `Memuat Model: 100%`;

                    setTimeout(() => {
                        if (overlay) overlay.classList.add('hidden');
                    }, 500);

                    isModelsLoaded = true;
                    return true;
                } catch (error) {
                    if (typeof interval !== 'undefined') clearInterval(interval);
                    console.error('Error loading face models:', error);
                    faceStatus.textContent = 'Gagal memuat model wajah. Periksa koneksi internet.';
                    if (overlay) overlay.classList.add('hidden');
                    return false;
                }
            }

            async function startFaceScanFlow() {
                readerError.classList.add('hidden');

                // 1. Load Models
                if (!await loadFaceModels()) return;

                // 2. Load Student Photos & Create Matcher
                if (!faceMatcher) {
                    faceStatus.textContent = 'Memproses data wajah siswa... (Mohon tunggu)';
                    try {
                        const labeledDescriptors = await loadLabeledImages();
                        if (labeledDescriptors.length === 0) {
                            faceStatus.textContent = 'Tidak ada data wajah valid yang dapat dimuat. Pastikan foto jelas dan berwajah tunggal.';
                            return; // Stop the camera from starting if no valid models exist
                        }
                        faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.5);
                    } catch (error) {
                        faceStatus.textContent = 'Terjadi kesalahan sistem saat memproses wajah.';
                        console.error(error);
                        return;
                    }
                }

                // 3. Start Video
                faceStatus.textContent = 'Menyalakan kamera...';
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userCoordinates = { latitude: position.coords.latitude, longitude: position.coords.longitude };
                        startFaceVideo();
                    },
                    (error) => {
                        readerError.textContent = 'Gagal mendapatkan lokasi GPS.';
                        readerError.classList.remove('hidden');
                    }
                );
            }

            function loadLabeledImages() {
                return Promise.all(
                    studentsWithPhotos.map(async student => {
                        return new Promise(async (resolve) => {
                            try {
                                // JIKA SUDAH ADA DESCRIPTOR DI DATABASE, GUNAKAN LANGSUNG (CEPAT!)
                                if (student.face_descriptor) {
                                    try {
                                        const descArray = JSON.parse(student.face_descriptor);
                                        const floatArray = new Float32Array(descArray);
                                        resolve(new faceapi.LabeledFaceDescriptors(student.unique_id, [floatArray]));
                                        return;
                                    } catch (e) {
                                        console.warn("Gagal parsing descriptor untuk student:", student.name, e);
                                    }
                                }

                                // JIKA BELUM ADA, PROSES SEPERTI BIASA (LAMBAT)
                                const img = new Image();
                                img.crossOrigin = 'anonymous';
                                img.src = student.photo_url;

                                img.onload = async () => {
                                    try {
                                        const options = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 }); // Turunkan threshold agar lebih mudah mendeteksi wajah
                                        const detections = await faceapi.detectSingleFace(img, options).withFaceLandmarks().withFaceDescriptor();
                                        if (!detections) {
                                            console.warn(`Wajah tidak terdeteksi pada foto profil: ${student.name} (Bahkan dengan confidence 0.3)`);
                                            resolve(null);
                                            return;
                                        }

                                        // SIMPAN KE DATABASE SECARA ASYNCHRONOUS UNTUK PENGGUNAAN BERIKUTNYA
                                        const descriptorStr = JSON.stringify(Array.from(detections.descriptor));
                                        fetch("{{ route('attendance.save_descriptor') }}", {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                                'Accept': 'application/json'
                                            },
                                            body: JSON.stringify({
                                                unique_id: student.unique_id,
                                                face_descriptor: descriptorStr
                                            })
                                        }).catch(err => console.error("Gagal menyimpan cache descriptor:", err));

                                        resolve(new faceapi.LabeledFaceDescriptors(student.unique_id, [detections.descriptor]));
                                    } catch (e) {
                                        console.error(`Gagal deteksi AI foto ${student.name}:`, e);
                                        resolve(null);
                                    }
                                };

                                img.onerror = () => {
                                    console.error(`Gagal memuat URL foto untuk ${student.name} (CORS/URL Tdk Valid)`);
                                    resolve(null);
                                };
                            } catch (err) {
                                console.error(`Error kritis proses foto ${student.name}:`, err);
                                resolve(null);
                            }
                        });
                    })
                ).then(results => results.filter(res => res !== null));
            }

            faceSwitchButton.addEventListener('click', () => {
                currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';

                // Hentikan stream yang ada
                if (faceVideo.srcObject) {
                    faceVideo.srcObject.getTracks().forEach(track => track.stop());
                }

                // Hapus canvas sisa
                if (faceCanvas.getContext) {
                    faceCanvas.getContext('2d').clearRect(0, 0, faceCanvas.width, faceCanvas.height);
                }

                faceStatus.textContent = 'Menukar kamera...';
                startFaceVideo();
            });

            function startFaceVideo() {
                navigator.mediaDevices.getUserMedia({
                    video: { facingMode: currentFacingMode }
                })
                    .then(stream => {
                        faceVideo.srcObject = stream;
                    })
                    .catch(err => {
                        console.error("Gagal akses kamera:", err);
                        readerError.textContent = "Gagal mengakses kamera. Pastikan browser memiliki izin.";
                        readerError.classList.remove('hidden');

                        // Fallback ke kamera default jika environment/user gagal
                        if (currentFacingMode !== 'user') {
                            currentFacingMode = 'user';
                            startFaceVideo();
                        }
                    });
            }

            faceVideo.addEventListener('play', () => {
                const displaySize = { width: faceVideo.offsetWidth, height: faceVideo.offsetHeight }; // Use dynamic size
                // Check if dimensions are valid
                if (displaySize.width === 0 || displaySize.height === 0) return;

                faceapi.matchDimensions(faceCanvas, displaySize);
                faceStatus.textContent = 'Arahkan wajah ke kamera...';

                faceScanInterval = setInterval(async () => {
                    if (faceVideo.paused || faceVideo.ended) return;

                    const detections = await faceapi.detectAllFaces(faceVideo, new faceapi.SsdMobilenetv1Options())
                        .withFaceLandmarks()
                        .withFaceDescriptors();

                    const resizedDetections = faceapi.resizeResults(detections, displaySize);
                    faceCanvas.getContext('2d').clearRect(0, 0, faceCanvas.width, faceCanvas.height);
                    // faceapi.draw.drawDetections(faceCanvas, resizedDetections); // Optional: Draw box

                    if (detections.length > 0) {
                        const bestMatch = faceMatcher.findBestMatch(detections[0].descriptor);
                        if (bestMatch.label !== 'unknown') {
                            consecutiveMatches++;
                            faceStatus.textContent = `Wajah dikenali! Tahan posisi... (${consecutiveMatches}/3)`;

                            // Check Cooldown
                            if (consecutiveMatches >= 3 && Date.now() - lastScanTime > scanCooldown) {
                                lastScanTime = Date.now();
                                processAttendance(bestMatch.label); // label is unique_id
                                consecutiveMatches = 0; // Reset
                            }
                        } else {
                            consecutiveMatches = 0;
                            faceStatus.textContent = 'Arahkan wajah ke kamera...';
                        }
                    } else {
                        consecutiveMatches = 0;
                        if (faceStatus.textContent !== 'Menyiapkan kamera...') {
                            faceStatus.textContent = 'Arahkan wajah ke kamera...';
                        }
                    }
                }, 500); // Check every 500ms
            });

            // Event listener untuk input manual (dengan debounce)
            let inputTimeout = null;
            manualInput.addEventListener('input', () => {
                clearTimeout(inputTimeout);
                const studentId = manualInput.value.trim();
                if (studentId) {
                    inputTimeout = setTimeout(() => {
                        if (Date.now() - lastScanTime < scanCooldown) return;
                        lastScanTime = Date.now();

                        readerError.classList.add('hidden');
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                userCoordinates = { latitude: position.coords.latitude, longitude: position.coords.longitude };
                                processAttendance(studentId);
                                manualInput.value = ''; // Kosongkan input setelah proses
                            },
                            (error) => {
                                showModal({ status: 'location_error', message: 'Gagal mendapatkan lokasi GPS.', student_name: 'Izinkan akses lokasi dan coba lagi.' });
                            }
                        );
                    }, 100); // Penundaan singkat untuk menunggu input selesai
                }
            });

        });
    </script>
@endpush