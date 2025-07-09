@extends('layouts.public')

@section('title', 'Pemindai QR Absensi')

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

        <h1 class="text-3xl font-bold text-slate-800 dark:text-white mb-2 animate-[fade-in-up_0.8s_ease-out_forwards]" style="animation-delay: 0.2s;">Pindai QR Code Kehadiran</h1>
        <p class="text-slate-600 dark:text-slate-400 mb-8 animate-[fade-in-up_0.8s_ease-out_forwards]" style="animation-delay: 0.3s;">Tekan tombol di bawah untuk memulai pemindaian.</p>

        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-lg p-6 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 animate-[fade-in-up_0.8s_ease-out_forwards]" style="animation-delay: 0.4s;">
            <div id="reader" class="w-full max-w-sm mx-auto aspect-square bg-slate-100 dark:bg-slate-700 rounded-lg overflow-hidden hidden"></div>
            
            <div id="scanner-controls">
                <button id="start-scan-button" class="w-full inline-flex items-center justify-center px-6 py-4 border border-transparent text-base font-medium rounded-md text-white bg-sky-600 hover:bg-sky-700 disabled:bg-slate-400 disabled:cursor-not-allowed transition-all duration-300">
                    <svg id="button-icon" class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h4.5a1.875 1.875 0 0 1 1.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 0 1-.937-.938v-3.375c0-.517.42-.938.938-.938z" /></svg>
                    <span id="button-text">Mulai Pindai</span>
                </button>
            </div>

            <div id="reader-error" class="text-red-500 text-sm mt-2 hidden"></div>
        </div>
    </div>
</div>

