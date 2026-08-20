<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <x-breadcrumb :breadcrumbs="[
                    ['title' => 'Dasbor', 'url' => route('dashboard')],
                    ['title' => 'Panduan Penggunaan', 'url' => route('guide')]
                ]" />
                <div class="flex items-center gap-2 flex-wrap mt-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Panduan Penggunaan Aplikasi
                    </h1>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 shadow-2xs">
                        Pusat Bantuan
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors shadow-2xs">
                    <span class="material-icons text-sm">arrow_back</span>
                    <span>Kembali ke Dasbor</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-2 sm:py-6">
        @include('partials._guide-content', ['defaultTab' => $defaultTab ?? 'all', 'userRoleLabel' => $userRoleLabel ?? ''])
    </div>
</x-app-layout>
