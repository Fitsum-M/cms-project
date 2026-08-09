<?php

namespace App\Filament\Pages\Content;

use App\Enums\Permission;
use App\Filament\Pages\NavigationHubPage;
use App\Filament\Resources\Categories\CategoryResource;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Nav parent for Taxonomies (SRS 10.1). Opens Categories by default.
 */
class TaxonomiesGroup extends NavigationHubPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Taxonomies';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Taxonomies';

    protected static ?string $slug = 'content/taxonomies-hub';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::TaxonomiesView->value) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->redirect(CategoryResource::getUrl('index'));
    }
}
