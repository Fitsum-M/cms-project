<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Listeners\Audit\LogAuthorizationEvents;
use App\Models\User;
use App\Services\ContentLifecycleService;
use App\Services\PostService;
use App\Services\RoleAssignmentService;
use App\Services\UserLifecycleService;
use App\Support\Audit\AuditLogger;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_login_success_and_failure_are_logged_to_security_channel(): void
    {
        $user = $this->makeUser(UserRole::Author, [
            'email' => 'logger@example.com',
            'password' => Hash::make('ValidPassword1!'),
        ]);

        $messages = [];

        Log::shouldReceive('channel')
            ->with(AuditLogger::SECURITY_CHANNEL)
            ->andReturnSelf();

        Log::shouldReceive('log')
            ->andReturnUsing(function (string $level, string $message, array $context) use (&$messages): void {
                $messages[] = compact('level', 'message', 'context');
            });

        Event::dispatch(new Login('web', $user, false));
        Event::dispatch(new Failed('web', null, ['email' => 'nobody@example.com', 'password' => 'secret']));

        $this->assertTrue(collect($messages)->contains(
            fn (array $row): bool => $row['level'] === 'warning'
                && $row['message'] === 'auth.login.succeeded'
                && ($row['context']['user_id'] ?? null) === $user->id
                && ! array_key_exists('password', $row['context'])
        ));

        $this->assertTrue(collect($messages)->contains(
            fn (array $row): bool => $row['level'] === 'warning'
                && $row['message'] === 'auth.login.failed'
                && ($row['context']['email'] ?? null) === 'nobody@example.com'
                && ! array_key_exists('password', $row['context'])
        ));
    }

    public function test_permission_denial_is_logged_without_secrets(): void
    {
        $actor = $this->makeUser(UserRole::Author);
        $messages = [];

        Log::shouldReceive('channel')
            ->with(AuditLogger::SECURITY_CHANNEL)
            ->andReturnSelf();

        Log::shouldReceive('log')
            ->andReturnUsing(function (string $level, string $message, array $context) use (&$messages): void {
                $messages[] = compact('level', 'message', 'context');
            });

        $this->actingAs($actor);

        app(LogAuthorizationEvents::class)
            ->handleAuthorizationException(new AuthorizationException('Denied'));

        $this->assertCount(1, $messages);
        $this->assertSame('warning', $messages[0]['level']);
        $this->assertSame('auth.permission.denied', $messages[0]['message']);
        $this->assertSame($actor->id, $messages[0]['context']['actor_id']);
        $this->assertArrayNotHasKey('token', $messages[0]['context']);
    }

    public function test_user_role_and_status_changes_are_logged(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $author = $this->makeUser(UserRole::Author, [
            'username' => 'role_target',
            'email' => 'role_target@example.com',
        ]);

        $messages = [];

        Log::shouldReceive('channel')
            ->with(AuditLogger::SECURITY_CHANNEL)
            ->andReturnSelf();

        Log::shouldReceive('log')
            ->andReturnUsing(function (string $level, string $message, array $context) use (&$messages): void {
                $messages[] = compact('level', 'message', 'context');
            });

        app(RoleAssignmentService::class)->assign($admin, $author, UserRole::Contributor);
        app(UserLifecycleService::class)->suspendAs($admin, $author->fresh());

        $names = collect($messages)->pluck('message');

        $this->assertTrue($names->contains('user.role_changed'));
        $this->assertTrue($names->contains('user.suspended'));
        $this->assertTrue(collect($messages)->every(
            fn (array $row): bool => ! array_key_exists('password', $row['context'])
                && ! array_key_exists('invitation_token', $row['context'])
        ));
    }

    public function test_content_create_update_and_trash_are_logged_to_audit_channel(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $messages = [];

        Log::shouldReceive('channel')
            ->with(AuditLogger::CONTENT_CHANNEL)
            ->andReturnSelf();

        Log::shouldReceive('log')
            ->andReturnUsing(function (string $level, string $message, array $context) use (&$messages): void {
                $messages[] = compact('level', 'message', 'context');
            });

        $post = app(PostService::class)->create([
            'title' => 'Audit Post',
            'body' => 'Body',
            'status' => ContentStatus::Draft->value,
        ], $admin);

        app(PostService::class)->update($post, [
            'title' => 'Audit Post Updated',
        ], $admin);

        app(ContentLifecycleService::class)->trash($post->fresh());

        $names = collect($messages)->pluck('message');

        $this->assertTrue($names->contains('content.created'));
        $this->assertTrue($names->contains('content.updated'));
        $this->assertTrue($names->contains('content.trashed'));
        $this->assertTrue(collect($messages)->every(
            fn (array $row): bool => $row['level'] === 'info'
                && array_key_exists('content_id', $row['context'])
                && ! array_key_exists('password', $row['context'])
        ));
    }

    public function test_sanitize_strips_sensitive_keys(): void
    {
        $clean = app(AuditLogger::class)->sanitize([
            'email' => 'a@example.com',
            'password' => 'secret',
            'invitation_token' => 'abc',
            'nested' => [
                'token' => 'x',
                'ok' => 1,
            ],
        ]);

        $this->assertSame('a@example.com', $clean['email']);
        $this->assertArrayNotHasKey('password', $clean);
        $this->assertArrayNotHasKey('invitation_token', $clean);
        $this->assertArrayNotHasKey('token', $clean['nested']);
        $this->assertSame(1, $clean['nested']['ok']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeUser(UserRole $role, array $attributes = []): User
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
