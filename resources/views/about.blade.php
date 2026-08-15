@extends('layouts.guest')

@section('title', 'Tentang Aplikasi - ' . config('app.name', 'Presensi Siswa'))

@section('content')
<div class="py-12 sm:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
        
        <!-- ================= HERO SECTION ================= -->
        <div class="text-center max-w-3xl mx-auto space-y-4">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-sky-50 dark:bg-sky-950/60 border border-sky-200/80 dark:border-sky-800/80 text-sky-700 dark:text-sky-300 text-xs font-extrabold shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                <span>Sistem Informasi Presensi Digital &bull; Versi 2.0 Enterprise</span>
            </div>
            
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-tight">
                Solusi Cerdas Presensi & Monitoring Sekolah Terpadu
            </h1>
            
            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed max-w-2xl mx-auto">
                Platform all-in-one pencatatan kehadiran berbasis pemindaian QR Code instan, automasi suara, pengajuan izin digital, visual analytics, serta kolaborasi multi-peran untuk ekosistem pendidikan modern.
            </p>

            <div class="flex flex-wrap items-center justify-center gap-3 pt-3">
                <a href="{{ route('login') }}" 
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-lg shadow-sky-600/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                    <span class="material-icons text-base">login</span>
                    <span>Masuk ke Portal</span>
                </a>
                <a href="{{ route('scanner') }}" 
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-2xs">
                    <span class="material-icons text-base text-sky-500">qr_code_scanner</span>
                    <span>Buka Scanner Kiosk</span>
                </a>
            </div>
        </div>

        <!-- ================= STATS / HIGHLIGHTS ================= -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm text-center">
                <div class="w-11 h-11 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 mx-auto flex items-center justify-center font-bold mb-3">
                    <span class="material-icons text-xl">speed</span>
                </div>
                <div class="text-2xl font-extrabold text-slate-900 dark:text-white">&lt; 1 Detik</div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Kecepatan Pindai QR</p>
            </div>

            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm text-center">
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 mx-auto flex items-center justify-center font-bold mb-3">
                    <span class="material-icons text-xl">group</span>
                </div>
                <div class="text-2xl font-extrabold text-slate-900 dark:text-white">5 Peran Pengguna</div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Eksosistem Terintegrasi</p>
            </div>

            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm text-center">
                <div class="w-11 h-11 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 mx-auto flex items-center justify-center font-bold mb-3">
                    <span class="material-icons text-xl">insights</span>
                </div>
                <div class="text-2xl font-extrabold text-slate-900 dark:text-white">Real-Time</div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Visual Charts & Matriks</p>
            </div>

            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm text-center">
                <div class="w-11 h-11 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 mx-auto flex items-center justify-center font-bold mb-3">
                    <span class="material-icons text-xl">hub</span>
                </div>
                <div class="text-2xl font-extrabold text-slate-900 dark:text-white">SSO & PWA</div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">LMS Mokopani & SIPADA</p>
            </div>
        </div>

        <!-- ================= SEMUA FITUR UNGGULAN ================= -->
        <div class="space-y-8">
            <div class="text-center max-w-2xl mx-auto">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Fitur & Kemampuan Lengkap Sistem
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2">
                    Dirancang menyeluruh untuk mendukung transparansi data, disiplin kehadiran, serta kenyamanan orang tua dan guru.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- 1. Kiosk Scanner -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-2xl bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold mb-4">
                        <span class="material-icons text-2xl">qr_code_scanner</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Pemindai Kiosk Mandiri</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Sistem pemindaian QR code mandiri di gerbang masuk dengan *Web Speech API Voice Synthesizer* untuk memanggil nama siswa, feedback audio lonceng, live activity feed, dan mode fullscreen.
                    </p>
                </div>

                <!-- 2. Permit Scanner -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold mb-4">
                        <span class="material-icons text-2xl">assignment_turned_in</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Pemindai Izin Keluar Sekolah</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Validasi QR digital surat izin siswa meninggalkan sekolah untuk satpam dan petugas piket. Memverifikasi identitas, alasan izin, dan waktu kembali secara akurat.
                    </p>
                </div>

                <!-- 3. Presensi Mata Pelajaran -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold mb-4">
                        <span class="material-icons text-2xl">class</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Presensi Jam Pelajaran (Mapel)</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Fitur presensi di kelas bagi Guru Mata Pelajaran dengan antarmuka split view: pemindaian kamera langsung dan penyesuaian status cepat (Hadir, Sakit, Izin, Alpa, Bolos).
                    </p>
                </div>

                <!-- 4. Pengajuan Izin & Sakit Digital -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold mb-4">
                        <span class="material-icons text-2xl">event_note</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Pengajuan Izin Online Orang Tua</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Orang tua dapat mengajukan permohonan izin atau sakit langsung dari ponsel dengan mengunggah foto surat dokter/keterangan, diproses cepat oleh Wali Kelas & Admin.
                    </p>
                </div>

                <!-- 5. Matriks Presensi & Ekspor Excel -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-2xl bg-teal-100 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold mb-4">
                        <span class="material-icons text-2xl">calendar_view_month</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Matriks Kehadiran & Ekspor Excel</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Rekapitulasi visual baris-kolom per tanggal dalam rentang bulanan atau triwulan, dilengkapi kemampuan koreksi status per tanggal dan ekspor data langsung ke file XLSX.
                    </p>
                </div>

                <!-- 6. Visual Analytics & Charts -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold mb-4">
                        <span class="material-icons text-2xl">pie_chart</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Grafik & Analitik Kehadiran</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Visualisasi persentase kehadiran siswa dan kelas melalui Doughnut Chart dan Bar Chart Timeline interaktif untuk evaluasi komprehensif tingkat kehadiran sekolah.
                    </p>
                </div>

                <!-- 7. Monitoring Presensi Guru -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold mb-4">
                        <span class="material-icons text-2xl">badge</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Rekapitulasi Kehadiran Guru</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Dasbor khusus pencatatan jam masuk & pulang tenaga pendidik, tren kehadiran bulanan, serta rekapitulasi statistik kehadiran guru untuk manajemen sekolah.
                    </p>
                </div>

                <!-- 8. Chat & Konsultasi Interaktif -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-2xl bg-cyan-100 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 flex items-center justify-center font-bold mb-4">
                        <span class="material-icons text-2xl">forum</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Pusat Pesan & Konsultasi Chat</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Kanal komunikasi langsung dua arah antara Orang Tua siswa dengan Guru Wali Kelas atau Staf Administrasi sekolah untuk mendiskusikan perkembangan anak.
                    </p>
                </div>

                <!-- 9. Generator Kartu QR Siap Cetak -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-2xl bg-orange-100 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 flex items-center justify-center font-bold mb-4">
                        <span class="material-icons text-2xl">print</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Kartu Identitas QR Siswa</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Fitur pencetakan kartu fisik QR Code siswa dengan format grid rapi dan styling *print-friendly* untuk mempermudah pindaian di Kiosk gerbang sekolah.
                    </p>
                </div>

            </div>
        </div>

        <!-- ================= MATRIKS HAK AKSES & PERAN ================= -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-10 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6">
            <div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold mb-2">
                    <span class="material-icons text-xs">admin_panel_settings</span>
                    <span>Role-Based Access Control</span>
                </div>
                <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white">
                    Matriks Peran & Hak Akses Pengguna
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Setiap peran pengguna memiliki tampilan dan fungsionalitas yang disesuaikan secara presisi:
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                
                <!-- Admin Role -->
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-850/60 border border-slate-200/60 dark:border-slate-800 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-sky-500">manage_accounts</span>
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">Admin & Operator</h4>
                    </div>
                    <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1.5 list-disc list-inside">
                        <li>Kontrol penuh rekapitulasi kehadiran sekolah</li>
                        <li>Persetujuan/penolakan izin siswa</li>
                        <li>Generator laporan PDF dan analitik grafik</li>
                        <li>Statistik kehadiran guru & kustomisasi tema</li>
                    </ul>
                </div>

                <!-- Wali Kelas -->
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-850/60 border border-slate-200/60 dark:border-slate-800 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-emerald-500">supervised_user_circle</span>
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">Guru Wali Kelas</h4>
                    </div>
                    <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1.5 list-disc list-inside">
                        <li>Monitoring harian kelas binaan</li>
                        <li>Matriks absensi bulanan & triwulan</li>
                        <li>Validasi surat izin siswa perwalian</li>
                        <li>Ekspor Excel & analitik performa kelas</li>
                    </ul>
                </div>

                <!-- Guru Mapel -->
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-850/60 border border-slate-200/60 dark:border-slate-800 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-indigo-500">menu_book</span>
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">Guru Mata Pelajaran</h4>
                    </div>
                    <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1.5 list-disc list-inside">
                        <li>Jadwal mengajar harian terintegrasi</li>
                        <li>Sesi presensi per jam tatap muka</li>
                        <li>Rekap kehadiran siswa per mata pelajaran</li>
                        <li>Akses Single Sign-On (SSO) LMS Mokopani</li>
                    </ul>
                </div>

                <!-- Satpam / Piket -->
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-850/60 border border-slate-200/60 dark:border-slate-800 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-amber-500">security</span>
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">Petugas Satpam & Piket</h4>
                    </div>
                    <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1.5 list-disc list-inside">
                        <li>Operasional Kiosk Scanner masuk/pulang</li>
                        <li>Pemindaian barcode/QR surat izin keluar</li>
                        <li>Verifikasi status izin siswa secara real-time</li>
                    </ul>
                </div>

                <!-- Orang Tua -->
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-850/60 border border-slate-200/60 dark:border-slate-800 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-purple-500">family_restroom</span>
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">Orang Tua / Wali</h4>
                    </div>
                    <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1.5 list-disc list-inside">
                        <li>Notifikasi & kalender kehadiran anak</li>
                        <li>Pengajuan surat izin/sakit berlampiran</li>
                        <li>Pantau riwayat presensi harian & mapel</li>
                        <li>Konsultasi chat dengan wali kelas</li>
                    </ul>
                </div>

                <!-- Siswa -->
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-850/60 border border-slate-200/60 dark:border-slate-800 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-teal-500">school</span>
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">Siswa / Peserta Didik</h4>
                    </div>
                    <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1.5 list-disc list-inside">
                        <li>Kartu identitas presensi QR Code unik</li>
                        <li>Pindai mandiri cepat di Kiosk gerbang</li>
                        <li>Feedback suara sapaan saat presensi</li>
                    </ul>
                </div>

            </div>
        </div>

        <!-- ================= TEKNOLOGI & SPESIFIKASI ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold">
                        <span class="material-icons">code</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Spesifikasi Teknologi</h3>
                        <p class="text-xs text-slate-400">Teknologi modern, tangguh, dan skalabel</p>
                    </div>
                </div>
                
                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-850/60">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Backend Framework</span>
                        <span class="font-semibold text-slate-500 dark:text-slate-400">Laravel PHP & Eloquent ORM</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-850/60">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Styling & UI Engine</span>
                        <span class="font-semibold text-slate-500 dark:text-slate-400">TailwindCSS & Glassmorphic Tokens</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-850/60">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Reaktivitas Frontend</span>
                        <span class="font-semibold text-slate-500 dark:text-slate-400">Alpine.js v3 & Chart.js</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-850/60">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Sintesis Suara & Kamera</span>
                        <span class="font-semibold text-slate-500 dark:text-slate-400">Web Speech API & HTML5 QR Scanner</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-850/60">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Mobile Support</span>
                        <span class="font-semibold text-slate-500 dark:text-slate-400">PWA Manifest & Bottom Navigation Dock</span>
                    </div>
                </div>
            </div>

            <!-- Developer & Info Card -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-6">
                <div class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                            <span class="material-icons">domain</span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Informasi Rilis & Pengembang</h3>
                            <p class="text-xs text-slate-400">Kemitraan pengembangan sistem sekolah</p>
                        </div>
                    </div>
                    
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Aplikasi presensi ini dikembangkan secara berkesinambungan untuk menyediakan solusi manajemen kehadiran sekolah yang andal, aman, dan mudah dioperasikan di berbagai perangkat.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Pengembang</span>
                        <a href="https://www.zahradev.online" target="_blank" class="font-extrabold text-sm text-sky-600 dark:text-sky-400 hover:underline">
                            ZahraDev
                        </a>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Versi Aplikasi</span>
                        <span class="font-extrabold text-sm text-emerald-600 dark:text-emerald-400">v2.0 Stable (2026)</span>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Dukungan Kontak</span>
                        <a href="mailto:emilsalimramadhan@gmail.com" class="font-semibold text-xs text-slate-700 dark:text-slate-300 hover:text-sky-500 transition-colors">
                            emilsalimramadhan@gmail.com
                        </a>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Lisensi</span>
                        <span class="font-semibold text-xs text-slate-700 dark:text-slate-300">Hak Cipta Terlindungi</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
