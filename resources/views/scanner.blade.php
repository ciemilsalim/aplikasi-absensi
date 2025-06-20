@extends('layouts.public')

@section('title', 'Pemindai QR Absensi')

@section('content')
<div class="max-w-xl mx-auto text-center">
    <!-- Jam Digital dan Tanggal -->
    <div class="mb-6">
        <p id="current-date" class="text-lg text-slate-600 dark:text-slate-500"></p>
        <p id="current-time" class="text-5xl font-bold text-sky-600 tracking-tight"></p>
    </div>

    <h1 class="text-3xl font-bold text-slate-800 dark:text-slate-400 mb-2">Pindai QR Code Kehadiran</h1>
    <p class="text-slate-600 mb-8">Arahkan QR Code pada kartu siswa ke kamera.</p>

    <div class="bg-white p-6 rounded-2xl shadow-md border border-slate-200  dark:bg-slate-900 dark:border-slate-700">
        <div id="reader" class="w-full max-w-sm mx-auto aspect-square bg-slate-100 rounded-lg overflow-hidden"></div>
        <div id="reader-error" class="text-red-500 text-sm mt-2 hidden">Gagal mengakses kamera. Mohon izinkan akses kamera di browser Anda.</div>
    </div>
</div>

<!-- Modal Pop-up Keren untuk Absensi -->
<div id="attendance-modal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0 hidden z-50">
    <div id="modal-content" class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-8 text-center transform scale-95 transition-all duration-300">
        <div id="modal-icon-container" class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5">
            <svg id="modal-icon-svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"></svg>
        </div>
        <h2 id="modal-title" class="text-2xl font-bold text-slate-800 mb-2"></h2>
        <div class="mt-4 mb-4">
             <span class="inline-block h-24 w-24 rounded-full overflow-hidden bg-slate-100">
                <svg class="h-full w-full text-slate-300" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </span>
        </div>
        <p id="modal-student-name" class="text-xl font-semibold text-sky-700"></p>
        <p id="modal-student-nis" class="text-md text-slate-500 mb-6"></p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const timeElement = document.getElementById('current-time');
        const dateElement = document.getElementById('current-date');
        const readerError = document.getElementById('reader-error');

        function updateClock() {
            const now = new Date();
            timeElement.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit'});
            dateElement.textContent = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        updateClock(); 
        setInterval(updateClock, 1000);

        const attendanceModal = document.getElementById('attendance-modal');
        const modalContent = document.getElementById('modal-content');
        const modalIconContainer = document.getElementById('modal-icon-container');
        const modalIconSvg = document.getElementById('modal-icon-svg');
        const modalTitle = document.getElementById('modal-title');
        const modalStudentName = document.getElementById('modal-student-name');
        const modalStudentNis = document.getElementById('modal-student-nis');

        let lastScanTime = 0;
        const scanCooldown = 5000;

        function onScanSuccess(decodedText, decodedResult) {
            const currentTime = new Date().getTime();
            if (currentTime - lastScanTime < scanCooldown) return;
            lastScanTime = currentTime;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch("{{ route('attendance.store') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ student_unique_id: decodedText })
            }).then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                showModal(data);
            }).catch(error => {
                console.error('Error:', error);
                showModal({ status: 'error', message: 'Tidak dapat terhubung ke server.' });
            });
        }
        
        function showModal(data) {
            modalIconContainer.className = 'mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5';
            modalIconSvg.className = 'h-12 w-12';
            modalIconSvg.innerHTML = '';

            switch (data.status) {
                case 'clock_in':
                    modalIconContainer.classList.add('bg-green-100');
                    modalIconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />`;
                    modalIconSvg.classList.add('text-green-600');
                    modalTitle.textContent = 'Selamat Datang!';
                    playSound('success');
                    break;
                case 'clock_out':
                    modalIconContainer.classList.add('bg-blue-100');
                    modalIconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />`;
                    modalIconSvg.classList.add('text-blue-600');
                    modalTitle.textContent = 'Sampai Jumpa!';
                    playSound('success');
                    break;
                case 'completed':
                    modalIconContainer.classList.add('bg-yellow-100');
                    modalIconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />`;
                    modalIconSvg.classList.add('text-yellow-600');
                    modalTitle.textContent = 'Absensi Selesai';
                    playSound('warning');
                    break;
                default:
                    modalIconContainer.classList.add('bg-red-100');
                    modalIconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />`;
                    modalIconSvg.classList.add('text-red-600');
                    modalTitle.textContent = 'Gagal!';
                    playSound('error');
                    break;
            }
            
            modalStudentName.textContent = data.student_name || data.message;
            modalStudentNis.textContent = data.student_nis ? 'NIS: ' + data.student_nis : '';
            
            attendanceModal.classList.remove('hidden');
            setTimeout(() => {
                attendanceModal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
            }, 10);

            setTimeout(hideModal, 4000);
        }

        function hideModal() {
            attendanceModal.classList.add('opacity-0');
            modalContent.classList.add('scale-95');
            setTimeout(() => attendanceModal.classList.add('hidden'), 300);
        }
        
        function playSound(type) {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (!audioCtx) return;
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            oscillator.connect(gainNode); gainNode.connect(audioCtx.destination);
            gainNode.gain.setValueAtTime(0, audioCtx.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.5, audioCtx.currentTime + 0.01);
            if (type === 'success') {
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(1200, audioCtx.currentTime + 0.05);
            } else if (type === 'warning') {
                oscillator.type = 'triangle';
                oscillator.frequency.setValueAtTime(440, audioCtx.currentTime);
            } else {
                oscillator.type = 'square';
                oscillator.frequency.setValueAtTime(300, audioCtx.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(200, audioCtx.currentTime + 0.1);
            }
            oscillator.start();
            gainNode.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.2);
            oscillator.stop(audioCtx.currentTime + 0.2);
        }
        
        function initializeScanner() {
            if (typeof Html5QrcodeScanner !== 'undefined') {
                const html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: (w, h) => ({ width: Math.floor(Math.min(w, h) * 0.8), height: Math.floor(Math.min(w, h) * 0.8) })}, false);
                html5QrcodeScanner.render(onScanSuccess, (error) => {});
            } else {
                setTimeout(initializeScanner, 100);
            }
        }
        initializeScanner();
    });
</script>
@endpush
