<?php

use App\Http\Controllers\AppointmentCalendarController;
use App\Http\Controllers\AppointmentExportController;
use App\Http\Controllers\WebAuthController;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/health/ready', function () {
    try {
        DB::connection()->getPdo();

        return response()->json(['status' => 'ok', 'checks' => ['database' => 'ok', 'queue' => config('queue.default')]]);
    } catch (Throwable $exception) {
        report($exception);

        return response()->json(['status' => 'degraded', 'checks' => ['database' => 'failed']], 503);
    }
})->name('health.ready');

Route::get('/login', [WebAuthController::class, 'create'])->name('login');
Route::post('/login', [WebAuthController::class, 'store']);
Route::get('/auth/google/redirect', [WebAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [WebAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::get('/register', [WebAuthController::class, 'register'])->name('register');
Route::post('/register', [WebAuthController::class, 'createWorkspace']);
Route::post('/logout', [WebAuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function (): void {
    Route::prefix('workspace')->group(function (): void {
        Route::get('/overview', fn () => view('workspace.overview'))->name('workspace.dashboard');
        Route::redirect('/', '/workspace/overview');
        Route::get('/calendar', fn () => view('workspace.calendar'))->name('workspace.calendar');
        Route::get('/appointments', fn () => view('workspace.appointments'))->name('workspace.appointments');
        Route::get('/appointments/export', AppointmentExportController::class)->name('workspace.appointments.export');
        Route::get('/appointments/{appointmentId}/calendar', AppointmentCalendarController::class)->name('workspace.appointments.calendar');
        Route::get('/appointments/{appointmentId}', fn (int $appointmentId) => view('workspace.appointment-detail', ['appointmentId' => $appointmentId]))->name('workspace.appointments.show');
        Route::get('/services', fn () => view('workspace.services'))->name('workspace.services');
        Route::get('/team', fn () => view('workspace.team'))->name('workspace.team');
        Route::get('/customers', fn () => view('workspace.customers'))->name('workspace.customers');
        Route::get('/customers/{customerId}', fn (int $customerId) => view('workspace.customer-profile', ['customerId' => $customerId]))->name('workspace.customers.show');
        Route::get('/reports', fn () => view('workspace.reports'))->name('workspace.reports');
        Route::get('/settings', fn () => view('workspace.settings'))->name('workspace.settings');
    });

    Route::redirect('/dashboard', '/workspace/workflow/overview')->name('dashboard');
    Route::redirect('/calendar', '/workspace/calendar');
    Route::redirect('/appointments/export', '/workspace/appointments/export');
    Route::get('/appointments/{appointmentId}/calendar', fn (int $appointmentId) => redirect()->route('workspace.appointments.calendar', $appointmentId));
    Route::get('/appointments/{appointmentId}', fn (int $appointmentId) => redirect()->route('workspace.appointments.show', $appointmentId));
    Route::redirect('/appointments', '/workspace/appointments');
    Route::redirect('/services', '/workspace/services');
    Route::redirect('/team', '/workspace/team');
    Route::get('/customers/{customerId}', fn (int $customerId) => redirect()->route('workspace.customers.show', $customerId));
    Route::redirect('/customers', '/workspace/customers');
    Route::redirect('/reports', '/workspace/reports');
    Route::redirect('/settings', '/workspace/settings');
});
Route::get('/booking/{token}', fn (string $token) => view('appointment-lookup', ['token' => $token]))->name('booking.lookup');
Route::get('/booking/{token}/reschedule', fn (string $token) => view('appointment-reschedule', ['token' => $token]))->name('booking.reschedule');
Route::get('/business/{tenant:slug}', function (Tenant $tenant) {
    return view('business', ['tenant' => $tenant]);
})->name('business.show');
Route::get('/embed/{tenant:slug}', function (Tenant $tenant) {
    return view('embed', ['tenant' => $tenant]);
})->name('booking.embed');
