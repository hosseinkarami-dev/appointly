<?php

namespace App\Livewire\Workspace;

use App\Application\Schedule\DeleteDayOffAction;
use App\Application\Schedule\DeleteWorkingHourAction;
use App\Application\Schedule\UpsertDayOffAction;
use App\Application\Schedule\UpsertWorkingHourAction;
use App\Application\Staff\CreateStaffAction;
use App\Application\Staff\DeleteStaffAction;
use App\Application\Staff\ToggleStaffServiceAssignmentAction;
use App\Application\Staff\ToggleStaffStatusAction;
use App\Application\Staff\UpdateStaffAction;
use App\Livewire\Workspace\Concerns\InteractsWithWorkspace;
use Livewire\Component;

class Team extends Component
{
    use InteractsWithWorkspace;

    public string $staffName = '';

    public string $staffDescription = '';

    public int|string $scheduleStaffId = '';

    public int|string $scheduleWeekday = 1;

    public string $scheduleStart = '09:00';

    public string $scheduleEnd = '17:00';

    public string $dayOffDate = '';

    public string $dayOffReason = '';

    public int|string $dayOffStaffId = '';

    public int|string $editingDayOffId = '';

    public int|string $editingWorkingHourId = '';

    public int|string $editingStaffId = '';

    public function startCreatingStaff(): void
    {
        $this->cancelEditingStaff();
        $this->resetValidation();
        $this->dispatch('open-staff-modal');
    }

    public function startEditingStaff(int $staffId): void
    {
        $this->resetValidation();
        $staff = $this->tenant()->staff()->whereKey($staffId)->firstOrFail();
        $this->editingStaffId = $staff->id;
        $this->staffName = $staff->display_name;
        $this->staffDescription = $staff->description ?? '';
        $this->dispatch('open-staff-modal');
    }

    public function saveStaff(CreateStaffAction $createStaff, UpdateStaffAction $updateStaff): void
    {
        $validated = $this->validate([
            'staffName' => ['required', 'string', 'max:255'],
            'staffDescription' => ['nullable', 'string', 'max:5000'],
        ]);
        $data = [
            'name' => $validated['staffName'],
            'description' => $validated['staffDescription'] ?: null,
        ];
        $wasEditing = $this->editingStaffId !== '';

        if ($wasEditing) {
            $updateStaff->handle($this->tenant(), (int) $this->editingStaffId, $data);
        } else {
            $createStaff->handle($this->tenant(), $data);
        }

        $this->cancelEditingStaff();
        session()->flash($wasEditing ? 'staff-updated' : 'staff-created', $wasEditing ? 'Team member updated.' : 'Team member added.');
        $this->dispatch('close-staff-modal');
    }

    public function removeStaff(int $staffId, DeleteStaffAction $deleteStaff): void
    {
        if (! $deleteStaff->handle($this->tenant(), $staffId)) {
            session()->flash('staff-error', 'This teammate has appointment history or scheduled days off. Remove those schedule exceptions first, or pause the teammate to preserve existing records.');

            return;
        }

        session()->flash('staff-updated', 'Team member removed.');
    }

    public function toggleStaff(int $staffId, ToggleStaffStatusAction $toggleStaffStatus): void
    {
        $staff = $toggleStaffStatus->handle($this->tenant(), $staffId);
        session()->flash('staff-updated', $staff->is_active ? 'Team member activated.' : 'Team member paused.');
    }

    public function cancelEditingStaff(): void
    {
        $this->reset(['editingStaffId', 'staffName', 'staffDescription']);
        $this->dispatch('close-staff-modal');
    }

    public function toggleAssignment(int $staffId, int $serviceId, ToggleStaffServiceAssignmentAction $toggleAssignment): void
    {
        $toggleAssignment->handle($this->tenant(), $staffId, $serviceId);
    }

    public function saveWorkingHour(UpsertWorkingHourAction $upsertWorkingHour): void
    {
        $validated = $this->validate([
            'scheduleStaffId' => ['required', 'integer'],
            'scheduleWeekday' => ['required', 'integer', 'between:1,7'],
            'scheduleStart' => ['required', 'date_format:H:i'],
            'scheduleEnd' => ['required', 'date_format:H:i', 'after:scheduleStart'],
        ]);

        $existingSchedule = $this->tenant()->workingHours()
            ->where('staff_profile_id', $validated['scheduleStaffId'])
            ->where('weekday', $validated['scheduleWeekday'])
            ->when($this->editingWorkingHourId !== '', fn ($query) => $query->where('id', '!=', (int) $this->editingWorkingHourId))
            ->exists();

        if ($existingSchedule) {
            $this->addError('scheduleWeekday', 'This team member already has working hours for that day. Edit the existing schedule instead.');

            return;
        }

        $upsertWorkingHour->handle(
            $this->tenant(),
            $validated['scheduleStaffId'],
            $validated['scheduleWeekday'],
            $validated['scheduleStart'],
            $validated['scheduleEnd'],
            $this->editingWorkingHourId !== '' ? (int) $this->editingWorkingHourId : null,
        );

        session()->flash('schedule-saved', 'Availability updated.');
        $this->cancelEditingWorkingHour();
        $this->dispatch('close-schedule-modal');
    }

