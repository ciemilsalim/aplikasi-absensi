@props(['disabled' => false, 'type' => 'text'])

@if ($type === 'password')
    <div x-data="{ showPassword: false }" class="relative">
        <input {{ $disabled ? 'disabled' : '' }} 
               :type="showPassword ? 'text' : 'password'"
               {!! $attributes->merge(['class' => 'w-full pr-11 py-3 px-4 sm:py-2 sm:px-3.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 rounded-xl text-base sm:text-sm shadow-2xs transition-all disabled:opacity-50 disabled:bg-slate-50 dark:disabled:bg-slate-800']) !!}>
        
        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
            <span x-show="!showPassword" class="material-icons text-lg">visibility</span>
            <span x-show="showPassword" style="display: none;" class="material-icons text-lg">visibility_off</span>
        </button>
    </div>
@else
    {{-- Input standar untuk tipe selain password --}}
    <input {{ $disabled ? 'disabled' : '' }} type="{{ $type }}" {!! $attributes->merge(['class' => 'w-full py-3 px-4 sm:py-2 sm:px-3.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 rounded-xl text-base sm:text-sm shadow-2xs transition-all disabled:opacity-50 disabled:bg-slate-50 dark:disabled:bg-slate-800']) !!}>
@endif

