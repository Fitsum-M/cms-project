<?php

namespace App\Filament\Pages\Content;

use App\Enums\Permission;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Services\PageService;
use BackedEnum;
use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use SolutionForest\FilamentTree\Actions\Action as TreeAction;
use SolutionForest\FilamentTree\Pages\TreePage;
use UnitEnum;

/**
 * Page hierarchy via solution-forest/filament-tree (Review Comment #3 §3.2 — Option A).
 * Keeps CMS null-root parent_id / sort_order conventions; does not use ModelTree
 * (avoids cascade-delete and sort_order=0 rewrite side effects).
 */
class PageHierarchy extends TreePage
{
    protected static string $model = Page::class;

    protected static int $maxDepth = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Page Hierarchy';

    protected static ?string $navigationParentItem = 'Pages';

    protected static ?int $navigationSort = 23;

    protected static ?string $title = 'Page Hierarchy';

    protected static ?string $slug = 'content/pages/hierarchy';

    protected ?string $heading = 'Page Hierarchy';

    protected ?string $subheading = 'Drag pages to nest or reorder. Changes save automatically.';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::PagesViewOwn->value)
            || $user->can(Permission::PagesViewAll->value);
    }

    public function getModel(): string
    {
        return Page::class;
    }

    protected function hasCreateAction(): bool
    {
        return false;
    }

    protected function hasDeleteAction(): bool
    {
        return false;
    }

    protected function hasEditAction(): bool
    {
        return false;
    }

    protected function hasViewAction(): bool
    {
        return false;
    }

    protected function getTreeActions(): array
    {
        return [
            TreeAction::make('edit')
                ->label('Edit')
                ->icon('heroicon-m-pencil-square')
                ->url(fn (?Model $record): ?string => $record instanceof Page
                    ? PageResource::getUrl('edit', ['record' => $record])
                    : null)
                ->visible(fn (?Model $record): bool => $record instanceof Page
                    && (auth()->user()?->can('update', $record) ?? false)),
        ];
    }

    protected function getTreeQuery(): Builder
    {
        $query = Page::query()->orderBy('sort_order')->orderBy('title');

        $user = auth()->user();

        if ($user !== null && ! $user->can(Permission::PagesViewAll->value)) {
            $query->where('author_id', $user->getKey());
        }

        return $query;
    }

    public function getTreeRecordTitle(?Model $record = null): string
    {
        return $record instanceof Page ? (string) $record->title : '';
    }

    public function getTreeRecordDescription(?Model $record = null): string|HtmlString|null
    {
        if (! $record instanceof Page) {
            return null;
        }

        return implode(' · ', [
            $record->contentStatus()->label(),
            $record->isNavigationReady() ? 'In nav' : 'Hidden from nav',
        ]);
    }

    public function getTreeRecordIcon(?Model $record = null): ?string
    {
        return $record instanceof Page ? $record->templateIcon() : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            FilamentAction::make('addPage')
                ->label('Add New Page')
                ->icon('heroicon-o-plus')
                ->url(fn (): string => PageResource::getUrl('create'))
                ->visible(fn (): bool => auth()->user()?->can(Permission::PagesCreate->value) ?? false),
        ];
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
     * Persist nestable drag-and-drop via PageService rules (cycle checks, null roots).
     *
     * @param  array<int, array<string, mixed>>|null  $list
     * @return array{reload: bool}
     */
    public function updateTree(?array $list = null): array
    {
        if (! $this->canManageTree()) {
            abort(403);
        }

        if ($list === null || $list === []) {
            return ['reload' => false];
        }

        $needReload = false;
        $records = $this->getRecords()?->keyBy(fn (Page $record): int => (int) $record->getKey()) ?? collect();
        $flat = [];
        $this->flattenTreePayload($flat, $list, null);
        $service = app(PageService::class);

        try {
            DB::transaction(function () use ($flat, $records, $service, &$needReload): void {
                foreach ($flat as $id => $data) {
                    /** @var Page|null $page */
                    $page = $records->get((int) $id);

                    if (! $page instanceof Page) {
                        continue;
                    }

                    $this->authorize('update', $page);

                    $newParentId = $data['parent_id'];
                    $newOrder = (int) $data['order'];

                    if ($page->parent_id !== $newParentId) {
                        $service->move($page, $newParentId);
                        $page = $page->fresh() ?? $page;
                        $needReload = true;
                    }

                    if ((int) $page->sort_order !== $newOrder) {
                        $page->forceFill(['sort_order' => $newOrder])->save();
                        $needReload = true;
                    }
                }
            });
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Cannot update page hierarchy')
                ->body(collect($exception->errors())->flatten()->first() ?? 'Hierarchy update blocked.')
                ->send();

            $this->records = null;
            $this->dispatch('refreshTree');

            return ['reload' => true];
        }

        if ($needReload) {
            Notification::make()
                ->success()
                ->title('Page hierarchy saved')
                ->send();

            $this->records = null;
            $this->dispatch('refreshTree');
        }

        return ['reload' => $needReload];
    }

    /**
     * @param  array<int|string, array{parent_id: int|null, order: int}>  $result
     * @param  array<int, array<string, mixed>>  $current
     */
    private function flattenTreePayload(array &$result, array $current, int|string|null $parent): void
    {
        foreach ($current as $index => $item) {
            $key = data_get($item, 'id');

            if ($key === null) {
                continue;
            }

            $parentId = null;

            if ($parent !== null && $parent !== '' && $parent !== false) {
                $parentId = is_numeric($parent) ? (int) $parent : null;
            }

            $result[$key] = [
                'parent_id' => $parentId,
                'order' => (int) $index,
            ];

            $children = data_get($item, 'children', []);

            if (is_array($children) && $children !== []) {
                $this->flattenTreePayload($result, $children, $key);
            }
        }
    }
}
