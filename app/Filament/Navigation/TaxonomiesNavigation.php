<?php

namespace App\Filament\Navigation;

use App\Enums\Permission;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;

use function Filament\Support\original_request;

/**
 * Content → Taxonomies parent item (SRS 10.1) without a redirect hub page.
 * Children nest via Category/Tag/CustomTaxonomy `$navigationParentItem = 'Taxonomies'`.
 */
final class TaxonomiesNavigation
{
    /**
     * Built at panel boot; visibility/URL resolve per request via closures.
     *
     * @return list<NavigationItem>
     */
    public static function items(): array
    {
        return [
            NavigationItem::make('Taxonomies')
                ->icon(Heroicon::OutlinedTag)
                ->group('Content')
                ->sort(30)
                ->visible(fn (): bool => auth()->user()?->can(Permission::TaxonomiesView->value) ?? false)
                ->url(fn (): string => CategoryResource::getUrl('index'))
                ->isActiveWhen(fn (): bool => original_request()->routeIs([
                    'filament.admin.resources.content.taxonomies.categories.*',
                    'filament.admin.resources.content.taxonomies.tags.*',
                    'filament.admin.resources.content.taxonomies.custom.*',
                ])),
        ];
    }
}
