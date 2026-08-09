<?php

namespace App\Filament\Resources\PostTypes;

use App\Enums\Permission;
use App\Filament\Resources\PostTypes\Pages\CreatePostType;
use App\Filament\Resources\PostTypes\Pages\EditPostType;
use App\Filament\Resources\PostTypes\Pages\ListPostTypes;
use App\Filament\Resources\PostTypes\Pages\ViewPostType;
use App\Filament\Resources\PostTypes\Schemas\PostTypeForm;
use App\Filament\Resources\PostTypes\Schemas\PostTypeInfolist;
use App\Filament\Resources\PostTypes\Tables\PostTypesTable;
use App\Models\PostType;
use App\Support\PostTypeRegistry;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PostTypeResource extends Resource
{
    protected static ?string $model = PostType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Manage Types';

    protected static ?string $navigationParentItem = 'Custom Post Types';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Post Type';

    protected static ?string $pluralModelLabel = 'Post Types';

    protected static ?string $recordTitleAttribute = 'plural_name';

    protected static ?string $slug = 'content/posts/types';

    public static function form(Schema $schema): Schema
    {
        return PostTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PostTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPostTypes::route('/'),
            'create' => CreatePostType::route('/create'),
            'view' => ViewPostType::route('/{record}'),
            'edit' => EditPostType::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['plural_name', 'singular_name', 'slug'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var PostType $record */
        return [
            'Slug' => $record->slug,
            'Singular' => $record->singular_name,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::CustomPostTypesManage->value) ?? false;
    }

    /**
     * Registered custom types appear under Custom Post Types (SRS 12.4.1 / 10.1).
     *
     * @return list<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        if (! (auth()->user()?->can(\App\Enums\Permission::PostsViewAll->value)
            || auth()->user()?->can(\App\Enums\Permission::PostsViewOwn->value))) {
            return $items;
        }

        $sort = 10;

        foreach (PostTypeRegistry::customTypes() as $type) {
            $items[] = NavigationItem::make($type->plural_name)
                ->group('Content')
                ->parentItem('Custom Post Types')
                ->icon($type->resolvedIcon())
                ->sort($sort++)
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.posts.*')
                    && request()->query('post_type') === $type->slug)
                ->url(\App\Filament\Resources\Posts\PostResource::getUrl('index', [
                    'post_type' => $type->slug,
                ]));
        }

        return $items;
    }
}
