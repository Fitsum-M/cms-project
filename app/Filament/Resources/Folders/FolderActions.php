<?php

namespace App\Filament\Resources\Folders;

use App\Models\Folder;
use App\Services\FolderService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Shared folder mutate/delete/move actions for Resource pages and tables.
 */
final class FolderActions
{
    public static function configureDeleteAction(DeleteAction $action): DeleteAction
    {
        return $action
            ->label('Delete')
            ->form(function (Folder $record): array {
                if ($record->isEmpty()) {
                    return [];
                }

                return [
                    Toggle::make('recursive')
                        ->label('Delete nested folders and unfile media in this tree')
                        ->helperText('Media items are moved to Unfiled (not deleted). Nested folders are removed.')
                        ->accepted()
                        ->required(),
                ];
            })
            ->requiresConfirmation()
            ->modalHeading('Delete folder')
            ->modalDescription(function (Folder $record): string {
                if ($record->isEmpty()) {
                    return "Delete empty folder “{$record->name}”?";
                }

                return "Folder “{$record->name}” is not empty. Confirm recursive deletion to continue.";
            })
            ->action(function (Folder $record, array $data): void {
                $recursive = $record->isEmpty() || (bool) ($data['recursive'] ?? false);

                try {
                    app(FolderService::class)->delete($record, $recursive);
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Cannot delete folder')
                        ->body(collect($exception->errors())->flatten()->first() ?? 'Delete blocked.')
                        ->send();

                    throw $exception;
                }

                Notification::make()
                    ->success()
                    ->title('Folder deleted')
                    ->send();
            });
    }

    public static function moveAction(): Action
    {
        return Action::make('move')
            ->label('Move')
            ->icon('heroicon-o-arrows-right-left')
            ->visible(fn (Folder $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->fillForm(fn (Folder $record): array => [
                'parent_id' => $record->parent_id,
            ])
            ->form(fn (Folder $record): array => [
                Select::make('parent_id')
                    ->label('Parent folder')
                    ->options(fn (): array => app(FolderService::class)->parentOptions($record->id))
                    ->searchable()
                    ->nullable()
                    ->placeholder('— Root level —'),
            ])
                    ->action(function (Folder $record, array $data): void {
                $parentId = $data['parent_id'] ?? null;
                $parentId = filled($parentId) ? (int) $parentId : null;

                try {
                    app(FolderService::class)->move($record, $parentId);
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Cannot move folder')
                        ->body(collect($exception->errors())->flatten()->first() ?? 'Move blocked.')
                        ->send();

                    throw $exception;
                }

                Notification::make()
                    ->success()
                    ->title('Folder moved')
                    ->send();
            });
    }
}
