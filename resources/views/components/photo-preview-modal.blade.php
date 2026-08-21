<div x-data="globalPhotoPreviewModal()" 
     x-show="isOpen" 
     x-cloak 
     @keydown.escape.window="close()" 
     @open-photo-preview.window="open($event.detail)"
     class="fixed inset-0 z-[999] flex items-center justify-center p-4 sm:p-6"
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
         class="fixed inset-0 bg-slate-950/85 backdrop-blur-md"></div>

    <!-- Modal Card -->
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-90 translate-y-4"
         @click.outside="close()" 
         class="relative w-full max-w-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl overflow-hidden z-10 flex flex-col items-center text-center p-6 sm:p-7">
        
        <!-- Close Button (Top Right) -->
        <button @click="close()" 
                class="absolute top-4 right-4 p-2 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-300 transition-colors focus:outline-none ring-1 ring-slate-200 dark:ring-slate-750" 
                title="Tutup (Esc)">
            <span class="material-icons text-xl">close</span>
        </button>

        <!-- Preview Image Frame -->
        <div class="relative w-48 h-48 sm:w-56 sm:h-56 rounded-2xl sm:rounded-3xl overflow-hidden ring-4 ring-sky-500/25 bg-slate-100 dark:bg-slate-950 shadow-inner my-2 flex items-center justify-center">
            <img :src="photoUrl" 
                 :alt="name" 
                 class="w-full h-full object-cover select-none pointer-events-auto" 
                 onerror="this.src='https://ui-avatars.com/api/?name=' + encodeURIComponent(name || 'Siswa') + '&color=0284c7&background=e0f2fe&size=256';" />
        </div>

        <!-- Student Details -->
        <div class="mt-3 w-full">
            <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight break-words" x-text="name"></h3>
            <div class="flex items-center justify-center gap-1.5 mt-1 text-xs font-semibold text-sky-600 dark:text-sky-400" x-show="subtitle">
                <span class="material-icons text-sm text-sky-500">school</span>
                <span x-text="subtitle"></span>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-5 flex items-center justify-center gap-2.5 w-full pt-4 border-t border-slate-100 dark:border-slate-800">
            <a :href="photoUrl" target="_blank" rel="noopener noreferrer" 
               class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all border border-slate-200 dark:border-slate-700">
                <span class="material-icons text-sm">open_in_new</span>
                <span>Ukuran Asli</span>
            </a>
            <button @click="close()" 
                    class="inline-flex items-center justify-center gap-1.5 px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold shadow-md shadow-sky-600/30 transition-all">
                <span>Tutup</span>
            </button>
        </div>
    </div>
</div>

<script>
    window.globalPhotoPreviewModal = function() {
        return {
            isOpen: false,
            photoUrl: '',
            name: '',
            subtitle: '',
            open(data) {
                this.photoUrl = data.photoUrl || '';
                this.name = data.name || 'Foto Siswa';
                this.subtitle = data.subtitle || '';
                this.isOpen = true;
            },
            close() {
                this.isOpen = false;
            }
        };
    };

    window.previewStudentPhoto = function(photoUrl, name, subtitle) {
        if (!photoUrl) return;
        window.dispatchEvent(new CustomEvent('open-photo-preview', {
            detail: {
                photoUrl: photoUrl,
                name: name || 'Foto Siswa',
                subtitle: subtitle || ''
            }
        }));
    };

    // Auto-delegate click listener for any image with class student-avatar or data-preview-photo
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(e) {
            const avatarImg = e.target.closest('img.student-avatar, img[data-preview-photo="true"]');
            if (avatarImg && avatarImg.src) {
                const name = avatarImg.dataset.studentName || avatarImg.alt || 'Foto Siswa';
                const sub = avatarImg.dataset.studentSub || avatarImg.dataset.studentClass || '';
                window.previewStudentPhoto(avatarImg.src, name, sub);
            }
        });
    });
</script>
