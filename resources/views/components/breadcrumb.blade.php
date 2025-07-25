@props(['breadcrumbs' => []])

@if (count($breadcrumbs) > 0)
    {{-- Margin bawah dihapus agar lebih fleksibel saat ditempatkan --}}
    <nav {{ $attributes->merge(['class' => 'flex text-sm']) }} aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
            <li>
                <div class="flex items-center">
                    {{-- Link ke Dasbor utama selalu ada --}}
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-slate-600 hover:text-sky-600 dark:text-slate-400 dark:hover:text-white">
                        <svg class="w-4 h-4 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                        </svg>
                        Dasbor
                    </a>
                </div>
            </li>
            @foreach ($breadcrumbs as $breadcrumb)
                <li>
                    <div class="flex items-center">
                        <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                        </svg>
                        {{-- Logika untuk menampilkan link atau teks biasa --}}
                        <a href="{{ $breadcrumb['url'] ?? '#' }}" class="{{ $loop->last ? 'text-slate-500 dark:text-slate-400 cursor-default' : 'text-slate-600 hover:text-sky-600 dark:text-slate-400 dark:hover:text-white' }} ms-1 md:ms-2 font-medium">{{ $breadcrumb['title'] }}</a>
                    </div>
                </li>
            @endforeach
        </ol>
    </nav>
@endif
