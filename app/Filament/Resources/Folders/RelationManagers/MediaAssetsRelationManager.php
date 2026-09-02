<?php

namespace App\Filament\Resources\Folders\RelationManagers;

use App\Enums\Permission;
use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Models\Folder;
use App\Models\MediaAsset;
use App\Services\FolderService;
use App\Services\MediaDeletionService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Media assets inside a folder (Step 2.4).
 * Create/upload stays on MediaAssetResource create (Upload Media); this manager lists, opens, moves, and deletes.
 */
class MediaAssetsRelationManager extends RelationManager
{
    protected static string $relationship = 'mediaAssets';

    protected static ?string $title = 'Media files';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can(Permission::MediaView->value) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                ImageColumn::make('preview')
                    ->label('Preview')
                    ->getStateUsing(fn (MediaAsset $record): ?string => $record->isImage() ? $record->previewUrl() : null)
                    ->square()
                    ->extraImgAttributes(['alt' => '']),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $term = '%'.$search.'%';

                        return $query->where(function (Builder $inner) use ($term): void {
                            $inner
                                ->where('title', 'like', $term)
                                ->orWhere('original_file_name', 'like', $term)
                                ->orWhere('alt_text', 'like', $term);
                        });
                    })
                    ->sortable()
                    ->description(fn (MediaAsset $record): string => $record->original_file_name),
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
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Action::make('addExistingMedia')
                    ->label('Add existing media')
                    ->icon('heroicon-o-plus')
                    ->visible(fn (): bool => $this->canManageMediaInFolder())
                    ->form([
                        Select::make('media_asset_ids')
                            ->label('Media')
                            ->multiple()
                            ->searchable()
                            ->required()
                            ->options(fn (): array => $this->addableMediaOptions())
                            ->helperText('Moves selected library items into this folder.'),
                    ])
                    ->action(function (array $data): void {
                        $this->moveSelectedMediaIntoFolder(
                            array_map('intval', $data['media_asset_ids'] ?? []),
                        );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (MediaAsset $record): string => MediaAssetResource::getUrl('view', ['record' => $record]))
                    ->visible(fn (MediaAsset $record): bool => auth()->user()?->can('view', $record) ?? false),
                EditAction::make()
                    ->url(fn (MediaAsset $record): string => MediaAssetResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn (MediaAsset $record): bool => auth()->user()?->can('update', $record) ?? false),
                Action::make('moveToFolder')
                    ->label('Move')
                    ->icon('heroicon-o-folder')
                    ->color('gray')
                    ->visible(fn (MediaAsset $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->form([
                        Select::make('folder_id')
                            ->label('Folder')
                            ->options(fn (): array => app(FolderService::class)->options())
                            ->searchable()
                            ->nullable()
                            ->placeholder('— Unfiled —')
                            ->default(fn (): ?int => $this->getOwnerRecord()->getKey()),
                    ])
                    ->action(function (MediaAsset $record, array $data): void {
                        $this->moveMediaRecords(
                            [$record->getKey()],
                            filled($data['folder_id'] ?? null) ? (int) $data['folder_id'] : null,
                        );
                    }),
                DeleteAction::make()
                    ->visible(fn (MediaAsset $record): bool => auth()->user()?->can('delete', $record) ?? false)
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('moveToFolder')
                        ->label('Move to folder')
                        ->icon('heroicon-o-folder')
                        ->visible(fn (): bool => $this->canManageMediaInFolder())
                        ->form([
                            Select::make('folder_id')
                                ->label('Folder')
                                ->options(fn (): array => app(FolderService::class)->options())
                                ->searchable()
                                ->nullable()
                                ->placeholder('— Unfiled —'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $this->moveMediaRecords(
                                $records->modelKeys(),
                                filled($data['folder_id'] ?? null) ? (int) $data['folder_id'] : null,
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->can(Permission::MediaDelete->value) ?? false)
                        ->using(function (Collection $records): void {
                            $service = app(MediaDeletionService::class);
                            $deleted = 0;

                            foreach ($records as $record) {
                                if (! (auth()->user()?->can('delete', $record) ?? false)) {
                                    continue;
                                }

                                try {
                                    $service->delete($record);
                                    $deleted++;
                                } catch (ValidationException $exception) {
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
                        }),
                ]),
            ])
            ->emptyStateHeading('No media in this folder')
            ->emptyStateDescription('Upload new files from Upload Media (Library → Upload), or add existing library items here.')
            ->recordUrl(fn (MediaAsset $record): ?string => auth()->user()?->can('view', $record)
                ? MediaAssetResource::getUrl(
                    auth()->user()?->can('update', $record) ? 'edit' : 'view',
                    ['record' => $record],
                )
                : null);
    }

    private function canManageMediaInFolder(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::MediaUpload->value)
            || $user->can(Permission::MediaEditOwn->value)
            || $user->can(Permission::MediaEditOthers->value);
    }

    /**
     * @return array<int, string>
     */
    private function addableMediaOptions(): array
    {
        /** @var Folder $folder */
        $folder = $this->getOwnerRecord();

        return MediaAsset::query()
            ->where(function (Builder $query) use ($folder): void {
                $query->whereNull('folder_id')
                    ->orWhere('folder_id', '!=', $folder->getKey());
            })
            ->orderBy('title')
            ->limit(200)
            ->get()
            ->mapWithKeys(fn (MediaAsset $asset): array => [
                $asset->id => trim($asset->title.' ('.$asset->original_file_name.')'),
            ])
            ->all();
    }

    /**
     * @param  list<int>  $mediaAssetIds
     */
    private function moveSelectedMediaIntoFolder(array $mediaAssetIds): void
    {
        /** @var Folder $folder */
        $folder = $this->getOwnerRecord();

        $this->moveMediaRecords($mediaAssetIds, $folder->getKey());
    }

    /**
     * @param  list<int>  $mediaAssetIds
     */
    private function moveMediaRecords(array $mediaAssetIds, ?int $folderId): void
    {
        try {
            $moved = app(FolderService::class)->moveMedia($mediaAssetIds, $folderId);
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
    }
}
