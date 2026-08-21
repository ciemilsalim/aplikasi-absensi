@props([
    'size' => 'md', // sm, md, lg, xl
    'color' => 'sky', // sky, indigo, amber, emerald, rose, slate
    'text' => null,
])

@php
    $sizeClasses = [
        'xs' => 'w-3.5 h-3.5 border-2',
        'sm' => 'w-4 h-4 border-2',
        'md' => 'w-6 h-6 border-2',
        'lg' => 'w-8 h-8 border-3',
        'xl' => 'w-12 h-12 border-4',
    ][$size] ?? 'w-6 h-6 border-2';

    $colorClasses = [
        'sky' => 'border-sky-100 dark:border-sky-950 border-t-sky-600 dark:border-t-sky-400',
        'indigo' => 'border-indigo-100 dark:border-indigo-950 border-t-indigo-600 dark:border-t-indigo-400',
        'amber' => 'border-amber-100 dark:border-amber-950 border-t-amber-600 dark:border-t-amber-400',
        'emerald' => 'border-emerald-100 dark:border-emerald-950 border-t-emerald-600 dark:border-t-emerald-400',
        'rose' => 'border-rose-100 dark:border-rose-950 border-t-rose-600 dark:border-t-rose-400',
        'slate' => 'border-slate-200 dark:border-slate-800 border-t-slate-700 dark:border-t-slate-300',
    ][$color] ?? 'border-sky-100 dark:border-sky-950 border-t-sky-600 dark:border-t-sky-400';
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <div class="rounded-full animate-spin shrink-0 {{ $sizeClasses }} {{ $colorClasses }}"></div>
    @if($text)
        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $text }}</span>
    @endif
</div>
