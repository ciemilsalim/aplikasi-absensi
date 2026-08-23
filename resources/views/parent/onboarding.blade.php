<html lang="id" class="h-full bg-slate-50 dark:bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Aktivasi Akun Orang Tua - {{ config('app.name', 'Presensi') }}</title>

    <!-- Google Fonts & Material Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: {
                        sky: { 50: '#f0f9ff', 100: '#e0f2fe', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1' }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="h-full bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased font-sans flex flex-col justify-between selection:bg-sky-500 selection:text-white">

    <!-- Header Sederhana -->
    <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 py-3.5 px-4 sticky top-0 z-30 shadow-2xs">
        <div class="max-w-xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold shadow-2xs">
                    <span class="material-icons text-xl">family_restroom</span>
                </div>
                <div>
                    <h1 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white leading-tight">
                        Portal Orang Tua
                    </h1>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Pendaftaran & Aktivasi Akun Wali Siswa</p>
                </div>
            </div>

            <!-- Logout Link -->
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 font-medium transition-colors">
                    <span class="material-icons text-base">logout</span>
                    <span class="hidden sm:inline">Keluar</span>
                </button>
            </form>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-xl w-full mx-auto p-4 sm:p-6 flex flex-col justify-center my-auto"
          x-data="onboardingApp({
              currentStep: 1,
              phoneNumber: '{{ addslashes($parent->phone_number ?? '') }}',
              address: '{{ addslashes($parent->address ?? '') }}',
              classes: {{ json_encode($classes) }},
              connectedStudents: {{ json_encode($connectedStudents) }},
              pendingRequests: {{ json_encode($pendingRequests) }}
          })">

        <!-- Banner Disapa -->
        <div class="text-center mb-5">
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Selamat Datang, Bapak/Ibu! 👋
            </h2>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-md mx-auto">
                Mari hubungkan akun Anda dengan data siswa di sekolah untuk memantau presensi dan kabar belajar anak secara mudah.
            </p>
        </div>

        <!-- Progress Indicator 3 Langkah Sederhana -->
        <div class="mb-6 bg-white dark:bg-slate-900 p-3 sm:p-4 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div class="flex items-center justify-between relative">
                <!-- Progress Bar Line Behind -->
                <div class="absolute top-1/2 left-6 right-6 -translate-y-1/2 h-1 bg-slate-200 dark:bg-slate-800 -z-0"></div>
                <div class="absolute top-1/2 left-6 -translate-y-1/2 h-1 bg-sky-500 transition-all duration-300 -z-0"
                     :style="'width: ' + ((currentStep - 1) / 2 * 100) + '%'"></div>

                <!-- Step 1 Indicator -->
                <button type="button" @click="if(currentStep > 1) currentStep = 1" class="relative z-10 flex flex-col items-center gap-1 group">
                    <div class="w-9 h-9 rounded-2xl flex items-center justify-center text-xs font-bold transition-all"
                         :class="currentStep >= 1 ? 'bg-sky-600 text-white shadow-md shadow-sky-600/30' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400'">
                        <span x-show="currentStep > 1" class="material-icons text-base">check</span>
                        <span x-show="currentStep <= 1">1</span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">No. WhatsApp</span>
                </button>

                <!-- Step 2 Indicator -->
                <button type="button" @click="if(currentStep > 2 || (currentStep === 1 && phoneNumber)) currentStep = 2" class="relative z-10 flex flex-col items-center gap-1 group">
                    <div class="w-9 h-9 rounded-2xl flex items-center justify-center text-xs font-bold transition-all"
                         :class="currentStep >= 2 ? 'bg-sky-600 text-white shadow-md shadow-sky-600/30' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400'">
                        <span x-show="currentStep > 2" class="material-icons text-base">check</span>
                        <span x-show="currentStep <= 2">2</span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">Hubungkan Anak</span>
                </button>

                <!-- Step 3 Indicator -->
                <button type="button" class="relative z-10 flex flex-col items-center gap-1 group">
                    <div class="w-9 h-9 rounded-2xl flex items-center justify-center text-xs font-bold transition-all"
                         :class="currentStep >= 3 ? 'bg-sky-600 text-white shadow-md shadow-sky-600/30' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400'">
                        <span>3</span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">Selesai</span>
                </button>
            </div>
        </div>

        <!-- Alert Pesan Sukses / Gagal -->
        <div x-show="alertMessage" x-cloak x-transition
             class="mb-4 p-3.5 rounded-2xl text-xs font-medium flex items-start gap-2 shadow-2xs"
             :class="alertType === 'success' ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800'">
            <span class="material-icons text-base shrink-0" x-text="alertType === 'success' ? 'check_circle' : 'error_outline'"></span>
            <span class="leading-relaxed" x-text="alertMessage"></span>
        </div>

        <!-- CARD LANGKAH 1: KONTAK & WHATSAPP -->
        <div x-show="currentStep === 1" x-transition class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="w-10 h-10 rounded-2xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold shrink-0">
                    <span class="material-icons text-xl">call</span>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Langkah 1 dari 3: Nomor WhatsApp Aktif</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Digunakan sekolah untuk mengirimkan notifikasi presensi anak Anda</p>
                </div>
            </div>

            <form @submit.prevent="submitStep1()" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Nomor HP / WhatsApp <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">phone</span>
                        <input type="tel" x-model="phoneNumber" placeholder="Contoh: 081234567890" required
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 font-medium transition-all">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Pastikan nomor aktif agar menerima laporan presensi harian.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Alamat Tempat Tinggal (Opsional)
                    </label>
                    <textarea x-model="address" rows="2" placeholder="Masukkan alamat singkat rumah Anda..."
                              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 font-medium transition-all resize-none"></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="loading"
                            class="w-full py-3.5 px-5 rounded-2xl bg-sky-600 hover:bg-sky-500 active:scale-98 text-white font-extrabold text-sm shadow-lg shadow-sky-600/30 flex items-center justify-center gap-2 transition-all">
                        <span x-show="!loading">Lanjut ke Langkah 2: Hubungkan Anak</span>
                        <span x-show="!loading" class="material-icons text-lg">arrow_forward</span>
                        <span x-show="loading" class="material-icons animate-spin text-lg">sync</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- CARD LANGKAH 2: HUBUNGKAN ANAK -->
        <div x-show="currentStep === 2" x-cloak x-transition class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="w-10 h-10 rounded-2xl bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold shrink-0">
                    <span class="material-icons text-xl">school</span>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Langkah 2 dari 3: Hubungkan Data Anak</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Pilih kelas dan nama anak Anda yang bersekolah di sini</p>
                </div>
            </div>

            <!-- Form Tambah Anak -->
            <form @submit.prevent="submitStep2()" class="space-y-3.5 bg-slate-50 dark:bg-slate-850 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700/60">
                <div class="text-xs font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span class="material-icons text-sky-500 text-base">person_add</span>
                    <span>Cari & Tambahkan Anak</span>
                </div>

                <!-- 1. Pilih Kelas -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        1. Pilih Kelas Siswa <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="selectedClassId" @change="onClassChange()" required
                            class="w-full py-2.5 px-3.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                        <option value="">-- Pilih Kelas Siswa --</option>
                        <template x-for="c in classes" :key="c.id">
                            <option :value="c.id" x-text="'Kelas ' + c.name"></option>
                        </template>
                    </select>
                </div>

                <!-- 2. Ketik Nama Siswa (Text Input dengan Deteksi Kemiripan 90% Tanpa NIS) -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        2. Ketik Nama Anak Anda <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" x-model="typedStudentName" :disabled="!selectedClassId" required
                           placeholder="Contoh: Ketik nama anak Anda..."
                           class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm font-semibold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 disabled:opacity-50 transition-all">

                    <!-- Kartu Hasil Pencocokan Kemiripan 90% (HANYA MENAMPILKAN NAMA TANPA NIS) -->
                    <div x-show="matchedStudent" x-cloak class="mt-2 p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-700/80 flex items-center justify-between gap-3 shadow-2xs">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="material-icons text-emerald-600 dark:text-emerald-400 text-xl shrink-0">check_circle</span>
                            <div class="min-w-0">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-300 block">Siswa Ditemukan di Database</span>
                                <strong class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white block truncate" x-text="matchedStudent?.nameOnly"></strong>
                            </div>
                        </div>
                        <button type="button" @click="typedStudentName = matchedStudent.nameOnly; selectedStudentId = matchedStudent.student.id"
                                class="px-2.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-extrabold shadow-2xs transition-all shrink-0">
                            Pilih Siswa Ini
                        </button>
                    </div>

                    <div x-show="typedStudentName.trim().length >= 3 && !matchedStudent && selectedClassId" x-cloak class="mt-1.5 text-[10px] text-amber-600 dark:text-amber-400 italic">
                        ⚠️ Belum ditemukan siswa dengan kemiripan 90% di kelas ini. Periksa kembali ejaan nama anak Anda.
                    </div>
                </div>

                <!-- 3. NIS / Kode Verifikasi (Optional for auto-match) -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center justify-between">
                        <span>3. NIS (Nomor Induk Siswa) / Kode Verifikasi</span>
                        <span class="text-[10px] text-sky-600 dark:text-sky-400 font-semibold">(Untuk Verifikasi Otomatis)</span>
                    </label>
                    <input type="text" x-model="verificationCode" placeholder="Masukkan NIS siswa (jika tahu)..."
                           class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 font-medium transition-all">
                    
                    <div class="mt-1.5 p-2 rounded-xl bg-sky-50/70 dark:bg-sky-950/40 text-[10px] text-sky-800 dark:text-sky-300 flex items-start gap-1.5">
                        <span class="material-icons text-sm text-sky-500 shrink-0">info</span>
                        <span>
                            <strong>Tips Orang Tua:</strong> Masukkan NIS siswa agar akun otomatis <strong>langsung aktif instan</strong>. Jika belum tahu NIS, tetap klik simpan dan sekolah akan memverifikasinya.
                        </span>
                    </div>
                </div>

                <!-- Hubungan Keluarga -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        4. Hubungan Keluarga
                    </label>
                    <select x-model="relationship"
                            class="w-full py-2 px-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                        <option value="Orang Tua">Orang Tua (Ayah / Ibu)</option>
                        <option value="Wali Siswa">Wali Siswa / Kerabat</option>
                    </select>
                </div>

                <button type="submit" :disabled="loading || (!selectedStudentId && !typedStudentName.trim())"
                        class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white dark:bg-sky-600 dark:hover:bg-sky-500 font-bold text-xs shadow-md flex items-center justify-center gap-1.5 transition-all disabled:opacity-50">
                    <span class="material-icons text-base">add_circle_outline</span>
                    <span>Hubungkan Siswa Ini</span>
                </button>
            </form>

            <!-- Daftar Anak Terhubung & Pending -->
            <div class="space-y-3 pt-1">
                <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Daftar Anak Anda ( Total: <span x-text="connectedStudents.length + pendingRequests.length"></span> )
                </h4>

                <!-- List Siswa Terverifikasi -->
                <template x-for="st in connectedStudents" :key="'st-' + st.id">
                    <div class="p-3 rounded-2xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold text-xs shrink-0">
                                <span class="material-icons text-base">verified</span>
                            </div>
                            <div class="min-w-0">
                                <h5 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="st.name"></h5>
                                <p class="text-[10px] text-emerald-700 dark:text-emerald-300 truncate">
                                    Kelas <span x-text="st.school_class ? st.school_class.name : '-'"></span> &bull; Terverifikasi Aktif
                                </p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-200 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 shrink-0">
                            Aktif
                        </span>
                    </div>
                </template>

                <!-- List Pengajuan Pending -->
                <template x-for="req in pendingRequests" :key="'req-' + req.id">
                    <div class="p-3 rounded-2xl bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-xs shrink-0">
                                <span class="material-icons text-base">hourglass_top</span>
                            </div>
                            <div class="min-w-0">
                                <h5 class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="req.student.name"></h5>
                                <p class="text-[10px] text-amber-700 dark:text-amber-300 truncate">
                                    Kelas <span x-text="req.student.school_class ? req.student.school_class.name : '-'"></span> &bull; Menunggu Verifikasi Sekolah
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="removeRequest(req.student_id)"
                                class="p-1 rounded-lg text-amber-600 hover:text-rose-600 hover:bg-amber-100 dark:hover:bg-amber-900/60 transition-colors"
                                title="Batalkan Pengajuan">
                            <span class="material-icons text-base">cancel</span>
                        </button>
                    </div>
                </template>

                <div x-show="connectedStudents.length === 0 && pendingRequests.length === 0"
                     class="p-4 text-center text-xs text-slate-400 italic border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                    Belum ada siswa yang ditambahkan. Silakan pilih kelas dan nama anak di atas.
                </div>
            </div>

            <!-- Tombol Navigasi Langkah 2 -->
            <div class="flex items-center gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="currentStep = 1"
                        class="py-3 px-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-200 transition-colors">
                    Kembali
                </button>
                <button type="button" @click="if(connectedStudents.length > 0 || pendingRequests.length > 0) currentStep = 3"
                        :disabled="connectedStudents.length === 0 && pendingRequests.length === 0"
                        class="flex-1 py-3 px-4 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-extrabold text-xs shadow-lg shadow-sky-600/20 flex items-center justify-center gap-1.5 transition-all disabled:opacity-40">
                    <span>Lanjut ke Langkah 3: Konfirmasi</span>
                    <span class="material-icons text-base">arrow_forward</span>
                </button>
            </div>
        </div>

        <!-- CARD LANGKAH 3: PANDUAN RINGKAS & SELESAI -->
        <div x-show="currentStep === 3" x-cloak x-transition class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <div class="text-center py-2 space-y-2">
                <div class="w-14 h-14 rounded-3xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 mx-auto flex items-center justify-center shadow-lg shadow-emerald-500/10">
                    <span class="material-icons text-3xl">task_alt</span>
                </div>
                <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">Pendaftaran Siap Digunakan!</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    Data anak Anda telah tersimpan. Berikut panduan singkat cara menggunakan aplikasi ini:
                </p>
            </div>

            <!-- 3 Ringkasan Fitur Ramah Orang Tua -->
            <div class="space-y-2.5">
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-100 dark:border-slate-800 flex items-start gap-3">
                    <div class="p-2 rounded-xl bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 shrink-0">
                        <span class="material-icons text-lg">event_available</span>
                    </div>
                    <div class="text-xs">
                        <strong class="text-slate-900 dark:text-white block font-bold">1. Pantau Presensi Harian</strong>
                        <span class="text-slate-500 dark:text-slate-400">Lihat jam masuk dan jam pulang anak Anda secara real-time setiap hari sekolah.</span>
                    </div>
                </div>

                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-100 dark:border-slate-800 flex items-start gap-3">
                    <div class="p-2 rounded-xl bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400 shrink-0">
                        <span class="material-icons text-lg">edit_document</span>
                    </div>
                    <div class="text-xs">
                        <strong class="text-slate-900 dark:text-white block font-bold">2. Permohonan Izin / Sakit Online</strong>
                        <span class="text-slate-500 dark:text-slate-400">Kirimkan surat izin sakit atau permohonan izin anak langsung ke wali kelas tanpa repot.</span>
                    </div>
                </div>

                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-100 dark:border-slate-800 flex items-start gap-3">
                    <div class="p-2 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 shrink-0">
                        <span class="material-icons text-lg">chat</span>
                    </div>
                    <div class="text-xs">
                        <strong class="text-slate-900 dark:text-white block font-bold">3. Obrolan Langsung Guru</strong>
                        <span class="text-slate-500 dark:text-slate-400">Komunikasi resmi dengan wali kelas dan admin sekolah terkait perkembangan anak.</span>
                    </div>
                </div>
            </div>

            <!-- Form Selesai Onboarding -->
            <form action="{{ route('parent.onboarding.complete') }}" method="POST" class="pt-2">
                @csrf
                <button type="submit"
                        class="w-full py-3.5 px-5 rounded-2xl bg-gradient-to-tr from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 active:scale-98 text-white font-extrabold text-sm shadow-xl shadow-sky-600/30 flex items-center justify-center gap-2 transition-all">
                    <span>Selesai & Masuk ke Dasbor Utama</span>
                    <span class="material-icons text-lg">dashboard</span>
                </button>
            </form>
        </div>

    </main>

    <!-- Footer Sederhana -->
    <footer class="py-4 text-center text-xs text-slate-400 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <p>&copy; {{ date('Y') }} {{ config('app.name', 'Presensi') }} &bull; Sistem Presensi Digital Sekolah</p>
    </footer>

    <!-- JavaScript Alpine App -->
    <script>
        function onboardingApp(config) {
            return {
                currentStep: config.currentStep || 1,
                phoneNumber: config.phoneNumber || '',
                address: config.address || '',
                classes: config.classes || [],
                connectedStudents: config.connectedStudents || [],
                pendingRequests: config.pendingRequests || [],
                selectedClassId: '',
                selectedStudentId: '',
                typedStudentName: '',
                verificationCode: '',
                relationship: 'Orang Tua',
                loading: false,
                alertMessage: '',
                alertType: 'success',

                get availableStudents() {
                    if (!this.selectedClassId) return [];
                    const cls = this.classes.find(c => String(c.id) === String(this.selectedClassId));
                    return cls ? (cls.students || []) : [];
                },

                get matchedStudent() {
                    if (!this.selectedClassId || !this.typedStudentName || this.typedStudentName.trim().length < 2) {
                        return null;
                    }

                    const search = this.typedStudentName.trim().toLowerCase();
                    const students = this.availableStudents;
                    let bestMatch = null;
                    let maxScore = 0;

                    for (const s of students) {
                        const candidate = s.name.trim().toLowerCase();
                        let score = 0;

                        if (candidate === search) {
                            score = 100;
                        } else if (candidate.includes(search) || search.includes(candidate)) {
                            const lenMin = Math.min(search.length, candidate.length);
                            const lenMax = Math.max(search.length, candidate.length);
                            score = (lenMin / lenMax) * 100;
                        } else {
                            score = this.calcSimilarity(search, candidate);
                        }

                        if (score > maxScore) {
                            maxScore = score;
                            bestMatch = s;
                        }
                    }

                    if (bestMatch && maxScore >= 80) {
                        return {
                            student: bestMatch,
                            score: Math.round(maxScore),
                            nameOnly: bestMatch.name
                        };
                    }

                    return null;
                },

                calcSimilarity(s1, s2) {
                    let longer = s1.length >= s2.length ? s1 : s2;
                    let shorter = s1.length < s2.length ? s1 : s2;
                    if (longer.length === 0) return 100;

                    let costs = [];
                    for (let i = 0; i <= s1.length; i++) {
                        let lastValue = i;
                        for (let j = 0; j <= s2.length; j++) {
                            if (i === 0) costs[j] = j;
                            else {
                                if (j > 0) {
                                    let newValue = costs[j - 1];
                                    if (s1.charAt(i - 1) !== s2.charAt(j - 1)) {
                                        newValue = Math.min(Math.min(newValue, lastValue), costs[j]) + 1;
                                    }
                                    costs[j - 1] = lastValue;
                                    lastValue = newValue;
                                }
                            }
                        }
                        if (i > 0) costs[s2.length] = lastValue;
                    }
                    return ((longer.length - costs[s2.length]) / parseFloat(longer.length)) * 100;
                },

                onClassChange() {
                    this.selectedStudentId = '';
                    this.typedStudentName = '';
                },

                showAlert(msg, type = 'success') {
                    this.alertMessage = msg;
                    this.alertType = type;
                    setTimeout(() => {
                        this.alertMessage = '';
                    }, 6000);
                },

                async submitStep1() {
                    if (!this.phoneNumber) {
                        this.showAlert('Harap isi nomor HP/WhatsApp aktif.', 'error');
                        return;
                    }

                    this.loading = true;
                    try {
                        const res = await fetch('{{ route("parent.onboarding.step1") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                phone_number: this.phoneNumber,
                                address: this.address
                            })
                        });

                        const data = await res.json();
                        this.loading = false;

                        if (data.success) {
                            this.showAlert('Data kontak berhasil disimpan.', 'success');
                            this.currentStep = 2;
                        } else {
                            this.showAlert(data.message || 'Gagal menyimpan data.', 'error');
                        }
                    } catch (e) {
                        this.loading = false;
                        this.showAlert('Terjadi kesalahan koneksi server.', 'error');
                    }
                },

                async submitStep2() {
                    const targetStudentId = this.matchedStudent ? this.matchedStudent.student.id : this.selectedStudentId;

                    if ((!targetStudentId && !this.typedStudentName.trim()) || !this.selectedClassId) {
                        this.showAlert('Harap pilih kelas dan ketik nama anak Anda.', 'error');
                        return;
                    }

                    this.loading = true;
                    try {
                        const res = await fetch('{{ route("parent.onboarding.verify_student") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                school_class_id: this.selectedClassId,
                                student_id: targetStudentId,
                                student_name: this.typedStudentName,
                                verification_code: this.verificationCode,
                                relationship: this.relationship
                            })
                        });

                        const data = await res.json();
                        this.loading = false;

                        if (data.success) {
                            this.showAlert(data.message, 'success');
                            
                            if (data.auto_approved) {
                                this.connectedStudents.push(data.student);
                            } else {
                                this.pendingRequests.push({
                                    id: Date.now(),
                                    student_id: data.student.id,
                                    student: data.student
                                });
                            }

                            // Reset form penambahan anak
                            this.selectedStudentId = '';
                            this.typedStudentName = '';
                            this.verificationCode = '';
                        } else {
                            this.showAlert(data.message || 'Gagal mengirim pengajuan.', 'error');
                        }
                    } catch (e) {
                        this.loading = false;
                        this.showAlert('Terjadi kesalahan saat verifikasi.', 'error');
                    }
                },

                async removeRequest(studentId) {
                    if (!confirm('Apakah Anda yakin ingin membatalkan pengajuan siswa ini?')) return;

                    try {
                        const res = await fetch(`{{ url('/parent/onboarding/request') }}/${studentId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await res.json();
                        if (data.success) {
                            this.pendingRequests = this.pendingRequests.filter(r => r.student_id !== studentId);
                            this.connectedStudents = this.connectedStudents.filter(s => s.id !== studentId);
                            this.showAlert(data.message, 'success');
                        }
                    } catch (e) {
                        this.showAlert('Gagal menghapus data.', 'error');
                    }
                }
            }
        }
    </script>
</body>
</html>
