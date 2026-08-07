<?php

namespace App\Filament\Pages\Dam;

use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
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
}
