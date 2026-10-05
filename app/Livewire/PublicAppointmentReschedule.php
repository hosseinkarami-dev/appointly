<?php

namespace App\Livewire;

use App\Application\Appointment\ReschedulePublicAppointmentAction;
use App\Application\Availability\GetAvailableSlotsAction;
use App\Domain\Appointment\Exceptions\BookingUnavailable;
use App\Models\Appointment;
use Livewire\Component;

class PublicAppointmentReschedule extends Component
{
    public string $token = '';

    public string $date = '';

    public string $selectedStartAt = '';

    public array $availableSlots = [];

    public function mount(string $token): void
    {
        $this->token = $token;
        $appointment = $this->appointment();
        abort_unless(in_array($appointment->status->value, ['pending', 'confirmed'], true), 404);
        $this->date = $appointment->local_date->toDateString();
        $this->loadSlots(app(GetAvailableSlotsAction::class));
    }

    public function updatedDate(GetAvailableSlotsAction $getAvailableSlots): void
    {
        $this->selectedStartAt = '';
        $this->loadSlots($getAvailableSlots);
    }

    public function loadSlots(GetAvailableSlotsAction $getAvailableSlots): void
    {
        $appointment = $this->appointment();
        $this->availableSlots = $getAvailableSlots->handle(
            $appointment->tenant,
            $appointment->service_id,
            $appointment->staff_profile_id,
            $this->date,
        );
    }

    public function reschedule(ReschedulePublicAppointmentAction $reschedule): void
    {
        $this->validate(['selectedStartAt' => ['required', 'date']]);

        try {
            $reschedule->handle($this->token, $this->selectedStartAt);
            session()->flash('appointment-rescheduled', 'Your appointment has been rescheduled.');
        } catch (BookingUnavailable $exception) {
            $this->addError('selectedStartAt', $exception->getMessage());
        }
    }

    public function render(): mixed
    {
        return view('livewire.public-appointment-reschedule', ['appointment' => $this->appointment()]);
    }

    private function appointment(): Appointment
    {
        return Appointment::query()
            ->with(['tenant', 'service', 'staff'])
            ->where('public_token', $this->token)
            ->firstOrFail();
    }

    /** @return list<array{staffId: int, startAt: string, endAt: string}> */
    public function getSlotsProperty(): array
    {
        return $this->availableSlots;
    }
}
