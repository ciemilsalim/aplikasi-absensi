<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :breadcrumbs="[['title' => 'Pusat Komunikasi', 'url' => route('chat.index')]]" />
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                    Pesan & Konsultasi
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="py-0">
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="flex h-[calc(100vh-12rem)] min-h-[500px]">
                
                <!-- Sidebar Kontak -->
                <div class="w-full lg:w-80 xl:w-96 border-r border-slate-200/80 dark:border-slate-800 flex flex-col shrink-0 @if($activeConversation) hidden lg:flex @endif">
                    <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0 bg-slate-50/50 dark:bg-slate-850/40">
                        <div class="flex items-center gap-2">
                            <span class="material-icons text-sky-500 text-lg">forum</span>
                            <h3 class="font-bold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300">Daftar Kontak</h3>
                        </div>
                    </div>

                    <div class="flex-grow overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60">
                        @if(Auth::user()->role === 'parent' && isset($adminConversation))
                            <a href="{{ route('chat.admin') }}" 
                               class="w-full text-left p-4 flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-850 transition-colors {{ request()->routeIs('chat.admin') ? 'bg-sky-50/80 dark:bg-sky-950/40 border-l-4 border-sky-500' : '' }}">
                                <div class="relative shrink-0">
                                    @if(isset($adminUser))
                                        <img class="h-11 w-11 rounded-2xl object-cover ring-2 ring-slate-100 dark:ring-slate-800" src="{{ $adminUser->profile_photo_path ? asset('storage/' . $adminUser->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($adminUser->name) . '&color=7F9CF5&background=EBF4FF' }}" alt="{{ $adminUser->name }}">
                                    @else
                                        <div class="h-11 w-11 rounded-2xl bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold text-base">
                                            <span class="material-icons">admin_panel_settings</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <p class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">{{ isset($adminUser) ? $adminUser->name : 'Admin Sekolah' }}</p>
                                    </div>
                                    <p class="text-xs text-slate-400 truncate">Layanan bantuan administrasi</p>
                                </div>
                                @if(isset($adminConversation->unread_messages_count) && $adminConversation->unread_messages_count > 0)
                                    <span class="text-[10px] bg-rose-500 text-white font-extrabold rounded-full px-2 py-0.5 shadow-sm">{{ $adminConversation->unread_messages_count }}</span>
                                @endif
                            </a>
                        @endif
                        
                        @forelse($conversations as $conv)
                            @php
                                $otherUser = Auth::user()->role === 'parent' ? $conv->teacher->user : $conv->parent->user;
                                $isActive = $activeConversation && get_class($activeConversation) === 'App\Models\Conversation' && $activeConversation->id === $conv->id;
                            @endphp
                            <a href="{{ route('chat.index', $conv) }}" 
                               class="w-full text-left p-4 flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-850 transition-colors {{ $isActive ? 'bg-sky-50/80 dark:bg-sky-950/40 border-l-4 border-sky-500' : '' }}">
                                <div class="relative shrink-0">
                                    <img class="h-11 w-11 rounded-2xl object-cover ring-2 ring-slate-100 dark:ring-slate-800" src="{{ $otherUser->profile_photo_path ? asset('storage/' . $otherUser->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($otherUser->name ?? 'User') . '&color=7F9CF5&background=EBF4FF' }}" alt="{{ $otherUser->name ?? 'User' }}">
                                </div>
                                <div class="flex-grow min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <p class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">
                                            {{ $otherUser->name ?? (Auth::user()->role === 'parent' ? 'Guru Dihapus' : 'Orang Tua Dihapus') }}
                                        </p>
                                        @if($conv->last_message_at)
                                            <span class="text-[10px] text-slate-400 shrink-0 ml-1">{{ \Carbon\Carbon::parse($conv->last_message_at)->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                    @if(Auth::user()->role === 'parent')
                                        @php
                                            $className = $conv->teacher?->homeroomClass?->name ?? $conv->student?->schoolClass?->name;
                                        @endphp
                                        <p class="text-xs font-semibold text-sky-600 dark:text-sky-400 truncate">
                                            Wali Kelas {{ $className ?? '' }} &bull; {{ $conv->student->name }}
                                        </p>
                                    @else
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">Siswa: {{ $conv->student->name }}</p>
                                    @endif
                                </div>
                                @if($conv->unread_messages_count > 0)
                                    <span class="text-[10px] bg-rose-500 text-white font-extrabold rounded-full px-2 py-0.5 shadow-sm shrink-0">{{ $conv->unread_messages_count }}</span>
                                @endif
                            </a>
                        @empty
                            @if(!(Auth::user()->role === 'parent' && isset($adminConversation)))
                                <div class="p-8 text-center text-xs text-slate-400">
                                    <span class="material-icons text-3xl mb-2 text-slate-300">chat_bubble_outline</span>
                                    <p>Belum ada riwayat kontak.</p>
                                </div>
                            @endif
                        @endforelse
                    </div>
                </div>

                <!-- Area Obrolan -->
                <div class="flex-1 flex flex-col @if(!$activeConversation) hidden lg:flex @else flex @endif bg-slate-50/50 dark:bg-slate-950/50">
                    @if($activeConversation)
                        <div class="flex flex-col h-full">
                            <!-- Header Obrolan Aktif -->
                            <div class="p-4 bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800 flex items-center gap-3 shrink-0">
                                <a href="{{ route('chat.index') }}" class="lg:hidden p-1.5 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                    <span class="material-icons text-lg">arrow_back</span>
                                </a>
                                
                                @php
                                    if (request()->routeIs('chat.admin')) {
                                        $activeOtherUser = $adminUser ?? null;
                                    } else {
                                        $activeOtherUser = Auth::user()->role === 'parent' ? $activeConversation->teacher->user : $activeConversation->parent->user;
                                    }
                                @endphp

                                <div class="relative">
                                    @if(isset($activeOtherUser))
                                        <img class="h-10 w-10 rounded-2xl object-cover ring-2 ring-slate-100 dark:ring-slate-800" src="{{ $activeOtherUser->profile_photo_path ? asset('storage/' . $activeOtherUser->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($activeOtherUser->name) . '&color=7F9CF5&background=EBF4FF' }}" alt="{{ $activeOtherUser->name }}">
                                    @else
                                        <div class="h-10 w-10 rounded-2xl bg-slate-200 dark:bg-slate-700 text-slate-500 flex items-center justify-center font-bold">
                                            <span class="material-icons text-base">person</span>
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    <h3 class="font-bold text-sm text-slate-900 dark:text-white leading-tight">
                                        @if(isset($activeOtherUser))
                                            {{ $activeOtherUser->name }}
                                        @else
                                            @if(request()->routeIs('chat.admin')) Admin Sekolah 
                                            @elseif(Auth::user()->role === 'parent') {{ 'Guru' }} 
                                            @else {{ 'Orang Tua' }} 
                                            @endif
                                        @endif
                                    </h3>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        @if(!request()->routeIs('chat.admin')) 
                                            @if(Auth::user()->role === 'parent')
                                                @php
                                                    $activeClassName = $activeConversation->teacher?->homeroomClass?->name ?? $activeConversation->student?->schoolClass?->name;
                                                @endphp
                                                Wali Kelas {{ $activeClassName ?? '' }} &bull; Siswa: {{ $activeConversation->student->name }}
                                            @else
                                                Membahas siswa: {{ $activeConversation->student->name }}
                                            @endif
                                        @else 
                                            Bantuan Administrasi Sekolah
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <!-- Pesan Container -->
                            <div id="messages-container" class="flex-grow p-6 overflow-y-auto space-y-4">
                                @forelse($messages as $date => $dailyMessages)
                                    <div class="flex justify-center my-4">
                                        <span class="px-3 py-1 text-[11px] font-bold text-slate-500 dark:text-slate-400 bg-white/80 dark:bg-slate-800/80 rounded-full shadow-2xs border border-slate-200/60 dark:border-slate-700">
                                            @php
                                                $messageDate = \Carbon\Carbon::parse($date);
                                            @endphp
                                            @if($messageDate->isToday())
                                                Hari ini
                                            @elseif($messageDate->isYesterday())
                                                Kemarin
                                            @else
                                                {{ $messageDate->translatedFormat('l, d F Y') }}
                                            @endif
                                        </span>
                                    </div>
                                    @foreach($dailyMessages as $message)
                                        <div class="flex {{ $message->user_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                                            <div class="max-w-xs sm:max-w-md p-3.5 rounded-3xl shadow-2xs {{ $message->user_id === Auth::id() ? 'bg-sky-600 text-white rounded-br-xs' : 'bg-white dark:bg-slate-850 text-slate-800 dark:text-slate-100 border border-slate-200/70 dark:border-slate-800 rounded-bl-xs' }}">
                                                <p class="text-xs leading-relaxed break-words">{!! nl2br(e($message->body)) !!}</p>
                                                <p class="text-[10px] mt-1 text-right {{ $message->user_id === Auth::id() ? 'text-sky-200' : 'text-slate-400' }}">
                                                    {{ $message->created_at->format('H:i') }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                @empty
                                    <div class="text-center py-12 text-slate-400 text-xs">
                                        <span class="material-icons text-3xl mb-2 text-slate-300">chat</span>
                                        <p>Belum ada pesan dalam percakapan ini.</p>
                                    </div>
                                @endforelse
                            </div>

                            <!-- Input Form -->
                            <div class="p-4 bg-white dark:bg-slate-900 border-t border-slate-200/80 dark:border-slate-800 shrink-0">
                                <form action="{{ request()->routeIs('chat.admin') ? route('chat.store_admin_message', $activeConversation) : route('chat.store_message', $activeConversation) }}" method="POST">
                                    @csrf
                                    <div class="flex items-center gap-2">
                                        <input name="body" type="text" 
                                               class="flex-grow text-xs font-semibold px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 text-slate-800 dark:text-slate-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15 placeholder-slate-400" 
                                               placeholder="Tulis pesan Anda di sini..." 
                                               autocomplete="off" 
                                               required />
                                        <button type="submit" 
                                                class="inline-flex items-center justify-center p-3 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white shadow-md shadow-sky-600/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 shrink-0">
                                            <span class="material-icons text-lg">send</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="hidden lg:flex flex-col items-center justify-center h-full text-slate-400 p-8 text-center">
                            <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-850 flex items-center justify-center text-slate-300 dark:text-slate-600 mb-3">
                                <span class="material-icons text-3xl">question_answer</span>
                            </div>
                            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">Pilih Percakapan</h4>
                            <p class="text-xs text-slate-400 mt-1 max-w-xs">Pilih salah satu kontak di sisi kiri untuk membaca atau memulai obrolan.</p>
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


