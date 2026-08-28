<?php

namespace App\Filament\Resources\Users\Pages;

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
        return app(UserLifecycleService::class)->createActive(
            [
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'bio' => $data['bio'] ?? null,
            ],
            (string) $data['role'],
            auth()->user(),
        );
    }

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->getRecord();

        Notification::make()
            ->success()
            ->title('User created')
            ->body("{$user->name} can sign in with their email and password. Access is limited to pages allowed by their role.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
