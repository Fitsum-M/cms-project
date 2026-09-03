<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Models\Role;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected static ?string $title = 'Create Role';

    /** @var list<string> */
    private array $permissionsToSync = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->permissionsToSync = RoleForm::extractPermissionNames($data);
        unset($data['permissionGroups']);

        $data['guard_name'] = 'web';
        $data['is_system'] = false;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Role $role */
        $role = $this->getRecord();
        $role->syncPermissions($this->permissionsToSync);

        Notification::make()
            ->success()
            ->title('Role Created')
            ->body("Role '{$role->name}' has been created successfully.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
