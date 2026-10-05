<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppointmentCalendarController extends Controller
{
    public function __invoke(Request $request, int $appointmentId): Response
    {
        $tenant = $request->user()->tenants()->wherePivot('is_active', true)->firstOrFail();
        $appointment = $tenant->appointments()->with(['customer', 'service', 'staff'])->findOrFail($appointmentId);
        $timezone = new \DateTimeZone($tenant->timezone);
        $start = $appointment->start_at->setTimezone($timezone)->format('Ymd\\THis');
        $end = $appointment->end_at->setTimezone($timezone)->format('Ymd\\THis');
        $summary = $this->escape($appointment->service_name ?: $appointment->service?->name ?: 'Appointment');
        $description = $this->escape($appointment->notes ?: 'Appointment with '.$appointment->staff?->display_name);
        $location = $this->escape($tenant->name);
        $uid = 'appointment-'.$appointment->id.'@appointly';

        $content = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Appointly//Appointments//EN',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.now('UTC')->format('Ymd\\THis\\Z'),
            'DTSTART;TZID='.$tenant->timezone.':'.$start,
            'DTEND;TZID='.$tenant->timezone.':'.$end,
            'SUMMARY:'.$summary,
            'DESCRIPTION:'.$description,
            'LOCATION:'.$location,
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="appointly-appointment-'.$appointment->id.'.ics"',
        ]);
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r", "\n"], ['\\\\', '\\;', '\\,', '', '\\n'], $value);
    }
}
