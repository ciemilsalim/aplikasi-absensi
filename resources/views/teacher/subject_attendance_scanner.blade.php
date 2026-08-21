@php
    $isCocurricular = $schedule->isCocurricular();
    $themeColor = $isCocurricular ? 'indigo' : 'sky';
    $themeBgSoft = $isCocurricular ? 'bg-indigo-50 dark:bg-indigo-950/60' : 'bg-sky-50 dark:bg-sky-950/60';
    $themeText = $isCocurricular ? 'text-indigo-600 dark:text-indigo-400' : 'text-sky-600 dark:text-sky-400';
    $themeBtn = $isCocurricular ? 'bg-indigo-600 hover:bg-indigo-500' : 'bg-sky-600 hover:bg-sky-500';
    $themeBorder = $isCocurricular ? 'border-indigo-200 dark:border-indigo-800' : 'border-sky-200 dark:border-sky-800';
    
    $rawClassName = $schedule->getTargetClass()?->name ?? '-';
    $displayClassName = str_starts_with($rawClassName, 'Kelas') ? $rawClassName : 'Kelas ' . $rawClassName;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => $isCocurricular ? 'fasilitator_kokurikuler' : 'guru_mapel'])],
                    ['title' => $isCocurricular ? 'Presensi Kokurikuler' : 'Sesi Presensi Mengajar', 'url' => '#']
                ]" />
                <h1 class="text-lg sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    {{ $isCocurricular ? 'Presensi Kokurikuler' : 'Sesi Presensi Mengajar' }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $schedule->getActivityName() }}</span> &bull; <span>{{ $displayClassName }}</span>
                </p>
            </div>

            <!-- Header Action Controls -->
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('teacher.dashboard', ['view' => $isCocurricular ? 'fasilitator_kokurikuler' : 'guru_mapel']) }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 transition-colors shadow-2xs">
                    <span class="material-icons text-sm">arrow_back</span>
                    <span class="hidden sm:inline">Dasbor</span>
                </a>

                <div class="flex items-center gap-1 bg-white dark:bg-slate-800 px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs">
                    <span class="material-icons text-slate-400 text-xs">calendar_today</span>
                    <input type="date" id="attendance-date" name="date" value="{{ $selectedDate->format('Y-m-d') }}" 
                           class="border-0 bg-transparent text-slate-800 dark:text-white focus:ring-0 text-xs font-bold py-0 px-1 cursor-pointer">
                </div>
            </div>
        </div>
    </x-slot>

    @push('styles')
        <style>
            body > footer, body > .back-to-top-button { display: none !important; }
            footer.mobile-footer { display: block !important; }

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

    <div class="space-y-4 sm:space-y-6 pb-32 sm:pb-12" x-data="{ mobileSection: 'scanner' }">
        
        <!-- Waktu Pembelajaran & Primary Action Switcher -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl {{ $themeBgSoft }} {{ $themeText }} flex items-center justify-center font-bold shrink-0">
                    <span class="material-icons text-xl">{{ $isCocurricular ? 'psychology' : 'schedule' }}</span>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ $isCocurricular ? 'Sesi Kokurikuler' : 'Waktu Pembelajaran' }}
                    </h3>
                    <p class="text-sm font-extrabold text-slate-800 dark:text-slate-100 mt-0.5">
                        {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }} WITA &bull; {{ $selectedDate->translatedFormat('l, d F Y') }}
                    </p>
                </div>
            </div>

            <!-- Action Switcher: Scan QR (Primary) vs Daftar Siswa (Secondary) -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button @click="mobileSection = 'scanner'" 
                        :class="mobileSection === 'scanner' ? '{{ $themeBtn }} text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-extrabold transition-all min-h-[44px] shadow-2xs">
                    <span class="material-icons text-base">qr_code_scanner</span>
                    <span>Scan Kamera</span>
                </button>
                <button @click="mobileSection = 'roster'" 
                        :class="mobileSection === 'roster' ? '{{ $themeBtn }} text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-extrabold transition-all min-h-[44px] shadow-2xs">
                    <span class="material-icons text-base">groups</span>
                    <span>Daftar Siswa ({{ $totalClassStudents }})</span>
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
                                class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-bold rounded-xl transition-all duration-200 text-white {{ $themeBtn }} shadow-sm">
                            <span class="material-icons text-base">qr_code_scanner</span>
                            <span>Scan QR Code</span>
                        </button>
                        <button id="tab-face"
                                class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-bold rounded-xl transition-all duration-200 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
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
                                <div class="w-full h-full relative overflow-hidden rounded-2xl border {{ $isCocurricular ? 'border-indigo-500/20 bg-indigo-500/5' : 'border-sky-500/20 bg-sky-500/5' }}">
                                    <div class="absolute top-0 left-0 w-7 h-7 border-t-3 border-l-3 {{ $isCocurricular ? 'border-indigo-400' : 'border-sky-400' }} rounded-tl-xl"></div>
                                    <div class="absolute top-0 right-0 w-7 h-7 border-t-3 border-r-3 {{ $isCocurricular ? 'border-indigo-400' : 'border-sky-400' }} rounded-tr-xl"></div>
                                    <div class="absolute bottom-0 left-0 w-7 h-7 border-b-3 border-l-3 {{ $isCocurricular ? 'border-indigo-400' : 'border-sky-400' }} rounded-bl-xl"></div>
                                    <div class="absolute bottom-0 right-0 w-7 h-7 border-b-3 border-r-3 {{ $isCocurricular ? 'border-indigo-400' : 'border-sky-400' }} rounded-br-xl"></div>

                                    <!-- Precision Laser Beam -->
                                    <div class="animate-laser h-0.5 bg-gradient-to-r from-transparent {{ $isCocurricular ? 'via-indigo-400 shadow-[0_0_16px_#818cf8,0_0_4px_#6366f1]' : 'via-sky-400 shadow-[0_0_16px_#38bdf8,0_0_4px_#0284c7]' }} to-transparent"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Camera Switcher -->
                        <div id="camera-switch-container" class="mt-3 text-center hidden">
                            <button id="camera-switch-button" type="button"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold {{ $themeText }} {{ $themeBgSoft }} hover:opacity-80 transition-colors shadow-2xs">
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

                <!-- Action Cards Under Camera (Rekap Anekdot & Isi Jurnal Sesi Ini) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Tombol Rekap Anekdot -->
                    <a href="{{ route('teacher.anecdotes.index', ['school_class_id' => $schedule->getTargetClass()?->id, 'subject_id' => $schedule->teachingAssignment?->subject_id]) }}" 
                       class="group p-3.5 sm:p-4 rounded-3xl bg-white dark:bg-slate-900 border border-amber-200/80 dark:border-amber-900/50 hover:border-amber-300 dark:hover:border-amber-700 shadow-2xs hover:shadow-md transition-all flex items-center justify-between gap-3 active:scale-[0.98]">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                                <span class="material-icons text-xl">rate_review</span>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">Rekap Anekdot</h4>
                                <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 truncate">Catatan Sikap & Nilai</p>
                            </div>
                        </div>
                        <span class="material-icons text-slate-400 text-sm group-hover:translate-x-0.5 transition-transform shrink-0">chevron_right</span>
                    </a>

                    <!-- Tombol Isi Jurnal Sesi Ini -->
                    <a href="{{ route('teacher.journals.create', ['schedule_id' => $schedule->id, 'date' => $selectedDate->format('Y-m-d')]) }}" 
                       class="group relative p-3.5 sm:p-4 rounded-3xl bg-white dark:bg-slate-900 border {{ $isCocurricular ? 'border-indigo-200/80 dark:border-indigo-900/50 hover:border-indigo-300' : 'border-sky-200/80 dark:border-sky-900/50 hover:border-sky-300' }} shadow-2xs hover:shadow-md transition-all flex items-center justify-between gap-3 active:scale-[0.98]">
                        <span class="absolute top-3 right-3 inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-extrabold uppercase bg-emerald-500 text-white shadow-2xs">Baru</span>
                        <div class="flex items-center gap-3 min-w-0 pr-4">
                            <div class="w-10 h-10 rounded-2xl {{ $themeBgSoft }} {{ $themeText }} flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                                <span class="material-icons text-xl">edit_note</span>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">Isi Jurnal</h4>
                                <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 truncate">Jurnal Sesi Ini</p>
                            </div>
                        </div>
                        <span class="material-icons text-slate-400 text-sm group-hover:translate-x-0.5 transition-transform shrink-0">chevron_right</span>
                    </a>
                </div>
            </div>

            <!-- Right Column: Live Roster & Quick Actions (7 Cols di Desktop) -->
            <div class="lg:col-span-7 space-y-4" :class="mobileSection === 'roster' ? 'block' : 'hidden lg:block'">

                <!-- Integrated Metric Ribbon with Progress Bar -->
                <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-3">
                    <div class="grid grid-cols-3 gap-2 sm:gap-3 text-center">
                        <div class="p-2.5 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/40">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Hadir</p>
                            <p class="text-base sm:text-xl font-black text-slate-900 dark:text-white mt-0.5">
                                <span id="attended-count">{{ $attendedStudents->count() }}</span> <span class="text-xs font-bold text-slate-400">/ {{ $totalClassStudents }}</span>
                            </p>
                        </div>

                        <div class="p-2.5 rounded-2xl bg-amber-50/60 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900/40">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Izin / Sakit</p>
                            <p class="text-base sm:text-xl font-black text-slate-900 dark:text-white mt-0.5">
                                <span id="leave-count">{{ $studentsOnLeave->count() }}</span> <span class="text-xs font-bold text-slate-400">/ {{ $totalClassStudents }}</span>
                            </p>
                        </div>

                        <div class="p-2.5 rounded-2xl bg-rose-50/60 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900/40">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Belum Hadir</p>
                            <p class="text-base sm:text-xl font-black text-slate-900 dark:text-white mt-0.5">
                                <span id="no-notice-count">{{ $studentsWithoutNotice->count() }}</span> <span class="text-xs font-bold text-slate-400">/ {{ $totalClassStudents }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Progress Bar Kehadiran Kelas -->
                    <div class="space-y-1.5 pt-1">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold text-slate-600 dark:text-slate-400">Tingkat Kehadiran Kelas</span>
                            <span class="font-black text-emerald-600 dark:text-emerald-400" id="attendance-percent-label">{{ $attendancePercentage }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                            <div id="attendance-percent-bar" class="bg-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ $attendancePercentage }}%;"></div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Tabs & Interactive List -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden" x-data="{ listTab: 'unmarked', search: '' }">
                    
                    <!-- List Navigation & Search -->
                    <div class="p-3.5 sm:p-4 border-b border-slate-100 dark:border-slate-800 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                            
                            <!-- Filter Status Tabs -->
                            <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200/60 dark:border-slate-700/60 overflow-x-auto no-scrollbar gap-1">
                                <button @click="listTab = 'unmarked'" 
                                        :class="listTab === 'unmarked' ? 'bg-white dark:bg-slate-700 text-rose-600 dark:text-white shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                                        class="px-2.5 py-1.5 text-xs rounded-lg transition-all whitespace-nowrap">
                                    Belum Absen (<span id="tab-count-unmarked">{{ $studentsWithoutNotice->count() }}</span>)
                                </button>
                                <button @click="listTab = 'attended'" 
                                        :class="listTab === 'attended' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-white shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                                        class="px-2.5 py-1.5 text-xs rounded-lg transition-all whitespace-nowrap">
                                    Hadir (<span id="tab-count-attended">{{ $attendedStudents->count() }}</span>)
                                </button>
                                <button @click="listTab = 'izin'" 
                                        :class="listTab === 'izin' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-white shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                                        class="px-2.5 py-1.5 text-xs rounded-lg transition-all whitespace-nowrap">
                                    Izin (<span id="tab-count-izin">{{ $studentsIzin->count() }}</span>)
                                </button>
                                <button @click="listTab = 'sakit'" 
                                        :class="listTab === 'sakit' ? 'bg-white dark:bg-slate-700 text-purple-600 dark:text-white shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                                        class="px-2.5 py-1.5 text-xs rounded-lg transition-all whitespace-nowrap">
                                    Sakit (<span id="tab-count-sakit">{{ $studentsSakit->count() }}</span>)
                                </button>
                                <button @click="listTab = 'alpa'" 
                                        :class="listTab === 'alpa' ? 'bg-white dark:bg-slate-700 text-orange-600 dark:text-white shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'"
                                        class="px-2.5 py-1.5 text-xs rounded-lg transition-all whitespace-nowrap">
                                    Alpa/Bolos (<span id="tab-count-alpa">{{ $studentsAlpa->count() + $studentsBolos->count() }}</span>)
                                </button>
                            </div>

                            <!-- Search Input (Nama / NIS) -->
                            <div class="relative w-full sm:w-56">
                                <span class="material-icons absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">search</span>
                                <input type="text" x-model="search" placeholder="Cari nama / NIS siswa..." 
                                       class="w-full text-xs py-2 pl-8 pr-3 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                            </div>
                        </div>
                    </div>

                    <!-- 1. TAB: BELUM ABSEN (Daftar Siswa dengan Tombol Sentuh 1-Klik Cepat & Lapang) -->
                    <div x-show="listTab === 'unmarked'" class="max-h-[500px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 no-scrollbar p-2 sm:p-3 space-y-2.5" id="no-notice-list">
                        @forelse($studentsWithoutNotice as $student)
                            <div class="p-3 sm:p-3.5 rounded-2xl bg-white dark:bg-slate-850 border border-slate-200/80 dark:border-slate-750 shadow-2xs space-y-2.5 student-item transition-all hover:border-sky-300 dark:hover:border-sky-700" 
                                 id="student-no-notice-{{ $student->id }}"
                                 x-show="search === '' || {{ json_encode(mb_strtolower($student->name)) }}.includes(search.toLowerCase()) || {{ json_encode($student->nis ?? '') }}.includes(search)">
                                
                                <!-- Baris Atas: Foto, Nama, NIS & Tombol Anekdot -->
                                <div class="flex items-center justify-between gap-2.5">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <img class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shrink-0 shadow-2xs"
                                             src="{{ $student->photo_url }}" 
                                             alt="{{ $student->name }}"
                                             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=0284c7&background=e0f2fe'">
                                        <div class="min-w-0">
                                            <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 truncate leading-tight">{{ $student->name }}</p>
                                            <p class="text-[11px] text-slate-400 mt-0.5">NIS: {{ $student->nis ?? '-' }}</p>
                                        </div>
                                    </div>

                                    <!-- Tombol Catatan Anekdot Sikap -->
                                    <button type="button" 
                                            onclick="openAnecdoteModal({{ $student->id }}, '{{ addslashes($student->name) }}', '{{ $student->photo_url }}', '{{ $student->nis ?? '-' }}')"
                                            class="anecdote-btn w-9 h-9 rounded-xl flex items-center justify-center transition-all active:scale-90 relative shrink-0 {{ isset($anecdotesToday[$student->id]) && $anecdotesToday[$student->id]->hasAnyNote() ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300 dark:border-amber-700 shadow-2xs' : 'bg-slate-100 text-slate-500 hover:bg-amber-50 hover:text-amber-600 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-amber-950/40 border border-slate-200 dark:border-slate-700' }}" 
                                            title="Catatan Anekdot (Akademik, Kehadiran, Sikap)" 
                                            id="anecdote-badge-{{ $student->id }}">
                                        <span class="material-icons text-base">rate_review</span>
                                        @if(isset($anecdotesToday[$student->id]) && $anecdotesToday[$student->id]->hasAnyNote())
                                            <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-amber-500 rounded-full border-2 border-white dark:border-slate-900"></span>
                                        @endif
                                    </button>
                                </div>

                                <!-- Baris Bawah: 5 Tombol Status 1-Klik Penuh dengan Area Sentuh Nyaman (≥42px) -->
                                <div class="grid grid-cols-5 gap-1.5 pt-1 border-t border-slate-100 dark:border-slate-800">
                                    <button data-student-id="{{ $student->id }}" data-status="hadir"
                                            class="manual-mark-btn min-h-[42px] rounded-xl font-black text-xs bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white border border-emerald-200/80 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800 transition-all active:scale-95 shadow-2xs flex items-center justify-center" 
                                            title="Tandai Hadir">
                                        <span>H</span>
                                    </button>
                                    <button data-student-id="{{ $student->id }}" data-status="sakit"
                                            class="manual-mark-btn min-h-[42px] rounded-xl font-black text-xs bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white border border-purple-200/80 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800 transition-all active:scale-95 shadow-2xs flex items-center justify-center" 
                                            title="Tandai Sakit">
                                        <span>S</span>
                                    </button>
                                    <button data-student-id="{{ $student->id }}" data-status="izin"
                                            class="manual-mark-btn min-h-[42px] rounded-xl font-black text-xs bg-sky-50 text-sky-700 hover:bg-sky-600 hover:text-white border border-sky-200/80 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800 transition-all active:scale-95 shadow-2xs flex items-center justify-center" 
                                            title="Tandai Izin">
                                        <span>I</span>
                                    </button>
                                    <button data-student-id="{{ $student->id }}" data-status="alpa"
                                            class="manual-mark-btn min-h-[42px] rounded-xl font-black text-xs bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white border border-rose-200/80 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800 transition-all active:scale-95 shadow-2xs flex items-center justify-center" 
                                            title="Tandai Alpa">
                                        <span>A</span>
                                    </button>
                                    <button data-student-id="{{ $student->id }}" data-status="bolos"
                                            class="manual-mark-btn min-h-[42px] rounded-xl font-black text-xs bg-orange-50 text-orange-700 hover:bg-orange-600 hover:text-white border border-orange-200/80 dark:bg-orange-950/60 dark:text-orange-300 dark:border-orange-800 transition-all active:scale-95 shadow-2xs flex items-center justify-center" 
                                            title="Tandai Bolos">
                                        <span>B</span>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div id="no-missing-students" class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 italic">
                                Semua siswa di kelas ini telah memiliki data absensi hari ini.
                            </div>
                        @endforelse
                    </div>

                    <!-- 2. TAB: SUDAH HADIR -->
                    <div x-show="listTab === 'attended'" class="max-h-[500px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 no-scrollbar" id="attended-list" style="display: none;">
                        @forelse($attendedStudents as $attendance)
                            <div class="p-3 sm:p-3.5 flex items-center justify-between gap-3 hover:bg-slate-50/70 dark:hover:bg-slate-850/50 transition-colors student-attended-row-{{ $attendance->student->id }}"
                                 x-show="search === '' || {{ json_encode(mb_strtolower($attendance->student->name)) }}.includes(search.toLowerCase()) || {{ json_encode($attendance->student->nis ?? '') }}.includes(search)">
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
                                            class="anecdote-btn w-8 h-8 rounded-xl flex items-center justify-center transition-all active:scale-90 relative {{ isset($anecdotesToday[$attendance->student->id]) && $anecdotesToday[$attendance->student->id]->hasAnyNote() ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300 dark:border-amber-700 shadow-2xs' : 'bg-slate-100 text-slate-500 hover:bg-amber-50 hover:text-amber-600 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-amber-950/40 border border-slate-200 dark:border-slate-700' }}" 
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

                    <!-- 3. TAB: SISWA IZIN -->
                    <div x-show="listTab === 'izin'" class="max-h-[500px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 no-scrollbar" id="izin-list" style="display: none;">
                        @forelse($studentsIzin as $subjectAttendance)
                            <div class="p-3 sm:p-3.5 flex items-center justify-between gap-3 hover:bg-slate-50/70 dark:hover:bg-slate-850/50 transition-colors student-izin-row-{{ $subjectAttendance->student->id }}"
                                 x-show="search === '' || {{ json_encode(mb_strtolower($subjectAttendance->student->name)) }}.includes(search.toLowerCase()) || {{ json_encode($subjectAttendance->student->nis ?? '') }}.includes(search)">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <img class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl object-cover border-2 border-sky-500 shadow-2xs shrink-0"
                                         src="{{ $subjectAttendance->student->photo_url }}" 
                                         alt="{{ $subjectAttendance->student->name }}"
                                         onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($subjectAttendance->student->name) }}&color=0284c7&background=e0f2fe'">
                                    <div class="truncate">
                                        <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 truncate leading-tight">{{ $subjectAttendance->student->name }}</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">NIS: {{ $subjectAttendance->student->nis ?? '-' }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                    Izin
                                </span>
                            </div>
                        @empty
                            <div class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 italic">
                                Tidak ada siswa yang berstatus izin hari ini.
                            </div>
                        @endforelse
                    </div>

                    <!-- 4. TAB: SISWA SAKIT -->
                    <div x-show="listTab === 'sakit'" class="max-h-[500px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 no-scrollbar" id="sakit-list" style="display: none;">
                        @forelse($studentsSakit as $subjectAttendance)
                            <div class="p-3 sm:p-3.5 flex items-center justify-between gap-3 hover:bg-slate-50/70 dark:hover:bg-slate-850/50 transition-colors student-sakit-row-{{ $subjectAttendance->student->id }}"
                                 x-show="search === '' || {{ json_encode(mb_strtolower($subjectAttendance->student->name)) }}.includes(search.toLowerCase()) || {{ json_encode($subjectAttendance->student->nis ?? '') }}.includes(search)">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <img class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl object-cover border-2 border-purple-500 shadow-2xs shrink-0"
                                         src="{{ $subjectAttendance->student->photo_url }}" 
                                         alt="{{ $subjectAttendance->student->name }}"
                                         onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($subjectAttendance->student->name) }}&color=0284c7&background=e0f2fe'">
                                    <div class="truncate">
                                        <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 truncate leading-tight">{{ $subjectAttendance->student->name }}</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">NIS: {{ $subjectAttendance->student->nis ?? '-' }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                    Sakit
                                </span>
                            </div>
                        @empty
                            <div class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 italic">
                                Tidak ada siswa yang berstatus sakit hari ini.
                            </div>
                        @endforelse
                    </div>

                    <!-- 5. TAB: SISWA ALPA / BOLOS -->
                    <div x-show="listTab === 'alpa'" class="max-h-[500px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 no-scrollbar" id="alpa-list" style="display: none;">
                        @php
                            $alpaBolosStudents = $studentsAlpa->concat($studentsBolos);
                        @endphp
                        @forelse($alpaBolosStudents as $subjectAttendance)
                            <div class="p-3 sm:p-3.5 flex items-center justify-between gap-3 hover:bg-slate-50/70 dark:hover:bg-slate-850/50 transition-colors"
                                 x-show="search === '' || {{ json_encode(mb_strtolower($subjectAttendance->student->name)) }}.includes(search.toLowerCase()) || {{ json_encode($subjectAttendance->student->nis ?? '') }}.includes(search)">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <img class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl object-cover border-2 border-rose-500 shadow-2xs shrink-0"
                                         src="{{ $subjectAttendance->student->photo_url }}" 
                                         alt="{{ $subjectAttendance->student->name }}"
                                         onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($subjectAttendance->student->name) }}&color=0284c7&background=e0f2fe'">
                                    <div class="truncate">
                                        <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 truncate leading-tight">{{ $subjectAttendance->student->name }}</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">NIS: {{ $subjectAttendance->student->nis ?? '-' }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase
                                    @if($subjectAttendance->status === 'alpa') bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-200 dark:border-rose-800 @else bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300 border border-orange-200 dark:border-orange-800 @endif">
                                    {{ ucfirst($subjectAttendance->status) }}
                                </span>
                            </div>
                        @empty
                            <div class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 italic">
                                Tidak ada siswa berstatus alpa atau bolos.
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
                                      placeholder="Contoh: Memahami materi dengan cepat, aktif menjawab pertanyaan..." 
                                      class="w-full text-xs p-3 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"></textarea>
                        </div>
                    </div>

                    <!-- 2. TAB: KEHADIRAN -->
                    <div x-show="anTab === 'attendance'" class="space-y-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <label class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <span class="material-icons text-sm text-sky-500">fact_check</span>
                                <span>Sentimen Ketertiban Kehadiran</span>
                            </label>
                            <span class="text-[10px] text-slate-400">Pilih salah satu</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="attendance_sentiment" value="positive" class="sr-only peer">
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-emerald-50 peer-checked:border-emerald-400 peer-checked:text-emerald-700 dark:peer-checked:bg-emerald-950/60 dark:peer-checked:text-emerald-300 dark:peer-checked:border-emerald-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">⏰</span>
                                    <span class="text-[11px] font-bold block mt-1">Tepat Waktu</span>
                                </div>
                            </label>
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="attendance_sentiment" value="neutral" class="sr-only peer" checked>
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-sky-50 peer-checked:border-sky-400 peer-checked:text-sky-700 dark:peer-checked:bg-sky-950/60 dark:peer-checked:text-sky-300 dark:peer-checked:border-sky-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">📋</span>
                                    <span class="text-[11px] font-bold block mt-1">Normal / Izin Sah</span>
                                </div>
                            </label>
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="attendance_sentiment" value="needs_guidance" class="sr-only peer">
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-rose-50 peer-checked:border-rose-400 peer-checked:text-rose-700 dark:peer-checked:bg-rose-950/60 dark:peer-checked:text-rose-300 dark:peer-checked:border-rose-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">⚠️</span>
                                    <span class="text-[11px] font-bold block mt-1">Terlambat / Bolos</span>
                                </div>
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Catatan Pengamatan Kehadiran:
                            </label>
                            <textarea id="anecdote-attendance-note" rows="3" 
                                      placeholder="Contoh: Datang terlambat 15 menit karena kendaraan rusak..." 
                                      class="w-full text-xs p-3 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all placeholder:text-slate-400"></textarea>
                        </div>
                    </div>

                    <!-- 3. TAB: SIKAP & KARAKTER -->
                    <div x-show="anTab === 'attitude'" class="space-y-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <label class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <span class="material-icons text-sm text-emerald-500">psychology</span>
                                <span>Sentimen Karakter / Sikap</span>
                            </label>
                            <span class="text-[10px] text-slate-400">Pilih salah satu</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="attitude_sentiment" value="positive" class="sr-only peer">
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-emerald-50 peer-checked:border-emerald-400 peer-checked:text-emerald-700 dark:peer-checked:bg-emerald-950/60 dark:peer-checked:text-emerald-300 dark:peer-checked:border-emerald-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">👏</span>
                                    <span class="text-[11px] font-bold block mt-1">Sangat Terpuji</span>
                                </div>
                            </label>
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="attitude_sentiment" value="neutral" class="sr-only peer" checked>
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-sky-50 peer-checked:border-sky-400 peer-checked:text-sky-700 dark:peer-checked:bg-sky-950/60 dark:peer-checked:text-sky-300 dark:peer-checked:border-sky-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">👍</span>
                                    <span class="text-[11px] font-bold block mt-1">Baik / Tertib</span>
                                </div>
                            </label>
                            <label class="sentiment-option cursor-pointer">
                                <input type="radio" name="attitude_sentiment" value="needs_guidance" class="sr-only peer">
                                <div class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-center peer-checked:bg-rose-50 peer-checked:border-rose-400 peer-checked:text-rose-700 dark:peer-checked:bg-rose-950/60 dark:peer-checked:text-rose-300 dark:peer-checked:border-rose-700 transition-all hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <span class="text-sm block">🚨</span>
                                    <span class="text-[11px] font-bold block mt-1">Perlu Pembinaan</span>
                                </div>
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Catatan Pengamatan Sikap:
                            </label>
                            <textarea id="anecdote-attitude-note" rows="3" 
                                      placeholder="Contoh: Sangat kooperatif dalam kerja tim, membantu teman yang kesulitan..." 
                                      class="w-full text-xs p-3 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all placeholder:text-slate-400"></textarea>
                        </div>
                    </div>

                    <!-- Tindak Lanjut & Visibilitas Orang Tua -->
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Rekomendasi Tindak Lanjut:
                            </label>
                            <input type="text" id="anecdote-follow-up" 
                                   placeholder="Contoh: Diberikan apresiasi di depan kelas / koordinasi dengan wali kelas..." 
                                   class="w-full text-xs px-3 py-2 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
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
                const totalStudentsCount = {{ $totalClassStudents }};

                // Face Recognition Variables
                const studentsWithPhotos = @json($studentsForFaceRecognition);
                let faceMatcher = null;
                let isModelsLoaded = false;
                let faceScanInterval = null;
                let currentMode = 'qr';
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
                const izinList = document.getElementById('izin-list');
                const sakitList = document.getElementById('sakit-list');
                const alpaList = document.getElementById('alpa-list');

                const tabCountUnmarked = document.getElementById('tab-count-unmarked');
                const tabCountAttended = document.getElementById('tab-count-attended');
                const tabCountIzin = document.getElementById('tab-count-izin');
                const tabCountSakit = document.getElementById('tab-count-sakit');
                const tabCountAlpa = document.getElementById('tab-count-alpa');

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

                function updateProgress() {
                    const currentAttended = parseInt(attendedCount ? attendedCount.textContent : 0) || 0;
                    const pct = totalStudentsCount > 0 ? Math.round((currentAttended / totalStudentsCount) * 100) : 0;
                    const bar = document.getElementById('attendance-percent-bar');
                    const label = document.getElementById('attendance-percent-label');
                    if (bar) bar.style.width = `${pct}%`;
                    if (label) label.textContent = `${pct}%`;
                }

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
                        tabQr.className = 'flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-bold rounded-xl transition-all duration-200 text-white bg-sky-600 shadow-sm';
                        tabFace.className = 'flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-bold rounded-xl transition-all duration-200 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white';

                        if (faceScannerContainer) faceScannerContainer.classList.add('hidden');
                        if (qrScannerContainer) qrScannerContainer.classList.remove('hidden');

                        stopFaceScanner();
                        startQrScanner();
                    } else {
                        tabFace.className = 'flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-bold rounded-xl transition-all duration-200 text-white bg-sky-600 shadow-sm';
                        tabQr.className = 'flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-bold rounded-xl transition-all duration-200 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white';

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
                        if (text) text.textContent = `Memuat AI: 100%`;

                        isModelsLoaded = true;
                        setTimeout(() => {
                            if (overlay) overlay.classList.add('hidden');
                        }, 300);
                        return true;
                    } catch (error) {
                        if (overlay) overlay.classList.add('hidden');
                        console.error('Error loading face-api models:', error);
                        if (faceStatus) faceStatus.textContent = 'Gagal memuat model AI.';
                        return false;
                    }
                }

                async function buildFaceMatcher() {
                    const labeledDescriptors = [];
                    for (const student of studentsWithPhotos) {
                        if (student.face_descriptor) {
                            try {
                                const descArray = JSON.parse(student.face_descriptor);
                                const floatArray = new Float32Array(descArray);
                                labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(student.unique_id, [floatArray]));
                                continue;
                            } catch (e) {
                                console.warn('Gagal parse descriptor untuk ' + student.name, e);
                            }
                        }

                        try {
                            const img = await faceapi.fetchImage(student.photo_url);
                            const detection = await faceapi.detectSingleFace(img).withFaceLandmarks().withFaceDescriptor();
                            if (detection) {
                                labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(student.unique_id, [detection.descriptor]));
                            }
                        } catch (err) {
                            console.warn(`Gagal memproses wajah untuk ${student.name}`);
                        }
                    }

                    if (labeledDescriptors.length > 0) {
                        faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.55);
                        return true;
                    }
                    return false;
                }

                async function startFaceScanner() {
                    const loaded = await loadFaceModels();
                    if (!loaded) return;

                    if (!faceMatcher) {
                        if (faceStatus) faceStatus.textContent = 'Menyiapkan database wajah kelas...';
                        const built = await buildFaceMatcher();
                        if (!built) {
                            if (faceStatus) faceStatus.textContent = 'Tidak ada data foto siswa yang valid.';
                            return;
                        }
                    }

                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: currentFacingMode }
                        });
                        faceVideo.srcObject = stream;
                        if (faceStatus) faceStatus.textContent = 'Arahkan wajah siswa ke dalam lingkaran.';

                        faceVideo.onplay = () => {
                            const displaySize = { width: faceVideo.videoWidth, height: faceVideo.videoHeight };
                            faceapi.matchDimensions(faceCanvas, displaySize);

                            faceScanInterval = setInterval(async () => {
                                if (currentMode !== 'face') return;
                                const now = Date.now();
                                if (now - lastScanTime < scanCooldown) return;

                                const detections = await faceapi.detectAllFaces(faceVideo).withFaceLandmarks().withFaceDescriptors();
                                const resizedDetections = faceapi.resizeResults(detections, displaySize);

                                const ctx = faceCanvas.getContext('2d');
                                ctx.clearRect(0, 0, faceCanvas.width, faceCanvas.height);

                                if (detections.length > 0) {
                                    const bestMatch = faceMatcher.findBestMatch(detections[0].descriptor);
                                    if (bestMatch.label !== 'unknown') {
                                        consecutiveMatches++;
                                        const box = resizedDetections[0].detection.box;
                                        const drawBox = new faceapi.draw.DrawBox(box, { 
                                            label: `Terdeteksi (${consecutiveMatches}/2)`,
                                            boxColor: '#10B981'
                                        });
                                        drawBox.draw(faceCanvas);

                                        if (consecutiveMatches >= 2) {
                                            lastScanTime = Date.now();
                                            consecutiveMatches = 0;
                                            onScanSuccess(bestMatch.label);
                                        }
                                    } else {
                                        consecutiveMatches = 0;
                                    }
                                } else {
                                    consecutiveMatches = 0;
                                }
                            }, 500);
                        };
                    } catch (err) {
                        console.error('Gagal mengakses kamera depan/belakang:', err);
                        if (faceStatus) faceStatus.textContent = 'Izin kamera ditolak atau tidak tersedia.';
                    }
                }

                function stopFaceScanner() {
                    if (faceScanInterval) clearInterval(faceScanInterval);
                    if (faceVideo && faceVideo.srcObject) {
                        faceVideo.srcObject.getTracks().forEach(track => track.stop());
                        faceVideo.srcObject = null;
                    }
                    if (faceCanvas) {
                        const ctx = faceCanvas.getContext('2d');
                        ctx.clearRect(0, 0, faceCanvas.width, faceCanvas.height);
                    }
                }

                if (faceSwitchButton) {
                    faceSwitchButton.addEventListener('click', () => {
                        currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
                        stopFaceScanner();
                        startFaceScanner();
                    });
                }

                // === QR CODE SCANNER ===
                function startQrScanner() {
                    Html5Qrcode.getCameras().then(devices => {
                        if (devices && devices.length) {
                            cameras = devices;
                            let backCamera = devices.find(c => c.label.toLowerCase().includes('back') || c.label.toLowerCase().includes('belakang') || c.label.toLowerCase().includes('environment'));
                            currentCameraIndex = backCamera ? devices.indexOf(backCamera) : 0;
                            startScannerWithCamera(devices[currentCameraIndex].id);

                            if (devices.length > 1 && switchContainer) {
                                switchContainer.classList.remove('hidden');
                            }
                        } else {
                            if (readerError) {
                                readerError.textContent = "Kamera tidak ditemukan pada perangkat ini.";
                                readerError.classList.remove('hidden');
                            }
                        }
                    }).catch(err => {
                        if (readerError) {
                            readerError.textContent = "Gagal mengakses kamera. Berikan izin di browser.";
                            readerError.classList.remove('hidden');
                        }
                    });
                }

                function stopQrScanner() {
                    if (html5QrCode && html5QrCode.isScanning) {
                        html5QrCode.stop().catch(err => console.error("Error stopping QR:", err));
                    }
                }

                function playSound(isSuccess) {
                    try {
                        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = audioCtx.createOscillator();
                        const gain = audioCtx.createGain();
                        osc.connect(gain);
                        gain.connect(audioCtx.destination);

                        if (isSuccess) {
                            osc.frequency.setValueAtTime(587.33, audioCtx.currentTime);
                            osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.1);
                            gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.25);
                            osc.start(audioCtx.currentTime);
                            osc.stop(audioCtx.currentTime + 0.25);
                        } else {
                            osc.frequency.setValueAtTime(220, audioCtx.currentTime);
                            osc.frequency.setValueAtTime(164.81, audioCtx.currentTime + 0.15);
                            gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
                            osc.start(audioCtx.currentTime);
                            osc.stop(audioCtx.currentTime + 0.35);
                        }
                    } catch (e) { }
                }

                function onScanSuccess(decodedText) {
                    const now = Date.now();
                    if (now - lastScanTime < scanCooldown) return;
                    lastScanTime = now;

                    if (currentMode === 'qr' && html5QrCode && html5QrCode.isScanning) {
                        html5QrCode.pause();
                    }

                    recordAttendance(decodedText);
                }

                function recordAttendance(studentId) {
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
                                const nextCount = Math.max(0, parseInt(noNoticeCount.textContent) - 1);
                                noNoticeCount.textContent = nextCount;
                                if (tabCountUnmarked) tabCountUnmarked.textContent = nextCount;
                            }
                            updateProgress();
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
                        const nextAttended = parseInt(attendedCount.textContent) + 1;
                        attendedCount.textContent = nextAttended;
                        if (tabCountAttended) tabCountAttended.textContent = nextAttended;
                    }
                    updateProgress();
                }

                function addStudentToLeaveList(student, status) {
                    if (leaveCount) {
                        leaveCount.textContent = parseInt(leaveCount.textContent) + 1;
                    }
                    if (status === 'izin' && tabCountIzin) {
                        tabCountIzin.textContent = parseInt(tabCountIzin.textContent) + 1;
                    } else if (status === 'sakit' && tabCountSakit) {
                        tabCountSakit.textContent = parseInt(tabCountSakit.textContent) + 1;
                    } else if ((status === 'alpa' || status === 'bolos') && tabCountAlpa) {
                        tabCountAlpa.textContent = parseInt(tabCountAlpa.textContent) + 1;
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
                            if (data.student.status === 'sakit' || data.student.status === 'izin' || data.student.status === 'alpa' || data.student.status === 'bolos') {
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

                    modal.classList.remove('opacity-0', 'pointer-events-none');
                    dialog.classList.remove('scale-95');

                    // Fetch existing anecdote if any
                    const dateVal = document.getElementById('attendance-date')?.value || '{{ $selectedDate->format('Y-m-d') }}';
                    fetch(`{{ route('teacher.anecdotes.show_json') }}?student_id=${studentId}&schedule_id=${scheduleId}&date=${dateVal}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success && res.data) {
                            const d = res.data;
                            if (d.academic_sentiment) {
                                const r = document.querySelector(`input[name="academic_sentiment"][value="${d.academic_sentiment}"]`);
                                if (r) r.checked = true;
                            }
                            if (d.attendance_sentiment) {
                                const r = document.querySelector(`input[name="attendance_sentiment"][value="${d.attendance_sentiment}"]`);
                                if (r) r.checked = true;
                            }
                            if (d.attitude_sentiment) {
                                const r = document.querySelector(`input[name="attitude_sentiment"][value="${d.attitude_sentiment}"]`);
                                if (r) r.checked = true;
                            }
                            document.getElementById('anecdote-academic-note').value = d.academic_notes || '';
                            document.getElementById('anecdote-attendance-note').value = d.attendance_notes || '';
                            document.getElementById('anecdote-attitude-note').value = d.attitude_notes || '';
                            document.getElementById('anecdote-follow-up').value = d.follow_up || '';
                            document.getElementById('anecdote-visible-to-parents').checked = !!d.is_visible_to_parents;
                            if (statusText) statusText.textContent = 'Catatan tersimpan ditemukan.';
                        } else {
                            if (statusText) statusText.textContent = 'Belum ada catatan hari ini.';
                        }
                    })
                    .catch(() => {
                        if (statusText) statusText.textContent = 'Gagal memuat riwayat catatan.';
                    });
                };

                window.closeAnecdoteModal = function() {
                    const modal = document.getElementById('anecdote-modal');
                    const dialog = document.getElementById('anecdote-modal-dialog');
                    if (modal) modal.classList.add('opacity-0', 'pointer-events-none');
                    if (dialog) dialog.classList.add('scale-95');
                };

                window.saveAnecdote = function() {
                    if (!currentAnecdoteStudentId) return;

                    const saveBtn = document.getElementById('save-anecdote-btn');
                    const statusText = document.getElementById('anecdote-status-text');
                    const dateVal = document.getElementById('attendance-date')?.value || '{{ $selectedDate->format('Y-m-d') }}';

                    const academicSentiment = document.querySelector('input[name="academic_sentiment"]:checked')?.value || 'neutral';
                    const attendanceSentiment = document.querySelector('input[name="attendance_sentiment"]:checked')?.value || 'neutral';
                    const attitudeSentiment = document.querySelector('input[name="attitude_sentiment"]:checked')?.value || 'neutral';

                    const academicNotes = document.getElementById('anecdote-academic-note').value;
                    const attendanceNotes = document.getElementById('anecdote-attendance-note').value;
                    const attitudeNotes = document.getElementById('anecdote-attitude-note').value;
                    const followUp = document.getElementById('anecdote-follow-up').value;
                    const isVisible = document.getElementById('anecdote-visible-to-parents').checked ? 1 : 0;

                    if (saveBtn) {
                        saveBtn.disabled = true;
                        saveBtn.innerHTML = '<span class="material-icons text-sm animate-spin">sync</span><span>Menyimpan...</span>';
                    }

                    fetch("{{ route('teacher.anecdotes.store_json') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            student_id: currentAnecdoteStudentId,
                            schedule_id: scheduleId,
                            date: dateVal,
                            academic_sentiment: academicSentiment,
                            attendance_sentiment: attendanceSentiment,
                            attitude_sentiment: attitudeSentiment,
                            academic_notes: academicNotes,
                            attendance_notes: attendanceNotes,
                            attitude_notes: attitudeNotes,
                            follow_up: followUp,
                            is_visible_to_parents: isVisible
                        })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (saveBtn) {
                            saveBtn.disabled = false;
                            saveBtn.innerHTML = '<span class="material-icons text-sm">save</span><span>Simpan Catatan</span>';
                        }
                        if (res.success) {
                            if (statusText) statusText.innerHTML = '<span class="text-emerald-600 font-bold">✓ Catatan berhasil disimpan!</span>';
                            
                            // Update badge indicator
                            const badge = document.getElementById(`anecdote-badge-${currentAnecdoteStudentId}`) || document.getElementById(`anecdote-badge-attended-${currentAnecdoteStudentId}`);
                            if (badge) {
                                badge.className = 'anecdote-btn w-9 h-9 rounded-xl flex items-center justify-center transition-all active:scale-90 relative bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300 dark:border-amber-700 shadow-2xs';
                                if (!badge.querySelector('.bg-amber-500')) {
                                    const dot = document.createElement('span');
                                    dot.className = 'absolute -top-1 -right-1 w-2.5 h-2.5 bg-amber-500 rounded-full border-2 border-white dark:border-slate-900';
                                    badge.appendChild(dot);
                                }
                            }

                            setTimeout(() => {
                                closeAnecdoteModal();
                            }, 800);
                        } else {
                            if (statusText) statusText.innerHTML = `<span class="text-rose-600 font-bold">${res.message || 'Gagal menyimpan'}</span>`;
                        }
                    })
                    .catch(() => {
                        if (saveBtn) {
                            saveBtn.disabled = false;
                            saveBtn.innerHTML = '<span class="material-icons text-sm">save</span><span>Simpan Catatan</span>';
                        }
                        if (statusText) statusText.innerHTML = '<span class="text-rose-600 font-bold">Kesalahan jaringan.</span>';
                    });
                };

            });
        </script>
    @endpush
</x-app-layout>