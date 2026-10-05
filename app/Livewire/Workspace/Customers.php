<?php

namespace App\Livewire\Workspace;

use App\Livewire\Workspace\Concerns\InteractsWithWorkspace;
use Livewire\Component;

class Customers extends Component
{
    use InteractsWithWorkspace;

    public string $search = '';

    public function render(): mixed
    {
        return view('livewire.workspace.customers', [
            'customers' => $this->tenant()->customers()
                ->withCount('appointments')
                ->when($this->search !== '', function ($query): void {
                    $query->where(function ($query): void {
                        $query->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('email', 'like', '%'.$this->search.'%')
                            ->orWhere('phone', 'like', '%'.$this->search.'%');
                    });
                })
                ->orderBy('name')
                ->limit(50)
                ->get(),
        ]);
    }
}
