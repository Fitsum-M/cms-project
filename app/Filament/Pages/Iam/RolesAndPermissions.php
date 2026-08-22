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
 * Roles & Permissions overview and management UI (SRS 10.1 / 11.4 / 15.6).
 */
class RolesAndPermissions extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Identity & Access Management';

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Roles & Permissions';

    protected static ?string $slug = 'iam/roles';

    protected string $view = 'filament.pages.iam.roles-matrix';

    // Livewire state properties
    public bool $isAddModalOpen = false;
    public bool $isDeleteModalOpen = false;

    public string $newRoleName = '';
    public ?int $deletingRoleId = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::UsersViewAll->value)
            || $user->can(Permission::UsersEditRole->value);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return list<array{role: Role, url: string, description: string, color: string, icon: string, granted_count: int, total_count: int, coverage_percent: int}>
     */
    public function getRoleCards(): array
    {
        $total = count(Permission::cases());
        $cards = [];

        foreach (Role::orderBy('name')->get() as $role) {
            $granted = $role->permissions->count();
            $enum = UserRole::tryFrom($role->name);

            $cards[] = [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $enum ? $enum->description() : 'Custom user-defined role.',
                'color' => $enum ? $enum->color() : 'gray',
                'icon' => $enum ? $enum->icon() : 'heroicon-o-shield-check',
                'granted_count' => $granted,
                'total_count' => $total,
                'coverage_percent' => $total > 0 ? (int) round(($granted / $total) * 100) : 0,
            ];
        }

        return $cards;
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

    public function addRole(): void
    {
        abort_unless(auth()->user()?->can(Permission::UsersEditRole->value), 403);

        $validated = $this->validate([
            'newRoleName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
        ], [
            'newRoleName.unique' => 'This role already exists.',
        ]);

        $role = Role::create([
            'name' => $validated['newRoleName'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([Permission::DashboardView->value]);

        Notification::make()
            ->success()
            ->title('Role Created')
            ->body("Role '{$role->name}' has been created successfully.")
            ->send();

        $this->newRoleName = '';
        $this->isAddModalOpen = false;
    }



    public function confirmDeleteRole(int $id): void
    {
        abort_unless(auth()->user()?->can(Permission::UsersEditRole->value), 403);

        $role = Role::findById($id, 'web');

        if ($role->name === UserRole::Administrator->value) {
            Notification::make()
                ->danger()
                ->title('Deletion Blocked')
                ->body('The Administrator role is system-protected and cannot be deleted.')
                ->send();
            return;
        }

        if (\App\Models\User::role($role->name)->exists()) {
            Notification::make()
                ->danger()
                ->title('Deletion Blocked')
                ->body("The role '{$role->name}' is currently assigned to one or more users and cannot be deleted.")
                ->send();
            return;
        }

        $this->deletingRoleId = $role->id;
        $this->isDeleteModalOpen = true;
    }

    public function deleteRole(): void
    {
        abort_unless(auth()->user()?->can(Permission::UsersEditRole->value), 403);

        $role = Role::findById($this->deletingRoleId, 'web');

        if ($role->name === UserRole::Administrator->value) {
            return;
        }

        $roleName = $role->name;
        $role->delete();

        Notification::make()
            ->success()
            ->title('Role Deleted')
            ->body("Role '{$roleName}' has been deleted successfully.")
            ->send();

        $this->isDeleteModalOpen = false;
        $this->deletingRoleId = null;
    }
}
