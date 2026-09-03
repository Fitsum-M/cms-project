<?php

namespace App\Filament\Resources\Roles\RelationManagers;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\RoleAssignmentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Role;

/**
 * Users assigned to this role (exactly one role per user — SRS 11.2 / 15.6).
 * Assignment goes through RoleAssignmentService; no create-user or password fields here.
 */
class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Assigned Users';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::UsersViewAll->value)
            || $user->can(Permission::UsersEditRole->value);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record): ?string => $record->username),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?UserStatus $state): string => $state?->label() ?? '—')
                    ->color(fn (?UserStatus $state): string => match ($state) {
                        UserStatus::Active => 'success',
                        UserStatus::PendingActivation => 'warning',
                        UserStatus::Suspended => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->headerActions([
                Action::make('assignUser')
                    ->label('Assign user')
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn (): bool => auth()->user()?->can(Permission::UsersEditRole->value) ?? false)
                    ->form([
                        Select::make('user_id')
                            ->label('User')
                            ->placeholder('Select a user to assign this role')
                            ->searchable()
                            ->required()
                            ->options(fn (): array => $this->assignableUserOptions())
                            ->helperText('Each user has exactly one role. Assigning replaces their current role.'),
                    ])
                    ->action(function (array $data): void {
                        $this->assignRoleToUser((int) $data['user_id']);
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
                    ->visible(fn (User $record): bool => auth()->user()?->can('view', $record) ?? false),
                EditAction::make()
                    ->url(fn (User $record): string => UserResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false),
                Action::make('reassignRole')
                    ->label('Change role')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (User $record): bool => auth()->user()?->can('updateRole', $record) ?? false)
                    ->form([
                        Select::make('role')
                            ->label('New role')
                            ->options(fn (): array => Role::query()
                                ->where('guard_name', 'web')
                                ->where('name', '!=', $this->getOwnerRecord()->name)
                                ->orderBy('name')
                                ->pluck('name', 'name')
                                ->all())
                            ->required()
                            ->native(false)
                            ->helperText('Moves this user off the current role onto the selected role.'),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Change user role')
                    ->action(function (User $record, array $data): void {
                        $this->reassignUserRole($record, (string) $data['role']);
                    }),
            ])
            ->emptyStateHeading('No users assigned')
            ->emptyStateDescription('Assign an existing user to this role. Create new users from All Users.')
            ->recordUrl(fn (User $record): ?string => auth()->user()?->can('view', $record)
                ? UserResource::getUrl(
                    auth()->user()?->can('update', $record) ? 'edit' : 'view',
                    ['record' => $record],
                )
                : null);
    }

    /**
     * @return array<int, string>
     */
    private function assignableUserOptions(): array
    {
        /** @var Role $role */
        $role = $this->getOwnerRecord();

        return User::query()
            ->whereDoesntHave('roles', function (Builder $query) use ($role): void {
                $query->where('name', $role->name)->where('guard_name', 'web');
            })
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $user): array => [
                $user->id => "{$user->name} ({$user->email})",
            ])
            ->all();
    }

    private function assignRoleToUser(int $userId): void
    {
        /** @var Role $role */
        $role = $this->getOwnerRecord();
        $actor = auth()->user();
        $target = User::query()->findOrFail($userId);

        if ($actor === null) {
            return;
        }

        try {
            app(RoleAssignmentService::class)->assign($actor, $target, $role->name);
        } catch (AuthorizationException $exception) {
            Notification::make()
                ->danger()
                ->title('Cannot assign role')
                ->body($exception->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('User assigned')
            ->body("{$target->name} is now assigned the '{$role->name}' role.")
            ->send();
    }

    private function reassignUserRole(User $target, string $newRoleName): void
    {
        $actor = auth()->user();

        if ($actor === null) {
            return;
        }

        try {
            app(RoleAssignmentService::class)->assign($actor, $target, $newRoleName);
        } catch (AuthorizationException $exception) {
            Notification::make()
                ->danger()
                ->title('Cannot change role')
                ->body($exception->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Role changed')
            ->body("{$target->name} is now assigned the '{$newRoleName}' role.")
            ->send();
    }
}
