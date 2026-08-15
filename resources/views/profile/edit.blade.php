<x-app-layout>
    <x-slot name="header">
        <div>
            <x-breadcrumb :breadcrumbs="[
                ['title' => 'Dasbor', 'url' => route('dashboard')],
                ['title' => 'Profil Pengguna', 'url' => route('profile.edit')]
            ]" />
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                Pengaturan Profil & Akun
            </h1>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-4xl">
        <!-- Informasi Profil Card -->
        <div class="p-6 sm:p-8 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <!-- Update Kata Sandi Card -->
        <div class="p-6 sm:p-8 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <!-- Hapus Akun Card -->
        <div class="p-6 sm:p-8 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>