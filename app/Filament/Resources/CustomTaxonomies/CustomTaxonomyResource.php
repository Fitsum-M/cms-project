<?php

namespace App\Filament\Resources\CustomTaxonomies;

use App\Filament\Resources\CustomTaxonomies\Pages\CreateCustomTaxonomy;
use App\Filament\Resources\CustomTaxonomies\Pages\EditCustomTaxonomy;
use App\Filament\Resources\CustomTaxonomies\Pages\ListCustomTaxonomies;
use App\Filament\Resources\CustomTaxonomies\Pages\ViewCustomTaxonomy;
use App\Filament\Resources\CustomTaxonomies\RelationManagers\TermsRelationManager;
use App\Filament\Resources\CustomTaxonomies\Schemas\CustomTaxonomyForm;
use App\Filament\Resources\CustomTaxonomies\Schemas\CustomTaxonomyInfolist;
use App\Filament\Resources\CustomTaxonomies\Tables\CustomTaxonomiesTable;
use App\Models\CustomTaxonomy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CustomTaxonomyResource extends Resource
{
    protected static ?string $model = CustomTaxonomy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Custom Taxonomies';

    protected static ?string $navigationParentItem = 'Taxonomies';

    protected static ?int $navigationSort = 33;

    protected static ?string $modelLabel = 'Custom Taxonomy';

    protected static ?string $pluralModelLabel = 'Custom Taxonomies';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'content/taxonomies/custom';

    public static function form(Schema $schema): Schema
    {
        return CustomTaxonomyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomTaxonomyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomTaxonomiesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('terms')->with('postTypeAssociations');
    }

    public static function getRelations(): array
    {
        return [
            TermsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomTaxonomies::route('/'),
            'create' => CreateCustomTaxonomy::route('/create'),
            'view' => ViewCustomTaxonomy::route('/{record}'),
            'edit' => EditCustomTaxonomy::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var CustomTaxonomy $record */
        return [
            'Slug' => $record->slug,
            'Type' => $record->structure_type->label(),
        ];
    }
}
