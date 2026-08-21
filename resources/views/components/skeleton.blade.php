@props([
    'type' => 'card', // card, list, table, kpi, text
    'count' => 1,
])

@for ($i = 0; $i < $count; $i++)
    @if ($type === 'card')
        <div {{ $attributes->merge(['class' => 'p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4']) }}>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl skeleton shrink-0"></div>
                <div class="space-y-2 flex-1 min-w-0">
                    <div class="h-4 w-3/5 rounded-lg skeleton"></div>
                    <div class="h-3 w-2/5 rounded-md skeleton"></div>
                </div>
            </div>
            <div class="space-y-2 pt-2">
                <div class="h-3.5 w-full rounded-md skeleton"></div>
                <div class="h-3.5 w-4/5 rounded-md skeleton"></div>
            </div>
            <div class="grid grid-cols-2 gap-2 pt-2">
                <div class="h-9 rounded-2xl skeleton"></div>
                <div class="h-9 rounded-2xl skeleton"></div>
            </div>
        </div>
    @elseif ($type === 'kpi')
        <div {{ $attributes->merge(['class' => 'p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-3']) }}>
            <div class="flex items-center justify-between">
                <div class="h-3 w-20 rounded-md skeleton"></div>
                <div class="w-8 h-8 rounded-xl skeleton"></div>
            </div>
            <div class="h-8 w-24 rounded-lg skeleton"></div>
            <div class="h-2 w-full rounded-full skeleton"></div>
        </div>
    @elseif ($type === 'list')
        <div {{ $attributes->merge(['class' => 'p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center justify-between gap-3']) }}>
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <div class="w-10 h-10 rounded-2xl skeleton shrink-0"></div>
                <div class="space-y-2 flex-1">
                    <div class="h-3.5 w-1/2 rounded-md skeleton"></div>
                    <div class="h-2.5 w-1/3 rounded-md skeleton"></div>
                </div>
            </div>
            <div class="w-16 h-6 rounded-xl skeleton shrink-0"></div>
        </div>
    @elseif ($type === 'table')
        <div {{ $attributes->merge(['class' => 'rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden']) }}>
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="h-4 w-32 rounded-lg skeleton"></div>
                <div class="h-8 w-48 rounded-xl skeleton"></div>
            </div>
            <div class="p-4 space-y-3">
                @for ($r = 0; $r < 4; $r++)
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg skeleton shrink-0"></div>
                        <div class="h-8 w-1/3 rounded-lg skeleton"></div>
                        <div class="h-8 w-1/4 rounded-lg skeleton"></div>
                        <div class="h-8 flex-1 rounded-lg skeleton"></div>
                    </div>
                @endfor
            </div>
        </div>
    @else
        <div {{ $attributes->merge(['class' => 'space-y-2']) }}>
            <div class="h-4 w-full rounded-lg skeleton"></div>
            <div class="h-3 w-4/5 rounded-md skeleton"></div>
            <div class="h-3 w-3/5 rounded-md skeleton"></div>
        </div>
    @endif
@endfor
