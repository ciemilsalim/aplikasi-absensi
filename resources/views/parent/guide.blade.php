<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor Orang Tua', 'url' => route('parent.dashboard')],
                    ['title' => 'Panduan Penggunaan', 'url' => route('parent.guide')]
                ]" />
                <div class="flex items-center gap-2.5 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Panduan Resmi Orang Tua & Wali Murid
                    </h1>
                    <span class="inline-flex items-center gap-1 px-3 py-0.5 rounded-full text-xs font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 shadow-2xs">
                        <span class="material-icons text-xs text-sky-500">verified</span>
                        Pusat Bantuan SIASEK
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('parent.dashboard') }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors border border-slate-200/80 dark:border-slate-700 shadow-2xs">
                    <span class="material-icons text-sm">arrow_back</span>
                    <span>Kembali ke Dasbor</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 sm:space-y-8 max-w-5xl mx-auto pb-16" x-data="{
        searchQuery: '',
        openFaq: 1,
        matchesSearch(text) {
            if (!this.searchQuery.trim()) return true;
            return text.toLowerCase().includes(this.searchQuery.toLowerCase().trim());
        }
    }">
        
        <!-- Header Hero Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-sky-950 to-indigo-950 text-white p-6 sm:p-8 shadow-xl border border-sky-900/40">
            <div class="absolute -right-10 -bottom-10 w-72 h-72 rounded-full bg-sky-500/15 blur-3xl pointer-events-none"></div>
            <div class="absolute right-6 bottom-4 opacity-10 pointer-events-none hidden md:block">
                <span class="material-icons text-[180px]">family_restroom</span>
            </div>

            <div class="relative z-10 max-w-2xl space-y-3">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-sky-500/20 backdrop-blur-md rounded-full text-xs font-bold text-sky-300 border border-sky-400/30">
                    <span class="material-icons text-sm">auto_stories</span>
                    <span>Panduan Aplikasi Presensi Siswa SIASEK</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-tight">
                    Selamat Datang di Portal Orang Tua / Wali
                </h2>
                <p class="text-slate-300 text-xs sm:text-sm leading-relaxed">
                    Halaman panduan ini memuat petunjuk langkah demi langkah untuk memantau status presensi harian putra/putri Anda, mengajukan surat izin dan sakit secara online, memantau absensi ekstrakurikuler, serta berkomunikasi langsung dengan Wali Kelas dan Admin Sekolah.
                </p>

                <!-- Pencarian Cepat Topik -->
                <div class="pt-2 max-w-lg">
                    <div class="relative flex items-center">
                        <span class="material-icons absolute left-3.5 text-slate-400 text-base">search</span>
                        <input type="text" 
                               x-model="searchQuery" 
                               placeholder="Cari panduan (misal: izin, sakit, alpa, nisn, dispensasi, chat, ekskul)..."
                               class="w-full pl-9 pr-8 py-2.5 rounded-2xl bg-white/95 dark:bg-slate-900/90 text-slate-900 dark:text-white placeholder-slate-400 border border-white/20 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 shadow-md backdrop-blur-md transition-all">
                        <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3 text-slate-400 hover:text-slate-600 text-xs">
                            <span class="material-icons text-sm">close</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu Pintasan Navigasi Topik -->
        <div>
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2.5 px-1">Pintasan Topik Panduan</div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 sm:gap-3">
                <a href="#hubungkan-anak" class="p-3.5 bg-white dark:bg-slate-850 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-sky-500 dark:hover:border-sky-500 transition-all shadow-2xs flex flex-col items-center text-center group">
                    <div class="w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <span class="material-icons text-xl">person_add</span>
                    </div>
                    <span class="font-extrabold text-xs text-slate-800 dark:text-slate-100">Hubungkan Anak</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">NISN & Multi-Anak</span>
                </a>
                
                <a href="#status-kehadiran" class="p-3.5 bg-white dark:bg-slate-850 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-emerald-500 dark:hover:border-emerald-500 transition-all shadow-2xs flex flex-col items-center text-center group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <span class="material-icons text-xl">verified</span>
                    </div>
                    <span class="font-extrabold text-xs text-slate-800 dark:text-slate-100">Status Kehadiran</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Arti warna & badge</span>
                </a>
                
                <a href="#ajukan-izin" class="p-3.5 bg-white dark:bg-slate-850 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-purple-500 dark:hover:border-purple-500 transition-all shadow-2xs flex flex-col items-center text-center group">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <span class="material-icons text-xl">assignment</span>
                    </div>
                    <span class="font-extrabold text-xs text-slate-800 dark:text-slate-100">Ajukan Izin / Sakit</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Surat online & dokter</span>
                </a>

                <a href="#dispensasi-pulang" class="p-3.5 bg-white dark:bg-slate-850 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-teal-500 dark:hover:border-teal-500 transition-all shadow-2xs flex flex-col items-center text-center group">
                    <div class="w-10 h-10 rounded-xl bg-teal-100 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <span class="material-icons text-xl">badge</span>
                    </div>
                    <span class="font-extrabold text-xs text-slate-800 dark:text-slate-100">E-Dispensasi</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Pulang cepat & satpam</span>
                </a>

                <a href="#fitur-chat" class="p-3.5 bg-white dark:bg-slate-850 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-amber-500 dark:hover:border-amber-500 transition-all shadow-2xs flex flex-col items-center text-center group">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <span class="material-icons text-xl">chat</span>
                    </div>
                    <span class="font-extrabold text-xs text-slate-800 dark:text-slate-100">Obrolan (Chat)</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Wali Kelas & Admin</span>
                </a>

                <a href="#faq" class="p-3.5 bg-white dark:bg-slate-850 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-rose-500 dark:hover:border-rose-500 transition-all shadow-2xs flex flex-col items-center text-center group">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <span class="material-icons text-xl">quiz</span>
                    </div>
                    <span class="font-extrabold text-xs text-slate-800 dark:text-slate-100">Tanya Jawab (FAQ)</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Kendala umum</span>
                </a>
            </div>
        </div>

        <!-- 1. Panduan Hubungkan Data Anak (Onboarding & Multi-Anak) -->
        <div id="hubungkan-anak" class="bg-white dark:bg-slate-850 rounded-3xl p-5 sm:p-7 shadow-sm border border-slate-200/80 dark:border-slate-800 space-y-4"
             x-show="matchesSearch('hubungkan anak data nisn tanggal lahir multi akun siswa ganti profil')">
            <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 rounded-2xl">
                        <span class="material-icons text-2xl">person_add</span>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 dark:text-white text-base sm:text-lg">1. Menghubungkan Akun & Data Siswa</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Petunjuk menghubungkan NISN anak dan cara mengelola multi-anak</p>
                    </div>
                </div>
                <a href="{{ route('parent.onboarding.index') }}" 
                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold transition-all shadow-xs">
                    <span class="material-icons text-sm">link</span>
                    <span>Buka Menu Hubungkan</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-2.5">
                    <div class="font-extrabold text-sky-700 dark:text-sky-300 flex items-center gap-1.5">
                        <span class="material-icons text-base">pin</span> Cara Menghubungkan Anak Pertama Kali:
                    </div>
                    <ol class="list-decimal list-inside space-y-1.5 text-slate-600 dark:text-slate-300 leading-relaxed">
                        <li>Buka menu <strong>Hubungkan Data Anak</strong> di sudut kanan atas dasbor.</li>
                        <li>Masukkan <strong>NISN (10 Digit)</strong> siswa yang terdaftar di sekolah.</li>
                        <li>Pilih <strong>Tanggal Lahir</strong> siswa sesuai dokumen Kartu Keluarga / Akta Kelahiran.</li>
                        <li>Sistem akan mencocokkan data rombel kelas, nama lengkap siswa, dan nama wali.</li>
                        <li>Klik <strong>Konfirmasi & Simpan</strong> untuk menyelesaikan koneksi.</li>
                    </ol>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-2.5">
                    <div class="font-extrabold text-purple-700 dark:text-purple-300 flex items-center gap-1.5">
                        <span class="material-icons text-base">groups</span> Memiliki Lebih dari 1 Anak (Multi-Anak):
                    </div>
                    <p class="text-slate-600 dark:text-slate-300 leading-relaxed">
                        Bapak/Ibu <strong>tidak perlu membuat akun baru</strong> jika memiliki 2 atau lebih anak di SMP Negeri 1 Biau. Cukup hubungkan masing-masing anak pada akun yang sama.
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-slate-600 dark:text-slate-300 leading-relaxed">
                        <li>Ulangi langkah menghubungkan data dengan memasukkan NISN anak kedua.</li>
                        <li>Pada dasbor utama, gunakan <strong>tab nama anak</strong> di bagian atas kartu untuk berpindah pemantauan status kehadiran anak secara instan.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 2. Penjelasan Status Kehadiran & Rekapitulasi Presensi -->
        <div id="status-kehadiran" class="bg-white dark:bg-slate-850 rounded-3xl p-5 sm:p-7 shadow-sm border border-slate-200/80 dark:border-slate-800 space-y-4"
             x-show="matchesSearch('status kehadiran warna badge hadir terlambat izin sakit alpa belum pulang whatsapp')">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="p-2.5 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-2xl">
                    <span class="material-icons text-2xl">verified</span>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 dark:text-white text-base sm:text-lg">2. Memahami Status Kehadiran Putra/Putri Anda</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Arti warna badge, jam scan masuk/pulang, dan notifikasi otomatis</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div class="p-4 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-2xl border border-emerald-200/70 dark:border-emerald-900/50 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-black px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/80 dark:text-emerald-200">Hadir</span>
                            <span class="material-icons text-emerald-500 text-lg">check_circle</span>
                        </div>
                        <h4 class="font-extrabold text-xs text-slate-800 dark:text-slate-100">Hadir Tepat Waktu</h4>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                            Siswa melakukan scan kartu di gerbang sekolah sebelum batas jam masuk (sebelum pukul 07.15 WITA).
                        </p>
                    </div>
                </div>

                <div class="p-4 bg-rose-50/50 dark:bg-rose-950/20 rounded-2xl border border-rose-200/70 dark:border-rose-900/50 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-black px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 dark:bg-rose-900/80 dark:text-rose-200">Terlambat</span>
                            <span class="material-icons text-rose-500 text-lg">schedule</span>
                        </div>
                        <h4 class="font-extrabold text-xs text-slate-800 dark:text-slate-100">Hadir Terlambat</h4>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                            Siswa melakukan scan di gerbang setelah batas toleransi tiba. Durasi menit keterlambatan tercatat otomatis.
                        </p>
                    </div>
                </div>

                <div class="p-4 bg-purple-50/50 dark:bg-purple-950/20 rounded-2xl border border-purple-200/70 dark:border-purple-900/50 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-black px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/80 dark:text-purple-200">Izin</span>
                            <span class="material-icons text-purple-500 text-lg">assignment</span>
                        </div>
                        <h4 class="font-extrabold text-xs text-slate-800 dark:text-slate-100">Izin Keperluan Keluarga</h4>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                            Orang tua telah mengirimkan surat izin digital dan disetujui oleh pihak sekolah / wali kelas.
                        </p>
                    </div>
                </div>

                <div class="p-4 bg-amber-50/50 dark:bg-amber-950/20 rounded-2xl border border-amber-200/70 dark:border-amber-900/50 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-black px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/80 dark:text-amber-200">Sakit</span>
                            <span class="material-icons text-amber-500 text-lg">medical_services</span>
                        </div>
                        <h4 class="font-extrabold text-xs text-slate-800 dark:text-slate-100">Sakit dengan Keterangan</h4>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                            Laporan sakit yang disertai lampiran surat dokter atau keterangan orang tua telah diverifikasi sekolah.
                        </p>
                    </div>
                </div>

                <div class="p-4 bg-teal-50/50 dark:bg-teal-950/20 rounded-2xl border border-teal-200/70 dark:border-teal-900/50 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-black px-2.5 py-0.5 rounded-full bg-teal-100 text-teal-800 dark:bg-teal-900/80 dark:text-teal-200">Dispensasi</span>
                            <span class="material-icons text-teal-500 text-lg">badge</span>
                        </div>
                        <h4 class="font-extrabold text-xs text-slate-800 dark:text-slate-100">E-Dispensasi Pulang Cepat</h4>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                            Siswa mendapatkan izin resmi meninggalkan sekolah lebih awal (karena sakit di sekolah / tugas dinas) dan diverifikasi Satpam.
                        </p>
                    </div>
                </div>

                <div class="p-4 bg-rose-100/50 dark:bg-rose-950/40 rounded-2xl border border-rose-300 dark:border-rose-800 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-black px-2.5 py-0.5 rounded-full bg-rose-200 text-rose-900 dark:bg-rose-900 dark:text-rose-200">Alpa</span>
                            <span class="material-icons text-rose-600 text-lg">warning</span>
                        </div>
                        <h4 class="font-extrabold text-xs text-slate-800 dark:text-slate-100">Tanpa Keterangan (Alpa)</h4>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                            Hingga batas jam selesai sekolah, anak belum scan dan tidak ada konfirmasi izin. Segera hubungi Wali Kelas.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Catatan Keterangan Jam Pulang & WhatsApp Gateway -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
                <div class="p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-900/50 rounded-2xl text-xs text-amber-900 dark:text-amber-200 flex items-start gap-2.5">
                    <span class="material-icons text-amber-600 dark:text-amber-400 text-lg shrink-0 mt-0.5">info</span>
                    <div>
                        <span class="font-extrabold">Keterangan Khusus Jam Pulang:</span>
                        <ul class="list-disc list-inside mt-1.5 space-y-1 text-[11px] leading-relaxed">
                            <li><strong>belum absen pulang</strong>: Ditampilkan pada hari berjalan jika siswa sudah masuk namun belum memindai kartu saat jam pulang.</li>
                            <li><strong>tidak absen pulang</strong>: Ditampilkan untuk hari lampau jika siswa tidak melakukan scan saat kepulangan sekolah.</li>
                        </ul>
                    </div>
                </div>

                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200/70 dark:border-emerald-900/50 rounded-2xl text-xs text-emerald-900 dark:text-emerald-200 flex items-start gap-2.5">
                    <span class="material-icons text-emerald-600 dark:text-emerald-400 text-lg shrink-0 mt-0.5">notifications_active</span>
                    <div>
                        <span class="font-extrabold">Notifikasi WhatsApp Realtime:</span>
                        <p class="mt-1 text-[11px] leading-relaxed">
                            Bila nomor WhatsApp Anda terdaftar pada sistem sekolah, Anda akan menerima pesan konfirmasi otomatis saat anak berhasil scan masuk di gerbang pagi dan scan pulang di siang hari.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Panduan Mengajukan Izin / Sakit Online (E-Izin) -->
        <div id="ajukan-izin" class="bg-white dark:bg-slate-850 rounded-3xl p-5 sm:p-7 shadow-sm border border-slate-200/80 dark:border-slate-800 space-y-4"
             x-show="matchesSearch('ajukan izin sakit dokter surat foto formulir langkah pending disetujui')">
            <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 rounded-2xl">
                        <span class="material-icons text-2xl">assignment</span>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 dark:text-white text-base sm:text-lg">3. Cara Mengajukan Surat Izin atau Sakit</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pengajuan digital tanpa perlu mengantar surat kertas ke sekolah</p>
                    </div>
                </div>
                <a href="{{ route('parent.leave-requests.create') }}" 
                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition-all shadow-xs">
                    <span class="material-icons text-sm">add_circle</span>
                    <span>Ajukan Izin Sekarang</span>
                </a>
            </div>

            <div class="space-y-3.5 text-xs">
                <div class="flex gap-3.5 items-start p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="w-7 h-7 rounded-full bg-purple-600 text-white font-black text-xs flex items-center justify-center shrink-0">1</div>
                    <div>
                        <h4 class="font-extrabold text-slate-800 dark:text-slate-100 text-xs sm:text-sm">Buka Formulir Pengajuan</h4>
                        <p class="text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                            Tekan tombol <strong>"Ajukan Izin / Sakit"</strong> berwarna oranye di banner Dasbor Orang Tua, atau akses melalui menu <strong>Izin Sakit</strong> pada bilah navigasi bawah.
                        </p>
                    </div>
                </div>

                <div class="flex gap-3.5 items-start p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="w-7 h-7 rounded-full bg-purple-600 text-white font-black text-xs flex items-center justify-center shrink-0">2</div>
                    <div>
                        <h4 class="font-extrabold text-slate-800 dark:text-slate-100 text-xs sm:text-sm">Pilih Nama Siswa & Jenis Keterangan</h4>
                        <p class="text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                            Pilih nama anak yang berhalangan hadir. Pilih jenis keterangan: <span class="font-bold text-purple-600 dark:text-purple-400">Izin</span> (keperluan keluarga/darurat) atau <span class="font-bold text-amber-600 dark:text-amber-400">Sakit</span>.
                        </p>
                    </div>
                </div>

                <div class="flex gap-3.5 items-start p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="w-7 h-7 rounded-full bg-purple-600 text-white font-black text-xs flex items-center justify-center shrink-0">3</div>
                    <div>
                        <h4 class="font-extrabold text-slate-800 dark:text-slate-100 text-xs sm:text-sm">Tentukan Rentang Tanggal & Tuliskan Alasan</h4>
                        <p class="text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                            Pilih tanggal mulai hingga tanggal selesai (bisa lebih dari 1 hari jika sakit berkelanjutan). Tuliskan alasan dengan sopan dan jelas (contoh: <em>"Ananda mengalami demam tinggi dan disarankan dokter beristirahat 2 hari"</em>).
                        </p>
                    </div>
                </div>

                <div class="flex gap-3.5 items-start p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="w-7 h-7 rounded-full bg-purple-600 text-white font-black text-xs flex items-center justify-center shrink-0">4</div>
                    <div>
                        <h4 class="font-extrabold text-slate-800 dark:text-slate-100 text-xs sm:text-sm">Unggah Foto Dokumen Pendukung</h4>
                        <p class="text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                            Ambil foto surat keterangan dokter atau surat izin bertanda tangan orang tua dari kamera ponsel Anda, kemudian unggah pada kolom lampiran yang disediakan (format JPG/PNG/PDF).
                        </p>
                    </div>
                </div>

                <div class="flex gap-3.5 items-start p-3.5 bg-emerald-50 dark:bg-emerald-950/30 rounded-2xl border border-emerald-200/80 dark:border-emerald-900/50">
                    <div class="w-7 h-7 rounded-full bg-emerald-600 text-white font-black text-xs flex items-center justify-center shrink-0">5</div>
                    <div>
                        <h4 class="font-extrabold text-emerald-900 dark:text-emerald-200 text-xs sm:text-sm">Kirim & Pantau Status Verifikasi</h4>
                        <p class="text-emerald-800/90 dark:text-emerald-300 mt-0.5 leading-relaxed">
                            Tekan tombol <strong>"Simpan Pengajuan"</strong>. Status permohonan akan diproses oleh sekolah dengan 3 indikator:
                            <span class="font-bold text-amber-600">Pending (Menunggu)</span>, 
                            <span class="font-bold text-emerald-600">Disetujui (Approved)</span>, atau 
                            <span class="font-bold text-rose-600">Ditolak (Rejected)</span> dengan catatan alasan dari pihak sekolah.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. E-Dispensasi Pulang Cepat Terintegrasi Satpam -->
        <div id="dispensasi-pulang" class="bg-white dark:bg-slate-850 rounded-3xl p-5 sm:p-7 shadow-sm border border-slate-200/80 dark:border-slate-800 space-y-4"
             x-show="matchesSearch('dispensasi pulang cepat pass card izin keluar sakit uks satpam gerbang')">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="p-2.5 bg-teal-100 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 rounded-2xl">
                    <span class="material-icons text-2xl">badge</span>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 dark:text-white text-base sm:text-lg">4. Alur E-Dispensasi / Izin Pulang Cepat Terintegrasi Satpam</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Prosedur resmi kepulangan sebelum jam pelajaran usai</p>
                </div>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                Jika putra/putri Anda mengalami sakit mendadak di sekolah (ditangani UKS) atau harus dijemput karena urusan keluarga yang sangat mendesak, pihak sekolah akan menerbitkan <strong>E-Dispensasi / Pass Card Pulang Cepat</strong>.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-1.5">
                    <div class="flex items-center gap-2 font-bold text-teal-700 dark:text-teal-300">
                        <span class="material-icons text-base">emergency</span>
                        <span>1. Penerbitan Izin</span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 leading-relaxed text-[11px]">
                        Guru Piket atau Petugas UKS menerbitkan dispensasi di sistem dengan mencantumkan alasan (misal: sakit demam di kelas).
                    </p>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-1.5">
                    <div class="flex items-center gap-2 font-bold text-teal-700 dark:text-teal-300">
                        <span class="material-icons text-base">qr_code_2</span>
                        <span>2. Pass Card Digital</span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 leading-relaxed text-[11px]">
                        Pass Card memuat kode izin unik dan QR Code resmi. Status presensi hari itu otomatis tercatat sebagai <strong>Dispensasi</strong>.
                    </p>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-1.5">
                    <div class="flex items-center gap-2 font-bold text-teal-700 dark:text-teal-300">
                        <span class="material-icons text-base">security</span>
                        <span>3. Verifikasi di Gerbang</span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 leading-relaxed text-[11px]">
                        Saat dijemput, perlihatkan tiket/kode izin ke Satpam di pos gerbang sekolah. Satpam memindai kode untuk memastikan siswa pulang aman.
                    </p>
                </div>
            </div>
        </div>

        <!-- 5. Presensi Kegiatan Ekstrakurikuler -->
        <div id="ekskul" class="bg-white dark:bg-slate-850 rounded-3xl p-5 sm:p-7 shadow-sm border border-slate-200/80 dark:border-slate-800 space-y-4"
             x-show="matchesSearch('ekstrakurikuler ekskul pramuka pmr pembina jadwal sore sabtu kehadiran')">
            <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 rounded-2xl">
                        <span class="material-icons text-2xl">military_tech</span>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 dark:text-white text-base sm:text-lg">5. Pemantauan Kegiatan Ekstrakurikuler</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Memantau keikutsertaan anak pada kegiatan ekskul wajib & pilihan</p>
                    </div>
                </div>
                <a href="{{ route('parent.dashboard') }}#ekskul" 
                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition-all shadow-xs">
                    <span class="material-icons text-sm">open_in_new</span>
                    <span>Lihat di Dasbor</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-2">
                    <div class="font-extrabold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                        <span class="material-icons text-base text-purple-500">event_available</span> Jadwal & Pembina Ekskul
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed text-[11px]">
                        Orang tua dapat melihat daftar kegiatan ekskul yang diikuti anak (misal: Pramuka, PMR, Futsal, Seni Tari), hari pelaksanaan, dan nama Guru Pembina yang bertanggung jawab.
                    </p>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-2">
                    <div class="font-extrabold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                        <span class="material-icons text-base text-purple-500">checklist</span> Absensi Langsung oleh Pembina
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed text-[11px]">
                        Presensi ekskul dicatat langsung oleh Pembina pada setiap sesi latihan. Riwayat kehadiran ekskul ditampilkan terpisah dari absensi jam sekolah formal agar mudah dievaluasi.
                    </p>
                </div>
            </div>
        </div>

        <!-- 6. Fitur Obrolan Terpadu (Chat Wali Kelas & Admin) -->
        <div id="fitur-chat" class="bg-white dark:bg-slate-850 rounded-3xl p-5 sm:p-7 shadow-sm border border-slate-200/80 dark:border-slate-800 space-y-4"
             x-show="matchesSearch('obrolan chat wali kelas admin pesan guru kontak komunikasi konsultasi')">
            <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 rounded-2xl">
                        <span class="material-icons text-2xl">forum</span>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 dark:text-white text-base sm:text-lg">6. Berkomunikasi Melalui Fitur Obrolan (Chat)</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Kirim pesan langsung ke Wali Kelas maupun Admin Sekolah</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('chat.index') }}" 
                       class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold transition-all shadow-xs">
                        <span class="material-icons text-sm">chat</span>
                        <span>Buka Obrolan</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-slate-900 dark:text-white font-extrabold text-sm">
                            <span class="material-icons text-amber-500">school</span> Chat Wali Kelas
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200">Akademik</span>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed text-[11px]">
                        Gunakan tombol <strong>Obrolan</strong> untuk berdiskusi langsung dengan Wali Kelas anak Anda mengenai:
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-slate-600 dark:text-slate-300 text-[11px] pl-0.5">
                        <li>Konfirmasi ketidakhadiran mendadak atau keterlambatan.</li>
                        <li>Perkembangan belajar, sikap, dan kedisiplinan siswa di kelas.</li>
                        <li>Konsultasi kendala pembelajaran harian.</li>
                    </ul>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-slate-900 dark:text-white font-extrabold text-sm">
                            <span class="material-icons text-sky-500">support_agent</span> Chat Admin Sekolah
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 dark:bg-sky-900/60 dark:text-sky-200">Teknis & Akun</span>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed text-[11px]">
                        Pilih opsi <strong>Chat Admin</strong> jika Anda mengalami kebutuhan terkait:
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-slate-600 dark:text-slate-300 text-[11px] pl-0.5">
                        <li>Kendala NISN atau tanggal lahir tidak cocok saat menghubungkan anak.</li>
                        <li>Permintaan perubahan nomor WhatsApp penerima notifikasi.</li>
                        <li>Kendala teknis login atau lupa kata sandi akun portal.</li>
                    </ul>
                </div>
            </div>

            <div class="p-3 bg-sky-50 dark:bg-sky-950/30 border border-sky-200/70 dark:border-sky-900/40 rounded-2xl text-[11px] text-sky-800 dark:text-sky-300 flex items-center gap-2">
                <span class="material-icons text-base text-sky-500 shrink-0">smartphone</span>
                <span><strong>Tips Penggunaan di HP:</strong> Halaman obrolan kini dilengkapi layar penuh fleksibel tanpa terhalang menu navigasi bawah, sehingga Anda dapat mengetik pesan dan membaca balasan dengan leluasa.</span>
            </div>
        </div>

        <!-- 7. Tanya Jawab Sering Ditanyakan (FAQ Interaktif) -->
        <div id="faq" class="bg-white dark:bg-slate-850 rounded-3xl p-5 sm:p-7 shadow-sm border border-slate-200/80 dark:border-slate-800 space-y-4"
             x-show="matchesSearch('faq tanya jawab pertanyaan belum tercatat lupa scan batas jam wa ganti')">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="p-2.5 bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 rounded-2xl">
                    <span class="material-icons text-2xl">quiz</span>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 dark:text-white text-base sm:text-lg">7. Pertanyaan Sering Ditanyakan (FAQ)</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Jawaban cepat untuk pertanyaan dan kendala yang kerap dialami orang tua</p>
                </div>
            </div>

            <div class="space-y-3">
                <!-- FAQ 1 -->
                <div class="border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden transition-all">
                    <button @click="openFaq = (openFaq === 1 ? 0 : 1)" class="w-full p-4 text-left font-extrabold text-xs sm:text-sm text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center gap-3">
                        <span class="flex items-center gap-2">
                            <span class="text-sky-500 font-black">Q1.</span> Mengapa muncul notifikasi banner "Ananda belum tercatat kehadirannya"?
                        </span>
                        <span class="material-icons text-base transition-transform shrink-0" :class="{ 'rotate-180': openFaq === 1 }">expand_more</span>
                    </button>
                    <div x-show="openFaq === 1" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-100 dark:border-slate-800 space-y-1.5 leading-relaxed bg-white dark:bg-slate-850">
                        <p>Peringatan ini muncul secara otomatis saat jam batas presensi masuk tiba namun siswa belum terdeteksi scan kartu di gerbang sekolah.</p>
                        <p class="text-sky-600 dark:text-sky-400 font-bold">Solusi Cepat: Tekan tombol "Ajukan Izin / Sakit" pada banner jika ananda tidak dapat masuk sekolah, atau tekan tombol "Chat Wali Kelas" untuk melakukan konfirmasi.</p>
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden transition-all">
                    <button @click="openFaq = (openFaq === 2 ? 0 : 2)" class="w-full p-4 text-left font-extrabold text-xs sm:text-sm text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center gap-3">
                        <span class="flex items-center gap-2">
                            <span class="text-sky-500 font-black">Q2.</span> Mengapa NISN anak saya tidak ditemukan saat menghubungkan akun?
                        </span>
                        <span class="material-icons text-base transition-transform shrink-0" :class="{ 'rotate-180': openFaq === 2 }">expand_more</span>
                    </button>
                    <div x-show="openFaq === 2" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-100 dark:border-slate-800 space-y-1.5 leading-relaxed bg-white dark:bg-slate-850">
                        <p>Pastikan penulisan NISN terdiri dari 10 digit angka tanpa spasi dan tanggal lahir sesuai dengan data di buku rapor/akta kelahiran.</p>
                        <p class="text-sky-600 dark:text-sky-400 font-bold">Bila masih tidak ditemukan, silakan hubungi Admin Sekolah via fitur Chat Admin untuk verifikasi sinkronisasi data Dapodik/SIPADA siswa.</p>
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden transition-all">
                    <button @click="openFaq = (openFaq === 3 ? 0 : 3)" class="w-full p-4 text-left font-extrabold text-xs sm:text-sm text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center gap-3">
                        <span class="flex items-center gap-2">
                            <span class="text-sky-500 font-black">Q3.</span> Bagaimana jika anak lupa melakukan scan kartu saat pulang sekolah?
                        </span>
                        <span class="material-icons text-base transition-transform shrink-0" :class="{ 'rotate-180': openFaq === 3 }">expand_more</span>
                    </button>
                    <div x-show="openFaq === 3" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-100 dark:border-slate-800 space-y-1.5 leading-relaxed bg-white dark:bg-slate-850">
                        <p>Status jam pulang akan menampilkan teks berwarna merah <span class="font-extrabold text-rose-600">"tidak absen pulang"</span>.</p>
                        <p>Bapak/Ibu tidak perlu cemas, kehadiran jam masuk anak di pagi hari tetap sah tersimpan di sistem rekapitulasi absensi sekolah.</p>
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden transition-all">
                    <button @click="openFaq = (openFaq === 4 ? 0 : 4)" class="w-full p-4 text-left font-extrabold text-xs sm:text-sm text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center gap-3">
                        <span class="flex items-center gap-2">
                            <span class="text-sky-500 font-black">Q4.</span> Apakah saya bisa mengajukan izin untuk beberapa hari ke depan sekaligus?
                        </span>
                        <span class="material-icons text-base transition-transform shrink-0" :class="{ 'rotate-180': openFaq === 4 }">expand_more</span>
                    </button>
                    <div x-show="openFaq === 4" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-100 dark:border-slate-800 space-y-1.5 leading-relaxed bg-white dark:bg-slate-850">
                        <p><strong>Bisa</strong>. Saat mengisi formulir pengajuan izin, tentukan tanggal mulai dan tanggal selesai sesuai durasi yang dibutuhkan (misal: 3 hari karena sakit rawat jalan dengan anjuran dokter).</p>
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden transition-all">
                    <button @click="openFaq = (openFaq === 5 ? 0 : 5)" class="w-full p-4 text-left font-extrabold text-xs sm:text-sm text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center gap-3">
                        <span class="flex items-center gap-2">
                            <span class="text-sky-500 font-black">Q5.</span> Bagaimana jika nomor WhatsApp saya berganti atau belum menerima pesan presensi?
                        </span>
                        <span class="material-icons text-base transition-transform shrink-0" :class="{ 'rotate-180': openFaq === 5 }">expand_more</span>
                    </button>
                    <div x-show="openFaq === 5" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-100 dark:border-slate-800 space-y-1.5 leading-relaxed bg-white dark:bg-slate-850">
                        <p>Pastikan nomor ponsel yang terdaftar menggunakan format WhatsApp yang aktif. Anda dapat memperbarui data nomor HP melalui menu <strong>Profil Akun</strong> atau menghubungi Admin Sekolah.</p>
                    </div>
                </div>

                <!-- FAQ 6 -->
                <div class="border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden transition-all">
                    <button @click="openFaq = (openFaq === 6 ? 0 : 6)" class="w-full p-4 text-left font-extrabold text-xs sm:text-sm text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center gap-3">
                        <span class="flex items-center gap-2">
                            <span class="text-sky-500 font-black">Q6.</span> Berapa batas toleransi jam kedatangan siswa di sekolah?
                        </span>
                        <span class="material-icons text-base transition-transform shrink-0" :class="{ 'rotate-180': openFaq === 6 }">expand_more</span>
                    </button>
                    <div x-show="openFaq === 6" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-100 dark:border-slate-800 space-y-1.5 leading-relaxed bg-white dark:bg-slate-850">
                        <p>Siswa diharapkan tiba dan melakukan scan di gerbang sekolah sebelum pukul <strong>07.15 WITA</strong>. Scan kartu setelah jam tersebut akan otomatis tercatat sebagai <strong>Terlambat</strong>.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu Kontak Dukungan & Bantuan Cepat -->
        <div class="p-6 sm:p-8 bg-gradient-to-r from-sky-50 to-indigo-50 dark:from-slate-850 dark:to-sky-950/40 rounded-3xl border border-sky-200/80 dark:border-sky-900/50 text-center space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-sky-600 text-white flex items-center justify-center mx-auto shadow-md shadow-sky-600/20">
                <span class="material-icons text-2xl">support_agent</span>
            </div>
            <h4 class="font-extrabold text-slate-900 dark:text-white text-base sm:text-lg">Membutuhkan Bantuan Lebih Lanjut?</h4>
            <p class="text-xs text-slate-600 dark:text-slate-400 max-w-xl mx-auto leading-relaxed">
                Jika Anda memiliki kendala teknis, pertanyaan khusus seputar akun, atau membutuhkan panduan langsung, tim Admin dan Wali Kelas siap membantu.
            </p>
            <div class="pt-2 flex flex-wrap items-center justify-center gap-2.5">
                <a href="{{ route('chat.admin') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-2xl text-xs font-bold shadow-md shadow-sky-600/20 transition-all">
                    <span class="material-icons text-base">support_agent</span>
                    <span>Chat Admin Sekolah</span>
                </a>
                <a href="{{ route('parent.onboarding.index') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700 rounded-2xl text-xs font-bold transition-all shadow-2xs">
                    <span class="material-icons text-base text-sky-500">person_add</span>
                    <span>Hubungkan Data Anak</span>
                </a>
                <a href="{{ route('parent.dashboard') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 rounded-2xl text-xs font-bold transition-all">
                    <span class="material-icons text-base">dashboard</span>
                    <span>Dasbor Utama</span>
                </a>
            </div>
        </div>

    </div>
</x-app-layout>
