@props(['breadcrumbs' => []])

@if (count($breadcrumbs) > 0)
    <nav {{ $attributes->merge(['class' => 'hidden sm:flex text-xs overflow-x-auto no-scrollbar py-0.5']) }} aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1.5 md:space-x-2">
            <li class="inline-flex items-center">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-slate-500 hover:text-sky-600 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
                    <span class="material-icons text-sm">home</span>
                    <span>Dasbor</span>
                </a>
            </li>
            @foreach ($breadcrumbs as $breadcrumb)
                <li class="inline-flex items-center">
                    <span class="material-icons text-xs text-slate-400 dark:text-slate-600 select-none">chevron_right</span>
                    @if($loop->last)
                        <span class="ms-1 md:ms-1.5 px-2.5 py-1 rounded-lg bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 font-bold border border-sky-100 dark:border-sky-900/40">
                            {{ $breadcrumb['title'] }}
                        </span>
                    @else
                        <a href="{{ $breadcrumb['url'] ?? '#' }}" class="ms-1 md:ms-1.5 px-2 py-1 rounded-lg text-slate-600 hover:text-sky-600 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 font-medium transition-colors">
                            {{ $breadcrumb['title'] }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif

