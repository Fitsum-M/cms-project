<?php

namespace App\Filament\Resources\MediaAssets\Tables;

use App\Filament\Resources\MediaAssets\Pages\ListMediaAssets;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\FolderService;
use App\Services\MediaDeletionService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class MediaAssetsTable
{
    public static function configure(Table $table): Table
    {
        $livewire = $table->getLivewire();
        $isGrid = $livewire instanceof ListMediaAssets && $livewire->isGridLayout();

        return $table
            ->columns($isGrid ? static::gridColumns() : static::listColumns())
            ->contentGrid($isGrid ? [
                'md' => 2,
                'xl' => 3,
                '2xl' => 4,
            ] : null)
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('folder_scope')
                    ->label('Folder')
                    ->options(function (): array {
                        return [
                            'unfiled' => 'Unfiled',
                            ...app(FolderService::class)->options(),
                        ];
                    })
                    ->query(function (Builder $query, array $data, HasTable $livewire): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        $searchAllFolders = (bool) ($livewire->getTableFilterState('search_all_folders')['isActive'] ?? false);

                        // SRS 14.11: keep folder as current context, but expand search across folders when opted in.
                        if ($searchAllFolders && $livewire->hasTableSearch()) {
                            return $query;
                        }

                        if ($value === 'unfiled') {
                            return $query->whereNull('folder_id');
                        }

                        return $query->where('folder_id', (int) $value);
                    })
                    ->searchable()
                    ->preload(),
                Filter::make('search_all_folders')
                    ->label('Search All Folders')
                    ->toggle()
                    ->indicator('Search All Folders'),
                SelectFilter::make('file_type')
                    ->label('File type')
                    ->options([
                        'image' => 'Image',
                        'document' => 'Document',
                        'archive' => 'Archive',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'image' => $query->where('mime_type', 'like', 'image/%'),
                            'document' => $query->whereIn('mime_type', MediaAsset::documentMimeTypes()),
                            'archive' => $query->whereIn('mime_type', MediaAsset::archiveMimeTypes()),
                            default => $query,
                        };
                    }),
                Filter::make('uploaded_at')
                    ->label('Upload date')
                    ->schema([
                        DatePicker::make('uploaded_from')
                            ->label('From'),
                        DatePicker::make('uploaded_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['uploaded_from'] ?? null,
                                fn (Builder $q, mixed $date): Builder => $q->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['uploaded_until'] ?? null,
                                fn (Builder $q, mixed $date): Builder => $q->whereDate('created_at', '<=', $date),
                            );
                    }),
                SelectFilter::make('uploaded_by')
                    ->label('Uploader')
                    ->options(fn (): array => User::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('moveToFolder')
                    ->label('Move')
                    ->icon('heroicon-o-folder')
                    ->form([
                        Select::make('folder_id')
                            ->label('Folder')
                            ->options(fn (): array => app(FolderService::class)->options())
                            ->searchable()
                            ->nullable()
                            ->placeholder('— Unfiled —')
                            ->default(fn (MediaAsset $record): ?int => $record->folder_id),
                    ])
                    ->action(function (MediaAsset $record, array $data): void {
                        try {
                            app(FolderService::class)->moveMedia(
                                [$record->getKey()],
                                isset($data['folder_id']) && $data['folder_id'] !== ''
                                    ? (int) $data['folder_id']
                                    : null,
                            );
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot move media')
                                ->body(collect($exception->errors())->flatten()->first() ?? 'Move blocked.')
                                ->send();

                            throw $exception;
                        }

                        Notification::make()
                            ->success()
                            ->title('Media moved')
                            ->send();
                    }),
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
                    ->visible(fn (MediaAsset $record): bool => auth()->user()?->can('forceDelete', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Force delete media')
                    ->modalDescription('This breaks existing references and leaves empty placeholders. This cannot be undone.')
                    ->action(function (MediaAsset $record): void {
                        app(MediaDeletionService::class)->forceDelete($record);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('moveToFolder')
                        ->label('Move to folder')
                        ->icon('heroicon-o-folder')
                        ->form([
                            Select::make('folder_id')
                                ->label('Folder')
                                ->options(fn (): array => app(FolderService::class)->options())
                                ->searchable()
                                ->nullable()
                                ->placeholder('— Unfiled —'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            try {
                                $moved = app(FolderService::class)->moveMedia(
                                    $records->modelKeys(),
                                    isset($data['folder_id']) && $data['folder_id'] !== ''
                                        ? (int) $data['folder_id']
                                        : null,
                                );
                            } catch (ValidationException $exception) {
                                Notification::make()
                                    ->danger()
                                    ->title('Cannot move media')
                                    ->body(collect($exception->errors())->flatten()->first() ?? 'Move blocked.')
                                    ->send();

                                throw $exception;
                            }

                            Notification::make()
                                ->success()
                                ->title($moved === 1 ? '1 item moved' : "{$moved} items moved")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->using(function (Collection $records): void {
                            $service = app(MediaDeletionService::class);
                            $blocked = 0;
                            $deleted = 0;

                            foreach ($records as $record) {
                                try {
                                    $service->delete($record);
                                    $deleted++;
                                } catch (ValidationException $exception) {
                                    $blocked++;
                                    Notification::make()
                                        ->danger()
                                        ->title("Cannot delete {$record->title}")
                                        ->body(collect($exception->errors())->flatten()->implode("\n"))
                                        ->persistent()
                                        ->send();
                                }
                            }

                            if ($deleted > 0) {
                                Notification::make()
                                    ->success()
                                    ->title($deleted === 1 ? '1 item deleted' : "{$deleted} items deleted")
                                    ->send();
                            }

                            if ($blocked > 0 && $deleted === 0) {
                                // notifications already sent per item
                            }
                        }),
                ]),
            ]);
    }

    /**
     * @return array<int, ImageColumn|TextColumn>
     */
    protected static function listColumns(): array
    {
        return [
            ImageColumn::make('preview')
                ->label('Preview')
                ->getStateUsing(fn (MediaAsset $record): ?string => $record->isImage() ? $record->previewUrl() : null)
                ->square()
                ->extraImgAttributes(['alt' => '']),
            TextColumn::make('title')
                ->label('Title')
                ->searchable(query: static::metadataSearchQuery())
                ->sortable()
                ->description(fn (MediaAsset $record): string => $record->original_file_name),
            TextColumn::make('folder.name')
                ->label('Folder')
                ->placeholder('Unfiled')
                ->sortable()
                ->toggleable(),
            TextColumn::make('mime_type')
                ->label('Type')
                ->badge()
                ->sortable()
                ->toggleable(),
            TextColumn::make('size')
                ->label('Size')
                ->formatStateUsing(fn (MediaAsset $record): string => $record->humanSize())
                ->sortable(),
            TextColumn::make('uploader.name')
                ->label('Uploader')
                ->sortable()
                ->toggleable(),
            TextColumn::make('created_at')
                ->label('Uploaded')
                ->dateTime()
                ->sortable(),
        ];
    }

    /**
     * @return array<int, Stack>
     */
    protected static function gridColumns(): array
    {
        return [
            Stack::make([
                ImageColumn::make('preview')
                    ->label('Preview')
                    ->getStateUsing(fn (MediaAsset $record): ?string => $record->isImage() ? $record->previewUrl() : null)
                    ->height(140)
                    ->extraImgAttributes(['alt' => '', 'class' => 'w-full object-cover rounded-lg']),
                TextColumn::make('title')
                    ->label('Title')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(query: static::metadataSearchQuery())
                    ->limit(40),
                TextColumn::make('original_file_name')
                    ->label('File')
                    ->color('gray')
                    ->limit(36),
                TextColumn::make('mime_type')
                    ->label('Type')
                    ->badge(),
            ])->space(2),
        ];
    }

    protected static function metadataSearchQuery(): \Closure
    {
        return function (Builder $query, string $search): Builder {
            $term = '%'.$search.'%';

            return $query->where(function (Builder $inner) use ($term): void {
                $inner
                    ->where('title', 'like', $term)
                    ->orWhere('original_file_name', 'like', $term)
                    ->orWhere('alt_text', 'like', $term)
                    ->orWhere('caption', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        };
    }
}
