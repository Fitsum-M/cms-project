<?php

namespace App\Filament\Navigation;

use App\Enums\Permission;
use App\Filament\Resources\Pages\PageResource;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;

use function Filament\Support\original_request;

/**
 * Content → Pages parent item (SRS 10.1) without a redirect hub page.
 * Children nest via PageResource / PageHierarchy / PageTemplates `$navigationParentItem = 'Pages'`.
 */
final class PagesNavigation
{
    /**
     * Built at panel boot; visibility/URL resolve per request via closures.
     *
     * @return list<NavigationItem>
     */
    public static function items(): array
    {
        return [
            NavigationItem::make('Pages')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->group('Content')
                ->sort(20)
                ->visible(function (): bool {
                    $user = auth()->user();

                    if ($user === null) {
                        return false;
                    }

                    return $user->can(Permission::PagesViewAll->value)
                        || $user->can(Permission::PagesViewOwn->value);
                })
                ->url(fn (): string => PageResource::getUrl('index'))
                ->isActiveWhen(fn (): bool => original_request()->routeIs([
                    'filament.admin.resources.content.pages.*',
                    'filament.admin.pages.content.pages.hierarchy',
                    'filament.admin.pages.content.pages.templates',
                ])),
        ];
    }
}
