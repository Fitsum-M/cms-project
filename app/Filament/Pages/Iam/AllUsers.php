<?php

namespace App\Filament\Pages\Iam;

use App\Enums\Permission;
use App\Filament\Resources\Users\UserResource;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Nav hub for All Users (SRS 10.1). Opens the Users listing.
 */
class AllUsers extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Identity & Access Management';

    protected static ?string $navigationLabel = 'All Users';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'All Users';

    protected static ?string $slug = 'iam/users-hub';

    protected string $view = 'filament.pages.placeholder';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::UsersViewAll->value) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->redirect(UserResource::getUrl('index'));
    }
}
