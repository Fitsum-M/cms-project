<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\UserLifecycleService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->state(fn (User $record): string => $record->primaryRoleName() ?? '—')
                    ->badge()
                    ->color(fn (User $record): string => $record->primaryRole()?->displayColor() ?? 'gray'),
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
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(UserStatus::cases())->mapWithKeys(
                        fn (UserStatus $status): array => [$status->value => $status->label()]
                    )->all()),
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(fn (): array => Role::query()
                        ->where('guard_name', 'web')
                        ->pluck('name', 'name')
                        ->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (! is_string($value) || $value === '') {
                            return $query;
                        }

                        return $query->role($value);
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('resendInvitation')
                    ->label('Resend invite')
                    ->icon('heroicon-o-envelope')
                    ->visible(fn (User $record): bool => (auth()->user()?->can(Permission::UsersCreate->value) ?? false)
                        && $record->status === UserStatus::PendingActivation
                        && ! $record->trashed())
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        try {
                            app(UserLifecycleService::class)->resendInvitation($record);
                        } catch (InvalidArgumentException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot resend invitation')
                                ->body($exception->getMessage())
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Invitation resent')
                            ->send();
                    }),
                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->visible(fn (User $record): bool => (auth()->user()?->can('suspend', $record) ?? false)
                        && $record->status !== UserStatus::Suspended
                        && $record->status !== UserStatus::PendingActivation
                        && ! $record->trashed())
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        app(UserLifecycleService::class)->suspendAs(auth()->user(), $record);

                        Notification::make()
                            ->success()
                            ->title('User suspended')
                            ->send();
                    }),
                Action::make('reactivate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => (auth()->user()?->can('suspend', $record) ?? false)
                        && $record->status === UserStatus::Suspended
                        && ! $record->trashed())
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        app(UserLifecycleService::class)->reactivate($record);

                        Notification::make()
                            ->success()
                            ->title('User reactivated')
                            ->send();
                    }),
                DeleteAction::make()
                    ->using(function (User $record): void {
                        app(UserLifecycleService::class)->softDeleteAs(auth()->user(), $record);
                    }),
                RestoreAction::make()
                    ->using(function (User $record): void {
                        app(UserLifecycleService::class)->restore($record);
                    }),
                ForceDeleteAction::make()
                    ->visible(false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function ($records): void {
                            $lifecycle = app(UserLifecycleService::class);
                            $actor = auth()->user();

                            foreach ($records as $record) {
                                if ($actor === null || ! $actor->can('delete', $record)) {
                                    continue;
                                }

                                $lifecycle->softDeleteAs($actor, $record);
                            }
                        }),
                ]),
            ])
            ->recordUrl(fn (User $record): string => UserResource::getUrl(
                auth()->user()?->can('update', $record) ? 'edit' : 'view',
                ['record' => $record],
            ));
    }
}
