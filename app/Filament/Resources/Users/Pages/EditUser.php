<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\UserAdminService;
use App\Services\UserLifecycleService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $record */
        $record = $this->getRecord();
        $data['role'] = $record->primaryRoleName();
        unset($data['password'], $data['passwordConfirmation']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
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

                    $this->refreshFormData(['status', 'suspended_at']);
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

                    $this->refreshFormData(['status', 'suspended_at']);
                }),
            DeleteAction::make()
                ->using(function (User $record): void {
                    app(UserLifecycleService::class)->softDeleteAs(auth()->user(), $record);
                }),
            RestoreAction::make()
                ->using(function (User $record): void {
                    app(UserLifecycleService::class)->restore($record);
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        return app(UserAdminService::class)->update(auth()->user(), $record, [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'bio' => $data['bio'] ?? null,
            'role' => $data['role'] ?? null,
            'password' => $data['password'] ?? null,
        ]);
    }
}
