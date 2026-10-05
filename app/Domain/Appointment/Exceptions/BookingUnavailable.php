<?php

namespace App\Domain\Appointment\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class BookingUnavailable extends Exception implements ShouldntReport
{
    public function __construct(string $message = 'The selected appointment time is no longer available.')
    {
        parent::__construct($message);
    }
}