    public function startCreatingWorkingHour(?int $staffId = null): void
    {
        $this->resetValidation();
        $this->reset(['editingWorkingHourId']);
        $this->scheduleStaffId = $staffId ?? '';
        $this->scheduleWeekday = 1;
        $this->scheduleStart = '09:00';
        $this->scheduleEnd = '17:00';
        $this->dispatch('open-schedule-modal');
    }

    public function startEditingWorkingHour(int $workingHourId): void
    {
        $workingHour = $this->tenant()->workingHours()->findOrFail($workingHourId);
        $this->editingWorkingHourId = $workingHour->id;
        $this->scheduleStaffId = $workingHour->staff_profile_id;
        $this->scheduleWeekday = $workingHour->weekday;
        $this->scheduleStart = substr($workingHour->start_local_time, 0, 5);
        $this->scheduleEnd = substr($workingHour->end_local_time, 0, 5);
        $this->resetValidation();
        $this->dispatch('open-schedule-modal');
    }

    public function cancelEditingWorkingHour(): void
    {
        $this->reset(['editingWorkingHourId']);
        $this->resetValidation();
    }

    public function removeWorkingHour(int $workingHourId, DeleteWorkingHourAction $deleteWorkingHour): void
    {
        $deleteWorkingHour->handle($this->tenant(), $workingHourId);
        session()->flash('schedule-saved', 'Working hours removed.');
    }

    public function saveDayOff(UpsertDayOffAction $upsertDayOff): void
    {
        $validated = $this->validate([
            'dayOffDate' => ['required', 'date', 'after_or_equal:today'],
            'dayOffReason' => ['nullable', 'string', 'max:255'],
            'dayOffStaffId' => ['nullable', 'integer'],
        ]);

        $upsertDayOff->handle(
            $this->tenant(),
            $validated['dayOffDate'],
            $validated['dayOffReason'] ?: null,
            $this->editingDayOffId !== '' ? (int) $this->editingDayOffId : null,
            $validated['dayOffStaffId'] !== '' ? (int) $validated['dayOffStaffId'] : null,
        );

        $wasEditing = $this->editingDayOffId !== '';
        $this->cancelEditingDayOff();
        session()->flash('schedule-saved', $wasEditing ? 'Day off updated.' : 'Day off saved.');
        $this->dispatch('close-day-off-modal');
    }

    public function startEditingDayOff(int $dayOffId): void
    {
        $dayOff = $this->tenant()->daysOff()->findOrFail($dayOffId);
        $this->editingDayOffId = $dayOff->id;
        $this->dayOffDate = $dayOff->local_date->toDateString();
        $this->dayOffReason = $dayOff->reason ?? '';
        $this->dayOffStaffId = $dayOff->staff_profile_id ?? '';
        $this->resetValidation();
        $this->dispatch('open-day-off-modal');
    }

    public function startCreatingDayOff(): void
    {
        $this->cancelEditingDayOff();
        $this->dayOffDate = now($this->tenant()->timezone)->toDateString();
        $this->dispatch('open-day-off-modal');
    }

    public function cancelEditingDayOff(): void
    {
        $this->reset(['editingDayOffId', 'dayOffDate', 'dayOffReason', 'dayOffStaffId']);
    }

    public function removeDayOff(int $dayOffId, DeleteDayOffAction $deleteDayOff): void
    {
        $deleteDayOff->handle($this->tenant(), $dayOffId);

        if ((int) $this->editingDayOffId === $dayOffId) {
            $this->cancelEditingDayOff();
            $this->dispatch('close-day-off-modal');
        }

        session()->flash('schedule-saved', 'Day off removed.');
    }

    public function render(): mixed
    {
        $tenant = $this->tenant();

        return view('livewire.workspace.team', [
            'today' => now($tenant->timezone)->toDateString(),
            'staff' => $tenant->staff()->with(['services', 'workingHours' => fn ($query) => $query->where('is_active', true)->orderBy('weekday')->orderBy('start_local_time')])->withCount('appointments')->latest()->get(),
            'services' => $tenant->services()->where('is_active', true)->orderBy('name')->get(),
            'daysOff' => $tenant->daysOff()->with('staff')->whereDate('local_date', '>=', now($tenant->timezone)->toDateString())->orderBy('local_date')->limit(20)->get(),
        ]);
    }
}
