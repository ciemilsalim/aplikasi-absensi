@extends('layouts.guest')

@section('title', 'Tentang Aplikasi')

@section('content')
    <div class="py-16 sm:py-24">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-none">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-sky-50 dark:bg-sky-950/60 border border-sky-200/80 dark:border-sky-800 text-sky-700 dark:text-sky-300 text-xs font-bold mb-4">
                    <span class="material-icons text-sm">verified</span>
                    <span>Versi 2.0 &bull; Enterprise Attendance</span>
                </div>
                
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Sistem Presensi Siswa Modern & Terpadu
                </h1>
                
                <div class="mt-8 grid max-w-xl grid-cols-1 gap-8 text-sm leading-relaxed text-slate-600 dark:text-slate-300 lg:max-w-none lg:grid-cols-2">
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                        <div class="w-10 h-10 rounded-2xl bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold mb-4">
                            <span class="material-icons">qr_code_scanner</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Automasi Presensi Berbasis QR</h3>
                        <p>
                            Aplikasi ini dirancang untuk menyederhanakan dan mengotomatiskan proses pencatatan kehadiran di lingkungan sekolah dengan teknologi pemindaian QR Code instan berkecepatan tinggi, baik pada Kiosk mandiri maupun sesi presensi di dalam kelas.
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold mb-4">
                            <span class="material-icons">hub</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Kolaborasi Multi-Peran</h3>
                        <p>
                            Menyediakan antarmuka terdedikasi untuk Admin Sekolah, Guru Wali Kelas, Guru Mata Pelajaran, Petugas Piket, serta Orang Tua Wali siswa untuk memastikan transparansi rekapitulasi kehadiran dan pengajuan izin yang transparan.
                        </p>
                    </div>
                </div>

                <div class="mt-12 grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Rilis Pertama</span>
                        <span class="text-xl font-extrabold text-slate-900 dark:text-white">Juni 2024</span>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Pengembang</span>
                        <a href="https://www.zahradev.online" target="_blank" class="text-xl font-extrabold text-sky-600 dark:text-sky-400 hover:underline">
                            ZahraDev
                        </a>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Stack Teknologi</span>
                        <span class="text-xl font-extrabold text-slate-900 dark:text-white">Laravel & Tailwind</span>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Versi Rilis</span>
                        <span class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400">v2.0 Stable</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection