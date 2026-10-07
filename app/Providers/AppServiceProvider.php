<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('public-api', function (Request $request): Limit {
            $tenant = $request->route('tenant');
            $tenantKey = is_object($tenant) ? ($tenant->id ?? 'unknown') : (string) $tenant;

            return Limit::perMinute(60)->by($tenantKey.'|'.$request->ip());
        });

        RateLimiter::for('public-booking', function (Request $request): Limit {
            $tenant = $request->route('tenant');
            $tenantKey = is_object($tenant) ? ($tenant->id ?? 'unknown') : (string) $tenant;

            return Limit::perMinute(10)->by($tenantKey.'|'.$request->ip());
        });

        RateLimiter::for('auth-attempts', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip().'|'.$request->string('email')->lower())
        );

        RateLimiter::for('web-login', function (Request $request): array {
            $email = mb_strtolower(trim((string) $request->input('email', '')));

            return [
                Limit::perMinute(10)->by($request->ip().'|'.$email),
                Limit::perMinute(60)->by('web-login-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('registration', function (Request $request): array {
            return [
                Limit::perMinute(3)->by('registration-minute:'.$request->ip()),
                Limit::perHour(20)->by('registration-hour:'.$request->ip()),
            ];
        });
    }
}
