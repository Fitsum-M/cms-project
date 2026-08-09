<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Models\MediaAsset;
use App\Services\MediaDeletionService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewMediaAsset extends ViewRecord
{
    protected static string $resource = MediaAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->using(function (MediaAsset $record): void {
                    try {
                        app(MediaDeletionService::class)->delete($record);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete media')
                            ->body(collect($exception->errors())->flatten()->implode("\n"))
                            ->persistent()
                            ->send();

                        throw $exception;
                    }
                }),
            Action::make('forceDelete')
                ->label('Force delete')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->visible(fn (): bool => auth()->user()?->can('forceDelete', $this->getRecord()) ?? false)
                ->requiresConfirmation()
                ->modalHeading('Force delete media')
                ->modalDescription('This breaks existing references and leaves empty placeholders. This cannot be undone.')
                ->action(function (MediaAsset $record): void {
                    $this->authorize('forceDelete', $record);
                    app(MediaDeletionService::class)->forceDelete($record);
                    $this->redirect(MediaAssetResource::getUrl('index'));
                }),
        ];
    }
}
