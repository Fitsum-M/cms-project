<?php

namespace App\Filament\Resources\Posts;

use App\Enums\Permission;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Pages\ViewPost;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Filament\Resources\Posts\Schemas\PostInfolist;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Models\Post;
use App\Support\PostTypeRegistry;
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

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'All Posts';

    protected static ?string $navigationParentItem = 'Posts';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'Post';

    protected static ?string $pluralModelLabel = 'Posts';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'content/posts';

    /**
     * Keep "All Posts" inactive on create so "Add New Post" owns that active state.
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

        foreach ($items as $item) {
            $item->isActiveWhen(function (): bool {
                $request = original_request();
                $base = static::getRouteBaseName();

                if (! $request->routeIs([
                    $base.'.index',
                    $base.'.view',
                    $base.'.edit',
                ])) {
                    return false;
                }

                $postType = $request->query('post_type');

                return ! (is_string($postType) && PostTypeRegistry::isCustom($postType));
            });
        }

        if (auth()->user()?->can(Permission::PostsCreate->value) ?? false) {
            $items[] = NavigationItem::make('Add New Post')
                ->group(static::getNavigationGroup())
                ->parentItem('Posts')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->sort(12)
                ->url(static::getUrl('create'))
                ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteBaseName().'.create'));
        }

        return array_merge($items, static::customPostTypeNavigationItems());
    }

    /**
     * CPT listings under Custom Post Types (SRS 12.4.1). Registered here so auth/registry resolve per request.
     *
     * @return list<NavigationItem>
     */
    public static function customPostTypeNavigationItems(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        if (! ($user->can(Permission::PostsViewAll->value) || $user->can(Permission::PostsViewOwn->value))) {
            return [];
        }

        $items = [];
        $sort = 10;

        foreach (PostTypeRegistry::customTypes() as $type) {
            $slug = $type->slug;

            $items[] = NavigationItem::make($type->plural_name)
                ->group(static::getNavigationGroup())
                ->parentItem('Custom Post Types')
                ->icon($type->resolvedIcon())
                ->sort($sort++)
                ->url(fn (): string => static::getUrl('index', [
                    'post_type' => $slug,
                ]))
                ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteBaseName().'.*')
                    && original_request()->query('post_type') === $slug);
        }

        return $items;
    }

    public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PostInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['author', 'featuredImage', 'categories', 'tags', 'customTaxonomyTerms.taxonomy']);

        $user = auth()->user();

        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can(Permission::PostsViewAll->value)) {
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
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'view' => ViewPost::route('/{record}'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug', 'excerpt'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Post $record */
        return [
            'Status' => $record->contentStatus()->label(),
            'Author' => $record->author?->name ?? '—',
        ];
    }
}
