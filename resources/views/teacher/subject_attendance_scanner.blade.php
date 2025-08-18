@extends('layouts.public')

@section('title', 'Pemindai Kehadiran Mapel')

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
                            <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $schedule->teachingAssignment->subject->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Kelas</p>
                            <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $schedule->teachingAssignment->schoolClass->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Waktu</p>
                            <p class="font-semibold text-gray-800 dark:text-gray-200">{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Pemindai Kamera -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm rounded-lg p-6">
                    <div id="reader" class="w-full max-w-sm mx-auto aspect-square bg-slate-100 dark:bg-slate-700 rounded-lg overflow-hidden"></div>
                    <div id="camera-switch-container" class="mt-4 text-center hidden">
                        <button id="camera-switch-button" class="text-sm text-sky-600 dark:text-sky-400 hover:underline">Ganti Kamera</button>
                    </div>
                    <div id="reader-error" class="text-red-500 text-sm mt-4 text-center hidden"></div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <!-- Daftar Hadir -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Siswa Hadir (<span id="attended-count">{{ $attendedStudents->count() }}</span>)</h3>
                    </div>
                    <div class="border-t border-gray-200 dark:border-slate-700">
                        <ul id="attended-list" class="divide-y divide-gray-200 dark:divide-slate-700 max-h-[60vh] overflow-y-auto">
                            @forelse($attendedStudents as $attendance)
                                <li class="p-4 flex items-center justify-between">
                                    <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ $attendance->student->name }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $attendance->created_at->format('H:i:s') }}</span>
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
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Siswa Izin/Sakit (Harian)</h3>
                    </div>
                    <div class="border-t border-gray-200 dark:border-slate-700">
                        <ul id="leave-list" class="divide-y divide-gray-200 dark:divide-slate-700 max-h-[30vh] overflow-y-auto">
                            @forelse($studentsOnLeave as $dailyAttendance)
                                <li class="p-4 flex items-center justify-between">
                                    <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ $dailyAttendance->student->name }}</span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($dailyAttendance->status == 'sakit') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300 @endif
                                        @if($dailyAttendance->status == 'izin') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 @endif
                                    ">{{ ucfirst($dailyAttendance->status) }}</span>
                                </li>
                            @empty
                                <li class="p-4 text-center text-sm text-gray-500 italic">
                                    Tidak ada siswa yang izin/sakit hari ini.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <!-- Panel Siswa Tanpa Kabar -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Siswa Tanpa Kabar</h3>
                    </div>
                    <div class="border-t border-gray-200 dark:border-slate-700">
                        <ul id="no-notice-list" class="divide-y divide-gray-200 dark:divide-slate-700 max-h-[30vh] overflow-y-auto">
                            @forelse($studentsWithoutNotice as $student)
                                <li class="p-4 flex items-center justify-between" id="student-row-{{$student->id}}">
                                    <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ $student->name }}</span>
                                    <div class="flex items-center gap-1">
                                        <button data-student-id="{{ $student->id }}" data-status="sakit" class="manual-mark-btn px-2 py-1 text-xs font-medium text-amber-800 bg-amber-100 hover:bg-amber-200 rounded-full">S</button>
                                        <button data-student-id="{{ $student->id }}" data-status="izin" class="manual-mark-btn px-2 py-1 text-xs font-medium text-purple-800 bg-purple-100 hover:bg-purple-200 rounded-full">I</button>
                                        <button data-student-id="{{ $student->id }}" data-status="alpa" class="manual-mark-btn px-2 py-1 text-xs font-medium text-red-800 bg-red-100 hover:bg-red-200 rounded-full">A</button>
                                        <button data-student-id="{{ $student->id }}" data-status="bolos" class="manual-mark-btn px-2 py-1 text-xs font-medium text-gray-800 bg-gray-200 hover:bg-gray-300 rounded-full">B</button>
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
<div id="attendance-modal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0 hidden z-50">
    <div id="modal-content" class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm p-8 text-center transform scale-95 transition-all duration-300">
        <div id="modal-icon-container" class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5">
            <svg id="modal-icon-svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"></svg>
        </div>
        <h2 id="modal-title" class="text-2xl font-bold text-slate-800 dark:text-white mb-2"></h2>
        <p id="modal-message" class="text-md text-slate-500 dark:text-slate-400 mb-6"></p>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let lastScanTime = 0;
    const scanCooldown = 3000;
    const scheduleId = {{ $schedule->id }};

    const readerError = document.getElementById('reader-error');
    const switchContainer = document.getElementById('camera-switch-container');
    const switchButton = document.getElementById('camera-switch-button');
    const attendedList = document.getElementById('attended-list');
    const attendedCount = document.getElementById('attended-count');
    const noStudentsYet = document.getElementById('no-students-yet');
    const noNoticeList = document.getElementById('no-notice-list');

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
    
    function addStudentToList(name, time) {
        if(noStudentsYet) {
            noStudentsYet.classList.add('hidden');
        }
        const listItem = document.createElement('li');
        listItem.className = 'p-4 flex items-center justify-between animate-[fade-in_0.5s]';
        listItem.innerHTML = `<span class="font-medium text-sm text-gray-800 dark:text-gray-200">${name}</span>
                              <span class="text-xs text-gray-500 dark:text-gray-400">${time}</span>`;
        attendedList.prepend(listItem);
        attendedCount.textContent = parseInt(attendedCount.textContent) + 1;
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
            if(data.student) { // Hanya jika dari scan
                addStudentToList(data.student.name, data.student.time);
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
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.resume();
            }
        }, 300);
    }

    function startScannerWithCamera(cameraId) {
        html5QrCode.start(
            cameraId, 
            { fps: 10, qrbox: { width: 250, height: 250 } },
            onScanSuccess,
            (errorMessage) => {}
        ).catch((err) => {
            readerError.textContent = "Gagal memulai kamera. Pastikan Anda memberikan izin akses kamera.";
            readerError.classList.remove('hidden');
        });
    }

    // --- FUNGSI BARU UNTUK MENANGANI KLIK TOMBOL MANUAL ---
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
            if (body.success) {
                // Hapus baris siswa dari daftar "Tanpa Kabar"
                const studentRow = document.getElementById(`student-row-${studentId}`);
                if (studentRow) {
                    studentRow.style.transition = 'opacity 0.5s';
                    studentRow.style.opacity = '0';
                    setTimeout(() => {
                        studentRow.remove();
                        // Cek jika daftar menjadi kosong
                        if (noNoticeList.children.length <= 1) { // 1 karena ada item "empty"
                            document.getElementById('no-missing-students').classList.remove('hidden');
                        }
                    }, 500);
                }
            }
        }).catch(error => {
            showModal(false, { message: 'Tidak dapat terhubung ke server.' });
        });
    }

    // Tambahkan event listener ke semua tombol manual
    document.querySelectorAll('.manual-mark-btn').forEach(button => {
        button.addEventListener('click', handleManualMark);
    });

    Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length) {
            cameras = devices;
            let backCameraIndex = cameras.findIndex(camera => camera.label.toLowerCase().includes('back'));
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
