<?php

namespace App\Filament\Pages\Content;

use App\Enums\Permission;
use App\Filament\Pages\PlaceholderPage;
use App\Filament\Resources\Posts\PostResource;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Nav parent for Posts (SRS 10.1). Opens the All Posts listing.
 */
class PostsGroup extends PlaceholderPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Posts';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Posts';

    protected static ?string $slug = 'content/posts-hub';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::PostsViewAll->value)
            || $user->can(Permission::PostsViewOwn->value);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return list<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        if (! (auth()->user()?->can(Permission::PostsCreate->value) ?? false)) {
            return $items;
        }

        $items[] = NavigationItem::make('Add New Post')
            ->group('Content')
            ->parentItem('Posts')
            ->icon(Heroicon::OutlinedPlusCircle)
            ->sort(12)
            ->url(PostResource::getUrl('create'))
            ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.content.posts.create'));

        return $items;
    }

    public function mount(): void
    {
        $this->redirect(PostResource::getUrl('index'));
    }
}
