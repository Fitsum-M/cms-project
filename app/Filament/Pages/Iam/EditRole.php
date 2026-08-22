<?php

namespace App\Filament\Pages\Iam;

use App\Enums\Permission;
use App\Enums\UserRole;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use UnitEnum;

class EditRole extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Identity & Access Management';

    protected static ?string $navigationLabel = 'Edit Role';

    protected static ?string $title = 'Edit Role';

    protected static ?string $slug = 'iam/roles/{record}/edit';

    protected string $view = 'filament.pages.iam.edit-role';

    public int $roleId;
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

    public function mount(int $record): void
    {
        $role = Role::findById($record, 'web');
        $this->roleId = $role->id;
        $this->roleName = $role->name;
        $this->rolePermissions = $role->permissions->pluck('name')->toArray();
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

        $role = Role::findById($this->roleId, 'web');

        $rules = [
            'roleName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($role->id)->where('guard_name', 'web'),
            ],
            'rolePermissions' => ['array'],
        ];

        $this->validate($rules, [
            'roleName.unique' => 'This role name is already taken.',
        ]);

        // Do not allow renaming the Administrator role
        if ($role->name === UserRole::Administrator->value && $this->roleName !== UserRole::Administrator->value) {
            Notification::make()
                ->danger()
                ->title('Renaming Blocked')
                ->body('The Administrator role name is system-protected and cannot be renamed.')
                ->send();
            return;
        }

        $role->name = $this->roleName;
        $role->save();

        $role->syncPermissions($this->rolePermissions);

        Notification::make()
            ->success()
            ->title('Role Saved')
            ->body("Role '{$role->name}' has been updated successfully.")
            ->send();

        $this->redirect(RolesAndPermissions::getUrl());
    }
}
