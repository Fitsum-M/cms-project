<?php

namespace App\Filament\Resources\Roles;

use App\Enums\Permission;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use App\Filament\Resources\Roles\RelationManagers\UsersRelationManager;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Schemas\RoleInfolist;
use App\Filament\Resources\Roles\Tables\RolesTable;
use App\Models\Role;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Role CRUD owns name + permissions only.
 * User create/update/password/email belongs in UserResource; assignment uses UsersRelationManager.
 */
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Identity & Access Management';

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Role';

    protected static ?string $pluralModelLabel = 'Roles';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'iam/roles';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RoleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('guard_name', 'web')
            ->withCount(['users', 'permissions']);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Role $record */
        $usersCount = (int) ($record->users_count ?? $record->users()->count());
        $permissionsCount = (int) ($record->permissions_count ?? $record->permissions()->count());

        return [
            'Type' => $record->is_system ? __('cms.iam.roles.system_role') : 'Custom Role',
            'Users' => (string) $usersCount,
            'Permissions' => (string) $permissionsCount,
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->where('guard_name', 'web')
            ->withCount(['users', 'permissions']);
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::UsersViewAll->value)
            || $user->can(Permission::UsersEditRole->value);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(Permission::UsersEditRole->value) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can(Permission::UsersEditRole->value) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        /** @var Role $record */
        if ($record->isAdministrator()) {
            return false;
        }

        return auth()->user()?->can(Permission::UsersEditRole->value) ?? false;
    }
}
