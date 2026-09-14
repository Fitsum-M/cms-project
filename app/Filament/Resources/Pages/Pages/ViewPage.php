<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Navigation\AdminBreadcrumbs;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Services\FrontendContentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPage extends ViewRecord
{
    protected static string $resource = PageResource::class;

    /**
     * @return array<int|string, string>
     */
    public function getBreadcrumbs(): array
    {
        /** @var Page $record */
        $record = $this->getRecord();
        $record->loadMissing('parent.parent.parent.parent');

        return AdminBreadcrumbs::insertAfter(
            parent::getBreadcrumbs(),
            $this->getResourceUrl(),
            AdminBreadcrumbs::pageAncestors($record),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openOnSite')
                ->label('Open on site')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): string => $this->getRecord()->publicUrl())
                ->openUrlInNewTab()
                ->visible(function (): bool {
                    /** @var Page $record */
                    $record = $this->getRecord();

                    return ! $record->trashed()
                        && app(FrontendContentService::class)->isPublicPage($record);
                }),
            EditAction::make()
                ->visible(fn (): bool => ! $this->getRecord()->trashed()),
        ];
    }
}
