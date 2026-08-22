<x-app-layout>
    {{-- Page Header (Hanya muncul saat tidak membuka percakapan spesifik di desktop) --}}
    @if(!$selectedParent)
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <div>
                    <x-breadcrumb :breadcrumbs="[['title' => 'Pesan', 'url' => route('admin.chat.index')]]" />
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                        Pesan
                    </h1>
                </div>
            </div>
        </x-slot>
    @endif

    <div x-data="{
        searchQuery: '',
        showTemplates: false,
        messageText: '',
        insertTemplate(text) {
            this.messageText = text;
            this.showTemplates = false;
            this.$nextTick(() => {
                const el = this.$refs.messageInput;
                if (el) {
                    el.style.height = 'auto';
                    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
                    el.focus();
                }
            });
        },
        autoExpand(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 120) + 'px';
        }
    }" class="h-full flex flex-col flex-1 overflow-hidden">
        
        <div class="bg-white dark:bg-slate-900 h-full flex-1 flex overflow-hidden border-0 lg:border lg:border-slate-200/80 lg:dark:border-slate-800 lg:rounded-3xl shadow-xs">
            <div class="flex flex-1 w-full h-full overflow-hidden">
                
                <!-- ========================================== -->
                <!-- 1. SIDEBAR KONTAK (Daftar Orang Tua / Chat) -->
                <!-- ========================================== -->
                <div id="contact-sidebar" class="w-full lg:w-80 xl:w-96 border-r border-slate-200/80 dark:border-slate-800 flex flex-col shrink-0 @if($selectedParent) hidden lg:flex @else flex @endif bg-white dark:bg-slate-900">
                    
                    <!-- Header Sidebar -->
                    <div class="p-4 border-b border-slate-100 dark:border-slate-800 shrink-0 bg-slate-50/60 dark:bg-slate-850/40">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="material-icons text-sky-600 dark:text-sky-400 text-xl">forum</span>
                                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Pesan</h2>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300">
                                {{ $parents->count() }} Kontak
                            </span>
                        </div>

                        <!-- Search Input (Live Filter) -->
                        <div class="relative">
                            <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">search</span>
                            <input type="text" 
                                   x-model="searchQuery" 
                                   placeholder="Cari orang tua / siswa..." 
                                   class="w-full pl-9 pr-8 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-2xs">
                            <button type="button" 
                                    x-show="searchQuery.length > 0" 
                                    @click="searchQuery = ''" 
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <span class="material-icons text-sm">close</span>
                            </button>
                        </div>
                    </div>

                    <!-- Contact List Items -->
                    <div id="contact-list" class="flex-grow overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60 no-scrollbar">
                        @forelse($parents as $parent)
                            @php
                                $isSelected = ($selectedParent && $selectedParent->id === $parent->id);
                                $searchTarget = strtolower($parent->name . ' ' . ($parent->student_subtitle ?? '') . ' ' . ($parent->user->email ?? ''));
                            @endphp
                            <a href="{{ route('admin.chat.index', ['selectedParent' => $parent->id]) }}" 
                               x-show="!searchQuery || '{{ addslashes($searchTarget) }}'.includes(searchQuery.toLowerCase())"
                               class="contact-button w-full text-left p-3.5 sm:p-4 flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-850 transition-colors {{ $isSelected ? 'bg-sky-50/90 dark:bg-sky-950/40 border-l-4 border-sky-500' : '' }}">
                                
                                <!-- Parent Avatar with Online Indicator -->
                                <div class="relative shrink-0">
                                    <img class="h-11 w-11 rounded-2xl object-cover ring-2 {{ $isSelected ? 'ring-sky-400' : 'ring-slate-200 dark:ring-slate-700' }}" 
                                         src="{{ $parent->user && $parent->user->profile_photo_path ? asset('storage/' . $parent->user->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($parent->name) . '&color=0284c7&background=e0f2fe' }}" 
                                         alt="{{ $parent->name }}">
                                    <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900"></span>
                                </div>

                                <!-- Contact Info -->
                                <div class="flex-grow min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <h3 class="font-bold text-sm text-slate-900 dark:text-white truncate {{ $isSelected ? 'text-sky-700 dark:text-sky-300' : '' }}">
                                            {{ $parent->name }}
                                        </h3>
                                        @if($parent->last_message_at)
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500 shrink-0 ml-2 font-medium">
                                                @php $msgTime = \Carbon\Carbon::parse($parent->last_message_at); @endphp
                                                @if($msgTime->isToday())
                                                    {{ $msgTime->format('H:i') }}
                                                @elseif($msgTime->isYesterday())
                                                    Kemarin
                                                @else
                                                    {{ $msgTime->format('d/m') }}
                                                @endif
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Student Identity Badge -->
                                    <p class="text-[11px] font-semibold text-sky-600 dark:text-sky-400 truncate -mt-0.5">
                                        {{ $parent->student_subtitle ?? 'Orang Tua / Wali Siswa' }}
                                    </p>

                                    <!-- Last Message Preview -->
                                    <div class="flex items-center justify-between gap-2 mt-0.5">
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate flex items-center gap-1">
                                            @if($parent->last_message_sender_id === Auth::id())
                                                <span class="material-icons text-[13px] text-sky-500 shrink-0">done_all</span>
                                            @endif
                                            <span>{{ $parent->last_message_body ?? 'Belum ada percakapan' }}</span>
                                        </p>

                                        @if($parent->unread_messages_count > 0)
                                            <span class="shrink-0 text-[10px] bg-rose-500 text-white font-extrabold rounded-full px-1.5 py-0.5 shadow-2xs animate-pulse">
                                                {{ $parent->unread_messages_count }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="p-8 text-center text-xs text-slate-400 italic">
                                Belum ada data orang tua terdaftar.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- 2. AREA PERCAKAPAN (Chat Conversation)     -->
                <!-- ========================================== -->
                <div id="chat-area" class="w-full lg:w-2/3 xl:flex-1 @if(!$selectedParent) hidden lg:flex @else fixed inset-0 z-50 lg:static lg:z-auto @endif flex flex-col h-full bg-slate-100/70 dark:bg-slate-950/60 overflow-hidden">
                    @if($selectedParent)
                        <div class="flex flex-col h-full w-full overflow-hidden">
                            
                            <!-- 2.1 HEADER CHAT (Focused 64-70px Header) -->
                            <div class="h-16 shrink-0 px-3 sm:px-5 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between z-20 shadow-2xs">
                                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                                    <!-- Mobile Back Button -->
                                    <a href="{{ route('admin.chat.index') }}" 
                                       class="lg:hidden inline-flex items-center justify-center w-10 h-10 min-h-[44px] min-w-[44px] -ml-1 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                       title="Kembali ke Daftar Pesan">
                                        <span class="material-icons text-2xl">arrow_back</span>
                                    </a>

                                    <!-- Parent Avatar -->
                                    <div class="relative shrink-0">
                                        <img class="h-10 w-10 sm:h-11 sm:w-11 rounded-2xl object-cover ring-2 ring-sky-500/30" 
                                             src="{{ $selectedParent->user && $selectedParent->user->profile_photo_path ? asset('storage/' . $selectedParent->user->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($selectedParent->name) . '&color=0284c7&background=e0f2fe' }}" 
                                             alt="{{ $selectedParent->name }}">
                                        <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900"></span>
                                    </div>

                                    <!-- Contact Name & Contextual Student Subtitle -->
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white truncate leading-tight">
                                            {{ $selectedParent->name }}
                                        </h3>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-[11px] font-semibold text-sky-600 dark:text-sky-400 truncate">
                                                {{ $selectedParent->student_subtitle ?? 'Orang Tua / Wali Siswa' }}
                                            </span>
                                            <span class="text-slate-300 dark:text-slate-700 hidden sm:inline">&bull;</span>
                                            <span class="hidden sm:inline-flex items-center gap-1 text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Online
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-1 shrink-0">
                                    @if($selectedParent->phone_number)
                                        @php
                                            $cleanPhone = preg_replace('/[^0-9]/', '', $selectedParent->phone_number);
                                            if (str_starts_with($cleanPhone, '0')) {
                                                $cleanPhone = '62' . substr($cleanPhone, 1);
                                            }
                                        @endphp
                                        <a href="https://wa.me/{{ $cleanPhone }}" target="_blank"
                                           class="inline-flex items-center justify-center w-10 h-10 min-h-[44px] min-w-[44px] rounded-xl text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition-colors"
                                           title="Buka WhatsApp ({{ $selectedParent->phone_number }})">
                                            <span class="material-icons text-xl">chat</span>
                                        </a>
                                        <a href="tel:{{ $selectedParent->phone_number }}"
                                           class="hidden sm:inline-flex items-center justify-center w-10 h-10 min-h-[44px] min-w-[44px] rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                           title="Hubungi Telepon">
                                            <span class="material-icons text-xl">call</span>
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <!-- 2.2 MESSAGE STREAM CONTAINER -->
                            <div id="messages-container" class="flex-1 p-3.5 sm:p-5 overflow-y-auto overscroll-contain space-y-3">
                                @forelse($messages as $date => $dailyMessages)
                                    
                                    <!-- Subtle Date Separator -->
                                    <div class="flex items-center justify-center my-3">
                                        <span class="px-3.5 py-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400 bg-white/80 dark:bg-slate-850/80 backdrop-blur-xs rounded-full shadow-2xs border border-slate-200/70 dark:border-slate-700/60 select-none">
                                            @php $messageDate = \Carbon\Carbon::parse($date); @endphp
                                            @if($messageDate->isToday())
                                                Hari ini
                                            @elseif($messageDate->isYesterday())
                                                Kemarin
                                            @else
                                                {{ $messageDate->translatedFormat('l, d F Y') }}
                                            @endif
                                        </span>
                                    </div>

                                    <!-- Daily Messages Loop -->
                                    @foreach($dailyMessages as $message)
                                        @php $isOutgoing = ($message->user_id === Auth::id()); @endphp
                                        
                                        <div class="flex {{ $isOutgoing ? 'justify-end' : 'justify-start' }}">
                                            @if($isOutgoing)
                                                <!-- Outgoing Message Bubble (Admin) -->
                                                <div class="max-w-[85%] sm:max-w-[72%] bg-gradient-to-tr from-sky-600 to-sky-500 text-white rounded-2xl rounded-tr-xs px-4 py-2.5 shadow-xs">
                                                    <p class="text-xs sm:text-sm leading-relaxed whitespace-pre-wrap break-words text-white">{!! nl2br(e($message->body)) !!}</p>
                                                    <div class="flex items-center justify-end gap-1 text-[10px] text-sky-100 text-right mt-1 font-medium select-none">
                                                        <span>{{ $message->created_at->format('H:i') }}</span>
                                                        @if($message->read_at)
                                                            <span class="material-icons text-xs text-sky-200" title="Dibaca">done_all</span>
                                                        @else
                                                            <span class="material-icons text-xs text-sky-200/70" title="Terkirim">done</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <!-- Incoming Message Bubble (Parent) -->
                                                <div class="max-w-[85%] sm:max-w-[72%] bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-2xl rounded-tl-xs px-4 py-2.5 shadow-xs border border-slate-200/70 dark:border-slate-700/60">
                                                    <p class="text-xs sm:text-sm leading-relaxed whitespace-pre-wrap break-words">{!! nl2br(e($message->body)) !!}</p>
                                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 text-right mt-1 font-medium select-none">
                                                        {{ $message->created_at->format('H:i') }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                @empty
                                    <!-- Conversation Empty State -->
                                    <div class="h-full flex flex-col items-center justify-center p-6 text-center text-slate-400 dark:text-slate-500 space-y-2">
                                        <div class="w-12 h-12 rounded-2xl bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                                            <span class="material-icons text-2xl">waving_hand</span>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                                            Mulai percakapan dengan <span class="font-bold text-slate-800 dark:text-white">{{ $selectedParent->name }}</span>
                                        </p>
                                        <p class="text-[11px] text-slate-400 max-w-xs">
                                            Kirim pesan atau informasi presensi terkait {{ $selectedParent->student_subtitle ?? 'siswa' }}.
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                            
                            <!-- 2.3 MESSAGE COMPOSER (Pinned Bottom) -->
                            <div class="shrink-0 bg-white dark:bg-slate-900 border-t border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 relative z-30 safe-area-pb">
                                
                                <!-- Quick Templates Popover -->
                                <div x-show="showTemplates" 
                                     @click.outside="showTemplates = false" 
                                     x-transition 
                                     style="display: none;" 
                                     class="absolute bottom-full left-3 sm:left-4 mb-2 w-72 sm:w-80 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-2 z-40">
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 px-2 py-1">Template Balasan Cepat</div>
                                    <div class="space-y-1">
                                        <button type="button" @click="insertTemplate('Assalamualaikum Wr. Wb. Mohon konfirmasi terkait kehadiran ananda hari ini. Terima kasih.')" class="w-full text-left p-2 rounded-xl text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors">
                                            📢 Konfirmasi Presensi Siswa
                                        </button>
                                        <button type="button" @click="insertTemplate('Permohonan izin ananda telah kami verifikasi dan disetujui. Semoga lekas sembuh.')" class="w-full text-left p-2 rounded-xl text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors">
                                            ✅ Verifikasi Izin Disetujui
                                        </button>
                                        <button type="button" @click="insertTemplate('Mohon mengirimkan surat keterangan dokter / surat izin resmi untuk kelengkapan administrasi presensi.')" class="w-full text-left p-2 rounded-xl text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors">
                                            📄 Permintaan Surat Izin
                                        </button>
                                    </div>
                                </div>

                                <form x-ref="chatForm" action="{{ route('admin.chat.store_message', $activeConversation) }}" method="POST" class="flex items-end gap-2">
                                    @csrf
                                    
                                    <!-- Attachment / Templates Button -->
                                    <button type="button" 
                                            @click="showTemplates = !showTemplates" 
                                            class="inline-flex items-center justify-center w-11 h-11 min-h-[44px] min-w-[44px] rounded-2xl text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shrink-0 mb-0.5"
                                            title="Template Pesan & Aksi Cepat">
                                        <span class="material-icons text-2xl" :class="showTemplates ? 'rotate-45' : ''">add_circle_outline</span>
                                    </button>

                                    <!-- Auto-expanding Textarea Input -->
                                    <div class="flex-grow min-w-0">
                                        <textarea x-ref="messageInput"
                                                  name="body" 
                                                  rows="1"
                                                  x-model="messageText"
                                                  @input="autoExpand($el)"
                                                  @keydown.enter.exact="if(!window.matchMedia('(max-width: 640px)').matches) { $event.preventDefault(); if(messageText.trim().length > 0) $refs.chatForm.requestSubmit(); }"
                                                  class="w-full px-4 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white rounded-2xl text-xs sm:text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 focus:bg-white dark:focus:bg-slate-850 border border-transparent transition-all resize-none max-h-32 min-h-[44px] leading-relaxed no-scrollbar" 
                                                  placeholder="Tulis pesan... (Enter untuk kirim, Shift+Enter baris baru)" 
                                                  autocomplete="off" 
                                                  required></textarea>
                                    </div>

                                    <!-- Send Button -->
                                    <div class="shrink-0 mb-0.5">
                                        <button type="submit" 
                                                class="inline-flex items-center justify-center w-11 h-11 min-h-[44px] min-w-[44px] rounded-2xl bg-sky-600 hover:bg-sky-500 active:scale-95 text-white shadow-md shadow-sky-600/30 transition-all"
                                                title="Kirim Pesan">
                                            <span class="material-icons text-xl">send</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    @else
                        <!-- Desktop Empty State (No parent selected) -->
                        <div class="hidden lg:flex flex-col items-center justify-center h-full p-8 text-center">
                            <div class="w-16 h-16 rounded-3xl bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-4 shadow-xs">
                                <span class="material-icons text-3xl">chat</span>
                            </div>
                            <h3 class="font-extrabold text-lg text-slate-900 dark:text-white">Pusat Komunikasi Orang Tua</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm">
                                Pilih salah satu kontak orang tua di sidebar sebelah kiri untuk melihat percakapan dan mengirim balasan.
                            </p>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messagesContainer = document.getElementById('messages-container');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        });
    </script>
    @endpush
</x-app-layout>

