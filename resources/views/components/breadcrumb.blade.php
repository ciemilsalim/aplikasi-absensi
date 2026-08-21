@props([
    'breadcrumbs' => [],
    'backUrl' => null,
    'showBack' => true
])

@php
    $count = count($breadcrumbs);
    $parentUrl = $backUrl;
    $parentTitle = '';
    
    // Only subpages (depth > 1 or explicit backUrl) should have back navigation
    $isSubpage = ($count > 1) || (!empty($backUrl));
    
    if ($isSubpage && !$parentUrl && $count > 1) {
        $prevItem = $breadcrumbs[$count - 2];
        $parentUrl = $prevItem['url'] ?? null;
        $parentTitle = $prevItem['title'] ?? 'Kembali';
    }
@endphp

@if ($count > 0)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-2 flex-wrap mb-1']) }}>
        @if ($showBack && $isSubpage && $parentUrl)
            <a href="{{ ($parentUrl !== '#' && !empty($parentUrl)) ? $parentUrl : 'javascript:void(0);' }}" 
               @if(empty($parentUrl) || $parentUrl === '#') onclick="if(window.history.length > 1) { window.history.back(); } else { window.location.href='{{ route('dashboard') }}'; } return false;" @endif
               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200/80 dark:border-slate-700 transition-all shadow-2xs group shrink-0"
               title="Kembali ke {{ $parentTitle }}">
                <span class="material-icons text-sm text-slate-400 group-hover:text-sky-600 dark:group-hover:text-sky-400 group-hover:-translate-x-0.5 transition-transform">arrow_back</span>
                <span>{{ $parentTitle }}</span>
            </a>
        @endif

        @if ($count > 1)
            <nav class="hidden sm:flex text-xs overflow-x-auto no-scrollbar py-0.5" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1.5 md:space-x-2">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-slate-500 hover:text-sky-600 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
                            <span class="material-icons text-xs">home</span>
                            <span>Dasbor</span>
                        </a>
                    </li>
                    @foreach ($breadcrumbs as $breadcrumb)
                        <li class="inline-flex items-center">
                            <span class="material-icons text-xs text-slate-400 dark:text-slate-600 select-none">chevron_right</span>
                            @if($loop->last)
                                <span class="ms-1 md:ms-1.5 px-2.5 py-0.5 rounded-lg bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 font-bold border border-sky-100 dark:border-sky-900/40">
                                    {{ $breadcrumb['title'] }}
                                </span>
                            @else
                                <a href="{{ $breadcrumb['url'] ?? '#' }}" class="ms-1 md:ms-1.5 px-2 py-0.5 rounded-lg text-slate-600 hover:text-sky-600 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 font-medium transition-colors">
                                    {{ $breadcrumb['title'] }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
    </div>
@endif
