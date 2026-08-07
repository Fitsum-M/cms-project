<?php

namespace App\Filament\Pages\Content;

use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AddNewPost extends PlaceholderPage
{

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Add New Post';

    protected static ?string $navigationParentItem = 'Posts';

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'Add New Post';

    protected static ?string $slug = 'content/posts/create';
}
