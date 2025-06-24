@props(['disabled' => false])

{{-- PERBAIKAN: Mengubah warna latar dan border pada dark mode agar lebih kontras --}}
<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm transition']) !!}>
