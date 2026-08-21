<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-3 sm:px-4 sm:py-2.5 min-h-[44px] sm:min-h-[38px] bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white text-sm sm:text-xs font-bold rounded-xl shadow-sm hover:shadow active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900']) }}>
    {{ $slot }}
</button>

