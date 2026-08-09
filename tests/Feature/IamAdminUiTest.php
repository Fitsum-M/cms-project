<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\Iam\AddNewUser;
use App\Filament\Pages\Iam\AdministratorRolePage;
use App\Filament\Pages\Iam\AllUsers;
use App\Filament\Pages\Iam\AuthorRolePage;
use App\Filament\Pages\Iam\ContributorRolePage;
use App\Filament\Pages\Iam\EditorRolePage;
use App\Filament\Pages\Iam\RolesAndPermissions;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IamAdminUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_all_users_hub_redirects_to_listing(): void
    {
        $this->actingAs($this->makeUser(UserRole::Administrator));

        Livewire::test(AllUsers::class)
            ->assertRedirect(UserResource::getUrl('index'));
    }

    public function test_add_new_user_hub_redirects_to_create(): void
    {
        $this->actingAs($this->makeUser(UserRole::Administrator));

        Livewire::test(AddNewUser::class)
            ->assertRedirect(UserResource::getUrl('create'));
    }

    public function test_administrator_can_list_and_invite_users(): void
    {
        Notification::fake();

        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertOk();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Invited Editor',
                'username' => 'invited_editor',
                'email' => 'invited@example.com',
                'bio' => 'Hello',
                'role' => UserRole::Editor->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $invited = User::query()->where('email', 'invited@example.com')->first();

        $this->assertNotNull($invited);
        $this->assertSame(UserStatus::PendingActivation, $invited->status);
        $this->assertSame(UserRole::Editor, $invited->primaryRole());
        $this->assertSame($admin->id, $invited->invited_by);

        Notification::assertSentTo($invited, UserInvitationNotification::class);
    }

    public function test_administrator_can_change_role_and_cannot_change_own_role(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $author = $this->makeUser(UserRole::Author, [
            'username' => 'author_one',
            'email' => 'author@example.com',
        ]);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $author->getRouteKey()])
            ->fillForm([
                'name' => $author->name,
                'username' => $author->username,
                'email' => $author->email,
                'bio' => null,
                'role' => UserRole::Contributor->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(UserRole::Contributor, $author->fresh()->primaryRole());

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->assertFormFieldIsDisabled('role');
    }

    public function test_administrator_can_suspend_and_soft_delete_user(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $author = $this->makeUser(UserRole::Author, [
            'username' => 'author_two',
            'email' => 'author2@example.com',
        ]);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $author->getRouteKey()])
            ->callAction('suspend');

        $this->assertSame(UserStatus::Suspended, $author->fresh()->status);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $author->getRouteKey()])
            ->callAction('reactivate');

        $this->assertSame(UserStatus::Active, $author->fresh()->status);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $author->getRouteKey()])
            ->callAction('delete');

        $this->assertSoftDeleted($author);
    }

    public function test_editor_can_view_users_but_cannot_create_or_edit_others(): void
    {
        $editor = $this->makeUser(UserRole::Editor);
        $author = $this->makeUser(UserRole::Author, [
            'username' => 'author_three',
            'email' => 'author3@example.com',
        ]);

        Livewire::actingAs($editor)
            ->test(ListUsers::class)
            ->assertOk();

        Livewire::actingAs($editor)
            ->test(ViewUser::class, ['record' => $author->getRouteKey()])
            ->assertOk();

        Livewire::actingAs($editor)
            ->test(CreateUser::class)
            ->assertForbidden();

        Livewire::actingAs($editor)
            ->test(EditUser::class, ['record' => $author->getRouteKey()])
            ->assertForbidden();

        Livewire::actingAs($editor)
            ->test(AddNewUser::class)
            ->assertForbidden();
    }

    public function test_author_and_contributor_cannot_access_iam_ui(): void
    {
        foreach ([UserRole::Author, UserRole::Contributor] as $role) {
            $user = $this->makeUser($role, [
                'username' => strtolower($role->value).'_user',
                'email' => strtolower($role->value).'@example.com',
            ]);

            Livewire::actingAs($user)
                ->test(ListUsers::class)
                ->assertForbidden();

            Livewire::actingAs($user)
                ->test(AllUsers::class)
                ->assertForbidden();

            Livewire::actingAs($user)
                ->test(RolesAndPermissions::class)
                ->assertForbidden();

            Livewire::actingAs($user)
                ->test(AdministratorRolePage::class)
                ->assertForbidden();
        }
    }

    public function test_roles_matrix_and_role_detail_pages_are_readable_by_admin(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(RolesAndPermissions::class)
            ->assertOk()
            ->assertSee('View Dashboard')
            ->assertSee('Administrator');

        foreach ([
            AdministratorRolePage::class,
            EditorRolePage::class,
            AuthorRolePage::class,
            ContributorRolePage::class,
        ] as $page) {
            Livewire::actingAs($admin)
                ->test($page)
                ->assertOk();
        }
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
