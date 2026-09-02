<?php

namespace App\Filament\Navigation;

use App\Enums\Permission;
use App\Filament\Resources\Posts\PostResource;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;

use function Filament\Support\original_request;

/**
 * Content → Posts parent item (SRS 10.1) without a redirect hub page.
 * Children nest via PostResource / CustomPostTypes `$navigationParentItem = 'Posts'`.
 */
final class PostsNavigation
{
    /**
     * Built at panel boot; visibility/URL resolve per request via closures.
     *
     * @return list<NavigationItem>
     */
    public static function items(): array
    {
        return [
            NavigationItem::make('Posts')
                ->icon(Heroicon::OutlinedNewspaper)
                ->group('Content')
                ->sort(10)
                ->visible(function (): bool {
                    $user = auth()->user();

                    if ($user === null) {
                        return false;
                    }

                    return $user->can(Permission::PostsViewAll->value)
                        || $user->can(Permission::PostsViewOwn->value);
                })
                ->url(fn (): string => PostResource::getUrl('index'))
                ->isActiveWhen(fn (): bool => original_request()->routeIs([
                    'filament.admin.resources.content.posts.*',
                    'filament.admin.pages.content.posts.custom-types',
                    'filament.admin.resources.content.posts.types.*',
                ])),
        ];
    }
}
