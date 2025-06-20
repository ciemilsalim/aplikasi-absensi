@props(['class' => ''])

{{-- Cek jika path logo tersedia dari View Composer.
     Variabel $appLogoPath ini dikirim oleh LogoServiceProvider. --}}
@if (isset($appLogoPath) && $appLogoPath)
    {{-- Jika ada, tampilkan logo yang diunggah dari storage --}}
    <img src="{{ asset('storage/' . $appLogoPath) }}" alt="Logo Aplikasi" {{ $attributes->merge(['class' => $class]) }}>
@else
    {{-- Jika tidak ada, tampilkan logo SVG default --}}
    <svg {{ $attributes->merge(['class' => 'fill-current ' . $class]) }} viewBox="0 0 57 57" xmlns="http://www.w3.org/2000/svg">
        <path d="M28.5 0C12.76 0 0 12.76 0 28.5S12.76 57 28.5 57 57 44.24 57 28.5 44.24 0 28.5 0zm0 54.15C14.237 54.15 2.85 42.763 2.85 28.5S14.237 2.85 28.5 2.85 54.15 14.237 54.15 28.5 42.763 54.15 28.5 54.15z"></path>
        <path d="M39.615 20.422h-4.845v16.29h4.845v-16.29zM22.23 20.422h-4.845v16.29h4.845v-16.29z"></path>
    </svg>
@endif

{{-- Catatan: 
    - Komponen ini akan menampilkan logo aplikasi yang diunggah melalui View Composer.
    - Jika tidak ada logo yang diunggah, akan menampilkan logo SVG default.
    - Pastikan untuk mengirim variabel $appLogoPath dari LogoServiceProvider ke view ini. --}}