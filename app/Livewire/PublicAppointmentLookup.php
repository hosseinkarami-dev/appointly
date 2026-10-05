<?php

namespace App\Livewire;

use App\Application\Appointment\CancelPublicAppointmentAction;
use App\Domain\Appointment\Exceptions\BookingUnavailable;
use App\Domain\Appointment\Exceptions\InvalidAppointmentTransition;
use App\Models\Appointment;
use Livewire\Component;

class PublicAppointmentLookup extends Component
{
    public string $token = '';

    public string $cancellationReason = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        abort_unless(Appointment::query()->where('public_token', $token)->exists(), 404);
    }

    public function cancel(CancelPublicAppointmentAction $cancelPublicAppointment): void
    {
        try {
            $cancelPublicAppointment->handle($this->token, $this->cancellationReason ?: null);
            $this->cancellationReason = '';
            session()->flash('appointment-cancelled', 'Your appointment has been cancelled.');
        } catch (InvalidAppointmentTransition|BookingUnavailable $exception) {
            session()->flash('appointment-error', $exception->getMessage());
        }
    }

    public function render(): mixed
    {
        return view('livewire.public-appointment-lookup', [
            'appointment' => Appointment::query()
                ->with(['tenant', 'customer', 'service', 'staff'])
                ->where('public_token', $this->token)
                ->firstOrFail(),
        ]);
    }
}
