<?php

namespace App\Filament\Pages\Content;

use App\Enums\Permission;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Services\PageService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page as FilamentPage;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * P.07 — Page hierarchy tree with drag-drop sibling reorder (SRS 12.3.4, 12.3.6).
 */
class PageHierarchy extends FilamentPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Page Hierarchy';

    protected static ?string $navigationParentItem = 'Pages';

    protected static ?int $navigationSort = 23;

    protected static ?string $title = 'Page Hierarchy';

    protected static ?string $slug = 'content/pages/hierarchy';

    protected string $view = 'filament.pages.content.page-hierarchy';

    /**
     * @var list<array<string, mixed>>
     */
    public array $tree = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::PagesViewOwn->value)
            || $user->can(Permission::PagesViewAll->value);
    }

    public function mount(): void
    {
        $this->refreshTree();
    }

    public function refreshTree(): void
    {
        $this->tree = app(PageService::class)->tree(auth()->user());
    }

    public function canManageTree(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::PagesEditOwn->value)
            || $user->can(Permission::PagesEditOthers->value);
    }

    /**
     * Reorder by placing dragged page before/after target (same or new parent).
     *
     * @param  'before'|'after'  $placement
     */
    public function reorderRelative(int $draggedId, int $targetId, string $placement = 'before'): void
    {
        if (! $this->canManageTree()) {
            abort(403);
        }

        $dragged = Page::query()->findOrFail($draggedId);
        $target = Page::query()->findOrFail($targetId);

        $this->authorize('update', $dragged);

        try {
            app(PageService::class)->reorderRelative($dragged, $target, $placement);
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Cannot reorder page')
                ->body(collect($exception->errors())->flatten()->first() ?? 'Reorder blocked.')
                ->send();

            $this->refreshTree();

            return;
        }

        $this->refreshTree();
    }

    /**
     * Nest page under another page (or root when $newParentId is null).
     */
    public function movePage(int $pageId, ?int $newParentId = null): void
    {
        if (! $this->canManageTree()) {
            abort(403);
        }

        $page = Page::query()->findOrFail($pageId);
        $this->authorize('update', $page);

        try {
            app(PageService::class)->move($page, $newParentId);
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Cannot move page')
                ->body(collect($exception->errors())->flatten()->first() ?? 'Move blocked.')
                ->send();

            $this->refreshTree();

            return;
        }

        Notification::make()
            ->success()
            ->title('Page moved')
            ->send();

        $this->refreshTree();
    }

    /**
     * Apply an explicit sibling order list for a parent.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorderSiblings(?int $parentId, array $orderedIds): void
    {
        if (! $this->canManageTree()) {
            abort(403);
        }

        try {
            app(PageService::class)->reorderSiblings($parentId, $orderedIds);
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Cannot reorder pages')
                ->body(collect($exception->errors())->flatten()->first() ?? 'Reorder blocked.')
                ->send();

            $this->refreshTree();

            return;
        }

        $this->refreshTree();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addPage')
                ->label('Add New Page')
                ->icon('heroicon-o-plus')
                ->url(fn (): string => PageResource::getUrl('create'))
                ->visible(fn (): bool => auth()->user()?->can(Permission::PagesCreate->value) ?? false),
        ];
    }
}
