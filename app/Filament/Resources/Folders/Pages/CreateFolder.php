<?php

namespace App\Filament\Resources\Folders\Pages;

use App\Filament\Resources\Folders\FolderResource;
use App\Services\FolderService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateFolder extends CreateRecord
{
    protected static string $resource = FolderResource::class;

    protected static ?string $title = 'Create Folder';

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(FolderService::class)->create([
                'name' => (string) $data['name'],
                'parent_id' => $data['parent_id'] ?? null,
            ]);
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Cannot create folder')
                ->body(collect($exception->errors())->flatten()->first() ?? 'Create blocked.')
                ->send();

            throw $exception;
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Folder created');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
