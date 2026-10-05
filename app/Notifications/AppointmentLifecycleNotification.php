<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentLifecycleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly string $message,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof Customer ? ['mail'] : ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->appointment->service_name.' — appointment update')
            ->greeting('Hello '.$this->appointment->customer?->name.',')
            ->line($this->message)
            ->line('When: '.$this->appointment->start_at->setTimezone($this->appointment->tenant->timezone)->format('l, F j, Y at H:i'))
            ->action('View appointment', route('booking.lookup', $this->appointment->public_token));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'appointmentId' => $this->appointment->id,
            'customerName' => $this->appointment->customer?->name,
            'serviceName' => $this->appointment->service_name,
            'startAt' => $this->appointment->start_at?->toIso8601String(),
            'status' => $this->appointment->status->value,
            'message' => $this->message,
        ];
    }
}
