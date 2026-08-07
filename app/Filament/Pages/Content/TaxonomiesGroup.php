<?php

namespace App\Filament\Pages\Content;

use App\Enums\Permission;
use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class TaxonomiesGroup extends PlaceholderPage
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
}
