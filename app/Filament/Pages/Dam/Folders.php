<?php

namespace App\Filament\Pages\Dam;

use App\Enums\Permission;
use App\Models\Folder;
use App\Services\FolderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * M.04 — Folder organization (nested, drag-drop move).
 */
class Folders extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Digital Asset Management';

    protected static ?string $navigationLabel = 'Folders';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Folders';

    protected static ?string $slug = 'dam/folders';

    protected string $view = 'filament.pages.dam.folders';

    /**
     * @var list<array<string, mixed>>
     */
    public array $tree = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::MediaView->value) ?? false;
    }

    public function mount(): void
    {
        $this->refreshTree();
    }

    public function refreshTree(): void
    {
        $this->tree = app(FolderService::class)->tree();
    }

    public function canManageFolders(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::MediaUpload->value)
            || $user->can(Permission::MediaEditOwn->value)
            || $user->can(Permission::MediaEditOthers->value);
    }

    public function moveFolder(int $folderId, ?int $newParentId = null): void
    {
        $folder = Folder::query()->findOrFail($folderId);
        $this->authorize('update', $folder);

        try {
            app(FolderService::class)->move($folder, $newParentId);
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Cannot move folder')
                ->body(collect($exception->errors())->flatten()->first() ?? 'Move blocked.')
                ->send();

            $this->refreshTree();

            return;
        }

        Notification::make()
            ->success()
            ->title('Folder moved')
            ->send();

        $this->refreshTree();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createFolder')
                ->label('New Folder')
                ->icon('heroicon-o-folder-plus')
                ->visible(fn (): bool => auth()->user()?->can(Permission::MediaUpload->value) ?? false)
                ->form([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                    Select::make('parent_id')
                        ->label('Parent folder')
                        ->options(fn (): array => app(FolderService::class)->parentOptions())
                        ->searchable()
                        ->nullable()
                        ->placeholder('— Root level —'),
                ])
                ->action(function (array $data): void {
                    $this->authorize('create', Folder::class);

                    try {
                        app(FolderService::class)->create([
                            'name' => $data['name'],
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

                    Notification::make()
                        ->success()
                        ->title('Folder created')
                        ->send();

                    $this->refreshTree();
                }),
        ];
    }

    public function renameFolderAction(): Action
    {
        return Action::make('renameFolder')
            ->label('Rename')
            ->iconButton()
            ->icon('heroicon-o-pencil-square')
            ->visible(fn (): bool => $this->canManageFolders())
            ->fillForm(function (array $arguments): array {
                $folder = Folder::query()->findOrFail($arguments['folder']);

                return [
                    'name' => $folder->name,
                    'parent_id' => $folder->parent_id,
                ];
            })
            ->form(function (Action $action): array {
                $folderId = (int) ($action->getArguments()['folder'] ?? 0);

                return [
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                    Select::make('parent_id')
                        ->label('Parent folder')
                        ->options(fn (): array => app(FolderService::class)->parentOptions($folderId ?: null))
                        ->searchable()
                        ->nullable()
                        ->placeholder('— Root level —'),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $folder = Folder::query()->findOrFail($arguments['folder']);
                $this->authorize('update', $folder);

                try {
                    app(FolderService::class)->update($folder, [
                        'name' => $data['name'],
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

                Notification::make()
                    ->success()
                    ->title('Folder updated')
                    ->send();

                $this->refreshTree();
            });
    }

    public function deleteFolderAction(): Action
    {
        return Action::make('deleteFolder')
            ->label('Delete')
            ->iconButton()
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (): bool => auth()->user()?->can(Permission::MediaDelete->value) ?? false)
            ->form(function (Action $action): array {
                $folder = Folder::query()->findOrFail($action->getArguments()['folder']);

                if ($folder->isEmpty()) {
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
            ->modalDescription(function (Action $action): string {
                $folder = Folder::query()->findOrFail($action->getArguments()['folder']);

                if ($folder->isEmpty()) {
                    return "Delete empty folder “{$folder->name}”?";
                }

                return "Folder “{$folder->name}” is not empty. Confirm recursive deletion to continue.";
            })
            ->action(function (array $data, array $arguments): void {
                $folder = Folder::query()->findOrFail($arguments['folder']);
                $this->authorize('delete', $folder);

                $recursive = $folder->isEmpty() || (bool) ($data['recursive'] ?? false);

                try {
                    app(FolderService::class)->delete($folder, $recursive);
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

                $this->refreshTree();
            });
    }
}
