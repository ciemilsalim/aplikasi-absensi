<div class="border-b border-gray-200 dark:border-slate-700 mb-6">
    <nav class="-mb-px flex space-x-6" aria-label="Tabs">
        <a href="{{ route('admin.settings.identity') }}" class="{{ request()->routeIs('admin.settings.identity') 
            ? 'border-sky-500 text-sky-600 dark:text-sky-400' 
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} 
            whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
            Identitas & Lokasi
        </a>
        <a href="{{ route('admin.settings.appearance') }}" class="{{ request()->routeIs('admin.settings.appearance') 
            ? 'border-sky-500 text-sky-600 dark:text-sky-400' 
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} 
            whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
            Tampilan & Logo
        </a>
        <a href="{{ route('admin.settings.attendance') }}" class="{{ request()->routeIs('admin.settings.attendance') 
            ? 'border-b-2 border-sky-500 text-sky-600 dark:text-sky-400' 
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} 
            whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
            Waktu Absensi
        </a>
    </nav>
</div>
