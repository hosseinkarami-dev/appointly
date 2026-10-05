<?php

namespace App\Application\Appointment;

use App\Models\Appointment;
use App\Models\Tenant;

final class UpdateAppointmentAction
{
    /** @param array{notes: ?string} $data */
    public function handle(Tenant $tenant, int $appointmentId, array $data): Appointment
    {
        $appointment = $tenant->appointments()->whereKey($appointmentId)->firstOrFail();
        $before = ['notes' => $appointment->notes];
        $appointment->update(['notes' => $data['notes']]);

        $tenant->auditLogs()->create([
            'actor_id' => auth()->id(),
            'action' => 'appointment.updated',
            'auditable_type' => Appointment::class,
            'auditable_id' => $appointment->id,
            'before' => $before,
            'after' => ['notes' => $appointment->notes],
        ]);

        return $appointment->refresh();
    }
}
