{{-- Konten khusus Dasbor Wali Kelas --}}

<div class="space-y-6">

    <!-- Hero Section: Welcome & Quick Access -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- Hero Welcome Card (8 cols) -->
        <div class="lg:col-span-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-850 to-sky-950 text-white p-6 sm:p-7 shadow-lg border border-slate-800/80">
            <div class="absolute -right-10 -bottom-10 w-60 h-60 rounded-full bg-sky-500/10 blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-5 h-full">
                <div class="flex items-center gap-4">
                    <img class="h-16 w-16 rounded-2xl object-cover ring-4 ring-white/10 bg-slate-800 shrink-0" 
                         src="{{ Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&color=0284c7&background=e0f2fe' }}" 
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

    <!-- KELOLA PRESENSI SISWA KELAS (DESKTOP TABLE & MOBILE LIST) -->
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

            <!-- TAMPILAN MOBILE: KARTU LIST (< sm breakpoint) -->
            <div class="block sm:hidden space-y-3">
                @forelse($studentsInClass as $student)
                    @php $attendance = $attendancesToday->get($student->id); @endphp
                    <div class="bg-slate-50 dark:bg-slate-850/60 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-2xs"
                         x-show="(search === '' || {{ json_encode(mb_strtolower($student->name)) }}.includes(search.toLowerCase())) && (statusFilter === 'all' || (statusFilter === 'belum_hadir' && {{ $attendance ? 'false' : 'true' }}) || (statusFilter === 'hadir' && ['tepat_waktu','terlambat'].includes('{{ $attendance->status ?? '' }}')) || (statusFilter === 'sakit' && '{{ $attendance->status ?? '' }}' === 'sakit') || (statusFilter === 'izin' && ['izin','izin_keluar'].includes('{{ $attendance->status ?? '' }}')) || (statusFilter === 'alpa' && '{{ $attendance->status ?? '' }}' === 'alpa'))">
                        
                        <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-200/60 dark:border-slate-800">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="relative group shrink-0">
                                    <img class="h-11 w-11 rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-700" 
                                         src="{{ $student->photo_url }}" 
                                         onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=0284c7&background=e0f2fe';"
                                         alt="{{ $student->name }}">
                                    
                                    <form action="{{ route('teacher.students.update_photo', $student->id) }}" method="POST" enctype="multipart/form-data" class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        @csrf
                                        <label for="student_photo_m_{{ $student->id }}" class="cursor-pointer bg-slate-900/70 rounded-full p-1 text-white">
                                            <span class="material-icons text-xs">photo_camera</span>
                                        </label>
                                        <input type="file" id="student_photo_m_{{ $student->id }}" name="photo" class="hidden" onchange="this.form.submit()">
                                    </form>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-xs text-slate-900 dark:text-white truncate">{{ $student->name }}</h4>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">NIS: {{ $student->nis ?? '-' }}</p>
                                </div>
                            </div>
                            
                            <!-- Status Badge -->
                            <div class="shrink-0">
                                @if($attendance)
                                    @if ($attendance->status === 'tepat_waktu')
                                        <span class="inline-flex items-center gap-1 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 text-[11px] font-bold px-2.5 py-1 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Tepat Waktu
                                        </span>
                                    @elseif ($attendance->status === 'terlambat')
                                        <span class="inline-flex items-center gap-1 bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 text-[11px] font-bold px-2.5 py-1 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Terlambat
                                        </span>
                                    @elseif ($attendance->status === 'izin')
                                        <span class="inline-flex items-center gap-1 bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300 text-[11px] font-bold px-2.5 py-1 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>Izin
                                        </span>
                                    @elseif ($attendance->status === 'sakit')
                                        <span class="inline-flex items-center gap-1 bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 text-[11px] font-bold px-2.5 py-1 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Sakit
                                        </span>
                                    @elseif ($attendance->status === 'alpa')
                                        <span class="inline-flex items-center gap-1 bg-red-100 dark:bg-red-950/60 text-red-800 dark:text-red-300 text-[11px] font-bold px-2.5 py-1 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Alpa
                                        </span>
                                    @elseif ($attendance->status === 'izin_keluar')
                                        <span class="inline-flex items-center gap-1 bg-sky-100 dark:bg-sky-950/60 text-sky-800 dark:text-sky-300 text-[11px] font-bold px-2.5 py-1 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>Izin Keluar
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 bg-slate-200 dark:bg-slate-750 text-slate-600 dark:text-slate-300 text-[11px] font-semibold px-2.5 py-1 rounded-full">
                                        Belum Hadir
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Masuk & Pulang Info -->
                        <div class="grid grid-cols-2 gap-2 my-2.5 bg-white dark:bg-slate-900 p-2.5 rounded-xl text-xs border border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-1.5">
                                <span class="material-icons text-emerald-500 text-base">login</span>
                                <div>
                                    <p class="text-[10px] text-slate-400 font-medium">Jam Masuk</p>
                                    <p class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                        @if($attendance && $attendance->attendance_time && !in_array($attendance->status, ['izin', 'sakit', 'alpa']))
                                            {{ \Carbon\Carbon::parse($attendance->attendance_time)->format('H:i:s') }}
                                        @else
                                            -
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 border-l border-slate-100 dark:border-slate-800 pl-2">
                                <span class="material-icons text-sky-500 text-base">logout</span>
                                <div>
                                    <p class="text-[10px] text-slate-400 font-medium">Jam Pulang</p>
                                    <p class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                        @if($attendance && $attendance->checkout_time)
                                            {{ \Carbon\Carbon::parse($attendance->checkout_time)->format('H:i:s') }}
                                        @else
                                            -
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Mark Actions -->
                        <div class="pt-1">
                            @if(!$attendance)
                                <div class="grid grid-cols-3 gap-2">
                                    <form action="{{ route('teacher.mark.attendance') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="status" value="sakit">
                                        <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                        <button type="submit" class="w-full py-1.5 px-2 text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-100 hover:bg-amber-200 dark:bg-amber-950/60 rounded-xl flex items-center justify-center gap-1 transition-colors">
                                            Sakit
                                        </button>
                                    </form>
                                    <form action="{{ route('teacher.mark.attendance') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="status" value="izin">
                                        <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                        <button type="submit" class="w-full py-1.5 px-2 text-xs font-bold text-purple-700 dark:text-purple-300 bg-purple-100 hover:bg-purple-200 dark:bg-purple-950/60 rounded-xl flex items-center justify-center gap-1 transition-colors">
                                            Izin
                                        </button>
                                    </form>
                                    <form action="{{ route('teacher.mark.attendance') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="status" value="alpa">
                                        <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                        <button type="submit" class="w-full py-1.5 px-2 text-xs font-bold text-red-700 dark:text-red-300 bg-red-100 hover:bg-red-200 dark:bg-red-950/60 rounded-xl flex items-center justify-center gap-1 transition-colors">
                                            Alpa
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[11px] text-slate-400 italic">Status telah dicatat</span>
                                    <form action="{{ route('teacher.mark.attendance') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mereset/menghapus status kehadiran siswa ini?')">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="status" value="hapus">
                                        <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                        <button type="submit" class="py-1 px-2.5 text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 rounded-xl flex items-center gap-1 transition-colors">
                                            <span class="material-icons text-xs">restart_alt</span> Reset
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>

                    </div>
                @empty
                    <div class="text-center p-8 bg-slate-50 dark:bg-slate-850/50 rounded-2xl text-xs text-slate-400">
                        Tidak ada siswa di kelas ini.
                    </div>
                @endforelse
            </div>

            <!-- TAMPILAN DESKTOP: TABEL LEBAR PENUH (≥ sm breakpoint) -->
            <div class="hidden sm:block overflow-x-auto rounded-2xl border border-slate-200/80 dark:border-slate-800">
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

