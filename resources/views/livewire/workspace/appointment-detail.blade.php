<div class="space-y-8">
    <a wire:navigate href="{{ route('workspace.appointments') }}" class="inline-flex items-center text-sm font-semibold text-[#171323]/55 transition hover:text-violet-700 dark:text-white/55"><i data-feather="arrow-left" class="mr-1 inline size-4"></i>Back to appointments</a>

    <x-workspace.page-header eyebrow="Appointment" title="{{ $appointment->service_name ?: $appointment->service?->name ?: 'Appointment' }}" description="Review the booking details and keep its status up to date." action-label="Open calendar" action-href="{{ route('workspace.calendar') }}" />

    @if (session('appointment-updated'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('appointment-updated') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
        <section class="rounded-[1.75rem] border border-[#171323]/8 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/6 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-600">Booking details</p>
                    <p class="mt-3 text-2xl font-semibold tracking-[-0.03em]">{{ $appointment->start_at->format('l, F j') }}</p>
                    <p class="mt-1 text-sm text-[#171323]/55 dark:text-white/55">{{ $appointment->start_at->format('H:i') }} · {{ $appointment->end_at->format('H:i') }}</p>
                </div>
                <x-workspace.status-badge :status="$appointment->status->value" />
            </div>

            <dl class="mt-8 grid gap-5 border-t border-[#171323]/8 pt-6 sm:grid-cols-2 dark:border-white/10">
                <div><dt class="text-xs font-semibold uppercase tracking-wider text-[#171323]/40 dark:text-white/40">Service</dt><dd class="mt-2 font-medium">{{ $appointment->service_name ?: $appointment->service?->name ?: 'Custom service' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wider text-[#171323]/40 dark:text-white/40">Team member</dt><dd class="mt-2 font-medium">{{ $appointment->staff?->display_name ?: 'Unassigned' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wider text-[#171323]/40 dark:text-white/40">Duration</dt><dd class="mt-2 font-medium">{{ $appointment->start_at->diffInMinutes($appointment->end_at) }} minutes</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wider text-[#171323]/40 dark:text-white/40">Created</dt><dd class="mt-2 font-medium">{{ $appointment->created_at->format('M j, Y') }}</dd></div>
            </dl>

            <div class="mt-8 rounded-2xl bg-[#f6f5f2] p-4 dark:bg-white/6"><div class="flex items-center justify-between gap-3"><p class="text-xs font-semibold uppercase tracking-wider text-[#171323]/40 dark:text-white/40">Notes</p><button type="button" wire:click="startEditingNotes" class="text-xs font-semibold text-violet-700 hover:text-violet-900 dark:text-violet-300">Edit</button></div>@if ($appointment->notes)<p class="mt-2 text-sm leading-6 text-[#171323]/70 dark:text-white/70">{{ $appointment->notes }}</p>@else<p class="mt-2 text-sm text-[#171323]/45 dark:text-white/45">Add internal context for your team.</p>@endif</div>

            @if ($editingNotes)
                <form wire:submit="updateNotes" class="mt-4 space-y-3">
                    <textarea wire:model="notes" rows="4" placeholder="Internal appointment notes" class="w-full rounded-xl border-[#171323]/10 bg-white text-sm dark:border-white/10 dark:bg-white/6"></textarea>
                    @error('notes')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    <div class="flex gap-2"><button class="rounded-xl bg-[#171323] px-4 py-2.5 text-sm font-semibold text-white">Save notes</button><button type="button" wire:click="cancelEditingNotes" class="rounded-xl border border-[#171323]/10 px-4 py-2.5 text-sm font-semibold dark:border-white/10">Cancel</button></div>
                </form>
            @endif
        </section>

        <aside class="space-y-6">
            <section class="rounded-[1.75rem] border border-[#171323]/8 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/6">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-600">Customer</p>
                @if ($appointment->customer)
                    <a wire:navigate href="{{ route('workspace.customers.show', $appointment->customer->id) }}" class="mt-4 block text-xl font-semibold hover:text-violet-700">{{ $appointment->customer->name }}</a>
                    <div class="mt-4 space-y-2 text-sm text-[#171323]/55 dark:text-white/55"><p>{{ $appointment->customer->email ?: 'No email provided' }}</p><p>{{ $appointment->customer->phone ?: 'No phone provided' }}</p></div>
                @else
                    <p class="mt-4 text-xl font-semibold">Guest booking</p><p class="mt-2 text-sm text-[#171323]/55 dark:text-white/55">No customer profile is attached to this appointment.</p>
                @endif
            </section>

            @if ($appointment->status->canTransitionTo(\App\Domain\Appointment\Enums\AppointmentStatus::Cancelled))
                <section class="rounded-[1.75rem] border border-[#171323]/8 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/6">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-600">Next action</p>
                    <p class="mt-3 text-sm leading-6 text-[#171323]/55 dark:text-white/55">Update this appointment as your team confirms, completes, or cancels the booking.</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        @if ($appointment->status->value === 'pending')<button wire:click="updateStatus('confirmed')" wire:loading.attr="disabled" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Confirm</button>@endif
                        @if ($appointment->status->value === 'confirmed')<button wire:click="updateStatus('completed')" wire:loading.attr="disabled" class="rounded-xl bg-violet-700 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Mark complete</button><button wire:click="updateStatus('no_show')" wire:loading.attr="disabled" class="rounded-xl border border-orange-200 px-4 py-2.5 text-sm font-semibold text-orange-700 disabled:opacity-50">No-show</button>@endif
                        <button wire:click="updateStatus('cancelled')" wire:loading.attr="disabled" class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700 disabled:opacity-50">Cancel</button>
                    </div>
                    <textarea wire:model="cancellationReason" rows="3" placeholder="Optional cancellation note" class="mt-4 w-full rounded-xl border-[#171323]/10 bg-[#f6f5f2] text-sm dark:border-white/10 dark:bg-white/6"></textarea>
                </section>
            @endif
        </aside>
    </div>

    <section class="rounded-[1.75rem] border border-[#171323]/8 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/6 sm:p-8">
        <div class="flex items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-600">Audit trail</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em]">Appointment timeline</h2></div><i data-feather="activity" class="size-5 text-violet-600"></i></div>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @forelse ($auditLogs as $log)
                <div class="rounded-2xl bg-[#f6f5f2] p-4 dark:bg-white/6"><div class="flex items-start gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-400/15 dark:text-violet-300"><i data-feather="check-circle" class="size-4"></i></span><div><p class="text-sm font-semibold">{{ str($log->action)->replace('.', ' · ')->title() }}</p><p class="mt-1 text-xs text-[#171323]/50 dark:text-white/50">{{ $log->created_at->format('M j, Y · H:i') }} · {{ $log->actor?->name ?? 'Public booking' }}</p></div></div></div>
            @empty
                <p class="text-sm text-[#171323]/50 dark:text-white/50">No activity has been recorded yet.</p>
            @endforelse
        </div>
    </section>
</div>
