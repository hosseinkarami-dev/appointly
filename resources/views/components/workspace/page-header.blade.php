<div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
    <div>
        @if ($eyebrow)<p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-600">{{ $eyebrow }}</p>@endif
        <h1 class="mt-2 text-3xl font-semibold tracking-[-0.04em] text-[#171323] dark:text-white sm:text-4xl">{{ $title }}</h1>
        @if ($description)<p class="mt-2 max-w-2xl text-sm leading-6 text-[#171323]/55 dark:text-white/55">{{ $description }}</p>@endif
    </div>
    @if ($actionLabel && $actionHref)<a wire:navigate href="{{ $actionHref }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-violet-700 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-violet-900/10 transition hover:-translate-y-0.5 hover:bg-violet-800 dark:bg-violet-300 dark:text-[#171323] dark:hover:bg-violet-200">{{ $actionLabel }} <i data-feather="arrow-up-right" class="size-4"></i></a>@endif
</div>
