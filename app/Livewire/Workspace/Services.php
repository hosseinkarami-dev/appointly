<?php

namespace App\Livewire\Workspace;

use App\Application\Service\CreateServiceAction;
use App\Application\Service\DeleteServiceAction;
use App\Application\Service\ToggleServiceStatusAction;
use App\Application\Service\UpdateServiceAction;
use App\Livewire\Workspace\Concerns\InteractsWithWorkspace;
use Livewire\Component;

class Services extends Component
{
    use InteractsWithWorkspace;

    public string $serviceName = '';

    public string $serviceDescription = '';

    public int|string $serviceDuration = 30;

    public int|string $serviceBuffer = 0;

    public int|string $editingServiceId = '';

    public function startCreatingService(): void
    {
        $this->cancelEditingService();
        $this->resetValidation();
        $this->dispatch('open-service-modal');
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
        session()->flash('service-created', 'Service added to your booking page.');
        $this->dispatch('close-service-modal');
    }

    public function startEditingService(int $serviceId): void
    {
        $this->resetValidation();
        $service = $this->tenant()->services()->whereKey($serviceId)->firstOrFail();
        $this->editingServiceId = $service->id;
        $this->serviceName = $service->name;
        $this->serviceDescription = $service->description ?? '';
        $this->serviceDuration = $service->duration_minutes;
        $this->serviceBuffer = $service->buffer_minutes;
        $this->dispatch('open-service-modal');
    }

    public function updateService(UpdateServiceAction $updateService): void
    {
        $validated = $this->validate([
            'serviceName' => ['required', 'string', 'max:255'],
            'serviceDescription' => ['nullable', 'string', 'max:5000'],
            'serviceDuration' => ['required', 'integer', 'min:5', 'max:1440'],
            'serviceBuffer' => ['required', 'integer', 'min:0', 'max:240'],
        ]);
        $updateService->handle($this->tenant(), (int) $this->editingServiceId, [
            'name' => $validated['serviceName'], 'description' => $validated['serviceDescription'] ?: null,
            'durationMinutes' => $validated['serviceDuration'], 'bufferMinutes' => $validated['serviceBuffer'],
        ]);
        $this->cancelEditingService();
        session()->flash('service-updated', 'Service updated.');
        $this->dispatch('close-service-modal');
    }

    public function toggleService(int $serviceId, ToggleServiceStatusAction $toggleServiceStatus): void
    {
        $service = $toggleServiceStatus->handle($this->tenant(), $serviceId);
        session()->flash('service-updated', $service->is_active ? 'Service activated.' : 'Service paused.');
    }

    public function removeService(int $serviceId, DeleteServiceAction $deleteService): void
    {
        if (! $deleteService->handle($this->tenant(), $serviceId)) {
            session()->flash('service-error', 'This service has appointment history and can’t be deleted. Pause it instead to keep past bookings intact.');

            return;
        }

        if ((int) $this->editingServiceId === $serviceId) {
            $this->cancelEditingService();
            $this->dispatch('close-service-modal');
        }

        session()->flash('service-updated', 'Service removed.');
    }

    public function cancelEditingService(): void
    {
        $this->reset(['editingServiceId', 'serviceName', 'serviceDescription', 'serviceBuffer']);
        $this->serviceDuration = 30;
    }

    public function render(): mixed
    {
        return view('livewire.workspace.services', [
            'services' => $this->tenant()->services()->withCount('staff')->latest()->get(),
        ]);
    }
}
