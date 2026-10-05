<?php

namespace App\Livewire\Workspace;

use App\Application\Appointment\TransitionAppointmentAction;
use App\Application\Appointment\UpdateAppointmentAction;
use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Livewire\Workspace\Concerns\InteractsWithWorkspace;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AppointmentDetail extends Component
{
    use InteractsWithWorkspace;

    public int $appointmentId;

    public string $cancellationReason = '';

    public string $notes = '';

    public bool $editingNotes = false;

    public function mount(int $appointmentId): void
    {
        $this->appointmentId = $appointmentId;
    }

    public function updateStatus(string $status, TransitionAppointmentAction $transition): void
    {
        $target = AppointmentStatus::tryFrom($status);
        abort_unless($target instanceof AppointmentStatus, 422);

        $transition->handle(
            $this->tenant(),
            $this->appointmentId,
            $target,
            Auth::id(),
            $target === AppointmentStatus::Cancelled ? ($this->cancellationReason ?: null) : null,
        );

        $this->cancellationReason = '';
        session()->flash('appointment-updated', 'Appointment status updated.');
    }

    public function startEditingNotes(): void
    {
        $this->notes = $this->appointment()->notes ?? '';
        $this->editingNotes = true;
    }

    public function cancelEditingNotes(): void
    {
        $this->editingNotes = false;
        $this->resetValidation();
    }

    public function updateNotes(UpdateAppointmentAction $updateAppointment): void
    {
        $validated = $this->validate(['notes' => ['nullable', 'string', 'max:5000']]);
        $updateAppointment->handle($this->tenant(), $this->appointmentId, $validated);
        $this->editingNotes = false;
        session()->flash('appointment-updated', 'Appointment notes updated.');
    }

    public function render(): mixed
    {
        $appointment = $this->appointment();
        $auditLogs = $this->tenant()->auditLogs()
            ->with('actor')
            ->where('auditable_type', Appointment::class)
            ->where('auditable_id', $appointment->id)
            ->latest()
            ->limit(20)
            ->get();

        return view('livewire.workspace.appointment-detail', compact('appointment', 'auditLogs'));
    }

    private function appointment(): Appointment
    {
        return $this->tenant()->appointments()
            ->with(['service', 'staff', 'customer'])
            ->findOrFail($this->appointmentId);
    }
}
