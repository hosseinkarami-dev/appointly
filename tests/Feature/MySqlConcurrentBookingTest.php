<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\StaffProfile;
use App\Models\Tenant;
use App\Models\WorkingHour;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class MySqlConcurrentBookingTest extends TestCase
{
    public function test_only_one_concurrent_request_can_book_a_staff_interval(): void
    {
        if (! filter_var(env('APPOINTLY_MYSQL_CONCURRENCY_TESTS', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Set APPOINTLY_MYSQL_CONCURRENCY_TESTS=true for the isolated MySQL race test.');
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('The booking race test requires MySQL.');
        }

        $tenant = Tenant::create([
            'name' => 'Concurrent booking test',
            'slug' => 'concurrent-'.Str::uuid(),
            'timezone' => 'UTC',
        ]);
        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Consultation',
            'duration_minutes' => 30,
        ]);
        $staff = StaffProfile::create([
            'tenant_id' => $tenant->id,
            'display_name' => 'Concurrent staff',
        ]);
        $staff->services()->attach($service, [
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $bookingDate = CarbonImmutable::now('UTC')->addWeek()->startOfWeek()->addDays(4);
        WorkingHour::create([
            'tenant_id' => $tenant->id,
            'staff_profile_id' => $staff->id,
            'weekday' => $bookingDate->isoWeekday(),
            'start_local_time' => '09:00',
            'end_local_time' => '11:00',
        ]);

        $barrierPath = tempnam(sys_get_temp_dir(), 'appointly-booking-race-');

        if ($barrierPath === false) {
            throw new RuntimeException('Unable to create the booking race barrier.');
        }

        file_put_contents($barrierPath, 'wait');
        $workerCode = $this->workerCode($tenant->id, $service->id, $staff->id, $bookingDate->setTime(9, 0)->toIso8601String(), $barrierPath);
        $workers = [];

        try {
            for ($workerId = 1; $workerId <= 2; $workerId++) {
                $pipes = [];
                $process = proc_open(
                    [PHP_BINARY, '-r', $workerCode],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                    $pipes,
                    base_path(),
                );

                if (! is_resource($process)) {
                    throw new RuntimeException('Unable to start a concurrent booking worker.');
                }

                fclose($pipes[0]);
                $workers[] = ['process' => $process, 'pipes' => $pipes];
            }

            file_put_contents($barrierPath, 'start');
            $results = [];

            foreach ($workers as $worker) {
                $output = trim(stream_get_contents($worker['pipes'][1]));
                $error = trim(stream_get_contents($worker['pipes'][2]));
                fclose($worker['pipes'][1]);
                fclose($worker['pipes'][2]);
                $exitCode = proc_close($worker['process']);

                $this->assertSame(0, $exitCode, $error);
                $results[] = $output;
            }

            $this->assertEqualsCanonicalizing(['created', 'unavailable'], $results);
            $this->assertDatabaseCount('appointments', 1);
        } finally {
            foreach ($workers as $worker) {
                if (is_resource($worker['process'])) {
                    proc_terminate($worker['process']);
                }
            }

            if (file_exists($barrierPath)) {
                unlink($barrierPath);
            }
        }
    }

    private function workerCode(int $tenantId, int $serviceId, int $staffId, string $startAt, string $barrierPath): string
    {
        $code = <<<'PHP'
            require 'vendor/autoload.php';
            $app = require 'bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            $barrierPath = __BARRIER__;
            $deadline = microtime(true) + 15;
            while (file_get_contents($barrierPath) !== 'start' && microtime(true) < $deadline) {
                usleep(1000);
            }
            if (file_get_contents($barrierPath) !== 'start') {
                fwrite(STDERR, 'The booking race barrier timed out.');
                exit(2);
            }
            try {
                app(App\Application\Appointment\CreateAppointmentAction::class)->handle(
                    App\Models\Tenant::query()->findOrFail(__TENANT_ID__),
                    [
                        'serviceId' => __SERVICE_ID__,
                        'staffId' => __STAFF_ID__,
                        'startAt' => __START_AT__,
                        'customer' => ['name' => 'Race test customer', 'email' => 'race-'.getmypid().'@example.test'],
                    ],
                );
                echo 'created';
            } catch (App\Domain\Appointment\Exceptions\BookingUnavailable) {
                echo 'unavailable';
            }
            PHP;

        return str_replace(
            ['__BARRIER__', '__TENANT_ID__', '__SERVICE_ID__', '__STAFF_ID__', '__START_AT__'],
            [var_export($barrierPath, true), (string) $tenantId, (string) $serviceId, (string) $staffId, var_export($startAt, true)],
            $code,
        );
    }
}
