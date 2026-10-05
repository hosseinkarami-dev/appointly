<div class="space-y-8">
    <a wire:navigate href="{{ route('workspace.customers') }}" class="inline-flex items-center text-sm font-semibold text-[#171323]/55 transition hover:text-violet-700 dark:text-white/55"><i data-feather="arrow-left" class="mr-1 inline size-4"></i>Back to customers</a>

    @if (session('customer-updated'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('customer-updated') }}</div>
    @endif

    <x-workspace.page-header eyebrow="Customer profile" title="{{ $customer->name }}" description="Understand this relationship at a glance and see every appointment in one place." action-label="View appointments" action-href="{{ route('workspace.appointments') }}" />

    <div class="grid gap-6 lg:grid-cols-[0.75fr_1.25fr]">
        <section class="rounded-[1.75rem] border border-[#171323]/8 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/6 sm:p-8">
            <div class="flex items-start justify-between gap-4"><div class="grid size-16 place-items-center rounded-2xl bg-violet-100 text-2xl font-semibold text-violet-700">{{ \Illuminate\Support\Str::of($customer->name)->substr(0, 1)->upper() }}</div><button type="button" wire:click="startEditing" class="inline-flex items-center gap-2 rounded-xl border border-[#171323]/10 px-3 py-2 text-sm font-semibold transition hover:border-violet-300 hover:text-violet-700 dark:border-white/10"><i data-feather="edit-3" class="size-4"></i>Edit</button></div>
            <h2 class="mt-5 text-2xl font-semibold tracking-[-0.03em]">{{ $customer->name }}</h2>
            <span class="mt-3 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ $customer->is_active ? 'Active customer' : 'Inactive customer' }}</span>
            <dl class="mt-8 space-y-5 border-t border-[#171323]/8 pt-6 text-sm dark:border-white/10"><div><dt class="text-xs font-semibold uppercase tracking-wider text-[#171323]/40 dark:text-white/40">Email</dt><dd class="mt-1">{{ $customer->email ?: 'Not provided' }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wider text-[#171323]/40 dark:text-white/40">Phone</dt><dd class="mt-1">{{ $customer->phone ?: 'Not provided' }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wider text-[#171323]/40 dark:text-white/40">Customer since</dt><dd class="mt-1">{{ $customer->created_at->format('M j, Y') }}</dd></div></dl>
            @if ($customer->notes)<div class="mt-8 rounded-2xl bg-[#f6f5f2] p-4 text-sm leading-6 text-[#171323]/65 dark:bg-white/6 dark:text-white/65"><p class="mb-1 text-xs font-semibold uppercase tracking-wider text-[#171323]/40 dark:text-white/40">Notes</p>{{ $customer->notes }}</div>@endif

            @if ($editing)
                <form wire:submit="updateCustomer" class="mt-8 space-y-4 border-t border-[#171323]/8 pt-6 dark:border-white/10">
                    <p class="text-sm font-semibold">Edit customer</p>
                    <input wire:model="name" placeholder="Full name" class="w-full rounded-xl border-[#171323]/10 bg-[#f6f5f2] text-sm dark:border-white/10 dark:bg-white/6">@error('name')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    <input wire:model="email" type="email" placeholder="Email address" class="w-full rounded-xl border-[#171323]/10 bg-[#f6f5f2] text-sm dark:border-white/10 dark:bg-white/6">@error('email')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    <input wire:model="phone" placeholder="Phone number" class="w-full rounded-xl border-[#171323]/10 bg-[#f6f5f2] text-sm dark:border-white/10 dark:bg-white/6">
                    <textarea wire:model="notes" rows="4" placeholder="Private notes" class="w-full rounded-xl border-[#171323]/10 bg-[#f6f5f2] text-sm dark:border-white/10 dark:bg-white/6"></textarea>
                    <div class="flex gap-2"><button class="rounded-xl bg-[#171323] px-4 py-2.5 text-sm font-semibold text-white">Save changes</button><button type="button" wire:click="cancelEditing" class="rounded-xl border border-[#171323]/10 px-4 py-2.5 text-sm font-semibold dark:border-white/10">Cancel</button></div>
                </form>
            @endif
        </section>

        <section class="rounded-[1.75rem] border border-[#171323]/8 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/6 sm:p-8">
            <div class="flex items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-600">History</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em]">Appointments</h2></div><span class="rounded-full bg-[#f6f5f2] px-3 py-1 text-xs font-semibold dark:bg-white/8">{{ $customer->appointments->count() }} total</span></div>
            <div class="mt-6 divide-y divide-[#171323]/8 dark:divide-white/10">
                @forelse ($customer->appointments as $appointment)
                    <a wire:navigate href="{{ route('workspace.appointments.show', $appointment->id) }}" class="flex items-center justify-between gap-4 py-4 transition first:pt-0 last:pb-0 hover:text-violet-700"><div><p class="font-semibold">{{ $appointment->service_name ?: $appointment->service?->name ?: 'Appointment' }}</p><p class="mt-1 text-sm text-[#171323]/50 dark:text-white/50">{{ $appointment->start_at->format('M j, Y · H:i') }} · {{ $appointment->staff?->display_name ?: 'Unassigned' }}</p></div><x-workspace.status-badge :status="$appointment->status->value" /></a>
                @empty
                    <x-workspace.empty-state title="No appointment history" message="This customer has not booked an appointment yet." />
                @endforelse
            </div>
        </section>

        <section class="rounded-[1.75rem] border border-[#171323]/8 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/6 sm:p-8 lg:col-span-2">
            <div class="flex items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-600">Relationship</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em]">Activity timeline</h2></div><i data-feather="activity" class="size-5 text-violet-600"></i></div>
            <div class="mt-6 space-y-5">
                @forelse ($auditLogs as $log)
                    <div class="relative border-l-2 border-violet-200 pl-5 dark:border-violet-400/30"><span class="absolute -left-[7px] top-1 size-3 rounded-full bg-violet-500 ring-4 ring-white dark:ring-[#211c31]"></span><p class="text-sm font-semibold">{{ str($log->action)->replace('.', ' · ')->title() }}</p><p class="mt-1 text-xs text-[#171323]/50 dark:text-white/50">{{ $log->created_at->diffForHumans() }} · {{ $log->actor?->name ?? 'System' }}</p></div>
                @empty
                    <p class="text-sm text-[#171323]/50 dark:text-white/50">Customer activity will appear here as details and appointments change.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
