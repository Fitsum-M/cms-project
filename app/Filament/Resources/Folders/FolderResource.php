<?php

namespace App\Filament\Resources\Folders;

use App\Enums\Permission;
use App\Filament\Resources\Folders\Pages\CreateFolder;
use App\Filament\Resources\Folders\Pages\EditFolder;
use App\Filament\Resources\Folders\Pages\ListFolders;
use App\Filament\Resources\Folders\Pages\ViewFolder;
use App\Filament\Resources\Folders\RelationManagers\MediaAssetsRelationManager;
use App\Filament\Resources\Folders\Schemas\FolderForm;
use App\Filament\Resources\Folders\Schemas\FolderInfolist;
use App\Filament\Resources\Folders\Tables\FoldersTable;
use App\Models\Folder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Folder CRUD via FolderService (create / rename / move / delete).
 * Nav still owned by Dam\Folders page until later Step 2 wiring.
 */
class FolderResource extends Resource
{
    protected static ?string $model = Folder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Digital Asset Management';

    protected static ?string $navigationLabel = 'Folders';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Folder';

    protected static ?string $pluralModelLabel = 'Folders';

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Interim slug while legacy Dam\Folders still owns `/dam/folders` (until Steps 2.6–2.7).
     */
    protected static ?string $slug = 'dam/folders-resource';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return FolderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FolderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FoldersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['parent'])
            ->withCount(['children', 'mediaAssets']);
    }

    public static function getRelations(): array
    {
        return [
            MediaAssetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFolders::route('/'),
            'create' => CreateFolder::route('/create'),
            'view' => ViewFolder::route('/{record}'),
            'edit' => EditFolder::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Folder $record */
        return [
            'Parent' => $record->parent?->name ?? '— Root —',
            'Files' => (string) ($record->media_assets_count ?? $record->mediaAssets()->count()),
        ];
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(Permission::MediaView->value) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(Permission::MediaUpload->value) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update', $record) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('view', $record) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete', $record) ?? false;
    }
}
