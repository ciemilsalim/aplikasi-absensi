@extends('layouts.public')

@section('title', 'Pemindai Izin Keluar/Kembali')

@section('content')
<div class="relative min-h-[calc(100vh-128px)] flex items-center justify-center overflow-hidden px-4">
    <!-- Latar Belakang Abstrak -->
    <div class="absolute inset-0 -z-10">
        <div class="absolute inset-0 bg-white dark:bg-slate-900"></div>
        <div class="absolute bottom-0 left-0 right-0 h-1/2 bg-slate-50 dark:bg-slate-800/50" style="clip-path: polygon(0 100%, 100% 100%, 100% 0, 0 100%);"></div>
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-sky-200/50 dark:bg-sky-900/50 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-indigo-200/50 dark:bg-indigo-900/50 rounded-full blur-3xl animate-pulse [animation-delay:-2s]"></div>
    </div>

    <div class="w-full max-w-xl text-center">
        <!-- Jam Digital dan Tanggal -->
        <div class="mb-6 animate-[fade-in-up_0.8s_ease-out_forwards]">
            <p id="current-date" class="text-lg text-slate-600 dark:text-slate-400"></p>
            <p id="current-time" class="text-5xl font-bold text-sky-600 dark:text-sky-400 tracking-tight"></p>
        </div>

        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-lg p-6 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 animate-[fade-in-up_0.8s_ease-out_forwards]" style="animation-delay: 0.4s;">
            
            <div id="scanner-choice">
                <h1 class="text-3xl font-bold text-slate-800 dark:text-white mb-2">Pilih Tipe Pemindai Izin</h1>
                <p class="text-slate-600 dark:text-slate-400 mb-8">Pilih metode yang akan Anda gunakan untuk mencatat izin siswa.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button id="use-camera-button" class="w-full inline-flex flex-col items-center justify-center p-6 border border-transparent text-base font-medium rounded-md text-white bg-sky-600 hover:bg-sky-700 transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 mb-2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.776 48.776 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" /></svg>
                        Pindai dengan Kamera
                    </button>
                    <button id="use-manual-button" class="w-full inline-flex flex-col items-center justify-center p-6 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 mb-2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 013.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h4.5a1.875 1.875 0 011.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 01-.937-.938v-3.375c0-.517.42-.938.938-.938z" /></svg>
                        Input Manual / Eksternal
                    </button>
                </div>
            </div>

            <div id="camera-scanner" class="hidden">
                <div id="reader" class="w-full max-w-sm mx-auto aspect-square bg-slate-100 dark:bg-slate-700 rounded-lg overflow-hidden"></div>
                <div id="camera-switch-container" class="mt-4 text-center hidden">
                    <button id="camera-switch-button" class="text-sm text-sky-600 dark:text-sky-400 hover:underline">Ganti Kamera</button>
                </div>
            </div>

            <div id="manual-scanner" class="hidden">
                <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">Input ID Manual</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-6">Arahkan pemindai eksternal ke kolom di bawah atau ketik ID siswa.</p>
                <form id="manual-form">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-slate-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 013.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h4.5a1.875 1.875 0 011.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 01-.937-.938v-3.375c0-.517.42-.938.938-.938z" />
                            </svg>
                        </div>
                        <x-text-input id="manual_input_id" class="block w-full text-center text-lg pl-10" type="text" name="manual_input_id" placeholder="ID Siswa" required autofocus />
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
<div id="reason-modal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm hidden">
    <div class="w-full max-w-md p-6 bg-white dark:bg-slate-800 rounded-2xl shadow-xl">
        <form id="reason-form">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Alasan Izin Keluar</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Siswa <strong id="modal-student-name-reason"></strong> akan izin keluar. Mohon isi alasannya.</p>
            <div class="mt-4">
                <x-input-label for="reason" value="Alasan" />
                <textarea id="reason" name="reason" rows="3" class="block w-full mt-1 border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 rounded-md shadow-sm" required></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-4">
                <x-secondary-button type="button" id="cancel-reason">Batal</x-secondary-button>
                <x-primary-button type="submit">Simpan Izin</x-primary-button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Pop-up Hasil Scan -->
