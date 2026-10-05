<?php

namespace App\Http\Controllers;

use App\Domain\Appointment\Enums\AppointmentStatus;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppointmentExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $tenant = $request->user()->tenants()->wherePivot('is_active', true)->firstOrFail();
        $status = $request->string('status')->toString();
        abort_if($status !== '' && ! in_array($status, AppointmentStatus::values(), true), 422);

        $appointments = $tenant->appointments()
            ->with(['customer', 'staff', 'service'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('start_at')
            ->orderBy('id')
            ->cursor();

        return response()->streamDownload(function () use ($appointments, $tenant): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Start', 'End', 'Service', 'Customer', 'Customer email', 'Staff', 'Status', 'Notes']);

            foreach ($appointments as $appointment) {
                fputcsv($handle, [
                    $appointment->start_at->setTimezone($tenant->timezone)->toDateString(),
                    $appointment->start_at->setTimezone($tenant->timezone)->format('H:i'),
                    $appointment->end_at->setTimezone($tenant->timezone)->format('H:i'),
                    $appointment->service_name ?: $appointment->service?->name,
                    $appointment->customer?->name,
                    $appointment->customer?->email,
                    $appointment->staff?->display_name,
                    $appointment->status->value,
                    $appointment->notes,
                ]);
            }

            fclose($handle);
        }, 'appointly-appointments-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
