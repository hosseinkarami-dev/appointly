<?php

namespace App\Domain\Schedule\ValueObjects;

use InvalidArgumentException;

final readonly class TimeInterval
{
    public function __construct(
        public int $startMinute,
        public int $endMinute,
    ) {
        if ($startMinute < 0 || $endMinute > 1440 || $startMinute >= $endMinute) {
            throw new InvalidArgumentException('A time interval must be ordered and fit within one day.');
        }
    }

    public function containsDuration(int $durationMinutes): bool
    {
        return $this->endMinute - $this->startMinute >= $durationMinutes;
    }

    public function overlaps(self $other): bool
    {
        return $this->startMinute < $other->endMinute
            && $this->endMinute > $other->startMinute;
    }

    public function subtract(self $blocked): array
    {
        if (! $this->overlaps($blocked)) {
            return [$this];
        }

        $remaining = [];

        if ($this->startMinute < $blocked->startMinute) {
            $remaining[] = new self($this->startMinute, min($this->endMinute, $blocked->startMinute));
        }

        if ($this->endMinute > $blocked->endMinute) {
            $remaining[] = new self(max($this->startMinute, $blocked->endMinute), $this->endMinute);
        }

        return $remaining;
    }
}
