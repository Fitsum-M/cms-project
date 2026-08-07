<?php

namespace App\Filament\Resources\MediaAssets;

use App\Filament\Resources\MediaAssets\Pages\ListMediaAssets;
use App\Filament\Resources\MediaAssets\Pages\ViewMediaAsset;
use App\Filament\Resources\MediaAssets\Schemas\MediaAssetInfolist;
use App\Filament\Resources\MediaAssets\Tables\MediaAssetsTable;
use App\Models\MediaAsset;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

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
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return MediaAssetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaAssetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaAssets::route('/'),
            'view' => ViewMediaAsset::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'original_file_name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var MediaAsset $record */
        return [
            'File' => $record->original_file_name,
        ];
    }
}
