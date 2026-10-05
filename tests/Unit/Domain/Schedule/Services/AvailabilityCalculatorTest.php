<?php

namespace Tests\Unit\Domain\Schedule\Services;

use App\Domain\Schedule\Services\AvailabilityCalculator;
use App\Domain\Schedule\ValueObjects\TimeInterval;
use PHPUnit\Framework\TestCase;

class AvailabilityCalculatorTest extends TestCase
{
    public function test_it_removes_occupied_time_and_returns_duration_sized_slots(): void
    {
        $calculator = new AvailabilityCalculator;

        $slots = $calculator->calculate(
            [new TimeInterval(540, 720)],
            [new TimeInterval(600, 630)],
            30,
            30,
        );

        self::assertSame(
            [[540, 570], [570, 600], [630, 660], [660, 690], [690, 720]],
            array_map(
                fn (TimeInterval $slot): array => [$slot->startMinute, $slot->endMinute],
                $slots
            )
        );
    }

    public function test_adjacent_appointments_do_not_overlap(): void
    {
        $calculator = new AvailabilityCalculator;

        $slots = $calculator->calculate(
            [new TimeInterval(540, 600)],
            [new TimeInterval(570, 600)],
            30,
        );

        self::assertSame([[540, 570]], array_map(
            fn (TimeInterval $slot): array => [$slot->startMinute, $slot->endMinute],
            $slots
        ));
    }
}
