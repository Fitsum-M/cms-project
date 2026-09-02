<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    /** @var list<string> */
    private array $permissionsToSync = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $record */
        $record = $this->getRecord();
        $record->loadMissing('permissions');

        $data['permissionGroups'] = RoleForm::permissionGroupsFillState(
            $record->permissions->pluck('name')->all()
        );

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Role $record */
        $record = $this->getRecord();

        $this->permissionsToSync = RoleForm::extractPermissionNames($data);
        unset($data['permissionGroups']);

        if (
            $record->name === UserRole::Administrator->value
            && ($data['name'] ?? $record->name) !== UserRole::Administrator->value
        ) {
            Notification::make()
                ->danger()
                ->title('Renaming Blocked')
                ->body('The Administrator role name is system-protected and cannot be renamed.')
                ->send();

            $data['name'] = UserRole::Administrator->value;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Role $role */
        $role = $this->getRecord();
        $role->syncPermissions($this->permissionsToSync);

        Notification::make()
            ->success()
            ->title('Role Saved')
            ->body("Role '{$role->name}' has been updated successfully.")
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => (auth()->user()?->can(Permission::UsersEditRole->value) ?? false)
                    && $this->getRecord()->name !== UserRole::Administrator->value)
                ->requiresConfirmation()
                ->modalHeading('Delete Role')
                ->modalDescription('Are you sure you want to delete this role? Users assigned to this role will need to be re-assigned. This action cannot be undone.')
                ->modalSubmitActionLabel('Delete Role')
                ->action(function (Role $record): void {
                    if ($record->name === UserRole::Administrator->value) {
                        Notification::make()
                            ->danger()
                            ->title('Deletion Blocked')
                            ->body('The Administrator role is system-protected and cannot be deleted.')
                            ->send();

                        return;
                    }

                    $hasUsers = User::query()
                        ->whereHas('roles', fn ($query) => $query->where('name', $record->name))
                        ->exists();

                    if ($hasUsers) {
                        Notification::make()
                            ->danger()
                            ->title('Deletion Blocked')
                            ->body("The role '{$record->name}' is currently assigned to one or more users and cannot be deleted.")
                            ->send();

                        return;
                    }

                    $roleName = $record->name;
                    $record->delete();

                    Notification::make()
                        ->success()
                        ->title('Role Deleted')
                        ->body("Role '{$roleName}' has been deleted successfully.")
                        ->send();

                    $this->redirect(RoleResource::getUrl('index'));
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
