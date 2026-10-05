<?php

namespace App\Domain\Appointment\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function blocksAvailability(): bool
    {
        return $this !== self::Cancelled;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => in_array($target, [self::Cancelled, self::Completed, self::NoShow], true),
            self::Cancelled, self::Completed, self::NoShow => false,
        };
    }
}
