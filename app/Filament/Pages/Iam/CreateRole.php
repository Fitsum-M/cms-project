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
            'rolePermissions' => ['array'],
        ], [
            'roleName.unique' => 'This role already exists.',
        ]);

        $role = Role::create([
            'name' => $validated['roleName'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($this->rolePermissions);

        Notification::make()
            ->success()
            ->title('Role Created')
            ->body("Role '{$role->name}' has been created successfully.")
            ->send();

        $this->redirect(RolesAndPermissions::getUrl());
    }
}
