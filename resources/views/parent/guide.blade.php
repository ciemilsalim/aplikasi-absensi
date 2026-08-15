<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Dasbor Orang Tua', 'url' => route('parent.dashboard')],
            ['title' => 'Panduan Penggunaan', 'url' => route('parent.guide')]
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Panduan Penggunaan Aplikasi') }}
        </h2>
    </x-slot>

    <div class="space-y-6 max-w-5xl mx-auto pb-12">
        
        <!-- Header Banner Panduan -->
        <div class="bg-gradient-to-r from-sky-600 to-indigo-700 rounded-2xl p-6 sm:p-8 text-white shadow-lg relative overflow-hidden">
            <div class="relative z-10 max-w-2xl">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-medium text-sky-100 mb-3">
                    <span class="material-icons text-sm">auto_stories</span> Petunjuk Resmi Orang Tua / Wali
                </span>
                <h3 class="text-2xl sm:text-3xl font-bold tracking-tight">Selamat Datang di Panduan Aplikasi Presensi!</h3>
                <p class="text-sky-100 text-sm sm:text-base mt-2 leading-relaxed">
                    Halaman ini dirancang khusus untuk membantu Bapak/Ibu Orang Tua dan Wali Murid dalam memantau kehadiran, mengajukan izin, serta berkomunikasi dengan pihak sekolah secara mudah.
                </p>
            </div>
            <div class="absolute -right-8 -bottom-8 opacity-20 pointer-events-none hidden sm:block">
                <span class="material-icons text-[180px]">help_center</span>
            </div>
        </div>

        <!-- Kartu Pintasan Topik Panduan -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            <a href="#status-kehadiran" class="p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-sky-500 dark:hover:border-sky-500 transition-all shadow-sm flex flex-col items-center text-center group">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                    <span class="material-icons text-2xl">verified</span>
                </div>
                <h4 class="font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-100">Status Kehadiran</h4>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Arti warna & status</p>
            </a>
            
            <a href="#ajukan-izin" class="p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-sky-500 dark:hover:border-sky-500 transition-all shadow-sm flex flex-col items-center text-center group">
                <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                    <span class="material-icons text-2xl">assignment</span>
                </div>
                <h4 class="font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-100">Ajukan Izin/Sakit</h4>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Cara membuat surat izin</p>
            </a>

            <a href="#fitur-chat" class="p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-sky-500 dark:hover:border-sky-500 transition-all shadow-sm flex flex-col items-center text-center group">
                <div class="w-12 h-12 rounded-xl bg-sky-100 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                    <span class="material-icons text-2xl">forum</span>
                </div>
                <h4 class="font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-100">Chat & Pesan</h4>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Hubungi Wali Kelas/Admin</p>
            </a>

            <a href="#faq" class="p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-sky-500 dark:hover:border-sky-500 transition-all shadow-sm flex flex-col items-center text-center group">
                <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                    <span class="material-icons text-2xl">quiz</span>
                </div>
                <h4 class="font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-100">Tanya Jawab</h4>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Pertanyaan umum (FAQ)</p>
            </a>
        </div>

        <!-- 1. Penjelasan Status Kehadiran -->
        <div id="status-kehadiran" class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100 dark:border-slate-700">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-lg">
                    <span class="material-icons text-xl">verified</span>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-slate-100 text-lg">1. Memahami Status Kehadiran Putra/Putri Anda</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Penjelasan warna badge dan keterangan waktu presensi</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="p-3.5 bg-slate-50 dark:bg-slate-700/40 rounded-xl border border-slate-100 dark:border-slate-700 flex items-start gap-3">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-900/60 dark:text-green-300 flex-shrink-0 mt-0.5">Hadir</span>
                    <div>
                        <p class="font-bold text-xs text-slate-800 dark:text-slate-200">Hadir Tepat Waktu</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Siswa melakukan scan masuk di sekolah sebelum batas jam keterlambatan.</p>
                    </div>
                </div>

                <div class="p-3.5 bg-slate-50 dark:bg-slate-700/40 rounded-xl border border-slate-100 dark:border-slate-700 flex items-start gap-3">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-800 dark:bg-red-900/60 dark:text-red-300 flex-shrink-0 mt-0.5">Terlambat</span>
                    <div>
                        <p class="font-bold text-xs text-slate-800 dark:text-slate-200">Hadir Terlambat</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Siswa datang dan melakukan scan masuk setelah jam batas waktu tiba di sekolah.</p>
                    </div>
                </div>

                <div class="p-3.5 bg-slate-50 dark:bg-slate-700/40 rounded-xl border border-slate-100 dark:border-slate-700 flex items-start gap-3">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300 flex-shrink-0 mt-0.5">Izin</span>
                    <div>
                        <p class="font-bold text-xs text-slate-800 dark:text-slate-200">Izin Keperluan Keluarga/Acara</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Orang tua telah mengajukan permohonan izin dan telah disetujui sekolah.</p>
                    </div>
                </div>

                <div class="p-3.5 bg-slate-50 dark:bg-slate-700/40 rounded-xl border border-slate-100 dark:border-slate-700 flex items-start gap-3">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 flex-shrink-0 mt-0.5">Sakit</span>
                    <div>
                        <p class="font-bold text-xs text-slate-800 dark:text-slate-200">Sakit dengan Keterangan</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Orang tua telah melaporkan kondisi siswa sakit dan melampirkan keterangan/surat.</p>
                    </div>
                </div>

                <div class="p-3.5 bg-slate-50 dark:bg-slate-700/40 rounded-xl border border-slate-100 dark:border-slate-700 flex items-start gap-3 col-span-1 sm:col-span-2">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-rose-200 text-rose-900 dark:bg-rose-900/80 dark:text-rose-200 flex-shrink-0 mt-0.5">Alpa</span>
                    <div>
                        <p class="font-bold text-xs text-slate-800 dark:text-slate-200">Tanpa Keterangan (Alpa)</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Siswa tidak tercatat scan masuk dan belum ada konfirmasi dari orang tua. Segera ajukan izin atau hubungi Wali Kelas jika anak sakit/izin.</p>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2">
                <span class="material-icons text-base text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5">info</span>
                <div>
                    <span class="font-bold">Catatan Keterangan Pulang:</span>
                    <ul class="list-disc list-inside mt-1 space-y-1 text-amber-900/90 dark:text-amber-200">
                        <li><span class="text-rose-600 dark:text-rose-400 font-semibold">belum absen pulang</span>: Ditampilkan untuk presensi hari ini jika siswa hadir masuk tetapi belum scan pulang.</li>
                        <li><span class="text-rose-600 dark:text-rose-400 font-semibold">tidak absen pulang</span>: Ditampilkan untuk presensi hari yang lalu jika siswa tidak melakukan scan pulang hingga jam sekolah berakhir.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 2. Panduan Mengajukan Izin / Sakit -->
        <div id="ajukan-izin" class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100 dark:border-slate-700">
                <div class="p-2 bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 rounded-lg">
                    <span class="material-icons text-xl">assignment</span>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-slate-100 text-lg">2. Cara Mengajukan Surat Izin atau Sakit</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Langkah mudah bila siswa berhalangan hadir ke sekolah</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex gap-4 items-start">
                    <div class="w-8 h-8 rounded-full bg-sky-600 text-white font-bold text-sm flex items-center justify-center flex-shrink-0">1</div>
                    <div>
                        <h4 class="font-semibold text-sm text-slate-800 dark:text-slate-100">Tekan Tombol "Ajukan Izin atau Sakit"</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tombol ini tersedia pada Dasbor Utama (bagian bawah) atau melalui menu <span class="font-semibold text-slate-700 dark:text-slate-300">Izin Sakit</span> di bilah navigasi bawah.</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <div class="w-8 h-8 rounded-full bg-sky-600 text-white font-bold text-sm flex items-center justify-center flex-shrink-0">2</div>
                    <div>
                        <h4 class="font-semibold text-sm text-slate-800 dark:text-slate-100">Pilih Nama Anak & Jenis Keterangan</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pilih siswa yang akan diajukan izin, lalu pilih tipe keterangan: <span class="font-semibold text-purple-600 dark:text-purple-400">Izin</span> atau <span class="font-semibold text-amber-600 dark:text-amber-400">Sakit</span>.</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <div class="w-8 h-8 rounded-full bg-sky-600 text-white font-bold text-sm flex items-center justify-center flex-shrink-0">3</div>
                    <div>
                        <h4 class="font-semibold text-sm text-slate-800 dark:text-slate-100">Tentukan Tanggal & Tuliskan Alasan</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pilih tanggal mulai hingga tanggal selesai. Tuliskan alasan singkat dan jelas (misal: "Demam tinggi dan berobat ke dokter").</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <div class="w-8 h-8 rounded-full bg-sky-600 text-white font-bold text-sm flex items-center justify-center flex-shrink-0">4</div>
                    <div>
                        <h4 class="font-semibold text-sm text-slate-800 dark:text-slate-100">Unggah Lampiran Surat (Opsional / Disarankan)</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ambil foto surat keterangan dokter atau surat izin bertanda tangan orang tua dari HP Anda, lalu lampirkan pada kolom yang tersedia.</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <div class="w-8 h-8 rounded-full bg-emerald-600 text-white font-bold text-sm flex items-center justify-center flex-shrink-0">5</div>
                    <div>
                        <h4 class="font-semibold text-sm text-slate-800 dark:text-slate-100">Kirim Pengajuan & Pantau Status</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tekan tombol <span class="font-semibold text-sky-600">Simpan Pengajuan</span>. Status pengajuan dapat Anda pantau pada halaman riwayat pengajuan izin (Disetujui / Pending / Ditolak).</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Fitur Obrolan (Chat) -->
        <div id="fitur-chat" class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100 dark:border-slate-700">
                <div class="p-2 bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 rounded-lg">
                    <span class="material-icons text-xl">forum</span>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-slate-100 text-lg">3. Berkomunikasi Melalui Fitur Obrolan (Chat)</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Kirim pesan langsung ke Wali Kelas maupun Admin Sekolah</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 bg-slate-50 dark:bg-slate-700/40 rounded-xl border border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-2 mb-2 text-slate-800 dark:text-slate-100 font-bold text-sm">
                        <span class="material-icons text-amber-500">forum</span> Chat Wali Kelas
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Gunakan tombol <span class="font-semibold text-slate-700 dark:text-slate-300">Obrolan</span> pada menu navigasi bawah atau pada banner peringatan untuk berdiskusi dengan Wali Kelas terkait perkembangan dan presensi harian anak.
                    </p>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-700/40 rounded-xl border border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-2 mb-2 text-slate-800 dark:text-slate-100 font-bold text-sm">
                        <span class="material-icons text-sky-500">support_agent</span> Chat Admin Sekolah
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Jika terdapat kendala teknis akun, masalah koneksi data anak, atau pertanyaan kebagian administrasi sekolah, Anda dapat memilih tombol <span class="font-semibold text-slate-700 dark:text-slate-300">Chat Admin</span>.
                    </p>
                </div>
            </div>
        </div>

        <!-- 4. Tanya Jawab (FAQ) -->
        <div id="faq" class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3 mb-6 pb-3 border-b border-slate-100 dark:border-slate-700">
                <div class="p-2 bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 rounded-lg">
                    <span class="material-icons text-xl">quiz</span>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-slate-100 text-lg">4. Pertanyaan Sering Ditanyakan (FAQ)</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Jawaban cepat untuk kendala yang sering dialami orang tua</p>
                </div>
            </div>

            <div class="space-y-4" x-data="{ openFaq: 1 }">
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                    <button @click="openFaq = (openFaq === 1 ? 0 : 1)" class="w-full p-4 text-left font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-700/50 flex justify-between items-center">
                        <span>Q: Mengapa muncul notifikasi "Ananda belum tercatat kehadirannya"?</span>
                        <span class="material-icons text-base transition-transform" :class="{ 'rotate-180': openFaq === 1 }">expand_more</span>
                    </button>
                    <div x-show="openFaq === 1" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-700 space-y-1">
                        <p>Notifikasi ini muncul secara otomatis saat jam batas presensi tiba namun anak Anda belum terdaftar scan kartu di sekolah.</p>
                        <p class="text-sky-600 dark:text-sky-400 font-semibold mt-1">Solusi: Tekan tombol "Ajukan Izin / Sakit" pada banner jika anak sakit, atau tekan "Chat Wali Kelas" untuk konfirmasi.</p>
                    </div>
                </div>

                <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                    <button @click="openFaq = (openFaq === 2 ? 0 : 2)" class="w-full p-4 text-left font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-700/50 flex justify-between items-center">
                        <span>Q: Bagaimana jika anak saya lupa melakukan scan kartu saat pulang sekolah?</span>
                        <span class="material-icons text-base transition-transform" :class="{ 'rotate-180': openFaq === 2 }">expand_more</span>
                    </button>
                    <div x-show="openFaq === 2" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-700">
                        Status jam pulang akan menampilkan tulisan merah <span class="font-bold text-rose-600">"tidak absen pulang"</span>. Bapak/Ibu tidak perlu panik, status masuk siswa tetap tercatat aman di sistem sekolah.
                    </div>
                </div>

                <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                    <button @click="openFaq = (openFaq === 3 ? 0 : 3)" class="w-full p-4 text-left font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-700/50 flex justify-between items-center">
                        <span>Q: Bagaimana jika saya memiliki 2 anak atau lebih di sekolah ini?</span>
                        <span class="material-icons text-base transition-transform" :class="{ 'rotate-180': openFaq === 3 }">expand_more</span>
                    </button>
                    <div x-show="openFaq === 3" class="p-4 text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-700">
                        Seluruh data anak yang terhubung dengan nomor/akun Bapak/Ibu akan tampil secara otomatis dalam kartu-kartu terpisah pada Dasbor Utama Anda.
                    </div>
                </div>
            </div>
        </div>

        <!-- Bantuan Tambahan & Kontak -->
        <div class="p-6 bg-slate-100 dark:bg-slate-700/50 rounded-2xl text-center">
            <h4 class="font-bold text-slate-800 dark:text-slate-100 text-sm">Masih Membutuhkan Bantuan Lebih Lanjut?</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Tim Admin dan Wali Kelas siap membantu kendala Bapak/Ibu.</p>
            <div class="mt-4 flex justify-center gap-3">
                <a href="{{ route('chat.admin') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors">
                    <span class="material-icons text-base">support_agent</span> Hubungi Admin Sekolah
                </a>
            </div>
        </div>

    </div>
</x-app-layout>
