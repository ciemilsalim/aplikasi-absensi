<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => $schedule->isCocurricular() ? 'fasilitator_kokurikuler' : 'guru_mapel'])],
                    ['title' => $schedule->isCocurricular() ? 'Sesi Presensi Kokurikuler' : 'Sesi Presensi Mengajar', 'url' => '#']
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        {{ $schedule->isCocurricular() ? 'Presensi Kokurikuler' : 'Sesi Presensi Mengajar' }}
                    </h1>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold {{ $schedule->isCocurricular() ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800' }} shadow-2xs">
                        {{ $schedule->getActivityName() }}
                    </span>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shadow-2xs">
                        Kelas {{ $schedule->getTargetClass()?->name ?? '-' }}
                    </span>
                </div>
            </div>

                <a href="{{ route('teacher.anecdotes.index', ['school_class_id' => $schedule->getTargetClass()?->id, 'subject_id' => $schedule->teachingAssignment?->subject_id]) }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 text-amber-700 dark:text-amber-300 font-bold text-xs border border-amber-200 dark:border-amber-800 transition-all active:scale-95 shadow-2xs">
                    <span class="material-icons text-sm">rate_review</span>
                    <span>Rekap Anekdot</span>
                </a>

                <a href="{{ route('teacher.journals.create', ['schedule_id' => $schedule->id, 'date' => $selectedDate->format('Y-m-d')]) }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-sm shadow-sky-600/20 transition-all active:scale-95">
                    <span class="material-icons text-sm">edit_note</span>
                    <span>Isi Jurnal Sesi Ini</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase bg-emerald-400 text-slate-950 shadow-2xs">Baru</span>
                </a>

                <a href="{{ route('teacher.dashboard', ['view' => $schedule->isCocurricular() ? 'fasilitator_kokurikuler' : 'guru_mapel']) }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors shadow-2xs">
                    <span class="material-icons text-sm">arrow_back</span>
                    <span class="hidden sm:inline">Dasbor</span>
                </a>

                <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 p-1 rounded-2xl border border-slate-200/80 dark:border-slate-700">
                    <span class="material-icons text-slate-400 text-xs ml-1.5">calendar_today</span>
                    <input type="date" id="attendance-date" name="date" value="{{ $selectedDate->format('Y-m-d') }}" 
                           class="border-0 bg-transparent text-slate-800 dark:text-white focus:ring-0 text-xs font-bold py-1 px-1.5 cursor-pointer">
                </div>
            </div>
        </div>
    </x-slot>

    @push('styles')
        <style>
            /* UI html5-qrcode kustom */
            #reader {
                border: none !important;
                background: transparent !important;
                border-radius: 1.25rem;
                overflow: hidden;
            }

            #reader video {
                object-fit: cover !important;
                width: 100% !important;
                height: 100% !important;
                border-radius: 1.25rem !important;
            }

            #reader__dashboard_section_csr span,
            #reader__dashboard_section_swaplink,
            #reader__status_span {
                display: none !important;
            }

            #reader__scan_region {
                background: transparent !important;
            }

            /* Animasi garis laser scan presisi */
            @keyframes laserSweep {
                0% {
                    top: 0%;
                    opacity: 0;
                }
                15% {
                    opacity: 1;
                }
                85% {
                    opacity: 1;
                }
                100% {
                    top: calc(100% - 2px);
                    opacity: 0;
                }
            }

            .animate-laser {
                position: absolute;
                left: 0;
                right: 0;
                animation: laserSweep 2.4s ease-in-out infinite;
            }
        </style>
    @endpush

    <div class="space-y-4 sm:space-y-6" x-data="{ mobileSection: 'scanner' }">
        
        <!-- Info Bar (Waktu Mengajar & Status Sesi) -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold shrink-0">
                    <span class="material-icons text-xl">schedule</span>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Waktu Pembelajaran
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Pukul <strong class="text-slate-800 dark:text-slate-200">{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</strong> WITA • Hari {{ ['1'=>'Senin','2'=>'Selasa','3'=>'Rabu','4'=>'Kamis','5'=>'Jumat','6'=>'Sabtu','7'=>'Minggu'][$schedule->day_of_week] ?? '' }}
                    </p>
                </div>
            </div>

            <!-- Mobile View Switcher (Hanya Tampil di Layar Ponsel & Tablet < lg) -->
            <div class="lg:hidden flex p-1 bg-slate-100 dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700">
                <button @click="mobileSection = 'scanner'" 
                        :class="mobileSection === 'scanner' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs transition-all">
                    <span class="material-icons text-sm">qr_code_scanner</span>
                    <span>Kamera Scan</span>
                </button>
                <button @click="mobileSection = 'roster'" 
                        :class="mobileSection === 'roster' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs transition-all">
                    <span class="material-icons text-sm">format_list_bulleted</span>
                    <span>Daftar Siswa</span>
                </button>
            </div>
        </div>

        <!-- Main Interactive Workspace -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6 items-start">

            <!-- Left Column: Camera Viewport & Mode Controls (5 Cols di Desktop) -->
            <div class="lg:col-span-5 space-y-4" :class="mobileSection === 'scanner' ? 'block' : 'hidden lg:block'">
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 overflow-hidden">
                    
                    <!-- Mode Tab Switcher (QR vs Face AI) -->
                    <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl mb-4 border border-slate-200/60 dark:border-slate-700/60">
                        <button id="tab-qr"
                                class="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold rounded-xl transition-all duration-200 text-white bg-sky-600 shadow-sm">
                            <span class="material-icons text-base">qr_code_scanner</span>
                            <span>Scan QR Code</span>
                        </button>
                        <button id="tab-face"
                                class="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold rounded-xl transition-all duration-200 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                            <span class="material-icons text-base">face</span>
                            <span>Scan Wajah AI</span>
                        </button>
                    </div>

                    <!-- QR Scanner Viewport -->
                    <div id="qr-scanner-container" class="relative">
                        <div class="relative w-full aspect-square max-w-[320px] sm:max-w-[340px] mx-auto bg-slate-950 rounded-3xl overflow-hidden shadow-inner border border-slate-800 flex items-center justify-center">
                            <div id="reader" class="w-full h-full"></div>
                            
                            <!-- Futuristic Precision Cyber Overlay -->
                            <div class="absolute inset-0 pointer-events-none p-4 sm:p-5 flex items-center justify-center z-10">
                                <div class="w-full h-full relative overflow-hidden rounded-2xl border border-sky-500/20 bg-sky-500/5">
                                    <div class="absolute top-0 left-0 w-7 h-7 border-t-3 border-l-3 border-sky-400 rounded-tl-xl"></div>
                                    <div class="absolute top-0 right-0 w-7 h-7 border-t-3 border-r-3 border-sky-400 rounded-tr-xl"></div>
                                    <div class="absolute bottom-0 left-0 w-7 h-7 border-b-3 border-l-3 border-sky-400 rounded-bl-xl"></div>
                                    <div class="absolute bottom-0 right-0 w-7 h-7 border-b-3 border-r-3 border-sky-400 rounded-br-xl"></div>

                                    <!-- Precision Laser Beam -->
                                    <div class="animate-laser h-0.5 bg-gradient-to-r from-transparent via-sky-400 to-transparent shadow-[0_0_16px_#38bdf8,0_0_4px_#0284c7]"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Camera Switcher -->
                        <div id="camera-switch-container" class="mt-3 text-center hidden">
                            <button id="camera-switch-button" type="button"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/40 hover:bg-sky-100 transition-colors shadow-2xs">
                                <span class="material-icons text-sm">cameraswitch</span>
                                <span>Ganti Kamera</span>
                            </button>
                        </div>
                    </div>

                    <!-- Face Scanner Viewport -->
                    <div id="face-scanner-container" class="relative hidden">
                        <div class="relative w-full aspect-square max-w-[320px] sm:max-w-[340px] mx-auto bg-slate-950 rounded-3xl overflow-hidden shadow-inner border-2 border-emerald-500/40 flex items-center justify-center">
                            <video id="face-video" class="w-full h-full object-cover" autoplay muted playsinline></video>
                            <canvas id="face-canvas" class="absolute inset-0 w-full h-full"></canvas>

                            <!-- Face Target Overlay -->
                            <div class="absolute inset-0 pointer-events-none flex items-center justify-center p-6 z-10">
                                <div class="w-full h-full max-w-[200px] max-h-[200px] relative">
                                    <div class="absolute top-0 left-0 w-6 h-6 border-t-4 border-l-4 border-emerald-400 rounded-tl-xl"></div>
                                    <div class="absolute top-0 right-0 w-6 h-6 border-t-4 border-r-4 border-emerald-400 rounded-tr-xl"></div>
                                    <div class="absolute bottom-0 left-0 w-6 h-6 border-b-4 border-l-4 border-emerald-400 rounded-bl-xl"></div>
                                    <div class="absolute bottom-0 right-0 w-6 h-6 border-b-4 border-r-4 border-emerald-400 rounded-br-xl"></div>
                                    <div class="absolute inset-3 border-2 border-dashed border-white/40 rounded-full animate-pulse"></div>
                                </div>
                            </div>

                            <!-- Loading Model Overlay -->
                            <div id="face-loading-overlay" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/85 backdrop-blur-sm z-20 hidden p-6 text-center">
                                <div class="w-8 h-8 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin mb-3"></div>
                                <div class="w-full max-w-[180px] bg-slate-800 rounded-full h-2 mb-2 overflow-hidden">
                                    <div id="face-loading-bar" class="bg-emerald-500 h-2 rounded-full transition-all duration-300 w-0"></div>
                                </div>
                                <p id="face-loading-text" class="text-xs text-white font-bold">Memuat Model AI...</p>
                            </div>
                        </div>

                        <p id="face-status" class="mt-3 text-center text-xs font-semibold text-slate-600 dark:text-slate-400">
                            Menyiapkan modul pengenal wajah...
                        </p>

                        <div id="face-camera-switch-container" class="mt-2 text-center">
                            <button id="face-camera-switch-button" type="button"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 transition-colors shadow-2xs">
                                <span class="material-icons text-sm">cameraswitch</span>
                                <span>Ganti Kamera</span>
                            </button>
                        </div>
                    </div>

                    <!-- Scanner Error Alert -->
                    <div id="reader-error" class="text-rose-600 dark:text-rose-400 text-xs mt-3 text-center font-bold bg-rose-50 dark:bg-rose-950/40 p-2.5 rounded-2xl border border-rose-200 dark:border-rose-900 hidden"></div>

                    <!-- Scanner Instruction Footer -->
                    <div class="mt-4 pt-3.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] sm:text-xs text-slate-500">
                        <span class="flex items-center gap-1.5 font-bold text-emerald-600 dark:text-emerald-400">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Kamera Siap
                        </span>
                        <span class="font-medium text-slate-400">Jarak Ideal: 20-40 cm</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Roster & Quick Actions (7 Cols di Desktop) -->
            <div class="lg:col-span-7 space-y-4" :class="mobileSection === 'roster' ? 'block' : 'hidden lg:block'">

                <!-- Metric Ribbon Cards -->
                <div class="grid grid-cols-3 gap-2.5 sm:gap-3">
                    <div class="bg-white dark:bg-slate-900 p-3 sm:p-3.5 rounded-2xl border border-emerald-100 dark:border-emerald-950/50 shadow-2xs flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Hadir</p>
                            <p class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white" id="attended-count">{{ $attendedStudents->count() }}</p>
                        </div>
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 font-black text-xs flex items-center justify-center">
                            H
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 p-3 sm:p-3.5 rounded-2xl border border-amber-100 dark:border-amber-950/50 shadow-2xs flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Izin / Sakit</p>
                            <p class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white" id="leave-count">{{ $studentsOnLeave->count() }}</p>
                        </div>
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 font-black text-xs flex items-center justify-center">
                            I/S
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 p-3 sm:p-3.5 rounded-2xl border border-rose-100 dark:border-rose-950/50 shadow-2xs flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Belum Hadir</p>
                            <p class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white" id="no-notice-count">{{ $studentsWithoutNotice->count() }}</p>
                        </div>
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 font-black text-xs flex items-center justify-center">
                            ?
                        </div>
                    </div>
                </div>

                <!-- Attendance Tabs & Interactive List -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden" x-data="{ listTab: 'unmarked', search: '' }">
                    
                    <!-- List Navigation & Search -->
                    <div class="p-3.5 sm:p-4 border-b border-slate-100 dark:border-slate-800 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                            <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200/60 dark:border-slate-700/60 overflow-x-auto no-scrollbar">
                                <button @click="listTab = 'unmarked'" 
                                        :class="listTab === 'unmarked' ? 'bg-white dark:bg-slate-700 text-rose-600 dark:text-white shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                                        class="px-3 py-1.5 text-xs rounded-lg transition-all whitespace-nowrap">
                                    Belum Absen
                                </button>
                                <button @click="listTab = 'attended'" 
                                        :class="listTab === 'attended' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-white shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                                        class="px-3 py-1.5 text-xs rounded-lg transition-all whitespace-nowrap">
                                    Sudah Hadir
                                </button>
                                <button @click="listTab = 'leave'" 
                                        :class="listTab === 'leave' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-white shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                                        class="px-3 py-1.5 text-xs rounded-lg transition-all whitespace-nowrap">
                                    Izin / Sakit
                                </button>
                            </div>

                            <div class="relative w-full sm:w-48">
                                <span class="material-icons absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">search</span>
                                <input type="text" x-model="search" placeholder="Cari nama siswa..." 
                                       class="w-full text-xs py-2 pl-8 pr-3 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                            </div>
                        </div>
                    </div>

                    <!-- 1. TAB: BELUM ABSEN (Daftar Siswa dengan Tombol Sentuh 1-Klik Cepat) -->
                    <div x-show="listTab === 'unmarked'" class="max-h-[460px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 no-scrollbar" id="no-notice-list">
                        @forelse($studentsWithoutNotice as $student)
                            <div class="p-3 sm:p-3.5 flex items-center justify-between gap-2.5 hover:bg-slate-50/70 dark:hover:bg-slate-850/50 transition-colors student-item" 
                                 id="student-no-notice-{{ $student->id }}"
                                 x-show="search === '' || {{ json_encode(mb_strtolower($student->name)) }}.includes(search.toLowerCase())">
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <img class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shrink-0 shadow-2xs"
                                         src="{{ $student->photo_url }}" 
                                         alt="{{ $student->name }}"
                                         onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=0284c7&background=e0f2fe'">
                                    <div class="min-w-0">
                                        <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 truncate leading-tight">{{ $student->name }}</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">NIS: {{ $student->nis ?? '-' }}</p>
                                    </div>
                                </div>
                                <!-- Quick Manual Touch Actions (H, S, I, A, B) + Anecdote Button -->
                                <div class="flex items-center gap-1 shrink-0">
                                    <button type="button" 
                                            onclick="openAnecdoteModal({{ $student->id }}, '{{ addslashes($student->name) }}', '{{ $student->photo_url }}', '{{ $student->nis ?? '-' }}')"
                                            class="anecdote-btn w-8 h-8 rounded-xl flex items-center justify-center transition-all active:scale-90 relative {{ isset($anecdotesToday[$student->id]) && $anecdotesToday[$student->id]->hasAnyNote() ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300 dark:border-amber-700 shadow-2xs' : 'bg-slate-100 text-slate-500 hover:bg-amber-50 hover:text-amber-600 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-amber-950/40 border border-slate-200 dark:border-slate-700' }}" 
                                            title="Catatan Anekdot (Akademik, Kehadiran, Sikap)" 
                                            id="anecdote-badge-{{ $student->id }}">
                                        <span class="material-icons text-sm">rate_review</span>
                                        @if(isset($anecdotesToday[$student->id]) && $anecdotesToday[$student->id]->hasAnyNote())
                                            <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-amber-500 rounded-full border-2 border-white dark:border-slate-900"></span>
                                        @endif
                                    </button>
                                    <button data-student-id="{{ $student->id }}" data-status="hadir"
                                            class="manual-mark-btn w-8 h-8 rounded-xl font-black text-xs bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white border border-emerald-200/80 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800 transition-all active:scale-90" 
                                            title="Tandai Hadir">H</button>
                                    <button data-student-id="{{ $student->id }}" data-status="sakit"
                                            class="manual-mark-btn w-8 h-8 rounded-xl font-black text-xs bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white border border-purple-200/80 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800 transition-all active:scale-90" 
                                            title="Tandai Sakit">S</button>
                                    <button data-student-id="{{ $student->id }}" data-status="izin"
                                            class="manual-mark-btn w-8 h-8 rounded-xl font-black text-xs bg-sky-50 text-sky-700 hover:bg-sky-600 hover:text-white border border-sky-200/80 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800 transition-all active:scale-90" 
                                            title="Tandai Izin">I</button>
                                    <button data-student-id="{{ $student->id }}" data-status="alpa"
                                            class="manual-mark-btn w-8 h-8 rounded-xl font-black text-xs bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white border border-rose-200/80 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800 transition-all active:scale-90" 
                                            title="Tandai Alpa">A</button>
                                    <button data-student-id="{{ $student->id }}" data-status="bolos"
                                            class="manual-mark-btn w-8 h-8 rounded-xl font-black text-xs bg-orange-50 text-orange-700 hover:bg-orange-600 hover:text-white border border-orange-200/80 dark:bg-orange-950/60 dark:text-orange-300 dark:border-orange-800 transition-all active:scale-90" 
                                            title="Tandai Bolos">B</button>
                                </div>
                            </div>
                        @empty
                            <div id="no-missing-students" class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 italic">
                                Semua siswa di kelas ini telah memiliki data absensi hari ini.
                            </div>
                        @endforelse
                    </div>

                    <!-- 2. TAB: SUDAH HADIR -->
                    <div x-show="listTab === 'attended'" class="max-h-[460px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 no-scrollbar" id="attended-list" style="display: none;">
                        @forelse($attendedStudents as $attendance)
                            <div class="p-3 sm:p-3.5 flex items-center justify-between gap-3 hover:bg-slate-50/70 dark:hover:bg-slate-850/50 transition-colors student-attended-row-{{ $attendance->student->id }}"
                                 x-show="search === '' || {{ json_encode(mb_strtolower($attendance->student->name)) }}.includes(search.toLowerCase())">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="relative shrink-0">
                                        <img class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl object-cover border-2 border-emerald-500 shadow-2xs"
                                             src="{{ $attendance->student->photo_url }}" 
                                             alt="{{ $attendance->student->name }}"
                                             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($attendance->student->name) }}&color=0284c7&background=e0f2fe'">
                                        <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full flex items-center justify-center text-[8px] text-white font-bold">✓</span>
                                    </div>
                                    <div class="truncate">
                                        <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 truncate leading-tight">{{ $attendance->student->name }}</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">NIS: {{ $attendance->student->nis ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" 
                                            onclick="openAnecdoteModal({{ $attendance->student->id }}, '{{ addslashes($attendance->student->name) }}', '{{ $attendance->student->photo_url }}', '{{ $attendance->student->nis ?? '-' }}')"
                                            class="anecdote-btn w-7 h-7 rounded-xl flex items-center justify-center transition-all active:scale-90 relative {{ isset($anecdotesToday[$attendance->student->id]) && $anecdotesToday[$attendance->student->id]->hasAnyNote() ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300 dark:border-amber-700 shadow-2xs' : 'bg-slate-100 text-slate-500 hover:bg-amber-50 hover:text-amber-600 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-amber-950/40 border border-slate-200 dark:border-slate-700' }}" 
                                            title="Catatan Anekdot" 
                                            id="anecdote-badge-attended-{{ $attendance->student->id }}">
                                        <span class="material-icons text-sm">rate_review</span>
                                        @if(isset($anecdotesToday[$attendance->student->id]) && $anecdotesToday[$attendance->student->id]->hasAnyNote())
                                            <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-amber-500 rounded-full border-2 border-white dark:border-slate-900"></span>
                                        @endif
                                    </button>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        {{ $attendance->created_at->format('H:i') }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div id="no-students-yet" class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 italic">
                                Belum ada siswa yang diabsen hadir.
                            </div>
                        @endforelse
                    </div>

                    <!-- 3. TAB: SISWA IZIN / SAKIT -->
                    <div x-show="listTab === 'leave'" class="max-h-[460px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 no-scrollbar" id="leave-list" style="display: none;">
                        @forelse($studentsOnLeave as $subjectAttendance)
                            <div class="p-3 sm:p-3.5 flex items-center justify-between gap-3 hover:bg-slate-50/70 dark:hover:bg-slate-850/50 transition-colors student-leave-row-{{ $subjectAttendance->student->id }}"
                                 x-show="search === '' || {{ json_encode(mb_strtolower($subjectAttendance->student->name)) }}.includes(search.toLowerCase())">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="relative shrink-0">
                                        <img class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl object-cover border-2 border-amber-500 shadow-2xs"
                                             src="{{ $subjectAttendance->student->photo_url }}" 
                                             alt="{{ $subjectAttendance->student->name }}"
                                             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($subjectAttendance->student->name) }}&color=0284c7&background=e0f2fe'">
                                        <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-amber-500 border-2 border-white dark:border-slate-900 rounded-full flex items-center justify-center text-[8px] text-white font-bold">
                                            {{ strtoupper(substr($subjectAttendance->status, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div class="truncate">
                                        <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 truncate leading-tight">{{ $subjectAttendance->student->name }}</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">NIS: {{ $subjectAttendance->student->nis ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" 
                                            onclick="openAnecdoteModal({{ $subjectAttendance->student->id }}, '{{ addslashes($subjectAttendance->student->name) }}', '{{ $subjectAttendance->student->photo_url }}', '{{ $subjectAttendance->student->nis ?? '-' }}')"
                                            class="anecdote-btn w-7 h-7 rounded-xl flex items-center justify-center transition-all active:scale-90 relative {{ isset($anecdotesToday[$subjectAttendance->student->id]) && $anecdotesToday[$subjectAttendance->student->id]->hasAnyNote() ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300 dark:border-amber-700 shadow-2xs' : 'bg-slate-100 text-slate-500 hover:bg-amber-50 hover:text-amber-600 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-amber-950/40 border border-slate-200 dark:border-slate-700' }}" 
                                            title="Catatan Anekdot" 
                                            id="anecdote-badge-leave-{{ $subjectAttendance->student->id }}">
                                        <span class="material-icons text-sm">rate_review</span>
                                        @if(isset($anecdotesToday[$subjectAttendance->student->id]) && $anecdotesToday[$subjectAttendance->student->id]->hasAnyNote())
                                            <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-amber-500 rounded-full border-2 border-white dark:border-slate-900"></span>
                                        @endif
                                    </button>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase
                                        @if($subjectAttendance->status == 'sakit') bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800 @endif
                                        @if($subjectAttendance->status == 'izin') bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-200 dark:border-sky-800 @endif
                                    ">
                                        {{ ucfirst($subjectAttendance->status) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div id="no-students-on-leave" class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 italic">
                                Tidak ada siswa yang izin atau sakit hari ini.
                            </div>
                        @endforelse
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Modal Notifikasi Hasil Scan Presensi (Teleported / Center Modal) -->
    <template x-teleport="body">
        <div id="attendance-modal"
             class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs flex items-center justify-center p-4 transition-opacity duration-300 opacity-0 hidden z-[99999]">
            <div id="modal-content"
                 class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-sm p-6 text-center transform scale-95 transition-all duration-300 border border-slate-200/80 dark:border-slate-800">
                <div id="modal-icon-container" class="mx-auto flex items-center justify-center h-16 w-16 rounded-2xl mb-4">
                    <svg id="modal-icon-svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"></svg>
                </div>
                <h2 id="modal-title" class="text-xl font-extrabold text-slate-900 dark:text-white mb-1"></h2>
                <p id="modal-message" class="text-xs text-slate-500 dark:text-slate-400 font-medium"></p>
            </div>
        </div>
    </template>

    <!-- Modal Catatan Anekdot Individu Siswa (Akademik, Kehadiran, Sikap) -->
    <template x-teleport="body">
        <div id="anecdote-modal" 
             class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 z-[999999] opacity-0 pointer-events-none transition-opacity duration-200">
            <div id="anecdote-modal-dialog" 
                 class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[90vh] overflow-hidden transform scale-95 transition-transform duration-200">
                
                <!-- Modal Header -->
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3 bg-slate-50/70 dark:bg-slate-850/50">
                    <div class="flex items-center gap-3 min-w-0">
                        <img id="anecdote-student-photo" src="" alt="Foto Siswa" 
                             class="w-10 h-10 rounded-2xl object-cover border border-slate-200 dark:border-slate-700 shadow-2xs shrink-0">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 id="anecdote-student-name" class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white truncate"></h3>
                                <span id="anecdote-date-badge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 dark:bg-sky-950/80 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                    {{ $selectedDate->translatedFormat('d M Y') }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-2">
                                <span id="anecdote-student-nis"></span> • <span>{{ $schedule->getActivityName() }}</span>
                            </p>
                        </div>
                    </div>
                    <button type="button" onclick="closeAnecdoteModal()" 
                            class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center transition-colors">
                        <span class="material-icons text-base">close</span>
                    </button>
                </div>

                <!-- Modal Body (Scrollable with Category Tabs) -->
                <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4" x-data="{ anTab: 'academic' }">
                    
                    <!-- Category Tabs Selector -->
                    <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 gap-1 overflow-x-auto no-scrollbar">
                        <button type="button" @click="anTab = 'academic'" 
                                :class="anTab === 'academic' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-300 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                                class="flex-1 min-w-[100px] flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs transition-all whitespace-nowrap">
                            <span class="material-icons text-sm">school</span>
                            <span>Akademik</span>
                        </button>
                        <button type="button" @click="anTab = 'attendance'" 
                                :class="anTab === 'attendance' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                                class="flex-1 min-w-[100px] flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs transition-all whitespace-nowrap">
                            <span class="material-icons text-sm">event_available</span>
                            <span>Kehadiran</span>
                        </button>
                        <button type="button" @click="anTab = 'attitude'" 
                                :class="anTab === 'attitude' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-300 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                                class="flex-1 min-w-[100px] flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs transition-all whitespace-nowrap">
                            <span class="material-icons text-sm">psychology</span>
                            <span>Sikap & Karakter</span>
                        </button>
                    </div>

                    <!-- 1. TAB: AKADEMIK -->
                    <div x-show="anTab === 'academic'" class="space-y-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <label class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <span class="material-icons text-sm text-indigo-500">auto_awesome</span>
                                <span>Penilaian / Sentimen Akademik</span>
                            </label>
                            <span class="text-[10px] text-slate-400">Pilih salah satu</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="academic_sentiment" value="positive" class="sr-only peer">
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-emerald-50 peer-checked:border-emerald-400 peer-checked:text-emerald-700 dark:peer-checked:bg-emerald-950/60 dark:peer-checked:text-emerald-300 dark:peer-checked:border-emerald-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">🌟</span>
                                    <span class="text-[11px] font-bold block mt-1">Sangat Baik / Aktif</span>
                                </div>
                            </label>
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="academic_sentiment" value="neutral" class="sr-only peer" checked>
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-sky-50 peer-checked:border-sky-400 peer-checked:text-sky-700 dark:peer-checked:bg-sky-950/60 dark:peer-checked:text-sky-300 dark:peer-checked:border-sky-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">⚖️</span>
                                    <span class="text-[11px] font-bold block mt-1">Cukup / Sesuai</span>
                                </div>
                            </label>
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="academic_sentiment" value="needs_guidance" class="sr-only peer">
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-rose-50 peer-checked:border-rose-400 peer-checked:text-rose-700 dark:peer-checked:bg-rose-950/60 dark:peer-checked:text-rose-300 dark:peer-checked:border-rose-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">💡</span>
                                    <span class="text-[11px] font-bold block mt-1">Perlu Bimbingan</span>
                                </div>
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Catatan Pengamatan Akademik:
                            </label>
                            <textarea id="anecdote-academic-note" rows="3" 
                                      placeholder="Contoh: Memahami materi fungsi kuadrat dengan cepat, sangat aktif memimpin diskusi kelompok, perlu latihan tambahan soal nomor 4..." 
                                      class="w-full text-xs p-3 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"></textarea>
                        </div>
                    </div>

                    <!-- 2. TAB: KEHADIRAN -->
                    <div x-show="anTab === 'attendance'" class="space-y-3.5" style="display: none;">
                        <div class="flex items-center justify-between gap-2">
                            <label class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <span class="material-icons text-sm text-sky-500">schedule</span>
                                <span>Kedisiplinan & Kehadiran Mapel</span>
                            </label>
                            <span class="text-[10px] text-slate-400">Pilih salah satu</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="attendance_sentiment" value="positive" class="sr-only peer">
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-emerald-50 peer-checked:border-emerald-400 peer-checked:text-emerald-700 dark:peer-checked:bg-emerald-950/60 dark:peer-checked:text-emerald-300 dark:peer-checked:border-emerald-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">⏰</span>
                                    <span class="text-[11px] font-bold block mt-1">Tepat Waktu / Disiplin</span>
                                </div>
                            </label>
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="attendance_sentiment" value="neutral" class="sr-only peer" checked>
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-sky-50 peer-checked:border-sky-400 peer-checked:text-sky-700 dark:peer-checked:bg-sky-950/60 dark:peer-checked:text-sky-300 dark:peer-checked:border-sky-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">📋</span>
                                    <span class="text-[11px] font-bold block mt-1">Normal / Izin Wajar</span>
                                </div>
                            </label>
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="attendance_sentiment" value="needs_guidance" class="sr-only peer">
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-rose-50 peer-checked:border-rose-400 peer-checked:text-rose-700 dark:peer-checked:bg-rose-950/60 dark:peer-checked:text-rose-300 dark:peer-checked:border-rose-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">⚠️</span>
                                    <span class="text-[11px] font-bold block mt-1">Perlu Diperhatikan</span>
                                </div>
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Catatan Kehadiran & Kedisiplinan:
                            </label>
                            <textarea id="anecdote-attendance-note" rows="3" 
                                      placeholder="Contoh: Terlambat 15 menit masuk kelas setelah istirahat, izin ke toilet lebih dari 20 menit, sering meminta izin keluar saat jam pelajaran..." 
                                      class="w-full text-xs p-3 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all placeholder:text-slate-400"></textarea>
                        </div>
                    </div>

                    <!-- 3. TAB: SIKAP & KARAKTER -->
                    <div x-show="anTab === 'attitude'" class="space-y-3.5" style="display: none;">
                        <div class="flex items-center justify-between gap-2">
                            <label class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <span class="material-icons text-sm text-emerald-500">favorite</span>
                                <span>Observasi Karakter & Sikap</span>
                            </label>
                            <span class="text-[10px] text-slate-400">Pilih salah satu</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="attitude_sentiment" value="positive" class="sr-only peer">
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-emerald-50 peer-checked:border-emerald-400 peer-checked:text-emerald-700 dark:peer-checked:bg-emerald-950/60 dark:peer-checked:text-emerald-300 dark:peer-checked:border-emerald-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">👏</span>
                                    <span class="text-[11px] font-bold block mt-1">Sopan & Teladan</span>
                                </div>
                            </label>
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="attitude_sentiment" value="neutral" class="sr-only peer" checked>
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-sky-50 peer-checked:border-sky-400 peer-checked:text-sky-700 dark:peer-checked:bg-sky-950/60 dark:peer-checked:text-sky-300 dark:peer-checked:border-sky-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">🙂</span>
                                    <span class="text-[11px] font-bold block mt-1">Baik / Tertib</span>
                                </div>
                            </label>
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="attitude_sentiment" value="needs_guidance" class="sr-only peer">
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-rose-50 peer-checked:border-rose-400 peer-checked:text-rose-700 dark:peer-checked:bg-rose-950/60 dark:peer-checked:text-rose-300 dark:peer-checked:border-rose-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">🤝</span>
                                    <span class="text-[11px] font-bold block mt-1">Perlu Pembinaan</span>
                                </div>
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Catatan Perilaku / Karakter:
                            </label>
                            <textarea id="anecdote-attitude-note" rows="3" 
                                      placeholder="Contoh: Sangat kooperatif membantu teman saat praktikum, berbicara tidak sopan saat ditegur guru, antusias membersihkan meja belajar..." 
                                      class="w-full text-xs p-3 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all placeholder:text-slate-400"></textarea>
                        </div>
                    </div>

                    <!-- Tindak Lanjut & Pengaturan Visibilitas (Selalu Tampil) -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Rencana Tindak Lanjut Guru (Opsional):
                            </label>
                            <input type="text" id="anecdote-follow-up" 
                                   placeholder="Misal: Berikan tugas pengayaan, ajak bicara empat mata, koordinasi dengan Wali Kelas/BK..." 
                                   class="w-full text-xs py-2 px-3 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                        </div>

                        <!-- Toggle Visibilitas Orang Tua -->
                        <div class="p-3 rounded-2xl bg-amber-50/50 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-900/60 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="material-icons text-amber-600 text-lg">family_restroom</span>
                                <div>
                                    <p class="text-xs font-bold text-slate-900 dark:text-white">Visibilitas Orang Tua</p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400">Izinkan orang tua melihat catatan ini di portal siswa</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="checkbox" id="anecdote-visible-to-parents" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-300 peer-focus:outline-hidden rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-amber-500"></div>
                            </label>
                        </div>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-3.5 bg-slate-50/80 dark:bg-slate-850/80 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
                    <div id="anecdote-status-text" class="text-xs text-slate-500 font-medium truncate"></div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" onclick="closeAnecdoteModal()" 
                                class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors">
                            Batal
                        </button>
                        <button type="button" id="save-anecdote-btn" onclick="saveAnecdote()" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-sm shadow-amber-600/20 transition-all active:scale-95">
                            <span class="material-icons text-sm">save</span>
                            <span>Simpan Catatan</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </template>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
        <script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                let lastScanTime = 0;
                const scanCooldown = 2500;
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
                const leaveCount = document.getElementById('leave-count');
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

                // === TABS SWITCH LOGIC (QR vs FACE AI) ===
                if (tabQr && tabFace) {
                    tabQr.addEventListener('click', () => switchMode('qr'));
                    tabFace.addEventListener('click', () => switchMode('face'));
                }

                function switchMode(mode) {
                    if (currentMode === mode) return;
                    currentMode = mode;
                    if (readerError) readerError.classList.add('hidden');

                    if (mode === 'qr') {
                        tabQr.className = 'flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold rounded-xl transition-all duration-200 text-white bg-sky-600 shadow-sm';
                        tabFace.className = 'flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold rounded-xl transition-all duration-200 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white';

                        if (faceScannerContainer) faceScannerContainer.classList.add('hidden');
                        if (qrScannerContainer) qrScannerContainer.classList.remove('hidden');

                        stopFaceScanner();
                        startQrScanner();
                    } else {
                        tabFace.className = 'flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold rounded-xl transition-all duration-200 text-white bg-sky-600 shadow-sm';
                        tabQr.className = 'flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold rounded-xl transition-all duration-200 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white';

                        if (qrScannerContainer) qrScannerContainer.classList.add('hidden');
                        if (faceScannerContainer) faceScannerContainer.classList.remove('hidden');

                        stopQrScanner();
                        startFaceScanner();
                    }
                }

                // === FACE RECOGNITION ===
                async function loadFaceModels() {
                    if (isModelsLoaded) return true;

                    const overlay = document.getElementById('face-loading-overlay');
                    const bar = document.getElementById('face-loading-bar');
                    const text = document.getElementById('face-loading-text');
                    if (overlay) overlay.classList.remove('hidden');

                    if (faceStatus) faceStatus.textContent = 'Memuat model AI wajah...';
                    try {
                        const MODEL_URL = '{{ asset('models') }}';

                        let progress = 0;
                        const interval = setInterval(() => {
                            progress += Math.random() * 15;
                            if (progress > 90) progress = 90;
                            if (bar) bar.style.width = `${progress}%`;
                            if (text) text.textContent = `Memuat AI: ${Math.round(progress)}%`;
                        }, 400);

                        await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
                        if (bar) bar.style.width = `33%`;
                        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                        if (bar) bar.style.width = `66%`;
                        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

                        clearInterval(interval);
                        if (bar) bar.style.width = `100%`;
                        if (text) text.textContent = `AI Siap Digunakan`;

                        setTimeout(() => {
                            if (overlay) overlay.classList.add('hidden');
                        }, 400);

                        isModelsLoaded = true;
                        return true;
                    } catch (error) {
                        if (typeof interval !== 'undefined') clearInterval(interval);
                        console.error('Error loading face models:', error);
                        if (faceStatus) faceStatus.textContent = 'Gagal memuat model wajah. Periksa koneksi jaringan.';
                        if (overlay) overlay.classList.add('hidden');
                        return false;
                    }
                }

                async function startFaceScanner() {
                    if (!await loadFaceModels()) return;

                    if (!faceMatcher) {
                        if (faceStatus) faceStatus.textContent = 'Memproses data foto kelas...';
                        try {
                            const labeledDescriptors = await loadLabeledImages();
                            if (labeledDescriptors.length === 0) {
                                if (faceStatus) faceStatus.textContent = 'Tidak ada data foto wajah siswa yang valid pada kelas ini.';
                                return;
                            }
                            faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.5);
                        } catch (error) {
                            if (faceStatus) faceStatus.textContent = 'Gagal memproses data wajah.';
                            console.error(error);
                            return;
                        }
                    }

                    if (faceStatus) faceStatus.textContent = 'Menyalakan kamera wajah...';
                    navigator.mediaDevices.getUserMedia({
                        video: { facingMode: currentFacingMode }
                    })
                    .then(stream => {
                        if (faceVideo) faceVideo.srcObject = stream;
                    })
                    .catch(err => {
                        console.error("Gagal akses kamera:", err);
                        if (readerError) {
                            readerError.textContent = "Gagal mengakses kamera. Berikan izin akses kamera di browser Anda.";
                            readerError.classList.remove('hidden');
                        }

                        if (currentFacingMode !== 'user') {
                            currentFacingMode = 'user';
                            startFaceScanner();
                        }
                    });
                }

                if (faceSwitchButton) {
                    faceSwitchButton.addEventListener('click', () => {
                        currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';

                        if (faceVideo && faceVideo.srcObject) {
                            faceVideo.srcObject.getTracks().forEach(track => track.stop());
                        }
                        if (faceCanvas && faceCanvas.getContext) {
                            faceCanvas.getContext('2d').clearRect(0, 0, faceCanvas.width, faceCanvas.height);
                        }

                        if (faceStatus) faceStatus.textContent = 'Menukar kamera...';
                        startFaceScanner();
                    });
                }

                function stopFaceScanner() {
                    if (faceVideo && faceVideo.srcObject) {
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
                            return new Promise(async (resolve) => {
                                try {
                                    if (student.face_descriptor) {
                                        try {
                                            const descArray = JSON.parse(student.face_descriptor);
                                            const floatArray = new Float32Array(descArray);
                                            resolve(new faceapi.LabeledFaceDescriptors(student.unique_id, [floatArray]));
                                            return;
                                        } catch (e) {
                                            console.warn("Gagal parsing descriptor:", student.name, e);
                                        }
                                    }

                                    const img = new Image();
                                    img.crossOrigin = 'anonymous';
                                    img.src = student.photo_url;

                                    img.onload = async () => {
                                        try {
                                            const options = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 });
                                            const detections = await faceapi.detectSingleFace(img, options).withFaceLandmarks().withFaceDescriptor();
                                            if (!detections) {
                                                resolve(null);
                                                return;
                                            }

                                            const descriptorStr = JSON.stringify(Array.from(detections.descriptor));
                                            fetch("{{ route('attendance.save_descriptor') }}", {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                                    'Accept': 'application/json'
                                                },
                                                body: JSON.stringify({
                                                    unique_id: student.unique_id,
                                                    face_descriptor: descriptorStr
                                                })
                                            });

                                            resolve(new faceapi.LabeledFaceDescriptors(student.unique_id, [detections.descriptor]));
                                        } catch (e) {
                                            resolve(null);
                                        }
                                    };

                                    img.onerror = () => resolve(null);
                                } catch (err) {
                                    resolve(null);
                                }
                            });
                        })
                    ).then(results => results.filter(res => res !== null));
                }

                if (faceVideo) {
                    faceVideo.addEventListener('play', () => {
                        const displaySize = { width: faceVideo.offsetWidth, height: faceVideo.offsetHeight };
                        if (displaySize.width === 0 || displaySize.height === 0) return;

                        faceapi.matchDimensions(faceCanvas, displaySize);
                        if (faceStatus) faceStatus.textContent = 'Arahkan wajah ke kamera...';

                        faceScanInterval = setInterval(async () => {
                            if (faceVideo.paused || faceVideo.ended) return;

                            const detections = await faceapi.detectAllFaces(faceVideo, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 }))
                                .withFaceLandmarks()
                                .withFaceDescriptors();

                            const resizedDetections = faceapi.resizeResults(detections, displaySize);
                            if (faceCanvas && faceCanvas.getContext) {
                                faceCanvas.getContext('2d').clearRect(0, 0, faceCanvas.width, faceCanvas.height);
                            }

                            if (detections.length > 0) {
                                const bestMatch = faceMatcher ? faceMatcher.findBestMatch(detections[0].descriptor) : { label: 'unknown' };
                                if (bestMatch.label !== 'unknown') {
                                    consecutiveMatches++;
                                    if (faceStatus) faceStatus.textContent = `Wajah Dikenali! Tahan sebentar... (${consecutiveMatches}/3)`;

                                    if (consecutiveMatches >= 3 && Date.now() - lastScanTime > scanCooldown) {
                                        lastScanTime = Date.now();
                                        processAttendance(bestMatch.label);
                                        consecutiveMatches = 0;
                                    }
                                } else {
                                    consecutiveMatches = 0;
                                    if (faceStatus) faceStatus.textContent = 'Arahkan wajah ke dalam lingkaran...';
                                }
                            } else {
                                consecutiveMatches = 0;
                                if (faceStatus && faceStatus.textContent !== 'Menyiapkan kamera...') {
                                    faceStatus.textContent = 'Arahkan wajah ke dalam lingkaran...';
                                }
                            }
                        }, 500);
                    });
                }

                // === QR SCANNER ===
                function startQrScanner() {
                    Html5Qrcode.getCameras().then(devices => {
                        if (devices && devices.length) {
                            cameras = devices;
                            let backCameraIndex = cameras.findIndex(camera => camera.label.toLowerCase().includes('back'));
                            currentCameraIndex = backCameraIndex !== -1 ? backCameraIndex : 0;

                            startScannerWithCamera(cameras[currentCameraIndex].id);
                            if (cameras.length > 1 && switchContainer) {
                                switchContainer.classList.remove('hidden');
                            }
                        } else { 
                            throw new Error("Tidak ada kamera yang terdeteksi."); 
                        }
                    }).catch(err => {
                        if (readerError) {
                            readerError.textContent = "Gagal mengakses kamera: " + err.message;
                            readerError.classList.remove('hidden');
                        }
                    });
                }

                function stopQrScanner() {
                    if (html5QrCode && html5QrCode.isScanning) {
                        html5QrCode.stop().catch(err => console.error("Dead QR scanner", err));
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
                        console.error("Gagal memutar audio:", e);
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
                    const dateInput = document.getElementById('attendance-date');
                    const selectedDate = dateInput ? dateInput.value : '{{ $selectedDate->format('Y-m-d') }}';
                    fetch("{{ route('teacher.subject.attendance.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            student_unique_id: studentId,
                            schedule_id: scheduleId,
                            date: selectedDate
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
                        studentRow.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        studentRow.style.opacity = '0';
                        studentRow.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            studentRow.remove();
                            if (noNoticeCount) {
                                noNoticeCount.textContent = Math.max(0, parseInt(noNoticeCount.textContent) - 1);
                            }
                        }, 300);
                    }
                }

                function addStudentToList(student) {
                    if (noStudentsYet) noStudentsYet.classList.add('hidden');
                    
                    const photoUrl = student.photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(student.name)}&color=0284c7&background=e0f2fe`;
                    const listItem = document.createElement('div');
                    listItem.className = 'p-3 sm:p-3.5 flex items-center justify-between gap-3 bg-emerald-50/40 dark:bg-emerald-950/20 transition-colors animate-[pulse_1s_ease-out]';
                    listItem.innerHTML = `
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="relative shrink-0">
                                <img class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl object-cover border-2 border-emerald-500 shadow-2xs"
                                     src="${photoUrl}" 
                                     alt="${student.name}"
                                     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(student.name)}&color=0284c7&background=e0f2fe'">
                                <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full flex items-center justify-center text-[8px] text-white font-bold">✓</span>
                            </div>
                            <div class="truncate">
                                <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 truncate leading-tight">${student.name}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">Presensi Berhasil</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            ${student.time}
                        </span>
                    `;
                    if (attendedList) attendedList.prepend(listItem);
                    if (attendedCount) {
                        attendedCount.textContent = parseInt(attendedCount.textContent) + 1;
                    }
                }

                function addStudentToLeaveList(student, status) {
                    if (noStudentsOnLeave) noStudentsOnLeave.classList.add('hidden');

                    const statusClass = status === 'sakit'
                        ? 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-200 dark:border-purple-800'
                        : 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border-sky-200 dark:border-sky-800';

                    const statusText = status.charAt(0).toUpperCase() + status.slice(1);
                    const photoUrl = student.photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(student.name)}&color=0284c7&background=e0f2fe`;

                    const listItem = document.createElement('div');
                    listItem.className = 'p-3 sm:p-3.5 flex items-center justify-between gap-3 bg-amber-50/40 dark:bg-amber-950/20 transition-colors';
                    listItem.innerHTML = `
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="relative shrink-0">
                                <img class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl object-cover border-2 border-amber-500 shadow-2xs"
                                     src="${photoUrl}" 
                                     alt="${student.name}"
                                     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(student.name)}&color=0284c7&background=e0f2fe'">
                                <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-amber-500 border-2 border-white dark:border-slate-900 rounded-full flex items-center justify-center text-[8px] text-white font-bold">
                                    ${status.charAt(0).toUpperCase()}
                                </span>
                            </div>
                            <div class="truncate">
                                <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 truncate leading-tight">${student.name}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">Status Khusus</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase border ${statusClass}">
                            ${statusText}
                        </span>
                    `;
                    if (leaveList) leaveList.prepend(listItem);
                    if (leaveCount) {
                        leaveCount.textContent = parseInt(leaveCount.textContent) + 1;
                    }
                }

                function showModal(isSuccess, data) {
                    playSound(isSuccess);
                    if (!modal.element) return;

                    modal.iconContainer.className = 'mx-auto flex items-center justify-center h-16 w-16 rounded-2xl mb-4';
                    modal.iconSvg.innerHTML = '';

                    if (isSuccess) {
                        modal.iconContainer.classList.add('bg-emerald-100', 'dark:bg-emerald-950/70');
                        modal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />`;
                        modal.iconSvg.className = 'h-8 w-8 text-emerald-600 dark:text-emerald-400';
                        modal.title.textContent = 'Presensi Berhasil';
                        if (data.student) {
                            if (data.student.status === 'sakit' || data.student.status === 'izin') {
                                addStudentToLeaveList(data.student, data.student.status);
                                removeStudentFromNoNoticeList(data.student.id);
                            } else if (data.student.status === 'hadir') {
                                addStudentToList(data.student);
                                removeStudentFromNoNoticeList(data.student.id);
                            } else {
                                removeStudentFromNoNoticeList(data.student.id);
                            }
                        }
                    } else {
                        modal.iconContainer.classList.add('bg-rose-100', 'dark:bg-rose-950/70');
                        modal.iconSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />`;
                        modal.iconSvg.className = 'h-8 w-8 text-rose-600 dark:text-rose-400';
                        modal.title.textContent = 'Presensi Gagal';
                    }

                    modal.message.textContent = data.message;

                    modal.element.classList.remove('hidden');
                    setTimeout(() => {
                        modal.element.classList.remove('opacity-0');
                        modal.content.classList.remove('scale-95');
                    }, 10);

                    setTimeout(hideModal, scanCooldown - 400);
                }

                function hideModal() {
                    if (!modal.element) return;
                    modal.element.classList.add('opacity-0');
                    modal.content.classList.add('scale-95');
                    setTimeout(() => {
                        modal.element.classList.add('hidden');
                        if (currentMode === 'qr' && html5QrCode && html5QrCode.isScanning) {
                            html5QrCode.resume();
                        }
                    }, 250);
                }

                function startScannerWithCamera(cameraId) {
                    html5QrCode.start(
                        cameraId,
                        { fps: 15, qrbox: { width: 220, height: 220 } },
                        onScanSuccess,
                        (errorMessage) => { }
                    ).catch((err) => {
                        if (readerError) {
                            readerError.textContent = "Gagal memulai kamera. Pastikan Anda mengizinkan akses kamera di browser.";
                            readerError.classList.remove('hidden');
                        }
                    });
                }

                function handleManualMark(event) {
                    const button = event.currentTarget;
                    const studentId = button.dataset.studentId;
                    const status = button.dataset.status;
                    const dateInput = document.getElementById('attendance-date');
                    const selectedDate = dateInput ? dateInput.value : '{{ $selectedDate->format('Y-m-d') }}';

                    button.classList.add('animate-pulse');

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
                            status: status,
                            date: selectedDate
                        })
                    }).then(response => response.json().then(data => ({ status: response.status, body: data })))
                        .then(({ status: httpStatus, body }) => {
                            button.classList.remove('animate-pulse');
                            showModal(body.success, body);
                        }).catch(error => {
                            button.classList.remove('animate-pulse');
                            showModal(false, { message: 'Tidak dapat terhubung ke server.' });
                        });
                }

                document.querySelectorAll('.manual-mark-btn').forEach(button => {
                    button.addEventListener('click', handleManualMark);
                });

                // Start QR Scanner
                startQrScanner();

                if (switchButton) {
                    switchButton.addEventListener('click', () => {
                        if (html5QrCode && html5QrCode.isScanning) {
                            html5QrCode.stop().then(() => {
                                currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
                                startScannerWithCamera(cameras[currentCameraIndex].id);
                            });
                        }
                    });
                }

                const dateInput = document.getElementById('attendance-date');
                if (dateInput) {
                    dateInput.addEventListener('change', function() {
                        window.location.href = `?date=${this.value}`;
                    });
                }

                // ==========================================
                // === MANAJEMEN MODAL CATATAN ANEKDOT SISWA ===
                // ==========================================
                let currentAnecdoteStudentId = null;

                window.openAnecdoteModal = function(studentId, name, photoUrl, nis) {
                    currentAnecdoteStudentId = studentId;
                    
                    const modal = document.getElementById('anecdote-modal');
                    const dialog = document.getElementById('anecdote-modal-dialog');
                    const photo = document.getElementById('anecdote-student-photo');
                    const nameElem = document.getElementById('anecdote-student-name');
                    const nisElem = document.getElementById('anecdote-student-nis');
                    const statusText = document.getElementById('anecdote-status-text');

                    if (photo) photo.src = photoUrl || `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=0284c7&background=e0f2fe`;
                    if (nameElem) nameElem.textContent = name;
                    if (nisElem) nisElem.textContent = `NIS: ${nis || '-'}`;
                    if (statusText) statusText.textContent = 'Memuat data...';

                    // Reset form
                    document.getElementById('anecdote-academic-note').value = '';
                    document.getElementById('anecdote-attendance-note').value = '';
                    document.getElementById('anecdote-attitude-note').value = '';
                    document.getElementById('anecdote-follow-up').value = '';
                    document.getElementById('anecdote-visible-to-parents').checked = false;

                    document.querySelectorAll('input[name="academic_sentiment"]').forEach(r => r.checked = (r.value === 'neutral'));
                    document.querySelectorAll('input[name="attendance_sentiment"]').forEach(r => r.checked = (r.value === 'neutral'));
                    document.querySelectorAll('input[name="attitude_sentiment"]').forEach(r => r.checked = (r.value === 'neutral'));

                    // Tampilkan modal
                    if (modal && dialog) {
                        modal.classList.remove('opacity-0', 'pointer-events-none');
                        modal.classList.add('opacity-100', 'pointer-events-auto');
                        dialog.classList.remove('scale-95');
                        dialog.classList.add('scale-100');
                    }

                    // Ambil data catatan eksisting via AJAX
                    const curDate = dateInput ? dateInput.value : '{{ $selectedDate->format('Y-m-d') }}';
                    fetch(`{{ route('teacher.anecdotes.get_student') }}?student_id=${studentId}&schedule_id=${scheduleId}&date=${curDate}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (statusText) statusText.textContent = '';
                        if (data.success && data.anecdote) {
                            const an = data.anecdote;
                            document.getElementById('anecdote-academic-note').value = an.academic_note || '';
                            document.getElementById('anecdote-attendance-note').value = an.attendance_note || '';
                            document.getElementById('anecdote-attitude-note').value = an.attitude_note || '';
                            document.getElementById('anecdote-follow-up').value = an.follow_up || '';
                            document.getElementById('anecdote-visible-to-parents').checked = !!an.is_visible_to_parents;

                            const acRadio = document.querySelector(`input[name="academic_sentiment"][value="${an.academic_sentiment || 'neutral'}"]`);
                            if (acRadio) acRadio.checked = true;

                            const atRadio = document.querySelector(`input[name="attendance_sentiment"][value="${an.attendance_sentiment || 'neutral'}"]`);
                            if (atRadio) atRadio.checked = true;

                            const attRadio = document.querySelector(`input[name="attitude_sentiment"][value="${an.attitude_sentiment || 'neutral'}"]`);
                            if (attRadio) attRadio.checked = true;
                        }
                    })
                    .catch(err => {
                        if (statusText) statusText.textContent = 'Gagal memuat catatan sebelumnya.';
                    });
                };

                window.closeAnecdoteModal = function() {
                    const modal = document.getElementById('anecdote-modal');
                    const dialog = document.getElementById('anecdote-modal-dialog');
                    if (modal && dialog) {
                        modal.classList.remove('opacity-100', 'pointer-events-auto');
                        modal.classList.add('opacity-0', 'pointer-events-none');
                        dialog.classList.remove('scale-100');
                        dialog.classList.add('scale-95');
                    }
                    currentAnecdoteStudentId = null;
                };

                window.saveAnecdote = function() {
                    if (!currentAnecdoteStudentId) return;

                    const saveBtn = document.getElementById('save-anecdote-btn');
                    const statusText = document.getElementById('anecdote-status-text');
                    const curDate = dateInput ? dateInput.value : '{{ $selectedDate->format('Y-m-d') }}';

                    const academicSentiment = document.querySelector('input[name="academic_sentiment"]:checked')?.value || 'neutral';
                    const attendanceSentiment = document.querySelector('input[name="attendance_sentiment"]:checked')?.value || 'neutral';
                    const attitudeSentiment = document.querySelector('input[name="attitude_sentiment"]:checked')?.value || 'neutral';

                    const academicNote = document.getElementById('anecdote-academic-note').value;
                    const attendanceNote = document.getElementById('anecdote-attendance-note').value;
                    const attitudeNote = document.getElementById('anecdote-attitude-note').value;
                    const followUp = document.getElementById('anecdote-follow-up').value;
                    const isVisibleToParents = document.getElementById('anecdote-visible-to-parents').checked;

                    const origBtnContent = saveBtn.innerHTML;
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = `<span class="inline-block animate-spin mr-1">↻</span> Menyimpan...`;
                    if (statusText) statusText.textContent = 'Menyimpan ke basis data...';

                    fetch("{{ route('teacher.anecdotes.store_update') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            student_id: currentAnecdoteStudentId,
                            schedule_id: scheduleId,
                            date: curDate,
                            academic_note: academicNote,
                            academic_sentiment: academicSentiment,
                            attendance_note: attendanceNote,
                            attendance_sentiment: attendanceSentiment,
                            attitude_note: attitudeNote,
                            attitude_sentiment: attitudeSentiment,
                            follow_up: followUp,
                            is_visible_to_parents: isVisibleToParents ? 1 : 0
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = origBtnContent;

                        if (data.success) {
                            if (statusText) {
                                statusText.className = 'text-xs text-emerald-600 dark:text-emerald-400 font-bold truncate';
                                statusText.textContent = '✓ Catatan berhasil disimpan!';
                            }

                            // Update badge penanda visual pada kartu siswa
                            const studentId = currentAnecdoteStudentId;
                            const badges = [
                                document.getElementById(`anecdote-badge-${studentId}`),
                                document.getElementById(`anecdote-badge-attended-${studentId}`),
                                document.getElementById(`anecdote-badge-leave-${studentId}`)
                            ];

                            badges.forEach(badge => {
                                if (badge) {
                                    if (data.anecdote.has_notes) {
                                        badge.className = badge.className.replace(/bg-slate-\d+/g, '').replace(/text-slate-\d+/g, '');
                                        badge.classList.add('bg-amber-100', 'text-amber-700', 'dark:bg-amber-950/80', 'dark:text-amber-300', 'border-amber-300', 'dark:border-amber-700', 'shadow-2xs');
                                        if (!badge.querySelector('.bg-amber-500')) {
                                            const dot = document.createElement('span');
                                            dot.className = 'absolute -top-1 -right-1 w-2.5 h-2.5 bg-amber-500 rounded-full border-2 border-white dark:border-slate-900';
                                            badge.appendChild(dot);
                                        }
                                    } else {
                                        const dot = badge.querySelector('.bg-amber-500');
                                        if (dot) dot.remove();
                                        badge.classList.remove('bg-amber-100', 'text-amber-700', 'dark:bg-amber-950/80', 'dark:text-amber-300', 'border-amber-300', 'dark:border-amber-700', 'shadow-2xs');
                                        badge.classList.add('bg-slate-100', 'text-slate-500', 'dark:bg-slate-800', 'dark:text-slate-400');
                                    }
                                }
                            });

                            setTimeout(() => {
                                closeAnecdoteModal();
                            }, 800);
                        } else {
                            if (statusText) {
                                statusText.className = 'text-xs text-rose-600 dark:text-rose-400 font-bold truncate';
                                statusText.textContent = data.message || 'Gagal menyimpan catatan.';
                            }
                        }
                    })
                    .catch(err => {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = origBtnContent;
                        if (statusText) {
                            statusText.className = 'text-xs text-rose-600 dark:text-rose-400 font-bold truncate';
                            statusText.textContent = 'Terjadi kesalahan jaringan.';
                        }
                    });
                };

            });
        </script>
    @endpush
</x-app-layout>