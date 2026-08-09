<?php

namespace App\Filament\Pages\Content;

use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AllPosts extends PlaceholderPage
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'All Posts';

    protected static ?string $navigationParentItem = 'Posts';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'All Posts';

    protected static ?string $slug = 'content/posts';
}
