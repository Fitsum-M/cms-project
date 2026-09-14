<?php

namespace App\Filament\Resources\Folders\Pages;

use App\Filament\Navigation\AdminBreadcrumbs;
use App\Filament\Resources\Folders\FolderResource;
use App\Models\Folder;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFolder extends ViewRecord
{
    protected static string $resource = FolderResource::class;

    /**
     * @return array<int|string, string>
     */
    public function getBreadcrumbs(): array
    {
        /** @var Folder $record */
        $record = $this->getRecord();
        $record->loadMissing('parent.parent.parent.parent');

        return AdminBreadcrumbs::insertAfter(
            parent::getBreadcrumbs(),
            $this->getResourceUrl(),
            AdminBreadcrumbs::folderAncestors($record),
        );
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->getRecord()->loadMissing(['parent'])->loadCount(['children', 'mediaAssets']);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->can('update', $this->getRecord()) ?? false),
        ];
    }
}
