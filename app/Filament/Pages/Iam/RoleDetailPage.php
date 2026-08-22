<?php

namespace App\Filament\Pages\Iam;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Support\Auth\RolePermissionMatrix;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * Single-role capability view under Roles & Permissions (SRS 10.1 / 11.4).
 */
abstract class RoleDetailPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Identity & Access Management';

    protected static ?string $navigationParentItem = 'Roles & Permissions';

    protected string $view = 'filament.pages.iam.role-detail';

    abstract public static function role(): UserRole;

    public static function getNavigationLabel(): string
    {
        return static::role()->value;
    }

    public static function getNavigationSort(): ?int
    {
        return match (static::role()) {
            UserRole::Administrator => 31,
            UserRole::Editor => 32,
            UserRole::Author => 33,
            UserRole::Contributor => 34,
        };
    }

    public function getTitle(): string|Htmlable
    {
        return static::role()->value;
    }

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

    public function getRoleDescription(): string
    {
        return static::role()->description();
    }

    /**
     * @return list<array{group: string, capabilities: list<array{label: string, granted: bool}>}>
     */
    public function getGroupedCapabilities(): array
    {
        $roleModel = \Spatie\Permission\Models\Role::where('name', static::role()->value)->where('guard_name', 'web')->first();
        $grantedNames = $roleModel ? $roleModel->permissions->pluck('name')->all() : [];
        $groups = [];

        foreach (Permission::cases() as $permission) {
            $group = $permission->group();
            $groups[$group] ??= [];
            $groups[$group][] = [
                'label' => $permission->label(),
                'granted' => in_array($permission->value, $grantedNames, true),
            ];
        }

        $rows = [];

        foreach ($groups as $group => $capabilities) {
            $grantedInGroup = count(array_filter($capabilities, static fn (array $capability): bool => $capability['granted']));

            $rows[] = [
                'group' => $group,
                'capabilities' => $capabilities,
                'granted_count' => $grantedInGroup,
                'total_count' => count($capabilities),
            ];
        }

        return $rows;
    }

    public function getGrantedCount(): int
    {
        $roleModel = \Spatie\Permission\Models\Role::where('name', static::role()->value)->where('guard_name', 'web')->first();
        return $roleModel ? $roleModel->permissions->count() : 0;
    }

    public function getTotalCount(): int
    {
        return count(Permission::cases());
    }

    public function getCoveragePercent(): int
    {
        $total = $this->getTotalCount();

        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->getGrantedCount() / $total) * 100);
    }
}
