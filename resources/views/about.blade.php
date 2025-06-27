@extends('layouts.guest')

@section('title', 'Tentang Aplikasi')

@section('content')
<div class="bg-white dark:bg-slate-800 py-24 sm:py-32">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-none">
            <p class="text-base font-semibold leading-7 text-sky-600 dark:text-sky-400">Versi 1.0.0</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">Tentang Aplikasi Absensi</h1>
            <div class="mt-6 grid max-w-xl grid-cols-1 gap-8 text-base leading-7 text-gray-700 dark:text-gray-300 lg:max-w-none lg:grid-cols-2">
                <div>
                    <p>Aplikasi ini adalah sistem absensi modern berbasis QR Code yang dirancang untuk menyederhanakan dan mengotomatiskan proses pencatatan kehadiran di lingkungan sekolah. Dengan platform ini, kami bertujuan untuk meningkatkan efisiensi, akurasi, dan transparansi data kehadiran.</p>
                    <p class="mt-6">Dibangun dengan teknologi web terbaru, aplikasi ini menyediakan antarmuka yang ramah pengguna untuk berbagai peran, termasuk Admin, Guru Wali Kelas, dan Orang Tua Wali, memastikan semua pihak mendapatkan informasi yang relevan dan real-time.</p>
                </div>
                <div>
                    <p>Dari pemindaian QR yang cepat, dasbor statistik yang informatif, hingga sistem pengajuan izin online, semua fitur dirancang untuk mendukung ekosistem sekolah yang lebih terhubung dan terorganisir.</p>
                </div>
            </div>
            <dl class="mt-16 grid grid-cols-1 gap-x-8 gap-y-12 sm:mt-20 sm:grid-cols-2 sm:gap-y-16 lg:mt-24 lg:grid-cols-4">
                <div class="flex flex-col-reverse">
                    <dt class="text-base leading-7 text-gray-600 dark:text-gray-400">Tanggal Rilis</dt>
                    <dd class="text-2xl font-bold leading-9 tracking-tight text-gray-900 dark:text-white">Juni 2024</dd>
                </div>
                <div class="flex flex-col-reverse">
                    <dt class="text-base leading-7 text-gray-600 dark:text-gray-400">Pembuat</dt>
                    <dd class="text-2xl font-bold leading-9 tracking-tight text-gray-900 dark:text-white">zahradev</dd>
                </div>
                <div class="flex flex-col-reverse">
                    <dt class="text-base leading-7 text-gray-600 dark:text-gray-400">Teknologi</dt>
                    <dd class="text-2xl font-bold leading-9 tracking-tight text-gray-900 dark:text-white">Laravel & Tailwind</dd>
                </div>
                <div class="flex flex-col-reverse">
                    <dt class="text-base leading-7 text-gray-600 dark:text-gray-400">Versi</dt>
                    <dd class="text-2xl font-bold leading-9 tracking-tight text-gray-900 dark:text-white">1.0.0</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
