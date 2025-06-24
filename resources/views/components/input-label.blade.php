@props(['value'])

{{-- PERBAIKAN: Menambahkan kelas 'dark:text-gray-300' agar teks label terlihat di mode gelap --}}
<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700 dark:text-gray-300']) }}>
    {{ $value ?? $slot }}
</label>
