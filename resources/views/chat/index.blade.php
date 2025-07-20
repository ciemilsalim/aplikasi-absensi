<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[['title' => 'Obrolan', 'url' => route('chat.index')]]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Obrolan') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                {{-- PERBAIKAN: Semua atribut x-data dan Alpine.js dihapus --}}
                <div class="h-[calc(100vh-250px)] flex">
                    
                    <!-- Sidebar Kontak -->
                    <div id="contact-sidebar" class="w-full lg:w-1/3 border-r border-gray-200 dark:border-slate-700 flex flex-col">
                        <div class="p-4 border-b border-gray-200 dark:border-slate-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Kontak</h3>
                        </div>
                        <div id="contact-list" class="flex-grow overflow-y-auto">
                            {{-- Kontak akan dimuat di sini oleh JavaScript --}}
                            <div class="p-4 text-center text-sm text-slate-500">Memuat kontak...</div>
                        </div>
                    </div>

                    <!-- Area Obrolan -->
                    <div id="chat-area" class="w-full lg:w-2/3 flex-col hidden lg:flex">
                        {{-- Placeholder ini akan diganti oleh konten obrolan --}}
                        <div id="chat-placeholder" class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                            Pilih percakapan untuk memulai.
                        </div>
                        
                        {{-- Konten obrolan yang sebenarnya (awalnya tersembunyi) --}}
                        <div id="chat-content" class="hidden flex-col h-full">
                            <!-- Header Obrolan -->
                            <div id="chat-header" class="p-4 border-b border-gray-200 dark:border-slate-700 flex items-center gap-3">
                                <button id="back-to-contacts-button" class="lg:hidden text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                                </button>
                                <span class="inline-block h-10 w-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-600">
                                    <svg class="h-full w-full text-slate-400 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                </span>
                                <div>
                                    <p id="chat-contact-name" class="font-semibold text-gray-900 dark:text-white"></p>
                                    <p id="chat-student-name" class="text-xs text-gray-500 dark:text-gray-400"></p>
                                </div>
                            </div>

                            <!-- Pesan -->
                            <div id="messages-container" class="flex-grow p-6 overflow-y-auto bg-slate-50 dark:bg-slate-800/50">
                                {{-- Pesan akan dimuat di sini oleh JavaScript --}}
                            </div>
                            
                            <!-- Form Input Pesan -->
                            <div class="p-4 bg-white dark:bg-slate-800 border-t border-gray-200 dark:border-slate-700">
                                <form id="message-form">
                                    <div class="flex items-center">
                                        <x-text-input id="message-input" type="text" class="flex-grow" placeholder="Ketik pesan Anda..." autocomplete="off" />
                                        <x-primary-button type="submit" class="ml-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                                        </x-primary-button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let conversations = [];
            let activeConversation = null;
            let messages = [];
            let pollingInterval = null;
            const userId = {{ Auth::id() }};
            const userRole = '{{ Auth::user()->role }}';

            // Elemen DOM
            const contactListEl = document.getElementById('contact-list');
            const chatAreaEl = document.getElementById('chat-area');
            const chatPlaceholderEl = document.getElementById('chat-placeholder');
            const chatContentEl = document.getElementById('chat-content');
            const chatContactNameEl = document.getElementById('chat-contact-name');
            const chatStudentNameEl = document.getElementById('chat-student-name');
            const messagesContainerEl = document.getElementById('messages-container');
            const messageFormEl = document.getElementById('message-form');
            const messageInputEl = document.getElementById('message-input');
            const contactSidebarEl = document.getElementById('contact-sidebar');
            const backToContactsButton = document.getElementById('back-to-contacts-button');

            // Fungsi untuk merender daftar kontak
            function renderConversations() {
                if (conversations.length === 0) {
                    contactListEl.innerHTML = `<div class="p-4 text-center text-sm text-slate-500">Tidak ada kontak ditemukan.</div>`;
                    return;
                }
                contactListEl.innerHTML = conversations.map(conv => {
                    const contactName = userRole === 'parent' ? (conv.teacher?.user?.name || 'Guru Dihapus') : (conv.parent?.user?.name || 'Orang Tua Dihapus');
                    return `
                        <button data-id="${conv.id}" class="contact-button w-full text-left p-4 flex items-center gap-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                            <div class="relative"><span class="inline-block h-10 w-10 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-600"><svg class="h-full w-full text-slate-400 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg></span></div>
                            <div class="flex-grow"><p class="font-semibold text-sm text-slate-800 dark:text-white">${contactName}</p><p class="text-xs text-slate-500 dark:text-slate-400">Siswa: ${conv.student.name}</p></div>
                        </button>
                    `;
                }).join('');
                
                document.querySelectorAll('.contact-button').forEach(button => {
                    button.addEventListener('click', () => {
                        const convId = parseInt(button.dataset.id);
                        selectConversation(conversations.find(c => c.id === convId));
                    });
                });
            }

            // Fungsi untuk merender pesan
            function renderMessages() {
                messagesContainerEl.innerHTML = messages.map(msg => {
                    const isSender = msg.user_id === userId;
                    const time = new Date(msg.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                    return `
                        <div class="flex ${isSender ? 'justify-end' : 'justify-start'} mb-4">
                            <div class="max-w-xs lg:max-w-md p-3 rounded-lg ${isSender ? 'bg-sky-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200'}">
                                <p class="text-sm">${msg.body}</p>
                                <p class="text-xs mt-1 opacity-70 text-right">${time}</p>
                            </div>
                        </div>
                    `;
                }).join('');
                scrollToBottom();
            }

            // Fungsi untuk memilih percakapan
            function selectConversation(conversation) {
                activeConversation = conversation;
                chatPlaceholderEl.classList.add('hidden');
                chatContentEl.classList.remove('hidden');
                chatContentEl.classList.add('flex');
                
                const contactName = userRole === 'parent' ? (conversation.teacher?.user?.name || 'Guru Dihapus') : (conversation.parent?.user?.name || 'Orang Tua Dihapus');
                chatContactNameEl.textContent = contactName;
                chatStudentNameEl.textContent = `Percakapan mengenai ${conversation.student?.name || 'Siswa Dihapus'}`;
                
                contactSidebarEl.classList.add('hidden', 'lg:flex');
                chatAreaEl.classList.remove('hidden');

                fetchMessages();
            }

            backToContactsButton.addEventListener('click', () => {
                activeConversation = null;
                contactSidebarEl.classList.remove('hidden');
                chatAreaEl.classList.add('hidden', 'lg:flex');
            });

            async function fetchConversations() {
                try {
                    const response = await fetch('{{ route('chat.conversations') }}');
                    if (!response.ok) throw new Error('Network response was not ok.');
                    conversations = await response.json();
                    renderConversations();
                } catch (error) {
                    contactListEl.innerHTML = `<div class="p-4 text-center text-sm text-red-500">Gagal memuat kontak.</div>`;
                    console.error('Fetch conversations error:', error);
                }
            }

            async function fetchMessages() {
                if (!activeConversation) return;
                messagesContainerEl.innerHTML = `<div class="p-4 text-center text-sm text-slate-500">Memuat pesan...</div>`;
                try {
                    const response = await fetch(`/chat/conversations/${activeConversation.id}/messages`);
                    const newMessages = await response.json();
                    if (JSON.stringify(messages) !== JSON.stringify(newMessages)) {
                        messages = newMessages;
                        renderMessages();
                    }
                } catch (error) {
                    messagesContainerEl.innerHTML = `<div class="p-4 text-center text-sm text-red-500">Gagal memuat pesan.</div>`;
                    console.error('Fetch messages error:', error);
                }
            }

            messageFormEl.addEventListener('submit', async (e) => {
                e.preventDefault();
                const body = messageInputEl.value.trim();
                if (body === '' || !activeConversation) return;
                
                messageInputEl.value = '';
                try {
                    const response = await fetch(`/chat/conversations/${activeConversation.id}/messages`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ body })
                    });
                    const newMessage = await response.json();
                    messages.push(newMessage);
                    renderMessages();
                } catch (error) {
                    messageInputEl.value = body;
                    alert('Gagal mengirim pesan.');
                    console.error('Send message error:', error);
                }
            });

            function scrollToBottom() {
                messagesContainerEl.scrollTop = messagesContainerEl.scrollHeight;
            }

            // Inisialisasi
            fetchConversations();
            pollingInterval = setInterval(() => {
                if (activeConversation) fetchMessages();
            }, 5000);
        });
    </script>
    @endpush
</x-app-layout>
