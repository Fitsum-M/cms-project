<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Enums\Permission;
use App\Enums\UserRole;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role Details')
                    ->description('Enter a name for the role.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Role Name')
                            ->required()
                            ->maxLength(255)
                            ->autocomplete(false)
                            ->placeholder('e.g. Moderator')
                            ->unique(
                                table: 'roles',
                                column: 'name',
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('guard_name', 'web'),
                            )
                            ->validationMessages([
                                'unique' => 'This role already exists.',
                            ])
                            ->disabled(fn (?Role $record): bool => $record?->name === UserRole::Administrator->value)
                            ->dehydrated()
                            ->helperText(fn (?Role $record): ?string => $record?->name === UserRole::Administrator->value
                                ? 'Administrator name is system-protected and cannot be edited.'
                                : null),
                    ]),
                Section::make('Role Capabilities Matrix')
                    ->description('Select capabilities permitted for this role. Expand a module to toggle its options.')
                    ->schema(self::permissionGroupSections()),
            ]);
    }

    /**
     * @return list<Section>
     */
    private static function permissionGroupSections(): array
    {
        $sections = [];

        foreach (self::permissionsGrouped() as $groupName => $permissions) {
            $slug = Str::slug($groupName);
            $count = count($permissions);

            $sections[] = Section::make($groupName)
                ->description("{$count} capabilities")
                ->collapsed($groupName !== 'Dashboard')
                ->schema([
                    CheckboxList::make("permissionGroups.{$slug}")
                        ->hiddenLabel()
                        ->options(collect($permissions)->mapWithKeys(
                            fn (Permission $permission): array => [$permission->value => $permission->label()]
                        )->all())
                        ->descriptions(collect($permissions)->mapWithKeys(
                            fn (Permission $permission): array => [$permission->value => $permission->value]
                        )->all())
                        ->columns(3)
                        ->bulkToggleable()
                        ->default(fn (): array => $groupName === 'Dashboard'
                            ? [Permission::DashboardView->value]
                            : []),
                ]);
        }

        return $sections;
    }

    /**
     * @return array<string, list<Permission>>
     */
    public static function permissionsGrouped(): array
    {
        $grouped = [];

        foreach (Permission::cases() as $permission) {
            $grouped[$permission->group()][] = $permission;
        }

        return $grouped;
    }

    /**
     * Flatten grouped checkbox state into a permission name list.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public static function extractPermissionNames(array $data): array
    {
        return collect($data['permissionGroups'] ?? [])
            ->flatten()
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Build form fill state for permission group checkbox lists.
     *
     * @param  list<string>  $grantedPermissionNames
     * @return array<string, list<string>>
     */
    public static function permissionGroupsFillState(array $grantedPermissionNames): array
    {
        $granted = array_flip($grantedPermissionNames);
        $state = [];

        foreach (self::permissionsGrouped() as $groupName => $permissions) {
            $slug = Str::slug($groupName);
            $state[$slug] = collect($permissions)
                ->map(fn (Permission $permission): string => $permission->value)
                ->filter(fn (string $value): bool => isset($granted[$value]))
                ->values()
                ->all();
        }

        return $state;
    }
}
