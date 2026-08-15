<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor', 'url' => route('dashboard')],
                    ['title' => 'Pemindai Izin Keluar', 'url' => route('permit.scanner')]
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Pemindai Izin Keluar Siswa
                    </h1>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 shadow-2xs">
                        Validasi Surat Izin
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
            <div class="absolute -top-32 -left-32 w-96 h-96 bg-indigo-500/10 dark:bg-indigo-500/15 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-sky-500/10 dark:bg-sky-500/15 rounded-full blur-3xl"></div>
        </div>

        <div class="w-full max-w-xl text-center">
            <!-- Jam Digital dan Tanggal Kiosk -->
            <div class="mb-6">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-slate-100 dark:bg-slate-850 border border-slate-200/80 dark:border-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-400 mb-2 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                    <span id="current-date">Memuat tanggal...</span>
                </div>
                <p id="current-time" class="text-4xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight font-mono">
                    --:--:--
                </p>
            </div>

            <!-- Main Scanner Card Container -->
            <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl p-6 sm:p-8 rounded-3xl shadow-xl border border-slate-200/80 dark:border-slate-800 transition-all">

                <!-- 1. Selection Screen -->
                <div id="scanner-choice">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 mb-3">
                        <span class="material-icons text-2xl">assignment</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Pilih Tipe Pemindai Izin</h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 mb-6">Pilih metode untuk mencatat izin keluar atau kembali siswa ke sekolah.</p>
                    
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
                                <span class="text-[11px] text-emerald-100 opacity-90 block">Deteksi izin keluar/kembali otomatis menggunakan kamera & AI</span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- 2. Camera QR Scanner View -->
                <div id="camera-scanner" class="hidden">
                    <div class="relative w-full max-w-sm mx-auto aspect-square bg-slate-900 rounded-3xl overflow-hidden border-2 border-indigo-500/40 shadow-inner">
                        <div id="reader" class="w-full h-full object-cover"></div>
                    </div>
                    
                    <div id="camera-switch-container" class="mt-4 text-center hidden">
                        <button id="camera-switch-button"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition-colors">
                            <span class="material-icons text-base text-indigo-500">cameraswitch</span>
                            <span>Ganti Kamera</span>
                        </button>
                    </div>
                </div>

                <!-- 3. Face Recognition Scanner View -->
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

                <!-- 4. Manual ID Input View -->
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
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-850 border border-slate-300 dark:border-slate-700 rounded-2xl text-center text-lg font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 transition-all shadow-xs" 
                                   placeholder="Contoh: 1029384" 
                                   required autofocus />
                        </div>
                    </form>
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

    <!-- Modal untuk Alasan Izin -->
    <div id="reason-modal"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/70 backdrop-blur-md p-4 hidden opacity-0 transition-opacity duration-300">
        <div id="reason-modal-content"
            class="w-full max-w-md p-6 bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200/80 dark:border-slate-800 transform scale-95 transition-all duration-300">
            <form id="reason-form" onsubmit="return false;">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <span class="material-icons text-xl">edit_note</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Alasan Izin Keluar Sekolah</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Siswa: <strong id="reason-modal-student-name" class="text-sky-600 dark:text-sky-400"></strong></p>
                    </div>
                </div>

                <div class="mt-4">
                    <label for="reason" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Keterangan / Alasan</label>
                    <textarea id="reason" name="reason" rows="3"
                        class="w-full rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 p-3 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 transition-all"
                        placeholder="Contoh: Sakit mendadak / dipanggil keluarga / urusan penting..."
                        required></textarea>
                </div>

                <div class="mt-6 flex justify-end gap-2.5">
                    <button type="button" id="cancel-reason-button"
                        class="px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="submit-reason-button"
                        class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 rounded-xl shadow-md shadow-indigo-600/25 transition-all">
                        Simpan Izin Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Pop-up Hasil -->
    <div id="result-modal"
        class="fixed inset-0 bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4 transition-opacity duration-300 opacity-0 hidden z-50">
        <div id="result-modal-content"
            class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-sm p-7 text-center transform scale-95 transition-all duration-300 border border-slate-200/80 dark:border-slate-800">
            
            <div id="result-modal-icon-container"
                class="mx-auto flex items-center justify-center h-16 w-16 rounded-2xl mb-4 shadow-md">
                <svg id="result-modal-icon-svg" class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"></svg>
            </div>

            <h2 id="result-modal-title" class="text-lg font-extrabold text-slate-900 dark:text-white mb-2"></h2>
            
            <div class="mt-3 mb-4">
                <span id="result-modal-student-image-container"
                    class="inline-block h-20 w-20 rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 ring-4 ring-slate-100 dark:ring-slate-800">
                    <img id="result-modal-student-image" src="" alt="Foto Siswa" class="h-full w-full object-cover hidden">
                    <svg id="result-modal-student-placeholder" class="h-full w-full text-slate-300 dark:text-slate-600 p-2"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </span>
            </div>

            <p id="result-modal-student-name" class="text-base font-bold text-sky-600 dark:text-sky-400"></p>
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
            let currentStudentId = null; // Menyimpan ID siswa saat meminta alasan

            // Data Siswa untuk Face Recognition
            const studentsWithPhotos = @json($students);
            let faceMatcher = null;
            let isModelsLoaded = false;
            let faceScanInterval = null;
            let consecutiveMatches = 0;
            let currentFacingMode = 'user';

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

            // Objek untuk mengelola modal Alasan
            const reasonModal = {
                element: document.getElementById('reason-modal'),
                content: document.getElementById('reason-modal-content'),
                form: document.getElementById('reason-form'),
                studentName: document.getElementById('reason-modal-student-name'),
                cancelButton: document.getElementById('cancel-reason-button'),
                submitButton: document.getElementById('submit-reason-button'),
                reasonTextarea: document.getElementById('reason'),
            };

            // Objek untuk mengelola modal Hasil
            const resultModal = {
                element: document.getElementById('result-modal'),
                content: document.getElementById('result-modal-content'),
                iconContainer: document.getElementById('result-modal-icon-container'),
                iconSvg: document.getElementById('result-modal-icon-svg'),
                title: document.getElementById('result-modal-title'),
                studentName: document.getElementById('result-modal-student-name'),
                message: document.getElementById('result-modal-message'),
                studentImage: document.getElementById('result-modal-student-image'),
                studentPlaceholder: document.getElementById('result-modal-student-placeholder'),
            };

            // === FUNGSI UTAMA ===

            function updateClock() {
                const now = new Date();
                document.getElementById('current-time').textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                document.getElementById('current-date').textContent = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }

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

            function resetToChoiceView() {
                // Stop QR Scanner
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().catch(err => console.error("Gagal menghentikan scanner.", err));
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

            // === FACE RECOGNITION LOGIC ===
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
                            return;
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
                        return new Promise((resolve) => {
                            try {
                                const img = new Image();
                                img.crossOrigin = 'anonymous';
                                img.src = student.photo_url;

                                img.onload = async () => {
                                    try {
                                        const detections = await faceapi.detectSingleFace(img).withFaceLandmarks().withFaceDescriptor();
                                        if (!detections) {
                                            console.warn(`Wajah tidak terdeteksi pada foto profil: ${student.name}`);
                                            resolve(null);
                                            return;
                                        }
                                        resolve(new faceapi.LabeledFaceDescriptors(student.unique_id, [detections.descriptor]));
                                    } catch (e) {
                                        console.error(`Gagal deteksi AI foto ${student.name}:`, e);
                                        resolve(null);
                                    }
                                };

                                img.onerror = () => {
                                    console.error(`Gagal memuat URL foto untuk ${student.name} (CORS/URL Invalid)`);
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

                        if (currentFacingMode !== 'user') {
                            currentFacingMode = 'user';
                            startFaceVideo();
                        }
                    });
            }

            faceVideo.addEventListener('play', () => {
                const displaySize = { width: faceVideo.offsetWidth, height: faceVideo.offsetHeight };
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

                    if (detections.length > 0) {
                        const bestMatch = faceMatcher.findBestMatch(detections[0].descriptor);
                        if (bestMatch.label !== 'unknown') {
                            consecutiveMatches++;
                            faceStatus.textContent = `Wajah dikenali! Tahan posisi... (${consecutiveMatches}/3)`;

                            // Check Cooldown
                            if (consecutiveMatches >= 3 && Date.now() - lastScanTime > scanCooldown) {
                                lastScanTime = Date.now();
                                processPermit(bestMatch.label); // label is unique_id
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
                }, 500);
            });

            function startScanFlow() {
                readerError.classList.add('hidden');
                if (html5QrCode) {
                    html5QrCode.resume();
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userCoordinates = { latitude: position.coords.latitude, longitude: position.coords.longitude };
                        initializeScanner();
                    },
                    (error) => {
                        showResultModal({ status: 'location_error', message: 'Gagal mendapatkan lokasi GPS. Izinkan akses lokasi dan coba lagi.' });
                    }
                );
            }

            function initializeScanner() {
                html5QrCode = new Html5Qrcode("reader");
                Html5Qrcode.getCameras().then(devices => {
                    if (devices && devices.length) {
                        cameras = devices;
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

            function startScannerWithCamera(cameraId) {
                html5QrCode.start(
                    cameraId,
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    onScanSuccess,
                    (errorMessage) => { }
                ).catch((err) => {
                    readerError.textContent = "Gagal memulai kamera yang dipilih.";
                    readerError.classList.remove('hidden');
                });
            }

            function onScanSuccess(decodedText, decodedResult) {
                if (Date.now() - lastScanTime < scanCooldown) return;
                lastScanTime = Date.now();

                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.pause();
                }

                processPermit(decodedText);
            }

            function processPermit(studentId, reason = null) {
                let body = {
                    student_unique_id: studentId,
                    latitude: userCoordinates.latitude,
                    longitude: userCoordinates.longitude
                };
                if (reason) {
                    body.reason = reason;
                }

                fetch("{{ route('permit.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(body)
                }).then(response => response.json().then(data => ({ status: response.status, body: data })))
                    .then(({ status, body }) => {
                        if (body.status === 'reason_required') {
                            currentStudentId = studentId; // Simpan ID siswa
                            reasonModal.studentName.textContent = body.student_name;
                            showReasonModal();
                        } else {
                            if (status >= 400) body.status = body.status || 'error';
                            showResultModal(body);
                        }
                    }).catch(error => {
                        showResultModal({ status: 'error', message: 'Tidak dapat terhubung ke server.' });
                    });
            }

            function showReasonModal() {
                reasonModal.element.classList.remove('hidden');
                setTimeout(() => {
                    reasonModal.element.classList.remove('opacity-0');
                    reasonModal.content.classList.remove('scale-95');
                    reasonModal.reasonTextarea.focus();
                }, 10);
            }

            function hideReasonModal() {
                reasonModal.element.classList.add('opacity-0');
                reasonModal.content.classList.add('scale-95');
                setTimeout(() => {
                    reasonModal.element.classList.add('hidden');
                    reasonModal.form.reset();
                }, 300);
            }

            function showResultModal(data) {
                resultModal.iconContainer.className = 'mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5';
                resultModal.iconSvg.className = 'h-12 w-12';
                resultModal.iconSvg.innerHTML = '';

                let soundToPlay = 'error';
                switch (data.status) {
                    case 'permit_granted':
                        resultModal.iconContainer.classList.add('bg-green-100', 'dark:bg-green-900');
                        resultModal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />`;
                        resultModal.iconSvg.classList.add('text-green-600', 'dark:text-green-400');
                        soundToPlay = 'success';
                        break;
                    case 'clock_in_from_permit':
                        resultModal.iconContainer.classList.add('bg-blue-100', 'dark:bg-blue-900');
                        resultModal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="m11.25 9-3 3m0 0 3 3m-3-3h7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />`;
                        resultModal.iconSvg.classList.add('text-blue-600', 'dark:text-blue-400');
                        soundToPlay = 'success';
                        break;
                    default:
                        resultModal.iconContainer.classList.add('bg-red-100', 'dark:bg-red-900');
                        resultModal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />`;
                        resultModal.iconSvg.classList.add('text-red-600', 'dark:text-red-400');
                        break;
                }

                resultModal.title.textContent = data.message || 'Terjadi Kesalahan';
                resultModal.studentName.textContent = data.student_name || 'Siswa Tidak Dikenal';
                resultModal.message.textContent = data.time ? `Waktu: ${data.time}` : '';

                if (data.student_photo_url) {
                    resultModal.studentImage.src = data.student_photo_url;
                    resultModal.studentImage.classList.remove('hidden');
                    resultModal.studentPlaceholder.classList.add('hidden');
                } else {
                    resultModal.studentImage.classList.add('hidden');
                    resultModal.studentPlaceholder.classList.remove('hidden');
                }

                playSound(soundToPlay);
                resultModal.element.classList.remove('hidden');
                setTimeout(() => {
                    resultModal.element.classList.remove('opacity-0');
                    resultModal.content.classList.remove('scale-95');
                }, 10);

                setTimeout(hideResultModal, scanCooldown);
            }

            function hideResultModal() {
                resultModal.element.classList.add('opacity-0');
                resultModal.content.classList.add('scale-95');
                setTimeout(() => {
                    resultModal.element.classList.add('hidden');
                    if (!manualScannerDiv.classList.contains('hidden')) {
                        manualInput.value = '';
                        manualInput.focus();
                    } else if (!cameraScannerDiv.classList.contains('hidden')) {
                        if (html5QrCode) html5QrCode.resume();
                    } else if (!faceScannerDiv.classList.contains('hidden')) {
                        // Resume logic handled by interval
                    }
                }, 300);
            }

            function playSound(type) {
                let audioFile;
                if (type === 'success') { audioFile = "{{ asset('sounds/success.mp3') }}"; }
                else { audioFile = "{{ asset('sounds/error.mp3') }}"; }
                try { new Audio(audioFile).play(); } catch (e) { console.error("Gagal memainkan suara:", e); }
            }

            // === EVENT LISTENERS ===

            updateClock();
            setInterval(updateClock, 1000);

            useCameraButton.addEventListener('click', () => showScannerView('camera'));
            useManualButton.addEventListener('click', () => showScannerView('manual'));
            useFaceButton.addEventListener('click', () => showScannerView('face')); // NEW

            backButton.addEventListener('click', resetToChoiceView);
            switchButton.addEventListener('click', () => {
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().then(() => {
                        currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
                        startScannerWithCamera(cameras[currentCameraIndex].id);
                    });
                }
            });

            reasonModal.form.addEventListener('submit', () => {
                const reason = reasonModal.reasonTextarea.value;
                if (reason.trim() && currentStudentId) {
                    hideReasonModal();
                    processPermit(currentStudentId, reason);
                }
            });

            reasonModal.cancelButton.addEventListener('click', () => {
                hideReasonModal();
                // Lanjutkan kamera setelah batal
                if (html5QrCode) {
                    html5QrCode.resume();
                }
            });

            let inputTimeout = null;
            manualInput.addEventListener('input', () => {
                clearTimeout(inputTimeout);
                const studentId = manualInput.value.trim();
                if (studentId) {
                    inputTimeout = setTimeout(() => {
                        if (Date.now() - lastScanTime < scanCooldown) return;
                        lastScanTime = Date.now();

                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                userCoordinates = { latitude: position.coords.latitude, longitude: position.coords.longitude };
                                processPermit(studentId);
                                manualInput.value = '';
                            },
                            (error) => {
                                showResultModal({ status: 'location_error', message: 'Gagal mendapatkan lokasi GPS.' });
                            }
                        );
                    }, 100);
                }
            });

        });
    </script>
@endpush
</x-app-layout>