<?php

namespace App\Filament\Pages\Iam;

use App\Enums\UserRole;

class AdministratorRolePage extends RoleDetailPage
{
    protected static ?string $slug = 'iam/roles/administrator';

    public static function role(): UserRole
    {
        return UserRole::Administrator;
    }
}
