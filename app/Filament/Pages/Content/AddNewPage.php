<?php

namespace App\Filament\Pages\Content;

use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AddNewPage extends PlaceholderPage
{

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Add New Page';

    protected static ?string $navigationParentItem = 'Pages';

    protected static ?int $navigationSort = 22;

    protected static ?string $title = 'Add New Page';

    protected static ?string $slug = 'content/pages/create';
}
