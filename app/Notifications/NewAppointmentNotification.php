<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewAppointmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Appointment $appointment) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'appointmentId' => $this->appointment->id,
            'customerName' => $this->appointment->customer?->name,
            'serviceName' => $this->appointment->service_name,
            'startAt' => $this->appointment->start_at?->toIso8601String(),
            'message' => 'A new appointment request needs your attention.',
        ];
    }
}
