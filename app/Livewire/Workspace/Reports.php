<?php

namespace App\Livewire\Workspace;

use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Livewire\Workspace\Concerns\InteractsWithWorkspace;
use Carbon\CarbonImmutable;
use Livewire\Component;

class Reports extends Component
{
    use InteractsWithWorkspace;

    public string $month = '';

    public function mount(): void
    {
        $this->month = CarbonImmutable::now($this->tenant()->timezone)->format('Y-m');
    }

    public function render(): mixed
    {
        $tenant = $this->tenant();
        $period = CarbonImmutable::createFromFormat('!Y-m', $this->month, $tenant->timezone);
        $appointments = $tenant->appointments()
            ->with(['service', 'staff'])
            ->whereBetween('local_date', [$period->startOfMonth()->toDateString(), $period->endOfMonth()->toDateString()])
            ->get();
        $total = $appointments->count();
        $completed = $appointments->where('status', AppointmentStatus::Completed)->count();
        $cancelled = $appointments->where('status', AppointmentStatus::Cancelled)->count();
        $confirmed = $appointments->where('status', AppointmentStatus::Confirmed)->count();
        $noShows = $appointments->where('status', AppointmentStatus::NoShow)->count();
        $bookedMinutes = $appointments->whereNotIn('status', [AppointmentStatus::Cancelled])->sum(fn ($appointment): int => $appointment->duration_minutes + $appointment->buffer_minutes);
        $availableMinutes = 0;
        $workingHours = $tenant->workingHours()->where('is_active', true)->get();
        for ($day = $period->startOfMonth(); $day->lte($period->endOfMonth()); $day = $day->addDay()) {
            foreach ($workingHours->where('weekday', $day->isoWeekday()) as $workingHour) {
                $availableMinutes += CarbonImmutable::parse($workingHour->end_local_time)->diffInMinutes(CarbonImmutable::parse($workingHour->start_local_time));
            }
        }
        $serviceBreakdown = $appointments
            ->groupBy(fn ($appointment): string => $appointment->service_name ?: $appointment->service?->name ?: 'Unassigned')
            ->map(fn ($group): int => $group->count())
            ->sortDesc();

        return view('livewire.workspace.reports', [
            'tenant' => $tenant,
            'periodLabel' => $period->format('F Y'),
            'metrics' => [
                'total' => $total,
                'confirmed' => $confirmed,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'noShows' => $noShows,
                'bookedMinutes' => $bookedMinutes,
                'averageDuration' => $total === 0 ? 0 : (int) round($bookedMinutes / $total),
                'utilizationRate' => $availableMinutes === 0 ? 0 : min(100, (int) round(($bookedMinutes / $availableMinutes) * 100)),
                'completionRate' => $total === 0 ? 0 : (int) round(($completed / $total) * 100),
                'cancellationRate' => $total === 0 ? 0 : (int) round(($cancelled / $total) * 100),
            ],
            'serviceBreakdown' => $serviceBreakdown,
        ]);
    }
}
