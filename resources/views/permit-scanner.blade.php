@extends('layouts.public')

@section('title', 'Pemindai Izin Keluar/Kembali')

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
                    <h1 class="text-3xl font-bold text-slate-800 dark:text-white mb-2">Pilih Tipe Pemindai Izin</h1>
                    <p class="text-slate-600 dark:text-slate-400 mb-8">Pilih metode untuk mencatat izin keluar atau kembali
                        siswa.</p>
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

                <div id="reader-error" class="text-red-500 text-sm mt-4 text-center hidden"></div>
                <button id="back-to-choice" class="mt-4 text-sm text-slate-500 dark:text-slate-400 hover:underline hidden">
                    &larr; Kembali ke Pilihan
                </button>
            </div>
        </div>
    </div>

    <!-- Modal untuk Alasan Izin -->
    <div id="reason-modal"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4 hidden opacity-0 transition-opacity duration-300">
        <div id="reason-modal-content"
            class="w-full max-w-md p-6 bg-white dark:bg-slate-800 rounded-2xl shadow-xl transform scale-95 transition-all duration-300">
            <form id="reason-form" onsubmit="return false;">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Alasan Izin Keluar</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Siswa <strong
                        id="reason-modal-student-name"></strong> akan izin keluar. Mohon isi alasannya.</p>
                <div class="mt-4">
                    <label for="reason"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 text-left">Alasan</label>
                    <textarea id="reason" name="reason" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-slate-300"
                        required></textarea>
                </div>
                <div class="mt-6 flex justify-end gap-4">
                    <button type="button" id="cancel-reason-button"
                        class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-slate-700 dark:text-gray-300 dark:border-slate-600 dark:hover:bg-slate-600">Batal</button>
                    <button type="submit" id="submit-reason-button"
                        class="inline-flex justify-center rounded-md border border-transparent bg-sky-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Simpan
                        Izin</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Modal Pop-up Hasil -->
    <div id="result-modal"
        class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0 hidden z-50">
        <div id="result-modal-content"
            class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm p-8 text-center transform scale-95 transition-all duration-300">
            <div id="result-modal-icon-container"
                class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5">
                <svg id="result-modal-icon-svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor"></svg>
            </div>
            <h2 id="result-modal-title" class="text-2xl font-bold text-slate-800 dark:text-white mb-2"></h2>
            <div class="mt-4 mb-4">
                <span id="result-modal-student-image-container"
                    class="inline-block h-24 w-24 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-700">
                    <img id="result-modal-student-image" src="" alt="Foto Siswa" class="h-full w-full object-cover hidden">
                    <svg id="result-modal-student-placeholder" class="h-full w-full text-slate-300 dark:text-slate-500"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </span>
            </div>
            <p id="result-modal-student-name" class="text-xl font-semibold text-sky-700 dark:text-sky-400"></p>
            <p id="result-modal-message" class="text-md text-slate-500 dark:text-slate-400 mb-6"></p>
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