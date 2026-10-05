<?php

namespace App\Livewire\Workspace\Concerns;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

trait InteractsWithWorkspace
{
    protected function tenant(): Tenant
    {
        return Auth::user()->tenants()->wherePivot('is_active', true)->firstOrFail();
    }
}
