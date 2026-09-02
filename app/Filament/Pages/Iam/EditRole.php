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

/**
 * Legacy edit page (removed in Step 1.8).
 * Role CRUD only — name + permissions. User accounts belong in UserResource.
 */
class EditRole extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Identity & Access Management';

    protected static ?string $navigationLabel = 'Edit Role';

    protected static ?string $title = 'Edit Role';

    protected static ?string $slug = 'iam/roles-legacy/{record}/edit';

    protected string $view = 'filament.pages.iam.edit-role';

    public int $roleId;

    public string $roleName = '';

    /** @var list<string> */
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
        $this->rolePermissions = $role->permissions->pluck('name')->all();
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

        $this->validate([
            'roleName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($role->id)->where('guard_name', 'web'),
            ],
            'rolePermissions' => ['array'],
        ], [
            'roleName.unique' => 'This role name is already taken.',
        ]);

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

        $this->redirect(\App\Filament\Resources\Roles\RoleResource::getUrl('index'));
    }
}
