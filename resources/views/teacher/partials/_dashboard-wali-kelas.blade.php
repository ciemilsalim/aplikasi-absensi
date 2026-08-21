{{-- Konten khusus Dasbor Wali Kelas (SMP Negeri 1 Biau) --}}

<div class="space-y-6">

    {{-- ========================================================================= --}}
    {{-- 1. TAMPILAN KHUSUS MOBILE (< sm breakpoint)                               --}}
    {{-- Mengikuti Standar UX Mobile: Hierarchy Jelas, Hero Status, 4 Quick Actions --}}
    {{-- ========================================================================= --}}
    <div class="block sm:hidden space-y-5" x-data="{ showStudentManagementModal: false, mobileSearch: '', mobileStatusFilter: 'all' }">
        
        <!-- Header Sapaan Sederhana & Ramah Jempol -->
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <img class="h-12 w-12 rounded-2xl object-cover ring-2 ring-sky-500/20 bg-slate-800 shrink-0 shadow-sm" 
                     src="{{ Auth::user()->profile_photo_url }}" 
                     onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=0284c7&background=e0f2fe';" 
                     alt="{{ Auth::user()->name }}">
                <div class="min-w-0">
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white leading-tight truncate">
                        Halo, {{ explode(' ', $teacher->name)[0] }} 👋
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                        Wali Kelas &bull; Kelas {{ $class->name }}
                    </p>
                </div>
            </div>
            <a href="{{ route('teacher.attendance.charts') }}" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-500 hover:text-sky-600 shadow-2xs shrink-0" title="Analitik">
                <span class="material-icons text-xl text-sky-500">insights</span>
            </a>
        </div>

        <!-- HERO CARD: STATUS PRESENSI KELAS BINAAN (SEDERHANA, JELAS, BEBAS OVERLAP) -->
        <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
            <!-- Header Card: Info Kelas & Status -->
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-black uppercase tracking-wider text-sky-600 dark:text-sky-400 block">
                        KELAS {{ $class->name }}
                    </span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-0.5">
                        {{ $totalStudents }} Siswa
                    </h3>
                </div>
                
                @if($dailyPresencePercentage > 0)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 {{ $dailyPresencePercentage >= 100 ? 'animate-pulse' : '' }}"></span>
                        <span>{{ $dailyPresencePercentage >= 100 ? '100% Hadir' : 'Sesi Berjalan' }}</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        Belum dimulai
                    </span>
                @endif
            </div>

            <!-- Box Status Kehadiran Hari Ini -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-850/60 border border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Presensi Hari Ini</p>
                        <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 mt-0.5">
                            {{ Carbon\Carbon::today()->translatedFormat('l, d F Y') }}
                        </p>
                    </div>
                    <div class="text-right">
                        @if($dailyPresencePercentage > 0)
                            <span class="text-2xl font-black text-slate-900 dark:text-white">{{ $dailyPresencePercentage }}%</span>
                        @else
                            <span class="text-2xl font-black text-slate-400">—</span>
                        @endif
                    </div>
                </div>

                @if($dailyPresencePercentage > 0)
                    <div class="mt-3 pt-3 border-t border-slate-200/60 dark:border-slate-750 text-[11px] font-semibold text-slate-600 dark:text-slate-300 flex items-center justify-between flex-wrap gap-1">
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $onTimeCount }} tepat</span>
                        <span>&bull;</span>
                        <span class="text-amber-600 dark:text-amber-400 font-bold">{{ $lateCount }} telat</span>
                        <span>&bull;</span>
                        <span class="text-purple-600 dark:text-purple-400 font-bold">{{ $sickCount + $permitCount }} izin/sakit</span>
                        <span>&bull;</span>
                        <span class="text-rose-600 dark:text-rose-400 font-bold">{{ $alphaCount }} alpa</span>
                    </div>
                @else
                    <p class="mt-2 text-xs text-slate-400 dark:text-slate-500 italic">
                        Belum ada data presensi siswa yang dicatat hari ini.
                    </p>
                @endif
            </div>

            <!-- Action CTA: Primary (Scan QR) & Secondary (Kelola Presensi) -->
            <div class="space-y-2 pt-1">
                <a href="{{ route('scanner') }}" 
                   class="w-full min-h-[48px] py-3 px-4 flex items-center justify-center gap-2 rounded-2xl bg-sky-600 hover:bg-sky-500 active:bg-sky-700 text-white font-bold text-sm shadow-md shadow-sky-600/20 active:scale-95 transition-all text-center">
                    <span class="material-icons text-lg">qr_code_scanner</span>
                    <span>Scan QR Siswa</span>
                </a>
                
                <button @click="showStudentManagementModal = true" 
                        class="w-full min-h-[44px] py-2.5 px-4 flex items-center justify-center gap-2 rounded-2xl bg-white hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-855 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700 shadow-2xs transition-all text-center">
                    <span class="material-icons text-base text-slate-500">checklist</span>
                    <span>Kelola Presensi</span>
                </button>
            </div>
        </div>

        <!-- AKSI CEPAT (4 Tombol Aksi Cepat) -->
        <div>
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2.5">Aksi Cepat</div>
            <div class="grid grid-cols-4 gap-2.5">
                <!-- 1. Scan QR Siswa -->
                <a href="{{ route('scanner') }}" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:bg-sky-50 dark:hover:bg-slate-800 active:scale-95 transition-all text-center group">
                    <div class="w-11 h-11 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <span class="material-icons text-xl">qr_code_scanner</span>
                    </div>
                    <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 truncate w-full">Scan QR</span>
                </a>

                <!-- 2. Izin Siswa -->
                <a href="{{ route('teacher.leave_requests.index') }}" class="relative flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:bg-amber-50 dark:hover:bg-slate-800 active:scale-95 transition-all text-center group">
                    @if(isset($teacherPendingLeaveRequestsCount) && $teacherPendingLeaveRequestsCount > 0)
                        <span class="absolute top-1.5 right-1.5 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-500 text-white text-[9px] font-bold shadow-xs">{{ $teacherPendingLeaveRequestsCount }}</span>
                    @endif
                    <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <span class="material-icons text-xl">fact_check</span>
                    </div>
                    <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 truncate w-full">Izin Siswa</span>
                </a>

                <!-- 3. Siswa Kelas -->
                <button @click="showStudentManagementModal = true" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:bg-indigo-50 dark:hover:bg-slate-800 active:scale-95 transition-all text-center group">
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <span class="material-icons text-xl">groups</span>
                    </div>
                    <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 truncate w-full">Siswa</span>
                </button>

                <!-- 4. Rekap Laporan -->
                <a href="{{ route('teacher.attendance.history') }}" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:bg-emerald-50 dark:hover:bg-slate-800 active:scale-95 transition-all text-center group">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <span class="material-icons text-xl">assessment</span>
                    </div>
                    <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 truncate w-full">Laporan</span>
                </a>
            </div>
        </div>

        <!-- DAFTAR SISWA BELUM HADIR / PERHATIAN (EXCEPTION-BASED WORKFLOW - PRIORITAS TINGGI) -->
        <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-1">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="material-icons text-amber-500 text-base">person_search</span>
                        <span>Siswa Belum Hadir</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        @if($totalBelumAbsen > 0)
                            {{ $totalBelumAbsen }} siswa belum mencatat kehadiran
                        @else
                            Seluruh siswa sudah terdata
                        @endif
                    </p>
                </div>
                @if($totalBelumAbsen > 0)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300">
                        {{ $totalBelumAbsen }} Siswa
                    </span>
                @endif
            </div>

            @if($totalBelumAbsen > 0)
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($absentStudents->take(4) as $student)
                        <div class="py-3 flex items-center justify-between gap-3 min-h-[56px]">
                            <div class="flex items-center gap-3 min-w-0">
                                <img class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700 shrink-0" 
                                     src="{{ $student->photo_url }}" 
                                     onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=0284c7&background=e0f2fe';" 
                                     alt="{{ $student->name }}">
                                <div class="min-w-0">
                                    <p class="font-bold text-xs text-slate-900 dark:text-white truncate">{{ $student->name }}</p>
                                    <p class="text-[10px] text-slate-400 truncate">NIS: {{ $student->nis ?? '-' }}</p>
                                </div>
                            </div>

                            <div class="shrink-0">
                                <button @click="showStudentManagementModal = true; mobileSearch = '{{ addslashes($student->name) }}'" 
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 transition-colors">
                                    Catat Status
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button @click="showStudentManagementModal = true; mobileSearch = ''; mobileStatusFilter = 'belum_hadir'" 
                            class="w-full min-h-[40px] py-2 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-750 text-xs font-bold text-sky-600 dark:text-sky-400 border border-slate-200/80 dark:border-slate-700/80 transition-colors flex items-center justify-center gap-1.5">
                        <span>Lihat Semua Siswa Belum Hadir ({{ $totalBelumAbsen }})</span>
                        <span class="material-icons text-sm">chevron_right</span>
                    </button>
                </div>
            @else
                <div class="py-6 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-2.5">
                        <span class="material-icons text-2xl">verified</span>
                    </div>
                    <p class="text-xs font-bold text-slate-800 dark:text-white">Semua Siswa Telah Terdata</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Seluruh siswa tercatat hadir atau memiliki status kehadiran hari ini.</p>
                </div>
            @endif
        </div>

        <!-- TREN KEHADIRAN KELAS (5 SESI TERAKHIR) -->
        <div class="rounded-3xl p-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="material-icons text-sky-500 text-base">show_chart</span>
                        <span>Tren Kehadiran</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">5 sesi terakhir &bull; Kelas {{ $class->name }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs font-extrabold text-sky-600 dark:text-sky-400">{{ $avgRate ?? 0 }}%</span>
                    <span class="text-[10px] text-slate-400 block -mt-0.5">rata-rata</span>
                </div>
            </div>

            <div class="h-40 w-full">
                <canvas id="weeklyAttendanceChartMobile"></canvas>
            </div>

            <!-- Insight Box -->
            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-850/60 border border-slate-100 dark:border-slate-800 flex items-center gap-2.5 text-xs text-slate-600 dark:text-slate-300">
                <span class="material-icons text-emerald-500 text-base">insights</span>
            </div>
        </div>

        <!-- MODAL BOTTOM SHEET KELOLA SELURUH SISWA (MOBILE ONLY) -->
        <div x-show="showStudentManagementModal" class="relative z-50" style="display: none;" x-transition>
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="showStudentManagementModal = false"></div>

            <!-- Bottom Sheet -->
            <div class="fixed inset-x-0 bottom-0 z-50 flex max-h-[90vh] flex-col rounded-t-3xl bg-white dark:bg-slate-900 p-5 shadow-2xl border-t border-slate-200 dark:border-slate-800 transition-transform duration-300"
                 x-show="showStudentManagementModal"
                 x-transition:enter="transform transition ease-out duration-300"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 x-transition:leave="transform transition ease-in duration-200"
                 x-transition:leave-start="translate-y-0"
                 x-transition:leave-end="translate-y-full">
                
                <!-- Drag handle -->
                <div class="mx-auto h-1 w-9 rounded-full bg-slate-300 dark:bg-slate-700 mb-3"></div>

                <!-- Header Modal -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Kelola Presensi Siswa</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Kelas {{ $class->name }} &bull; {{ $totalStudents }} Siswa</p>
                    </div>
                    <button @click="showStudentManagementModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <span class="material-icons text-xl">close</span>
                    </button>
                </div>

                <!-- Filter & Search bar -->
                <div class="pt-3 pb-2 space-y-2">
                    <div class="relative">
                        <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-base">search</span>
                        <input type="text" x-model="mobileSearch" placeholder="Cari nama siswa..." 
                               class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:border-sky-500">
                    </div>
                    <div class="flex gap-1.5 overflow-x-auto no-scrollbar pb-1">
                        <button @click="mobileStatusFilter = 'all'" :class="mobileStatusFilter === 'all' ? 'bg-sky-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'" class="px-2.5 py-1 text-[11px] font-bold rounded-lg shrink-0">Semua</button>
                        <button @click="mobileStatusFilter = 'belum_hadir'" :class="mobileStatusFilter === 'belum_hadir' ? 'bg-slate-700 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'" class="px-2.5 py-1 text-[11px] font-bold rounded-lg shrink-0">Belum Hadir</button>
                        <button @click="mobileStatusFilter = 'hadir'" :class="mobileStatusFilter === 'hadir' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'" class="px-2.5 py-1 text-[11px] font-bold rounded-lg shrink-0">Hadir</button>
                        <button @click="mobileStatusFilter = 'sakit'" :class="mobileStatusFilter === 'sakit' ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'" class="px-2.5 py-1 text-[11px] font-bold rounded-lg shrink-0">Sakit</button>
                        <button @click="mobileStatusFilter = 'izin'" :class="mobileStatusFilter === 'izin' ? 'bg-purple-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'" class="px-2.5 py-1 text-[11px] font-bold rounded-lg shrink-0">Izin</button>
                    </div>
                </div>

                <!-- Student List -->
                <div class="overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 max-h-[55vh] no-scrollbar">
                    @forelse($studentsInClass as $student)
                        @php $attendance = $attendancesToday->get($student->id); @endphp
                        <div class="py-3 space-y-2"
                             x-show="(mobileSearch === '' || {{ json_encode(mb_strtolower($student->name)) }}.includes(mobileSearch.toLowerCase())) && (mobileStatusFilter === 'all' || (mobileStatusFilter === 'belum_hadir' && {{ $attendance ? 'false' : 'true' }}) || (mobileStatusFilter === 'hadir' && ['tepat_waktu','terlambat'].includes('{{ $attendance->status ?? '' }}')) || (mobileStatusFilter === 'sakit' && '{{ $attendance->status ?? '' }}' === 'sakit') || (mobileStatusFilter === 'izin' && ['izin','izin_keluar'].includes('{{ $attendance->status ?? '' }}')) || (mobileStatusFilter === 'alpa' && '{{ $attendance->status ?? '' }}' === 'alpa'))">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <img class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700 shrink-0" 
                                         src="{{ $student->photo_url }}" 
                                         onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=0284c7&background=e0f2fe';" 
                                         alt="{{ $student->name }}">
                                    <div class="min-w-0">
                                        <p class="font-bold text-xs text-slate-900 dark:text-white truncate">{{ $student->name }}</p>
                                        <p class="text-[10px] text-slate-400">NIS: {{ $student->nis ?? '-' }}</p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $attendance ? ($attendance->status === 'tepat_waktu' ? 'bg-emerald-100 text-emerald-800' : ($attendance->status === 'terlambat' ? 'bg-rose-100 text-rose-800' : ($attendance->status === 'sakit' ? 'bg-amber-100 text-amber-800' : 'bg-purple-100 text-purple-800'))) : 'bg-slate-100 text-slate-500' }}">
                                    {{ $attendance ? ucfirst(str_replace('_', ' ', $attendance->status)) : 'Belum Hadir' }}
                                </span>
                            </div>

                            <!-- 1-Tap Quick Action Form -->
                            <div class="flex items-center gap-1.5 pt-1">
                                @if(!$attendance)
                                    <form action="{{ route('teacher.mark.attendance') }}" method="POST" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="status" value="sakit">
                                        <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                        <button type="submit" class="w-full py-1 text-[11px] font-bold text-amber-700 bg-amber-100 hover:bg-amber-200 dark:bg-amber-950/60 dark:text-amber-300 rounded-lg">Sakit</button>
                                    </form>
                                    <form action="{{ route('teacher.mark.attendance') }}" method="POST" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="status" value="izin">
                                        <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                        <button type="submit" class="w-full py-1 text-[11px] font-bold text-purple-700 bg-purple-100 hover:bg-purple-200 dark:bg-purple-950/60 dark:text-purple-300 rounded-lg">Izin</button>
                                    </form>
                                    <form action="{{ route('teacher.mark.attendance') }}" method="POST" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="status" value="alpa">
                                        <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                        <button type="submit" class="w-full py-1 text-[11px] font-bold text-red-700 bg-red-100 hover:bg-red-200 dark:bg-red-950/60 dark:text-red-300 rounded-lg">Alpa</button>
                                    </form>
                                @else
                                    <form action="{{ route('teacher.mark.attendance') }}" method="POST" class="w-full" onsubmit="return confirm('Reset status kehadiran siswa ini?')">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="status" value="hapus">
                                        <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                        <button type="submit" class="w-full py-1 text-[11px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 rounded-lg">Reset Status Kehadiran</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-6 text-center">Tidak ada siswa.</p>
                    @endforelse
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button @click="showStudentManagementModal = false" class="w-full min-h-[44px] rounded-xl bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 text-xs font-bold">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- 2. TAMPILAN DESKTOP (≥ sm breakpoint)                                      --}}
    {{-- Full-featured: Hero 8-cols, 6 KPI cards, Full Interactive Table, 7-Day Chart --}}
    {{-- ========================================================================= --}}
    <div class="hidden sm:block space-y-6">

        <!-- Hero Section: Welcome & Quick Access -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
            
            <!-- Hero Welcome Card (8 cols) -->
            <div class="lg:col-span-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-850 to-sky-950 text-white p-6 sm:p-7 shadow-lg border border-slate-800/80">
                <div class="absolute -right-10 -bottom-10 w-60 h-60 rounded-full bg-sky-500/10 blur-3xl pointer-events-none"></div>
                
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-5 h-full">
                    <div class="flex items-center gap-4">
                        <img class="h-16 w-16 rounded-2xl object-cover ring-4 ring-white/10 bg-slate-800 shrink-0" 
                             src="{{ Auth::user()->profile_photo_url }}" 
                             onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=0284c7&background=e0f2fe';" 
                             alt="{{ Auth::user()->name }}">
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-sky-500/20 text-[10px] font-bold text-sky-300 border border-sky-400/20 mb-1">
                                <span>Wali Kelas {{ $class->name }}</span>
                            </div>
                            <h2 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-snug">
                                Halo, {{ $teacher->name }}!
                            </h2>
                            <p class="text-xs text-slate-300 mt-0.5">
                                Kelola presensi harian & pantau kedisiplinan siswa kelas binaan Anda.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('teacher.attendance.charts') }}" 
                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white/15 hover:bg-white/25 backdrop-blur-md text-white text-xs font-bold border border-white/20 transition-all">
                            <span class="material-icons text-base">insights</span>
                            <span>Analitik Visual</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Access (4 cols) -->
            <div class="lg:col-span-4 rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1.5 mb-3">
                    <span class="material-icons text-sky-500 text-base">qr_code_scanner</span>
                    Pindai Kehadiran
                </h3>

                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('scanner') }}" 
                       class="flex flex-col items-center justify-center text-center p-3 rounded-2xl bg-sky-50/70 dark:bg-sky-950/30 border border-sky-200/60 dark:border-sky-900/40 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition-all group">
                        <span class="material-icons text-2xl text-sky-600 dark:text-sky-400 mb-1 group-hover:scale-110 transition-transform">qr_code_2</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Scan Hadir</span>
                    </a>

                    <a href="{{ route('permit.scanner') }}" 
                       class="flex flex-col items-center justify-center text-center p-3 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-900/40 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-all group">
                        <span class="material-icons text-2xl text-amber-600 dark:text-amber-400 mb-1 group-hover:scale-110 transition-transform">directions_walk</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Scan Izin</span>
                    </a>
                </div>

                <div class="mt-3 text-[11px] text-slate-400 dark:text-slate-500 text-center">
                    {{ now()->translatedFormat('l, d F Y') }}
                </div>
            </div>
        </div>

        <!-- KARTU STATISTIK REKAP HARIAN KELAS (6 Kolom) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
            
            <!-- Tepat Waktu -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Tepat Waktu</span>
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-icons text-sm">task_alt</span>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ $onTimeCount }}</div>
            </div>

            <!-- Terlambat -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Terlambat</span>
                    <div class="w-7 h-7 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <span class="material-icons text-sm">alarm_on</span>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-extrabold text-rose-600 dark:text-rose-400">{{ $lateCount }}</div>
            </div>

            <!-- Sakit -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Sakit</span>
                    <div class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <span class="material-icons text-sm">healing</span>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ $sickCount }}</div>
            </div>

            <!-- Izin -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Izin</span>
                    <div class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <span class="material-icons text-sm">assignment</span>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-extrabold text-purple-600 dark:text-purple-400">{{ $permitCount }}</div>
            </div>

            <!-- Alpa -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Alpa</span>
                    <div class="w-7 h-7 rounded-lg bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-red-400 flex items-center justify-center">
                        <span class="material-icons text-sm">cancel</span>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-extrabold text-red-600 dark:text-red-400">{{ $alphaCount }}</div>
            </div>

            <!-- Tanpa Kabar / Belum Hadir -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Belum Hadir</span>
                    <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center">
                        <span class="material-icons text-sm">person_outline</span>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-extrabold text-slate-700 dark:text-slate-300">{{ $noRecordCount }}</div>
            </div>

        </div>

        <!-- KELOLA PRESENSI SISWA KELAS (DESKTOP TABLE) -->
        <div x-data="{ search: '', statusFilter: 'all' }" class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="p-6">
                
                <!-- Toolbar Header & Search Filters -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-sky-500 text-lg">checklist</span>
                            Presensi & Absensi Siswa Hari Ini
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Pemantauan dan input cepat kehadiran siswa kelas <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $class->name }}</span>
                        </p>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="relative w-full sm:w-56">
                            <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-base">search</span>
                            <input type="text" x-model="search" placeholder="Cari nama siswa..." 
                                   class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 shadow-2xs">
                        </div>

                        <select x-model="statusFilter" class="py-2 px-3 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 shadow-2xs">
                            <option value="all">Semua Status Kehadiran</option>
                            <option value="belum_hadir">Belum Hadir</option>
                            <option value="hadir">Hadir / Terlambat</option>
                            <option value="sakit">Sakit</option>
                            <option value="izin">Izin</option>
                            <option value="alpa">Alpa</option>
                        </select>
                    </div>
                </div>

                <!-- TAMPILAN DESKTOP: TABEL LEBAR PENUH -->
                <div class="overflow-x-auto rounded-2xl border border-slate-200/80 dark:border-slate-800">
                    <table class="w-full text-xs text-left text-slate-600 dark:text-slate-400">
                        <thead class="text-[11px] font-bold uppercase tracking-wider bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th scope="col" class="px-5 py-3.5">Nama Siswa</th>
                                <th scope="col" class="px-4 py-3.5 text-center w-32">Jam Masuk</th>
                                <th scope="col" class="px-4 py-3.5 text-center w-32">Jam Pulang</th>
                                <th scope="col" class="px-4 py-3.5 text-center w-36">Status</th>
                                <th scope="col" class="px-4 py-3.5 text-center w-48">Aksi Absensi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @forelse($studentsInClass as $student)
                                @php $attendance = $attendancesToday->get($student->id); @endphp
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-850/50 transition-colors"
                                    x-show="(search === '' || {{ json_encode(mb_strtolower($student->name)) }}.includes(search.toLowerCase())) && (statusFilter === 'all' || (statusFilter === 'belum_hadir' && {{ $attendance ? 'false' : 'true' }}) || (statusFilter === 'hadir' && ['tepat_waktu','terlambat'].includes('{{ $attendance->status ?? '' }}')) || (statusFilter === 'sakit' && '{{ $attendance->status ?? '' }}' === 'sakit') || (statusFilter === 'izin' && ['izin','izin_keluar'].includes('{{ $attendance->status ?? '' }}')) || (statusFilter === 'alpa' && '{{ $attendance->status ?? '' }}' === 'alpa'))">
                                    <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white">
                                        <div class="flex items-center gap-3">
                                            <div class="relative group shrink-0">
                                                <img class="h-9 w-9 rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-700" 
                                                     src="{{ $student->photo_url }}" 
                                                     onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=0284c7&background=e0f2fe';" 
                                                     alt="{{ $student->name }}">
                                                
                                                <!-- Tombol Ubah Foto -->
                                                <form action="{{ route('teacher.students.update_photo', $student->id) }}" method="POST" enctype="multipart/form-data" class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                    @csrf
                                                    <label for="student_photo_d_{{ $student->id }}" class="cursor-pointer bg-slate-900/70 rounded-full p-1 text-white">
                                                        <span class="material-icons text-xs">photo_camera</span>
                                                    </label>
                                                    <input type="file" id="student_photo_d_{{ $student->id }}" name="photo" class="hidden" onchange="this.form.submit()">
                                                </form>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900 dark:text-white text-xs">{{ $student->name }}</p>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-normal">NIS: {{ $student->nis ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        @if($attendance && $attendance->attendance_time && !in_array($attendance->status, ['izin', 'sakit', 'alpa']))
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-900/40">
                                                <span class="material-icons text-xs">login</span>
                                                {{ \Carbon\Carbon::parse($attendance->attendance_time)->format('H:i:s') }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        @if($attendance && $attendance->checkout_time)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200/60 dark:border-sky-900/40">
                                                <span class="material-icons text-xs">logout</span>
                                                {{ \Carbon\Carbon::parse($attendance->checkout_time)->format('H:i:s') }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        @if($attendance)
                                            @if ($attendance->status === 'tepat_waktu')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Tepat Waktu
                                                </span>
                                            @elseif ($attendance->status === 'terlambat')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Terlambat
                                                </span>
                                            @elseif ($attendance->status === 'izin')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>Izin
                                                </span>
                                            @elseif ($attendance->status === 'sakit')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Sakit
                                                </span>
                                            @elseif ($attendance->status === 'alpa')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-100 dark:bg-red-950/60 text-red-800 dark:text-red-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Alpa
                                                </span>
                                            @elseif ($attendance->status === 'izin_keluar')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-sky-100 dark:bg-sky-950/60 text-sky-800 dark:text-sky-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>Izin Keluar
                                                </span>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                                Belum Hadir
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        @if(!$attendance)
                                            <div class="flex items-center justify-center gap-1.5">
                                                <form action="{{ route('teacher.mark.attendance') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                    <input type="hidden" name="status" value="sakit">
                                                    <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                                    <button type="submit" title="Tandai Sakit" class="px-2.5 py-1 text-[11px] font-bold text-amber-700 bg-amber-100 hover:bg-amber-200 dark:bg-amber-950/60 dark:text-amber-300 rounded-lg transition-colors">Sakit</button>
                                                </form>
                                                <form action="{{ route('teacher.mark.attendance') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                    <input type="hidden" name="status" value="izin">
                                                    <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                                    <button type="submit" title="Tandai Izin" class="px-2.5 py-1 text-[11px] font-bold text-purple-700 bg-purple-100 hover:bg-purple-200 dark:bg-purple-950/60 dark:text-purple-300 rounded-lg transition-colors">Izin</button>
                                                </form>
                                                <form action="{{ route('teacher.mark.attendance') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                    <input type="hidden" name="status" value="alpa">
                                                    <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                                    <button type="submit" title="Tandai Alpa" class="px-2.5 py-1 text-[11px] font-bold text-red-700 bg-red-100 hover:bg-red-200 dark:bg-red-950/60 dark:text-red-300 rounded-lg transition-colors">Alpa</button>
                                                </form>
                                            </div>
                                        @else
                                            <form action="{{ route('teacher.mark.attendance') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mereset/menghapus status kehadiran siswa ini?')">
                                                @csrf
                                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                <input type="hidden" name="status" value="hapus">
                                                <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                                <button type="submit" title="Reset Status Kehadiran" class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 rounded-lg transition-colors">
                                                    <span class="material-icons text-xs">restart_alt</span> Reset
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400 italic">
                                        Tidak ada siswa di kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SEKSI BAWAH: GRAFIK & PANEL MONITORING (GRID 3 KOLOM) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Kolom Kiri: Grafik Tren Kehadiran Mingguan (2 cols) -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-slate-200/80 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-1">
                    <span class="material-icons text-sky-500 text-lg">show_chart</span>
                    Tren Kehadiran Kelas (7 Hari Terakhir)
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Persentase kehadiran harian kelas {{ $class->name }}</p>
                <div class="h-72 w-full">
                    <canvas id="weeklyAttendanceChart"></canvas>
                </div>
            </div>

            <!-- Kolom Kanan: Siswa Perlu Perhatian & Izin Keluar (1 col) -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Siswa Perlu Perhatian -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-amber-500 text-lg">priority_high</span>
                            Siswa Perlu Perhatian
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Berdasarkan data 30 hari terakhir</p>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800/80 max-h-60 overflow-y-auto no-scrollbar">
                        @forelse($studentsForAttentionWali as $student)
                            <div class="p-3.5 flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-850/50 transition-colors">
                                <img src="{{ $student->photo_url }}" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=0284c7&background=e0f2fe';" class="h-9 w-9 rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-700 shrink-0" alt="{{ $student->name }}">
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-xs text-slate-800 dark:text-white truncate">{{ $student->name }}</p>
                                    <div class="flex gap-2 text-[10px] font-semibold mt-0.5">
                                        @if($student->late_count > 0)<span class="text-rose-600 dark:text-rose-400">{{ $student->late_count }}x Terlambat</span>@endif
                                        @if($student->alpha_count > 0)<span class="text-red-600 dark:text-red-400">{{ $student->alpha_count }}x Alpa</span>@endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-xs text-slate-400 italic">
                                Tidak ada siswa yang memerlukan perhatian khusus.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Panel Siswa Sedang Izin Keluar -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="material-icons text-sky-500 text-lg">directions_walk</span>
                                Siswa Izin Keluar
                            </h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Siswa yang keluar hari ini</p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300">
                            {{ $studentsOnPermit->count() }}
                        </span>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800/80 max-h-56 overflow-y-auto no-scrollbar">
                        @forelse($studentsOnPermit as $permit)
                            <div class="p-3.5 hover:bg-slate-50 dark:hover:bg-slate-850/50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <p class="font-bold text-xs text-slate-800 dark:text-white truncate">{{ $permit->student->name }}</p>
                                    <span class="text-[10px] text-slate-400 font-semibold">{{ \Carbon\Carbon::parse($permit->time_out)->format('H:i') }}</span>
                                </div>
                                <p class="mt-1 text-[11px] text-slate-600 dark:text-slate-300 italic">"{{ $permit->reason }}"</p>
                            </div>
                        @empty
                            <div class="p-6 text-center text-xs text-slate-400 italic">
                                Tidak ada siswa yang sedang izin keluar.
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>
