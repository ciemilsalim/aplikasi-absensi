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
        <div class="mb-6">
            <p id="current-date" class="text-lg text-slate-600 dark:text-slate-400"></p>
            <p id="current-time" class="text-5xl font-bold text-sky-600 dark:text-sky-400 tracking-tight"></p>
        </div>

        <h1 class="text-3xl font-bold text-slate-800 dark:text-white mb-2">Pindai QR Code Izin</h1>
        <p class="text-slate-600 dark:text-slate-400 mb-8">Tekan tombol di bawah untuk memulai pemindaian izin keluar atau kembali.</p>

        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-lg p-6 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700">
            <div id="reader" class="w-full max-w-sm mx-auto aspect-square bg-slate-100 dark:bg-slate-700 rounded-lg overflow-hidden hidden"></div>
            
            <div id="scanner-controls">
                <button id="start-scan-button" class="w-full inline-flex items-center justify-center px-6 py-4 border border-transparent text-base font-medium rounded-md text-white bg-sky-600 hover:bg-sky-700 disabled:bg-slate-400 disabled:cursor-not-allowed transition-all duration-300">
                    <svg id="button-icon" class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h4.5a1.875 1.875 0 0 1 1.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 0 1-.937-.938v-3.375c0-.517.42-.938.938-.938z" /></svg>
                    <span id="button-text">Mulai Pindai Izin</span>
                </button>
            </div>
            
            <div id="camera-switch-container" class="mt-4 text-center hidden">
                <button id="camera-switch-button" class="text-sm text-sky-600 dark:text-sky-400 hover:underline">Ganti Kamera</button>
            </div>

            <div id="reader-error" class="text-red-500 text-sm mt-2 hidden"></div>
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

        const readerDiv = document.getElementById('reader');
        const readerError = document.getElementById('reader-error');
        const controlsDiv = document.getElementById('scanner-controls');
        const startButton = document.getElementById('start-scan-button');
        const buttonText = document.getElementById('button-text');
        const buttonIcon = document.getElementById('button-icon');
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

        startButton.addEventListener('click', () => {
            startButton.disabled = true;
            buttonText.textContent = 'Meminta Izin Lokasi...';
            buttonIcon.innerHTML = `<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>`;
            buttonIcon.classList.add('animate-spin');
            readerError.classList.add('hidden');

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userCoordinates = { latitude: position.coords.latitude, longitude: position.coords.longitude };
                    initializeScanner();
                },
                (error) => {
                    readerError.textContent = 'Gagal mendapatkan lokasi. Izinkan akses lokasi dan coba lagi.';
                    readerError.classList.remove('hidden');
                    resetButtonState();
                }, { enableHighAccuracy: true }
            );
        });

        function initializeScanner() {
            controlsDiv.classList.add('hidden');
            readerDiv.classList.remove('hidden');
            html5QrCode = new Html5Qrcode("reader");

            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    cameras = devices;
                    currentCameraIndex = cameras.findIndex(c => c.label.toLowerCase().includes('back')) !== -1 ? cameras.findIndex(c => c.label.toLowerCase().includes('back')) : 0;
                    startScannerWithCamera(cameras[currentCameraIndex].id);
                    if (cameras.length > 1) switchContainer.classList.remove('hidden');
                } else { throw new Error("Tidak ada kamera ditemukan."); }
            }).catch(err => {
                readerError.textContent = "Gagal mengakses kamera: " + err.message;
                readerError.classList.remove('hidden');
                resetUI();
            });
        }

        function startScannerWithCamera(cameraId) {
            html5QrCode.start(
                cameraId, 
                { fps: 10, qrbox: { width: 250, height: 250 } },
                onScanSuccess,
                (errorMessage) => {}
            ).catch((err) => {
                readerError.textContent = "Gagal memulai kamera.";
                readerError.classList.remove('hidden');
                resetUI();
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
            
            await html5QrCode.stop();
            await processPermit(decodedText);
        }

        async function processPermit(studentId, reason = null) {
            try {
                const formData = new FormData();
                formData.append('student_unique_id', studentId);
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('latitude', userCoordinates.latitude);
                formData.append('longitude', userCoordinates.longitude);
                if (reason) formData.append('reason', reason);

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

            setTimeout(hideResultModal, scanCooldown - 500);
        }

        function hideResultModal() {
            resultModal.element.classList.add('opacity-0');
            resultModal.content.classList.add('scale-95');
            setTimeout(() => {
                resultModal.element.classList.add('hidden');
                resetUI();
            }, 300);
        }

        function resetButtonState() {
            startButton.disabled = false;
            buttonText.textContent = 'Mulai Pindai Izin';
            buttonIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h4.5a1.875 1.875 0 0 1 1.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 0 1-.937-.938v-3.375c0-.517.42-.938.938-.938z" />`;
            buttonIcon.classList.remove('animate-spin');
        }
        
        function resetUI() {
            controlsDiv.classList.remove('hidden');
            readerDiv.classList.add('hidden');
            switchContainer.classList.add('hidden');
            resetButtonState();
        }

        function playSound(type) {
            let audioFile;
            if (type === 'success') { audioFile = '{{ asset('sounds/success.mp3') }}'; } 
            else if (type === 'warning') { audioFile = '{{ asset('sounds/warning.mp3') }}'; } 
            else { audioFile = '{{ asset('sounds/error.mp3') }}'; }
            try { new Audio(audioFile).play(); } catch (e) { console.error("Gagal memainkan suara:", e); }
        }
    });
</script>
@endpush
