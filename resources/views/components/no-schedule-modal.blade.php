<div x-data="{
        isOpen: false,
        open() { this.isOpen = true; },
        close() { this.isOpen = false; }
     }" 
     x-show="isOpen" 
     x-cloak 
     @keydown.escape.window="close()" 
     @open-no-schedule-modal.window="open()"
     class="fixed inset-0 z-[999] flex items-center justify-center p-4 sm:p-6"
     style="display: none;"
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop Blur with Animation -->
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="close()" 
         class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs"></div>

    <!-- Modal Content Card -->
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-90 translate-y-4"
         @click.outside="close()" 
         class="relative w-full max-w-sm bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl shadow-2xl overflow-hidden z-10 flex flex-col items-center text-center p-6 sm:p-7">
        
        <!-- Close Button (Top Right) -->
        <button @click="close()" 
                type="button"
                class="absolute top-4 right-4 p-2 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-300 transition-colors focus:outline-none ring-1 ring-slate-200 dark:ring-slate-750" 
                title="Tutup (Esc)">
            <span class="material-icons text-xl">close</span>
        </button>

        <!-- Warning Badge Icon -->
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-500 dark:text-amber-400 mb-3 ring-8 ring-amber-500/10 shrink-0">
            <span class="material-icons text-3xl">event_busy</span>
        </div>
        
        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Tidak Ada Jadwal Mengajar</h3>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
            Tidak ada jadwal mengajar aktif hari ini untuk melakukan presensi mata pelajaran.
        </p>

        <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-2.5 w-full">
            <a href="{{ route('teacher.dashboard') }}" 
               class="w-full min-h-[44px] px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-200 transition-colors text-center inline-flex items-center justify-center gap-1.5 border border-slate-200/80 dark:border-slate-700">
                <span class="material-icons text-base">dashboard</span>
                <span>Cek Dasbor</span>
            </a>
            <button @click="close()" type="button" 
                    class="w-full min-h-[44px] px-4 py-2.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-sky-600/25 transition-all active:scale-95 text-center">
                Mengerti
            </button>
        </div>
    </div>
</div>
