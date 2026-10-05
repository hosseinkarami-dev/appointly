<?php

namespace App\Application\Appointment;

use App\Jobs\DeliverAppointmentWebhook;
use App\Models\Appointment;
use App\Notifications\AppointmentLifecycleNotification;

final class NotifyAppointmentLifecycleAction
{
    public function handle(Appointment $appointment, string $message): void
    {
        $appointment->loadMissing(['customer', 'tenant']);
        DeliverAppointmentWebhook::dispatch($appointment, 'appointment.updated')->afterCommit();
        $notification = new AppointmentLifecycleNotification($appointment, $message);

        if ($appointment->customer?->email) {
            $appointment->customer->notify($notification);
        }

        if ($appointment->tenant->notify_appointment_lifecycle) {
            $appointment->tenant->users()
                ->wherePivot('is_active', true)
                ->get()
                ->each(fn ($user) => $user->notify($notification));
        }
    }
}
