<?php

namespace App\Filament\Pages\Iam;

use App\Enums\Permission;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use UnitEnum;

class CreateRole extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Identity & Access Management';

    protected static ?string $navigationLabel = 'Create Role';

    protected static ?string $title = 'Create Role';

    protected static ?string $slug = 'iam/roles/create';

    protected string $view = 'filament.pages.iam.create-role';

    public string $roleName = '';
    public string $email = '';
    public string $password = 'password';
    public array $rolePermissions = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::UsersEditRole->value);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->rolePermissions = [Permission::DashboardView->value];
    }

    public function updatedRoleName(string $value): void
    {
        $slugged = strtolower(str_replace(' ', '_', $value));
        if ($this->email === '' || $this->email === null || $this->email === strtolower(str_replace(' ', '_', $this->roleName ?? '')) . '@example.com') {
            $this->email = $slugged . '@example.com';
        }
    }

    /**
     * @return array<string, list<Permission>>
     */
    public function getPermissionsGrouped(): array
    {
        $grouped = [];

        foreach (Permission::cases() as $permission) {
            $grouped[$permission->group()][] = $permission;
        }

        return $grouped;
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can(Permission::UsersEditRole->value), 403);

        $validated = $this->validate([
            'roleName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'rolePermissions' => ['array'],
        ], [
            'roleName.unique' => 'This role already exists.',
            'email.unique' => 'This email is already taken.',
        ]);

        // 1. Create the role
        $role = Role::create([
            'name' => $validated['roleName'],
            'guard_name' => 'web',
        ]);

        // 2. Sync permissions
        $role->syncPermissions($this->rolePermissions);

        // 3. Create the user associated with this role
        $username = strtolower(str_replace(' ', '_', $validated['roleName']));
        $originalUsername = $username;
        $counter = 1;
        while (\App\Models\User::where('username', $username)->exists()) {
            $username = $originalUsername . '_' . $counter;
            $counter++;
        }

        $user = \App\Models\User::create([
            'name' => $validated['roleName'],
            'username' => $username,
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'status' => \App\Enums\UserStatus::Active,
            'activated_at' => now(),
        ]);

        $user->assignSingleRole($role->name);

        Notification::make()
            ->success()
            ->title('Role Created')
            ->body("Role '{$role->name}' and its associated user account have been created successfully.")
            ->send();

        $this->redirect(RolesAndPermissions::getUrl());
    }
}
