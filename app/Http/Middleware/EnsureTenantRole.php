<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $tenant = $request->attributes->get('tenant');
        $role = $tenant?->pivot?->role;

        abort_unless($role !== null && in_array($role, $roles, true), 403);

        return $next($request);
    }
}
