<?php

namespace App\Filament\Navigation;

use App\Enums\Permission;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\PostTypes\PostTypeResource;
use App\Support\PostTypeRegistry;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;

use function Filament\Support\original_request;

/**
 * Content → Posts → Custom Post Types parent (SRS 10.1 / 12.4.1) without a redirect hub.
 * Dynamic CPT listing items are registered from PostResource::getNavigationItems().
 * Manage Types stays on PostTypeResource.
 */
final class CustomPostTypesNavigation
{
    /**
     * Built at panel boot; visibility/URL resolve per request via closures.
     *
     * @return list<NavigationItem>
     */
    public static function items(): array
    {
        return [
            NavigationItem::make('Custom Post Types')
                ->icon(Heroicon::OutlinedRectangleStack)
                ->group('Content')
                ->parentItem('Posts')
                ->sort(13)
                ->visible(fn (): bool => self::canSeeParent())
                ->url(fn (): string => self::parentUrl())
                ->isActiveWhen(fn (): bool => self::isParentActive()),
        ];
    }

    private static function canSeeParent(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::CustomPostTypesManage->value)
            || $user->can(Permission::PostsViewAll->value)
            || $user->can(Permission::PostsViewOwn->value);
    }

    private static function parentUrl(): string
    {
        $user = auth()->user();

        if ($user?->can(Permission::CustomPostTypesManage->value)) {
            return PostTypeResource::getUrl('index');
        }

        $first = PostTypeRegistry::customTypes()[0] ?? null;

        if ($first !== null) {
            return PostResource::getUrl('index', [
                'post_type' => $first->slug,
            ]);
        }

        return PostResource::getUrl('index');
    }

    private static function isParentActive(): bool
    {
        $request = original_request();

        if ($request->routeIs('filament.admin.resources.content.posts.types.*')) {
            return true;
        }

        if (! $request->routeIs('filament.admin.resources.content.posts.*')) {
            return false;
        }

        $postType = $request->query('post_type');

        return is_string($postType) && PostTypeRegistry::isCustom($postType);
    }
}
