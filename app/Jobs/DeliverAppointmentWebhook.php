<?php

namespace App\Jobs;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DeliverAppointmentWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly Appointment $appointment, public readonly string $event) {}

    public function handle(): void
    {
        $appointment = $this->appointment->loadMissing(['tenant', 'customer', 'staff', 'service']);
        $tenant = $appointment->tenant;

        if (blank($tenant->webhook_url) || blank($tenant->webhook_secret)) {
            return;
        }

        $payload = [
            'event' => $this->event,
            'sentAt' => now()->toIso8601String(),
            'appointment' => [
                'id' => $appointment->id,
                'publicToken' => $appointment->public_token,
                'status' => $appointment->status->value,
                'service' => $appointment->service_name,
                'customer' => $appointment->customer?->name,
                'startAt' => $appointment->start_at?->toIso8601String(),
            ],
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $body, (string) $tenant->webhook_secret);

        /** @var PendingRequest $request */
        $request = Http::asJson()->acceptJson()->timeout(10)->withHeaders([
            'X-Appointly-Event' => $this->event,
            'X-Appointly-Signature' => 'sha256='.$signature,
        ]);
        $request->post($tenant->webhook_url, $payload)->throw();
    }
}
