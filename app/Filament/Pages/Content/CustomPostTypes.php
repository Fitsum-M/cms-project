<?php

namespace App\Filament\Pages\Content;

use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CustomPostTypes extends PlaceholderPage
{

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Custom Post Types';

    protected static ?string $navigationParentItem = 'Posts';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Custom Post Types';

    protected static ?string $slug = 'content/posts/types';
}
