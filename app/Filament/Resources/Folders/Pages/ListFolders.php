<?php

namespace App\Filament\Resources\Folders\Pages;

use App\Enums\Permission;
use App\Filament\Resources\Folders\FolderResource;
use App\Models\Folder;
use App\Services\FolderService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

/**
 * Decision (Step 2.3): keep tree + drag-drop UX on ListFolders.
 * Create / Edit / View stay as standard Resource pages; table CRUD remains available via those routes.
 */
class ListFolders extends ListRecords
{
    protected static string $resource = FolderResource::class;

    protected string $view = 'filament.resources.folders.pages.list-folders';

    /**
     * @var list<array<string, mixed>>
     */
    public array $tree = [];

    public function mount(): void
    {
        parent::mount();

        $this->refreshTree();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Folders';
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
            CreateAction::make()
                ->label('New Folder')
                ->icon('heroicon-o-folder-plus')
                ->visible(fn (): bool => auth()->user()?->can(Permission::MediaUpload->value) ?? false),
        ];
    }

    public function deleteFolderAction(): Action
    {
        return Action::make('deleteFolder')
            ->label('Delete')
            ->link()
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
