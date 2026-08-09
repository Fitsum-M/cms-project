<?php

namespace App\Filament\Pages\Content;

use App\Enums\Permission;
use App\Filament\Pages\PlaceholderPage;
use App\Filament\Resources\Pages\PageResource;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Nav parent for Pages (SRS 10.1). Opens the All Pages listing.
 */
class PagesGroup extends PlaceholderPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Pages';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Pages';

    protected static ?string $slug = 'content/pages-hub';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::PagesViewAll->value)
            || $user->can(Permission::PagesViewOwn->value);
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

        if (! (auth()->user()?->can(Permission::PagesCreate->value) ?? false)) {
            return $items;
        }

        $items[] = NavigationItem::make('Add New Page')
            ->group('Content')
            ->parentItem('Pages')
            ->icon(Heroicon::OutlinedPlusCircle)
            ->sort(22)
            ->url(PageResource::getUrl('create'))
            ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.content.pages.create'));

        return $items;
    }

    public function mount(): void
    {
        $this->redirect(PageResource::getUrl('index'));
    }
}
