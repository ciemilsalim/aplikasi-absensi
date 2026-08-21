<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-3 sm:px-4 sm:py-2.5 min-h-[44px] sm:min-h-[38px] bg-sky-600 hover:bg-sky-500 active:bg-sky-700 dark:bg-sky-500 dark:hover:bg-sky-400 text-white text-sm sm:text-xs font-bold rounded-xl shadow-sm hover:shadow active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900']) }}>
    {{ $slot }}
</button>

