<?php

namespace App\Filament\Resources\Folders\Pages;

use App\Filament\Resources\Folders\FolderActions;
use App\Filament\Resources\Folders\FolderResource;
use App\Models\Folder;
use App\Services\FolderService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditFolder extends EditRecord
{
    protected static string $resource = FolderResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Folder $record */
        try {
            return app(FolderService::class)->update($record, [
                'name' => (string) $data['name'],
                'parent_id' => $data['parent_id'] ?? null,
            ]);
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Cannot update folder')
                ->body(collect($exception->errors())->flatten()->first() ?? 'Update blocked.')
                ->send();

            throw $exception;
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Folder updated');
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            FolderActions::configureDeleteAction(
                DeleteAction::make()
                    ->successRedirectUrl(FolderResource::getUrl('index')),
            ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
