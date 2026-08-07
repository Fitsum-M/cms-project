<?php

namespace App\Filament\Pages\Content;

use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PostsGroup extends PlaceholderPage
{

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Posts';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Posts';

    protected static ?string $slug = 'content/posts-hub';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
