<?php

namespace App\Application\Appointment;

use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Domain\Appointment\Exceptions\BookingUnavailable;
use App\Domain\Appointment\Exceptions\InvalidAppointmentTransition;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

final class CancelPublicAppointmentAction
{
    public function __construct(
        private readonly NotifyAppointmentLifecycleAction $notifyAppointmentLifecycle,
    ) {}

    public function handle(string $token, ?string $reason = null): Appointment
    {
        return DB::transaction(function () use ($token, $reason): Appointment {
            $appointment = Appointment::query()
                ->where('public_token', $token)
                ->lockForUpdate()
                ->firstOrFail();
            $current = $appointment->status;

            if (! $current->canTransitionTo(AppointmentStatus::Cancelled)) {
                throw new InvalidAppointmentTransition($current->value, AppointmentStatus::Cancelled->value);
            }

            if ($appointment->start_at->isBefore(now()->addHours($appointment->tenant->cancellation_cutoff_hours))) {
                throw new BookingUnavailable('This appointment is inside the cancellation cutoff window.');
            }

            $appointment->status = AppointmentStatus::Cancelled;
            $appointment->cancelled_at = now();
            $appointment->cancelled_by = null;
            $appointment->cancellation_reason = $reason;
            $appointment->save();

            $appointment->tenant->auditLogs()->create([
                'actor_id' => null,
                'action' => 'appointment.publicly_cancelled',
                'auditable_type' => Appointment::class,
                'auditable_id' => $appointment->id,
                'before' => ['status' => $current->value],
                'after' => ['status' => AppointmentStatus::Cancelled->value],
                'metadata' => array_filter(['cancellationReason' => $reason]),
            ]);

            $appointment = $appointment->refresh();
            $this->notifyAppointmentLifecycle->handle($appointment, 'Your appointment has been cancelled.');

            return $appointment;
        });
    }
}
