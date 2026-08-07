<?php

namespace App\Filament\Pages\Dam;

use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class UploadMedia extends PlaceholderPage
{

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Digital Asset Management';

    protected static ?string $navigationLabel = 'Upload Media';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Upload Media';

    protected static ?string $slug = 'dam/upload';
}
