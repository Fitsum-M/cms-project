<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Enums\Permission;
use App\Filament\Pages\Dam\UploadMedia;
use App\Filament\Resources\MediaAssets\MediaAssetResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListMediaAssets extends ListRecords
{
    public const LAYOUT_SESSION_KEY = 'media_library_layout';

    public const LAYOUT_LIST = 'list';

    public const LAYOUT_GRID = 'grid';

    protected static string $resource = MediaAssetResource::class;

    public string $libraryLayout = self::LAYOUT_LIST;

    public function mount(): void
    {
        parent::mount();

        $layout = session(self::LAYOUT_SESSION_KEY, self::LAYOUT_LIST);

        $this->libraryLayout = in_array($layout, [self::LAYOUT_LIST, self::LAYOUT_GRID], true)
            ? $layout
            : self::LAYOUT_LIST;
    }

    public function setLibraryLayout(string $layout): void
    {
        if (! in_array($layout, [self::LAYOUT_LIST, self::LAYOUT_GRID], true)) {
            return;
        }

        $this->libraryLayout = $layout;
        session([self::LAYOUT_SESSION_KEY => $layout]);

        $this->table = $this->table($this->makeTable());
    }

    public function isGridLayout(): bool
    {
        return $this->libraryLayout === self::LAYOUT_GRID;
    }

    public function isListLayout(): bool
    {
        return $this->libraryLayout === self::LAYOUT_LIST;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('listView')
                    ->label('List')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->action(fn (): mixed => $this->setLibraryLayout(self::LAYOUT_LIST))
                    ->color(fn (): string => $this->isListLayout() ? 'primary' : 'gray'),
                Action::make('gridView')
                    ->label('Grid')
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->action(fn (): mixed => $this->setLibraryLayout(self::LAYOUT_GRID))
                    ->color(fn (): string => $this->isGridLayout() ? 'primary' : 'gray'),
            ])
                ->label('View')
                ->buttonGroup(),
            Action::make('upload')
                ->label('Upload Media')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(UploadMedia::getUrl())
                ->visible(fn (): bool => auth()->user()?->can(Permission::MediaUpload->value) ?? false),
        ];
    }
}
