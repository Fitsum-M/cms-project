<?php

namespace App\Filament\Pages\Content;

use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AllPages extends PlaceholderPage
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'All Pages';

    protected static ?string $navigationParentItem = 'Pages';

    protected static ?int $navigationSort = 21;

    protected static ?string $title = 'All Pages';

    protected static ?string $slug = 'content/pages';
}
