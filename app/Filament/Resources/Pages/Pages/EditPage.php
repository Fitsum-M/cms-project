<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Enums\ContentStatus;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Services\ContentLifecycleService;
use App\Services\PageService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('restore')
                ->label('Restore')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->visible(fn (): bool => (auth()->user()?->can('restore', $this->getRecord()) ?? false)
                    && app(ContentLifecycleService::class)->canRestore($this->getRecord()))
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var Page $record */
                    $record = $this->getRecord();

                    try {
                        app(ContentLifecycleService::class)->restore($record, auth()->user(), ContentStatus::Draft);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot restore page')
                            ->body(collect($exception->errors())->flatten()->first() ?? 'Restore blocked.')
                            ->send();

                        throw $exception;
                    }

                    Notification::make()
                        ->success()
                        ->title('Page restored to Draft')
                        ->send();

                    $this->redirect(PageResource::getUrl('edit', ['record' => $record]));
                }),
            DeleteAction::make()
                ->visible(fn (): bool => ! $this->getRecord()->trashed())
                ->using(function (Page $record): void {
                    try {
                        app(ContentLifecycleService::class)->trash($record);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete page')
                            ->body(collect($exception->errors())->flatten()->first() ?? 'Delete blocked.')
                            ->send();

                        throw $exception;
                    }
                }),
            ForceDeleteAction::make()
                ->visible(fn (): bool => $this->getRecord()->trashed()
                    && (auth()->user()?->can('forceDelete', $this->getRecord()) ?? false))
                ->using(function (Page $record): void {
                    try {
                        app(ContentLifecycleService::class)->forceDelete($record, auth()->user());
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot permanently delete page')
                            ->body(collect($exception->errors())->flatten()->first() ?? 'Delete blocked.')
                            ->send();

                        throw $exception;
                    }
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Page $record */
        $record = $this->getRecord();

        $data['template'] = $record->resolvedTemplate();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Page $record */
        return app(PageService::class)->update($record, $data, auth()->user());
    }
}
