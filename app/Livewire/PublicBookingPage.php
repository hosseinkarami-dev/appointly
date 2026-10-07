<?php

namespace App\Livewire;

use App\Application\Appointment\CreateAppointmentAction;
use App\Application\Availability\GetAvailableSlotsAction;
use App\Domain\Appointment\Exceptions\BookingUnavailable;
use App\Domain\Appointment\Exceptions\PendingBookingExists;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PublicBookingPage extends Component
{
    public Tenant $tenant;

    public string $serviceId = '';

    public string $staffId = '';

    public string $date = '';

    #[Locked]
    public string $deviceTimezone = '';

    public string $selectedStartAt = '';

    public string $customerName = '';

    public string $customerEmail = '';

    public string $customerPhone = '';

    public string $website = '';

    public array $availableSlots = [];

    public bool $booked = false;

    public string $bookedAppointmentToken = '';

    public function mount(Tenant $tenant): void
    {
        abort_unless($tenant->is_active, 404);
        $this->tenant = $tenant;
        $this->date = now($tenant->timezone)->addDay()->toDateString();

        $service = $tenant->services()->where('services.is_active', true)->orderBy('services.name')->first();
        $staff = $service?->staff()
            ->where('staff_profiles.is_active', true)
            ->wherePivot('is_active', true)
            ->whereHas('workingHours', fn ($query) => $query->where('is_active', true))
            ->orderBy('staff_profiles.display_name')
            ->first();
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
        $this->resetErrorBag('date');

        $selectedDate = $this->selectedCalendarDate();

        if ($selectedDate === null) {
            $this->addError('date', 'Choose a valid date within this booking window.');
            $this->availableSlots = [];

            return;
        }

        $today = CarbonImmutable::now($this->resolvedDeviceTimezone())->startOfDay();
        $lastBookableDate = $this->lastBookableDate($this->resolvedDeviceTimezone());

        if ($selectedDate->lt($today) || $selectedDate->gt($lastBookableDate)) {
            $this->addError('date', 'Choose a date within this booking window.');
            $this->availableSlots = [];

            return;
        }

        $this->loadSlots(app(GetAvailableSlotsAction::class));
    }

    public function setDeviceTimezone(string $timezone): void
    {
        if (! in_array($timezone, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC), true)) {
            return;
        }

        if ($timezone === $this->deviceTimezone) {
            return;
        }

        $this->deviceTimezone = $timezone;
        $this->date = CarbonImmutable::now($timezone)->addDay()->toDateString();
        $this->selectedStartAt = '';
        $this->resetErrorBag('date');
        $this->loadSlots(app(GetAvailableSlotsAction::class));
    }

    public function loadSlots(GetAvailableSlotsAction $getAvailableSlots): void
    {
        if ($this->serviceId === '' || $this->staffId === '' || $this->date === '') {
            $this->availableSlots = [];

            return;
        }

        try {
            $this->availableSlots = $this->slotsForDeviceDate($getAvailableSlots, $this->date);
        } catch (\Throwable) {
            $this->availableSlots = [];
        }
    }

    public function book(CreateAppointmentAction $createAppointment): void
    {
        if (filled($this->website)) {
            return;
        }

        $ipRateLimitKey = 'public-booking:'.$this->tenant->id.':'.request()->ip();

        if (RateLimiter::tooManyAttempts($ipRateLimitKey, 5)) {
            throw ValidationException::withMessages([
                'bookingRateLimit' => 'Too many booking attempts. Please wait a minute and try again.',
            ]);
        }

        RateLimiter::hit($ipRateLimitKey, 60);

        $this->validate([
            'serviceId' => ['required', 'integer'],
            'staffId' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'selectedStartAt' => ['required', 'date'],
            'customerName' => ['required', 'string', 'max:255'],
            'customerEmail' => ['required', 'email', 'max:255'],
            'customerPhone' => ['nullable', 'string', 'max:50'],
            'website' => ['prohibited'],
        ]);

        $selectedDeviceDate = CarbonImmutable::parse($this->selectedStartAt)
            ->setTimezone($this->resolvedDeviceTimezone())
            ->startOfDay();
        $todayInDeviceTimezone = CarbonImmutable::now($this->resolvedDeviceTimezone())->startOfDay();

        if ($selectedDeviceDate->lt($todayInDeviceTimezone) || $selectedDeviceDate->gt($this->lastBookableDate($this->resolvedDeviceTimezone()))) {
            throw ValidationException::withMessages([
                'selectedStartAt' => 'Choose a time within the next '.$this->bookingHorizonDays().' days.',
            ]);
        }

        $emailRateLimitKey = 'public-booking-email:'.$this->tenant->id.':'.hash('sha256', mb_strtolower(trim($this->customerEmail)).'|'.$this->serviceId.'|'.$this->staffId);

        if (RateLimiter::tooManyAttempts($emailRateLimitKey, 5)) {
            throw ValidationException::withMessages([
                'bookingRateLimit' => 'Too many booking attempts for these details. Please try again later.',
            ]);
        }

        RateLimiter::hit($emailRateLimitKey, 3600);

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
        } catch (PendingBookingExists $exception) {
            throw ValidationException::withMessages(['customerEmail' => $exception->getMessage()]);
        } catch (BookingUnavailable $exception) {
            throw ValidationException::withMessages(['selectedStartAt' => $exception->getMessage()]);
        }

        $this->booked = true;
        $this->bookedAppointmentToken = (string) $appointment->public_token;
        $this->availableSlots = [];
    }

    public function render(): mixed
    {
        $staffQuery = $this->tenant->staff()
            ->where('staff_profiles.is_active', true)
            ->whereHas('workingHours', fn ($query) => $query->where('is_active', true));

        if ($this->serviceId !== '') {
            $staffQuery->whereHas('services', function ($query): void {
                $query->whereKey($this->serviceId)->where('staff_services.is_active', true);
            });
        }

        return view('livewire.public-booking-page', [
            'services' => $this->tenant->services()->where('is_active', true)->orderBy('name')->get(),
            'staff' => $staffQuery->orderBy('display_name')->get(),
            'calendarDays' => $this->calendarDays(),
            'availableSlotCount' => collect($this->availableSlots)->where('available', true)->count(),
            'deviceTimezone' => $this->deviceTimezone,
            'bookingHorizonDays' => $this->bookingHorizonDays(),
        ]);
    }

    /** @return list<array{date: string, day: string, month: string, number: string, available: int, selected: bool}> */
    private function calendarDays(): array
    {
        $today = CarbonImmutable::now($this->resolvedDeviceTimezone())->startOfDay();
        $dayCount = $this->bookingHorizonDays() + 1;

        $days = [];

        for ($offset = 0; $offset < $dayCount; $offset++) {
            $day = $today->addDays($offset);
            $available = 0;

            if ($this->serviceId !== '' && $this->staffId !== '') {
                try {
                    $available = collect($this->slotsForDeviceDate(
                        app(GetAvailableSlotsAction::class),
                        $day->toDateString(),
                    ))->where('available', true)->count();
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

    private function selectedCalendarDate(): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $this->date)) {
            return null;
        }

        try {
            $selectedDate = CarbonImmutable::parse($this->date, $this->resolvedDeviceTimezone())->startOfDay();
        } catch (InvalidFormatException) {
            return null;
        }

        return $selectedDate->toDateString() === $this->date ? $selectedDate : null;
    }

    /** @return list<array{staffId: int, startAt: string, endAt: string}|array{staffId: int, startAt: string, endAt: string, available: bool}> */
    private function slotsForDeviceDate(GetAvailableSlotsAction $getAvailableSlots, string $date): array
    {
        $deviceTimezone = $this->resolvedDeviceTimezone();
        $deviceDate = CarbonImmutable::parse($date, $deviceTimezone)->startOfDay();
        $firstBusinessDate = $deviceDate->utc()->setTimezone($this->tenant->timezone)->startOfDay();
        $lastBusinessDate = $deviceDate->addDay()->subSecond()->utc()->setTimezone($this->tenant->timezone)->startOfDay();
        $latestBusinessDate = $this->lastBookableDate($this->tenant->timezone);
        $slots = [];

        for ($businessDate = $firstBusinessDate; $businessDate->lessThanOrEqualTo($lastBusinessDate); $businessDate = $businessDate->addDay()) {
            $businessDateSlots = $getAvailableSlots->handleForBookingPage(
                $this->tenant,
                (int) $this->serviceId,
                (int) $this->staffId,
                $businessDate->toDateString(),
            );

            foreach ($businessDateSlots as $slot) {
                $startAt = CarbonImmutable::parse($slot['startAt']);
                $businessLocalDate = $startAt->setTimezone($this->tenant->timezone)->startOfDay();

                if ($startAt->isPast() || $businessLocalDate->gt($latestBusinessDate)) {
                    $slot['available'] = false;
                }

                if ($startAt->setTimezone($deviceTimezone)->toDateString() === $date) {
                    $slots[] = $slot;
                }
            }
        }

        return $slots;
    }

    private function resolvedDeviceTimezone(): string
    {
        return $this->deviceTimezone !== '' ? $this->deviceTimezone : $this->tenant->timezone;
    }

    private function bookingHorizonDays(): int
    {
        return min(30, max(0, $this->tenant->booking_horizon_days));
    }

    private function lastBookableDate(string $timezone): CarbonImmutable
    {
        return CarbonImmutable::now($timezone)->startOfDay()->addDays($this->bookingHorizonDays());
    }
}
