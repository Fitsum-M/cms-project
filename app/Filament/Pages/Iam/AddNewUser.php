<?php

namespace App\Filament\Pages\Iam;

use BackedEnum;
use App\Filament\Pages\PlaceholderPage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AddNewUser extends PlaceholderPage
{

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|UnitEnum|null $navigationGroup = 'Identity & Access Management';

    protected static ?string $navigationLabel = 'Add New User';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Add New User';

    protected static ?string $slug = 'iam/users/create';
}
