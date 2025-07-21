<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[['title' => 'Obrolan', 'url' => route('chat.index')]]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Obrolan') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="h-[calc(100vh-250px)] flex">
                    
                    <!-- Sidebar Kontak -->
                    <div class="w-full lg:w-1/3 border-r border-gray-200 dark:border-slate-700 flex flex-col @if($activeConversation) hidden lg:flex @endif">
                        <div class="p-4 border-b border-gray-200 dark:border-slate-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Kontak</h3>
                        </div>
                        <div class="flex-grow overflow-y-auto">
                            {{-- Kontak Admin untuk Orang Tua --}}
                            @if(Auth::user()->role === 'parent' && isset($adminConversation))
                                <a href="{{ route('chat.admin') }}" class="w-full text-left p-4 flex items-center gap-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition {{ request()->routeIs('chat.admin') ? 'bg-sky-100 dark:bg-sky-900/50' : '' }}">
                                    <div class="relative"><span class="inline-block h-10 w-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-600"><svg class="h-full w-full text-slate-400 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg></span></div>
                                    <div class="flex-grow"><p class="font-semibold text-sm text-slate-800 dark:text-white">Admin Sekolah</p><p class="text-xs text-slate-500 dark:text-slate-400">Hubungi administrasi</p></div>
                                </a>
                            @endif
                            
                            @forelse($conversations as $conv)
                                <a href="{{ route('chat.index', $conv) }}" class="w-full text-left p-4 flex items-center gap-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition {{-- ... --}}">
                                    <div class="relative"><span class="inline-block h-10 w-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-600"><svg><!-- ... --></svg></span></div>
                                    <div class="flex-grow">
                                        <p class="font-semibold text-sm text-slate-800 dark:text-white">
                                            @if(Auth::user()->role === 'parent') {{ $conv->teacher->user->name ?? 'Guru Dihapus' }} @else {{ $conv->parent->user->name ?? 'Orang Tua Dihapus' }} @endif
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Siswa: {{ $conv->student->name }}</p>
                                    </div>
                                    {{-- Badge Notifikasi BARU --}}
                                    @if($conv->unread_messages_count > 0)
                                        <span class="ml-auto text-xs bg-red-500 text-white font-bold rounded-full h-5 w-5 flex items-center justify-center">{{ $conv->unread_messages_count }}</span>
                                    @endif
                                </a>
                            @empty
                                @if(Auth::user()->role === 'parent' && isset($adminConversation))
                                    {{-- Jangan tampilkan apa-apa jika hanya ada chat admin --}}
                                @else
                                    <div class="p-4 text-center text-sm text-slate-500">Tidak ada kontak ditemukan.</div>
                                @endif
                            @endforelse
                        </div>
                    </div>

                    <!-- Area Obrolan -->
                    <div class="w-full lg:w-2/3 flex-col @if(!$activeConversation) hidden lg:flex @else flex @endif">
                        @if($activeConversation)
                            <div class="flex flex-col h-full">
                                <!-- Header Obrolan -->
                                <div class="p-4 border-b border-gray-200 dark:border-slate-700 flex items-center gap-3">
                                    <a href="{{ route('chat.index') }}" class="lg:hidden text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg></a>
                                    <span class="inline-block h-10 w-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-600"><svg class="h-full w-full text-slate-400 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg></span>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">
                                            @if(request()->routeIs('chat.admin')) Admin Sekolah @elseif(Auth::user()->role === 'parent') {{ $activeConversation->teacher->user->name ?? 'Guru Dihapus' }} @else {{ $activeConversation->parent->user->name ?? 'Orang Tua Dihapus' }} @endif
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            @if(!request()->routeIs('chat.admin')) Percakapan mengenai {{ $activeConversation->student->name }} @else Hubungan Administrasi @endif
                                        </p>
                                    </div>
                                </div>
                                <div id="messages-container" class="flex-grow p-6 overflow-y-auto bg-slate-50 dark:bg-slate-800/50">
                                    <div class="space-y-4">
                                        @foreach($messages as $message)
                                            <div class="flex {{ $message->user_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                                                <div class="max-w-xs lg:max-w-md p-3 rounded-lg {{ $message->user_id === Auth::id() ? 'bg-sky-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200' }}">
                                                    <p class="text-sm">{!! nl2br(e($message->body)) !!}</p>
                                                    <p class="text-xs mt-1 opacity-70 text-right">{{ $message->created_at->format('H:i') }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="p-4 bg-white dark:bg-slate-800 border-t border-gray-200 dark:border-slate-700">
                                    <form action="{{ request()->routeIs('chat.admin') ? route('chat.store_admin_message', $activeConversation) : route('chat.store_message', $activeConversation) }}" method="POST">
                                        @csrf
                                        <div class="flex items-center">
                                            <x-text-input name="body" type="text" class="flex-grow" placeholder="Ketik pesan Anda..." autocomplete="off" required />
                                            <x-primary-button type="submit" class="ml-2"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg></x-primary-button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="hidden lg:flex items-center justify-center h-full text-gray-500 dark:text-gray-400">Pilih percakapan untuk memulai.</div>
                        @endif
                    </div>
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
