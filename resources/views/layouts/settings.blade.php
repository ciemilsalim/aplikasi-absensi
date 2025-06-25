<!-- =================================================================== -->
<!-- File: resources/views/layouts/settings.blade.php (Baru)             -->
<!-- =================================================================== -->
<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Pengaturan', 'url' => route('admin.settings.identity')]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Pengaturan Aplikasi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                
                <!-- Sidebar Navigasi Pengaturan -->
                <div class="md:col-span-1">
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                        <ul class="space-y-1">
                            <li>
                                <a href="{{ route('admin.settings.identity') }}" class="{{ request()->routeIs('admin.settings.identity') ? 'bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-400' : 'hover:bg-gray-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' }} w-full flex items-center gap-3 px-4 py-2 text-left text-sm font-medium rounded-md transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18" /></svg>
                                    Identitas & Lokasi
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.settings.appearance') }}" class="{{ request()->routeIs('admin.settings.appearance') ? 'bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-400' : 'hover:bg-gray-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' }} w-full flex items-center gap-3 px-4 py-2 text-left text-sm font-medium rounded-md transition">
                                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.158 0a.225.225 0 0 1 .225.225V8.7a.225.225 0 0 1-.225.225h-.008a.225.225 0 0 1-.225-.225V8.475a.225.225 0 0 1 .225-.225h.008Z" /></svg>
                                    Tampilan & Logo
                                </a>
                            </li>
                             <li>
                                <a href="{{ route('admin.settings.attendance') }}" class="{{ request()->routeIs('admin.settings.attendance') ? 'bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-400' : 'hover:bg-gray-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' }} w-full flex items-center gap-3 px-4 py-2 text-left text-sm font-medium rounded-md transition">
                                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    Waktu Absensi
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Konten Form (akan diisi oleh @yield('content')) -->
                <div class="md:col-span-3">
                    @if (session('success'))
                        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition class="bg-green-500 border border-green-600 text-white px-4 py-3 rounded-lg shadow-lg relative mb-6" role="alert" style="display: none;">
                            <strong class="font-bold">Sukses!</strong>
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif
                    
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>