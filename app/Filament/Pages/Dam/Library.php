<?php

namespace App\Filament\Pages\Dam;

use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Library extends PlaceholderPage
{

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Digital Asset Management';

    protected static ?string $navigationLabel = 'Library';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Media Library';

    protected static ?string $slug = 'dam/library';
}
