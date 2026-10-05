<?php

namespace App\Livewire;

use App\Application\Appointment\TransitionAppointmentAction;
use App\Application\Schedule\UpsertDayOffAction;
use App\Application\Schedule\UpsertWorkingHourAction;
use App\Application\Service\CreateServiceAction;
use App\Application\Staff\CreateStaffAction;
use App\Application\Staff\ToggleStaffServiceAssignmentAction;
use App\Application\Tenant\UpdateTenantProfileAction;
use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AdminDashboard extends Component
{
    public string $serviceName = '';

    public string $serviceDescription = '';

    public int|string $serviceDuration = 30;

    public int|string $serviceBuffer = 0;

    public string $staffName = '';

    public string $staffDescription = '';

    public int|string $scheduleStaffId = '';

    public int|string $scheduleWeekday = 1;

    public string $scheduleStart = '09:00';

    public string $scheduleEnd = '17:00';

    public string $dayOffDate = '';

    public string $dayOffReason = '';

    public string $status = '';

    public string $tenantName = '';

    public string $tenantSlug = '';

    public string $tenantTimezone = '';

    public string $tenantEmail = '';

    public string $tenantPhone = '';

    public int|string $bookingHorizonDays = 60;

    public string $appointmentStatus = 'all';

    public string $appointmentDate = '';

    public function mount(): void
    {
        $this->appointmentDate = CarbonImmutable::now($this->tenant()->timezone)->toDateString();
    }

    public function previousAppointmentDay(): void
    {
        $this->appointmentDate = CarbonImmutable::createFromFormat('!Y-m-d', $this->appointmentDate)
            ->subDay()
            ->toDateString();
    }

    public function nextAppointmentDay(): void
    {
        $this->appointmentDate = CarbonImmutable::createFromFormat('!Y-m-d', $this->appointmentDate)
            ->addDay()
            ->toDateString();
    }

    public function showTodayAppointments(): void
    {
        $this->appointmentDate = CarbonImmutable::now($this->tenant()->timezone)->toDateString();
    }

    public function updatedAppointmentDate(): void
    {
        $this->validateOnly('appointmentDate', [
            'appointmentDate' => ['required', 'date_format:Y-m-d'],
        ]);
    }

    public function toggleAssignment(int $staffId, int $serviceId, ToggleStaffServiceAssignmentAction $toggleAssignment): void
    {
        $toggleAssignment->handle($this->tenant(), $staffId, $serviceId);
    }

    public function updateTenantProfile(UpdateTenantProfileAction $updateTenantProfile): void
    {
        $tenant = $this->tenant();
        $validated = $this->validate([
            'tenantName' => ['required', 'string', 'max:255'],
            'tenantSlug' => ['required', 'alpha_dash', 'max:255', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'tenantTimezone' => ['required', 'timezone'],
            'tenantEmail' => ['nullable', 'email', 'max:255'],
            'tenantPhone' => ['nullable', 'string', 'max:50'],
            'bookingHorizonDays' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $updateTenantProfile->handle($tenant, [
            'name' => $validated['tenantName'],
            'slug' => $validated['tenantSlug'],
            'timezone' => $validated['tenantTimezone'],
            'email' => $validated['tenantEmail'] ?: null,
            'phone' => $validated['tenantPhone'] ?: null,
            'bookingHorizonDays' => $validated['bookingHorizonDays'],
        ]);

        session()->flash('profile-saved', 'Workspace details updated.');
    }

    public function createService(CreateServiceAction $createService): void
    {
        $validated = $this->validate([
            'serviceName' => ['required', 'string', 'max:255'],
            'serviceDescription' => ['nullable', 'string', 'max:5000'],
            'serviceDuration' => ['required', 'integer', 'min:5', 'max:1440'],
            'serviceBuffer' => ['required', 'integer', 'min:0', 'max:240'],
        ]);

        $createService->handle($this->tenant(), [
            'name' => $validated['serviceName'],
            'description' => $validated['serviceDescription'] ?: null,
            'durationMinutes' => $validated['serviceDuration'],
            'bufferMinutes' => $validated['serviceBuffer'],
        ]);

        $this->reset(['serviceName', 'serviceDescription', 'serviceBuffer']);
        $this->serviceDuration = 30;
        $this->dispatch('workspace-updated');
    }

    public function createStaff(CreateStaffAction $createStaff): void
    {
        $validated = $this->validate([
            'staffName' => ['required', 'string', 'max:255'],
            'staffDescription' => ['nullable', 'string', 'max:5000'],
        ]);

        $createStaff->handle($this->tenant(), [
            'name' => $validated['staffName'],
            'description' => $validated['staffDescription'] ?: null,
        ]);

        $this->reset(['staffName', 'staffDescription']);
        $this->dispatch('workspace-updated');
    }

    public function updateStatus(int $appointmentId, string $status, TransitionAppointmentAction $transition): void
    {
        abort_unless(in_array($status, AppointmentStatus::values(), true), 422);

        $transition->handle($this->tenant(), $appointmentId, AppointmentStatus::from($status), Auth::id());
    }

    public function markNotificationsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function createWorkingHour(UpsertWorkingHourAction $upsertWorkingHour): void
    {
        $validated = $this->validate([
            'scheduleStaffId' => ['required', 'integer'],
            'scheduleWeekday' => ['required', 'integer', 'between:1,7'],
            'scheduleStart' => ['required', 'date_format:H:i'],
            'scheduleEnd' => ['required', 'date_format:H:i', 'after:scheduleStart'],
        ]);

        $upsertWorkingHour->handle(
            $this->tenant(),
            $validated['scheduleStaffId'],
            $validated['scheduleWeekday'],
            $validated['scheduleStart'],
            $validated['scheduleEnd'],
        );

        $this->reset(['scheduleStart', 'scheduleEnd']);
        $this->scheduleStart = '09:00';
        $this->scheduleEnd = '17:00';
    }

    public function createDayOff(UpsertDayOffAction $upsertDayOff): void
    {
        $validated = $this->validate([
            'dayOffDate' => ['required', 'date_format:Y-m-d'],
            'dayOffReason' => ['nullable', 'string', 'max:255'],
        ]);

        $upsertDayOff->handle(
            $this->tenant(),
            $validated['dayOffDate'],
            $validated['dayOffReason'] ?: null,
        );

        $this->reset(['dayOffDate', 'dayOffReason']);
    }

    public function render(): mixed
    {
        $tenant = $this->tenant();
        $this->tenantName = $this->tenantName ?: $tenant->name;
        $this->tenantSlug = $this->tenantSlug ?: $tenant->slug;
        $this->tenantTimezone = $this->tenantTimezone ?: $tenant->timezone;
        $this->tenantEmail = $this->tenantEmail ?: ($tenant->email ?? '');
        $this->tenantPhone = $this->tenantPhone ?: ($tenant->phone ?? '');
        $this->bookingHorizonDays = $this->bookingHorizonDays ?: $tenant->booking_horizon_days;
        $appointments = $tenant->appointments()
            ->with(['service', 'staff'])
            ->whereDate('local_date', $this->appointmentDate)
            ->when($this->appointmentStatus !== 'all', fn ($query) => $query->where('status', $this->appointmentStatus))
            ->orderBy('start_at')
            ->orderBy('id')
            ->get();

        return view('livewire.admin-dashboard', [
            'tenant' => $tenant,
            'services' => $tenant->services()->latest()->get(),
            'staff' => $tenant->staff()->with('services')->latest()->get(),
            'workingHours' => $tenant->workingHours()->with('staff')->orderBy('staff_profile_id')->orderBy('weekday')->get(),
            'daysOff' => $tenant->daysOff()->latest('local_date')->limit(10)->get(),
            'appointments' => $appointments,
            'notifications' => Auth::user()->notifications()->latest()->limit(5)->get(),
        ]);
    }

    private function tenant(): Tenant
    {
        return Auth::user()->tenants()->wherePivot('is_active', true)->firstOrFail();
    }
}
