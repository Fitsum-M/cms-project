<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;
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

    public function test_user_resource_owns_all_users_navigation(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $this->actingAs($admin);
        $this->assertTrue(UserResource::shouldRegisterNavigation());
        $this->assertSame('All Users', UserResource::getNavigationLabel());

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertOk();
    }

    public function test_user_resource_owns_add_new_user_navigation(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $this->actingAs($admin);

        $labels = collect(UserResource::getNavigationItems())
            ->map(fn ($item) => $item->getLabel())
            ->all();

        $this->assertContains('All Users', $labels);
        $this->assertContains('Add New User', $labels);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->assertOk();
    }

    public function test_administrator_can_create_user_with_password_and_they_can_login(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $password = 'pass';

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertOk();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Created Editor',
                'username' => 'created_editor',
                'email' => 'created@example.com',
                'password' => $password,
                'passwordConfirmation' => $password,
                'bio' => 'Hello',
                'role' => UserRole::Editor->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::query()->where('email', 'created@example.com')->first();

        $this->assertNotNull($created);
        $this->assertSame(UserStatus::Active, $created->status);
        $this->assertSame(UserRole::Editor, $created->primaryRole());
        $this->assertSame($admin->id, $created->invited_by);
        $this->assertTrue(Hash::check($password, $created->password));
        $this->assertTrue($created->canAccessPanel(filament()->getPanel('admin')));

        $this->assertTrue(auth()->attempt([
            'email' => 'created@example.com',
            'password' => $password,
        ]));
    }

    public function test_administrator_can_update_existing_user_password(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $author = $this->makeUser(UserRole::Author, [
            'username' => 'author_password',
            'email' => 'author_password@example.com',
        ]);

        $newPassword = 'new';

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $author->getRouteKey()])
            ->fillForm([
                'name' => $author->name,
                'username' => $author->username,
                'email' => $author->email,
                'bio' => null,
                'role' => UserRole::Author->value,
                'password' => $newPassword,
                'passwordConfirmation' => $newPassword,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check($newPassword, $author->fresh()->password));
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

        $this->actingAs($editor);
        $labels = collect(UserResource::getNavigationItems())
            ->map(fn ($item) => $item->getLabel())
            ->all();
        $this->assertNotContains('Add New User', $labels);
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
                ->test(ListRoles::class)
                ->assertForbidden();

            $administratorRole = Role::findByName('Administrator', 'web');

            Livewire::actingAs($user)
                ->test(ViewRole::class, ['record' => $administratorRole->getRouteKey()])
                ->assertForbidden();
        }
    }

    public function test_roles_list_and_role_view_pages_are_readable_by_admin(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(ListRoles::class)
            ->assertOk()
            ->assertCanSeeTableRecords(Role::query()->where('guard_name', 'web')->get());

        foreach (['Administrator', 'Editor', 'Author', 'Contributor'] as $roleName) {
            $role = Role::findByName($roleName, 'web');

            Livewire::actingAs($admin)
                ->test(ViewRole::class, ['record' => $role->getRouteKey()])
                ->assertOk();
        }
    }

    public function test_administrator_can_crud_custom_roles_and_permissions(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        // 1. Create a role via RoleResource
        Livewire::actingAs($admin)
            ->test(CreateRole::class)
            ->fillForm([
                'name' => 'Moderator',
                'permissionGroups' => [
                    'dashboard' => [Permission::DashboardView->value],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'Moderator',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseMissing('users', [
            'name' => 'Moderator',
        ]);

        $role = Role::findByName('Moderator', 'web');

        // 2. Edit the role name & permissions only (user accounts stay in UserResource)
        Livewire::actingAs($admin)
            ->test(EditRole::class, ['record' => $role->getRouteKey()])
            ->assertFormSet([
                'name' => 'Moderator',
            ])
            ->fillForm([
                'name' => 'Super Moderator',
                'permissionGroups' => [
                    'posts' => [
                        Permission::PostsCreate->value,
                        Permission::PostsEditOwn->value,
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Super Moderator',
        ]);

        $this->assertDatabaseMissing('users', [
            'name' => 'Super Moderator',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'supermod@example.com',
        ]);

        $this->assertTrue($role->fresh()->hasPermissionTo(Permission::PostsCreate->value));
        $this->assertTrue($role->fresh()->hasPermissionTo(Permission::PostsEditOwn->value));
        $this->assertFalse($role->fresh()->hasPermissionTo(Permission::PagesCreate->value));

        // 3. Delete the role from the Resource list table
        Livewire::actingAs($admin)
            ->test(ListRoles::class)
            ->callTableAction('delete', $role)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
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
