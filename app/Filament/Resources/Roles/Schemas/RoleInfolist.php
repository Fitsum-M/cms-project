<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Enums\Permission;
use App\Enums\UserRole;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role Summary')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Role')
                            ->badge()
                            ->color(fn (string $state): string => UserRole::tryFrom($state)?->color() ?? 'gray')
                            ->icon(fn (string $state): string => UserRole::tryFrom($state)?->icon() ?? 'heroicon-o-shield-check'),
                        TextEntry::make('role_type')
                            ->label('Type')
                            ->badge()
                            ->state(fn (Role $record): string => UserRole::tryFrom($record->name) !== null
                                ? __('cms.iam.roles.system_role')
                                : 'Custom Role')
                            ->color(fn (Role $record): string => UserRole::tryFrom($record->name)?->color() ?? 'gray'),
                        TextEntry::make('description')
                            ->label('Description')
                            ->state(fn (Role $record): string => UserRole::tryFrom($record->name)?->description()
                                ?? 'Custom user-defined role.')
                            ->columnSpanFull(),
                        TextEntry::make('users_count')
                            ->label('Assigned users')
                            ->state(fn (Role $record): int => (int) ($record->users_count ?? $record->users()->count())),
                        TextEntry::make('permission_coverage')
                            ->label(__('cms.iam.roles.coverage'))
                            ->state(function (Role $record): string {
                                $granted = self::grantedCount($record);
                                $total = self::totalPermissionCount();

                                return "{$granted}/{$total}";
                            })
                            ->badge()
                            ->color(fn (Role $record): string => UserRole::tryFrom($record->name)?->color() ?? 'gray')
                            ->helperText(fn (Role $record): string => __('cms.iam.roles.coverage_detail', [
                                'percent' => self::coveragePercent($record),
                            ])),
                    ])
                    ->columns(2),
                Section::make('Role Capabilities Matrix')
                    ->description('View capabilities permitted for this role. Expand a module to see its options.')
                    ->schema(self::capabilityGroupSections()),
            ]);
    }

    /**
     * @return list<Section>
     */
    private static function capabilityGroupSections(): array
    {
        $sections = [];

        foreach (RoleForm::permissionsGrouped() as $groupName => $permissions) {
            $sections[] = Section::make($groupName)
                ->description(function (Role $record) use ($permissions): string {
                    $grantedNames = self::grantedPermissionNames($record);
                    $grantedInGroup = collect($permissions)
                        ->filter(fn (Permission $permission): bool => isset($grantedNames[$permission->value]))
                        ->count();

                    return __('cms.iam.roles.group_coverage', [
                        'granted' => $grantedInGroup,
                        'total' => count($permissions),
                    ]);
                })
                ->collapsed(fn (): bool => $groupName !== 'Dashboard')
                ->schema(
                    collect($permissions)
                        ->map(fn (Permission $permission): IconEntry => IconEntry::make("capability_{$permission->name}")
                            ->label($permission->label())
                            ->boolean()
                            ->state(fn (Role $record): bool => isset(self::grantedPermissionNames($record)[$permission->value]))
                            ->helperText($permission->value))
                        ->all()
                )
                ->columns(3);
        }

        return $sections;
    }

    /**
     * @return array<string, true>
     */
    private static function grantedPermissionNames(Role $record): array
    {
        $record->loadMissing('permissions');

        return array_fill_keys($record->permissions->pluck('name')->all(), true);
    }

    private static function grantedCount(Role $record): int
    {
        if (isset($record->permissions_count)) {
            return (int) $record->permissions_count;
        }

        return count(self::grantedPermissionNames($record));
    }

    private static function totalPermissionCount(): int
    {
        return count(Permission::cases());
    }

    private static function coveragePercent(Role $record): int
    {
        $total = self::totalPermissionCount();

        if ($total === 0) {
            return 0;
        }

        return (int) round((self::grantedCount($record) / $total) * 100);
    }
}
