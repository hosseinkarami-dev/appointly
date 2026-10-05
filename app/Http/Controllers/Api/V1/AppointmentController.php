<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Appointment\CreateAppointmentAction;
use App\Application\Appointment\TransitionAppointmentAction;
use App\Domain\Appointment\Enums\AppointmentStatus;
use App\Domain\Appointment\Exceptions\BookingUnavailable;
use App\Domain\Appointment\Exceptions\InvalidAppointmentTransition;
use App\Domain\Identity\Enums\MembershipRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CancelAppointmentRequest;
use App\Http\Requests\Api\V1\CreateAppointmentRequest;
use App\Http\Resources\Api\V1\AppointmentResource;
use App\Models\Appointment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function publicShow(string $token): AppointmentResource
    {
        $appointment = Appointment::query()
            ->with('customer')
            ->where('public_token', $token)
            ->firstOrFail();

        return new AppointmentResource($appointment);
    }

    public function index(Request $request): mixed
    {
        $tenant = $this->tenant($request);
        $query = $this->visibleAppointments($request, $tenant)
            ->with('customer')
            ->orderByDesc('start_at')
            ->orderByDesc('id');

        $query->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')));
        $query->when($request->filled('staffId'), fn ($builder) => $builder->where('staff_profile_id', $request->integer('staffId')));
        $query->when($request->filled('from'), fn ($builder) => $builder->whereDate('local_date', '>=', $request->date('from')));
        $query->when($request->filled('to'), fn ($builder) => $builder->whereDate('local_date', '<=', $request->date('to')));

        return AppointmentResource::collection($query->paginate(min($request->integer('perPage', 20), 100)));
    }

    public function show(Request $request, int $appointment): AppointmentResource
    {
        return new AppointmentResource(
            $this->visibleAppointments($request, $this->tenant($request))
                ->with('customer')
                ->findOrFail($appointment)
        );
    }

    public function store(
        CreateAppointmentRequest $request,
        CreateAppointmentAction $createAppointment,
        ?Tenant $tenant = null,
    ): AppointmentResource|JsonResponse {
        try {
            return new AppointmentResource($createAppointment->handle(
                $tenant ?? $this->tenant($request),
                $request->validated()
            ));
        } catch (BookingUnavailable $exception) {
            return response()->json([
                'error' => [
                    'code' => 'appointment_conflict',
                    'message' => $exception->getMessage(),
                    'requestId' => $request->attributes->get('requestId'),
                ],
            ], 409);
        }
    }

    public function confirm(
        Request $request,
        int $appointment,
        TransitionAppointmentAction $transition,
    ): AppointmentResource|JsonResponse {
        return $this->transition($request, $appointment, AppointmentStatus::Confirmed, $transition);
    }

    public function cancel(
        CancelAppointmentRequest $request,
        int $appointment,
        TransitionAppointmentAction $transition,
    ): AppointmentResource|JsonResponse {
        return $this->transition(
            $request,
            $appointment,
            AppointmentStatus::Cancelled,
            $transition,
            $request->validated()['reason'] ?? null
        );
    }

    public function complete(
        Request $request,
        int $appointment,
        TransitionAppointmentAction $transition,
    ): AppointmentResource|JsonResponse {
        return $this->transition($request, $appointment, AppointmentStatus::Completed, $transition);
    }

    private function transition(
        Request $request,
        int $appointment,
        AppointmentStatus $target,
        TransitionAppointmentAction $transition,
        ?string $reason = null,
    ): AppointmentResource|JsonResponse {
        try {
            return new AppointmentResource($transition->handle(
                $this->tenant($request),
                $appointment,
                $target,
                $request->user()?->id,
                $reason
            ));
        } catch (InvalidAppointmentTransition $exception) {
            return response()->json([
                'error' => [
                    'code' => 'invalid_appointment_transition',
                    'message' => $exception->getMessage(),
                    'requestId' => $request->attributes->get('requestId'),
                ],
            ], 409);
        }
    }

    private function tenant(Request $request): Tenant
    {
        return $request->attributes->get('tenant') ?? $request->route('tenant');
    }

    /** @return HasMany<Appointment, Tenant> */
    private function visibleAppointments(Request $request, Tenant $tenant): HasMany
    {
        $query = $tenant->appointments();
        $membership = $tenant->users()->whereKey($request->user()->id)->first()?->pivot;

        if ($membership?->role === MembershipRole::Staff->value) {
            $staffId = $tenant->staff()->where('user_id', $request->user()->id)->value('id');
            $query->where('staff_profile_id', $staffId ?? 0);
        }

        return $query;
    }
}
