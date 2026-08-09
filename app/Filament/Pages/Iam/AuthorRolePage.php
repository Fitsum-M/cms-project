<?php

namespace App\Filament\Pages\Iam;

use App\Enums\UserRole;

class AuthorRolePage extends RoleDetailPage
{
    protected static ?string $slug = 'iam/roles/author';

    public static function role(): UserRole
    {
        return UserRole::Author;
    }
}
