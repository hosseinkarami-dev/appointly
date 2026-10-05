<?php

namespace App\Domain\Appointment\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class InvalidAppointmentTransition extends Exception implements ShouldntReport
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("An appointment cannot transition from {$from} to {$to}.");
    }
}
