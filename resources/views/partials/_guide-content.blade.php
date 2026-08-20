<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 sm:space-y-12" x-data="{ 
    activeTab: '{{ $defaultTab ?? 'all' }}',
    searchQuery: '',
    openFaq: null,
    matchesSearch(text) {
        if (!this.searchQuery.trim()) return true;
        return text.toLowerCase().includes(this.searchQuery.toLowerCase().trim());
    }
}">

    <!-- ================= HERO HEADER ================= -->
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
                Panduan Penggunaan Aplikasi Presensi
            </h1>
            <p class="text-xs sm:text-sm text-sky-100 leading-relaxed max-w-2xl">
                Temukan petunjuk langkah demi langkah, tips penggunaan fitur, serta jawaban atas pertanyaan umum sesuai dengan peran akun Anda di sekolah.
            </p>

            <!-- Search Bar -->
            <div class="pt-2 max-w-xl">
                <div class="relative flex items-center">
                    <span class="material-icons absolute left-3.5 text-slate-400 text-lg">search</span>
                    <input type="text" 
                           x-model="searchQuery"
                           placeholder="Cari panduan (contoh: izin, scan, jadwal, SSO, guru, laporan)..." 
                           class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-white/95 dark:bg-slate-900/90 text-slate-900 dark:text-white placeholder-slate-400 border border-white/30 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 shadow-md backdrop-blur-md transition-all">
                    <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3 text-slate-400 hover:text-slate-600 text-xs">
                        <span class="material-icons text-sm">close</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= ROLE TABS ================= -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 no-scrollbar border-b border-slate-200 dark:border-slate-800">
        <button @click="activeTab = 'all'" 
                :class="activeTab === 'all' ? 'bg-sky-600 text-white font-bold shadow-md shadow-sky-600/20' : 'bg-white dark:bg-slate-850 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs whitespace-nowrap transition-all">
            <span class="material-icons text-base">dashboard_customize</span>
            <span>Semua Panduan</span>
        </button>

        <button @click="activeTab = 'teacher'" 
                :class="activeTab === 'teacher' ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/20' : 'bg-white dark:bg-slate-850 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs whitespace-nowrap transition-all">
            <span class="material-icons text-base text-indigo-400">school</span>
            <span>Panduan Guru</span>
        </button>

        <button @click="activeTab = 'admin'" 
                :class="activeTab === 'admin' ? 'bg-slate-900 text-white dark:bg-sky-500 font-bold shadow-md' : 'bg-white dark:bg-slate-850 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs whitespace-nowrap transition-all">
            <span class="material-icons text-base text-sky-400">admin_panel_settings</span>
            <span>Panduan Admin & Operator</span>
        </button>

        <button @click="activeTab = 'parent'" 
                :class="activeTab === 'parent' ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20' : 'bg-white dark:bg-slate-850 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs whitespace-nowrap transition-all">
            <span class="material-icons text-base text-purple-400">family_restroom</span>
            <span>Panduan Orang Tua / Wali</span>
        </button>

        <button @click="activeTab = 'satpam'" 
                :class="activeTab === 'satpam' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'bg-white dark:bg-slate-850 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs whitespace-nowrap transition-all">
            <span class="material-icons text-base text-emerald-400">security</span>
            <span>Panduan Piket / Satpam</span>
        </button>

        <button @click="activeTab = 'sso'" 
                :class="activeTab === 'sso' ? 'bg-gradient-to-r from-indigo-600 to-sky-600 text-white font-bold shadow-md' : 'bg-white dark:bg-slate-850 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs whitespace-nowrap transition-all">
            <span class="material-icons text-base text-amber-400">link</span>
            <span>SSO & Ekosistem SIASEK</span>
        </button>
    </div>

    <!-- ================= SECTION: PANDUAN GURU ================= -->
    <div x-show="activeTab === 'all' || activeTab === 'teacher'" class="space-y-6">
        <div class="flex items-center gap-3 pb-2 border-b border-slate-200/80 dark:border-slate-800">
            <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                <span class="material-icons text-2xl">school</span>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white">Panduan Penggunaan untuk Guru</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Petunjuk presensi mata pelajaran, absensi mandiri, jurnal mengajar, dan ekstrakurikuler</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Card 1: Presensi Mandiri Guru -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow"
                 x-show="matchesSearch('absensi guru mandiri gps scan kehadiran')">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-bold text-xs flex items-center justify-center">1</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-900">Kehadiran Guru</span>
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">Presensi Mandiri Guru (GPS / Kiosk)</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Guru dapat melakukan absensi masuk dan pulang melalui menu <strong>Absensi Saya</strong>. Pastikan GPS HP aktif jika menggunakan verifikasi radius sekolah, atau scan kartu di Kiosk Sekolah.
                    </p>
                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-2xl text-[11px] text-slate-600 dark:text-slate-400 space-y-1.5">
                        <div class="flex items-center gap-1.5 font-semibold text-slate-800 dark:text-slate-200">
                            <span class="material-icons text-xs text-emerald-500">check_circle</span> Langkah:
                        </div>
                        <ol class="list-decimal list-inside space-y-1 pl-1">
                            <li>Buka menu <strong>Absensi Saya</strong> di sidebar</li>
                            <li>Tekan tombol <strong>Absen Masuk</strong> atau <strong>Absen Pulang</strong></li>
                            <li>Izinkan akses lokasi GPS browser perangkat Anda</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Card 2: Presensi Mata Pelajaran -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow"
                 x-show="matchesSearch('presensi mata pelajaran mapel kelas scanner qr siswa bolos')">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-full bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 font-bold text-xs flex items-center justify-center">2</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-200/60 dark:border-sky-900">Mapel di Kelas</span>
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">Presensi Mata Pelajaran di Kelas</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Saat mengajar di kelas, guru membuka jadwal pelajaran aktif, lalu memindai kartu siswa menggunakan kamera HP/Laptop atau melakukan centang presensi secara massal.
                    </p>
                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-2xl text-[11px] text-slate-600 dark:text-slate-400 space-y-1.5">
                        <div class="flex items-center gap-1.5 font-semibold text-slate-800 dark:text-slate-200">
                            <span class="material-icons text-xs text-emerald-500">check_circle</span> Fitur Utama:
                        </div>
                        <ul class="list-disc list-inside space-y-1 pl-1">
                            <li>Pemindai QR Code kamera depan/belakang</li>
                            <li>Deteksi status: Hadir, Izin, Sakit, Bolos, Alpa</li>
                            <li>Tanda peringatan jika siswa izin meninggalkan sekolah</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Card 3: Jurnal Mengajar Guru Mata Pelajaran (BARU) -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border-2 border-sky-500/30 dark:border-sky-500/20 shadow-md flex flex-col justify-between hover:shadow-lg transition-shadow relative overflow-hidden"
                 x-show="matchesSearch('jurnal mengajar guru mapel harian rekap mingguan semester refleksi asesmen cetak supervisi')">
                <div class="absolute top-0 right-0">
                    <span class="inline-flex items-center px-3 py-1 rounded-bl-2xl text-[9px] font-extrabold uppercase bg-emerald-500 text-white shadow-2xs">Fitur Baru</span>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 font-bold text-xs flex items-center justify-center">3</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-900">Jurnal Mengajar</span>
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">Jurnal Mengajar Guru Terpadu</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Fasilitas lengkap bagi Guru Mata Pelajaran untuk mencatat jurnal harian, memantau ketercapaian jam pelajaran, menyusun refleksi evaluatif semester, serta mencetak dokumen resmi.
                    </p>
                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-2xl text-[11px] text-slate-600 dark:text-slate-400 space-y-1.5">
                        <div class="flex items-center gap-1.5 font-semibold text-slate-800 dark:text-slate-200">
                            <span class="material-icons text-xs text-sky-500">menu_book</span> Alur & Fitur Utama:
                        </div>
                        <ul class="list-disc list-inside space-y-1 pl-1">
                            <li><strong>Tulis Jurnal:</strong> Pilih jadwal, isi materi, tujuan pembelajaran (TP), keterlaksanaan JP, asesmen, dan tindak lanjut.</li>
                            <li><strong>Rekap Mingguan:</strong> Pantau frekuensi tatap muka dan materi tiap minggu.</li>
                            <li><strong>Rekap Semester:</strong> Evaluasi perbandingan JP rencana vs realisasi & ketuntasan TP.</li>
                            <li><strong>Refleksi Guru:</strong> Isi 6 aspek evaluasi diri di akhir semester.</li>
                            <li><strong>Cetak Dokumen Resmi:</strong> Cetak berkas A4 landscape lengkap dengan Kop Sekolah, Identitas, dan Tanda Tangan Kepala Sekolah.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Card 4: SSO LMS Mokopani -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow"
                 x-show="matchesSearch('sso lms mokopani modul ajar rpp asesmen tugas')">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-950 text-purple-600 dark:text-purple-400 font-bold text-xs flex items-center justify-center">4</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200/60 dark:border-purple-900">Integrasi LMS</span>
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">Jalur Cepat (SSO) ke LMS Mokopani</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Cukup klik menu <strong>LMS Mokopani</strong> di bagian bawah sidebar untuk langsung masuk ke ruang pembuatan Modul Ajar AI, bank materi, dan tugas tanpa perlu mengetik kata sandi lagi.
                    </p>
                </div>
                @auth
                    @if(auth()->user()->hasAnyRole(['teacher', 'admin', 'operator']))
                        <div class="pt-3">
                            <a href="{{ route('sso.lms') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm transition-all w-full justify-center">
                                <span class="material-icons text-sm">school</span>
                                <span>Buka LMS Mokopani Sekarang</span>
                            </a>
                        </div>
                    @endif
                @endauth
            </div>

            <!-- Card 5: Ekstrakurikuler -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow"
                 x-show="matchesSearch('ekskul ekstrakurikuler pembina kegiatan presensi')">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400 font-bold text-xs flex items-center justify-center">5</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-900">Ekskul</span>
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">Presensi Pembina Ekstrakurikuler</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Guru pembina ekskul dapat mengelola jadwal latihan/kegiatan sore, memindai kehadiran anggota ekskul, serta mencatat materi kegiatan mingguan.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= SECTION: PANDUAN ADMIN & SUPERVISI ================= -->
    <div x-show="activeTab === 'all' || activeTab === 'admin'" class="space-y-6">
        <div class="flex items-center gap-3 pb-2 border-b border-slate-200/80 dark:border-slate-800">
            <div class="p-2 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400">
                <span class="material-icons text-2xl">admin_panel_settings</span>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white">Panduan Admin, Wakasek & Kepala Sekolah</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Supervisi jurnal mengajar, manajemen master data, radius GPS, rekap laporan, dan approval izin</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Card Supervisi Jurnal (BARU) -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border-2 border-emerald-500/30 dark:border-emerald-500/20 shadow-md space-y-3 relative overflow-hidden"
                 x-show="matchesSearch('supervisi jurnal mengajar wakasek kurikulum kepala sekolah verifikasi catatan')">
                <div class="absolute top-0 right-0">
                    <span class="inline-flex items-center px-3 py-1 rounded-bl-2xl text-[9px] font-extrabold uppercase bg-emerald-500 text-white shadow-2xs">Supervisi</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 font-bold text-xs flex items-center justify-center">1</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-900">Kurikulum & Kepsek</span>
                </div>
                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Supervisi Jurnal Mengajar Guru</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Dikhususkan bagi <strong>Wakasek Kurikulum, Kepala Sekolah, dan Admin</strong> untuk memonitor progres pembelajaran, memeriksa kesesuaian TP, dan memvalidasi jurnal guru.
                </p>
                <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-2xl text-[11px] text-slate-600 dark:text-slate-400 space-y-1">
                    <div class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1">
                        <span class="material-icons text-xs text-emerald-500">verified</span> Fitur Supervisi:
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 pl-1">
                        <li>Filter per Guru, Mata Pelajaran, Kelas, & Bulan</li>
                        <li>Verifikasi individual & Verifikasi Massal (Batch)</li>
                        <li>Pemberian catatan/umpan balik supervisor</li>
                        <li>Melihat refleksi semester & rekap target JP guru</li>
                    </ul>
                </div>
            </div>

            <!-- Card 2: Manajemen Master Data -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3"
                 x-show="matchesSearch('admin master data siswa guru kelas jadwal pelajaran sinkronisasi sipada')">
                <div class="flex items-center justify-between">
                    <span class="w-8 h-8 rounded-full bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 font-bold text-xs flex items-center justify-center">2</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-200/60 dark:border-sky-900">Master Data</span>
                </div>
                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Kelola Siswa, Guru & Jadwal</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Admin dapat mengelola data siswa dan guru secara massal via Excel atau integrasi SIPADA, serta menyusun jadwal pelajaran tiap rombel per semester aktif.
                </p>
            </div>

            <!-- Card 3: Pengaturan Radius GPS & Jam Kerja -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3"
                 x-show="matchesSearch('admin pengaturan setting gps radius peta geofencing jam masuk pulang')">
                <div class="flex items-center justify-between">
                    <span class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-bold text-xs flex items-center justify-center">3</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-900">Geofencing & Waktu</span>
                </div>
                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Pengaturan Jam & Radius Lokasi Sekolah</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Tentukan titik koordinat sekolah dan batas radius (meter) pada peta interaktif di menu <strong>Pengaturan</strong> agar absensi mandiri guru hanya bisa dilakukan di area sekolah.
                </p>
            </div>

            <!-- Card 4: Persetujuan Izin Siswa -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3"
                 x-show="matchesSearch('admin persetujuan izin sakit siswa approve reject surat orang tua')">
                <div class="flex items-center justify-between">
                    <span class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400 font-bold text-xs flex items-center justify-center">4</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-900">Persetujuan Izin</span>
                </div>
                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Verifikasi & Approval Surat Izin</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Setiap pengajuan izin dari orang tua akan masuk ke menu <strong>Pengajuan Izin</strong>. Petugas dapat melihat lampiran surat dokter/foto, lalu menyetujui atau menolak izin siswa.
                </p>
            </div>

            <!-- Card 5: Laporan & Rekapitulasi -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3"
                 x-show="matchesSearch('admin laporan rekap excel cetak pdf kehadiran persentase')">
                <div class="flex items-center justify-between">
                    <span class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-950 text-purple-600 dark:text-purple-400 font-bold text-xs flex items-center justify-center">5</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200/60 dark:border-purple-900">Rekap Laporan</span>
                </div>
                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Cetak Laporan & Ekspor Excel</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Unduh laporan kehadiran harian, mingguan, bulanan, atau per semester dalam format Excel/PDF siap cetak untuk pelaporan dinas atau arsip evaluasi sekolah.
                </p>
            </div>
        </div>
    </div>
        </div>
    </div>

    <!-- ================= SECTION: PANDUAN ORANG TUA / WALI ================= -->
    <div x-show="activeTab === 'all' || activeTab === 'parent'" class="space-y-6">
        <div class="flex items-center gap-3 pb-2 border-b border-slate-200/80 dark:border-slate-800">
            <div class="p-2 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400">
                <span class="material-icons text-2xl">family_restroom</span>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white">Panduan Orang Tua & Wali Murid</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Pemantauan kehadiran anak, pengajuan izin digital, notifikasi ketidakhadiran, dan chat</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Status Kehadiran -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4"
                 x-show="matchesSearch('orang tua status hadir terlambat sakit izin alpa anak')">
                <h3 class="font-bold text-slate-900 dark:text-white text-sm sm:text-base flex items-center gap-2">
                    <span class="material-icons text-emerald-500 text-lg">verified</span>
                    Arti Status & Warna Kehadiran
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center gap-2.5">
                        <span class="px-2 py-0.5 rounded-full font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">Hadir</span>
                        <span class="text-slate-600 dark:text-slate-400">Tepat waktu di sekolah</span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center gap-2.5">
                        <span class="px-2 py-0.5 rounded-full font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300">Terlambat</span>
                        <span class="text-slate-600 dark:text-slate-400">Scan lewat jam batas</span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center gap-2.5">
                        <span class="px-2 py-0.5 rounded-full font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300">Izin</span>
                        <span class="text-slate-600 dark:text-slate-400">Ada surat permohonan</span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center gap-2.5">
                        <span class="px-2 py-0.5 rounded-full font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">Sakit</span>
                        <span class="text-slate-600 dark:text-slate-400">Surat keterangan dokter</span>
                    </div>
                </div>
            </div>

            <!-- Cara Izin -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4"
                 x-show="matchesSearch('orang tua ajukan izin sakit foto surat dokter langkah')">
                <h3 class="font-bold text-slate-900 dark:text-white text-sm sm:text-base flex items-center gap-2">
                    <span class="material-icons text-purple-500 text-lg">assignment</span>
                    Langkah Mengajukan Surat Izin / Sakit
                </h3>
                <ol class="list-decimal list-inside space-y-2 text-xs text-slate-600 dark:text-slate-400">
                    <li>Buka halaman utama dasbor orang tua, lalu tekan <strong>Ajukan Izin atau Sakit</strong></li>
                    <li>Pilih nama siswa & pilih jenis keterangan (<strong>Izin</strong> atau <strong>Sakit</strong>)</li>
                    <li>Tentukan rentang tanggal dan tuliskan alasan singkat</li>
                    <li>Lampirkan foto surat dokter / surat izin orang tua bertanda tangan</li>
                    <li>Tekan <strong>Simpan Pengajuan</strong> dan pantau status persetujuannya</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- ================= SECTION: PANDUAN PIKET & SATPAM ================= -->
    <div x-show="activeTab === 'all' || activeTab === 'satpam'" class="space-y-6">
        <div class="flex items-center gap-3 pb-2 border-b border-slate-200/80 dark:border-slate-800">
            <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                <span class="material-icons text-2xl">security</span>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white">Panduan Petugas Piket & Satpam</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Operasional pemindai gerbang masuk, jam pulang, dan kontrol izin keluar sementara</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3"
                 x-show="matchesSearch('kiosk scanner qr gerbang masuk pulang kartu satpam')">
                <div class="flex items-center gap-2 font-bold text-slate-900 dark:text-white text-sm">
                    <span class="material-icons text-sky-500">qr_code_scanner</span>
                    Pemindai Kiosk Masuk & Pulang
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Buka menu <strong>Scan Masuk/Pulang</strong> pada layar tablet/kiosk gerbang. Siswa mendekatkan kartu QR ke kamera. Sistem otomatis mengeluarkan audio nama siswa dan mencatat waktu presensi dalam hitungan milidetik.
                </p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3"
                 x-show="matchesSearch('izin keluar dispensasi sementara meninggalkan sekolah satpam piket')">
                <div class="flex items-center gap-2 font-bold text-slate-900 dark:text-white text-sm">
                    <span class="material-icons text-indigo-500">assignment</span>
                    Pemindai Izin Keluar Sekolah
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Untuk siswa yang meninggalkan sekolah di tengah jam pelajaran (berobat / dispensasi lomba), scan kartu siswa di menu <strong>Scan Izin Keluar</strong>. Sistem memvalidasi surat persetujuan guru piket sebelum mengizinkan siswa keluar gerbang.
                </p>
            </div>
        </div>
    </div>

    <!-- ================= SECTION: FAQ INTERAKTIF ================= -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6">
        <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400">
                <span class="material-icons text-2xl">quiz</span>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white">Pertanyaan Sering Ditanyakan (FAQ)</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Solusi cepat untuk kendala umum saat menggunakan aplikasi</p>
            </div>
        </div>

        <div class="space-y-3">
            <!-- FAQ 1 -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden transition-colors"
                 x-show="matchesSearch('gps radius lokasi di luar jangkauan guru')">
                <button @click="openFaq = (openFaq === 1 ? null : 1)" class="w-full p-4 text-left font-bold text-xs sm:text-sm text-slate-900 dark:text-white bg-slate-50/50 dark:bg-slate-850/50 hover:bg-slate-100 dark:hover:bg-slate-800 flex justify-between items-center transition-colors">
                    <span>Q: Mengapa muncul pesan "Anda berada di luar radius sekolah" saat absen guru?</span>
                    <span class="material-icons text-base transition-transform duration-200" :class="{ 'rotate-180': openFaq === 1 }">expand_more</span>
                </button>
                <div x-show="openFaq === 1" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800 leading-relaxed space-y-1 bg-white dark:bg-slate-900">
                    <p>Pastikan Anda berada di area sekolah dan telah memberikan izin <strong>Location/GPS</strong> berakurasi tinggi (High Accuracy) pada browser HP Anda. Jika GPS masih meleset, buka aplikasi Google Maps sebentar agar sinyal satelit GPS HP terkunci akurat.</p>
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden transition-colors"
                 x-show="matchesSearch('kamera scanner tidak bisa dibuka izin permission')">
                <button @click="openFaq = (openFaq === 2 ? null : 2)" class="w-full p-4 text-left font-bold text-xs sm:text-sm text-slate-900 dark:text-white bg-slate-50/50 dark:bg-slate-850/50 hover:bg-slate-100 dark:hover:bg-slate-800 flex justify-between items-center transition-colors">
                    <span>Q: Kamera pemindai (scanner) tidak mau menyala di HP/Laptop?</span>
                    <span class="material-icons text-base transition-transform duration-200" :class="{ 'rotate-180': openFaq === 2 }">expand_more</span>
                </button>
                <div x-show="openFaq === 2" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800 leading-relaxed space-y-1 bg-white dark:bg-slate-900">
                    <p>Periksa izin kamera pada browser Anda (klik ikon gembok di bilah alamat URL browser $\rightarrow$ pilih <em>Permissions</em> $\rightarrow$ aktifkan <strong>Camera: Allow</strong>), lalu muat ulang (refresh) halaman.</p>
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden transition-colors"
                 x-show="matchesSearch('sso lms mokopani token kadaluarsa tidak bisa login')">
                <button @click="openFaq = (openFaq === 3 ? null : 3)" class="w-full p-4 text-left font-bold text-xs sm:text-sm text-slate-900 dark:text-white bg-slate-50/50 dark:bg-slate-850/50 hover:bg-slate-100 dark:hover:bg-slate-800 flex justify-between items-center transition-colors">
                    <span>Q: Bagaimana cara kerja SSO ke LMS Mokopani dan apa yang dilakukan jika token habis?</span>
                    <span class="material-icons text-base transition-transform duration-200" :class="{ 'rotate-180': openFaq === 3 }">expand_more</span>
                </button>
                <div x-show="openFaq === 3" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800 leading-relaxed space-y-1 bg-white dark:bg-slate-900">
                    <p>SSO membuat kunci token aman sekali pakai. Jika token kadaluarsa (lebih dari 15 menit belum dibuka), cukup klik ulang tombol <strong>LMS Mokopani</strong> di sidebar Aplikasi Presensi untuk membuat token baru.</p>
                </div>
            </div>

            <!-- FAQ 4 (Jurnal Mengajar) -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden transition-colors"
                 x-show="matchesSearch('jurnal mengajar cetak pdf a4 landscape format resmi refleksi')">
                <button @click="openFaq = (openFaq === 4 ? null : 4)" class="w-full p-4 text-left font-bold text-xs sm:text-sm text-slate-900 dark:text-white bg-slate-50/50 dark:bg-slate-850/50 hover:bg-slate-100 dark:hover:bg-slate-800 flex justify-between items-center transition-colors">
                    <span>Q: Bagaimana cara mencetak berkas Jurnal Mengajar resmi untuk keperluan supervisi / PKG?</span>
                    <span class="material-icons text-base transition-transform duration-200" :class="{ 'rotate-180': openFaq === 4 }">expand_more</span>
                </button>
                <div x-show="openFaq === 4" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800 leading-relaxed space-y-1.5 bg-white dark:bg-slate-900">
                    <p>Buka menu <strong>Jurnal Mengajar</strong>, lalu klik tombol <strong>Cetak Jurnal</strong> di kanan atas. Dokumen dicetak dalam format landscape standar A4 yang sudah mencakup Kop Resmi Sekolah, Identitas Guru (Bagian A), Jurnal Pelaksanaan (Bagian B), Rekap Mingguan (Bagian C), Rekap Semester & Asesmen (Bagian D & E), Refleksi Guru (Bagian F), serta Pengesahan Tanda Tangan Kepala Sekolah (Bagian G).</p>
                </div>
            </div>

            <!-- FAQ 5 (Supervisi Jurnal) -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden transition-colors"
                 x-show="matchesSearch('supervisi siapa yang memverifikasi wakasek kurikulum kepala sekolah')">
                <button @click="openFaq = (openFaq === 5 ? null : 5)" class="w-full p-4 text-left font-bold text-xs sm:text-sm text-slate-900 dark:text-white bg-slate-50/50 dark:bg-slate-850/50 hover:bg-slate-100 dark:hover:bg-slate-800 flex justify-between items-center transition-colors">
                    <span>Q: Siapa yang berhak melakukan supervisi dan memvalidasi Jurnal Mengajar Guru?</span>
                    <span class="material-icons text-base transition-transform duration-200" :class="{ 'rotate-180': openFaq === 5 }">expand_more</span>
                </button>
                <div x-show="openFaq === 5" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800 leading-relaxed space-y-1.5 bg-white dark:bg-slate-900">
                    <p>Hak akses supervisi jurnal diberikan kepada <strong>Wakasek Kurikulum, Kepala Sekolah, dan Administrator</strong> melalui menu <strong>Supervisi Jurnal</strong> di bilah navigasi samping. Supervisor dapat memeriksa jurnal harian, memverifikasi ketercapaian tujuan pembelajaran, serta memberikan catatan pembinaan pada jurnal guru terkait.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= HELP DESK & HUBUNGI ADMIN ================= -->
    <div class="rounded-3xl p-6 sm:p-8 bg-slate-100 dark:bg-slate-850 border border-slate-200 dark:border-slate-800 text-center space-y-3">
        <h3 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">Masih Mengalami Kendala atau Pertanyaan Lain?</h3>
        <p class="text-xs text-slate-600 dark:text-slate-400 max-w-xl mx-auto">
            Tim Administrator dan Tim IT Sekolah siap membantu kendala teknis akun atau presensi Anda.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
            @auth
                <a href="{{ route('chat.admin') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold shadow-md shadow-sky-600/20 transition-all">
                    <span class="material-icons text-base">support_agent</span>
                    <span>Chat Admin Sekolah</span>
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
