@extends('layouts.public')

@section('title', 'Pemindai QR Absensi')

@section('content')
<div class="max-w-xl mx-auto text-center">
    <!-- **JAM DIGITAL DAN TANGGAL DITAMBAHKAN DI SINI** -->
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
    
    <div id="result-container" class="mt-6 p-4 rounded-lg text-center transition-all duration-300">
        <p id="result-message" class="font-medium"></p>
        <p id="result-details" class="text-sm"></p>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // **SCRIPT UNTUK JAM DIGITAL DAN TANGGAL**
        const timeElement = document.getElementById('current-time');
        const dateElement = document.getElementById('current-date');

        function updateClock() {
            const now = new Date();
            
            // Format Waktu (HH:MM:SS)
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            timeElement.textContent = `${hours}:${minutes}:${seconds}`;

            // Format Tanggal (misal: Rabu, 18 Juni 2025)
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            dateElement.textContent = now.toLocaleDateString('id-ID', dateOptions);
        }

        updateClock(); // Panggil sekali saat load
        setInterval(updateClock, 1000); // Update setiap detik

        // **SCRIPT UNTUK SCANNER**
        const resultContainer = document.getElementById('result-container');
        const resultMessage = document.getElementById('result-message');
        const resultDetails = document.getElementById('result-details');
        const readerError = document.getElementById('reader-error');
        let lastScanTime = 0;
        const scanCooldown = 5000; // 5 detik cooldown

        function onScanSuccess(decodedText, decodedResult) {
            const currentTime = new Date().getTime();
            if (currentTime - lastScanTime < scanCooldown) {
                return;
            }
            lastScanTime = currentTime;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch("{{ route('attendance.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    student_unique_id: decodedText
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                displayResult(data);
            })
            .catch(error => {
                console.error('Error:', error);
                displayResult({
                    status: 'error',
                    message: error.message || 'QR Code tidak valid atau terjadi kesalahan.'
                });
            });
        }

        function onScanFailure(error) {
            // Dibiarkan kosong
        }
        
        function displayResult(data) {
            resultContainer.classList.remove('bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800', 'bg-yellow-100', 'text-yellow-800');

            switch (data.status) {
                case 'success':
                    resultContainer.classList.add('bg-green-100', 'text-green-800');
                    resultMessage.textContent = `Selamat Datang, ${data.student_name}!`;
                    resultDetails.textContent = `Kehadiran dicatat pukul ${data.time}.`;
                    playSound('success');
                    break;
                case 'warning':
                    resultContainer.classList.add('bg-yellow-100', 'text-yellow-800');
                    resultMessage.textContent = `Halo, ${data.student_name}!`;
                    resultDetails.textContent = data.message;
                    playSound('warning');
                    break;
                case 'error':
                default:
                    resultContainer.classList.add('bg-red-100', 'text-red-800');
                    resultMessage.textContent = 'Gagal!';
                    resultDetails.textContent = data.message || 'QR Code tidak valid atau terjadi kesalahan.';
                    playSound('error');
                    break;
            }

            setTimeout(() => {
                resultContainer.classList.remove('bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800', 'bg-yellow-100', 'text-yellow-800');
                resultMessage.textContent = '';
                resultDetails.textContent = '';
            }, scanCooldown);
        }

        function playSound(type) {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (!audioCtx) return;
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            gainNode.gain.setValueAtTime(0, audioCtx.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.3, audioCtx.currentTime + 0.01);

            if (type === 'success') {
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(600, audioCtx.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(800, audioCtx.currentTime + 0.1);
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

        if (typeof Html5QrcodeScanner !== 'undefined') {
            const html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", 
                { 
                    fps: 10, 
                    qrbox: (viewfinderWidth, viewfinderHeight) => {
                        const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                        const qrboxSize = Math.floor(minEdge * 0.7);
                        return { width: qrboxSize, height: qrboxSize };
                    },
                    rememberLastUsedCamera: true,
                    supportedScanTypes: [0]
                },
                false
            );
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        } else {
            console.error('Html5QrcodeScanner library not loaded.');
            readerError.textContent = 'Gagal memuat library pemindai. Periksa koneksi internet Anda.';
            readerError.classList.remove('hidden');
        }
    });
</script>
@endpush
