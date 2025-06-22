<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="[
            ['title' => 'Dasbor Guru', 'url' => route('teacher.dashboard')]
        ]" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dasbor Guru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 md:p-8 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        <div class="flex-shrink-0">
                            <span class="inline-block h-24 w-24 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-700">
                                <svg class="h-full w-full text-slate-300 dark:text-slate-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 20.993V24H0v-2.997A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold">Selamat datang, {{ $teacher->name }}!</p>
                            <p class="mt-1 text-gray-600 dark:text-gray-400">Ini adalah halaman dasbor Anda. Di sini Anda dapat melihat informasi terkait akun Anda.</p>
                        </div>
                    </div>
                    
                    <div class="mt-8 border-t border-gray-200 dark:border-slate-700 pt-6">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-8">
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama Lengkap</dt>
                                <dd class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $teacher->name }}</dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">NIP</dt>
                                <dd class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $teacher->nip ?? '-' }}</dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Login</dt>
                                <dd class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $teacher->user->email }}</dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nomor HP</dt>
                                <dd class="mt-1 text-lg text-gray-900 dark:text-gray-100">{{ $teacher->phone_number ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
