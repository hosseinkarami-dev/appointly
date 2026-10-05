<?php

namespace App\Http\Controllers;

use App\Domain\Identity\Enums\MembershipRole;
use App\Http\Requests\WebRegisterRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;

class WebAuthController extends Controller
{
    public function create(): mixed
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those credentials could not be verified.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('workspace.dashboard'))->with('welcome', 'Welcome back.');
    }

    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            /** @var GoogleUser $googleUser */
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors(['email' => 'Google sign-in could not be completed. Please try again.']);
        }

        if (! $googleUser->getEmail()) {
            return redirect()->route('login')->withErrors(['email' => 'Google did not provide an email address for this account.']);
        }

        [$user, $created] = DB::transaction(function () use ($googleUser): array {
            $user = User::query()
                ->where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if ($user === null) {
                $user = User::create([
                    'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Google user',
                    'email' => $googleUser->getEmail(),
                    'password' => Str::random(64),
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();

                $tenant = Tenant::create([
                    'name' => ($user->name ?: 'My').' workspace',
                    'slug' => $this->uniqueTenantSlug(($user->name ?: 'my').' workspace'),
                    'timezone' => config('app.timezone', 'UTC'),
                ]);
                $tenant->users()->attach($user, ['role' => MembershipRole::Owner->value, 'is_active' => true]);

                return [$user, true];
            }

            $user->forceFill([
                'google_id' => $user->google_id ?: $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar() ?: $user->avatar_url,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();

            return [$user, false];
        });

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('workspace.dashboard'))->with(
            'welcome',
            $created ? 'Your Google workspace is ready.' : 'Welcome back.',
        );
    }

    public function register(): mixed
    {
        return view('auth.register');
    }

    public function createWorkspace(WebRegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        [$user, $tenant] = DB::transaction(function () use ($validated): array {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
            $tenant = Tenant::create([
                'name' => $validated['businessName'],
                'slug' => $this->uniqueTenantSlug($validated['businessName']),
                'timezone' => $validated['timezone'],
            ]);
            $tenant->users()->attach($user, ['role' => MembershipRole::Owner->value, 'is_active' => true]);

            return [$user, $tenant];
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('workspace.dashboard')->with('welcome', 'Your workspace is ready.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function uniqueTenantSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'business';
        $slug = $baseSlug;
        $suffix = 2;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
