<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\PostVisibility;
use App\Enums\UserStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\ContentLifecycleService;
use App\Services\PostService;
use App\Support\Content\ContentSearch;
use App\Support\PostTypeRegistry;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image_preview')
                    ->label('')
                    ->getStateUsing(fn (Post $record): ?string => $record->featuredImageUrl())
                    ->square()
                    ->extraImgAttributes(['alt' => ''])
                    ->toggleable(),
                TextColumn::make('title')
                    ->label(__('cms.tables.title'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => ContentSearch::applyPostsSearch($query, $search))
                    ->sortable()
                    ->description(fn (Post $record): string => $record->slug),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->separator(',')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function (Post $record): string {
                        return $record->lifecycleLabel();
                    })
                    ->sortable(),
                TextColumn::make('visibility')
                    ->label('Visibility')
                    ->badge()
                    ->formatStateUsing(fn (PostVisibility|string $state): string => $state instanceof PostVisibility
                        ? $state->label()
                        : (PostVisibility::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->toggleable(),
                TextColumn::make('post_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => PostTypeRegistry::options()[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->label('Publish Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options(ContentStatus::options()),
                SelectFilter::make('visibility')
                    ->options(PostVisibility::options()),
                SelectFilter::make('author_id')
                    ->label('Author')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('post_type')
                    ->label('Post Type')
                    ->options(fn (): array => PostTypeRegistry::options()),
                SelectFilter::make('categories')
                    ->label('Category')
                    ->relationship('categories', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('tags')
                    ->label('Tag')
                    ->relationship('tags', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('date_range')
                    ->label('Date range')
                    ->schema([
                        Select::make('field')
                            ->label('Date field')
                            ->options([
                                'published_at' => 'Published',
                                'created_at' => 'Created',
                            ])
                            ->default('published_at'),
                        DatePicker::make('from')
                            ->label('From'),
                        DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $field = ($data['field'] ?? 'published_at') === 'created_at'
                            ? 'created_at'
                            : 'published_at';

                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, mixed $date): Builder => $q->whereDate($field, '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $q, mixed $date): Builder => $q->whereDate($field, '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Post $record): bool => ! $record->trashed()),
                Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->visible(fn (Post $record): bool => auth()->user()?->can('duplicate', $record) ?? false)
                    ->action(function (Post $record) {
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
                            ->body('Opened the new draft copy.')
                            ->send();

                        return redirect(PostResource::getUrl('edit', ['record' => $copy]));
                    }),
                Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn (Post $record): bool => (auth()->user()?->can('restore', $record) ?? false)
                        && app(ContentLifecycleService::class)->canRestore($record))
                    ->requiresConfirmation()
                    ->action(function (Post $record): void {
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
                    }),
                DeleteAction::make()
                    ->visible(fn (Post $record): bool => ! $record->trashed())
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
                    ->visible(fn (Post $record): bool => $record->trashed()
                        && (auth()->user()?->can('forceDelete', $record) ?? false))
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('changeStatus')
                        ->label('Change status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('status')
                                ->label('Status')
                                ->options(ContentStatus::options())
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $result = app(PostService::class)->bulkChangeStatus(
                                $records,
                                (string) $data['status'],
                                auth()->user(),
                            );
                            self::notifyBulkResult('Status updated', $result);
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('changeAuthor')
                        ->label('Change author')
                        ->icon('heroicon-o-user')
                        ->visible(fn (): bool => auth()->user()?->can(Permission::PostsEditOthers->value) ?? false)
                        ->form([
                            Select::make('author_id')
                                ->label('Author')
                                ->options(fn (): array => User::query()
                                    ->where('status', UserStatus::Active)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            try {
                                $result = app(PostService::class)->bulkChangeAuthor(
                                    $records,
                                    (int) $data['author_id'],
                                    auth()->user(),
                                );
                            } catch (ValidationException $exception) {
                                Notification::make()
                                    ->danger()
                                    ->title('Cannot change author')
                                    ->body(collect($exception->errors())->flatten()->first() ?? 'Blocked.')
                                    ->send();

                                return;
                            }

                            self::notifyBulkResult('Author updated', $result);
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('assignCategories')
                        ->label('Assign categories')
                        ->icon('heroicon-o-folder')
                        ->form([
                            Select::make('category_ids')
                                ->label('Categories')
                                ->multiple()
                                ->options(fn (): array => app(CategoryService::class)->parentOptions())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $result = app(PostService::class)->bulkAssignCategories(
                                $records,
                                array_values((array) ($data['category_ids'] ?? [])),
                                auth()->user(),
                            );
                            self::notifyBulkResult('Categories assigned', $result);
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('assignTags')
                        ->label('Assign tags')
                        ->icon('heroicon-o-tag')
                        ->form([
                            Select::make('tag_ids')
                                ->label('Tags')
                                ->multiple()
                                ->options(fn (): array => Tag::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $result = app(PostService::class)->bulkAssignTags(
                                $records,
                                array_values((array) ($data['tag_ids'] ?? [])),
                                auth()->user(),
                            );
                            self::notifyBulkResult('Tags assigned', $result);
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('restore')
                        ->label('Restore')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $result = app(PostService::class)->bulkRestore($records, auth()->user());
                            self::notifyBulkResult('Posts restored', $result);
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->using(function (Collection $records): void {
                            $result = app(PostService::class)->bulkTrash($records, auth()->user());
                            self::notifyBulkResult('Posts moved to trash', $result);
                        }),
                    ForceDeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->can(Permission::PostsForceDelete->value) ?? false)
                        ->using(function (Collection $records): void {
                            $result = app(PostService::class)->bulkForceDelete($records, auth()->user());
                            self::notifyBulkResult('Posts permanently deleted', $result);
                        }),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->with(['author', 'categories', 'tags', 'featuredImage']);
            });
    }

    /**
     * @param  array{success: int, failed: int}  $result
     */
    private static function notifyBulkResult(string $title, array $result): void
    {
        $success = $result['success'];
        $failed = $result['failed'];

        if ($success > 0 && $failed === 0) {
            Notification::make()
                ->success()
                ->title($title)
                ->body($success === 1 ? '1 post updated.' : "{$success} posts updated.")
                ->send();

            return;
        }

        if ($success > 0) {
            Notification::make()
                ->warning()
                ->title($title)
                ->body("{$success} succeeded, {$failed} failed.")
                ->send();

            return;
        }

        Notification::make()
            ->danger()
            ->title($title)
            ->body($failed === 1 ? '1 post could not be updated.' : "{$failed} posts could not be updated.")
            ->send();
    }
}
