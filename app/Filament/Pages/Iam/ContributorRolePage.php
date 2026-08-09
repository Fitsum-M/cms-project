<?php

namespace App\Filament\Pages\Iam;

use App\Enums\UserRole;

class ContributorRolePage extends RoleDetailPage
{
    protected static ?string $slug = 'iam/roles/contributor';

    public static function role(): UserRole
    {
        return UserRole::Contributor;
    }
}
