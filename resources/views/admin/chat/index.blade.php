<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[['title' => 'Pesan Ortu', 'url' => route('admin.chat.index')]]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Obrolan dengan Orang Tua') }}</h2>
    </x-slot>

    <div class="py-0">
        <div class="max-w-full mx-auto">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm">
                <div class="flex h-[calc(100vh-4rem-1px)]">
                    
                    <!-- Sidebar Kontak (Daftar Orang Tua) -->
                    <div id="contact-sidebar" class="w-full lg:w-1/3 border-r border-gray-200 dark:border-slate-700 flex flex-col @if($selectedParent) hidden lg:flex @endif">
                        <div class="p-4 border-b border-gray-200 dark:border-slate-700 flex-shrink-0">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Daftar Orang Tua</h3>
                        </div>
                        <div id="contact-list" class="flex-grow overflow-y-auto">
                            @forelse($parents as $parent)
                                <a href="{{ route('admin.chat.index', ['selectedParent' => $parent->id]) }}" class="contact-button w-full text-left p-4 flex items-center gap-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition {{ ($selectedParent && $selectedParent->id === $parent->id) ? 'bg-sky-100 dark:bg-sky-900/50' : '' }}">
                                    <div class="relative"><span class="inline-block h-10 w-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-600"><svg class="h-full w-full text-slate-400 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg></span></div>
                                    <div class="flex-grow">
                                        <div class="flex justify-between items-start">
                                            <p class="font-semibold text-sm text-slate-800 dark:text-white">{{ $parent->name }}</p>
                                            @if($parent->last_message_at)
                                                <p class="text-xs text-slate-400 dark:text-slate-500 flex-shrink-0">{{ \Carbon\Carbon::parse($parent->last_message_at)->diffForHumans() }}</p>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $parent->user->email ?? 'Email tidak tersedia' }}</p>
                                    </div>
                                    @if($parent->unread_messages_count > 0)
                                        <span class="ml-2 text-xs bg-red-500 text-white font-bold rounded-full h-5 w-5 flex items-center justify-center flex-shrink-0">{{ $parent->unread_messages_count }}</span>
                                    @endif
                                </a>
                            @empty
                                <div class="p-4 text-center text-sm text-slate-500">Tidak ada data orang tua.</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Area Obrolan -->
                    <div id="chat-area" class="w-full lg:w-2/3 flex-col @if(!$selectedParent) hidden lg:flex @else flex @endif">
                        @if($selectedParent)
                            <div class="flex flex-col h-full">
                                <!-- Header Obrolan -->
                                <div class="p-4 border-b border-gray-200 dark:border-slate-700 flex items-center gap-3 flex-shrink-0">
                                    <a href="{{ route('admin.chat.index') }}" class="lg:hidden text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                                    </a>
                                    <span class="inline-block h-10 w-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-600">
                                        <svg class="h-full w-full text-slate-400 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $selectedParent->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Orang Tua / Wali Siswa</p>
                                    </div>
                                </div>

                                <!-- Pesan -->
                                <div id="messages-container" class="flex-grow p-6 overflow-y-auto bg-slate-50 dark:bg-slate-800/50">
                                    <div class="space-y-2">
                                        @forelse($messages as $date => $dailyMessages)
                                            {{-- Pemisah Tanggal --}}
                                            <div class="flex justify-center my-4">
                                                <div class="px-3 py-1 text-xs font-semibold text-gray-500 bg-gray-200 dark:bg-slate-700 dark:text-gray-300 rounded-full">
                                                    @php
                                                        $messageDate = \Carbon\Carbon::parse($date);
                                                    @endphp
                                                    @if($messageDate->isToday())
                                                        Hari ini
                                                    @elseif($messageDate->isYesterday())
                                                        Kemarin
                                                    @else
                                                        {{ $messageDate->isoFormat('dddd, D MMMM YYYY') }}
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Loop untuk pesan harian --}}
                                            @foreach($dailyMessages as $message)
                                                <div class="flex {{ $message->user_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                                                    <div class="max-w-xs lg:max-w-md p-3 rounded-lg {{ $message->user_id === Auth::id() ? 'bg-sky-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200' }}">
                                                        <p class="text-sm">{!! nl2br(e($message->body)) !!}</p>
                                                        <p class="text-xs mt-1 opacity-70 text-right">{{ $message->created_at->format('H:i') }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @empty
                                            <div class="text-center text-sm text-slate-500">Belum ada pesan.</div>
                                        @endforelse
                                    </div>
                                </div>
                                
                                <!-- Form Input Pesan -->
                                <div class="p-4 bg-white dark:bg-slate-800 border-t border-gray-200 dark:border-slate-700 flex-shrink-0">
                                    <form action="{{ route('admin.chat.store_message', $activeConversation) }}" method="POST">
                                        @csrf
                                        <div class="flex items-center gap-2">
                                            <div class="flex-grow">
                                                <x-text-input name="body" type="text" class="w-full" placeholder="Ketik balasan Anda..." autocomplete="off" required />
                                            </div>
                                            <div class="flex-shrink-0">
                                                <x-primary-button type="submit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                                                </x-primary-button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="hidden lg:flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                                Pilih orang tua dari daftar untuk memulai percakapan.
                            </div>
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
