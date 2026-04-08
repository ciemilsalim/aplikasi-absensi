@extends('layouts.public')

@section('title', 'Pemindai Kehadiran Mapel')

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
    <div class="relative min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-900 px-4 py-12">
        <div class="w-full max-w-7xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Pemindai Kehadiran Mata Pelajaran</h1>
                <p class="text-slate-600 dark:text-slate-400">Arahkan kamera ke QR Code siswa untuk mencatat kehadiran.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 space-y-6">
                    <!-- Informasi Jadwal -->
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Informasi Pembelajaran</h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Mata Pelajaran</p>
                                <p class="font-semibold text-gray-800 dark:text-gray-200">
                                    {{ $schedule->teachingAssignment->subject->name }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Kelas</p>
                                <p class="font-semibold text-gray-800 dark:text-gray-200">
                                    {{ $schedule->teachingAssignment->schoolClass->name }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Waktu</p>
                                <p class="font-semibold text-gray-800 dark:text-gray-200">
                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Pemindai Kamera -->
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm rounded-lg p-6">
                        <!-- Scanner Type Tabs -->
                        <div class="flex space-x-4 mb-4 justify-center">
                            <button id="tab-qr"
                                class="px-4 py-2 text-sm font-medium text-white bg-sky-600 rounded-md shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                                Scan QR Code
                            </button>
                            <button id="tab-face"
                                class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 dark:bg-slate-700 dark:text-slate-200 dark:border-slate-600 dark:hover:bg-slate-600">
                                Scan Wajah
                            </button>
                        </div>

                        <div id="qr-scanner-container">
                            <div id="reader"
                                class="w-full max-w-sm mx-auto aspect-square bg-slate-100 dark:bg-slate-700 rounded-lg overflow-hidden">
                            </div>
                            <div id="camera-switch-container" class="mt-4 text-center hidden">
                                <button id="camera-switch-button"
                                    class="text-sm text-sky-600 dark:text-sky-400 hover:underline">Ganti Kamera</button>
                            </div>
                        </div>

                        <div id="face-scanner-container" class="hidden">
                            <div
                                class="relative w-full max-w-sm mx-auto aspect-square bg-slate-100 dark:bg-slate-700 rounded-lg overflow-hidden">
                                <video id="face-video" class="w-full h-full object-cover" autoplay muted
                                    playsinline></video>
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
                                        <div class="absolute inset-4 border-2 border-dashed border-white/40 rounded-[100%]">
                                        </div>
                                    </div>
                                </div>

                                <!-- Loading overlay -->
                                <div id="face-loading-overlay"
                                    class="absolute inset-0 flex flex-col items-center justify-center bg-black/60 backdrop-blur-sm z-20 hidden">
                                    <svg class="animate-spin h-10 w-10 text-sky-500 mb-4" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
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
                            <p id="face-status" class="mt-4 text-center text-sm text-slate-600 dark:text-slate-400">
                                Menyiapkan kamera...</p>
                            <div id="face-camera-switch-container" class="mt-4 text-center">
                                <button id="face-camera-switch-button"
                                    class="text-sm text-sky-600 dark:text-sky-400 hover:underline flex items-center justify-center mx-auto gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                    Ganti Kamera
                                </button>
                            </div>
                        </div>

                        <div id="reader-error" class="text-red-500 text-sm mt-4 text-center hidden"></div>
                    </div>
                </div>

                <div class="lg:col-span-1 space-y-6">
                    <!-- Daftar Hadir -->
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Siswa Hadir (<span
                                    id="attended-count">{{ $attendedStudents->count() }}</span>)</h3>
                        </div>
                        <div class="border-t border-gray-200 dark:border-slate-700">
                            <ul id="attended-list"
                                class="divide-y divide-gray-200 dark:divide-slate-700 max-h-[60vh] overflow-y-auto">
                                @forelse($attendedStudents as $attendance)
                                    <li class="p-4 flex items-center justify-between">
                                        <span
                                            class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ $attendance->student->name }}</span>
                                        <span
                                            class="text-xs text-gray-500 dark:text-gray-400">{{ $attendance->created_at->format('H:i:s') }}</span>
                                    </li>
                                @empty
                                    <li id="no-students-yet" class="p-4 text-center text-sm text-gray-500 italic">
                                        Belum ada siswa yang diabsen hadir.
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    <!-- Panel Siswa Izin/Sakit -->
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Siswa Izin/Sakit</h3>
                        </div>
                        <div class="border-t border-gray-200 dark:border-slate-700">
                            <ul id="leave-list"
                                class="divide-y divide-gray-200 dark:divide-slate-700 max-h-[30vh] overflow-y-auto">
                                @forelse($studentsOnLeave as $subjectAttendance)
                                    <li class="p-4 flex items-center justify-between">
                                        <span
                                            class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ $subjectAttendance->student->name }}</span>
                                        <span
                                            class="px-2 py-1 text-xs font-semibold rounded-full 
                                                                                                @if($subjectAttendance->status == 'sakit') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300 @endif
                                                                                                @if($subjectAttendance->status == 'izin') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 @endif
                                                                                            ">{{ ucfirst($subjectAttendance->status) }}</span>
                                    </li>
                                @empty
                                    <li id="no-students-on-leave" class="p-4 text-center text-sm text-gray-500 italic">
                                        Tidak ada siswa yang izin/sakit.
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    <!-- Panel Siswa Tanpa Keterangan -->
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Siswa Tanpa Keterangan (<span
                                    id="no-notice-count">{{ $studentsWithoutNotice->count() }}</span>)</h3>
                        </div>
                        <div class="border-t border-gray-200 dark:border-slate-700">
                            <ul id="no-notice-list"
                                class="divide-y divide-gray-200 dark:divide-slate-700 max-h-[30vh] overflow-y-auto">
                                @forelse($studentsWithoutNotice as $student)
                                    <li class="p-4 flex items-center justify-between" id="student-no-notice-{{$student->id}}">
                                        <span
                                            class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ $student->name }}</span>
                                        <div class="flex items-center gap-1">
                                            <button data-student-id="{{ $student->id }}" data-status="sakit"
                                                class="manual-mark-btn px-2 py-1 text-xs font-medium text-amber-800 bg-amber-100 hover:bg-amber-200 rounded-full">S</button>
                                            <button data-student-id="{{ $student->id }}" data-status="izin"
                                                class="manual-mark-btn px-2 py-1 text-xs font-medium text-purple-800 bg-purple-100 hover:bg-purple-200 rounded-full">I</button>
                                            <button data-student-id="{{ $student->id }}" data-status="alpa"
                                                class="manual-mark-btn px-2 py-1 text-xs font-medium text-red-800 bg-red-100 hover:bg-red-200 rounded-full">A</button>
                                            <button data-student-id="{{ $student->id }}" data-status="bolos"
                                                class="manual-mark-btn px-2 py-1 text-xs font-medium text-gray-800 bg-gray-200 hover:bg-gray-300 rounded-full">B</button>
                                        </div>
                                    </li>
                                @empty
                                    <li id="no-missing-students" class="p-4 text-center text-sm text-gray-500 italic">
                                        Semua siswa telah memiliki keterangan.
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>

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
            <p id="modal-message" class="text-md text-slate-500 dark:text-slate-400 mb-6"></p>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let lastScanTime = 0;
            const scanCooldown = 3000;
            const scheduleId = {{ $schedule->id }};

            // Face Recognition Variables
            const studentsWithPhotos = @json($studentsForFaceRecognition);
            let faceMatcher = null;
            let isModelsLoaded = false;
            let faceScanInterval = null;
            let currentMode = 'qr'; // 'qr' or 'face'
            let consecutiveMatches = 0;
            let currentFacingMode = 'user';

            // DOM Elements
            const readerError = document.getElementById('reader-error');
            const switchContainer = document.getElementById('camera-switch-container');
            const switchButton = document.getElementById('camera-switch-button');
            const attendedList = document.getElementById('attended-list');
            const attendedCount = document.getElementById('attended-count');
            const noStudentsYet = document.getElementById('no-students-yet');
            const noNoticeList = document.getElementById('no-notice-list');
            const noNoticeCount = document.getElementById('no-notice-count');
            const noMissingStudents = document.getElementById('no-missing-students');
            const leaveList = document.getElementById('leave-list');
            const noStudentsOnLeave = document.getElementById('no-students-on-leave');

            const tabQr = document.getElementById('tab-qr');
            const tabFace = document.getElementById('tab-face');
            const qrScannerContainer = document.getElementById('qr-scanner-container');
            const faceScannerContainer = document.getElementById('face-scanner-container');
            const faceVideo = document.getElementById('face-video');
            const faceCanvas = document.getElementById('face-canvas');
            const faceStatus = document.getElementById('face-status');
            const faceSwitchButton = document.getElementById('face-camera-switch-button');

            const modal = {
                element: document.getElementById('attendance-modal'),
                content: document.getElementById('modal-content'),
                iconContainer: document.getElementById('modal-icon-container'),
                iconSvg: document.getElementById('modal-icon-svg'),
                title: document.getElementById('modal-title'),
                message: document.getElementById('modal-message'),
            };

            let html5QrCode = new Html5Qrcode("reader");
            let cameras = [];
            let currentCameraIndex = 0;

            // === TABS LOGIC ===
            tabQr.addEventListener('click', () => switchMode('qr'));
            tabFace.addEventListener('click', () => switchMode('face'));

            function switchMode(mode) {
                if (currentMode === mode) return;
                currentMode = mode;
                readerError.classList.add('hidden');

                if (mode === 'qr') {
                    // Update UI
                    tabQr.classList.remove('bg-white', 'text-slate-700', 'border', 'border-slate-300');
                    tabQr.classList.add('text-white', 'bg-sky-600');
                    tabFace.classList.remove('text-white', 'bg-sky-600');
                    tabFace.classList.add('bg-white', 'text-slate-700', 'border', 'border-slate-300');

                    faceScannerContainer.classList.add('hidden');
                    qrScannerContainer.classList.remove('hidden');

                    // Stop Face, Start QR
                    stopFaceScanner();
                    startQrScanner();
                } else {
                    // Update UI
                    tabFace.classList.remove('bg-white', 'text-slate-700', 'border', 'border-slate-300');
                    tabFace.classList.add('text-white', 'bg-sky-600');
                    tabQr.classList.remove('text-white', 'bg-sky-600');
                    tabQr.classList.add('bg-white', 'text-slate-700', 'border', 'border-slate-300');

                    qrScannerContainer.classList.add('hidden');
                    faceScannerContainer.classList.remove('hidden');

                    // Stop QR, Start Face
                    stopQrScanner();
                    startFaceScanner();
                }
            }

            // === FACE RECOGNITION LOGIC ===
            async function loadFaceModels() {
                if (isModelsLoaded) return true;

                const overlay = document.getElementById('face-loading-overlay');
                const bar = document.getElementById('face-loading-bar');
                const text = document.getElementById('face-loading-text');
                if (overlay) overlay.classList.remove('hidden');

                faceStatus.textContent = 'Memuat model wajah...';
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
                    faceStatus.textContent = 'Gagal memuat model wajah. Periksa interent.';
                    if (overlay) overlay.classList.add('hidden');
                    return false;
                }
            }

            async function startFaceScanner() {
                if (!await loadFaceModels()) return;

                if (!faceMatcher) {
                    faceStatus.textContent = 'Memproses data wajah... (Mohon tunggu)';
                    try {
                        const labeledDescriptors = await loadLabeledImages();
                        if (labeledDescriptors.length === 0) {
                            faceStatus.textContent = 'Tidak ada data wajah valid yang dapat dimuat. Pastikan foto kelas ini jelas.';
                            return;
                        }
                        faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.5);
                    } catch (error) {
                        faceStatus.textContent = 'Terjadi kesalahan sistem saat memproses wajah.';
                        console.error(error);
                        return;
                    }
                }

                faceStatus.textContent = 'Menyalakan kamera...';
                navigator.mediaDevices.getUserMedia({
                    video: { facingMode: currentFacingMode }
                })
                    .then(stream => {
                        faceVideo.srcObject = stream;
                    })
                    .catch(err => {
                        console.error("Gagal akses kamera:", err);
                        readerError.textContent = "Gagal mengakses kamera. Cek izin browser.";
                        readerError.classList.remove('hidden');

                        if (currentFacingMode !== 'user') {
                            currentFacingMode = 'user';
                            startFaceScanner();
                        }
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
                startFaceScanner();
            });

            function stopFaceScanner() {
                if (faceVideo.srcObject) {
                    faceVideo.srcObject.getTracks().forEach(track => track.stop());
                    faceVideo.srcObject = null;
                }
                if (faceScanInterval) {
                    clearInterval(faceScanInterval);
                    faceScanInterval = null;
                }
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
                                        const options = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 });
                                        const detections = await faceapi.detectSingleFace(img, options).withFaceLandmarks().withFaceDescriptor();
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

            faceVideo.addEventListener('play', () => {
                const displaySize = { width: faceVideo.offsetWidth, height: faceVideo.offsetHeight };
                if (displaySize.width === 0 || displaySize.height === 0) return;

                faceapi.matchDimensions(faceCanvas, displaySize);
                faceStatus.textContent = 'Arahkan wajah ke kamera...';

                faceScanInterval = setInterval(async () => {
                    if (faceVideo.paused || faceVideo.ended) return;

                    const detections = await faceapi.detectAllFaces(faceVideo, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 }))
                        .withFaceLandmarks()
                        .withFaceDescriptors();

                    const resizedDetections = faceapi.resizeResults(detections, displaySize);
                    faceCanvas.getContext('2d').clearRect(0, 0, faceCanvas.width, faceCanvas.height);

                    if (detections.length > 0) {
                        const bestMatch = faceMatcher.findBestMatch(detections[0].descriptor);
                        if (bestMatch.label !== 'unknown') {
                            consecutiveMatches++;
                            faceStatus.textContent = `Wajah dikenali! Tahan posisi... (${consecutiveMatches}/3)`;

                            if (consecutiveMatches >= 3 && Date.now() - lastScanTime > scanCooldown) {
                                lastScanTime = Date.now();
                                processAttendance(bestMatch.label);
                                consecutiveMatches = 0;
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


            // === QR SCANNER LOGIC ===
            function startQrScanner() {
                Html5Qrcode.getCameras().then(devices => {
                    if (devices && devices.length) {
                        cameras = devices;
                        let backCameraIndex = cameras.findIndex(camera => camera.label.toLowerCase().includes('back')); // Prioritize back camera
                        currentCameraIndex = backCameraIndex !== -1 ? backCameraIndex : 0;

                        startScannerWithCamera(cameras[currentCameraIndex].id);
                        if (cameras.length > 1) {
                            switchContainer.classList.remove('hidden');
                        }
                    } else { throw new Error("Tidak ada kamera ditemukan."); }
                }).catch(err => {
                    readerError.textContent = "Gagal mengakses kamera: " + err.message;
                    readerError.classList.remove('hidden');
                });
            }

            function stopQrScanner() {
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().catch(err => console.error("Found dead QR scanner", err));
                }
            }

            function playSound(isSuccess) {
                const soundFile = isSuccess
                    ? "{{ asset('sounds/success.mp3') }}"
                    : "{{ asset('sounds/error.mp3') }}";

                try {
                    const audio = new Audio(soundFile);
                    audio.play();
                } catch (e) {
                    console.error("Gagal memutar suara:", e);
                }
            }

            function onScanSuccess(decodedText, decodedResult) {
                if (Date.now() - lastScanTime < scanCooldown) return;
                lastScanTime = Date.now();
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.pause();
                }
                processAttendance(decodedText);
            }

            function processAttendance(studentId) {
                fetch("{{ route('teacher.subject.attendance.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        student_unique_id: studentId,
                        schedule_id: scheduleId
                    })
                }).then(response => response.json().then(data => ({ status: response.status, body: data })))
                    .then(({ status, body }) => {
                        showModal(body.success, body);
                    }).catch(error => {
                        showModal(false, { message: 'Tidak dapat terhubung ke server.' });
                    });
            }

            function removeStudentFromNoNoticeList(studentId) {
                const studentRow = document.getElementById(`student-no-notice-${studentId}`);
                if (studentRow) {
                    studentRow.style.transition = 'opacity 0.5s';
                    studentRow.style.opacity = '0';
                    setTimeout(() => {
                        studentRow.remove();
                        noNoticeCount.textContent = parseInt(noNoticeCount.textContent) - 1;
                        if (noNoticeList.children.length === 0 && noMissingStudents) {
                            noMissingStudents.classList.remove('hidden');
                        }
                    }, 500);
                }
            }

            function addStudentToList(name, time) {
                if (noStudentsYet) {
                    noStudentsYet.classList.add('hidden');
                }
                const listItem = document.createElement('li');
                listItem.className = 'p-4 flex items-center justify-between animate-[fade-in_0.5s]';
                listItem.innerHTML = `<span class="font-medium text-sm text-gray-800 dark:text-gray-200">${name}</span>
                                                          <span class="text-xs text-gray-500 dark:text-gray-400">${time}</span>`;
                attendedList.prepend(listItem);
                attendedCount.textContent = parseInt(attendedCount.textContent) + 1;
            }

            function addStudentToLeaveList(name, status) {
                if (noStudentsOnLeave) noStudentsOnLeave.classList.add('hidden');

                const statusClass = status === 'sakit'
                    ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300'
                    : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300';

                const statusText = status.charAt(0).toUpperCase() + status.slice(1);

                const listItem = document.createElement('li');
                listItem.className = 'p-4 flex items-center justify-between animate-[fade-in_0.5s]';
                listItem.innerHTML = `<span class="font-medium text-sm text-gray-800 dark:text-gray-200">${name}</span>
                                                          <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">${statusText}</span>`;
                leaveList.prepend(listItem);
            }

            function showModal(isSuccess, data) {
                playSound(isSuccess);
                modal.iconContainer.className = 'mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5';
                modal.iconSvg.innerHTML = '';

                if (isSuccess) {
                    modal.iconContainer.classList.add('bg-green-100', 'dark:bg-green-900');
                    modal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />`;
                    modal.iconSvg.classList.add('text-green-600', 'dark:text-green-400');
                    modal.title.textContent = 'Berhasil';
                    if (data.student) {
                        // Logika untuk memindahkan siswa ke daftar yang sesuai
                        if (data.student.status === 'sakit' || data.student.status === 'izin') {
                            addStudentToLeaveList(data.student.name, data.student.status);
                            removeStudentFromNoNoticeList(data.student.id);
                        } else if (data.student.time) { // Jika ada 'time', berarti dari scan (hadir)
                            addStudentToList(data.student.name, data.student.time);
                            removeStudentFromNoNoticeList(data.student.id);
                        } else { // Jika status lain (alpa/bolos) dari manual mark
                            removeStudentFromNoNoticeList(data.student.id);
                        }
                    }
                } else {
                    modal.iconContainer.classList.add('bg-red-100', 'dark:bg-red-900');
                    modal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />`;
                    modal.iconSvg.classList.add('text-red-600', 'dark:text-red-400');
                    modal.title.textContent = 'Gagal';
                }

                modal.message.textContent = data.message;

                modal.element.classList.remove('hidden');
                setTimeout(() => {
                    modal.element.classList.remove('opacity-0');
                    modal.content.classList.remove('scale-95');
                }, 10);

                setTimeout(hideModal, scanCooldown - 500);
            }

            function hideModal() {
                modal.element.classList.add('opacity-0');
                modal.content.classList.add('scale-95');
                setTimeout(() => {
                    modal.element.classList.add('hidden');
                    if (currentMode === 'qr' && html5QrCode && html5QrCode.isScanning) {
                        html5QrCode.resume();
                    }
                    // No resume need for face scanner as it runs on interval
                }, 300);
            }

            function startScannerWithCamera(cameraId) {
                html5QrCode.start(
                    cameraId,
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    onScanSuccess,
                    (errorMessage) => { }
                ).catch((err) => {
                    readerError.textContent = "Gagal memulai kamera. Pastikan Anda memberikan izin akses kamera.";
                    readerError.classList.remove('hidden');
                });
            }

            function handleManualMark(event) {
                const button = event.target;
                const studentId = button.dataset.studentId;
                const status = button.dataset.status;

                fetch("{{ route('teacher.subject.attendance.mark_manual') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        schedule_id: scheduleId,
                        status: status
                    })
                }).then(response => response.json().then(data => ({ status: response.status, body: data })))
                    .then(({ status, body }) => {
                        showModal(body.success, body);
                    }).catch(error => {
                        showModal(false, { message: 'Tidak dapat terhubung ke server.' });
                    });
            }

            document.querySelectorAll('.manual-mark-btn').forEach(button => {
                button.addEventListener('click', handleManualMark);
            });

            // Start with QR Scanner
            startQrScanner();

            switchButton.addEventListener('click', () => {
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().then(() => {
                        currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
                        startScannerWithCamera(cameras[currentCameraIndex].id);
                    });
                }
            });

        });
    </script>
@endpush