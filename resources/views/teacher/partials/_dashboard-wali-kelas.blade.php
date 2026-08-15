{{-- Konten ini khusus untuk Dasbor Wali Kelas --}}

<!-- Bagian Welcome dan Akses Cepat -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Welcome Section -->
    <div class="lg:col-span-2 bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 flex items-center gap-6">
            <div class="flex-shrink-0">
                <img class="h-16 w-16 rounded-full object-cover" src="{{ Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&color=7F9CF5&background=EBF4FF' }}" alt="{{ Auth::user()->name }}">
            </div>
            <div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Selamat Datang di Portal Presensi Guru, {{ $teacher->name }}!</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Kelola kehadiran siswa kelas <span class="font-bold text-sky-600 dark:text-sky-400">{{ $class->name }}</span>. Selamat bertugas.</p>
            </div>
        </div>
    </div>

    <!-- PERBAIKAN: Panel Akses Cepat diubah -->
    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Akses Cepat</h3>
        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('scanner') }}" class="flex flex-col items-center justify-center p-4 bg-sky-50 dark:bg-sky-900/50 hover:bg-sky-100 dark:hover:bg-sky-900 rounded-lg transition-colors duration-200">
                <span class="material-icons text-3xl text-sky-600 dark:text-sky-400 mb-1">qr_code_scanner</span>
                <span class="text-sm font-medium text-center text-sky-800 dark:text-sky-300">Scan Hadir</span>
            </a>
            <a href="{{ route('permit.scanner') }}" class="flex flex-col items-center justify-center p-4 bg-amber-50 dark:bg-amber-900/50 hover:bg-amber-100 dark:hover:bg-amber-900 rounded-lg transition-colors duration-200">
                <span class="material-icons text-3xl text-amber-600 dark:text-amber-400 mb-1">logout</span>
                <span class="text-sm font-medium text-center text-amber-800 dark:text-amber-300">Scan Izin</span>
            </a>
        </div>
    </div>
</div>

