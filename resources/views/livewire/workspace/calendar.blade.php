<div class="space-y-8">
    <x-workspace.page-header eyebrow="Planning" title="Calendar" description="A clear view of the appointments shaping your day." action-label="Manage appointments" action-href="{{ route('workspace.appointments') }}" />

    <section class="rounded-[1.75rem] border border-[#171323]/8 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/6 sm:p-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-2">
                <button wire:click="previousDay" aria-label="Previous period" class="grid size-10 place-items-center rounded-xl border border-[#171323]/10 transition hover:border-violet-300 hover:text-violet-700 dark:border-white/10"><i data-feather="chevron-left" class="size-4"></i></button>
                <button wire:click="nextDay" aria-label="Next period" class="grid size-10 place-items-center rounded-xl border border-[#171323]/10 transition hover:border-violet-300 hover:text-violet-700 dark:border-white/10"><i data-feather="chevron-right" class="size-4"></i></button>
                <button wire:click="showToday" class="rounded-xl bg-violet-100 px-3 py-2 text-xs font-semibold text-violet-700 transition hover:bg-violet-200 dark:bg-violet-400/15 dark:text-violet-300">Today</button>
                <x-workspace.date-picker wire-model="date" :value="$date" :today="now($tenant->timezone)->toDateString()" />
            </div>
            <div class="flex rounded-xl bg-[#f6f5f2] p-1 dark:bg-white/8">
                <button wire:click="setViewMode('day')" class="rounded-lg px-4 py-2 text-xs font-semibold transition {{ $viewMode === 'day' ? 'bg-white text-[#171323] shadow-sm dark:bg-white/15 dark:text-white' : 'text-[#171323]/50 dark:text-white/50' }}">Day</button>
                <button wire:click="setViewMode('week')" class="rounded-lg px-4 py-2 text-xs font-semibold transition {{ $viewMode === 'week' ? 'bg-white text-[#171323] shadow-sm dark:bg-white/15 dark:text-white' : 'text-[#171323]/50 dark:text-white/50' }}">Week</button>
            </div>
        </div>

        <div class="mt-5 flex gap-2 overflow-x-auto pb-1" aria-label="Filter appointments by status">
            @foreach (['active' => 'Active', 'all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'no_show' => 'No-show', 'cancelled' => 'Cancelled'] as $value => $label)
                <button wire:click="$set('statusFilter', '{{ $value }}')" class="shrink-0 rounded-full px-3.5 py-2 text-xs font-semibold transition {{ $statusFilter === $value ? 'bg-[#171323] text-white dark:bg-white dark:text-[#171323]' : 'bg-[#f6f5f2] text-[#171323]/55 hover:bg-violet-100 hover:text-violet-700 dark:bg-white/8 dark:text-white/55 dark:hover:bg-violet-400/15 dark:hover:text-violet-300' }}">{{ $label }}</button>
            @endforeach
        </div>

        <div class="mt-8 flex items-end justify-between gap-4">
            <div><p class="text-sm font-semibold">{{ $viewMode === 'week' ? $periodStart->format('M j').' – '.$periodEnd->format('M j, Y') : $periodStart->format('l, F j, Y') }}</p><p class="mt-1 text-xs text-[#171323]/45 dark:text-white/45">{{ $tenant->timezone }} · {{ $appointments->count() }} appointment{{ $appointments->count() === 1 ? '' : 's' }}</p></div>
            <i data-feather="calendar" class="size-5 text-violet-600"></i>
        </div>

        @if ($viewMode === 'day')
            @if ($daysOffByDate->has($periodStart->toDateString()))
                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-300/20 dark:bg-amber-400/10">
                    <div class="flex items-start gap-3"><i data-feather="sun" class="mt-0.5 size-4 text-amber-700 dark:text-amber-300"></i><div><p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Day-off schedule</p><div class="mt-1 space-y-1 text-sm text-amber-800/80 dark:text-amber-100/75">@foreach ($daysOffByDate->get($periodStart->toDateString()) as $dayOff)<p>{{ $dayOff->staff?->display_name ?? 'Everyone' }}{{ $dayOff->reason ? ' · '.$dayOff->reason : '' }}</p>@endforeach</div></div></div>
                </div>
            @endif
            <div class="mt-5 grid gap-3">
                @forelse ($appointments as $appointment)
                    <a wire:key="calendar-{{ $appointment->id }}" wire:navigate href="{{ route('workspace.appointments.show', $appointment->id) }}" class="flex flex-col gap-4 rounded-2xl border border-[#171323]/8 p-4 transition hover:border-violet-300 hover:bg-violet-50/40 sm:flex-row sm:items-center dark:border-white/10 dark:hover:bg-white/6"><span class="w-16 text-sm font-semibold text-violet-600">{{ $appointment->start_at->setTimezone($tenant->timezone)->format('H:i') }}</span><span class="flex-1"><span class="block font-semibold">{{ $appointment->service_name ?: $appointment->service?->name }}</span><span class="mt-1 block text-sm text-[#171323]/45 dark:text-white/45">{{ $appointment->customer?->name }} · {{ $appointment->staff?->display_name }}</span></span><x-workspace.status-badge :status="$appointment->status->value" /></a>
                @empty
                    <x-workspace.empty-state title="Nothing booked yet" message="This day is open. Share your booking page when you’re ready." />
                @endforelse
            </div>
        @else
            <div class="mt-5 grid gap-3 overflow-x-auto pb-2 md:grid-cols-7">
                @for ($offset = 0; $offset < 7; $offset++)
                    @php($day = $periodStart->addDays($offset))
                    <div class="min-w-[190px] rounded-2xl bg-[#f6f5f2] p-3 dark:bg-white/6">
                        <div class="flex items-center justify-between gap-2 border-b border-[#171323]/8 pb-3 dark:border-white/10"><div><p class="text-xs font-semibold uppercase tracking-wider text-[#171323]/45 dark:text-white/45">{{ $day->format('D') }}</p><p class="mt-1 text-lg font-semibold">{{ $day->format('j') }}</p></div><span class="text-xs text-[#171323]/45 dark:text-white/45">{{ $appointmentsByDate->get($day->toDateString(), collect())->count() }}</span></div>
                        @if ($daysOffByDate->has($day->toDateString()))
                            <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-2.5 py-2 text-xs text-amber-800 dark:border-amber-300/20 dark:bg-amber-400/10 dark:text-amber-200">
                                @foreach ($daysOffByDate->get($day->toDateString()) as $dayOff)
                                    <p class="{{ $loop->first ? '' : 'mt-1' }}"><span class="font-semibold">{{ $dayOff->staff?->display_name ?? 'Everyone' }} off</span>{{ $dayOff->reason ? ' · '.$dayOff->reason : '' }}</p>
                                @endforeach
                            </div>
                        @endif
                        <div class="mt-3 space-y-2">
                            @forelse ($appointmentsByDate->get($day->toDateString(), collect()) as $appointment)
                                <a wire:key="week-calendar-{{ $appointment->id }}" wire:navigate href="{{ route('workspace.appointments.show', $appointment->id) }}" class="block rounded-xl border border-[#171323]/8 bg-white p-3 transition hover:border-violet-300 dark:border-white/10 dark:bg-white/8"><p class="text-xs font-semibold text-violet-600">{{ $appointment->start_at->setTimezone($tenant->timezone)->format('H:i') }}</p><p class="mt-1 truncate text-sm font-semibold">{{ $appointment->service_name ?: $appointment->service?->name }}</p><p class="mt-1 truncate text-xs text-[#171323]/45 dark:text-white/45">{{ $appointment->customer?->name ?: 'Guest' }}</p></a>
                            @empty
                                <p class="py-5 text-center text-xs text-[#171323]/35 dark:text-white/35">Open</p>
                            @endforelse
                        </div>
                    </div>
                @endfor
            </div>
        @endif
    </section>
</div>
