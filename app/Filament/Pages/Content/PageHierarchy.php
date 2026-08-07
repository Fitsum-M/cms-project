<?php

namespace App\Filament\Pages\Content;

use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PageHierarchy extends PlaceholderPage
{

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Page Hierarchy';

    protected static ?string $navigationParentItem = 'Pages';

    protected static ?int $navigationSort = 23;

    protected static ?string $title = 'Page Hierarchy';

    protected static ?string $slug = 'content/pages/hierarchy';
}
