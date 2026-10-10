<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor', 'url' => route('dashboard')],
                    ['title' => 'Pemindai Absensi Siswa', 'url' => route('scanner')]
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Pemindai Absensi Siswa
                    </h1>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 shadow-2xs">
                        Kiosk Datang & Pulang
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors shadow-2xs">
                    <span class="material-icons text-sm">arrow_back</span>
                    <span>Kembali ke Dasbor</span>
                </a>
            </div>
        </div>
    </x-slot>

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

    <div class="relative min-h-[calc(100vh-220px)] flex items-center justify-center overflow-hidden py-4 sm:py-6">
        <!-- Modern Background Glows -->
        <div class="absolute inset-0 -z-10 overflow-hidden pointer-events-none">
            <div class="absolute -top-32 -left-32 w-96 h-96 bg-sky-500/10 dark:bg-sky-500/15 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-indigo-500/10 dark:bg-indigo-500/15 rounded-full blur-3xl"></div>
        </div>

        <div class="w-full max-w-xl text-center">
            <!-- Jam Digital dan Tanggal Kiosk -->
            <div class="mb-6">
                <div class="flex items-center justify-center gap-2 flex-wrap mb-2">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-slate-100 dark:bg-slate-850 border border-slate-200/80 dark:border-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-400 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span id="current-date">Memuat tanggal...</span>
                    </div>

                    <!-- Status Jaringan (Online / Offline) -->
                    <div id="network-status-badge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-700 dark:text-emerald-300 shadow-2xs transition-colors">
                        <span id="network-dot" class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span id="network-text">Online</span>
                    </div>

                    <!-- Antrean Offline Badge & Tombol Sinkronisasi -->
                    <div id="offline-queue-container" class="hidden inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-800 text-xs font-bold text-amber-800 dark:text-amber-200 shadow-2xs">
                        <span class="material-icons text-sm text-amber-600 dark:text-amber-400">cloud_queue</span>
                        <span><span id="offline-queue-count">0</span> Antrean Offline</span>
                        <button id="btn-sync-offline" type="button" title="Sinkronkan antrean sekarang"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-semibold transition-all cursor-pointer">
                            <span id="sync-icon" class="material-icons text-xs">sync</span>
                            <span>Sinkron</span>
                        </button>
                    </div>
                </div>

                <p id="current-time" class="text-4xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight font-mono">
                    --:--:--
                </p>
            </div>

            <!-- Main Scanner Card Container -->
            <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl p-6 sm:p-8 rounded-3xl shadow-xl border border-slate-200/80 dark:border-slate-800 transition-all">

                <!-- 1. Selection Screen -->
                <div id="scanner-choice">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400 mb-3">
                        <span class="material-icons text-2xl">qr_code_scanner</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Pilih Tipe Pemindai</h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 mb-6">Pilih metode yang akan digunakan untuk mencatat kehadiran siswa.</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <button id="use-camera-button"
                            class="group flex flex-col items-center justify-center p-5 rounded-2xl bg-gradient-to-b from-sky-500 to-sky-600 hover:from-sky-400 hover:to-sky-500 text-white shadow-lg shadow-sky-600/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 text-center">
                            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center mb-2.5 group-hover:scale-110 transition-transform">
                                <span class="material-icons text-2xl">photo_camera</span>
                            </div>
                            <span class="font-extrabold text-sm tracking-tight">Pindai Kamera (QR)</span>
                            <span class="text-[11px] text-sky-100 mt-0.5 opacity-90">Gunakan kartu QR code siswa</span>
                        </button>

                        <button id="use-manual-button"
                            class="group flex flex-col items-center justify-center p-5 rounded-2xl bg-gradient-to-b from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white shadow-lg shadow-indigo-600/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 text-center">
                            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center mb-2.5 group-hover:scale-110 transition-transform">
                                <span class="material-icons text-2xl">keyboard</span>
                            </div>
                            <span class="font-extrabold text-sm tracking-tight">Input Manual / Barcode</span>
                            <span class="text-[11px] text-indigo-100 mt-0.5 opacity-90">Ketik ID atau barcode scanner</span>
                        </button>

                        <button id="use-face-button"
                            class="group sm:col-span-2 flex flex-row items-center justify-center gap-4 p-5 rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white shadow-lg shadow-emerald-600/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 text-left">
                            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                <span class="material-icons text-2xl">face</span>
                            </div>
                            <div>
                                <span class="font-extrabold text-sm tracking-tight block">Pindai dengan Wajah (Face Recognition)</span>
                                <span class="text-[11px] text-emerald-100 opacity-90 block">Deteksi kehadiran otomatis menggunakan kamera & AI</span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- 2. Camera QR Scanner View -->
                <div id="camera-scanner" class="hidden">
                    <div class="relative w-full max-w-sm mx-auto aspect-square bg-slate-900 rounded-3xl overflow-hidden border-2 border-sky-500/40 shadow-inner">
                        <div id="reader" class="w-full h-full object-cover"></div>
                    </div>
                    
                    <div id="camera-switch-container" class="mt-4 text-center hidden">
                        <button id="camera-switch-button"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition-colors">
                            <span class="material-icons text-base text-sky-500">cameraswitch</span>
                            <span>Ganti Kamera</span>
                        </button>
                    </div>
                </div>

                <!-- 3. Manual ID Input View -->
                <div id="manual-scanner" class="hidden">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center mb-3">
                        <span class="material-icons text-2xl">keyboard</span>
                    </div>
                    <h2 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Input ID Siswa</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mb-5">Arahkan barcode scanner eksternal atau ketik nomor ID / NIS siswa lalu tekan Enter.</p>
                    
                    <form id="manual-form" onsubmit="return false;" class="max-w-sm mx-auto">
                        <div class="relative">
                            <span class="material-icons absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl pointer-events-none">pin</span>
                            <input id="manual_input_id" 
                                   type="text" 
                                   name="manual_input_id" 
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700 rounded-2xl text-center text-lg font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15 transition-all shadow-xs" 
                                   placeholder="Contoh: 1029384" 
                                   required autofocus />
                        </div>
                    </form>
                </div>

                <!-- 4. Face Recognition Scanner View -->
                <div id="face-scanner" class="hidden">
                    <div class="relative w-full max-w-sm mx-auto aspect-square bg-slate-950 rounded-3xl overflow-hidden border-2 border-emerald-500/40 shadow-inner">
                        <video id="face-video" class="w-full h-full object-cover" autoplay muted playsinline></video>
                        <canvas id="face-canvas" class="absolute inset-0 w-full h-full pointer-events-none"></canvas>

                        <!-- Futuristic Face Guide Frame -->
                        <div class="absolute inset-0 pointer-events-none flex items-center justify-center p-6 z-10">
                            <div class="w-full h-full max-w-[240px] max-h-[240px] relative">
                                <div class="absolute top-0 left-0 w-7 h-7 border-t-4 border-l-4 border-emerald-400 rounded-tl-xl"></div>
                                <div class="absolute top-0 right-0 w-7 h-7 border-t-4 border-r-4 border-emerald-400 rounded-tr-xl"></div>
                                <div class="absolute bottom-0 left-0 w-7 h-7 border-b-4 border-l-4 border-emerald-400 rounded-bl-xl"></div>
                                <div class="absolute bottom-0 right-0 w-7 h-7 border-b-4 border-r-4 border-emerald-400 rounded-br-xl"></div>
                                <div class="absolute inset-3 border border-emerald-400/30 rounded-full animate-pulse"></div>
                            </div>
                        </div>

                        <!-- Loading overlay -->
                        <div id="face-loading-overlay"
                            class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/80 backdrop-blur-sm z-20 hidden p-6">
                            <div class="w-10 h-10 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin mb-3"></div>
                            <div class="w-full bg-slate-800 rounded-full h-2 mb-2 overflow-hidden">
                                <div id="face-loading-bar" class="bg-emerald-500 h-full rounded-full transition-all duration-300 w-0"></div>
                            </div>
                            <p id="face-loading-text" class="text-xs text-white font-bold">Memuat Model: 0%</p>
                        </div>
                    </div>

                    <p id="face-status" class="mt-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400">
                        Menyiapkan kamera...
                    </p>

                    <div id="face-camera-switch-container" class="mt-3 text-center">
                        <button id="face-camera-switch-button"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition-colors mx-auto">
                            <span class="material-icons text-base text-emerald-500">cameraswitch</span>
                            <span>Ganti Kamera</span>
                        </button>
                    </div>
                </div>

                <div id="reader-error" class="text-rose-500 text-xs font-bold mt-4 text-center hidden"></div>
                
                <button id="back-to-choice" 
                        class="mt-6 inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition-colors hidden">
                    <span class="material-icons text-sm">arrow_back</span>
                    <span>Kembali ke Pilihan Pemindai</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Feedback Pop-up -->
    <div id="attendance-modal"
        class="fixed inset-0 bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4 transition-opacity duration-300 opacity-0 hidden z-50">
        <div id="modal-content"
            class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-sm p-7 text-center transform scale-95 transition-all duration-300 border border-slate-200/80 dark:border-slate-800">
            
            <div id="modal-icon-container" class="mx-auto flex items-center justify-center h-16 w-16 rounded-2xl mb-4 shadow-md">
                <svg id="modal-icon-svg" class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"></svg>
            </div>

            <h2 id="modal-title" class="text-lg font-extrabold text-slate-900 dark:text-white mb-2"></h2>
            
            <div class="mt-3 mb-4">
                <span id="modal-student-image-container"
                    class="inline-block h-20 w-20 rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 ring-4 ring-slate-100 dark:ring-slate-800">
                    <img id="modal-student-image" src="" alt="Foto Siswa" class="h-full w-full object-cover hidden">
                    <svg id="modal-student-placeholder" class="h-full w-full text-slate-300 dark:text-slate-600 p-2"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </span>
            </div>

            <p id="modal-student-name" class="text-base font-bold text-sky-600 dark:text-sky-400"></p>
            <p id="modal-student-nis" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"></p>
        </div>
    </div>

    <!-- Floating Toast Notification for Sync & Offline Info -->
    <div id="kiosk-toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 transition-all duration-300 opacity-0 pointer-events-none transform translate-y-4">
        <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-2xl bg-slate-900/90 dark:bg-slate-100/95 backdrop-blur text-white dark:text-slate-900 shadow-2xl border border-slate-700/50 dark:border-slate-300 text-xs font-bold">
            <span id="kiosk-toast-icon" class="material-icons text-emerald-400 dark:text-emerald-600 text-base">cloud_done</span>
            <span id="kiosk-toast-message">Notifikasi</span>
        </div>
    </div>

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

            // Data Siswa untuk Face Recognition & Fast Lookup
            const studentsWithPhotos = @json($students);
            const studentLookup = new Map();
            studentsWithPhotos.forEach(s => studentLookup.set(s.unique_id, s));

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

            // Elemen Status Jaringan & Antrean Kiosk Offline
            const netBadge = document.getElementById('network-status-badge');
            const netDot = document.getElementById('network-dot');
            const netText = document.getElementById('network-text');
            const queueContainer = document.getElementById('offline-queue-container');
            const queueCount = document.getElementById('offline-queue-count');
            const btnSync = document.getElementById('btn-sync-offline');
            const syncIcon = document.getElementById('sync-icon');

            // Ambil GPS sedini mungkin saat halaman dibuka
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userCoordinates = { latitude: position.coords.latitude, longitude: position.coords.longitude };
                    },
                    (err) => { console.warn("GPS belum aktif/diizinkan:", err); }
                );
            }

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

            // === INDEXEDDB OFFLINE BUFFER QUEUE ===
            const DB_NAME = 'SiasekKioskDB';
            const DB_VERSION = 1;
            const STORE_NAME = 'offline_scans';
            let dbInstance = null;

            function openKioskDB() {
                return new Promise((resolve, reject) => {
                    if (dbInstance) return resolve(dbInstance);
                    const request = indexedDB.open(DB_NAME, DB_VERSION);
                    request.onupgradeneeded = (event) => {
                        const db = event.target.result;
                        if (!db.objectStoreNames.contains(STORE_NAME)) {
                            db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                        }
                    };
                    request.onsuccess = (event) => {
                        dbInstance = event.target.result;
                        resolve(dbInstance);
                    };
                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                });
            }

            async function saveOfflineScan(scanItem) {
                const db = await openKioskDB();
                return new Promise((resolve, reject) => {
                    const tx = db.transaction(STORE_NAME, 'readwrite');
                    const store = tx.objectStore(STORE_NAME);
                    scanItem.client_uuid = 'kiosk-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                    const req = store.add(scanItem);
                    req.onsuccess = () => resolve(req.result);
                    req.onerror = () => reject(req.error);
                });
            }

            async function getOfflineScans() {
                const db = await openKioskDB();
                return new Promise((resolve, reject) => {
                    const tx = db.transaction(STORE_NAME, 'readonly');
                    const store = tx.objectStore(STORE_NAME);
                    const req = store.getAll();
                    req.onsuccess = () => resolve(req.result || []);
                    req.onerror = () => reject(req.error);
                });
            }

            async function deleteOfflineScans(ids) {
                if (!ids || ids.length === 0) return;
                const db = await openKioskDB();
                return new Promise((resolve, reject) => {
                    const tx = db.transaction(STORE_NAME, 'readwrite');
                    const store = tx.objectStore(STORE_NAME);
                    ids.forEach(id => store.delete(id));
                    tx.oncomplete = () => resolve();
                    tx.onerror = () => reject(tx.error);
                });
            }

            function updateNetworkStatus() {
                const isOnline = navigator.onLine;
                if (netBadge && netDot && netText) {
                    if (isOnline) {
                        netBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-700 dark:text-emerald-300 shadow-2xs transition-colors';
                        netDot.className = 'w-2 h-2 rounded-full bg-emerald-500';
                        netText.textContent = 'Online';
                    } else {
                        netBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-800 text-xs font-bold text-amber-700 dark:text-amber-300 shadow-2xs transition-colors';
                        netDot.className = 'w-2 h-2 rounded-full bg-amber-500 animate-pulse';
                        netText.textContent = 'Mode Offline';
                    }
                }
            }

            async function updateOfflineUI(count = null) {
                if (count === null) {
                    try {
                        const items = await getOfflineScans();
                        count = items.length;
                    } catch (e) {
                        count = 0;
                    }
                }

                if (queueContainer && queueCount) {
                    queueCount.textContent = count;
                    if (count > 0) {
                        queueContainer.classList.remove('hidden');
                    } else {
                        queueContainer.classList.add('hidden');
                    }
                }
            }

            function setSyncButtonState(isBusy) {
                if (!syncIcon || !btnSync) return;
                if (isBusy) {
                    syncIcon.classList.add('animate-spin');
                    btnSync.disabled = true;
                    btnSync.classList.add('opacity-70', 'cursor-not-allowed');
                } else {
                    syncIcon.classList.remove('animate-spin');
                    btnSync.disabled = false;
                    btnSync.classList.remove('opacity-70', 'cursor-not-allowed');
                }
            }

            function showToastNotification(message, icon = 'cloud_done', isError = false) {
                const toast = document.getElementById('kiosk-toast');
                const toastMsg = document.getElementById('kiosk-toast-message');
                const toastIcon = document.getElementById('kiosk-toast-icon');
                if (!toast || !toastMsg) return;

                toastMsg.textContent = message;
                if (toastIcon) {
                    toastIcon.textContent = icon;
                    if (isError) {
                        toastIcon.className = 'material-icons text-rose-400 dark:text-rose-600 text-base';
                    } else {
                        toastIcon.className = 'material-icons text-emerald-400 dark:text-emerald-600 text-base';
                    }
                }

                toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
                toast.classList.add('opacity-100', 'translate-y-0');

                setTimeout(() => {
                    toast.classList.remove('opacity-100', 'translate-y-0');
                    toast.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
                }, 3500);
            }

            let isSyncing = false;
            async function syncOfflineQueue(isManual = false) {
                if (isSyncing || !navigator.onLine) {
                    if (isManual && !navigator.onLine) {
                        showToastNotification("Perangkat masih offline, hubungkan internet terlebih dahulu.", 'wifi_off', true);
                    }
                    return;
                }

                try {
                    const items = await getOfflineScans();
                    if (items.length === 0) {
                        updateOfflineUI(0);
                        if (isManual) {
                            showToastNotification("Antrean kosong, seluruh data presensi telah tersimpan di server.", 'done_all');
                        }
                        return;
                    }

                    isSyncing = true;
                    setSyncButtonState(true);

                    // Kirim batch maksimal 100 record per request
                    const batch = items.slice(0, 100);
                    const payload = {
                        scans: batch.map(item => ({
                            client_uuid: item.client_uuid,
                            student_unique_id: item.student_unique_id,
                            latitude: item.latitude,
                            longitude: item.longitude,
                            scanned_at: item.scanned_at
                        }))
                    };

                    const response = await fetch("{{ route('attendance.sync_offline') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (response.ok) {
                        const resData = await response.json();
                        const processedIds = batch.map(b => b.id);
                        await deleteOfflineScans(processedIds);

                        const remainingItems = await getOfflineScans();
                        updateOfflineUI(remainingItems.length);

                        showToastNotification(`Sinkronisasi berhasil: ${resData.synced_count} presensi tercatat di server.`);

                        if (remainingItems.length > 0) {
                            setTimeout(() => syncOfflineQueue(), 1500);
                        }
                    } else {
                        if (isManual) {
                            showToastNotification("Gagal menyinkronkan data ke server (Status: " + response.status + ")", 'error', true);
                        }
                    }
                } catch (e) {
                    console.warn("Kesalahan auto-sync antrean offline:", e);
                    if (isManual) {
                        showToastNotification("Koneksi bermasalah saat menyinkronkan.", 'wifi_off', true);
                    }
                } finally {
                    isSyncing = false;
                    setSyncButtonState(false);
                }
            }

            // Mengirim data absensi ke server (dengan fallback ke IndexedDB offline queue)
            function processAttendance(studentId) {
                const nowIso = new Date().toISOString();
                const studentMeta = studentLookup.get(studentId) || null;
                const studentDisplayName = studentMeta ? studentMeta.name : (studentId.length > 20 ? 'Siswa' : studentId);
                const currentCoords = userCoordinates || { latitude: 0, longitude: 0 };

                // Jika terdeteksi offline secara eksplisit, simpan langsung ke IndexedDB
                if (!navigator.onLine) {
                    saveOfflineScan({
                        student_unique_id: studentId,
                        latitude: currentCoords.latitude,
                        longitude: currentCoords.longitude,
                        scanned_at: nowIso,
                        student_name: studentDisplayName
                    }).then(() => {
                        showModal({
                            status: 'clock_in_offline',
                            student_name: studentDisplayName,
                            message: 'Perangkat sedang offline. Presensi berhasil disimpan di perangkat dan akan disinkronkan saat terhubung internet.',
                            student_photo_url: studentMeta ? studentMeta.photo_url : null
                        });
                        updateOfflineUI();
                    }).catch(err => {
                        console.error("Gagal menyimpan ke IndexedDB:", err);
                        showModal({ status: 'error', message: 'Gagal menyimpan presensi offline.' });
                    });
                    return;
                }

                // Kirim request ke server dengan timeout 8 detik
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 8000);

                fetch("{{ route('attendance.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    signal: controller.signal,
                    body: JSON.stringify({
                        student_unique_id: studentId,
                        latitude: currentCoords.latitude,
                        longitude: currentCoords.longitude,
                        scanned_at: nowIso
                    })
                })
                .then(response => {
                    clearTimeout(timeoutId);
                    return response.json().then(data => ({ status: response.status, body: data }));
                })
                .then(({ status, body }) => {
                    if (status >= 500) {
                        // Server error -> fallback simpan offline agar antrean siswa tidak hilang
                        saveOfflineScan({
                            student_unique_id: studentId,
                            latitude: currentCoords.latitude,
                            longitude: currentCoords.longitude,
                            scanned_at: nowIso,
                            student_name: studentDisplayName
                        }).then(() => {
                            showModal({
                                status: 'clock_in_offline',
                                student_name: studentDisplayName,
                                message: 'Gangguan respon server. Presensi telah diamankan di antrean offline kiosk.',
                                student_photo_url: studentMeta ? studentMeta.photo_url : null
                            });
                            updateOfflineUI();
                        });
                        return;
                    }

                    if (status === 403) body.status = 'location_error';
                    if (status === 409) body.status = body.status || 'completed';
                    if (status === 404) body.status = 'not_found';
                    showModal(body);
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    console.warn("Koneksi gagal atau timeout, mengalihkan ke antrean offline:", error);
                    saveOfflineScan({
                        student_unique_id: studentId,
                        latitude: currentCoords.latitude,
                        longitude: currentCoords.longitude,
                        scanned_at: nowIso,
                        student_name: studentDisplayName
                    }).then(() => {
                        showModal({
                            status: 'clock_in_offline',
                            student_name: studentDisplayName,
                            message: 'Koneksi terputus/timeout. Presensi berhasil diamankan di antrean offline perangkat.',
                            student_photo_url: studentMeta ? studentMeta.photo_url : null
                        });
                        updateOfflineUI();
                    }).catch(dbErr => {
                        console.error("Gagal menyimpan ke IndexedDB:", dbErr);
                        showModal({ status: 'error', message: 'Gagal terhubung dan gagal menyimpan di perangkat.' });
                    });
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
                    case 'clock_in_offline':
                    case 'offline_saved':
                        modal.iconContainer.classList.add('bg-amber-100', 'dark:bg-amber-900');
                        modal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />`;
                        modal.iconSvg.classList.add('text-amber-600', 'dark:text-amber-400');
                        modal.title.textContent = 'Tersimpan Offline';
                        playSound('success');
                        break;
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

            // === AUDIO SYNTH FEEDBACK (ZERO EXTERNAL ASSET) ===
            function playAttendanceChime(isSuccess = true) {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    if (isSuccess) {
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(880, ctx.currentTime);
                        osc.frequency.exponentialRampToValueAtTime(1760, ctx.currentTime + 0.12);
                        gain.gain.setValueAtTime(0.15, ctx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.18);
                        osc.start();
                        osc.stop(ctx.currentTime + 0.18);
                    } else {
                        osc.type = 'sawtooth';
                        osc.frequency.setValueAtTime(220, ctx.currentTime);
                        gain.gain.setValueAtTime(0.12, ctx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
                        osc.start();
                        osc.stop(ctx.currentTime + 0.25);
                    }
                } catch (e) {}
            }

            async function startFaceScanFlow() {
                readerError.classList.add('hidden');

                // 1. Load Models
                if (!await loadFaceModels()) return;

                // 2. Load Student Photos & Create Matcher
                if (!faceMatcher) {
                    faceStatus.textContent = 'Memproses data biometrik siswa... (Mohon tunggu)';
                    try {
                        const labeledDescriptors = await loadLabeledImages();
                        if (labeledDescriptors.length === 0) {
                            faceStatus.textContent = 'Belum ada data foto wajah siswa yang valid untuk pemindai.';
                            return;
                        }
                        // 0.45 threshold: keseimbangan sempurna akurasi & toleransi pencahayaan
                        faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.45);
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
                        readerError.textContent = 'Gagal mendapatkan lokasi GPS. Izinkan lokasi untuk absensi.';
                        readerError.classList.remove('hidden');
                    }
                );
            }

            async function loadLabeledImages() {
                const results = [];
                const uncachedStudents = [];

                // Langkah A: Muat seluruh siswa yang sudah memiliki face_descriptor tersimpan (INSTAN!)
                studentsWithPhotos.forEach(student => {
                    if (student.face_descriptor) {
                        try {
                            const descArray = JSON.parse(student.face_descriptor);
                            const floatArray = new Float32Array(descArray);
                            results.push(new faceapi.LabeledFaceDescriptors(student.unique_id, [floatArray]));
                        } catch (e) {
                            console.warn("Gagal parsing descriptor untuk:", student.name, e);
                            uncachedStudents.push(student);
                        }
                    } else if (student.photo_url) {
                        uncachedStudents.push(student);
                    }
                });

                // Langkah B: Jika ada siswa tanpa descriptor, proses berurutan dengan indikator status
                if (uncachedStudents.length > 0) {
                    for (let i = 0; i < uncachedStudents.length; i++) {
                        const student = uncachedStudents[i];
                        faceStatus.textContent = `Mengindeks foto baru (${i + 1}/${uncachedStudents.length}): ${student.name}...`;

                        try {
                            const descriptor = await extractDescriptorFromImage(student.photo_url);
                            if (descriptor) {
                                results.push(new faceapi.LabeledFaceDescriptors(student.unique_id, [descriptor]));

                                // Simpan ke database di latar belakang
                                const descriptorStr = JSON.stringify(Array.from(descriptor));
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
                                }).catch(() => {});
                            }
                        } catch (err) {
                            console.warn(`Gagal ekstraksi biometrik untuk ${student.name}:`, err);
                        }
                    }
                }

                return results;
            }

            function extractDescriptorFromImage(photoUrl) {
                return new Promise((resolve) => {
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.src = photoUrl;
                    img.onload = async () => {
                        try {
                            const detection = await faceapi.detectSingleFace(
                                img, 
                                new faceapi.SsdMobilenetv1Options({ minConfidence: 0.35 })
                            ).withFaceLandmarks().withFaceDescriptor();
                            resolve(detection ? detection.descriptor : null);
                        } catch (e) {
                            resolve(null);
                        }
                    };
                    img.onerror = () => resolve(null);
                });
            }

            faceSwitchButton.addEventListener('click', () => {
                currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';

                if (faceVideo.srcObject) {
                    faceVideo.srcObject.getTracks().forEach(track => track.stop());
                }

                if (faceCanvas.getContext) {
                    faceCanvas.getContext('2d').clearRect(0, 0, faceCanvas.width, faceCanvas.height);
                }

                faceStatus.textContent = 'Menukar kamera...';
                startFaceVideo();
            });

            function startFaceVideo() {
                // Resolusi optimal 640x480 untuk kecepatan inferensi real-time maksimal
                navigator.mediaDevices.getUserMedia({
                    video: { 
                        facingMode: currentFacingMode,
                        width: { ideal: 640, max: 1280 },
                        height: { ideal: 480, max: 720 },
                        frameRate: { ideal: 24, max: 30 }
                    }
                })
                    .then(stream => {
                        faceVideo.srcObject = stream;
                    })
                    .catch(err => {
                        console.error("Gagal akses kamera:", err);
                        readerError.textContent = "Gagal mengakses kamera. Pastikan browser memiliki izin.";
                        readerError.classList.remove('hidden');

                        if (currentFacingMode !== 'user') {
                            currentFacingMode = 'user';
                            startFaceVideo();
                        }
                    });
            }

            // Visual HUD Bounding Box Drawer
            function drawFaceHUD(ctx, box, studentInfo, matchCount, isMatched) {
                const { x, y, width, height } = box;
                const cornerLength = Math.min(width, height) * 0.22;
                const strokeColor = isMatched ? '#10b981' : '#f59e0b';

                ctx.save();
                ctx.strokeStyle = strokeColor;
                ctx.lineWidth = 3.5;
                ctx.lineCap = 'round';
                ctx.shadowColor = isMatched ? 'rgba(16, 185, 129, 0.6)' : 'rgba(245, 158, 11, 0.4)';
                ctx.shadowBlur = 12;

                // 4 Sudut Bounding Box Futuristik
                // Top-Left
                ctx.beginPath();
                ctx.moveTo(x, y + cornerLength);
                ctx.lineTo(x, y);
                ctx.lineTo(x + cornerLength, y);
                ctx.stroke();

                // Top-Right
                ctx.beginPath();
                ctx.moveTo(x + width - cornerLength, y);
                ctx.lineTo(x + width, y);
                ctx.lineTo(x + width, y + cornerLength);
                ctx.stroke();

                // Bottom-Left
                ctx.beginPath();
                ctx.moveTo(x, y + height - cornerLength);
                ctx.lineTo(x, y + height);
                ctx.lineTo(x + cornerLength, y + height);
                ctx.stroke();

                // Bottom-Right
                ctx.beginPath();
                ctx.moveTo(x + width - cornerLength, y + height);
                ctx.lineTo(x + width, y + height);
                ctx.lineTo(x + width, y + height - cornerLength);
                ctx.stroke();

                // Floating Tag Banner
                ctx.shadowBlur = 0;
                const tagText = isMatched 
                    ? `${studentInfo.name} (${matchCount}/3)` 
                    : 'Wajah Tidak Dikenali';
                ctx.font = 'bold 12px "Plus Jakarta Sans", sans-serif';
                const textWidth = ctx.measureText(tagText).width;
                const badgeWidth = textWidth + 24;
                const badgeHeight = 26;
                const badgeX = x + (width - badgeWidth) / 2;
                const badgeY = y - badgeHeight - 8 > 0 ? y - badgeHeight - 8 : y + height + 8;

                // Background Tag
                ctx.fillStyle = isMatched ? 'rgba(6, 78, 59, 0.92)' : 'rgba(120, 53, 15, 0.92)';
                ctx.beginPath();
                ctx.roundRect(badgeX, badgeY, badgeWidth, badgeHeight, 8);
                ctx.fill();

                ctx.strokeStyle = strokeColor;
                ctx.lineWidth = 1.2;
                ctx.stroke();

                // Label Text
                ctx.fillStyle = '#ffffff';
                ctx.fillText(tagText, badgeX + 12, badgeY + 17);

                ctx.restore();
            }

            faceVideo.addEventListener('play', () => {
                const displaySize = { width: faceVideo.offsetWidth, height: faceVideo.offsetHeight };
                if (displaySize.width === 0 || displaySize.height === 0) return;

                faceapi.matchDimensions(faceCanvas, displaySize);
                faceStatus.textContent = 'Arahkan wajah ke kamera...';

                let isDetectingFrame = false;

                faceScanInterval = setInterval(async () => {
                    if (isDetectingFrame || faceVideo.paused || faceVideo.ended || !faceMatcher) return;

                    isDetectingFrame = true;
                    try {
                        // SsdMobilenetv1 dioptimalkan: maxResults = 1 untuk efisiensi kiosk 1 siswa
                        const detections = await faceapi.detectAllFaces(
                            faceVideo, 
                            new faceapi.SsdMobilenetv1Options({ minConfidence: 0.45, maxResults: 1 })
                        )
                            .withFaceLandmarks()
                            .withFaceDescriptors();

                        const resizedDetections = faceapi.resizeResults(detections, displaySize);
                        const ctx = faceCanvas.getContext('2d');
                        ctx.clearRect(0, 0, faceCanvas.width, faceCanvas.height);

                        if (resizedDetections.length > 0) {
                            const detection = resizedDetections[0];
                            const bestMatch = faceMatcher.findBestMatch(detection.descriptor);
                            const matchedStudent = studentLookup.get(bestMatch.label);

                            if (bestMatch.label !== 'unknown' && matchedStudent) {
                                consecutiveMatches++;
                                drawFaceHUD(ctx, detection.detection.box, matchedStudent, consecutiveMatches, true);
                                faceStatus.textContent = `Wajah terdeteksi: ${matchedStudent.name}. Tahan posisi... (${consecutiveMatches}/3)`;

                                // Trigger kehadiran setelah 3 frame stabil & cooldown lewat
                                if (consecutiveMatches >= 3 && Date.now() - lastScanTime > scanCooldown) {
                                    lastScanTime = Date.now();
                                    playAttendanceChime(true);
                                    processAttendance(bestMatch.label);
                                    consecutiveMatches = 0;
                                }
                            } else {
                                consecutiveMatches = 0;
                                drawFaceHUD(ctx, detection.detection.box, null, 0, false);
                                faceStatus.textContent = 'Wajah belum terdaftar atau posisi kurang jelas. Dekatkan wajah...';
                            }
                        } else {
                            consecutiveMatches = 0;
                            if (faceStatus.textContent !== 'Menyiapkan kamera...') {
                                faceStatus.textContent = 'Arahkan wajah ke kamera...';
                            }
                        }
                    } catch (err) {
                        console.warn("Kesalahan evaluasi frame:", err);
                    } finally {
                        isDetectingFrame = false;
                    }
                }, 300); // 300ms = ~3.3 FPS ideal untuk neural net realtime tanpa panas berlebih
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

            // Inisialisasi status jaringan & antrean IndexedDB
            updateNetworkStatus();
            openKioskDB().then(() => {
                updateOfflineUI();
                // Jika sedang online dan ada antrean tertinggal dari sesi sebelumnya, langsung sinkronkan
                if (navigator.onLine) {
                    syncOfflineQueue();
                }
            }).catch(e => console.warn("Inisialisasi IndexedDB Kiosk:", e));

            window.addEventListener('online', () => {
                updateNetworkStatus();
                showToastNotification("Koneksi pulih. Memulai sinkronisasi antrean...", 'wifi');
                syncOfflineQueue();
            });

            window.addEventListener('offline', () => {
                updateNetworkStatus();
                showToastNotification("Perangkat beralih ke Mode Offline. Presensi tetap tersimpan.", 'wifi_off', true);
            });

            if (btnSync) {
                btnSync.addEventListener('click', () => {
                    syncOfflineQueue(true);
                });
            }

            // Interval berkala auto-sync setiap 20 detik jika online
            setInterval(() => {
                if (navigator.onLine && !isSyncing) {
                    syncOfflineQueue();
                }
            }, 20000);

        });
    </script>
@endpush
</x-app-layout>