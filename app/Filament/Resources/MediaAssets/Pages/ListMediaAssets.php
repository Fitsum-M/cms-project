<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Enums\Permission;
use App\Filament\Resources\MediaAssets\MediaAssetResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListMediaAssets extends ListRecords
{
    protected static string $resource = MediaAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Upload Media')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(MediaAssetResource::getUrl('create'))
                ->visible(fn (): bool => auth()->user()?->can(Permission::MediaUpload->value) ?? false),
        ];
    }
}
