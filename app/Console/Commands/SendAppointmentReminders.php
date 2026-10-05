<?php

namespace App\Console\Commands;

use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Notifications\AppointmentLifecycleNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-appointment-reminders')]
#[Description('Send one-time reminders for confirmed appointments starting in about 24 hours')]
class SendAppointmentReminders extends Command
{
    public function handle(): int
    {
        $windowStart = CarbonImmutable::now()->addHours(23)->startOfMinute();
        $windowEnd = $windowStart->addHours(2);
        $sent = 0;

        Appointment::query()
            ->with(['customer', 'tenant'])
            ->where('status', AppointmentStatus::Confirmed)
            ->whereHas('tenant', fn ($query) => $query->where('reminders_enabled', true))
            ->whereNull('reminder_sent_at')
            ->whereBetween('start_at', [$windowStart, $windowEnd])
            ->orderBy('id')
            ->eachById(function (Appointment $appointment) use (&$sent): void {
                if ($appointment->customer?->email === null) {
                    $appointment->update(['reminder_sent_at' => now()]);

                    return;
                }

                $appointment->customer->notify(new AppointmentLifecycleNotification(
                    $appointment,
                    'This is a reminder that your appointment is coming up tomorrow.',
                ));
                $appointment->update(['reminder_sent_at' => now()]);
                $sent++;
            });

        $this->info("Sent {$sent} appointment reminder(s).");

        return self::SUCCESS;
    }
}
