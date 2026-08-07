<?php

namespace App\Filament\Pages\Content;

use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PageTemplates extends PlaceholderPage
{

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Page Templates';

    protected static ?string $navigationParentItem = 'Pages';

    protected static ?int $navigationSort = 24;

    protected static ?string $title = 'Page Templates';

    protected static ?string $slug = 'content/pages/templates';
}
