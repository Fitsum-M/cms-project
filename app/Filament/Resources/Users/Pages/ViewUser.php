<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\Users\UserResource;
use App\Services\UserLifecycleService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use InvalidArgumentException;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->can('update', $this->getRecord()) ?? false),
            Action::make('resendInvitation')
                ->label('Resend invitation')
                ->icon('heroicon-o-envelope')
                ->visible(fn (): bool => (auth()->user()?->can(Permission::UsersCreate->value) ?? false)
                    && $this->getRecord()->status === UserStatus::PendingActivation
                    && ! $this->getRecord()->trashed())
                ->requiresConfirmation()
                ->action(function (): void {
                    try {
                        app(UserLifecycleService::class)->resendInvitation($this->getRecord());
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
                ->visible(fn (): bool => (auth()->user()?->can('suspend', $this->getRecord()) ?? false)
                    && $this->getRecord()->status !== UserStatus::Suspended
                    && $this->getRecord()->status !== UserStatus::PendingActivation
                    && ! $this->getRecord()->trashed())
                ->requiresConfirmation()
                ->action(function (): void {
                    app(UserLifecycleService::class)->suspendAs(auth()->user(), $this->getRecord());

                    Notification::make()
                        ->success()
                        ->title('User suspended')
                        ->send();
                }),
            Action::make('reactivate')
                ->label('Reactivate')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => (auth()->user()?->can('suspend', $this->getRecord()) ?? false)
                    && $this->getRecord()->status === UserStatus::Suspended
                    && ! $this->getRecord()->trashed())
                ->requiresConfirmation()
                ->action(function (): void {
                    app(UserLifecycleService::class)->reactivate($this->getRecord());

                    Notification::make()
                        ->success()
                        ->title('User reactivated')
                        ->send();
                }),
        ];
    }
}
