<?php

namespace App\Filament\Pages\Iam;

use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AllUsers extends PlaceholderPage
{

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Identity & Access Management';

    protected static ?string $navigationLabel = 'All Users';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'All Users';

    protected static ?string $slug = 'iam/users';
}
