<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\ContentStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Services\ContentLifecycleService;
use App\Services\PostService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => ! $this->getRecord()->trashed()),
            Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->visible(fn (): bool => auth()->user()?->can('duplicate', $this->getRecord()) ?? false)
                ->action(function (): void {
                    /** @var Post $record */
                    $record = $this->getRecord();

                    try {
                        $copy = app(PostService::class)->duplicate($record, auth()->user());
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot duplicate post')
                            ->body(collect($exception->errors())->flatten()->first() ?? 'Duplicate blocked.')
                            ->send();

                        return;
                    }

                    $this->redirect(PostResource::getUrl('edit', ['record' => $copy]));
                }),
            Action::make('restore')
                ->label('Restore')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->visible(fn (): bool => (auth()->user()?->can('restore', $this->getRecord()) ?? false)
                    && app(ContentLifecycleService::class)->canRestore($this->getRecord()))
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var Post $record */
                    $record = $this->getRecord();

                    try {
                        app(ContentLifecycleService::class)->restore($record, auth()->user(), ContentStatus::Draft);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot restore post')
                            ->body(collect($exception->errors())->flatten()->first() ?? 'Restore blocked.')
                            ->send();

                        throw $exception;
                    }

                    $this->redirect(PostResource::getUrl('edit', ['record' => $record]));
                }),
        ];
    }
}
