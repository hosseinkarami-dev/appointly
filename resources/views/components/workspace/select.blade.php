@props(['label', 'wireModel', 'value' => '', 'options' => [], 'placeholder' => 'Choose an option'])

<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.stop.prevent="open = false" class="relative min-w-0">
    <span class="text-sm font-medium dark:text-white">{{ $label }}</span>
    <button type="button" role="combobox" aria-haspopup="listbox" :aria-expanded="open.toString()" @click="open = !open" wire:loading.attr="disabled" class="mt-2 flex min-h-12 w-full cursor-pointer items-center justify-between gap-3 rounded-xl border border-[#171323]/10 bg-[#f6f5f2] px-4 py-3 text-left text-sm text-[#171323] shadow-sm transition hover:border-violet-300 hover:bg-white focus-visible:border-violet-500 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-violet-500/10 disabled:opacity-60 dark:border-white/10 dark:bg-[#211c31] dark:text-white dark:hover:border-violet-300/50 dark:hover:bg-[#2a2340]">
        <span class="truncate">{{ $options[(string) $value] ?? $options[$value] ?? $placeholder }}</span>
        <i data-feather="chevron-down" class="size-4 shrink-0 text-[#171323]/45 transition dark:text-white/45" :class="open && 'rotate-180'"></i>
    </button>
    <div x-cloak x-show="open" x-transition.origin.top class="workspace-scroll absolute z-40 mt-2 max-h-64 w-full overflow-y-auto rounded-2xl border border-[#171323]/10 bg-white p-1.5 shadow-2xl shadow-[#171323]/15 dark:border-white/10 dark:bg-[#211c31] dark:shadow-black/30" role="listbox" aria-label="{{ $label }} options">
        @forelse ($options as $optionValue => $optionLabel)
            <button type="button" role="option" aria-selected="{{ (string) $value === (string) $optionValue ? 'true' : 'false' }}" @click="$wire.$set(@js($wireModel), @js((string) $optionValue)); open = false" class="my-0.5 flex w-full cursor-pointer items-center justify-between gap-3 rounded-xl px-3.5 py-3 text-left text-sm transition {{ (string) $value === (string) $optionValue ? 'bg-violet-100 font-semibold text-violet-800 dark:bg-violet-400/15 dark:text-violet-200' : 'text-[#171323]/70 hover:bg-[#f6f5f2] hover:text-[#171323] dark:text-white/70 dark:hover:bg-white/8 dark:hover:text-white' }}">
                <span class="truncate">{{ $optionLabel }}</span>
                @if ((string) $value === (string) $optionValue)
                    <i data-feather="check" class="size-4 shrink-0 text-violet-600 dark:text-violet-300"></i>
                @endif
            </button>
        @empty
            <p class="px-3.5 py-3 text-sm text-[#171323]/45 dark:text-white/45">No options available.</p>
        @endforelse
    </div>
    @error($wireModel)
        <p class="mt-2 rounded-xl bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-400/10 dark:text-rose-200">{{ $message }}</p>
    @enderror
</div>
