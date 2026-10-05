<?php

namespace App\Domain\Schedule\Services;

use App\Domain\Schedule\ValueObjects\TimeInterval;

final class AvailabilityCalculator
{
    /**
     * @param  list<TimeInterval>  $workingIntervals
     * @param  list<TimeInterval>  $occupiedIntervals
     * @return list<TimeInterval>
     */
    public function calculate(
        array $workingIntervals,
        array $occupiedIntervals,
        int $durationMinutes,
        int $slotIncrementMinutes = 15,
    ): array {
        $availableIntervals = $workingIntervals;

        foreach ($occupiedIntervals as $occupiedInterval) {
            $availableIntervals = array_merge(
                ...array_map(
                    fn (TimeInterval $interval): array => $interval->subtract($occupiedInterval),
                    $availableIntervals
                )
            );
        }

        $slots = [];

        foreach ($availableIntervals as $availableInterval) {
            for (
                $startMinute = $availableInterval->startMinute;
                $startMinute + $durationMinutes <= $availableInterval->endMinute;
                $startMinute += $slotIncrementMinutes
            ) {
                $slots[] = new TimeInterval($startMinute, $startMinute + $durationMinutes);
            }
        }

        return $slots;
    }
}
