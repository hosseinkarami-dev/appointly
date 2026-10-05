<?php

namespace App\Livewire\Workspace;

use App\Livewire\Workspace\Concerns\InteractsWithWorkspace;
use Carbon\CarbonImmutable;
use Livewire\Component;

class Calendar extends Component
{
    use InteractsWithWorkspace;

    public string $date = '';

    public string $viewMode = 'day';

    public string $statusFilter = 'active';

    public function mount(): void
    {
        $this->date = CarbonImmutable::now($this->tenant()->timezone)->toDateString();
    }

    public function previousDay(): void
    {
        $this->date = CarbonImmutable::createFromFormat('!Y-m-d', $this->date)
            ->subDays($this->viewMode === 'week' ? 7 : 1)
            ->toDateString();
    }

    public function nextDay(): void
    {
        $this->date = CarbonImmutable::createFromFormat('!Y-m-d', $this->date)
            ->addDays($this->viewMode === 'week' ? 7 : 1)
            ->toDateString();
    }

    public function showToday(): void
    {
        $this->date = CarbonImmutable::now($this->tenant()->timezone)->toDateString();
    }

    public function setViewMode(string $viewMode): void
    {
        abort_unless(in_array($viewMode, ['day', 'week'], true), 422);

        $this->viewMode = $viewMode;
    }

    public function render(): mixed
    {
        $tenant = $this->tenant();
        $anchor = CarbonImmutable::createFromFormat('!Y-m-d', $this->date, $tenant->timezone);
        $periodStart = $this->viewMode === 'week' ? $anchor->startOfWeek() : $anchor;
        $periodEnd = $this->viewMode === 'week' ? $periodStart->addDays(6) : $anchor;
        $appointments = $tenant->appointments()
            ->with(['service', 'staff', 'customer'])
            ->whereBetween('local_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('status', '!=', 'cancelled'))
            ->when($this->statusFilter !== 'active' && $this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('start_at')
            ->orderBy('id')
            ->get();
        $daysOff = $tenant->daysOff()
            ->with('staff')
            ->whereBetween('local_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->orderBy('local_date')
            ->get()
            ->groupBy(fn ($dayOff): string => $dayOff->local_date->toDateString());

        return view('livewire.workspace.calendar', [
            'tenant' => $tenant,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'appointments' => $appointments,
            'appointmentsByDate' => $appointments->groupBy(fn ($appointment): string => $appointment->local_date->toDateString()),
            'daysOffByDate' => $daysOff,
        ]);
    }
}
