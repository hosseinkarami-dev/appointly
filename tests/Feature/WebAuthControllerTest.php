<?php

namespace Tests\Feature;

use App\Domain\Identity\Enums\MembershipRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class WebAuthControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_web_login_is_rate_limited_by_email_and_ip(): void
    {
        $credentials = ['email' => 'attempts@example.test', 'password' => 'invalid-password'];

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post('/login', $credentials)->assertSessionHasErrors('email');
        }

        $this->post('/login', $credentials)->assertStatus(429);
    }

    public function test_web_registration_is_rate_limited_by_ip(): void
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/register', [])->assertSessionHasErrors();
        }

        $this->post('/register', [])->assertStatus(429);
    }

    public function test_google_callback_creates_a_workspace_and_logs_the_user_in(): void
    {
        $googleUser = GoogleUser::fake([
            'id' => 'google-123',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/workspace/overview');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'ada@example.com',
            'google_id' => 'google-123',
        ]);
        $this->assertDatabaseHas('tenant_memberships', [
            'role' => MembershipRole::Owner->value,
            'is_active' => true,
        ]);
    }

    public function test_google_callback_links_an_existing_account_by_email(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $googleUser = GoogleUser::fake([
            'id' => 'google-456',
            'email' => 'ada@example.com',
        ]);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get('/auth/google/callback')->assertRedirect('/workspace/overview');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => 'google-456',
        ]);
        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_authenticated_workspace_uses_dashboard_route(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'name' => 'North Clinic',
            'slug' => 'north-clinic',
            'timezone' => 'UTC',
        ]);
        $tenant->users()->attach($user, [
            'role' => MembershipRole::Owner->value,
            'is_active' => true,
        ]);

        $this->actingAs($user)->get('/workspace/overview')->assertOk();
        $this->get('/admin')->assertNotFound();
    }
}
