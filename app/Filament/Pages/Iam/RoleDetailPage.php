<?php

namespace App\Filament\Pages\Iam;

use App\Enums\Permission;
use App\Enums\UserRole;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class RoleDetailPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Identity & Access Management';

    protected static ?string $navigationParentItem = 'Roles & Permissions';

    protected static ?string $slug = 'iam/roles-legacy/view/{record}';

    protected string $view = 'filament.pages.iam.role-detail';

    public string $record;

    public function mount(string $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record;
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
        return false;
    }

    /**
     * Per-role sidebar items removed (Step 1.7) — caused a roles DB query on every request.
     * Role detail lives on RoleResource view pages instead.
     *
     * @return array<int, never>
     */
    public static function getNavigationItems(): array
    {
        return [];
    }

    public function getRoleModel(): ?\Spatie\Permission\Models\Role
    {
        return \Spatie\Permission\Models\Role::where('name', $this->record)->where('guard_name', 'web')->first();
    }

    public function getRoleDescription(): string
    {
        $enum = UserRole::tryFrom($this->record);
        return $enum ? $enum->description() : 'Custom user-defined role.';
    }

    public function getRoleColor(): string
    {
        $enum = UserRole::tryFrom($this->record);
        return $enum ? $enum->color() : 'gray';
    }

    public function getRoleIcon(): string
    {
        $enum = UserRole::tryFrom($this->record);
        return $enum ? $enum->icon() : 'heroicon-o-shield-check';
    }

    /**
     * @return list<array{group: string, capabilities: list<array{label: string, value: string, granted: bool}>, granted_count: int, total_count: int}>
     */
    public function getGroupedCapabilities(): array
    {
        $roleModel = $this->getRoleModel();
        $grantedNames = $roleModel ? $roleModel->permissions->pluck('name')->all() : [];
        $groups = [];

        foreach (Permission::cases() as $permission) {
            $group = $permission->group();
            $groups[$group] ??= [];
            $groups[$group][] = [
                'label' => $permission->label(),
                'value' => $permission->value,
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
        $roleModel = $this->getRoleModel();
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
