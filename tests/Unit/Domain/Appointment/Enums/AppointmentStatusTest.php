<?php

namespace Tests\Unit\Domain\Appointment\Enums;

use App\Domain\Appointment\Enums\AppointmentStatus;
use PHPUnit\Framework\TestCase;

class AppointmentStatusTest extends TestCase
{
    public function test_cancelled_appointments_do_not_block_availability(): void
    {
        self::assertFalse(AppointmentStatus::Cancelled->blocksAvailability());
    }

    public function test_active_appointment_statuses_block_availability(): void
    {
        self::assertTrue(AppointmentStatus::Pending->blocksAvailability());
        self::assertTrue(AppointmentStatus::Confirmed->blocksAvailability());
        self::assertTrue(AppointmentStatus::Completed->blocksAvailability());
    }
}
