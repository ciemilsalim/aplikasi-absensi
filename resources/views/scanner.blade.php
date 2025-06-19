@extends('layouts.public')

@section('title', 'Pemindai QR Absensi')

@section('content')
<div class="max-w-xl mx-auto text-center">
    <!-- Jam Digital dan Tanggal -->
    <div class="mb-6">
        <p id="current-date" class="text-lg text-slate-600"></p>
        <p id="current-time" class="text-5xl font-bold text-sky-600 tracking-tight"></p>
    </div>

    <h1 class="text-3xl font-bold text-slate-800 mb-2">Pindai QR Code Kehadiran</h1>
    <p class="text-slate-600 mb-8">Arahkan QR Code pada kartu siswa ke kamera.</p>

    <div class="bg-white p-6 rounded-2xl shadow-md border border-slate-200">
        <div id="reader" class="w-full max-w-sm mx-auto aspect-square bg-slate-100 rounded-lg overflow-hidden"></div>
        <div id="reader-error" class="text-red-500 text-sm mt-2 hidden">Gagal mengakses kamera. Mohon izinkan akses kamera di browser Anda.</div>
    </div>
    
    <!-- Notifikasi untuk Peringatan/Error -->
    <div id="notification-container" class="mt-6 p-4 rounded-lg text-center transition-all duration-300 opacity-0">
        <p id="notification-message" class="font-medium"></p>
        <p id="notification-details" class="text-sm"></p>
    </div>
</div>

<!-- Modal Pop-up Keren untuk Absensi Sukses -->
<div id="success-modal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0 hidden z-50">
    <div id="modal-content" class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-8 text-center transform scale-95 transition-all duration-300">
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-5">
            <svg class="h-12 w-12 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-800 mb-2">Selamat Datang!</h2>
        {{-- Avatar Pengganti Foto Siswa --}}
        <div class="mt-4 mb-4">
             <span class="inline-block h-24 w-24 rounded-full overflow-hidden bg-slate-100">
                <svg class="h-full w-full text-slate-300" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </span>
        </div>
        <p id="modal-student-name" class="text-xl font-semibold text-sky-700"></p>
        <p id="modal-student-nis" class="text-md text-slate-500 mb-6"></p>
        <button id="close-modal-button" class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-3 px-4 rounded-lg transition-colors">
            Tutup
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ... (Kode untuk jam digital tetap sama) ...
        const timeElement = document.getElementById('current-time');
        const dateElement = document.getElementById('current-date');
        function updateClock() {
            const now = new Date();
            timeElement.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit'});
            dateElement.textContent = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        updateClock(); setInterval(updateClock, 1000);

        // Elemen Notifikasi
        const notificationContainer = document.getElementById('notification-container');
        const notificationMessage = document.getElementById('notification-message');
        const notificationDetails = document.getElementById('notification-details');

        // Elemen Modal
        const successModal = document.getElementById('success-modal');
        const modalContent = document.getElementById('modal-content');
        const modalStudentName = document.getElementById('modal-student-name');
        const modalStudentNis = document.getElementById('modal-student-nis');
        const closeModalButton = document.getElementById('close-modal-button');

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
            }).then(response => response.json().then(data => ({ ok: response.ok, status: response.status, data })))
            .then(({ ok, status, data }) => {
                if (ok) {
                    showSuccessModal(data);
                } else {
                    showNotification(data);
                }
            }).catch(error => {
                console.error('Error:', error);
                showNotification({ status: 'error', message: 'Tidak dapat terhubung ke server.' });
            });
        }
        
        function showSuccessModal(data) {
            modalStudentName.textContent = data.student_name;
            modalStudentNis.textContent = 'NIS: ' + data.student_nis;
            
            successModal.classList.remove('hidden');
            setTimeout(() => {
                successModal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
            }, 10); // Sedikit delay untuk transisi

            playSound('success');

            setTimeout(hideSuccessModal, 4000); // Otomatis tutup setelah 4 detik
        }

        function hideSuccessModal() {
            successModal.classList.add('opacity-0');
            modalContent.classList.add('scale-95');
            setTimeout(() => {
                successModal.classList.add('hidden');
            }, 300); // Waktu sesuai durasi transisi
        }

        function showNotification(data) {
            notificationContainer.className = 'mt-6 p-4 rounded-lg text-center transition-all duration-300 opacity-100';
            let message = '';
            let details = '';

            switch (data.status) {
                case 'warning':
                    notificationContainer.classList.add('bg-yellow-100', 'text-yellow-800');
                    message = `Halo, ${data.student_name}!`;
                    details = data.message;
                    playSound('warning');
                    break;
                default:
                    notificationContainer.classList.add('bg-red-100', 'text-red-800');
                    message = 'Gagal!';
                    details = data.message;
                    playSound('error');
                    break;
            }
            notificationMessage.textContent = message;
            notificationDetails.textContent = details;

            setTimeout(() => {
                notificationContainer.classList.add('opacity-0');
            }, 4000);
        }

        function playSound(type) {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (!audioCtx) return;
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            gainNode.gain.setValueAtTime(0, audioCtx.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.5, audioCtx.currentTime + 0.01);

            if (type === 'success') { // Bunyi 'beep' yang lebih modern
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(1200, audioCtx.currentTime + 0.05);
            } else { /* ... (suara lain tetap sama) ... */ }
            
            oscillator.start();
            gainNode.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.2);
            oscillator.stop(audioCtx.currentTime + 0.2);
        }
        
        closeModalButton.addEventListener('click', hideSuccessModal);

        // Inisialisasi Scanner
        const html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: (w, h) => ({ width: Math.floor(Math.min(w, h) * 0.8), height: Math.floor(Math.min(w, h) * 0.8) })}, false);
        html5QrcodeScanner.render(onScanSuccess);
    });
</script>
@endpush
