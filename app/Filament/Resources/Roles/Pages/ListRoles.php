<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Enums\Permission;
use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add New Role')
                ->visible(fn (): bool => auth()->user()?->can(Permission::UsersEditRole->value) ?? false),
        ];
    }
}
