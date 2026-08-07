<?php

namespace App\Filament\Pages\Dam;

use App\Enums\Permission;
use App\Filament\Pages\PlaceholderPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Folders extends PlaceholderPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Digital Asset Management';

    protected static ?string $navigationLabel = 'Folders';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Folders';

    protected static ?string $slug = 'dam/folders';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::MediaView->value) ?? false;
    }
}
