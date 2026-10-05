<?php

namespace App\Livewire\Workspace;

use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Livewire\Workspace\Concerns\InteractsWithWorkspace;
use Carbon\CarbonImmutable;
use Livewire\Component;

class Overview extends Component
{
    use InteractsWithWorkspace;

    public function render(): mixed
    {
        $tenant = $this->tenant();
        $today = CarbonImmutable::now($tenant->timezone)->toDateString();
        $blockingStatuses = array_map(
            fn (AppointmentStatus $status): string => $status->value,
            array_filter(AppointmentStatus::cases(), fn (AppointmentStatus $status): bool => $status->blocksAvailability()),
        );

        return view('livewire.workspace.overview', [
            'tenant' => $tenant,
            'today' => $today,
            'todayAppointments' => $tenant->appointments()
                ->with(['service', 'staff', 'customer'])
                ->whereDate('local_date', $today)
                ->whereIn('status', $blockingStatuses)
                ->orderBy('start_at')
                ->orderBy('id')
                ->limit(6)
                ->get(),
            'stats' => [
                'today' => $tenant->appointments()->whereDate('local_date', $today)->whereIn('status', $blockingStatuses)->count(),
                'customers' => $tenant->customers()->where('is_active', true)->count(),
                'services' => $tenant->services()->where('is_active', true)->count(),
                'team' => $tenant->staff()->where('is_active', true)->count(),
            ],
            'setup' => [
                'profile' => filled($tenant->email) && filled($tenant->timezone),
                'services' => $tenant->services()->where('is_active', true)->exists(),
                'team' => $tenant->staff()->where('is_active', true)->exists(),
                'schedule' => $tenant->workingHours()->where('is_active', true)->exists(),
            ],
        ]);
    }
}