<div id="result-modal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0 hidden z-50">
    <div id="modal-content" class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm p-8 text-center transform scale-95 transition-all duration-300">
        <div id="modal-icon-container" class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5">
            <svg id="modal-icon-svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"></svg>
        </div>
        <h2 id="modal-title" class="text-2xl font-bold text-slate-800 dark:text-white mb-2"></h2>
        <div class="mt-4 mb-4">
            <span class="inline-block h-24 w-24 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-700">
                <svg class="h-full w-full text-slate-300 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </span>
        </div>
        <p id="modal-student-name" class="text-xl font-semibold text-sky-700 dark:text-sky-400"></p>
        <p id="modal-time" class="text-md text-slate-500 dark:text-slate-400 mb-6"></p>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let userCoordinates = null;
        let lastScanTime = 0;
        const scanCooldown = 4000;
        let currentStudentId = null;

        const scannerChoiceDiv = document.getElementById('scanner-choice');
        const cameraScannerDiv = document.getElementById('camera-scanner');
        const manualScannerDiv = document.getElementById('manual-scanner');
        const useCameraButton = document.getElementById('use-camera-button');
        const useManualButton = document.getElementById('use-manual-button');
        const backButton = document.getElementById('back-to-choice');
        const manualForm = document.getElementById('manual-form');
        const manualInput = document.getElementById('manual_input_id');

        const readerDiv = document.getElementById('reader');
        const readerError = document.getElementById('reader-error');
        const switchContainer = document.getElementById('camera-switch-container');
        const switchButton = document.getElementById('camera-switch-button');
        
        const reasonModal = document.getElementById('reason-modal');
        const reasonForm = document.getElementById('reason-form');
        const cancelReasonBtn = document.getElementById('cancel-reason');
        const modalStudentNameReason = document.getElementById('modal-student-name-reason');

        const resultModal = {
            element: document.getElementById('result-modal'),
            content: document.getElementById('modal-content'),
            iconContainer: document.getElementById('modal-icon-container'),
            iconSvg: document.getElementById('modal-icon-svg'),
            title: document.getElementById('modal-title'),
            studentName: document.getElementById('modal-student-name'),
            time: document.getElementById('modal-time'),
        };

        let html5QrCode = null;
        let cameras = [];
        let currentCameraIndex = 0;

        function updateClock() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit'});
            document.getElementById('current-date').textContent = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        updateClock(); 
        setInterval(updateClock, 1000);

        useCameraButton.addEventListener('click', () => {
            scannerChoiceDiv.classList.add('hidden');
            cameraScannerDiv.classList.remove('hidden');
            backButton.classList.remove('hidden');
            startScanFlow();
        });

        useManualButton.addEventListener('click', () => {
            scannerChoiceDiv.classList.add('hidden');
            manualScannerDiv.classList.remove('hidden');
            backButton.classList.remove('hidden');
            manualInput.focus();
        });

        backButton.addEventListener('click', () => {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop();
            }
            cameraScannerDiv.classList.add('hidden');
            manualScannerDiv.classList.add('hidden');
            scannerChoiceDiv.classList.remove('hidden');
            backButton.classList.add('hidden');
            readerError.classList.add('hidden');
        });

        let inputTimeout = null;
        manualInput.addEventListener('input', (e) => {
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
                            processPermit(studentId);
                        },
                        (error) => {
                            showResultModal({ message: 'Gagal mendapatkan lokasi GPS.', student_name: 'Izinkan akses lokasi dan coba lagi.' }, true);
                        }
                    );
                }, 100);
            }
        });

        function startScanFlow() {
            readerError.classList.add('hidden');
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
                (errorMessage) => {}
            ).catch((err) => {
                readerError.textContent = "Gagal memulai kamera yang dipilih.";
                readerError.classList.remove('hidden');
            });
        }

        switchButton.addEventListener('click', () => {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
                    startScannerWithCamera(cameras[currentCameraIndex].id);
                });
            }
        });

        async function onScanSuccess(decodedText, decodedResult) {
            if (Date.now() - lastScanTime < scanCooldown) return;
            lastScanTime = Date.now();
            
            if (html5QrCode && html5QrCode.isScanning) {
                await html5QrCode.stop();
            }
            await processPermit(decodedText);
        }

        async function processPermit(studentId, reason = null) {
            try {
                const formData = new FormData();
                formData.append('student_unique_id', studentId);
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('latitude', userCoordinates.latitude);
                formData.append('longitude', userCoordinates.longitude);
                if (reason) {
                    formData.append('reason', reason);
                }

                const response = await fetch('{{ route("permit.store") }}', { method: 'POST', body: formData });
                const data = await response.json();

                if (response.ok) {
                    showResultModal(data, false);
                } else {
                    if (data.status === 'reason_required') {
                        currentStudentId = studentId;
                        modalStudentNameReason.textContent = data.student_name;
                        reasonModal.classList.remove('hidden');
                    } else {
                        showResultModal(data, true);
                    }
                }
            } catch (error) {
                showResultModal({ message: 'Kesalahan Jaringan', student_name: 'Tidak dapat terhubung ke server.' }, true);
            }
        }
        
        reasonForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const reason = document.getElementById('reason').value;
            reasonModal.classList.add('hidden');
            processPermit(currentStudentId, reason);
            this.reset();
        });

        cancelReasonBtn.addEventListener('click', function() {
            reasonModal.classList.add('hidden');
            reasonForm.reset();
            resetUI();
        });

        function showResultModal(data, isError) {
            const icons = {
                permit_granted: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />`,
                clock_in_from_permit: `<path stroke-linecap="round" stroke-linejoin="round" d="m11.25 9-3 3m0 0 3 3m-3-3h7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />`,
                error: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />`
            };
            const colors = {
                permit_granted: 'green', clock_in_from_permit: 'blue', error: 'red'
            };
            const statusType = isError ? 'error' : (data.status || 'permit_granted');
            const color = colors[statusType] || 'red';

            resultModal.iconContainer.className = `mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5 bg-${color}-100 dark:bg-${color}-900`;
            resultModal.iconSvg.innerHTML = icons[statusType] || icons.error;
            resultModal.iconSvg.className = `h-12 w-12 text-${color}-600 dark:text-${color}-400`;
            resultModal.title.textContent = data.message;
            resultModal.studentName.textContent = data.student_name;
            resultModal.time.textContent = data.time || '';

            playSound(isError ? 'error' : 'success');

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
                if (cameraScannerDiv.classList.contains('hidden')) {
                    manualInput.value = '';
                    manualInput.focus();
                } else {
                    startScanFlow();
                }
            }, 300);
        }

        function playSound(type) {
            let audioFile;
            if (type === 'success') { audioFile = "{{ asset('sounds/success.mp3') }}"; } 
            else if (type === 'warning') { audioFile = "{{ asset('sounds/warning.mp3') }}"; } 
            else { audioFile = "{{ asset('sounds/error.mp3') }}"; }
            try { new Audio(audioFile).play(); } catch (e) { console.error("Gagal memainkan suara:", e); }
        }
        
        function resetUI() {
            cameraScannerDiv.classList.add('hidden');
            manualScannerDiv.classList.add('hidden');
            scannerChoiceDiv.classList.remove('hidden');
            backButton.classList.add('hidden');
            readerError.classList.add('hidden');
        }
    });
</script>
@endpush
