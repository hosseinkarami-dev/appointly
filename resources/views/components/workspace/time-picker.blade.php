@props(['label', 'wireModel', 'value'])

@php
    $selectedTime = substr((string) $value, 0, 5);
    [$selectedHour, $selectedMinute] = explode(':', $selectedTime);
@endphp

<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.stop.prevent="open = false" class="relative min-w-0">
    <span class="text-sm font-medium dark:text-white">{{ $label }}</span>
    <button type="button" role="combobox" aria-haspopup="dialog" :aria-expanded="open.toString()" @click="open = !open" class="mt-2 flex min-h-12 w-full cursor-pointer items-center justify-between gap-3 rounded-xl border border-[#171323]/10 bg-[#f6f5f2] px-4 py-3 text-left text-sm text-[#171323] shadow-sm transition hover:border-violet-300 hover:bg-white focus-visible:border-violet-500 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-violet-500/10 dark:border-white/10 dark:bg-[#211c31] dark:text-white dark:hover:border-violet-300/50 dark:hover:bg-[#2a2340]">
        <span class="flex items-center gap-2.5"><i data-feather="clock" class="size-4 text-violet-600 dark:text-violet-300"></i><span class="font-semibold tabular-nums">{{ $selectedTime }}</span></span>
        <i data-feather="chevron-down" class="size-4 text-[#171323]/45 transition dark:text-white/45" :class="open && 'rotate-180'"></i>
    </button>
    <div x-cloak x-show="open" x-transition.origin.top class="absolute z-40 mt-2 w-full min-w-64 rounded-2xl border border-[#171323]/10 bg-white p-3 shadow-2xl shadow-[#171323]/15 dark:border-white/10 dark:bg-[#211c31] dark:shadow-black/30" role="dialog" aria-label="Choose a time">
        <div class="mb-3 flex items-center justify-between border-b border-[#171323]/8 pb-3 dark:border-white/10"><div><p class="text-sm font-semibold">Choose a time</p><p class="mt-0.5 text-xs text-[#171323]/45 dark:text-white/45">Select an hour and minute</p></div><span class="rounded-lg bg-violet-100 px-2.5 py-1.5 text-sm font-bold tabular-nums text-violet-800 dark:bg-violet-400/15 dark:text-violet-200">{{ $selectedTime }}</span></div>
        <div class="grid grid-cols-2 gap-3">
            <section aria-label="Hours"><p class="mb-1.5 px-1 text-[0.65rem] font-bold uppercase tracking-wider text-[#171323]/40 dark:text-white/40">Hour</p><div class="workspace-scroll max-h-44 space-y-1 overflow-y-auto pr-1" role="listbox" aria-label="Hour">
                @for ($hour = 0; $hour < 24; $hour++)
                    @php($hourValue = str_pad((string) $hour, 2, '0', STR_PAD_LEFT))
                    <button type="button" role="option" aria-selected="{{ $selectedHour === $hourValue ? 'true' : 'false' }}" @click="$wire.$set(@js($wireModel), @js($hourValue) + ':' + @js($selectedMinute))" class="w-full cursor-pointer rounded-lg px-2 py-2 text-center text-sm tabular-nums transition {{ $selectedHour === $hourValue ? 'bg-violet-100 font-semibold text-violet-800 dark:bg-violet-400/15 dark:text-violet-200' : 'text-[#171323]/65 hover:bg-[#f6f5f2] dark:text-white/65 dark:hover:bg-white/8' }}">{{ $hourValue }}</button>
                @endfor
            </div></section>
            <section aria-label="Minutes"><p class="mb-1.5 px-1 text-[0.65rem] font-bold uppercase tracking-wider text-[#171323]/40 dark:text-white/40">Minute</p><div class="workspace-scroll max-h-44 space-y-1 overflow-y-auto pr-1" role="listbox" aria-label="Minute">
                @for ($minute = 0; $minute < 60; $minute++)
                    @php($minuteValue = str_pad((string) $minute, 2, '0', STR_PAD_LEFT))
                    <button type="button" role="option" aria-selected="{{ $selectedMinute === $minuteValue ? 'true' : 'false' }}" @click="$wire.$set(@js($wireModel), @js($selectedHour) + ':' + @js($minuteValue)); open = false" class="w-full cursor-pointer rounded-lg px-2 py-2 text-center text-sm tabular-nums transition {{ $selectedMinute === $minuteValue ? 'bg-violet-100 font-semibold text-violet-800 dark:bg-violet-400/15 dark:text-violet-200' : 'text-[#171323]/65 hover:bg-[#f6f5f2] dark:text-white/65 dark:hover:bg-white/8' }}">{{ $minuteValue }}</button>
                @endfor
            </div></section>
        </div>
    </div>
    @error($wireModel)
        <p class="mt-2 rounded-xl bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-400/10 dark:text-rose-200">{{ $message }}</p>
    @enderror
</div>
