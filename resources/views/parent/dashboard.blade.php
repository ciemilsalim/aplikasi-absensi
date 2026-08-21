<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Orang Tua', 'url' => route('parent.dashboard')]
                ]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Portal Presensi Orang Tua
                </h1>
            </div>
            
            <div class="hidden sm:flex items-center gap-2">
                <a href="{{ route('parent.guide') }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-white dark:bg-slate-850 border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                    <span class="material-icons text-sky-500 text-base">auto_stories</span>
                    <span>Panduan Penggunaan</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
            
        <!-- Welcome Hero Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-850 to-sky-950 text-white p-6 sm:p-7 shadow-lg border border-slate-800/80">
            <div class="absolute -right-10 -bottom-10 w-60 h-60 rounded-full bg-sky-500/10 blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-sky-500/20 text-[10px] font-bold text-sky-300 border border-sky-400/20 mb-1.5">
                        <span class="material-icons text-xs">family_restroom</span>
                        <span>Portal Orang Tua / Wali</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
                        Selamat Datang, {{ Auth::user()->name }}! 👋
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-xl leading-relaxed">
                        Pantau kehadiran real-time putra/putri Anda di sekolah dan kirimkan pengajuan izin ketidakhadiran dengan cepat.
                    </p>
                </div>

                <a href="{{ route('parent.leave-requests.create') }}" 
                   class="inline-flex items-center justify-center gap-2 px-5 py-3 sm:px-4 sm:py-2.5 min-h-[44px] sm:min-h-[38px] rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm sm:text-xs font-extrabold shadow-md shadow-amber-500/25 transition-all transform hover:scale-105 active:scale-95 shrink-0 w-full sm:w-auto">
                    <span class="material-icons text-base">add_circle</span>
                    <span>Ajukan Izin / Sakit</span>
                </a>
            </div>
        </div>

        {{-- Notifikasi Siswa Belum Absen / Peringatan --}}
        @if($unreadNotifications->isNotEmpty())
            <div class="space-y-3">
                @foreach($unreadNotifications as $notification)
                <div x-data="{ show: true }" x-show="show" x-transition 
                     class="bg-amber-50/90 dark:bg-amber-950/40 border border-amber-300/80 dark:border-amber-700/60 p-4 sm:p-5 rounded-3xl shadow-2xs" role="alert">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 bg-amber-500 text-white rounded-2xl shadow-xs shrink-0">
                            <span class="material-icons text-xl">warning_amber</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="font-bold text-amber-950 dark:text-amber-100 text-xs sm:text-sm">{{ $notification->title }}</h4>
                                <form action="{{ route('notifications.read', $notification) }}" method="POST" class="shrink-0">
                                    @csrf
                                    <button @click="show = false" type="submit" class="text-amber-500 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-200 p-1 rounded-lg transition-colors" title="Tandai sudah dibaca">
                                        <span class="material-icons text-base">close</span>
                                    </button>
                                </form>
                            </div>
                            <p class="text-xs text-amber-800/90 dark:text-amber-200/90 mt-1 leading-relaxed">{{ $notification->message }}</p>
                            
                            <!-- Quick Action Buttons -->
                            <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-amber-200/60 dark:border-amber-800/60">
                                <a href="{{ route('parent.leave-requests.create') }}" 
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors">
                                    <span class="material-icons text-sm">assignment</span>
                                    <span>Ajukan Izin / Sakit</span>
                                </a>
                                <a href="{{ route('chat.index') }}" 
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-slate-750 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700 text-xs font-semibold rounded-xl transition-colors">
                                    <span class="material-icons text-sm text-amber-600 dark:text-amber-400">forum</span>
                                    <span>Chat Wali Kelas</span>
                                </a>
                                <a href="{{ route('chat.admin') }}" 
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-slate-750 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700 text-xs font-semibold rounded-xl transition-colors">
                                    <span class="material-icons text-sm text-sky-600 dark:text-sky-400">support_agent</span>
                                    <span>Chat Admin</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif

        <!-- Pengumuman dari Admin / Sekolah -->
        @if($announcements->isNotEmpty())
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
                    <span class="material-icons text-sky-500 text-base">campaign</span>
                    Pengumuman Sekolah
                </h3>
                @foreach($announcements as $announcement)
                    <div class="bg-sky-50/90 dark:bg-sky-950/40 border border-sky-200/80 dark:border-sky-800/60 p-4 rounded-2xl shadow-2xs">
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-sky-500 text-white rounded-xl shadow-xs shrink-0">
                                <span class="material-icons text-base">info</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs sm:text-sm font-bold text-sky-950 dark:text-sky-100">{{ $announcement->title }}</h4>
                                <span class="text-[10px] text-sky-600 dark:text-sky-400 mt-0.5 block font-semibold">
                                    Dipublikasikan {{ $announcement->published_at->translatedFormat('d F Y') }}
                                </span>
                                <div class="text-xs text-sky-800/90 dark:text-sky-300/90 mt-1 leading-relaxed">
                                    {!! nl2br(e($announcement->content)) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Kartu Data Siswa (Putra / Putri) -->
        @forelse($students as $student)
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                <div class="p-6">
                    
                    <!-- Header Siswa -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-4">
                            <img class="h-16 w-16 rounded-2xl object-cover ring-4 ring-slate-100 dark:ring-slate-800 shrink-0" 
                                 src="{{ $student->photo_url }}" 
                                 onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=0284c7&background=e0f2fe';"
                                 alt="{{ $student->name }}">
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">{{ $student->name }}</h3>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 text-xs font-bold border border-sky-100 dark:border-sky-900/40">
                                        <span class="material-icons text-xs">school</span>
                                        {{ $student->schoolClass->name ?? 'Belum Ada Kelas' }}
                                    </span>
                                    @if($student->nis)
                                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">NIS: {{ $student->nis }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('parent.leave-requests.create') }}" 
                           class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/60 border border-amber-200/60 dark:border-amber-900/40 text-xs font-bold transition-all shrink-0 shadow-2xs">
                            <span class="material-icons text-base text-amber-600 dark:text-amber-400">add_circle_outline</span>
                            <span>Ajukan Izin Baru</span>
                        </a>
                    </div>
                    
                    <!-- Riwayat Kehadiran (5 Hari Terakhir) -->
                    <div class="mt-6">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1.5 mb-3">
                            <span class="material-icons text-sky-500 text-base">history</span>
                            Riwayat Kehadiran (5 Hari Sekolah Terakhir)
                        </h4>
                        
                        <div class="space-y-2.5">
                            @forelse($student->attendances as $attendance)
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 bg-slate-50 dark:bg-slate-850/60 border border-slate-100 dark:border-slate-800 rounded-2xl">
                                    <div>
                                        <p class="font-bold text-xs text-slate-900 dark:text-white">{{ $attendance->attendance_time->translatedFormat('l, d F Y') }}</p>
                                        <div class="flex flex-wrap items-center gap-2 mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                                            @if(!in_array($attendance->status, ['izin', 'sakit', 'alpa']))
                                                <span class="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                                                    <span class="material-icons text-xs">login</span>
                                                    Masuk: {{ $attendance->attendance_time->format('H:i') }}
                                                </span>
                                                <span>•</span>
                                                <span class="inline-flex items-center gap-1 font-semibold text-sky-600 dark:text-sky-400">
                                                    <span class="material-icons text-xs">logout</span>
                                                    Pulang: 
                                                    @if($attendance->checkout_time)
                                                        {{ $attendance->checkout_time->format('H:i') }}
                                                    @elseif($attendance->attendance_time->isToday())
                                                        <span class="text-amber-600 dark:text-amber-400 font-bold">Belum absen pulang</span>
                                                    @else
                                                        <span class="text-rose-600 dark:text-rose-400 font-bold">Tidak absen pulang</span>
                                                    @endif
                                                </span>
                                            @else
                                                <span>Keterangan Status: <strong class="capitalize text-slate-700 dark:text-slate-300">{{ $attendance->status }}</strong></span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="shrink-0">
                                        @if ($attendance->status === 'tepat_waktu')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Hadir Tepat Waktu
                                            </span>
                                        @elseif ($attendance->status === 'terlambat')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                Terlambat
                                            </span>
                                        @elseif ($attendance->status === 'izin')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                                Izin
                                            </span>
                                        @elseif ($attendance->status === 'sakit')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Sakit
                                            </span>
                                        @elseif ($attendance->status === 'alpa')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-100 dark:bg-red-950/60 text-red-800 dark:text-red-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                Alpa
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-xs text-slate-400 italic bg-slate-50 dark:bg-slate-850/40 rounded-2xl">
                                    Belum ada catatan presensi dalam 5 hari terakhir.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Riwayat Ekstrakurikuler --}}
                    <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1.5 mb-3">
                            <span class="material-icons text-sky-500 text-base">military_tech</span>
                            Kegiatan Ekstrakurikuler
                        </h4>
                        
                        @if($student->extracurriculars->isNotEmpty())
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                @foreach($student->extracurriculars as $ekskul)
                                    <div class="bg-slate-50 dark:bg-slate-850/60 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
                                        <h5 class="font-bold text-slate-900 dark:text-white uppercase text-xs tracking-tight mb-2.5 flex items-center justify-between">
                                            <span>{{ $ekskul->name }}</span>
                                            <span class="material-icons text-sky-500 text-sm">verified</span>
                                        </h5>

                                        <div class="space-y-1.5">
                                            @php
                                                $ekskulAttendances = $student->extracurricularAttendances
                                                    ->where('extracurricular_id', $ekskul->id)
                                                    ->take(3);
                                            @endphp
                                            
                                            @forelse($ekskulAttendances as $att)
                                                <div class="flex justify-between items-center text-[11px] py-1 border-b border-slate-200/50 dark:border-slate-800 last:border-0">
                                                    <span class="text-slate-500 dark:text-slate-400 font-medium">{{ \Carbon\Carbon::parse($att->attendance_date)->translatedFormat('d M Y') }}</span>
                                                    <span class="px-2 py-0.5 rounded-full font-bold uppercase text-[9px]
                                                        @if($att->status == 'hadir') bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 @endif
                                                        @if($att->status == 'sakit') bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 @endif
                                                        @if($att->status == 'izin') bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400 @endif
                                                        @if($att->status == 'alpa') bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 @endif
                                                    ">
                                                        {{ $att->status }}
                                                    </span>
                                                </div>
                                            @empty
                                                <p class="text-[11px] text-slate-400 italic">Belum ada riwayat absensi ekskul.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-slate-400 italic">Anak Anda belum terdaftar pada kegiatan ekstrakurikuler.</p>
                        @endif
                    </div>

                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-10 text-center border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="w-14 h-14 rounded-3xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <span class="material-icons text-3xl">no_accounts</span>
                </div>
                <h3 class="text-base font-bold text-slate-800 dark:text-white">Belum Ada Data Siswa Terhubung</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                    Silakan hubungi administrator atau staf tata usaha sekolah untuk menghubungkan akun Anda dengan profil siswa.
                </p>
            </div>
        @endforelse

    </div>
</x-app-layout>

