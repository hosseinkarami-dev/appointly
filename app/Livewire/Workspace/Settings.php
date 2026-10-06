<?php

namespace App\Livewire\Workspace;

use App\Application\Tenant\UpdateTenantProfileAction;
use App\Domain\Identity\Enums\MembershipRole;
use App\Livewire\Workspace\Concerns\InteractsWithWorkspace;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Settings extends Component
{
    use InteractsWithWorkspace;

    public string $tenantName = '';

    public string $tenantSlug = '';

    public string $tenantTimezone = '';

    public string $tenantEmail = '';

    public string $tenantPhone = '';

    public int|string $bookingHorizonDays = 60;

    public int|string $cancellationCutoffHours = 24;

    public bool $notifyNewAppointments = true;

    public bool $notifyAppointmentLifecycle = true;

    public bool $remindersEnabled = true;

    public string $webhookUrl = '';

    public string $webhookSecret = '';

    public ?string $newApiToken = null;

    public function mount(): void
    {
        $this->authorizeOwner();
        $tenant = $this->tenant();
        $this->tenantName = $tenant->name;
        $this->tenantSlug = $tenant->slug;
        $this->tenantTimezone = $tenant->timezone;
        $this->tenantEmail = $tenant->email ?? '';
        $this->tenantPhone = $tenant->phone ?? '';
        $this->bookingHorizonDays = $tenant->booking_horizon_days;
        $this->cancellationCutoffHours = $tenant->cancellation_cutoff_hours;
        $this->notifyNewAppointments = $tenant->notify_new_appointments;
        $this->notifyAppointmentLifecycle = $tenant->notify_appointment_lifecycle;
        $this->remindersEnabled = $tenant->reminders_enabled;
        $this->webhookUrl = $tenant->webhook_url ?? '';
        $this->webhookSecret = $tenant->webhook_secret ?? '';
    }

    public function updateTenantProfile(UpdateTenantProfileAction $updateTenantProfile): void
    {
        $this->authorizeOwner();
        $tenant = $this->tenant();
        $validated = $this->validate([
            'tenantName' => ['required', 'string', 'max:255'],
            'tenantSlug' => ['required', 'alpha_dash', 'max:255', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'tenantTimezone' => ['required', 'timezone'],
            'tenantEmail' => ['nullable', 'email', 'max:255'],
            'tenantPhone' => ['nullable', 'string', 'max:50'],
            'bookingHorizonDays' => ['required', 'integer', 'min:1', 'max:365'],
            'cancellationCutoffHours' => ['required', 'integer', 'min:0', 'max:168'],
            'notifyNewAppointments' => ['boolean'],
            'notifyAppointmentLifecycle' => ['boolean'],
            'remindersEnabled' => ['boolean'],
            'webhookUrl' => ['nullable', 'url', 'max:255'],
            'webhookSecret' => ['nullable', 'string', 'max:128'],
        ]);

        $updateTenantProfile->handle($tenant, [
            'name' => $validated['tenantName'],
            'slug' => $validated['tenantSlug'],
            'timezone' => $validated['tenantTimezone'],
            'email' => $validated['tenantEmail'] ?: null,
            'phone' => $validated['tenantPhone'] ?: null,
            'bookingHorizonDays' => $validated['bookingHorizonDays'],
            'cancellationCutoffHours' => $validated['cancellationCutoffHours'],
            'notifyNewAppointments' => $validated['notifyNewAppointments'],
            'notifyAppointmentLifecycle' => $validated['notifyAppointmentLifecycle'],
            'remindersEnabled' => $validated['remindersEnabled'],
            'webhookUrl' => $validated['webhookUrl'] ?: null,
            'webhookSecret' => $validated['webhookSecret'] ?: null,
        ]);

        session()->flash('profile-saved', 'Workspace details updated.');
    }

    public function generateWebhookSecret(): void
    {
        $this->authorizeOwner();
        $this->webhookSecret = Str::random(64);
    }

    public function createApiToken(): void
    {
        $this->authorizeOwner();
        $tenant = $this->tenant();
        $this->newApiToken = auth()->user()->createToken('tenant:'.$tenant->id.':'.now()->format('Y-m-d H:i:s'), ['tenant:'.$tenant->id])->plainTextToken;
        session()->flash('token-created', 'API key created. Copy and store it now.');
    }

    public function revokeApiToken(int $tokenId): void
    {
        $this->authorizeOwner();
        auth()->user()->tokens()->whereKey($tokenId)->where('name', 'like', 'tenant:'.$this->tenant()->id.':%')->delete();
        session()->flash('token-revoked', 'API key revoked.');
    }

    public function render(): mixed
    {
        $tenant = $this->tenant();

        return view('livewire.workspace.settings', [
            'tenant' => $tenant,
            'apiTokens' => auth()->user()->tokens()->where('name', 'like', 'tenant:'.$tenant->id.':%')->latest()->get(),
        ]);
    }

    private function authorizeOwner(): void
    {
        abort_unless(
            $this->tenant()->users()->whereKey(auth()->id())->wherePivot('role', MembershipRole::Owner->value)->exists(),
            403,
        );
    }
}
