<?php

namespace App\Application\Tenant;

use App\Models\Tenant;

final class UpdateTenantProfileAction
{
    /** @param array{name: string, slug: string, timezone: string, email: ?string, phone: ?string, bookingHorizonDays: int, cancellationCutoffHours?: int, notifyNewAppointments?: bool, notifyAppointmentLifecycle?: bool, remindersEnabled?: bool, webhookUrl?: ?string, webhookSecret?: ?string} $data */
    public function handle(Tenant $tenant, array $data): Tenant
    {
        $tenant->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'timezone' => $data['timezone'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'booking_horizon_days' => $data['bookingHorizonDays'],
            'cancellation_cutoff_hours' => $data['cancellationCutoffHours'] ?? $tenant->cancellation_cutoff_hours ?? 24,
            'notify_new_appointments' => $data['notifyNewAppointments'] ?? $tenant->notify_new_appointments ?? true,
            'notify_appointment_lifecycle' => $data['notifyAppointmentLifecycle'] ?? $tenant->notify_appointment_lifecycle ?? true,
            'reminders_enabled' => $data['remindersEnabled'] ?? $tenant->reminders_enabled ?? true,
            'webhook_url' => $data['webhookUrl'] ?? $tenant->webhook_url,
            'webhook_secret' => $data['webhookSecret'] ?? $tenant->webhook_secret,
        ]);

        return $tenant->refresh();
    }
}
