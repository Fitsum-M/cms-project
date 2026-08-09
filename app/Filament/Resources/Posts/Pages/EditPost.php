<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\ContentStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Services\ContentLifecycleService;
use App\Services\PostService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
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

                    Notification::make()
                        ->success()
                        ->title('Post duplicated')
                        ->send();

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

                    Notification::make()
                        ->success()
                        ->title('Post restored to Draft')
                        ->send();

                    $this->redirect(PostResource::getUrl('edit', ['record' => $record]));
                }),
            DeleteAction::make()
                ->visible(fn (): bool => ! $this->getRecord()->trashed())
                ->using(function (Post $record): void {
                    try {
                        app(ContentLifecycleService::class)->trash($record);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete post')
                            ->body(collect($exception->errors())->flatten()->first() ?? 'Delete blocked.')
                            ->send();

                        throw $exception;
                    }
                }),
            ForceDeleteAction::make()
                ->visible(fn (): bool => $this->getRecord()->trashed()
                    && (auth()->user()?->can('forceDelete', $this->getRecord()) ?? false))
                ->using(function (Post $record): void {
                    try {
                        app(ContentLifecycleService::class)->forceDelete($record, auth()->user());
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot permanently delete post')
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
        /** @var Post $record */
        $record = $this->getRecord();

        $data['category_ids'] = $record->categories()->pluck('categories.id')->all();
        $data['tag_ids'] = $record->tags()->pluck('tags.id')->all();
        $data['custom_term_ids'] = $record->customTaxonomyTerms()->pluck('custom_taxonomy_terms.id')->all();
        $data['seo'] = app(\App\Services\ContentSeoService::class)->formState($record);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Post $record */
        return app(PostService::class)->update($record, $data, auth()->user());
    }
}
