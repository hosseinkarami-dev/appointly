<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request): mixed
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        return CustomerResource::collection(
            $tenant->customers()->orderBy('name')->paginate(min($request->integer('perPage', 20), 100))
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->tenant($request)->customers()->create($this->attributes($request->validated()));

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    public function update(UpdateCustomerRequest $request, int $customer): CustomerResource|JsonResponse
    {
        $model = $this->tenant($request)->customers()->whereKey($customer)->first();

        if ($model === null) {
            return response()->json(['error' => ['code' => 'customer_not_found', 'message' => 'The requested customer was not found.', 'requestId' => $request->attributes->get('requestId')]], 404);
        }

        $model->update($this->attributes($request->validated(), true));

        return new CustomerResource($model->refresh());
    }

    private function tenant(Request $request): Tenant
    {
        return $request->attributes->get('tenant');
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function attributes(array $data, bool $partial = false): array
    {
        $attributes = $data;

        if (! $partial || array_key_exists('email', $data)) {
            $attributes['normalized_email'] = isset($data['email']) && $data['email'] !== null ? Str::lower(trim($data['email'])) : null;
        }

        if (! $partial || array_key_exists('phone', $data)) {
            $attributes['normalized_phone'] = isset($data['phone']) && $data['phone'] !== null ? preg_replace('/\D+/', '', $data['phone']) : null;
        }

        if (array_key_exists('isActive', $attributes)) {
            $attributes['is_active'] = $attributes['isActive'];
            unset($attributes['isActive']);
        }

        return $attributes;
    }
}
