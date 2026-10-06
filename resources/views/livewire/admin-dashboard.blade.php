<main class="min-h-[calc(100vh-73px)] bg-[#f4f2f8] px-4 py-5 sm:px-6 lg:px-8">
    <div class="mx-auto flex max-w-[1440px] gap-6">
        <aside class="hidden w-64 shrink-0 flex-col rounded-[2rem] bg-[#171323] p-5 text-white shadow-xl shadow-violet-950/10 lg:flex">
            <div class="flex items-center gap-3 border-b border-white/10 pb-6"><span class="grid size-10 place-items-center rounded-2xl bg-violet-400 text-lg font-bold text-[#171323]">a.</span><div><p class="font-semibold">{{ $tenant->name }}</p><p class="text-xs text-white/45">Business workspace</p></div></div>
            <nav class="mt-8 space-y-2 text-sm"><a href="#overview" class="flex items-center gap-3 rounded-xl bg-white/10 px-3 py-3 font-medium text-white"><svg class="size-4 text-violet-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg> Overview</a><a href="#schedule" class="flex items-center gap-3 rounded-xl px-3 py-3 text-white/55 transition hover:bg-white/10 hover:text-white"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg> Schedule</a><a href="#team" class="flex items-center gap-3 rounded-xl px-3 py-3 text-white/55 transition hover:bg-white/10 hover:text-white"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg> Team & services</a></nav>
            <div class="mt-auto rounded-2xl bg-violet-400/15 p-4"><p class="text-xs font-semibold uppercase tracking-widest text-violet-200">Quick tip</p><p class="mt-2 text-sm leading-6 text-white/65">Share your public booking link to fill open hours.</p></div>
        </aside>
        <div id="overview" class="min-w-0 flex-1">
            <nav class="mb-4 grid grid-cols-3 gap-2 rounded-2xl border border-[#171323]/8 bg-white/80 p-2 shadow-sm lg:hidden"><a href="#overview" class="rounded-xl bg-[#171323] px-2 py-2.5 text-center text-xs font-semibold text-white">Overview</a><a href="#schedule" class="rounded-xl px-2 py-2.5 text-center text-xs font-semibold text-[#171323]/60">Schedule</a><a href="#team" class="rounded-xl px-2 py-2.5 text-center text-xs font-semibold text-[#171323]/60">Team</a></nav>
            <div class="flex flex-wrap items-end justify-between gap-4 rounded-[2rem] border border-[#171323]/8 bg-white/75 p-6 shadow-sm backdrop-blur sm:p-8">
                <div><div class="flex items-center gap-2 text-sm font-medium text-violet-600"><span class="size-2 rounded-full bg-emerald-400"></span> Workspace is live</div><h1 class="mt-3 text-3xl font-semibold tracking-[-0.04em] text-[#171323] sm:text-4xl">Good to see you, {{ \Illuminate\Support\Str::before(Auth::user()->name, ' ') }}.</h1><p class="mt-2 text-sm text-[#171323]/50">Here’s what’s happening at {{ $tenant->name }}.</p></div>
                <div class="flex items-center gap-3"><a href="{{ route('business.show', $tenant) }}" target="_blank" class="inline-flex items-center gap-2 rounded-full border border-[#171323]/10 bg-white px-4 py-2.5 text-sm font-semibold text-[#171323] transition hover:border-violet-300 hover:text-violet-700"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 3h7v7M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg> View booking page</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-full bg-[#171323] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-900">Sign out</button></form></div>
            </div>
            @if ($notifications->isNotEmpty())
                <div class="mt-4 flex items-center justify-between gap-4 rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-950"><div class="flex items-center gap-3"><span class="grid size-8 place-items-center rounded-xl bg-violet-600 text-white"><i data-feather="activity" class="size-4"></i></span><span><strong>{{ $notifications->whereNull('read_at')->count() }} new activity</strong> in your workspace.</span></div><button wire:click="markNotificationsRead" class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-violet-700 ring-1 ring-violet-200">Mark read</button></div>
            @endif
            <div class="mt-6 grid gap-4 sm:grid-cols-3"><div class="rounded-2xl bg-[#171323] p-5 text-white"><p class="text-sm text-white/55">Services</p><p class="mt-3 text-3xl font-semibold">{{ $services->count() }}</p><p class="mt-1 text-xs text-violet-300">Available to book</p></div><div class="rounded-2xl bg-violet-100 p-5 text-[#171323]"><p class="text-sm text-violet-900/55">Team members</p><p class="mt-3 text-3xl font-semibold">{{ $staff->count() }}</p><p class="mt-1 text-xs text-violet-700">Ready to help</p></div><div class="rounded-2xl bg-amber-100 p-5 text-[#171323]"><p class="text-sm text-amber-900/55">Upcoming</p><p class="mt-3 text-3xl font-semibold">{{ $appointments->whereIn('status', ['pending', 'confirmed'])->count() }}</p><p class="mt-1 text-xs text-amber-700">Appointments to review</p></div></div>
            <section class="mt-6 rounded-[1.75rem] border border-[#171323]/8 bg-white/85 p-6 shadow-sm sm:p-7"><div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-violet-600">Workspace settings</p><h2 class="mt-1 text-xl font-semibold">Your public profile</h2></div>@if (session('profile-saved'))<span class="rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700">{{ session('profile-saved') }}</span>@endif</div><form wire:submit="updateTenantProfile" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><div><label class="text-sm font-medium text-[#171323]/70">Business name<input wire:model="tenantName" class="mt-2 w-full rounded-xl border-slate-200 px-4 py-3"></label>@error('tenantName')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div><div><label class="text-sm font-medium text-[#171323]/70">Public slug<input wire:model="tenantSlug" class="mt-2 w-full rounded-xl border-slate-200 px-4 py-3"></label>@error('tenantSlug')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div><div><label class="text-sm font-medium text-[#171323]/70">Timezone<input wire:model="tenantTimezone" class="mt-2 w-full rounded-xl border-slate-200 px-4 py-3"></label>@error('tenantTimezone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div><div><label class="text-sm font-medium text-[#171323]/70">Contact email<input wire:model="tenantEmail" type="email" class="mt-2 w-full rounded-xl border-slate-200 px-4 py-3"></label></div><div><label class="text-sm font-medium text-[#171323]/70">Contact phone<input wire:model="tenantPhone" class="mt-2 w-full rounded-xl border-slate-200 px-4 py-3"></label></div><div><label class="text-sm font-medium text-[#171323]/70">Booking horizon (days)<input wire:model="bookingHorizonDays" type="number" min="1" max="365" class="mt-2 w-full rounded-xl border-slate-200 px-4 py-3"></label></div><div class="sm:col-span-2 lg:col-span-3"><button class="rounded-full bg-[#171323] px-5 py-3 text-sm font-semibold text-white transition hover:bg-violet-900">Save profile</button></div></form></section>
            <div id="team" class="mt-6 grid gap-6 xl:grid-cols-2">
            <section class="rounded-[1.75rem] border border-[#171323]/8 bg-white/85 p-6 shadow-sm sm:p-7">
                <div class="flex items-center justify-between gap-3"><h2 class="text-xl font-semibold">Services</h2><span class="text-sm text-slate-500">{{ $services->count() }}</span></div>
                <form wire:submit="createService" class="mt-5 space-y-3 border-b border-slate-100 pb-6">
                    <input wire:model="serviceName" placeholder="Service name" class="w-full rounded-xl border-slate-200 px-4 py-3">
                    @error('serviceName')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    <textarea wire:model="serviceDescription" placeholder="Description (optional)" rows="2" class="w-full rounded-xl border-slate-200 px-4 py-3"></textarea>
                    <div class="grid grid-cols-2 gap-3"><input wire:model="serviceDuration" type="number" min="5" placeholder="Minutes" class="w-full rounded-xl border-slate-200 px-4 py-3"><input wire:model="serviceBuffer" type="number" min="0" placeholder="Buffer" class="w-full rounded-xl border-slate-200 px-4 py-3"></div>
                    <button class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-500">Add service</button>
                </form>
                <div class="mt-5 space-y-3">
                    @forelse ($services as $service)
                        <div wire:key="service-{{ $service->id }}" class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                            <span class="font-medium">{{ $service->name }}</span><span class="text-sm text-slate-500">{{ $service->duration_minutes }} min</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No services yet.</p>
                    @endforelse
                </div>
            </section>
            <section id="schedule" class="rounded-[1.75rem] border border-[#171323]/8 bg-white/85 p-6 shadow-sm sm:p-7">
                <h2 class="text-xl font-semibold">Working hours</h2>
                <form wire:submit="createWorkingHour" class="mt-5 space-y-3 border-b border-slate-100 pb-6">
                    <select wire:model="scheduleStaffId" class="w-full rounded-xl border-slate-200 px-4 py-3"><option value="">Select staff member</option>@foreach ($staff as $member)<option wire:key="schedule-staff-{{ $member->id }}" value="{{ $member->id }}">{{ $member->display_name }}</option>@endforeach</select>
                    <div class="grid grid-cols-2 gap-3"><select wire:model="scheduleWeekday" class="rounded-xl border-slate-200 px-4 py-3">@foreach ([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $day => $label)<option value="{{ $day }}">{{ $label }}</option>@endforeach</select><input wire:model="scheduleStart" type="time" class="rounded-xl border-slate-200 px-4 py-3"></div>
                    <input wire:model="scheduleEnd" type="time" class="w-full rounded-xl border-slate-200 px-4 py-3">
                    @error('scheduleEnd')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    <button class="w-full rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-700">Save working hours</button>
                </form>
                <div class="mt-5 space-y-2">@forelse ($workingHours as $hour)<div wire:key="hour-{{ $hour->id }}" class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm"><span>{{ $hour->staff->display_name }} · {{ ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$hour->weekday - 1] }}</span><span class="text-slate-500">{{ substr($hour->start_local_time, 0, 5) }}–{{ substr($hour->end_local_time, 0, 5) }}</span></div>@empty<p class="text-sm text-slate-500">No working hours configured.</p>@endforelse</div>
            </section>
            <section class="rounded-[1.75rem] border border-[#171323]/8 bg-white/85 p-6 shadow-sm sm:p-7">
                <h2 class="text-xl font-semibold">Days off</h2>
                <form wire:submit="createDayOff" class="mt-5 space-y-3 border-b border-slate-100 pb-6">
                    <input wire:model="dayOffDate" type="date" class="w-full rounded-xl border-slate-200 px-4 py-3">
                    <input wire:model="dayOffReason" placeholder="Reason (optional)" class="w-full rounded-xl border-slate-200 px-4 py-3">
                    @error('dayOffDate')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    <button class="w-full rounded-xl border border-slate-200 px-4 py-3 font-semibold hover:bg-slate-50">Add day off</button>
                </form>
                <div class="mt-5 space-y-2">@forelse ($daysOff as $dayOff)<div wire:key="day-off-{{ $dayOff->id }}" class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm"><span>{{ $dayOff->local_date->format('M j, Y') }}</span><span class="text-slate-500">{{ $dayOff->reason ?: 'Unavailable' }}</span></div>@empty<p class="text-sm text-slate-500">No days off configured.</p>@endforelse</div>
            </section>
            <section class="rounded-[1.75rem] border border-[#171323]/8 bg-white/85 p-6 shadow-sm sm:p-7">
                <div class="flex items-center justify-between gap-3"><h2 class="text-xl font-semibold">Staff</h2><span class="text-sm text-slate-500">{{ $staff->count() }}</span></div>
                <form wire:submit="createStaff" class="mt-5 space-y-3 border-b border-slate-100 pb-6">
                    <input wire:model="staffName" placeholder="Staff member name" class="w-full rounded-xl border-slate-200 px-4 py-3">
                    @error('staffName')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    <textarea wire:model="staffDescription" placeholder="Description (optional)" rows="2" class="w-full rounded-xl border-slate-200 px-4 py-3"></textarea>
                    <button class="w-full rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-700">Add staff member</button>
                </form>
                <div class="mt-5 space-y-3">
                    @forelse ($staff as $member)
                        <div wire:key="staff-{{ $member->id }}" class="rounded-2xl bg-slate-50 px-4 py-3"><p class="font-medium">{{ $member->display_name }}</p><p class="text-sm text-slate-500">{{ $member->description ?: 'Available for appointments' }}</p><div class="mt-3 flex flex-wrap gap-2">@foreach ($services as $service)<button type="button" wire:click="toggleAssignment({{ $member->id }}, {{ $service->id }})" class="rounded-full px-3 py-1 text-xs font-medium {{ $member->services->contains(fn ($assigned) => $assigned->id === $service->id && $assigned->pivot->is_active) ? 'bg-indigo-100 text-indigo-700' : 'bg-white text-slate-500 ring-1 ring-slate-200' }}">{{ $service->name }}</button>@endforeach</div></div>
                    @empty
                        <p class="text-sm text-slate-500">No staff members yet.</p>
                    @endforelse
                </div>
            </section>
            <section class="rounded-[1.75rem] border border-[#171323]/8 bg-white/85 p-6 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-violet-600">Operations</p><h2 class="mt-1 text-xl font-semibold">Appointments</h2></div><span class="grid size-10 place-items-center rounded-2xl bg-violet-100 text-violet-700"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/><path d="m8 15 2 2 5-5"/></svg></span></div>
                <div class="workspace-scroll mt-5 flex gap-2 overflow-x-auto pb-2 text-xs font-semibold"><button wire:click="$set('appointmentStatus', 'all')" class="shrink-0 rounded-full px-3 py-2 {{ $appointmentStatus === 'all' ? 'bg-[#171323] text-white' : 'bg-slate-100 text-slate-500' }}">All</button>@foreach (['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)<button wire:key="filter-{{ $value }}" wire:click="$set('appointmentStatus', '{{ $value }}')" class="shrink-0 rounded-full px-3 py-2 {{ $appointmentStatus === $value ? 'bg-[#171323] text-white' : 'bg-slate-100 text-slate-500' }}">{{ $label }}</button>@endforeach</div>
                <div class="mt-5 divide-y divide-slate-100">
                    @forelse ($appointments as $appointment)
                        <div wire:key="appointment-{{ $appointment->id }}" class="flex flex-wrap items-center justify-between gap-3 py-4">
                            <div><p class="font-medium">{{ $appointment->service->name }}</p><p class="text-sm text-slate-500">{{ $appointment->start_at->format('M j, Y · H:i') }} · {{ $appointment->staff->display_name }}</p></div>
                            <select wire:change="updateStatus({{ $appointment->id }}, $event.target.value)" class="rounded-lg border-slate-200 text-sm">
                                @foreach (\App\Domain\Appointment\Enums\AppointmentStatus::values() as $option)<option value="{{ $option }}" @selected($appointment->status->value === $option)>{{ ucfirst($option) }}</option>@endforeach
                            </select>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-slate-500">No appointments yet.</p>
                    @endforelse
                </div>
            </section>
            </div>
        </div>
    </div>
</main>
