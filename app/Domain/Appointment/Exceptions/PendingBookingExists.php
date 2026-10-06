<?php

namespace App\Domain\Appointment\Exceptions;

class PendingBookingExists extends BookingUnavailable
{
    public function __construct()
    {
        parent::__construct('You already have a booking request for this service with this team member. Wait for confirmation before requesting another.');
    }
}
