<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-10" x-data="{ 
    activeTab: '{{ $defaultTab ?? 'all' }}',
    searchQuery: '',
    openFaq: null,
    matchesSearch(text) {
        if (!this.searchQuery.trim()) return true;
        return text.toLowerCase().includes(this.searchQuery.toLowerCase().trim());
    }
}">

    <!-- ================= HERO BANNER ================= -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-sky-600 via-indigo-600 to-slate-900 p-6 sm:p-10 text-white shadow-xl">
        <div class="absolute -right-12 -bottom-12 opacity-10 pointer-events-none hidden md:block">
            <span class="material-icons text-[260px]">menu_book</span>
        </div>
        <div class="relative z-10 max-w-3xl space-y-3">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/15 backdrop-blur-md text-sky-200 text-xs font-bold border border-white/20 shadow-xs">
                <span class="material-icons text-sm text-sky-300">auto_stories</span>
                <span>Pusat Panduan & Bantuan Terpadu</span>
            </div>
            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-white leading-tight">
                Panduan Penggunaan Aplikasi Presensi & Jurnal
            </h1>
            <p class="text-xs sm:text-sm text-sky-100 leading-relaxed max-w-2xl">
                Petunjuk langkah demi langkah yang mudah dipahami sesuai dengan peran akun Anda di sekolah.
            </p>

            <!-- Search Bar -->
            <div class="pt-2 max-w-xl">
                <div class="relative flex items-center">
                    <span class="material-icons absolute left-3.5 text-slate-400 text-lg">search</span>
                    <input type="text" 
                           x-model="searchQuery"
                           placeholder="Ketik kata kunci (contoh: jurnal, izin, scan, jadwal, supervisi, laporan)..." 
                           class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-white/95 dark:bg-slate-900/90 text-slate-900 dark:text-white placeholder-slate-400 border border-white/30 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 shadow-md backdrop-blur-md transition-all">
                    <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3 text-slate-400 hover:text-slate-600 text-xs">
                        <span class="material-icons text-sm">close</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= NOTIFIKASI PERAN TERLOGIN ================= -->
    @auth
    <div class="p-4 sm:p-5 rounded-3xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200/80 dark:border-sky-800/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-sky-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                <span class="material-icons text-xl">account_circle</span>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Selamat Datang, <strong class="text-slate-800 dark:text-white font-extrabold">{{ Auth::user()->name }}</strong></p>
                <p class="text-xs font-bold text-sky-700 dark:text-sky-300 flex items-center gap-1.5 mt-0.5">
                    <span>Peran Akun:</span>
                    <span class="px-2.5 py-0.5 rounded-full bg-sky-100 dark:bg-sky-900/70 text-[11px] font-extrabold border border-sky-300/60 dark:border-sky-700">{{ $userRoleLabel ?? 'Pengguna' }}</span>
                </p>
            </div>
        </div>
        <div class="text-[11px] text-slate-500 dark:text-slate-400 bg-white/70 dark:bg-slate-900/70 px-3 py-2 rounded-2xl border border-sky-100 dark:border-slate-800">
            💡 <em>Tab panduan di bawah telah otomatis terbuka sesuai peran aktif Anda.</em>
        </div>
    </div>
    @endauth

    <!-- ================= TAB NAVIGASI PERAN ================= -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 scrollbar-none border-b border-slate-200 dark:border-slate-800">
        <button @click="activeTab = 'all'" 
                :class="activeTab === 'all' ? 'bg-sky-600 text-white font-bold shadow-md shadow-sky-600/20' : 'bg-white dark:bg-slate-850 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800'"
                class="flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl text-xs whitespace-nowrap shrink-0 transition-all">
            <span class="material-icons text-base">dashboard_customize</span>
            <span>Semua Panduan</span>
        </button>

        <button @click="activeTab = 'teacher'" 
                :class="activeTab === 'teacher' ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/20' : 'bg-white dark:bg-slate-850 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800'"
                class="flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl text-xs whitespace-nowrap shrink-0 transition-all">
            <span class="material-icons text-base text-indigo-400">school</span>
            <span>Panduan Guru</span>
            @if(isset($defaultTab) && $defaultTab === 'teacher')
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            @endif
        </button>

        <button @click="activeTab = 'supervisor'" 
                :class="activeTab === 'supervisor' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'bg-white dark:bg-slate-850 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800'"
                class="flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl text-xs whitespace-nowrap shrink-0 transition-all">
            <span class="material-icons text-base text-emerald-400">verified</span>
            <span>Wakasek & Kepsek</span>
            @if(isset($defaultTab) && $defaultTab === 'supervisor')
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            @endif
        </button>

        <button @click="activeTab = 'admin'" 
                :class="activeTab === 'admin' ? 'bg-slate-900 text-white dark:bg-sky-500 font-bold shadow-md' : 'bg-white dark:bg-slate-850 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800'"
                class="flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl text-xs whitespace-nowrap shrink-0 transition-all">
            <span class="material-icons text-base text-sky-400">admin_panel_settings</span>
            <span>Admin & Operator</span>
            @if(isset($defaultTab) && $defaultTab === 'admin')
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            @endif
        </button>

        <button @click="activeTab = 'parent'" 
                :class="activeTab === 'parent' ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20' : 'bg-white dark:bg-slate-850 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800'"
                class="flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl text-xs whitespace-nowrap shrink-0 transition-all">
            <span class="material-icons text-base text-purple-400">family_restroom</span>
            <span>Orang Tua / Wali</span>
            @if(isset($defaultTab) && $defaultTab === 'parent')
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            @endif
        </button>

        <button @click="activeTab = 'satpam'" 
                :class="activeTab === 'satpam' ? 'bg-teal-600 text-white font-bold shadow-md shadow-teal-600/20' : 'bg-white dark:bg-slate-850 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800'"
                class="flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl text-xs whitespace-nowrap shrink-0 transition-all">
            <span class="material-icons text-base text-teal-400">security</span>
            <span>Piket & Satpam</span>
            @if(isset($defaultTab) && $defaultTab === 'satpam')
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            @endif
        </button>

        <button @click="activeTab = 'sso'" 
                :class="activeTab === 'sso' ? 'bg-gradient-to-r from-indigo-600 to-sky-600 text-white font-bold shadow-md' : 'bg-white dark:bg-slate-850 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800'"
                class="flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-2xl text-xs whitespace-nowrap shrink-0 transition-all">
            <span class="material-icons text-base text-amber-400">link</span>
            <span>SSO & LMS</span>
        </button>
    </div>

    <!-- ================= 1. PANDUAN GURU ================= -->
    <div x-show="activeTab === 'all' || activeTab === 'teacher'" class="space-y-6">
        <div class="flex items-center gap-3 pb-2 border-b border-slate-200/80 dark:border-slate-800">
            <div class="p-2.5 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                <span class="material-icons text-2xl">school</span>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white">Panduan untuk Guru Mata Pelajaran & Wali Kelas</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Petunjuk praktis pengisian jurnal harian, presensi mengajar, refleksi, dan administrasi kelas</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Card 1: Jurnal Mengajar Terpadu -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border-2 border-sky-500/40 dark:border-sky-500/30 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                 x-show="matchesSearch('jurnal mengajar guru mapel tulis rekap mingguan semester cetak refleksi')">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-xl text-[10px] font-black bg-sky-500 text-white uppercase tracking-wider">Fitur Utama</span>
                        <span class="text-xs font-bold text-slate-400">Langkah 1</span>
                    </div>
                    <h3 class="font-extrabold text-slate-900 dark:text-white text-base">Tulis Jurnal Mengajar Harian</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Isi catatan setiap selesai mengajar agar riwayat materi, tujuan pembelajaran (TP), dan jumlah jam pelajaran (JP) tersimpan rapi.
                    </p>
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-850 rounded-2xl text-[11px] text-slate-700 dark:text-slate-300 space-y-2 border border-slate-200/60 dark:border-slate-800">
                        <div class="font-bold text-sky-600 dark:text-sky-400 flex items-center gap-1">
                            <span class="material-icons text-sm">edit_note</span> Cara Pengisian:
                        </div>
                        <ol class="list-decimal list-inside space-y-1 pl-0.5">
                            <li>Buka menu <strong>Jurnal Mengajar &rarr; Tulis Jurnal Baru</strong>.</li>
                            <li>Pilih <strong>Jadwal Pelajaran / Kelas</strong> (otomatis terurut dari Senin s/d Sabtu).</li>
                            <li>Tuliskan <strong>Materi Pokok</strong> dan <strong>Tujuan Pembelajaran (TP)</strong>.</li>
                            <li>Pilih <strong>Bentuk Asesmen</strong> (Formatif / Sumatif) serta <strong>Catatan Refleksi & Tindak Lanjut</strong>.</li>
                            <li>Klik <strong>Simpan Jurnal Mengajar</strong>.</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Card 2: Rekap Mingguan, Semester & Cetak Dokumen -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                 x-show="matchesSearch('rekap mingguan semester cetak jurnal landscape a4 pkg supervisi')">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800">Rekap & Cetak</span>
                        <span class="text-xs font-bold text-slate-400">Langkah 2</span>
                    </div>
                    <h3 class="font-extrabold text-slate-900 dark:text-white text-base">Rekap Semester & Cetak Jurnal</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Lihat rekapitulasi ketercapaian target JP, buat refleksi 6 aspek evaluasi diri di akhir semester, dan cetak berkas fisik resmi.
                    </p>
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-850 rounded-2xl text-[11px] text-slate-700 dark:text-slate-300 space-y-2 border border-slate-200/60 dark:border-slate-800">
                        <div class="font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                            <span class="material-icons text-sm">print</span> Menu Pendukung:
                        </div>
                        <ul class="list-disc list-inside space-y-1 pl-0.5">
                            <li><strong>Rekap Mingguan:</strong> Pantau frekuensi tatap muka dan keterlaksanaan TP per minggu.</li>
                            <li><strong>Rekap Semester:</strong> Evaluasi perbandingan JP rencana vs realisasi per kelas.</li>
                            <li><strong>Refleksi Guru:</strong> Isi 6 aspek evaluasi pembelajaran semester.</li>
                            <li><strong>Cetak Jurnal:</strong> Cetak dokumen landscape A4 resmi ber-Kop sekolah dan tanda tangan Kepala Sekolah.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Card 3: Presensi Siswa di Kelas -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                 x-show="matchesSearch('presensi mapel kelas scanner scan qr kartu siswa bolos hadir sakit izin')">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800">Di Dalam Kelas</span>
                        <span class="text-xs font-bold text-slate-400">Langkah 3</span>
                    </div>
                    <h3 class="font-extrabold text-slate-900 dark:text-white text-base">Presensi Mapel & Scan QR Siswa</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Catat kehadiran siswa saat jam pelajaran berlangsung menggunakan kamera pemindai kartu atau input manual langsung.
                    </p>
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-850 rounded-2xl text-[11px] text-slate-700 dark:text-slate-300 space-y-2 border border-slate-200/60 dark:border-slate-800">
                        <div class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <span class="material-icons text-sm">qr_code_scanner</span> Cara Presensi Mapel:
                        </div>
                        <ol class="list-decimal list-inside space-y-1 pl-0.5">
                            <li>Buka <strong>Dasbor Guru Mapel</strong> &rarr; klik tombol <strong>Mulai Presensi Kelas</strong>.</li>
                            <li>Pindai QR Code kartu siswa dengan kamera atau klik nama siswa yang tidak hadir.</li>
                            <li>Sistem otomatis menandai jika siswa izin keluar atau bolos dari kelas.</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Card 4: Absensi Mandiri Guru -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                 x-show="matchesSearch('absensi guru mandiri gps scan kehadiran masuk pulang')">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800">Kehadiran Guru</span>
                        <span class="text-xs font-bold text-slate-400">Harian</span>
                    </div>
                    <h3 class="font-extrabold text-slate-900 dark:text-white text-base">Absen Mandiri Guru (GPS / Wajah)</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Lakukan presensi kedatangan dan kepulangan guru dari perangkat HP masing-masing di area radius sekolah.
                    </p>
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-850 rounded-2xl text-[11px] text-slate-700 dark:text-slate-300 space-y-2 border border-slate-200/60 dark:border-slate-800">
                        <div class="font-bold text-amber-600 dark:text-amber-400 flex items-center gap-1">
                            <span class="material-icons text-sm">my_location</span> Langkah:
                        </div>
                        <ol class="list-decimal list-inside space-y-1 pl-0.5">
                            <li>Buka menu <strong>Absensi Saya</strong>.</li>
                            <li>Klik tombol <strong>Absen Masuk</strong> atau <strong>Absen Pulang</strong>.</li>
                            <li>Pastikan GPS HP aktif dan berada di lingkungan SMPN 1 Biau.</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Card 5: Wali Kelas & Pengajuan Izin -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                 x-show="matchesSearch('wali kelas persetujuan izin siswa rekap kehadiran riwayat chat')">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200/60 dark:border-purple-800">Tugas Tambahan</span>
                        <span class="text-xs font-bold text-slate-400">Wali Kelas</span>
                    </div>
                    <h3 class="font-extrabold text-slate-900 dark:text-white text-base">Dasbor & Persetujuan Izin Siswa</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Bagi Bapak/Ibu yang bertugas sebagai Wali Kelas, Anda dapat memantau kehadiran siswa satu rombel dan memvalidasi izin orang tua.
                    </p>
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-850 rounded-2xl text-[11px] text-slate-700 dark:text-slate-300 space-y-2 border border-slate-200/60 dark:border-slate-800">
                        <ul class="list-disc list-inside space-y-1 pl-0.5">
                            <li>Buka menu <strong>Wali Kelas &rarr; Pengajuan Izin</strong> untuk menyetujui surat izin/sakit.</li>
                            <li>Buka <strong>Riwayat Kehadiran</strong> untuk melihat grafik persentase kehadiran kelas binaan Anda.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Card 6: SSO LMS Mokopani -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                 x-show="matchesSearch('sso lms mokopani modul ajar rpp materi tugas')">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-200/60 dark:border-sky-800">Integrasi</span>
                        <span class="text-xs font-bold text-slate-400">SSO</span>
                    </div>
                    <h3 class="font-extrabold text-slate-900 dark:text-white text-base">Akses Cepat LMS Mokopani</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Masuk ke portal Modul Ajar AI, bank materi, dan penugasan kelas tanpa perlu login berulang kali.
                    </p>
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-850 rounded-2xl text-[11px] text-slate-700 dark:text-slate-300 space-y-2 border border-slate-200/60 dark:border-slate-800">
                        <p>Klik tombol <strong>LMS Mokopani</strong> di bagian bawah sidebar untuk langsung berpindah ke platform pembelajaran sekolah.</p>
                    </div>
                </div>
                @auth
                    @if(auth()->user()->hasAnyRole(['teacher', 'admin', 'operator']))
                        <div class="pt-3">
                            <a href="{{ route('sso.lms') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm transition-all w-full justify-center">
                                <span class="material-icons text-sm">school</span>
                                <span>Buka LMS Mokopani</span>
                            </a>
                        </div>
                    @endif
                @endauth
            </div>
        </div>
    </div>

    <!-- ================= 2. PANDUAN WAKASEK & KEPSEK (SUPERVISI) ================= -->
    <div x-show="activeTab === 'all' || activeTab === 'supervisor'" class="space-y-6">
        <div class="flex items-center gap-3 pb-2 border-b border-slate-200/80 dark:border-slate-800">
            <div class="p-2.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                <span class="material-icons text-2xl">verified</span>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white">Panduan Supervisi untuk Wakasek Kurikulum & Kepala Sekolah</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Monitoring pelaksanaan kurikulum, evaluasi jurnal mengajar seluruh guru, dan verifikasi berkas</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Card 1: Monitoring & Filter Jurnal -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border-2 border-emerald-500/30 dark:border-emerald-500/20 shadow-sm hover:shadow-md transition-all space-y-3"
                 x-show="matchesSearch('supervisi jurnal mengajar guru monitoring filter kelas mapel')">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-1 rounded-xl text-[10px] font-black bg-emerald-500 text-white uppercase tracking-wider">Supervisi</span>
                    <span class="text-xs font-bold text-slate-400">Langkah 1</span>
                </div>
                <h3 class="font-extrabold text-slate-900 dark:text-white text-base">Monitoring Jurnal Mengajar Guru</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Pantau kepatuhan dan keaktifan guru mata pelajaran dalam mencatat jurnal pembelajaran secara real-time.
                </p>
                <div class="p-3.5 bg-slate-50 dark:bg-slate-850 rounded-2xl text-[11px] text-slate-700 dark:text-slate-300 space-y-2 border border-slate-200/60 dark:border-slate-800">
                    <div class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                        <span class="material-icons text-sm">filter_list</span> Fitur Monitoring:
                    </div>
                    <ol class="list-decimal list-inside space-y-1 pl-0.5">
                        <li>Buka menu <strong>Supervisi Jurnal</strong> di sidebar kiri.</li>
                        <li>Gunakan filter untuk menyaring berdasarkan <strong>Nama Guru, Mata Pelajaran, Rombel Kelas, Bulan, atau Status</strong>.</li>
                        <li>Lihat kartu statistik: Total Jurnal, Telah Diverifikasi, dan Guru Aktif Menulis.</li>
                    </ol>
                </div>
            </div>

            <!-- Card 2: Validasi & Catatan Supervisor -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-all space-y-3"
                 x-show="matchesSearch('verifikasi validasi batch catatan supervisor masal')">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-200/60 dark:border-sky-800">Verifikasi</span>
                    <span class="text-xs font-bold text-slate-400">Langkah 2</span>
                </div>
                <h3 class="font-extrabold text-slate-900 dark:text-white text-base">Verifikasi Mandiri & Massal</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Beri status validasi resmi serta masukan pembinaan pembelajaran pada setiap sesi jurnal yang telah diperiksa.
                </p>
                <div class="p-3.5 bg-slate-50 dark:bg-slate-850 rounded-2xl text-[11px] text-slate-700 dark:text-slate-300 space-y-2 border border-slate-200/60 dark:border-slate-800">
                    <div class="font-bold text-sky-600 dark:text-sky-400 flex items-center gap-1">
                        <span class="material-icons text-sm">check_circle</span> Cara Verifikasi:
                    </div>
                    <ul class="list-disc list-inside space-y-1 pl-0.5">
                        <li><strong>Verifikasi Satuan:</strong> Klik tombol checklist pada baris jurnal dan berikan catatan pembinaan.</li>
                        <li><strong>Verifikasi Massal (Batch):</strong> Centang beberapa jurnal sekaligus &rarr; klik tombol <strong>Verifikasi Terpilih</strong>.</li>
                    </ul>
                </div>
            </div>

            <!-- Card 3: Evaluasi Refleksi & Ketercapaian JP -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-all space-y-3"
                 x-show="matchesSearch('evaluasi refleksi semester target jam pelajaran kurikulum pembinaan')">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800">Evaluasi</span>
                    <span class="text-xs font-bold text-slate-400">Langkah 3</span>
                </div>
                <h3 class="font-extrabold text-slate-900 dark:text-white text-base">Tinjauan Refleksi Semester Guru</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Periksa capaian 6 aspek refleksi guru dan perbandingan jam pelajaran (JP) untuk bahan laporan evaluasi kurikulum sekolah.
                </p>
                <div class="p-3.5 bg-slate-50 dark:bg-slate-850 rounded-2xl text-[11px] text-slate-700 dark:text-slate-300 space-y-2 border border-slate-200/60 dark:border-slate-800">
                    <ul class="list-disc list-inside space-y-1 pl-0.5">
                        <li>Klik nama guru pada tabel supervisi untuk membuka berkas rekapitulasi individual.</li>
                        <li>Tinjau kendala pembelajaran, hal-hal baik, dan rencana tindak lanjut semester berikutnya.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= 3. PANDUAN ADMIN & OPERATOR ================= -->
    <div x-show="activeTab === 'all' || activeTab === 'admin'" class="space-y-6">
        <div class="flex items-center gap-3 pb-2 border-b border-slate-200/80 dark:border-slate-800">
            <div class="p-2.5 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400">
                <span class="material-icons text-2xl">admin_panel_settings</span>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white">Panduan untuk Administrator & Operator</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Manajemen master data, geofencing lokasi, persetujuan izin, dan pelaporan</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Card Master Data -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3"
                 x-show="matchesSearch('admin master data siswa guru jadwal kelas sipada')">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-200/60 dark:border-sky-800">Data Master</span>
                    <span class="text-xs font-bold text-slate-400">Admin</span>
                </div>
                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Kelola Siswa, Guru & Jadwal</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Sinkronisasi data otomatis dengan SIPADA atau impor berkas Excel untuk memperbarui data siswa, guru, rombel, dan jadwal pelajaran aktif.
                </p>
            </div>

            <!-- Card Radius & GPS -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3"
                 x-show="matchesSearch('admin radius gps geofencing lokasi peta sekolah jam kerja')">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800">Lokasi & Jam</span>
                    <span class="text-xs font-bold text-slate-400">Admin</span>
                </div>
                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Pengaturan Titik Radius Sekolah</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Tentukan titik koordinat SMP Negeri 1 Biau dan toleransi radius (meter) pada peta agar presensi mandiri guru hanya valid di lingkungan sekolah.
                </p>
            </div>

            <!-- Card Laporan & Rekap -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3"
                 x-show="matchesSearch('admin rekap laporan excel pdf cetak kehadiran guru siswa')">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200/60 dark:border-purple-800">Laporan</span>
                    <span class="text-xs font-bold text-slate-400">Admin</span>
                </div>
                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Ekspor Laporan & Arsip Excel</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Cetak dan unduh rekapitulasi presensi harian, bulanan, atau triwulan siswa dan guru dalam format Excel siap pakai untuk rapat dinas.
                </p>
            </div>
        </div>
    </div>

    <!-- ================= 4. PANDUAN ORANG TUA / WALI ================= -->
    <div x-show="activeTab === 'all' || activeTab === 'parent'" class="space-y-6">
        <div class="flex items-center gap-3 pb-2 border-b border-slate-200/80 dark:border-slate-800">
            <div class="p-2.5 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400">
                <span class="material-icons text-2xl">family_restroom</span>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white">Panduan untuk Orang Tua & Wali Murid</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Pantau kehadiran putra/putri Anda dan kirim surat izin sakit secara digital</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Status Warna -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4"
                 x-show="matchesSearch('orang tua status hadir terlambat sakit izin alpa anak')">
                <h3 class="font-extrabold text-slate-900 dark:text-white text-sm sm:text-base flex items-center gap-2">
                    <span class="material-icons text-emerald-500 text-lg">verified</span>
                    Arti Status & Warna Kehadiran
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center gap-2.5">
                        <span class="px-2.5 py-0.5 rounded-full font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">Hadir</span>
                        <span class="text-slate-600 dark:text-slate-400">Tepat waktu di sekolah</span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center gap-2.5">
                        <span class="px-2.5 py-0.5 rounded-full font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300">Terlambat</span>
                        <span class="text-slate-600 dark:text-slate-400">Scan lewat batas waktu</span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center gap-2.5">
                        <span class="px-2.5 py-0.5 rounded-full font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300">Izin</span>
                        <span class="text-slate-600 dark:text-slate-400">Ada surat permohonan</span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center gap-2.5">
                        <span class="px-2.5 py-0.5 rounded-full font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">Sakit</span>
                        <span class="text-slate-600 dark:text-slate-400">Surat dokter terlampir</span>
                    </div>
                </div>
            </div>

            <!-- Cara Izin -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4"
                 x-show="matchesSearch('orang tua ajukan izin sakit foto surat dokter langkah')">
                <h3 class="font-extrabold text-slate-900 dark:text-white text-sm sm:text-base flex items-center gap-2">
                    <span class="material-icons text-purple-500 text-lg">assignment</span>
                    Langkah Mengajukan Surat Izin / Sakit
                </h3>
                <ol class="list-decimal list-inside space-y-2 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    <li>Buka halaman dasbor orang tua, lalu klik <strong>Ajukan Izin atau Sakit</strong>.</li>
                    <li>Pilih nama siswa & pilih jenis keterangan (<strong>Izin</strong> atau <strong>Sakit</strong>).</li>
                    <li>Tentukan rentang tanggal dan tuliskan alasan singkat.</li>
                    <li>Lampirkan foto surat dokter / surat izin bertanda tangan orang tua.</li>
                    <li>Klik <strong>Simpan Pengajuan</strong> dan tunggu persetujuan dari sekolah.</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- ================= 5. PANDUAN PIKET & SATPAM ================= -->
    <div x-show="activeTab === 'all' || activeTab === 'satpam'" class="space-y-6">
        <div class="flex items-center gap-3 pb-2 border-b border-slate-200/80 dark:border-slate-800">
            <div class="p-2.5 rounded-2xl bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400">
                <span class="material-icons text-2xl">security</span>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white">Panduan Petugas Piket & Satpam</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Operasional pemindai gerbang masuk/pulang dan validasi izin keluar siswa</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3"
                 x-show="matchesSearch('kiosk scanner qr gerbang masuk pulang kartu satpam')">
                <div class="flex items-center gap-2 font-bold text-slate-900 dark:text-white text-sm">
                    <span class="material-icons text-teal-500">qr_code_scanner</span>
                    Pemindai Kiosk Masuk & Pulang
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Buka menu <strong>Scan Masuk/Pulang</strong> pada layar tablet/kiosk gerbang. Siswa mendekatkan kartu QR ke kamera. Sistem otomatis mengeluarkan audio nama siswa dan mencatat presensi secara instan.
                </p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3"
                 x-show="matchesSearch('izin keluar dispensasi sementara meninggalkan sekolah satpam piket')">
                <div class="flex items-center gap-2 font-bold text-slate-900 dark:text-white text-sm">
                    <span class="material-icons text-indigo-500">assignment</span>
                    Pemindai Izin Keluar Sekolah
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Untuk siswa yang meninggalkan sekolah di tengah jam belajar (berobat / lomba), pindai kartu siswa di menu <strong>Scan Izin Keluar</strong>. Sistem memvalidasi surat persetujuan guru piket sebelum mengizinkan siswa keluar gerbang.
                </p>
            </div>
        </div>
    </div>

    <!-- ================= 6. TANYA JAWAB (FAQ) ================= -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6">
        <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400">
                <span class="material-icons text-2xl">quiz</span>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white">Pertanyaan Sering Ditanyakan (FAQ)</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Jawaban ringkas untuk kendala yang sering ditemui</p>
            </div>
        </div>

        <div class="space-y-3">
            <!-- FAQ 1: Jurnal Mengajar Cetak -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden transition-colors"
                 x-show="matchesSearch('jurnal cetak format landscape a4 pkg kop tanda tangan kepala sekolah')">
                <button @click="openFaq = (openFaq === 1 ? null : 1)" class="w-full p-4 text-left font-bold text-xs sm:text-sm text-slate-900 dark:text-white bg-slate-50/50 dark:bg-slate-850/50 hover:bg-slate-100 dark:hover:bg-slate-800 flex justify-between items-center transition-colors">
                    <span>Q: Bagaimana cara mencetak berkas Jurnal Mengajar resmi untuk keperluan PKG / Supervisi?</span>
                    <span class="material-icons text-base transition-transform duration-200" :class="{ 'rotate-180': openFaq === 1 }">expand_more</span>
                </button>
                <div x-show="openFaq === 1" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800 leading-relaxed space-y-1.5 bg-white dark:bg-slate-900">
                    <p>Buka menu <strong>Jurnal Mengajar</strong>, lalu klik tombol <strong>Cetak Jurnal</strong> di sudut kanan atas. Berkas dicetak dalam format landscape A4 lengkap dengan Kop Resmi SMP Negeri 1 Biau, Identitas Guru, Catatan Pelaksanaan, Rekapitulasi, Refleksi, dan Lembar Tanda Tangan Pengesahan Kepala Sekolah.</p>
                </div>
            </div>

            <!-- FAQ 2: Supervisi -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden transition-colors"
                 x-show="matchesSearch('supervisi siapa yang berhak memvalidasi wakasek kurikulum kepala sekolah')">
                <button @click="openFaq = (openFaq === 2 ? null : 2)" class="w-full p-4 text-left font-bold text-xs sm:text-sm text-slate-900 dark:text-white bg-slate-50/50 dark:bg-slate-850/50 hover:bg-slate-100 dark:hover:bg-slate-800 flex justify-between items-center transition-colors">
                    <span>Q: Siapa saja yang berhak melakukan supervisi dan memvalidasi Jurnal Guru?</span>
                    <span class="material-icons text-base transition-transform duration-200" :class="{ 'rotate-180': openFaq === 2 }">expand_more</span>
                </button>
                <div x-show="openFaq === 2" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800 leading-relaxed space-y-1.5 bg-white dark:bg-slate-900">
                    <p>Fitur <strong>Supervisi Jurnal</strong> dapat diakses oleh <strong>Wakasek Kurikulum, Kepala Sekolah, dan Administrator</strong>. Supervisor dapat memeriksa kepatuhan jam mengajar, memberi catatan perbaikan, serta menyetujui jurnal secara individual maupun serentak (massal).</p>
                </div>
            </div>

            <!-- FAQ 3: Radius GPS -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden transition-colors"
                 x-show="matchesSearch('gps radius lokasi di luar jangkauan guru')">
                <button @click="openFaq = (openFaq === 3 ? null : 3)" class="w-full p-4 text-left font-bold text-xs sm:text-sm text-slate-900 dark:text-white bg-slate-50/50 dark:bg-slate-850/50 hover:bg-slate-100 dark:hover:bg-slate-800 flex justify-between items-center transition-colors">
                    <span>Q: Mengapa muncul keterangan "Di Luar Radius Sekolah" saat absen mandiri guru?</span>
                    <span class="material-icons text-base transition-transform duration-200" :class="{ 'rotate-180': openFaq === 3 }">expand_more</span>
                </button>
                <div x-show="openFaq === 3" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800 leading-relaxed space-y-1 bg-white dark:bg-slate-900">
                    <p>Pastikan Anda telah berada di area sekolah dan mengaktifkan izin GPS akurasi tinggi pada browser HP. Buka aplikasi Google Maps sejenak agar sinyal satelit terkunci sebelum menekan tombol absen.</p>
                </div>
            </div>

            <!-- FAQ 4: Kamera Scanner -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden transition-colors"
                 x-show="matchesSearch('kamera scanner tidak menyala izin camera permission')">
                <button @click="openFaq = (openFaq === 4 ? null : 4)" class="w-full p-4 text-left font-bold text-xs sm:text-sm text-slate-900 dark:text-white bg-slate-50/50 dark:bg-slate-850/50 hover:bg-slate-100 dark:hover:bg-slate-800 flex justify-between items-center transition-colors">
                    <span>Q: Kamera scanner tidak mau menyala di browser HP / Komputer?</span>
                    <span class="material-icons text-base transition-transform duration-200" :class="{ 'rotate-180': openFaq === 4 }">expand_more</span>
                </button>
                <div x-show="openFaq === 4" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800 leading-relaxed space-y-1 bg-white dark:bg-slate-900">
                    <p>Klik ikon gembok/setelan di sebelah kiri bilah URL browser Anda &rarr; pilih <em>Permissions</em> &rarr; ubah <strong>Camera</strong> menjadi <strong>Allow / Izinkan</strong>, lalu muat ulang halaman.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= BANTUAN TEKNIS ================= -->
    <div class="rounded-3xl p-6 sm:p-8 bg-slate-100 dark:bg-slate-850 border border-slate-200 dark:border-slate-800 text-center space-y-3">
        <h3 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">Butuh Bantuan Lebih Lanjut?</h3>
        <p class="text-xs text-slate-600 dark:text-slate-400 max-w-xl mx-auto">
            Jika Bapak/Ibu mengalami kendala teknis akun atau jadwal, silakan hubungi Tim IT / Administrator SMP Negeri 1 Biau.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
            @auth
                <a href="{{ route('chat.admin') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold shadow-md shadow-sky-600/20 transition-all">
                    <span class="material-icons text-base">support_agent</span>
                    <span>Hubungi Admin Sekolah</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold shadow-md shadow-sky-600/20 transition-all">
                    <span class="material-icons text-base">login</span>
                    <span>Masuk untuk Bantuan</span>
                </a>
            @endauth
        </div>
    </div>

</div>
