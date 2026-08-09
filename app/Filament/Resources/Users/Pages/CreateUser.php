<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\UserLifecycleService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Add New User';

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(UserLifecycleService::class)->invite(
            [
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'bio' => $data['bio'] ?? null,
            ],
            UserRole::from((string) $data['role']),
            auth()->user(),
            sendNotification: true,
        );
    }

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->getRecord();

        Notification::make()
            ->success()
            ->title('Invitation sent')
            ->body("{$user->name} was invited as {$user->primaryRole()?->value}. They must activate via email.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
