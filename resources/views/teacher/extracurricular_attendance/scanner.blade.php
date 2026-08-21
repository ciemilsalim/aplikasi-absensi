<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard', ['view' => 'pembina_ekskul'])],
                    ['title' => 'Presensi ' . $extracurricular->name, 'url' => '#']
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Presensi Ekstrakurikuler
                    </h1>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-2xs">
                        {{ $extracurricular->name }}
                    </span>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shadow-2xs">
                        {{ $students->count() }} Anggota
                    </span>
                </div>
            </div>

            <!-- Date Filter & Back Controls -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <a href="{{ route('teacher.dashboard', ['view' => 'pembina_ekskul']) }}" 
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
        
        <!-- Mobile View Switcher (< lg) -->
        <div class="lg:hidden flex p-1 bg-slate-100 dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700">
            <button @click="mobileSection = 'scanner'" 
                    :class="mobileSection === 'scanner' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                    class="flex-1 py-2 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all">
                <span class="material-icons text-base">qr_code_scanner</span>
                <span>Kamera Scanner</span>
            </button>
            <button @click="mobileSection = 'students'" 
                    :class="mobileSection === 'students' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                    class="flex-1 py-2 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all">
                <span class="material-icons text-base">people</span>
                <span>Daftar Anggota</span>
            </button>
        </div>

        <!-- Layout Utama: Scanner & Student Attendance List -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6">
            
            <!-- Kolom Kiri: Scanner Area (5 cols) -->
            <div class="lg:col-span-5 space-y-4" :class="mobileSection === 'scanner' ? 'block' : 'hidden lg:block'">
                
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden" 
                     x-data="{
                         mode: 'qr',
                         statusMessage: '',
                         statusType: '',
                         setStatus(msg, type) {
                             this.statusMessage = msg;
                             this.statusType = type;
                             if (type === 'success') {
                                 setTimeout(() => { this.statusMessage = ''; }, 4000);
                             }
                         }
                     }">
                    
                    <!-- Scanner Mode Switcher Header -->
                    <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-850 p-1 rounded-2xl border border-slate-200/60 dark:border-slate-700/60">
                            <button @click="mode = 'qr'; window.switchScannerMode('qr')" 
                                    :class="mode === 'qr' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-300 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 font-medium hover:text-slate-900 dark:hover:text-white'"
                                    class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all">
                                <span class="material-icons text-base">qr_code</span>
                                <span>QR Code</span>
                            </button>
                            <button @click="mode = 'face'; window.switchScannerMode('face')" 
                                    :class="mode === 'face' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-300 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 font-medium hover:text-slate-900 dark:hover:text-white'"
                                    class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all">
                                <span class="material-icons text-base">face</span>
                                <span>Face AI</span>
                            </button>
                        </div>

                        <!-- Manual Checklist Form Link -->
                        <a href="{{ route('teacher.extracurricular-attendance.create', $extracurricular) }}" 
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors">
                            <span class="material-icons text-xs">checklist</span>
                            <span>Manual</span>
                        </a>
                    </div>

                    <!-- Live Viewport Container -->
                    <div class="p-4 sm:p-5">
                        
                        <!-- QR Code Scanner Box -->
                        <div x-show="mode === 'qr'" class="relative aspect-square max-w-[360px] mx-auto rounded-3xl overflow-hidden bg-slate-950 flex items-center justify-center border border-slate-800 shadow-inner">
                            <div id="reader" class="w-full h-full"></div>
                            
                            <!-- Scanner Precision Overlay Graphics -->
                            <div class="absolute inset-0 pointer-events-none flex items-center justify-center p-4">
                                <div class="w-60 h-60 sm:w-64 sm:h-64 relative overflow-hidden rounded-3xl border border-amber-500/20 bg-amber-500/5">
                                    <!-- 4 Precision Corner HUD Brackets -->
                                    <div class="absolute top-0 left-0 w-7 h-7 border-t-3 border-l-3 border-amber-400 rounded-tl-2xl"></div>
                                    <div class="absolute top-0 right-0 w-7 h-7 border-t-3 border-r-3 border-amber-400 rounded-tr-2xl"></div>
                                    <div class="absolute bottom-0 left-0 w-7 h-7 border-b-3 border-l-3 border-amber-400 rounded-bl-2xl"></div>
                                    <div class="absolute bottom-0 right-0 w-7 h-7 border-b-3 border-r-3 border-amber-400 rounded-br-2xl"></div>

                                    <!-- Precision Laser Beam with Glow -->
                                    <div class="animate-laser h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent shadow-[0_0_18px_#fbbf24,0_0_6px_#f59e0b]"></div>
                                    
                                    <!-- Status Pill at Bottom of Target Box -->
                                    <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between text-[10px] text-amber-300 font-bold bg-slate-950/75 px-3 py-1.5 rounded-xl backdrop-blur-md border border-amber-400/20 shadow-md">
                                        <span class="flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping"></span>
                                            <span>Posisikan QR di dalam kotak</span>
                                        </span>
                                        <span class="material-icons text-xs">center_focus_strong</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Face AI Scanner Box -->
                        <div x-show="mode === 'face'" style="display: none;" class="relative aspect-square max-w-[360px] mx-auto rounded-3xl overflow-hidden bg-slate-950 flex items-center justify-center border border-slate-800 shadow-inner">
                            <video id="face-video" autoplay muted playsinline class="w-full h-full object-cover"></video>
                            <canvas id="face-canvas" class="absolute inset-0 w-full h-full pointer-events-none"></canvas>
                            
                            <div class="absolute top-3 right-3 z-20">
                                <button type="button" id="face-switch-camera" 
                                        class="p-2 rounded-xl bg-slate-900/80 hover:bg-slate-900 text-white backdrop-blur-xs border border-white/10 transition-transform active:scale-95 shadow-md" 
                                        title="Ganti Kamera">
                                    <span class="material-icons text-sm">cameraswitch</span>
                                </button>
                            </div>

                            <!-- Face Loading Overlay -->
                            <div id="face-loading-overlay" class="absolute inset-0 bg-slate-950/90 flex flex-col items-center justify-center p-6 text-center z-10 hidden">
                                <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center mb-3 animate-bounce">
                                    <span class="material-icons text-xl">psychology</span>
                                </div>
                                <h4 class="text-xs font-bold text-white mb-1" id="face-loading-text">Memuat Model AI Wajah...</h4>
                                <div class="w-36 bg-slate-800 h-1.5 rounded-full overflow-hidden mt-2">
                                    <div id="face-loading-bar" class="bg-amber-500 h-full rounded-full transition-all duration-300" style="width: 15%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Message Alert Box -->
                        <div x-show="statusMessage" x-cloak 
                             :class="statusType === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200' : 'bg-rose-50 dark:bg-rose-950/60 border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200'"
                             class="mt-4 p-3.5 rounded-2xl border text-xs font-bold flex items-center gap-2.5 transition-all">
                            <span class="material-icons text-lg" x-text="statusType === 'success' ? 'check_circle' : 'error'"></span>
                            <span x-text="statusMessage" class="flex-1"></span>
                        </div>

                        <p class="text-[11px] text-center text-slate-400 dark:text-slate-500 mt-3">
                            Arahkan kartu QR siswa atau wajah siswa ke kamera untuk mencatat presensi ekskul secara instan.
                        </p>
                    </div>
                </div>

            </div>

            <!-- Kolom Kanan: Daftar Anggota & Status Sesi (7 cols) -->
            <div class="lg:col-span-7 space-y-4" :class="mobileSection === 'students' ? 'block' : 'hidden lg:block'" 
                 x-data="{
                     activeTab: 'hadir',
                     searchQuery: '',
                     editModalOpen: false,
                     selectedStudent: null,
                     selectedStatus: 'hadir',
                     selectedNotes: '',
                     openEditModal(student, currentStatus) {
                         this.selectedStudent = student;
                         this.selectedStatus = currentStatus || 'hadir';
                         this.selectedNotes = '';
                         this.editModalOpen = true;
                     }
                 }">
                
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                    
                    <!-- Search & Tab Filter Header -->
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-icons text-amber-500 text-lg">groups</span>
                                Data Kehadiran Anggota
                            </h3>

                            <!-- Search Input -->
                            <div class="relative w-full sm:w-60">
                                <input type="text" x-model="searchQuery" placeholder="Cari nama / NIS..." 
                                       class="w-full text-xs py-2 pl-8 pr-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/15">
                                <span class="material-icons text-slate-400 text-sm absolute left-2.5 top-1/2 -translate-y-1/2">search</span>
                            </div>
                        </div>

                        <!-- Status Tabs -->
                        <div class="flex bg-slate-100 dark:bg-slate-850 p-1 rounded-2xl border border-slate-200/60 dark:border-slate-700/60 overflow-x-auto">
                            <button @click="activeTab = 'hadir'" 
                                    :class="activeTab === 'hadir' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 font-extrabold shadow-xs' : 'text-slate-600 dark:text-slate-400 font-semibold'"
                                    class="flex-1 py-2 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all whitespace-nowrap">
                                <span>Hadir</span>
                                <span id="count-hadir" class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-extrabold">
                                    {{ count($studentsHadir) }}
                                </span>
                            </button>

                            <button @click="activeTab = 'izin'" 
                                    :class="activeTab === 'izin' ? 'bg-white dark:bg-slate-700 text-purple-600 dark:text-purple-400 font-extrabold shadow-xs' : 'text-slate-600 dark:text-slate-400 font-semibold'"
                                    class="flex-1 py-2 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all whitespace-nowrap">
                                <span>Izin / Sakit</span>
                                <span id="count-izin" class="px-1.5 py-0.2 rounded-full text-[10px] bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 font-extrabold">
                                    {{ count($studentsIzin) }}
                                </span>
                            </button>

                            <button @click="activeTab = 'belum_absen'" 
                                    :class="activeTab === 'belum_absen' ? 'bg-white dark:bg-slate-700 text-rose-600 dark:text-rose-400 font-extrabold shadow-xs' : 'text-slate-600 dark:text-slate-400 font-semibold'"
                                    class="flex-1 py-2 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all whitespace-nowrap">
                                <span>Belum Absen / Alpa</span>
                                <span id="count-belum" class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 font-extrabold">
                                    {{ count($studentsBelumAbsen) }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Students List Container -->
                    <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-[480px] overflow-y-auto" id="student-list-container">
                        
                        @foreach($students as $student)
                            @php
                                $att = $attendances->get($student->id);
                                $currentStatus = $att ? $att->status : 'belum_absen';
                                $category = ($currentStatus === 'hadir') ? 'hadir' : (in_array($currentStatus, ['sakit', 'izin']) ? 'izin' : 'belum_absen');
                            @endphp
                            <div class="p-4 flex items-center justify-between hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition-colors student-row" 
                                 data-student-id="{{ $student->id }}"
                                 data-student-name="{{ mb_strtolower($student->name) }}"
                                 data-student-nis="{{ $student->nis ?? '' }}"
                                 data-category="{{ $category }}"
                                 x-show="(activeTab === '{{ $category }}') && (searchQuery === '' || '{{ mb_strtolower($student->name) }}'.includes(searchQuery.toLowerCase()) || '{{ $student->nis }}'.includes(searchQuery))">
                                
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 font-extrabold text-xs flex items-center justify-center shrink-0 border border-amber-200/60 dark:border-amber-800 overflow-hidden shadow-2xs">
                                        @if(!empty($student->photo_url))
                                            <img src="{{ $student->photo_url }}" 
                                                 alt="{{ $student->name }}" 
                                                 class="w-full h-full object-cover rounded-2xl cursor-pointer hover:scale-105 active:scale-95 transition-all student-avatar" 
                                                 loading="lazy"
                                                 onclick="previewStudentPhoto('{{ $student->photo_url }}', '{{ addslashes($student->name) }}', '{{ $student->schoolClass->name ?? '' }} {{ $student->nis ? '&bull; NIS ' . $student->nis : '' }}')"
                                                 title="Klik untuk memperbesar foto siswa"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <span style="display: none;" class="w-full h-full items-center justify-center font-extrabold text-xs">
                                                {{ strtoupper(substr($student->name, 0, 1)) }}
                                            </span>
                                        @else
                                            <span>{{ strtoupper(substr($student->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">
                                            {{ $student->name }}
                                        </h4>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                            Kelas {{ $student->schoolClass?->name ?? '-' }} • NIS: {{ $student->nis ?? '-' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="status-badge px-2.5 py-1 rounded-full text-[10px] font-extrabold 
                                        {{ $currentStatus === 'hadir' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : '' }}
                                        {{ $currentStatus === 'sakit' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : '' }}
                                        {{ $currentStatus === 'izin' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' : '' }}
                                        {{ $currentStatus === 'alpa' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300' : '' }}
                                        {{ $currentStatus === 'belum_absen' ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' : '' }}">
                                        {{ ucfirst(str_replace('_', ' ', $currentStatus)) }}
                                    </span>

                                    <!-- Action Button: Open Modal Status -->
                                    <button type="button" 
                                            @click="openEditModal({{ json_encode($student) }}, '{{ $currentStatus }}')" 
                                            class="p-1.5 rounded-xl text-slate-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition-colors" 
                                            title="Ubah Status Presensi">
                                        <span class="material-icons text-base">edit</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>

                <!-- Modal Koreksi Status Manual -->
                <div x-show="editModalOpen" x-cloak 
                     class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
                    
                    <div class="relative bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200/80 dark:border-slate-800" 
                         @click.away="editModalOpen = false">
                        
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                    <span class="material-icons text-lg">edit_note</span>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Ubah Status Kehadiran</h3>
                                    <p class="text-xs text-slate-500" x-text="selectedStudent ? selectedStudent.name : ''"></p>
                                </div>
                            </div>
                            <button @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <span class="material-icons">close</span>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Pilih Status Baru</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="flex items-center gap-2 p-3 rounded-2xl border cursor-pointer transition-all"
                                           :class="selectedStatus === 'hadir' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 font-bold' : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300'">
                                        <input type="radio" name="status_opt" value="hadir" x-model="selectedStatus" class="text-emerald-600 focus:ring-emerald-500">
                                        <span class="text-xs">Hadir</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-3 rounded-2xl border cursor-pointer transition-all"
                                           :class="selectedStatus === 'izin' ? 'border-purple-500 bg-purple-50/50 dark:bg-purple-950/40 text-purple-800 dark:text-purple-300 font-bold' : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300'">
                                        <input type="radio" name="status_opt" value="izin" x-model="selectedStatus" class="text-purple-600 focus:ring-purple-500">
                                        <span class="text-xs">Izin</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-3 rounded-2xl border cursor-pointer transition-all"
                                           :class="selectedStatus === 'sakit' ? 'border-amber-500 bg-amber-50/50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 font-bold' : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300'">
                                        <input type="radio" name="status_opt" value="sakit" x-model="selectedStatus" class="text-amber-600 focus:ring-amber-500">
                                        <span class="text-xs">Sakit</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-3 rounded-2xl border cursor-pointer transition-all"
                                           :class="selectedStatus === 'alpa' ? 'border-rose-500 bg-rose-50/50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 font-bold' : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300'">
                                        <input type="radio" name="status_opt" value="alpa" x-model="selectedStatus" class="text-rose-600 focus:ring-rose-500">
                                        <span class="text-xs">Alpa</span>
                                    </label>
                                </div>
                                <div class="mt-2 text-right">
                                    <button type="button" @click="selectedStatus = 'hapus'" 
                                            class="text-[11px] font-bold text-rose-500 hover:text-rose-600 underline">
                                        Reset (Belum Absen)
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Catatan / Keterangan (Opsional)</label>
                                <input type="text" x-model="selectedNotes" placeholder="Contoh: Mengikuti lomba antar sekolah..." 
                                       class="w-full text-xs p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:border-amber-500">
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                                <button type="button" @click="editModalOpen = false" 
                                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200">
                                    Batal
                                </button>
                                <button type="button" @click="window.submitManualStatus(selectedStudent.id, selectedStatus, selectedNotes); editModalOpen = false;" 
                                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 shadow-md shadow-amber-500/20 active:scale-95 transition-all">
                                    Simpan Status
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    @push('scripts')
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/dist/face-api.js"></script>
        
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                function playBeep(success = true) {
                    try {
                        const osc = audioCtx.createOscillator();
                        const gain = audioCtx.createGain();
                        osc.connect(gain);
                        gain.connect(audioCtx.destination);
                        osc.type = success ? 'sine' : 'sawtooth';
                        osc.frequency.setValueAtTime(success ? 880 : 330, audioCtx.currentTime);
                        gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.25);
                        osc.start();
                        osc.stop(audioCtx.currentTime + 0.25);
                    } catch (e) { console.warn('Audio feedback error:', e); }
                }

                // QR Scanner Setup
                let html5QrCode = null;
                let isScanningQr = false;
                let lastScannedCode = null;
                let scanThrottleTimer = null;

                function startQrScanner() {
                    if (isScanningQr) return;
                    html5QrCode = new Html5Qrcode("reader");
                    const config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };

                    html5QrCode.start(
                        { facingMode: "environment" },
                        config,
                        (decodedText) => {
                            if (lastScannedCode === decodedText) return;
                            lastScannedCode = decodedText;
                            clearTimeout(scanThrottleTimer);
                            scanThrottleTimer = setTimeout(() => { lastScannedCode = null; }, 3000);

                            handleScanSuccess(decodedText);
                        },
                        (errorMessage) => { /* ignore frame errors */ }
                    ).then(() => {
                        isScanningQr = true;
                    }).catch(err => {
                        console.warn("Kamera environment gagal, coba default user camera:", err);
                        html5QrCode.start(
                            { facingMode: "user" },
                            config,
                            (decodedText) => { handleScanSuccess(decodedText); }
                        ).then(() => { isScanningQr = true; }).catch(e => console.error("Kamera gagal:", e));
                    });
                }

                function stopQrScanner() {
                    if (html5QrCode && isScanningQr) {
                        html5QrCode.stop().then(() => {
                            isScanningQr = false;
                        }).catch(err => console.error("Gagal stop QR:", err));
                    }
                }

                // Face API Setup
                let isFaceApiLoaded = false;
                let faceVideo = document.getElementById('face-video');
                let faceCanvas = document.getElementById('face-canvas');
                let faceMatcher = null;
                let faceInterval = null;
                let currentFacingMode = 'user';
                const studentsWithPhotos = @json($studentsWithPhotos ?? []);

                async function loadFaceModels() {
                    if (isFaceApiLoaded) return true;
                    const overlay = document.getElementById('face-loading-overlay');
                    const bar = document.getElementById('face-loading-bar');
                    if (overlay) overlay.classList.remove('hidden');

                    try {
                        const MODEL_URL = '{{ asset('models') }}';
                        await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
                        if (bar) bar.style.width = '50%';
                        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
                        if (bar) bar.style.width = '100%';
                        setTimeout(() => { if (overlay) overlay.classList.add('hidden'); }, 300);
                        isFaceApiLoaded = true;
                        return true;
                    } catch (e) {
                        console.error("Gagal load model wajah:", e);
                        if (overlay) overlay.classList.add('hidden');
                        return false;
                    }
                }

                async function startFaceScanner() {
                    if (!await loadFaceModels()) return;
                    navigator.mediaDevices.getUserMedia({ video: { facingMode: currentFacingMode } })
                    .then(stream => {
                        if (faceVideo) faceVideo.srcObject = stream;
                    }).catch(e => console.error("Akses video wajah gagal:", e));
                }

                function stopFaceScanner() {
                    if (faceVideo && faceVideo.srcObject) {
                        faceVideo.srcObject.getTracks().forEach(t => t.stop());
                        faceVideo.srcObject = null;
                    }
                    if (faceInterval) clearInterval(faceInterval);
                }

                window.switchScannerMode = function(mode) {
                    if (mode === 'qr') {
                        stopFaceScanner();
                        startQrScanner();
                    } else if (mode === 'face') {
                        stopQrScanner();
                        startFaceScanner();
                    }
                };

                // AJAX Kirim Presensi Scan
                function handleScanSuccess(identifier) {
                    const dateVal = document.getElementById('attendance-date').value;
                    fetch("{{ route('teacher.extracurricular-attendance.scan', $extracurricular) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            student_unique_id: identifier,
                            date: dateVal
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            playBeep(true);
                            Alpine.$data(document.querySelector('[x-data]')).setStatus(data.message, 'success');
                            updateStudentUI(data.student.id, 'hadir');
                        } else {
                            playBeep(false);
                            Alpine.$data(document.querySelector('[x-data]')).setStatus(data.message, 'error');
                        }
                    })
                    .catch(err => {
                        playBeep(false);
                        console.error(err);
                        Alpine.$data(document.querySelector('[x-data]')).setStatus('Terjadi kesalahan saat memproses scan.', 'error');
                    });
                }

                // AJAX Kirim Status Manual
                window.submitManualStatus = function(studentId, status, notes) {
                    const dateVal = document.getElementById('attendance-date').value;
                    fetch("{{ route('teacher.extracurricular-attendance.mark_manual', $extracurricular) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            student_id: studentId,
                            status: status,
                            notes: notes,
                            date: dateVal
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Alpine.$data(document.querySelector('[x-data]')).setStatus(data.message, 'success');
                            updateStudentUI(studentId, data.status);
                        } else {
                            Alpine.$data(document.querySelector('[x-data]')).setStatus(data.message, 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Alpine.$data(document.querySelector('[x-data]')).setStatus('Gagal mengubah status presensi.', 'error');
                    });
                };

                function updateStudentUI(studentId, newStatus) {
                    const row = document.querySelector(`.student-row[data-student-id="${studentId}"]`);
                    if (!row) return;

                    const category = (newStatus === 'hadir') ? 'hadir' : (['sakit', 'izin'].includes(newStatus) ? 'izin' : 'belum_absen');
                    row.setAttribute('data-category', category);

                    const badge = row.querySelector('.status-badge');
                    if (badge) {
                        badge.className = 'status-badge px-2.5 py-1 rounded-full text-[10px] font-extrabold';
                        if (newStatus === 'hadir') {
                            badge.classList.add('bg-emerald-100', 'text-emerald-800', 'dark:bg-emerald-950/60', 'dark:text-emerald-300');
                            badge.textContent = 'Hadir';
                        } else if (newStatus === 'sakit') {
                            badge.classList.add('bg-amber-100', 'text-amber-800', 'dark:bg-amber-950/60', 'dark:text-amber-300');
                            badge.textContent = 'Sakit';
                        } else if (newStatus === 'izin') {
                            badge.classList.add('bg-purple-100', 'text-purple-800', 'dark:bg-purple-950/60', 'dark:text-purple-300');
                            badge.textContent = 'Izin';
                        } else if (newStatus === 'alpa') {
                            badge.classList.add('bg-rose-100', 'text-rose-800', 'dark:bg-rose-950/60', 'dark:text-rose-300');
                            badge.textContent = 'Alpa';
                        } else {
                            badge.classList.add('bg-slate-100', 'text-slate-600', 'dark:bg-slate-800', 'dark:text-slate-400');
                            badge.textContent = 'Belum Absen';
                        }
                    }

                    // Refresh badge counts
                    const allRows = document.querySelectorAll('.student-row');
                    let hadirCount = 0, izinCount = 0, belumCount = 0;
                    allRows.forEach(r => {
                        const cat = r.getAttribute('data-category');
                        if (cat === 'hadir') hadirCount++;
                        else if (cat === 'izin') izinCount++;
                        else belumCount++;
                    });

                    document.getElementById('count-hadir').textContent = hadirCount;
                    document.getElementById('count-izin').textContent = izinCount;
                    document.getElementById('count-belum').textContent = belumCount;
                }

                // Inisialisasi awal QR Scanner
                startQrScanner();

                // Ganti tanggal reload
                document.getElementById('attendance-date').addEventListener('change', (e) => {
                    window.location.href = "{{ route('teacher.extracurricular-attendance.scanner', $extracurricular) }}?date=" + e.target.value;
                });
            });
        </script>
    @endpush
</x-app-layout>
