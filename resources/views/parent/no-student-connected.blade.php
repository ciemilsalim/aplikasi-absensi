<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dasbor Orang Tua') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 md:p-10 text-center">
                    <div class="flex justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-20 h-20 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Akun Belum Terhubung</h3>
                    <p class="mt-4 text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        Saat ini, akun Anda belum terhubung dengan data siswa mana pun. Untuk dapat melihat informasi kehadiran dan data lainnya, akun Anda perlu ditautkan oleh administrator sekolah.
                    </p>
                    <p class="mt-2 text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        Silakan hubungi pihak administrasi atau tata usaha sekolah untuk meminta penautan akun.
                    </p>
                    <div class="mt-8 flex justify-center gap-4">
                        <x-primary-button as="a" :href="route('profile.edit')">
                            Lengkapi Profil
                        </x-primary-button>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-secondary-button as="a" :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-secondary-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
