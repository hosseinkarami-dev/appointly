<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Identity\Enums\MembershipRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_creates_an_owner_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'businessName' => 'North Clinic',
            'timezone' => 'Asia/Tehran',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.tenant.slug', 'north-clinic')
            ->assertJsonPath('data.tenant.role', MembershipRole::Owner->value)
            ->assertJsonStructure(['data', 'token']);
        $this->assertDatabaseHas('tenant_memberships', [
            'role' => MembershipRole::Owner->value,
            'is_active' => true,
        ]);
    }

    public function test_login_returns_401_for_invalid_credentials(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'businessName' => 'North Clinic',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ada@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('error.code', 'invalid_credentials');
    }

    public function test_authenticated_user_can_view_memberships_and_revoke_their_token(): void
    {
        $registration = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'businessName' => 'North Clinic',
        ]);
        $token = $registration->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.tenants.0.role', MembershipRole::Owner->value);

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.loggedOut', true);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }
}
