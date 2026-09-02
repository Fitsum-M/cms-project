<?php

namespace App\Filament\Resources\Pages;

use App\Enums\Permission;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\Pages\ViewPage;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Filament\Resources\Pages\Schemas\PageInfolist;
use App\Filament\Resources\Pages\Tables\PagesTable;
use App\Models\Page;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

use function Filament\Support\original_request;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'All Pages';

    protected static ?string $navigationParentItem = 'Pages';

    protected static ?int $navigationSort = 21;

    protected static ?string $modelLabel = 'Page';

    protected static ?string $pluralModelLabel = 'Pages';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'content/pages';

    /**
     * Keep "All Pages" inactive on create so "Add New Page" owns that active state.
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

        if (! (auth()->user()?->can(Permission::PagesCreate->value) ?? false)) {
            return $items;
        }

        $items[] = NavigationItem::make('Add New Page')
            ->group(static::getNavigationGroup())
            ->parentItem('Pages')
            ->icon(Heroicon::OutlinedPlusCircle)
            ->sort(22)
            ->url(static::getUrl('create'))
            ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteBaseName().'.create'));

        return $items;
    }

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['author', 'parent']);

        $user = auth()->user();

        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can(Permission::PagesViewAll->value)) {
            return $query;
        }

        return $query->where('author_id', $user->getKey());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'view' => ViewPage::route('/{record}'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug', 'body'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Page $record */
        return [
            'Status' => $record->contentStatus()->label(),
            'Path' => $record->publicPath(),
        ];
    }
}
