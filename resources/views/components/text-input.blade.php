@props(['disabled' => false])

{{-- PERBAIKAN: Mengubah warna latar dan border pada dark mode agar lebih kontras --}}
<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border border-slate-300 rounded-md shadow-md focus:outline-none focus:ring-2 focus:ring-slate-500 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-300 dark:focus:border-sky-600 dark:focus:ring-sky-600 focus:outline-none focus:ring-1 transition']) !!}>
