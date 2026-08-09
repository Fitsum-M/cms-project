<?php

namespace App\Filament\Pages\Content;

use App\Enums\Permission;
use App\Filament\Pages\PlaceholderPage;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\PostTypes\PostTypeResource;
use App\Support\PostTypeRegistry;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Navigation parent for Manage Types + registered CPT listings (SRS 10.1 / 12.4.1).
 */
class CustomPostTypes extends PlaceholderPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Custom Post Types';

    protected static ?string $navigationParentItem = 'Posts';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Custom Post Types';

    protected static ?string $slug = 'content/posts/custom-types';

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::CustomPostTypesManage->value)
            || $user->can(Permission::PostsViewAll->value)
            || $user->can(Permission::PostsViewOwn->value);
    }

    /**
     * CPT listings for any role that can view posts (SRS 12.4.1). Manage Types stays on PostTypeResource.
     *
     * @return list<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        $user = auth()->user();

        if ($user === null) {
            return $items;
        }

        if (! ($user->can(Permission::PostsViewAll->value) || $user->can(Permission::PostsViewOwn->value))) {
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
                ->url(PostResource::getUrl('index', [
                    'post_type' => $type->slug,
                ]));
        }

        return $items;
    }

    public function mount(): void
    {
        if (auth()->user()?->can(Permission::CustomPostTypesManage->value)) {
            $this->redirect(PostTypeResource::getUrl('index'));

            return;
        }

        $first = PostTypeRegistry::customTypes()[0] ?? null;

        if ($first !== null) {
            $this->redirect(PostResource::getUrl('index', ['post_type' => $first->slug]));

            return;
        }

        $this->redirect(PostResource::getUrl('index'));
    }
}