<!-- REKAPITULASI HARIAN -->
<div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rekapitulasi Harian Kelas {{ $class->name }} - {{ now()->translatedFormat('d F Y') }}</h3>
        <a href="{{ route('teacher.attendance.charts') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Analitik Visual
        </a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
        <div class="bg-green-100 dark:bg-green-900/50 p-4 rounded-lg text-center">
            <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $onTimeCount }}</p>
            <p class="text-sm font-medium text-green-800 dark:text-green-300">Tepat Waktu</p>
        </div>
        <div class="bg-yellow-100 dark:bg-yellow-900/50 p-4 rounded-lg text-center">
            <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $lateCount }}</p>
            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Terlambat</p>
        </div>
        <div class="bg-amber-100 dark:bg-amber-900/50 p-4 rounded-lg text-center">
            <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $sickCount }}</p>
            <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Sakit</p>
        </div>
        <div class="bg-purple-100 dark:bg-purple-900/50 p-4 rounded-lg text-center">
            <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $permitCount }}</p>
            <p class="text-sm font-medium text-purple-800 dark:text-purple-300">Izin</p>
        </div>
        <div class="bg-red-200 dark:bg-red-900/50 p-4 rounded-lg text-center">
            <p class="text-3xl font-bold text-red-700 dark:text-red-400">{{ $alphaCount }}</p>
            <p class="text-sm font-medium text-red-900 dark:text-red-300">Alpa</p>
        </div>
         <div class="bg-gray-100 dark:bg-slate-700 p-4 rounded-lg text-center">
            <p class="text-3xl font-bold text-gray-600 dark:text-gray-300">{{ $noRecordCount }}</p>
            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Tanpa Kabar</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Kolom Kiri: Grafik & Daftar Siswa -->
    <div class="lg:col-span-2 space-y-6">
        <!-- GRAFIK TREN KEHADIRAN MINGGUAN -->
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-medium">Grafik Tren Kehadiran Kelas (7 Hari Terakhir)</h3>
                <div class="h-80 mt-4">
                    <canvas id="weeklyAttendanceChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Daftar Siswa untuk Dikelola (Dual View: Kartu Mobile & Tabel Desktop) -->
        <div x-data="{ search: '', statusFilter: 'all' }" class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 sm:p-6 text-gray-900 dark:text-gray-100">
                <!-- Header & Live Filter Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Kelola Kehadiran Siswa Hari Ini</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pemantauan dan pengisian absensi harian kelas {{ $class->name }}</p>
                    </div>
                    
                    <!-- Search & Filter Controls -->
                    <div class="flex flex-col sm:flex-row gap-2.5 sm:items-center w-full sm:w-auto">
                        <!-- Input Cari Nama -->
                        <div class="relative flex-1 sm:w-56">
                            <span class="material-icons absolute left-3 top-2.5 text-gray-400 text-base">search</span>
                            <input type="text" x-model="search" placeholder="Cari nama siswa..." 
                                   class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>

                        <!-- Dropdown Filter Status -->
                        <select x-model="statusFilter" class="py-1.5 px-3 text-xs rounded-lg border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            <option value="all">Semua Status</option>
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
                        <div class="bg-gray-50 dark:bg-slate-700/60 border border-gray-200 dark:border-slate-700 rounded-xl p-4 transition-all duration-200 shadow-sm"
                             x-show="(search === '' || '{{ strtolower(addslashes($student->name)) }}'.includes(search.toLowerCase())) && (statusFilter === 'all' || (statusFilter === 'belum_hadir' && {{ $attendance ? 'false' : 'true' }}) || (statusFilter === 'hadir' && ['tepat_waktu','terlambat'].includes('{{ $attendance->status ?? '' }}')) || (statusFilter === 'sakit' && '{{ $attendance->status ?? '' }}' === 'sakit') || (statusFilter === 'izin' && ['izin','izin_keluar'].includes('{{ $attendance->status ?? '' }}')) || (statusFilter === 'alpa' && '{{ $attendance->status ?? '' }}' === 'alpa'))">
                            
                            <!-- Header Kartu: Foto, Nama & Status Badge -->
                            <div class="flex items-center justify-between gap-3 pb-3 border-b border-gray-200 dark:border-slate-600">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="relative group flex-shrink-0">
                                        <img class="h-11 w-11 rounded-full object-cover border-2 border-slate-200 dark:border-slate-600 shadow-sm" 
                                             src="{{ $student->photo_url }}" 
                                             onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=7F9CF5&background=EBF4FF';"
                                             alt="{{ $student->name }}">
                                        
                                        <!-- Tombol Ubah Foto -->
                                        <form action="{{ route('teacher.students.update_photo', $student->id) }}" method="POST" enctype="multipart/form-data" class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                            @csrf
                                            <label for="student_photo_m_{{ $student->id }}" class="cursor-pointer bg-black/60 rounded-full p-1 text-white hover:bg-black/80">
                                                <span class="material-icons text-xs">photo_camera</span>
                                            </label>
                                            <input type="file" id="student_photo_m_{{ $student->id }}" name="photo" class="hidden" onchange="this.form.submit()">
                                        </form>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $student->name }}</h4>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400">NIS: {{ $student->nis ?? '-' }}</p>
                                    </div>
                                </div>
                                
                                <!-- Status Badge -->
                                <div class="flex-shrink-0">
                                    @if($attendance)
                                        @if ($attendance->status === 'tepat_waktu')
                                            <span class="inline-flex items-center gap-1 bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 text-xs font-semibold px-2.5 py-1 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Tepat Waktu
                                            </span>
                                        @elseif ($attendance->status === 'terlambat')
                                            <span class="inline-flex items-center gap-1 bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 text-xs font-semibold px-2.5 py-1 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Terlambat
                                            </span>
                                        @elseif ($attendance->status === 'izin')
                                            <span class="inline-flex items-center gap-1 bg-purple-100 dark:bg-purple-900/60 text-purple-800 dark:text-purple-300 text-xs font-semibold px-2.5 py-1 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>Izin
                                            </span>
                                        @elseif ($attendance->status === 'sakit')
                                            <span class="inline-flex items-center gap-1 bg-yellow-100 dark:bg-yellow-900/60 text-yellow-800 dark:text-yellow-300 text-xs font-semibold px-2.5 py-1 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>Sakit
                                            </span>
                                        @elseif ($attendance->status === 'alpa')
                                            <span class="inline-flex items-center gap-1 bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-300 text-xs font-semibold px-2.5 py-1 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Alpa
                                            </span>
                                        @elseif ($attendance->status === 'izin_keluar')
                                            <span class="inline-flex items-center gap-1 bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-300 text-xs font-semibold px-2.5 py-1 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Izin Keluar
                                            </span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center gap-1 bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-300 text-xs font-medium px-2.5 py-1 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Belum Hadir
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Detail Jam Masuk & Pulang -->
                            <div class="grid grid-cols-2 gap-2 my-3 bg-white dark:bg-slate-800 p-2.5 rounded-lg text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="material-icons text-emerald-500 text-base">login</span>
                                    <div>
                                        <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">Jam Masuk</p>
                                        <p class="font-bold text-gray-800 dark:text-gray-200">
                                            @if($attendance && $attendance->attendance_time && !in_array($attendance->status, ['izin', 'sakit', 'alpa']))
                                                {{ \Carbon\Carbon::parse($attendance->attendance_time)->format('H:i:s') }}
                                            @else
                                                -
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 border-l border-gray-200 dark:border-slate-700 pl-2">
                                    <span class="material-icons text-rose-500 text-base">logout</span>
                                    <div>
                                        <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">Jam Pulang</p>
                                        <p class="font-bold text-gray-800 dark:text-gray-200">
                                            @if($attendance && $attendance->checkout_time)
                                                {{ \Carbon\Carbon::parse($attendance->checkout_time)->format('H:i:s') }}
                                            @else
                                                -
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Aksi Absensi Cepat / Reset -->
                            <div class="pt-1">
                                @if(!$attendance)
                                    <div class="grid grid-cols-3 gap-2">
                                        <form action="{{ route('teacher.mark.attendance') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <input type="hidden" name="status" value="sakit">
                                            <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                            <button type="submit" class="w-full py-2 px-3 text-xs font-semibold text-yellow-800 dark:text-yellow-200 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900/60 dark:hover:bg-yellow-800/80 rounded-lg flex items-center justify-center gap-1 transition-colors">
                                                <span class="material-icons text-sm">medical_services</span> Sakit
                                            </button>
                                        </form>
                                        <form action="{{ route('teacher.mark.attendance') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <input type="hidden" name="status" value="izin">
                                            <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                            <button type="submit" class="w-full py-2 px-3 text-xs font-semibold text-purple-800 dark:text-purple-200 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/60 dark:hover:bg-purple-800/80 rounded-lg flex items-center justify-center gap-1 transition-colors">
                                                <span class="material-icons text-sm">assignment</span> Izin
                                            </button>
                                        </form>
                                        <form action="{{ route('teacher.mark.attendance') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <input type="hidden" name="status" value="alpa">
                                            <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                            <button type="submit" class="w-full py-2 px-3 text-xs font-semibold text-rose-800 dark:text-rose-200 bg-rose-100 hover:bg-rose-200 dark:bg-rose-900/60 dark:hover:bg-rose-800/80 rounded-lg flex items-center justify-center gap-1 transition-colors">
                                                <span class="material-icons text-sm">cancel</span> Alpa
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 italic">Status telah dicatat</span>
                                        <form action="{{ route('teacher.mark.attendance') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mereset/menghapus status kehadiran siswa ini?')">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <input type="hidden" name="status" value="hapus">
                                            <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                            <button type="submit" class="py-1.5 px-3 text-xs font-medium text-slate-600 dark:text-slate-300 bg-slate-200 hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500 rounded-lg flex items-center gap-1 transition-colors">
                                                <span class="material-icons text-xs">restart_alt</span> Reset Status
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                        </div>
                    @empty
                        <div class="text-center p-6 bg-gray-50 dark:bg-slate-700/50 rounded-xl text-sm text-gray-500 dark:text-gray-400">
                            Tidak ada siswa di kelas ini.
                        </div>
                    @endforelse
                </div>

                <!-- TAMPILAN DESKTOP: TABEL (≥ sm breakpoint) -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-slate-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">Nama Siswa</th>
                                <th scope="col" class="px-6 py-3 text-center">Jam Masuk</th>
                                <th scope="col" class="px-6 py-3 text-center">Jam Pulang</th>
                                <th scope="col" class="px-6 py-3 text-center">Status</th>
                                <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($studentsInClass as $student)
                                @php $attendance = $attendancesToday->get($student->id); @endphp
                                <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600"
                                    x-show="(search === '' || '{{ strtolower(addslashes($student->name)) }}'.includes(search.toLowerCase())) && (statusFilter === 'all' || (statusFilter === 'belum_hadir' && {{ $attendance ? 'false' : 'true' }}) || (statusFilter === 'hadir' && ['tepat_waktu','terlambat'].includes('{{ $attendance->status ?? '' }}')) || (statusFilter === 'sakit' && '{{ $attendance->status ?? '' }}' === 'sakit') || (statusFilter === 'izin' && ['izin','izin_keluar'].includes('{{ $attendance->status ?? '' }}')) || (statusFilter === 'alpa' && '{{ $attendance->status ?? '' }}' === 'alpa'))">
                                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="relative group">
                                                <img class="h-10 w-10 rounded-full object-cover border-2 border-slate-200 dark:border-slate-700" 
                                                     src="{{ $student->photo_url }}" 
                                                     onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=7F9CF5&background=EBF4FF';"
                                                     alt="{{ $student->name }}">
                                                
                                                <!-- Tombol Ubah Foto (Wali Kelas) -->
                                                <form action="{{ route('teacher.students.update_photo', $student->id) }}" method="POST" enctype="multipart/form-data" class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                    @csrf
                                                    <label for="student_photo_d_{{ $student->id }}" class="cursor-pointer bg-black/50 rounded-full p-1 text-white hover:bg-black/70">
                                                        <span class="material-icons text-base">photo_camera</span>
                                                    </label>
                                                    <input type="file" id="student_photo_d_{{ $student->id }}" name="photo" class="hidden" onchange="this.form.submit()">
                                                </form>
                                            </div>
                                            <span>{{ $student->name }}</span>
                                        </div>
                                    </th>
                                    <td class="px-6 py-4 text-center">
                                        @if($attendance && $attendance->attendance_time && !in_array($attendance->status, ['izin', 'sakit', 'alpa']))
                                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-slate-600 dark:text-slate-300">{{ \Carbon\Carbon::parse($attendance->attendance_time)->format('H:i:s') }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($attendance && $attendance->checkout_time)
                                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-slate-600 dark:text-slate-300">{{ \Carbon\Carbon::parse($attendance->checkout_time)->format('H:i:s') }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($attendance)
                                            @if ($attendance->status === 'tepat_waktu')<span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">Hadir</span>
                                            @elseif ($attendance->status === 'terlambat')<span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-yellow-900 dark:text-yellow-300">Terlambat</span>
                                            @elseif ($attendance->status === 'izin')<span class="bg-purple-100 text-purple-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-purple-900 dark:text-purple-300">Izin</span>
                                            @elseif ($attendance->status === 'sakit')<span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-amber-900 dark:text-amber-300">Sakit</span>
                                            @elseif ($attendance->status === 'alpa')<span class="bg-red-200 text-red-900 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-red-800 dark:text-red-200">Alpa</span>
                                            @elseif ($attendance->status === 'izin_keluar')<span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">Izin Keluar</span>
                                            @endif
                                        @else<span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded-full dark:bg-slate-600 dark:text-slate-300">Belum Hadir</span>@endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if(!$attendance)
                                            <div class="flex items-center justify-center gap-2">
                                                <form action="{{ route('teacher.mark.attendance') }}" method="POST">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><input type="hidden" name="status" value="sakit"><input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}"><button type="submit" title="Sakit" class="px-3 py-1 text-xs font-medium text-amber-800 bg-amber-100 hover:bg-amber-200 rounded-full">S</button></form>
                                                <form action="{{ route('teacher.mark.attendance') }}" method="POST">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><input type="hidden" name="status" value="izin"><input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}"><button type="submit" title="Izin" class="px-3 py-1 text-xs font-medium text-purple-800 bg-purple-100 hover:bg-purple-200 rounded-full">I</button></form>
                                                <form action="{{ route('teacher.mark.attendance') }}" method="POST">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><input type="hidden" name="status" value="alpa"><input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}"><button type="submit" title="Alpa" class="px-3 py-1 text-xs font-medium text-red-800 bg-red-100 hover:bg-red-200 rounded-full">A</button></form>
                                            </div>
                                        @else
                                            <form action="{{ route('teacher.mark.attendance') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mereset/menghapus status kehadiran siswa ini?')">
                                                @csrf
                                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                <input type="hidden" name="status" value="hapus">
                                                <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                                <button type="submit" title="Reset Status" class="p-1 text-xs text-gray-500 hover:text-red-600 rounded">
                                                    <span class="material-icons text-sm">restart_alt</span>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="bg-white border-b dark:bg-slate-800 dark:border-slate-700"><td colspan="5" class="px-6 py-4 text-center">Tidak ada siswa di kelas ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Siswa Perlu Perhatian & Panel Peringatan -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Siswa Perlu Perhatian -->
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Siswa Perlu Perhatian</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Berdasarkan data 30 hari terakhir.</p>
            </div>
            <div class="border-t border-gray-200 dark:border-slate-700">
                <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($studentsForAttentionWali as $student)
                    <li class="p-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-slate-700/50">
                        <span class="inline-block h-10 w-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-600 border border-slate-300 dark:border-slate-600">
                            <img src="{{ $student->photo_url }}" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=7F9CF5&background=EBF4FF';" class="h-full w-full object-cover" alt="{{ $student->name }}">
                        </span>
                        <div>
                            <p class="font-semibold text-sm text-slate-800 dark:text-white">{{ $student->name }}</p>
                            <div class="flex gap-2 text-xs text-gray-500 dark:text-gray-400">
                                @if($student->late_count > 0)<span class="font-medium text-yellow-600">{{ $student->late_count }}x Terlambat</span>@endif
                                @if($student->alpha_count > 0)<span class="font-medium text-red-600">{{ $student->alpha_count }}x Alpa</span>@endif
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="p-4 text-center text-sm text-gray-500 italic">
                        Tidak ada siswa yang memerlukan perhatian khusus saat ini. Kerja bagus!
                    </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- Panel Siswa Izin Keluar -->
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Siswa Sedang Izin Keluar</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Daftar siswa yang keluar pada hari ini dan belum kembali.</p>
            </div>
            <div class="border-t border-gray-200 dark:border-slate-700 @if($studentsOnPermit->isNotEmpty()) max-h-60 overflow-y-auto @endif">
                <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($studentsOnPermit as $permit)
                    <li class="p-4 flex items-start gap-4">
                        <div class="flex-shrink-0 pt-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm text-slate-800 dark:text-white">{{ $permit->student->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Keluar pukul: <span class="font-medium">{{ \Carbon\Carbon::parse($permit->time_out)->format('H:i') }}</span>
                            </p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 italic">
                                "{{ $permit->reason }}"
                            </p>
                        </div>
                    </li>
                    @empty
                    <li class="p-4 text-center text-sm text-gray-500 italic">
                        Tidak ada siswa yang sedang izin keluar.
                    </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- Panel Siswa Belum Absen Pulang -->
        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Siswa Belum Absen Pulang</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Daftar siswa yang sudah masuk tapi belum absen pulang.</p>
            </div>
            <div class="border-t border-gray-200 dark:border-slate-700 @if($studentsNotCheckedOut->isNotEmpty()) max-h-60 overflow-y-auto @endif">
                <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($studentsNotCheckedOut as $attendance)
                    <li class="p-4 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <span class="inline-block h-10 w-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-600 border border-slate-300 dark:border-slate-600">
                                <img src="{{ $attendance->student->photo_url }}" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($attendance->student->name) }}&color=7F9CF5&background=EBF4FF';" class="h-full w-full object-cover" alt="{{ $attendance->student->name }}">
                            </span>
                            <div>
                                <p class="font-semibold text-sm text-slate-800 dark:text-white">{{ $attendance->student->name }}</p>
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Masuk:</span>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ \Carbon\Carbon::parse($attendance->attendance_time)->format('H:i') }}</span>
                        </div>
                    </li>
                    @empty
                    <li class="p-4 text-center text-sm text-gray-500 italic">Semua siswa yang hadir sudah absen pulang.</li>
                    @endforelse
                </ul>
            </div>
        </div>

    </div>
</div>
