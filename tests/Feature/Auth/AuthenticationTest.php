<?php

use App\Enums\UserRole;
use App\Models\AccountActivationCode;
use App\Models\SavedAccountLogin;
use App\Models\User;
use App\Services\Auth\AccountActivationCodeManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/login')
            ->where('name', 'MarriottConnect')
            ->where('canResetPassword', true)
            ->where('status', null)
        );
});

test('login screen keeps the right panel fixed while saved account items can scroll', function () {
    $loginPageSource = file_get_contents(resource_path('js/pages/auth/login.tsx'));
    $loginFormSource = file_get_contents(resource_path('js/components/login-form.tsx'));

    expect($loginPageSource)
        ->toBeString()
        ->toContain('items-start justify-center overflow-hidden')
        ->not->toContain('items-start justify-center overflow-y-auto');

    expect($loginFormSource)
        ->toBeString()
        ->toContain('max-h-64 space-y-2 overflow-y-auto pr-1');
});

test('login screen saved account items include a hover remove action', function () {
    $loginFormSource = file_get_contents(resource_path('js/components/login-form.tsx'));

    expect($loginFormSource)
        ->toBeString()
        ->toContain('handleRemoveStoredAccount(account.email)')
        ->toContain('aria-label={`Remove ${account.email}`}')
        ->toContain('group-hover:opacity-100');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create([
        'first_name' => 'Jade',
        'name' => 'Jade Godalle',
        'role' => UserRole::REGISTRAR,
    ]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    $response->assertSessionHas('login_welcome_toast', function (array $payload): bool {
        return $payload['title'] === 'Welcome, Jade!'
            && $payload['description'] === 'Logged in as Registrar'
            && is_string($payload['key'])
            && $payload['key'] !== '';
    });
});

test('inactive users cannot authenticate', function () {
    $user = User::factory()->create([
        'is_active' => false,
    ]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('users cannot authenticate after account expiry', function () {
    $user = User::factory()->create([
        'is_active' => true,
        'access_expires_at' => now()->subMinute(),
    ]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('home'));
});

test('users can authenticate and receive a remember me cookie', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => 'on',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $response->assertCookie(app('auth')->guard()->getRecallerName());
});

test('remembered logins issue a saved account payload for the current device', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => 'on',
        'saved_account_device_id' => 'browser-device-1',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $response->assertSessionHas('saved_account_login', function (array $payload) use ($user): bool {
        return $payload['action'] === 'store'
            && $payload['account']['email'] === $user->email
            && $payload['account']['remember'] === true
            && $payload['account']['device_login']['device_id'] === 'browser-device-1'
            && is_string($payload['account']['device_login']['selector'])
            && $payload['account']['device_login']['selector'] !== ''
            && is_string($payload['account']['device_login']['token'])
            && $payload['account']['device_login']['token'] !== ''
            && is_string($payload['account']['device_login']['expires_at'])
            && is_string($payload['account']['last_used_at']);
    });

    expect(DB::table('saved_account_logins')->count())->toBe(1);
});

test('users can authenticate with a valid saved account token after logout', function () {
    $user = User::factory()->create();
    $savedAccountLogin = null;

    $loginResponse = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => 'on',
        'saved_account_device_id' => 'browser-device-2',
    ]);

    $loginResponse->assertSessionHas('saved_account_login', function (array $payload) use (&$savedAccountLogin): bool {
        $savedAccountLogin = $payload;

        return true;
    });

    expect($savedAccountLogin)->not->toBeNull();

    $this->post(route('logout'));
    $this->assertGuest();

    $response = $this->post(route('login.saved-account.store'), [
        'email' => $savedAccountLogin['account']['email'],
        'device_id' => $savedAccountLogin['account']['device_login']['device_id'],
        'selector' => $savedAccountLogin['account']['device_login']['selector'],
        'token' => $savedAccountLogin['account']['device_login']['token'],
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
    $response->assertSessionHas('saved_account_login', function (array $payload) use ($user): bool {
        return $payload['action'] === 'store'
            && $payload['account']['email'] === $user->email
            && $payload['account']['remember'] === true
            && is_string($payload['account']['device_login']['selector'])
            && $payload['account']['device_login']['selector'] !== ''
            && is_string($payload['account']['device_login']['token'])
            && $payload['account']['device_login']['token'] !== '';
    });
});

test('logging in without remember clears the saved account token for the current device', function () {
    $user = User::factory()->create();
    $savedAccountLogin = null;

    $rememberedLoginResponse = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => 'on',
        'saved_account_device_id' => 'browser-device-3',
    ]);

    $rememberedLoginResponse->assertSessionHas('saved_account_login', function (array $payload) use (&$savedAccountLogin): bool {
        $savedAccountLogin = $payload;

        return true;
    });

    $this->post(route('logout'));

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'saved_account_device_id' => 'browser-device-3',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $response->assertSessionHas('saved_account_login', function (array $payload) use ($user): bool {
        return $payload['action'] === 'forget'
            && $payload['email'] === $user->email
            && $payload['device_id'] === 'browser-device-3';
    });

    expect(DB::table('saved_account_logins')->count())->toBe(0);

    $this->post(route('logout'));

    $savedAccountResponse = $this->post(route('login.saved-account.store'), [
        'email' => $savedAccountLogin['account']['email'],
        'device_id' => $savedAccountLogin['account']['device_login']['device_id'],
        'selector' => $savedAccountLogin['account']['device_login']['selector'],
        'token' => $savedAccountLogin['account']['device_login']['token'],
    ]);

    $savedAccountResponse->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('inactive users cannot authenticate with saved account token', function () {
    $user = User::factory()->create([
        'is_active' => false,
    ]);

    $plainToken = Str::random(64);

    SavedAccountLogin::query()->create([
        'user_id' => $user->id,
        'device_id' => 'inactive-device',
        'selector' => (string) Str::uuid(),
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addDays(30),
        'last_used_at' => now(),
    ]);

    $savedAccountLogin = SavedAccountLogin::query()->first();

    $response = $this->post(route('login.saved-account.store'), [
        'email' => $user->email,
        'device_id' => 'inactive-device',
        'selector' => (string) $savedAccountLogin?->selector,
        'token' => $plainToken,
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});

test('users can authenticate once with a valid activation code when password change is required', function () {
    $user = User::factory()->create([
        'must_change_password' => true,
    ]);

    $activationCode = app(AccountActivationCodeManager::class)->issueForUser($user);
    $persistedActivationCode = AccountActivationCode::query()
        ->where('user_id', $user->id)
        ->first();

    expect($persistedActivationCode)->not->toBeNull();
    expect($persistedActivationCode?->used_at)->toBeNull();

    $firstLoginResponse = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => $activationCode,
    ]);

    $firstLoginResponse->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'))->assertRedirect(route('home'));
    $this->assertGuest();

    $secondLoginResponse = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => $activationCode,
    ]);

    $secondLoginResponse->assertSessionHasErrors('email');
    $this->assertGuest();
});
