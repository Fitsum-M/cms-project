<?php

namespace App\Filament\Pages\Iam;

use App\Enums\UserRole;

class EditorRolePage extends RoleDetailPage
{
    protected static ?string $slug = 'iam/roles/editor';

    public static function role(): UserRole
    {
        return UserRole::Editor;
    }
}
