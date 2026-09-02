<?php

namespace App\Filament\Resources\MediaAssets;

use App\Enums\Permission;
use App\Filament\Resources\MediaAssets\Pages\CreateMediaAsset;
use App\Filament\Resources\MediaAssets\Pages\EditMediaAsset;
use App\Filament\Resources\MediaAssets\Pages\ListMediaAssets;
use App\Filament\Resources\MediaAssets\Pages\ViewMediaAsset;
use App\Filament\Resources\MediaAssets\Schemas\MediaAssetForm;
use App\Filament\Resources\MediaAssets\Schemas\MediaAssetInfolist;
use App\Filament\Resources\MediaAssets\Tables\MediaAssetsTable;
use App\Models\MediaAsset;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

use function Filament\Support\original_request;

class MediaAssetResource extends Resource
{
    protected static ?string $model = MediaAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Digital Asset Management';

    protected static ?string $navigationLabel = 'Library';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Media';

    protected static ?string $pluralModelLabel = 'Media Library';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'dam/library';

    public static function canCreate(): bool
    {
        return auth()->user()?->can(Permission::MediaUpload->value) ?? false;
    }

    /**
     * Keep Library inactive on create so "Upload Media" owns that active state.
     *
     * @return string|array<string>
     */
    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        $base = static::getRouteBaseName();

        return [
            $base.'.index',
            $base.'.view',
            $base.'.edit',
        ];
    }

    /**
     * @return list<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        if (! static::canCreate()) {
            return $items;
        }

        $items[] = NavigationItem::make('Upload Media')
            ->group(static::getNavigationGroup())
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->sort(2)
            ->url(static::getUrl('create'))
            ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteBaseName().'.create'));

        return $items;
    }

    public static function form(Schema $schema): Schema
    {
        return MediaAssetForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MediaAssetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaAssetsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['uploader', 'folder']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaAssets::route('/'),
            'create' => CreateMediaAsset::route('/upload'),
            'view' => ViewMediaAsset::route('/{record}'),
            'edit' => EditMediaAsset::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'original_file_name', 'alt_text', 'caption', 'description'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var MediaAsset $record */
        return [
            'File' => $record->original_file_name,
            'Type' => $record->mime_type ?? '—',
        ];
    }
}
