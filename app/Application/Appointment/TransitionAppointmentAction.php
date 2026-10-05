<?php

namespace App\Application\Appointment;

use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Domain\Appointment\Exceptions\InvalidAppointmentTransition;
use App\Domain\Identity\Enums\MembershipRole;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransitionAppointmentAction
{
    public function __construct(
        private readonly NotifyAppointmentLifecycleAction $notifyAppointmentLifecycle,
    ) {}

    public function handle(
        Tenant $tenant,
        int $appointmentId,
        AppointmentStatus $target,
        ?int $actorId = null,
        ?string $reason = null,
    ): Appointment {
        return DB::transaction(function () use ($tenant, $appointmentId, $target, $actorId, $reason): Appointment {
            $appointment = $tenant->appointments()->whereKey($appointmentId)->lockForUpdate()->firstOrFail();
            $this->authorizeTransition($tenant, $appointment, $actorId);
            $current = $appointment->status;

            if (! $current->canTransitionTo($target)) {
                throw new InvalidAppointmentTransition($current->value, $target->value);
            }

            $appointment->status = $target;

            if ($target === AppointmentStatus::Cancelled) {
                $appointment->cancelled_at = now();
                $appointment->cancelled_by = $actorId;
                $appointment->cancellation_reason = $reason;
            }

            if ($target === AppointmentStatus::Completed) {
                $appointment->completed_at = now();
            }

            $appointment->save();

            $tenant->auditLogs()->create([
                'actor_id' => $actorId,
                'action' => 'appointment.status_changed',
                'auditable_type' => Appointment::class,
                'auditable_id' => $appointment->id,
                'before' => ['status' => $current->value],
                'after' => ['status' => $target->value],
                'metadata' => array_filter([
                    'cancellationReason' => $reason,
                ]),
            ]);

            $appointment = $appointment->refresh();
            $this->notifyAppointmentLifecycle->handle($appointment, match ($target) {
                AppointmentStatus::Confirmed => 'Your appointment has been confirmed.',
                AppointmentStatus::Cancelled => 'Your appointment has been cancelled.',
                AppointmentStatus::Completed => 'Your appointment has been completed.',
                AppointmentStatus::NoShow => 'Your appointment was marked as a no-show.',
                AppointmentStatus::Pending => 'Your appointment is pending confirmation.',
            });

            return $appointment;
        });
    }

    private function authorizeTransition(Tenant $tenant, Appointment $appointment, ?int $actorId): void
    {
        $actor = $actorId === null
            ? null
            : $tenant->users()->whereKey($actorId)->wherePivot('is_active', true)->first();

        abort_unless($actor instanceof User, 403);

        if ($actor->pivot->role === MembershipRole::Owner->value) {
            return;
        }

        $staffProfile = $tenant->staff()->where('user_id', $actor->id)->where('is_active', true)->first();

        abort_unless($staffProfile !== null && $appointment->staff_profile_id === $staffProfile->id, 403);
    }
}
