<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantQuery = $request->user()
            ->tenants()
            ->wherePivot('is_active', true);
        $requestedTenantId = $request->header('X-Tenant-Id');

        $tenant = $requestedTenantId === null
            ? $tenantQuery->first()
            : $tenantQuery->whereKey($requestedTenantId)->first();

        abort_if($tenant === null, 403, 'You do not have access to this business.');

        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
