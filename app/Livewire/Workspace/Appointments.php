<?php

namespace App\Livewire\Workspace;

use App\Livewire\Workspace\Concerns\InteractsWithWorkspace;
use Livewire\Component;

class Appointments extends Component
{
    use InteractsWithWorkspace;

    public string $status = 'all';

    public function render(): mixed
    {
        return view('livewire.workspace.appointments', [
            'appointments' => $this->tenant()->appointments()
                ->with(['service', 'staff', 'customer'])
                ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
                ->orderByDesc('start_at')
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
        ]);
    }
}
