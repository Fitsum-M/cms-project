<?php

namespace App\Filament\Pages\Iam;

use App\Enums\Permission;
use App\Filament\Resources\Users\UserResource;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Nav hub for Add New User (SRS 10.1). Opens the invite form.
 */
class AddNewUser extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|UnitEnum|null $navigationGroup = 'Identity & Access Management';

    protected static ?string $navigationLabel = 'Add New User';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Add New User';

    protected static ?string $slug = 'iam/users/create-hub';

    protected string $view = 'filament.pages.placeholder';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::UsersCreate->value) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->redirect(UserResource::getUrl('create'));
    }
}
