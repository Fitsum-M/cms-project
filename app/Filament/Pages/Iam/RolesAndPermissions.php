<?php

namespace App\Filament\Pages\Iam;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Support\Auth\RolePermissionMatrix;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Roles & Permissions overview — full §11.4 matrix (SRS 10.1 / 15.6).
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
     * @return list<array{group: string, capability: string, roles: array<string, bool>}>
     */
    public function getMatrixRows(): array
    {
        $roles = UserRole::cases();
        $rows = [];

        foreach (Permission::cases() as $permission) {
            $granted = [];

            foreach ($roles as $role) {
                $granted[$role->value] = in_array(
                    $permission,
                    RolePermissionMatrix::permissionsFor($role),
                    true,
                );
            }

            $rows[] = [
                'group' => $permission->group(),
                'capability' => $permission->label(),
                'roles' => $granted,
            ];
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function getRoleNames(): array
    {
        return array_map(fn (UserRole $role): string => $role->value, UserRole::cases());
    }
}
