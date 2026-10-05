<?php

namespace App\Livewire\Workspace;

use App\Application\Customer\UpdateCustomerAction;
use App\Livewire\Workspace\Concerns\InteractsWithWorkspace;
use App\Models\Customer;
use Livewire\Component;

class CustomerProfile extends Component
{
    use InteractsWithWorkspace;

    public int $customerId;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $notes = '';

    public bool $editing = false;

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function startEditing(): void
    {
        $customer = $this->customer();
        $this->name = $customer->name;
        $this->email = $customer->email ?? '';
        $this->phone = $customer->phone ?? '';
        $this->notes = $customer->notes ?? '';
        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->editing = false;
        $this->resetValidation();
    }

    public function updateCustomer(UpdateCustomerAction $updateCustomer): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $updateCustomer->handle($this->tenant(), $this->customerId, $validated);
        $this->editing = false;
        session()->flash('customer-updated', 'Customer details updated.');
    }

    public function render(): mixed
    {
        $customer = $this->tenant()->customers()
            ->with(['appointments' => fn ($query) => $query->with(['service', 'staff'])->orderByDesc('start_at')])
            ->findOrFail($this->customerId);

        $auditLogs = $this->tenant()->auditLogs()
            ->with('actor')
            ->where('auditable_type', Customer::class)
            ->where('auditable_id', $customer->id)
            ->latest()
            ->limit(20)
            ->get();

        return view('livewire.workspace.customer-profile', compact('customer', 'auditLogs'));
    }

    private function customer(): Customer
    {
        return $this->tenant()->customers()->findOrFail($this->customerId);
    }
}
