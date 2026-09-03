<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Enums\Permission;
use App\Filament\Resources\Roles\RoleResource;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        $totalPermissions = count(Permission::cases());

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Role')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (Role $record): string => $record->displayColor())
                    ->icon(fn (Role $record): string => $record->displayIcon()),
                TextColumn::make('description')
                    ->label('Description')
                    ->state(fn (Role $record): string => $record->displayDescription())
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn (Role $record): string => $record->displayDescription()),
                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->sortable()
                    ->formatStateUsing(function (int $state) use ($totalPermissions): string {
                        $percent = $totalPermissions > 0
                            ? (int) round(($state / $totalPermissions) * 100)
                            : 0;

                        return "{$state}/{$totalPermissions} ({$percent}%)";
                    })
                    ->description(function (Role $record) use ($totalPermissions): ?string {
                        $granted = (int) ($record->permissions_count ?? $record->permissions()->count());
                        $percent = $totalPermissions > 0
                            ? (int) round(($granted / $totalPermissions) * 100)
                            : 0;

                        return "Coverage {$percent}%";
                    }),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make()
                    ->visible(fn (): bool => auth()->user() !== null
                        && (auth()->user()->can(Permission::UsersViewAll->value)
                            || auth()->user()->can(Permission::UsersEditRole->value))),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can(Permission::UsersEditRole->value) ?? false),
                DeleteAction::make()
                    ->label('Delete')
                    ->visible(fn (Role $record): bool => (auth()->user()?->can(Permission::UsersEditRole->value) ?? false)
                        && ! $record->isAdministrator())
                    ->requiresConfirmation()
                    ->modalHeading('Delete Role')
                    ->modalDescription('Are you sure you want to delete this role? Users assigned to this role will need to be re-assigned. This action cannot be undone.')
                    ->modalSubmitActionLabel('Delete Role')
                    ->action(function (Role $record): void {
                        if ($record->isAdministrator()) {
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
                    }),
            ])
            ->recordUrl(fn (Role $record): string => RoleResource::getUrl(
                auth()->user()?->can(Permission::UsersEditRole->value) ? 'edit' : 'view',
                ['record' => $record],
            ));
    }
}
