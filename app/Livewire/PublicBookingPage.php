<?php

namespace App\Livewire;

use App\Application\Appointment\CreateAppointmentAction;
use App\Application\Availability\GetAvailableSlotsAction;
use App\Domain\Appointment\Exceptions\BookingUnavailable;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PublicBookingPage extends Component
{
    public Tenant $tenant;

    public string $serviceId = '';

    public string $staffId = '';

    public string $date = '';

    public string $selectedStartAt = '';

    public string $customerName = '';

    public string $customerEmail = '';

    public string $customerPhone = '';

    public array $availableSlots = [];

    public bool $booked = false;

    public string $bookedAppointmentToken = '';

    public function mount(Tenant $tenant): void
    {
        abort_unless($tenant->is_active, 404);
        $this->tenant = $tenant;
        $this->date = now($tenant->timezone)->addDay()->toDateString();

        $service = $tenant->services()->where('services.is_active', true)->orderBy('services.name')->first();
        $staff = $service?->staff()->where('staff_profiles.is_active', true)->wherePivot('is_active', true)->orderBy('staff_profiles.display_name')->first();
        $this->serviceId = $service?->id ?? '';
        $this->staffId = $staff?->id ?? '';

        if ($this->serviceId !== '' && $this->staffId !== '') {
            $this->loadSlots(app(GetAvailableSlotsAction::class));
        }
    }

    public function updatedServiceId(mixed $value = null): void
    {
        $this->staffId = '';
        $this->selectedStartAt = '';
        $this->loadSlots(app(GetAvailableSlotsAction::class));
    }

    public function updatedStaffId(mixed $value = null): void
    {
        $this->selectedStartAt = '';
        $this->loadSlots(app(GetAvailableSlotsAction::class));
    }

    public function updatedDate(mixed $value = null): void
    {
        $this->selectedStartAt = '';
        $this->loadSlots(app(GetAvailableSlotsAction::class));
    }

    public function loadSlots(GetAvailableSlotsAction $getAvailableSlots): void
    {
        if ($this->serviceId === '' || $this->staffId === '' || $this->date === '') {
            $this->availableSlots = [];

            return;
        }

        try {
            $this->availableSlots = $getAvailableSlots->handle(
                $this->tenant,
                (int) $this->serviceId,
                (int) $this->staffId,
                $this->date
            );
        } catch (\Throwable) {
            $this->availableSlots = [];
        }
    }

    public function book(CreateAppointmentAction $createAppointment): void
    {
        $this->validate([
            'serviceId' => ['required', 'integer'],
            'staffId' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'selectedStartAt' => ['required', 'date'],
            'customerName' => ['required', 'string', 'max:255'],
            'customerEmail' => ['required', 'email', 'max:255'],
            'customerPhone' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $appointment = $createAppointment->handle($this->tenant, [
                'serviceId' => (int) $this->serviceId,
                'staffId' => (int) $this->staffId,
                'startAt' => $this->selectedStartAt,
                'customer' => [
                    'name' => $this->customerName,
                    'email' => $this->customerEmail,
                    'phone' => $this->customerPhone ?: null,
                ],
            ]);
        } catch (BookingUnavailable $exception) {
            throw ValidationException::withMessages(['selectedStartAt' => $exception->getMessage()]);
        }

        $this->booked = true;
        $this->bookedAppointmentToken = (string) $appointment->public_token;
        $this->availableSlots = [];
    }

    public function render(): mixed
    {
        $staffQuery = $this->tenant->staff()->where('is_active', true);

        if ($this->serviceId !== '') {
            $staffQuery->whereHas('services', function ($query): void {
                $query->whereKey($this->serviceId)->where('staff_services.is_active', true);
            });
        }

        return view('livewire.public-booking-page', [
            'services' => $this->tenant->services()->where('is_active', true)->orderBy('name')->get(),
            'staff' => $staffQuery->orderBy('display_name')->get(),
            'calendarDays' => $this->calendarDays(),
        ]);
    }

    /** @return list<array{date: string, day: string, month: string, number: string, available: int, selected: bool}> */
    private function calendarDays(): array
    {
        $start = CarbonImmutable::now($this->tenant->timezone)->startOfDay();
        $days = [];

        for ($offset = 0; $offset < min(21, $this->tenant->booking_horizon_days + 1); $offset++) {
            $day = $start->addDays($offset);
            $available = 0;

            if ($this->serviceId !== '' && $this->staffId !== '') {
                try {
                    $available = count(app(GetAvailableSlotsAction::class)->handle(
                        $this->tenant,
                        (int) $this->serviceId,
                        (int) $this->staffId,
                        $day->toDateString(),
                    ));
                } catch (\Throwable) {
                    $available = 0;
                }
            }

            $days[] = [
                'date' => $day->toDateString(),
                'day' => $day->isToday() ? 'Today' : $day->format('D'),
                'month' => $day->format('M'),
                'number' => $day->format('j'),
                'available' => $available,
                'selected' => $this->date === $day->toDateString(),
            ];
        }

        return $days;
    }

    /** @return list<array{staffId: int, startAt: string, endAt: string}> */
    public function getSlotsProperty(): array
    {
        return $this->availableSlots;
    }
}
