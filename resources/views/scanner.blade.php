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

        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-lg p-6 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 animate-[fade-in-up_0.8s_ease-out_forwards]" style="animation-delay: 0.4s;">
            
            <div id="scanner-choice">
                <h1 class="text-3xl font-bold text-slate-800 dark:text-white mb-2">Pilih Tipe Pemindai</h1>
                <p class="text-slate-600 dark:text-slate-400 mb-8">Pilih metode yang akan Anda gunakan untuk mencatat kehadiran.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button id="use-camera-button" class="w-full inline-flex flex-col items-center justify-center p-6 border border-transparent text-base font-medium rounded-md text-white bg-sky-600 hover:bg-sky-700 transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 mb-2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.776 48.776 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" /></svg>
                        Pindai dengan Kamera
                    </button>
                    <button id="use-manual-button" class="w-full inline-flex flex-col items-center justify-center p-6 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 mb-2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875-1.875h-4.5A1.875 1.875 0 013.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875-1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875-1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15.75h4.5a1.875 1.875 0 011.875 1.875v3.375c0 .517-.42.938-.938.938h-2.925a.938.938 0 01-.937-.938v-3.375c0-.517.42-.938.938-.938z" /></svg>
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
                <form id="manual-form" onsubmit="return false;">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-slate-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875-1.875h-4.5A1.875 1.875 0 013.75 9.375v-4.5zM3.75 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875-1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5zM13.5 4.875c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875-1.875h-4.5a1.875 1.875 0 01-1.875-1.875v-4.5z" />
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

<!-- Modal Pop-up -->
<div id="attendance-modal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0 hidden z-50">
    <div id="modal-content" class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm p-8 text-center transform scale-95 transition-all duration-300">
        <div id="modal-icon-container" class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5">
            <svg id="modal-icon-svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"></svg>
        </div>
        <h2 id="modal-title" class="text-2xl font-bold text-slate-800 dark:text-white mb-2"></h2>
        <div class="mt-4 mb-4">
             <span id="modal-student-image-container" class="inline-block h-24 w-24 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-700">
                <img id="modal-student-image" src="" alt="Foto Siswa" class="h-full w-full object-cover hidden">
                <svg id="modal-student-placeholder" class="h-full w-full text-slate-300 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
              </span>
        </div>
        <p id="modal-student-name" class="text-xl font-semibold text-sky-700 dark:text-sky-400"></p>
        <p id="modal-student-nis" class="text-md text-slate-500 dark:text-slate-400 mb-6"></p>
    </div>
</div>
@endsection

@push('scripts')
{{-- Library untuk memindai QR Code dari kamera --}}
<script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // === VARIABEL GLOBAL & ELEMENT DOM ===
        let userCoordinates = null;
        let lastScanTime = 0;
        const scanCooldown = 3000; // Jeda 3 detik antar scan
        
        const scannerChoiceDiv = document.getElementById('scanner-choice');
        const cameraScannerDiv = document.getElementById('camera-scanner');
        const manualScannerDiv = document.getElementById('manual-scanner');
        const useCameraButton = document.getElementById('use-camera-button');
        const useManualButton = document.getElementById('use-manual-button');
        const backButton = document.getElementById('back-to-choice');
        const manualInput = document.getElementById('manual_input_id');

        const readerDiv = document.getElementById('reader');
        const readerError = document.getElementById('reader-error');
        const switchContainer = document.getElementById('camera-switch-container');
        const switchButton = document.getElementById('camera-switch-button');
        
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
            document.getElementById('current-time').textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit'});
            document.getElementById('current-date').textContent = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }

        // Fungsi untuk memulai alur pemindaian (kamera atau manual)
        function showScannerView(type) {
            scannerChoiceDiv.classList.add('hidden');
            backButton.classList.remove('hidden');
            if (type === 'camera') {
                cameraScannerDiv.classList.remove('hidden');
                startScanFlow();
            } else {
                manualScannerDiv.classList.remove('hidden');
                manualInput.focus();
            }
        }

        // Fungsi untuk kembali ke menu pilihan
        function resetToChoiceView() {
            // PERBAIKAN: Pastikan kamera benar-benar berhenti saat kembali ke menu
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    console.log("QR Code scanning stopped.");
                }).catch(err => {
                    console.error("Failed to stop QR Code scanning.", err);
                });
            }
            html5QrCode = null; // Reset objek scanner
            cameraScannerDiv.classList.add('hidden');
            manualScannerDiv.classList.add('hidden');
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
                (errorMessage) => {} // Error callback, bisa diabaikan
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

        // Mengirim data absensi ke server
        function processAttendance(studentId) {
            // Tampilkan loading spinner atau sejenisnya jika perlu
            fetch("{{ route('attendance.store') }}", {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    student_unique_id: studentId,
                    latitude: userCoordinates.latitude,
                    longitude: userCoordinates.longitude
                })
            }).then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(({ status, body }) => {
                 // Normalisasi status error untuk kemudahan
                 if(status === 403) body.status = 'location_error';
                 if(status === 409) body.status = body.status || 'completed';
                 if(status === 404) body.status = 'not_found';
                 showModal(body);
            }).catch(error => {
                showModal({ status: 'error', message: 'Tidak dapat terhubung ke server.' });
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
                // Cek apakah kita sedang dalam mode kamera atau manual
                if (cameraScannerDiv.classList.contains('hidden')) {
                    // Jika mode manual, fokus kembali ke input
                    manualInput.focus();
                } else {
                    // Jika mode kamera, LANJUTKAN (RESUME) pemindaian yang dijeda
                    // Ini jauh lebih cepat daripada memulai ulang.
                    if (html5QrCode) {
                        html5QrCode.resume();
                    }
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

    });
</script>
@endpush
