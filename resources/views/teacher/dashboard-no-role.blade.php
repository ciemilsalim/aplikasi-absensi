<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dasbor Guru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 md:p-10 text-center">
                    <div class="flex justify-center mb-6">
                        <div class="w-24 h-24 bg-yellow-100 dark:bg-yellow-900/50 rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Anda Belum Memiliki Peran
                    </h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Akun Anda belum ditetapkan sebagai Wali Kelas atau Guru Mata Pelajaran. <br class="hidden sm:block">
                        Saat ini dasbor Anda masih kosong.
                    </p>
                    <div class="mt-6">
                        <p class="text-sm text-gray-500 dark:text-gray-500">
                            Silakan hubungi Administrator sekolah untuk mendapatkan penetapan peran.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
