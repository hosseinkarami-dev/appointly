<?php

namespace App\Application\Availability;

use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Domain\Schedule\Services\AvailabilityCalculator;
use App\Domain\Schedule\ValueObjects\TimeInterval;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidDateException;

class GetAvailableSlotsAction
{
    public function __construct(
        private readonly AvailabilityCalculator $availabilityCalculator,
    ) {}

    /** @return list<array{staffId: int, startAt: string, endAt: string}> */
    public function handle(Tenant $tenant, int $serviceId, int $staffId, string $date): array
    {
        return $this->slots($tenant, $serviceId, $staffId, $date, false);
    }

    /** @return list<array{staffId: int, startAt: string, endAt: string, available: bool}> */
    public function handleForBookingPage(Tenant $tenant, int $serviceId, int $staffId, string $date): array
    {
        return $this->slots($tenant, $serviceId, $staffId, $date, true);
    }

    /**
     * @return list<array{staffId: int, startAt: string, endAt: string}|array{staffId: int, startAt: string, endAt: string, available: bool}>
     */
    private function slots(Tenant $tenant, int $serviceId, int $staffId, string $date, bool $includeUnavailable): array
    {
        abort_unless($tenant->is_active, 404);

        $service = $tenant->services()->whereKey($serviceId)->where('is_active', true)->firstOrFail();
        $staff = $tenant->staff()->whereKey($staffId)->where('is_active', true)->firstOrFail();
        abort_unless($staff->services()->whereKey($service->id)->wherePivot('is_active', true)->exists(), 404);
        $localDate = CarbonImmutable::createFromFormat('Y-m-d', $date, $tenant->timezone);

        $workingHours = $staff->workingHours()
            ->where('weekday', $localDate->isoWeekday())
            ->where('is_active', true)
            ->orderBy('start_local_time')
            ->get();
        $isDayOff = $tenant->daysOff()
            ->whereDate('local_date', $localDate->toDateString())
            ->where(function ($query) use ($staff): void {
                $query->whereNull('staff_profile_id')->orWhere('staff_profile_id', $staff->id);
            })
            ->exists();

        if ($isDayOff) {
            return [];
        }

        $occupiedAppointments = $staff->appointments()
            ->whereDate('local_date', $localDate->toDateString())
            ->whereIn('status', array_map(
                fn (AppointmentStatus $status): string => $status->value,
                array_filter(AppointmentStatus::cases(), fn (AppointmentStatus $status): bool => $status->blocksAvailability())
            ))
            ->get();
        $workingIntervals = $workingHours->map(fn ($workingHour): TimeInterval => new TimeInterval(
            $this->minutes($workingHour->start_local_time),
            $this->minutes($workingHour->end_local_time)
        ))->all();
        $occupiedIntervals = $occupiedAppointments->map(function ($appointment) use ($tenant): TimeInterval {
            $start = $appointment->start_at->setTimezone($tenant->timezone);
            $end = $appointment->end_at->setTimezone($tenant->timezone);

            return new TimeInterval(
                ($start->hour * 60) + $start->minute,
                ($end->hour * 60) + $end->minute
            );
        })->all();
        $availableSlots = $this->availabilityCalculator->calculate(
            $workingIntervals,
            $occupiedIntervals,
            $service->duration_minutes + $service->buffer_minutes
        );
        $slots = $includeUnavailable
            ? $this->availabilityCalculator->calculate($workingIntervals, [], $service->duration_minutes + $service->buffer_minutes)
            : $availableSlots;
        $availableStarts = array_fill_keys(array_map(
            fn (TimeInterval $slot): int => $slot->startMinute,
            $availableSlots,
        ), true);
        $slotsForResponse = [];

        $availableSlots = [];

        foreach ($slots as $slot) {
            $localHour = intdiv($slot->startMinute, 60);
            $localMinute = $slot->startMinute % 60;

            try {
                $start = CarbonImmutable::createSafe(
                    $localDate->year,
                    $localDate->month,
                    $localDate->day,
                    $localHour,
                    $localMinute,
                    0,
                    $tenant->timezone,
                );
            } catch (InvalidDateException) {
                continue;
            }

            if ($start->hour !== $localHour || $start->minute !== $localMinute) {
                continue;
            }

            $slotData = [
                'staffId' => $staff->id,
                'startAt' => $start->utc()->toIso8601String(),
                'endAt' => $start->addMinutes($service->duration_minutes)->utc()->toIso8601String(),
            ];

            if ($includeUnavailable) {
                $slotData['available'] = isset($availableStarts[$slot->startMinute]);
            }

            $slotsForResponse[] = $slotData;
        }

        return $slotsForResponse;
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}
