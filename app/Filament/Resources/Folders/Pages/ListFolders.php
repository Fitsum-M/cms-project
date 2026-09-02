<?php

namespace App\Filament\Resources\Folders\Pages;

use App\Enums\Permission;
use App\Filament\Resources\Folders\FolderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFolders extends ListRecords
{
    protected static string $resource = FolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Folder')
                ->icon('heroicon-o-folder-plus')
                ->visible(fn (): bool => auth()->user()?->can(Permission::MediaUpload->value) ?? false),
        ];
    }
}