<!-- Modal Pop-up -->
<div id="attendance-modal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0 hidden z-50">
    <div id="modal-content" class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm p-8 text-center transform scale-95 transition-all duration-300">
        <div id="modal-icon-container" class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5">
            <svg id="modal-icon-svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"></svg>
        </div>
        <h2 id="modal-title" class="text-2xl font-bold text-slate-800 dark:text-white mb-2"></h2>
        <div class="mt-4 mb-4">
             <span class="inline-block h-24 w-24 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-700">
                <svg class="h-full w-full text-slate-300 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </span>
        </div>
        <p id="modal-student-name" class="text-xl font-semibold text-sky-700 dark:text-sky-400"></p>
        <p id="modal-student-nis" class="text-md text-slate-500 dark:text-slate-400 mb-6"></p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let userCoordinates = null;
        let lastScanTime = 0;
        const scanCooldown = 3000; // Cooldown dipersingkat menjadi 3 detik
        const readerDiv = document.getElementById('reader');
        const readerError = document.getElementById('reader-error');
        const controlsDiv = document.getElementById('scanner-controls');
        const startButton = document.getElementById('start-scan-button');
        const buttonText = document.getElementById('button-text');
        const buttonIcon = document.getElementById('button-icon');
        
        let html5QrCode = null;

        const modal = {
            element: document.getElementById('attendance-modal'),
            content: document.getElementById('modal-content'),
            iconContainer: document.getElementById('modal-icon-container'),
            iconSvg: document.getElementById('modal-icon-svg'),
            title: document.getElementById('modal-title'),
            studentName: document.getElementById('modal-student-name'),
            studentNis: document.getElementById('modal-student-nis'),
        };

        function updateClock() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit'});
            document.getElementById('current-date').textContent = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        updateClock(); 
        setInterval(updateClock, 1000);

        function startScanFlow() {
            startButton.disabled = true;
            buttonText.textContent = 'Meminta Izin Lokasi...';
            buttonIcon.innerHTML = `<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>`;
            buttonIcon.classList.add('animate-spin');
            readerError.classList.add('hidden');

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userCoordinates = {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude
                        };
                        initializeScanner();
                    },
                    (error) => {
                        readerError.textContent = 'Gagal mendapatkan lokasi GPS. Izinkan akses lokasi dan coba lagi.';
                        readerError.classList.remove('hidden');
                        resetButton();
                    }
                );
            } else {
                readerError.textContent = 'Browser Anda tidak mendukung Geolocation.';
                readerError.classList.remove('hidden');
                resetButton();
            }
        }

        startButton.addEventListener('click', startScanFlow);

        function initializeScanner() {
            controlsDiv.classList.add('hidden');
            readerDiv.classList.remove('hidden');
            
            html5QrCode = new Html5Qrcode("reader");
            
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                onScanSuccess,
                (errorMessage) => { /* Abaikan error */ }
            ).catch((err) => {
                readerError.textContent = "Gagal memulai kamera. Pastikan izin telah diberikan.";
                readerError.classList.remove('hidden');
                resetUI();
            });
        }

        function onScanSuccess(decodedText, decodedResult) {
            if (Date.now() - lastScanTime < scanCooldown) return;
            lastScanTime = Date.now();
            
            // PERBAIKAN: Hapus panggilan stopScanning() agar kamera tetap aktif
            // stopScanning(); 

            fetch("{{ route('attendance.store') }}", {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    student_unique_id: decodedText,
                    latitude: userCoordinates.latitude,
                    longitude: userCoordinates.longitude
                })
            }).then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(({ status, body }) => {
                 if(status === 403) body.status = 'location_error';
                 showModal(body);
            }).catch(error => {
                showModal({ status: 'error', message: 'Tidak dapat terhubung ke server.' });
            });
        }

        function showModal(data) {
            modal.iconContainer.className = 'mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5';
            modal.iconSvg.className = 'h-12 w-12';
            modal.iconSvg.innerHTML = '';

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
                    modal.iconContainer.classList.add('bg-yellow-100', 'dark:bg-yellow-900');
                    modal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />`;
                    modal.iconSvg.classList.add('text-yellow-600', 'dark:text-yellow-400');
                    modal.title.textContent = 'Absensi Selesai';
                    playSound('warning');
                    break;
                case 'on_leave':
                    modal.iconContainer.classList.add('bg-orange-100', 'dark:bg-orange-900');
                    modal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />`;
                    modal.iconSvg.classList.add('text-orange-600', 'dark:text-orange-400');
                    modal.title.textContent = 'Absensi Tidak Diizinkan';
                    playSound('error');
                    break;
                case 'location_error':
                    modal.iconContainer.classList.add('bg-red-100', 'dark:bg-red-900');
                    modal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />`;
                    modal.iconSvg.classList.add('text-red-600', 'dark:text-red-400');
                    modal.title.textContent = 'Lokasi Tidak Sesuai!';
                    playSound('error');
                    break;
                default:
                    modal.iconContainer.classList.add('bg-red-100', 'dark:bg-red-900');
                    modal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />`;
                    modal.iconSvg.classList.add('text-red-600', 'dark:text-red-400');
                    modal.title.textContent = 'Gagal!';
                    playSound('error');
                    break;
            }
            
            modal.studentName.textContent = data.student_name || 'Error';
            modal.studentNis.textContent = data.message || (data.student_nis ? 'NIS: ' + data.student_nis : '');
            
            modal.element.classList.remove('hidden');
            setTimeout(() => {
                modal.element.classList.remove('opacity-0');
                modal.content.classList.remove('scale-95');
            }, 10);

            setTimeout(hideModal, 4000);
        }

        function hideModal() {
            modal.element.classList.add('opacity-0');
            modal.content.classList.add('scale-95');
            setTimeout(() => modal.element.classList.add('hidden'), 300);
        }

        function playSound(type) {
            let audioFile;
            if (type === 'success') { audioFile = '{{ asset('sounds/success.mp3') }}'; } 
            else if (type === 'warning') { audioFile = '{{ asset('sounds/warning.mp3') }}'; } 
            else { audioFile = '{{ asset('sounds/error.mp3') }}'; }
            try { new Audio(audioFile).play(); } catch (e) { console.error("Gagal memainkan suara:", e); }
        }
        
        function stopScanning() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().catch(err => console.error("Gagal menghentikan pemindaian.", err));
            }
            resetUI();
        }

        function resetButton() {
            startButton.disabled = false;
            buttonText.textContent = 'Mulai Pindai QR Code';
            buttonIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h4.5a1.875 1.875 0 0 1 1.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 0 1-.937-.938v-3.375c0-.517.42-.938.938-.938z" />`;
            buttonIcon.classList.remove('animate-spin');
        }
        
        function resetUI() {
            controlsDiv.classList.remove('hidden');
            readerDiv.classList.add('hidden');
            resetButton();
        }

        // Mulai alur dengan menampilkan tombol
        resetButton();
        startButton.disabled = false;
        buttonText.textContent = 'Mulai Pindai QR Code';
        buttonIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h4.5a1.875 1.875 0 0 1 1.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 0 1-.937-.938v-3.375c0-.517.42-.938.938-.938z" />`;
        buttonIcon.classList.remove('animate-spin');
    });
</script>
@endpush
