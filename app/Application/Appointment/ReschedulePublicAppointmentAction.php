<?php

namespace App\Application\Appointment;

use App\Application\Availability\GetAvailableSlotsAction;
use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Domain\Appointment\Exceptions\BookingUnavailable;
use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ReschedulePublicAppointmentAction
{
    public function __construct(
        private readonly GetAvailableSlotsAction $getAvailableSlots,
        private readonly NotifyAppointmentLifecycleAction $notifyAppointmentLifecycle,
    ) {}

    public function handle(string $token, string $startAt): Appointment
    {
        return DB::transaction(function () use ($token, $startAt): Appointment {
            $appointment = Appointment::query()
                ->with(['tenant', 'service', 'staff'])
                ->where('public_token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($appointment->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true)) {
                throw new BookingUnavailable('This appointment can no longer be rescheduled.');
            }

            if ($appointment->start_at->isBefore(now()->addHours($appointment->tenant->cancellation_cutoff_hours))) {
                throw new BookingUnavailable('This appointment is inside the rescheduling cutoff window.');
            }

            $tenant = $appointment->tenant;
            $service = $appointment->service;
            $staff = $appointment->staff;
            $targetStart = CarbonImmutable::parse($startAt)->utc();
            $localStart = $targetStart->setTimezone($tenant->timezone);

            if ($targetStart->isPast() || $localStart->startOfDay()->diffInDays($localStart->nowWithSameTz()->startOfDay()) > $tenant->booking_horizon_days) {
                throw new BookingUnavailable('That time is outside the booking window.');
            }

            $lockNow = now();
            DB::table('staff_day_locks')->insertOrIgnore([
                'tenant_id' => $tenant->id,
                'staff_profile_id' => $staff->id,
                'local_date' => $localStart->toDateString(),
                'created_at' => $lockNow,
                'updated_at' => $lockNow,
            ]);
            DB::table('staff_day_locks')
                ->where('tenant_id', $tenant->id)
                ->where('staff_profile_id', $staff->id)
                ->where('local_date', $localStart->toDateString())
                ->lockForUpdate()
                ->first();

            $sameStart = $targetStart->equalTo($appointment->start_at);
            $isAvailable = $sameStart || collect($this->getAvailableSlots->handle(
                $tenant,
                $service->id,
                $staff->id,
                $localStart->toDateString(),
            ))->contains(fn (array $slot): bool => $slot['startAt'] === $targetStart->toIso8601String());

            if (! $isAvailable) {
                throw new BookingUnavailable('That time is no longer available.');
            }

            $before = [
                'startAt' => $appointment->start_at->toIso8601String(),
                'endAt' => $appointment->end_at->toIso8601String(),
            ];
            $appointment->start_at = $targetStart;
            $appointment->end_at = $targetStart->addMinutes($appointment->duration_minutes + $appointment->buffer_minutes);
            $appointment->local_date = $localStart->toDateString();
            $appointment->save();

            $tenant->auditLogs()->create([
                'actor_id' => null,
                'action' => 'appointment.publicly_rescheduled',
                'auditable_type' => Appointment::class,
                'auditable_id' => $appointment->id,
                'before' => $before,
                'after' => [
                    'startAt' => $appointment->start_at->toIso8601String(),
                    'endAt' => $appointment->end_at->toIso8601String(),
                ],
                'metadata' => [],
            ]);

            $appointment = $appointment->refresh();
            $this->notifyAppointmentLifecycle->handle($appointment, 'Your appointment time has been changed.');

            return $appointment;
        });
    }
}
