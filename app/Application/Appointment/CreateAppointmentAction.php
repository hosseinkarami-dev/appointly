<?php

namespace App\Application\Appointment;

use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Domain\Appointment\Exceptions\BookingUnavailable;
use App\Domain\Appointment\Exceptions\PendingBookingExists;
use App\Domain\Schedule\Services\AvailabilityCalculator;
use App\Domain\Schedule\ValueObjects\TimeInterval;
use App\Jobs\DeliverAppointmentWebhook;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Tenant;
use App\Notifications\AppointmentLifecycleNotification;
use App\Notifications\NewAppointmentNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateAppointmentAction
{
    public function __construct(
        private readonly AvailabilityCalculator $availabilityCalculator,
    ) {}

    /** @param array{serviceId: int, staffId: int, startAt: string, customer: array{name: string, email?: ?string, phone?: ?string}, notes?: ?string} $data */
    public function handle(Tenant $tenant, array $data): Appointment
    {
        $service = $tenant->services()->whereKey($data['serviceId'])->where('is_active', true)->firstOrFail();
        $staff = $tenant->staff()->whereKey($data['staffId'])->where('is_active', true)->firstOrFail();

        abort_unless(
            $staff->services()->whereKey($service->id)->wherePivot('is_active', true)->exists(),
            404
        );

        $startAt = CarbonImmutable::parse($data['startAt'])->utc();
        $localStart = $startAt->setTimezone($tenant->timezone);
        $localDate = $localStart->toDateString();
        $totalDuration = $service->duration_minutes + $service->buffer_minutes;
        $endAt = $startAt->addMinutes($totalDuration);
        $localEnd = $endAt->setTimezone($tenant->timezone);

        if ($startAt->isPast() || $localStart->toDateString() !== $localEnd->toDateString()) {
            throw new BookingUnavailable('The selected time is not bookable.');
        }

        if ($localStart->startOfDay()->diffInDays($localStart->nowWithSameTz()->startOfDay()) > $tenant->booking_horizon_days) {
            throw new BookingUnavailable('The selected time is outside the booking window.');
        }

        return DB::transaction(function () use ($data, $tenant, $service, $staff, $startAt, $endAt, $localStart, $localDate, $totalDuration): Appointment {
            $now = now();
            DB::table('staff_day_locks')->insertOrIgnore([
                'tenant_id' => $tenant->id,
                'staff_profile_id' => $staff->id,
                'local_date' => $localDate,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('staff_day_locks')
                ->where('tenant_id', $tenant->id)
                ->where('staff_profile_id', $staff->id)
                ->where('local_date', $localDate)
                ->lockForUpdate()
                ->first();

            $workingHours = $staff->workingHours()
                ->where('weekday', $localStart->isoWeekday())
                ->where('is_active', true)
                ->orderBy('start_local_time')
                ->get();
            $isDayOff = $tenant->daysOff()
                ->whereDate('local_date', $localDate)
                ->where(function ($query) use ($staff): void {
                    $query->whereNull('staff_profile_id')->orWhere('staff_profile_id', $staff->id);
                })
                ->exists();

            if ($isDayOff || $workingHours->isEmpty()) {
                throw new BookingUnavailable('The selected staff member is not working at that time.');
            }

            $occupiedAppointments = $staff->appointments()
                ->whereDate('local_date', $localDate)
                ->whereIn('status', array_map(
                    fn (AppointmentStatus $status): string => $status->value,
                    array_filter(AppointmentStatus::cases(), fn (AppointmentStatus $status): bool => $status->blocksAvailability())
                ))
                ->get();
            $workingIntervals = $workingHours->map(fn ($workingHour): TimeInterval => new TimeInterval(
                $this->minutes($workingHour->start_local_time),
                $this->minutes($workingHour->end_local_time)
            ))->all();
            $occupiedIntervals = $occupiedAppointments->map(function (Appointment $appointment) use ($tenant): TimeInterval {
                $start = $appointment->start_at->setTimezone($tenant->timezone);
                $end = $appointment->end_at->setTimezone($tenant->timezone);

                return new TimeInterval(
                    ($start->hour * 60) + $start->minute,
                    ($end->hour * 60) + $end->minute
                );
            })->all();
            $candidateStart = ($localStart->hour * 60) + $localStart->minute;
            $availableSlots = $this->availabilityCalculator->calculate(
                $workingIntervals,
                $occupiedIntervals,
                $totalDuration
            );
            $isAvailable = collect($availableSlots)->contains(
                fn (TimeInterval $slot): bool => $slot->startMinute === $candidateStart
            );

            if (! $isAvailable) {
                throw new BookingUnavailable;
            }

            $customer = $this->findOrCreateCustomer($tenant, $data['customer']);
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $hasPendingBooking = $tenant->appointments()
                ->where('customer_id', $customer->id)
                ->where('service_id', $service->id)
                ->where('staff_profile_id', $staff->id)
                ->where('status', AppointmentStatus::Pending)
                ->exists();

            if ($hasPendingBooking) {
                throw new PendingBookingExists;
            }

            $appointment = $tenant->appointments()->create([
                'customer_id' => $customer->id,
                'public_token' => Str::random(64),
                'staff_profile_id' => $staff->id,
                'service_id' => $service->id,
                'service_name' => $service->name,
                'duration_minutes' => $service->duration_minutes,
                'buffer_minutes' => $service->buffer_minutes,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'local_date' => $localDate,
                'status' => AppointmentStatus::Pending,
                'notes' => $data['notes'] ?? null,
            ]);

            $tenant->auditLogs()->create([
                'actor_id' => null,
                'action' => 'appointment.created',
                'auditable_type' => Appointment::class,
                'auditable_id' => $appointment->id,
                'before' => [],
                'after' => [
                    'status' => AppointmentStatus::Pending->value,
                    'startAt' => $appointment->start_at->toIso8601String(),
                    'serviceName' => $appointment->service_name,
                ],
                'metadata' => ['source' => 'public_booking'],
            ]);

            DeliverAppointmentWebhook::dispatch($appointment, 'appointment.created')->afterCommit();

            if ($appointment->customer?->email) {
                $appointment->customer->notify(new AppointmentLifecycleNotification(
                    $appointment->load(['customer', 'tenant']),
                    'Your booking request has been received and is waiting for confirmation.',
                ));
            }

            if ($tenant->notify_new_appointments) {
                $tenant->users()
                    ->wherePivot('is_active', true)
                    ->get()
                    ->each(fn ($user) => $user->notify(new NewAppointmentNotification($appointment->load('customer'))));
            }

            return $appointment;
        });
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    /** @param array{name: string, email?: ?string, phone?: ?string} $data */
    private function findOrCreateCustomer(Tenant $tenant, array $data): Customer
    {
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;
        $normalizedEmail = $email === null ? null : Str::lower(trim($email));
        $normalizedPhone = $phone === null ? null : preg_replace('/\D+/', '', $phone);
        $customer = $tenant->customers()
            ->when($normalizedEmail !== null, fn ($query) => $query->where('normalized_email', $normalizedEmail))
            ->when($normalizedEmail === null && $normalizedPhone !== null, fn ($query) => $query->where('normalized_phone', $normalizedPhone))
            ->first();

        return $customer ?? $tenant->customers()->create([
            'name' => $data['name'],
            'email' => $email,
            'phone' => $phone,
            'normalized_email' => $normalizedEmail,
            'normalized_phone' => $normalizedPhone,
        ]);
    }
}
