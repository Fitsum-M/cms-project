<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Auth\Login;
use App\Filament\Auth\RequestPasswordReset;
use App\Filament\Auth\ResetPassword;
use App\Models\User;
use App\Support\Auth\CmsPassword;
use Database\Seeders\RoleSeeder;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_password_reset_request_page_is_registered_and_sends_email(): void
    {
        Notification::fake();

        $user = $this->makeUser('Author', [
            'email' => 'resetme@example.com',
        ]);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm([
                'email' => $user->email,
            ])
            ->call('request')
            ->assertNotified();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_password_reset_enforces_cms_complexity_rules(): void
    {
        $user = $this->makeUser('Author', [
            'email' => 'complex@example.com',
            'password' => Hash::make('OldPassword1!'),
        ]);

        $token = Password::broker('users')->createToken($user);

        Livewire::test(ResetPassword::class, [
            'email' => $user->email,
            'token' => $token,
        ])
            ->fillForm([
                'password' => 'short',
                'passwordConfirmation' => 'short',
            ])
            ->call('resetPassword')
            ->assertHasFormErrors(['password']);

        Livewire::test(ResetPassword::class, [
            'email' => $user->email,
            'token' => $token,
        ])
            ->fillForm([
                'password' => 'ValidPassword1!',
                'passwordConfirmation' => 'ValidPassword1!',
            ])
            ->call('resetPassword');

        $this->assertTrue(Hash::check('ValidPassword1!', $user->fresh()->password));
    }

    public function test_cms_password_defaults_match_srs_complexity(): void
    {
        $rule = CmsPassword::rules();

        $this->assertFalse(validator(['password' => 'alllowercase1!'], ['password' => $rule])->passes());
        $this->assertFalse(validator(['password' => 'ALLUPPERCASE1!'], ['password' => $rule])->passes());
        $this->assertFalse(validator(['password' => 'NoNumber!!Aa'], ['password' => $rule])->passes());
        $this->assertFalse(validator(['password' => 'NoSymbol11Aa'], ['password' => $rule])->passes());
        $this->assertTrue(validator(['password' => 'ValidPassword1!'], ['password' => $rule])->passes());
    }

    public function test_profile_avatar_upload_syncs_to_spatie_media_collection(): void
    {
        Storage::fake('public');

        $user = $this->makeUser('Author');
        $file = UploadedFile::fake()->image('avatar.jpg', 120, 120);
        $path = $file->store('avatars', 'public');

        $user->forceFill(['avatar_url' => $path])->save();

        $this->assertNotNull($user->fresh()->getFirstMedia('avatar'));
        $this->assertNotNull($user->fresh()->getFilamentAvatarUrl());
    }

    public function test_session_lifetime_is_thirty_minutes(): void
    {
        $this->assertSame(30, (int) config('session.lifetime'));
    }

    public function test_login_is_rate_limited_after_five_attempts_for_fifteen_minutes(): void
    {
        $user = $this->makeUser('Author', [
            'email' => 'throttle@example.com',
            'password' => Hash::make('ValidPassword1!'),
        ]);

        RateLimiter::clear('livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1'));

        for ($i = 0; $i < Login::MAX_ATTEMPTS; $i++) {
            Livewire::test(Login::class)
                ->fillForm([
                    'email' => $user->email,
                    'password' => 'WrongPassword1!',
                    'remember' => false,
                ])
                ->call('authenticate');
        }

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'WrongPassword1!',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertNotified();

        $key = 'livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1');

        $this->assertTrue(RateLimiter::tooManyAttempts($key, Login::MAX_ATTEMPTS));
        $this->assertGreaterThan(14 * 60, RateLimiter::availableIn($key));
    }

    public function test_breezy_profile_has_avatars_enabled(): void
    {
        $this->assertTrue(filament('filament-breezy')->hasAvatars());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeUser(string $role, array $attributes = []): User
    {
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'activated_at' => now(),
            ...$attributes,
        ]);

        $user->assignSingleRole($role);

        return $user->fresh();
    }
}
