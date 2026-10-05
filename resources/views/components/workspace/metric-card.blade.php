<article class="rounded-[1.5rem] border border-[#171323]/8 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/6">
    <div class="flex items-start justify-between gap-3"><p class="text-sm font-medium text-[#171323]/55 dark:text-white/55">{{ $label }}</p><span class="grid size-9 place-items-center rounded-xl {{ $tone === 'amber' ? 'bg-amber-100 text-amber-700' : ($tone === 'emerald' ? 'bg-emerald-100 text-emerald-700' : 'bg-violet-100 text-violet-700') }}"><i data-feather="activity" class="size-4"></i></span></div>
    <p class="mt-5 text-3xl font-semibold tracking-tight text-[#171323] dark:text-white">{{ $value }}</p>
    @if ($detail)<p class="mt-1 text-xs text-[#171323]/45 dark:text-white/45">{{ $detail }}</p>@endif
</article>
