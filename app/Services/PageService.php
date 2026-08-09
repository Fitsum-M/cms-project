<?php

namespace App\Services;

use App\Enums\ContentSlugScope;
use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\PageTemplateRegistry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pages create/update with parent hierarchy + circular-reference guard (SRS 12.3.1–12.3.3).
 */
class PageService
{
    public function __construct(
        private readonly ContentSlugService $slugs,
        private readonly ContentLifecycleService $lifecycle,
        private readonly ContentSeoService $seo,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{
     *     title: string,
     *     slug?: string|null,
     *     body?: string|null,
     *     author_id?: int|null,
     *     parent_id?: int|null,
     *     sort_order?: int|null,
     *     status?: string|ContentStatus,
     *     published_at?: mixed,
     *     confirm_slug_change?: bool,
     *     accept_conflict_resolution?: bool
     * }  $data
     */
    public function create(array $data, User $actor): Page
    {
        $page = DB::transaction(function () use ($data, $actor): Page {
            $title = trim((string) $data['title']);
            if ($title === '') {
                throw ValidationException::withMessages([
                    'title' => 'A title is required.',
                ]);
            }

            $authorId = $this->resolveAuthorId($data['author_id'] ?? null, $actor, isCreate: true);
            $parentId = $this->resolveParentId($data['parent_id'] ?? null);
            $publishedAt = $this->resolvePublishedAt($data['published_at'] ?? null, isCreate: true);
            $status = $this->resolveInitialStatus($data['status'] ?? ContentStatus::Draft, $actor);
            $template = $this->resolveTemplate($data['template'] ?? null);
            $showInNavigation = $this->resolveShowInNavigation($data['show_in_navigation'] ?? false);

            $slug = $this->slugs->resolve([
                'title' => $title,
                'slug' => $data['slug'] ?? null,
                'scope' => ContentSlugScope::Pages,
                'has_been_published' => false,
                'confirm_slug_change' => false,
                'accept_conflict_resolution' => (bool) ($data['accept_conflict_resolution'] ?? false),
            ]);

            $sortOrder = array_key_exists('sort_order', $data) && $data['sort_order'] !== null
                ? max(0, (int) $data['sort_order'])
                : $this->nextSortOrder($parentId);

            $page = Page::query()->create([
                'title' => mb_substr($title, 0, 255),
                'slug' => $slug,
                'body' => $this->nullableString($data['body'] ?? null),
                'author_id' => $authorId,
                'parent_id' => $parentId,
                'sort_order' => $sortOrder,
                'template' => $template,
                'show_in_navigation' => $showInNavigation,
                'status' => $status,
                'published_at' => $publishedAt,
            ]);

            $this->seo->sync($page, isset($data['seo']) && is_array($data['seo']) ? $data['seo'] : null, $actor);

            return $page->fresh(['author', 'parent', 'seo']) ?? $page;
        });

        $this->audit->contentChanged('created', $page, $actor);

        return $page;
    }

    /**
     * Incremental draft save — title required only (SRS 12.5.2 autosave).
     *
     * @param  array<string, mixed>  $data
     */
    public function autosaveDraft(Page $page, array $data, User $actor): Page
    {
        if ($page->trashed()) {
            throw ValidationException::withMessages([
                'status' => 'Trashed pages cannot be auto-saved.',
            ]);
        }

        if ($page->contentStatus() !== ContentStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Auto-save only applies to Draft pages.',
            ]);
        }

        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '') {
            throw ValidationException::withMessages([
                'title' => 'A title is required to auto-save a draft.',
            ]);
        }

        unset($data['status'], $data['confirm_slug_change']);
        $data['accept_conflict_resolution'] = true;

        return $this->update($page, $data, $actor);
    }

    /**
     * @param  array{
     *     title?: string,
     *     slug?: string|null,
     *     body?: string|null,
     *     author_id?: int|null,
     *     parent_id?: int|null,
     *     sort_order?: int|null,
     *     status?: string|ContentStatus,
     *     published_at?: mixed,
     *     confirm_slug_change?: bool,
     *     accept_conflict_resolution?: bool
     * }  $data
     */
    public function update(Page $page, array $data, User $actor): Page
    {
        $updated = DB::transaction(function () use ($page, $data, $actor): Page {
            if ($page->trashed()) {
                throw ValidationException::withMessages([
                    'status' => 'Trashed pages cannot be edited. Restore the page first.',
                ]);
            }

            $title = array_key_exists('title', $data)
                ? trim((string) $data['title'])
                : $page->title;

            if ($title === '') {
                throw ValidationException::withMessages([
                    'title' => 'A title is required.',
                ]);
            }

            $authorId = array_key_exists('author_id', $data)
                ? $this->resolveAuthorId($data['author_id'], $actor, isCreate: false, page: $page)
                : $page->author_id;

            $parentId = array_key_exists('parent_id', $data)
                ? $this->resolveParentId($data['parent_id'], $page)
                : $page->parent_id;

            $publishedAt = array_key_exists('published_at', $data)
                ? $this->resolvePublishedAt($data['published_at'], isCreate: false, fallback: $page->published_at)
                : $page->published_at;

            $slug = $this->slugs->resolve([
                'title' => $title,
                'slug' => array_key_exists('slug', $data) ? $data['slug'] : $page->slug,
                'scope' => ContentSlugScope::Pages,
                'ignore_id' => $page->id,
                'current_slug' => $page->slug,
                'has_been_published' => $page->hasBeenPublished(),
                'confirm_slug_change' => (bool) ($data['confirm_slug_change'] ?? false),
                'accept_conflict_resolution' => (bool) ($data['accept_conflict_resolution'] ?? false),
            ]);

            $sortOrder = array_key_exists('sort_order', $data) && $data['sort_order'] !== null
                ? max(0, (int) $data['sort_order'])
                : $page->sort_order;

            if ($parentId !== $page->parent_id && ! array_key_exists('sort_order', $data)) {
                $sortOrder = $this->nextSortOrder($parentId);
            }

            $page->fill([
                'title' => mb_substr($title, 0, 255),
                'slug' => $slug,
                'body' => array_key_exists('body', $data)
                    ? $this->nullableString($data['body'])
                    : $page->body,
                'author_id' => $authorId,
                'parent_id' => $parentId,
                'sort_order' => $sortOrder,
                'template' => array_key_exists('template', $data)
                    ? $this->resolveTemplate($data['template'])
                    : $page->template,
                'show_in_navigation' => array_key_exists('show_in_navigation', $data)
                    ? $this->resolveShowInNavigation($data['show_in_navigation'])
                    : $page->show_in_navigation,
                'published_at' => $publishedAt,
            ])->save();

            if (array_key_exists('status', $data) && $data['status'] !== null) {
                $this->applyStatusChange($page->fresh() ?? $page, $data['status'], $actor);
            }

            $page = $page->fresh() ?? $page;

            if (array_key_exists('seo', $data)) {
                $this->seo->sync($page, is_array($data['seo']) ? $data['seo'] : null, $actor);
            }

            return $page->fresh(['author', 'parent', 'children', 'seo']) ?? $page;
        });

        $this->audit->contentChanged('updated', $updated, $actor);

        return $updated;
    }

    /**
     * Nested page tree for hierarchy UI (SRS 12.3.4).
     *
     * @return list<array<string, mixed>>
     */
    public function tree(?User $viewer = null): array
    {
        $query = Page::query()
            ->orderBy('sort_order')
            ->orderBy('title');

        if ($viewer !== null && ! $viewer->can(Permission::PagesViewAll->value)) {
            $query->where('author_id', $viewer->getKey());
        }

        $pages = $query->get();

        /** @var array<string, Collection<int, Page>> $byParent */
        $byParent = $pages->groupBy(fn (Page $page): string => (string) ($page->parent_id ?? 'root'));

        $build = function (string $parentKey) use (&$build, $byParent): array {
            $nodes = [];

            foreach ($byParent[$parentKey] ?? [] as $page) {
                $status = $page->contentStatus();

                $nodes[] = [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'parent_id' => $page->parent_id,
                    'sort_order' => $page->sort_order,
                    'status' => $status->value,
                    'status_label' => $status->label(),
                    'status_color' => $status->color(),
                    'template' => $page->resolvedTemplate(),
                    'template_label' => $page->templateLabel(),
                    'template_icon' => $page->templateIcon(),
                    'show_in_navigation' => $page->isNavigationReady(),
                    'edit_url' => PageResource::getUrl('edit', ['record' => $page]),
                    'children' => $build((string) $page->id),
                ];
            }

            return $nodes;
        };

        return $build('root');
    }

    /**
     * Navigation-ready pages in hierarchy order (SRS 12.3.8).
     * Only pages with show_in_navigation=true; children kept only when flagged.
     *
     * @return list<array<string, mixed>>
     */
    public function navigationTree(): array
    {
        $pages = Page::query()
            ->where('show_in_navigation', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        /** @var array<string, Collection<int, Page>> $byParent */
        $byParent = $pages->groupBy(fn (Page $page): string => (string) ($page->parent_id ?? 'root'));

        $build = function (string $parentKey) use (&$build, $byParent): array {
            $nodes = [];

            foreach ($byParent[$parentKey] ?? [] as $page) {
                $nodes[] = [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'path' => $page->publicPath(),
                    'parent_id' => $page->parent_id,
                    'sort_order' => $page->sort_order,
                    'template' => $page->resolvedTemplate(),
                    'children' => $build((string) $page->id),
                ];
            }

            return $nodes;
        };

        // Promote orphaned nav children (parent not flagged) to root of the nav tree.
        $navIds = $pages->pluck('id')->all();
        $roots = [];

        foreach ($pages as $page) {
            if ($page->parent_id === null || ! in_array($page->parent_id, $navIds, true)) {
                $roots[] = [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'path' => $page->publicPath(),
                    'parent_id' => $page->parent_id,
                    'sort_order' => $page->sort_order,
                    'template' => $page->resolvedTemplate(),
                    'children' => $build((string) $page->id),
                ];
            }
        }

        return $roots;
    }

    /**
     * Reorder siblings under the same parent (SRS 12.3.6).
     *
     * @param  list<int|string>  $orderedIds
     */
    public function reorderSiblings(?int $parentId, array $orderedIds): void
    {
        $orderedIds = array_values(array_unique(array_map('intval', $orderedIds)));

        if ($orderedIds === []) {
            return;
        }

        DB::transaction(function () use ($parentId, $orderedIds): void {
            $siblings = Page::query()
                ->where('parent_id', $parentId)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get()
                ->keyBy('id');

            foreach ($orderedIds as $id) {
                if (! $siblings->has($id)) {
                    throw ValidationException::withMessages([
                        'order' => 'All reordered pages must share the same parent.',
                    ]);
                }
            }

            if (count($orderedIds) !== $siblings->count()) {
                // Allow partial lists: apply given order first, append remaining in prior order.
                $remaining = $siblings->keys()
                    ->reject(fn (int $id): bool => in_array($id, $orderedIds, true))
                    ->values()
                    ->all();
                $orderedIds = [...$orderedIds, ...$remaining];
            }

            foreach ($orderedIds as $index => $id) {
                Page::query()->whereKey($id)->update(['sort_order' => $index]);
            }
        });
    }

    /**
     * Move a page under a new parent (or root) and append to the end of that sibling list.
     */
    public function move(Page $page, ?int $newParentId): Page
    {
        return DB::transaction(function () use ($page, $newParentId): Page {
            $parentId = $this->resolveParentId($newParentId, $page);

            if ($parentId === $page->parent_id) {
                return $page;
            }

            $page->fill([
                'parent_id' => $parentId,
                'sort_order' => $this->nextSortOrder($parentId),
            ])->save();

            return $page->fresh(['parent', 'children']) ?? $page;
        });
    }

    /**
     * Place $dragged immediately before or after $target (reparents when needed).
     *
     * @param  'before'|'after'  $placement
     */
    public function reorderRelative(Page $dragged, Page $target, string $placement = 'before'): void
    {
        if (! in_array($placement, ['before', 'after'], true)) {
            throw ValidationException::withMessages([
                'order' => 'Placement must be before or after.',
            ]);
        }

        if ($dragged->id === $target->id) {
            return;
        }

        DB::transaction(function () use ($dragged, $target, $placement): void {
            $parentId = $target->parent_id;

            if ($dragged->wouldCreateCycle($parentId)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A page cannot be its own parent or descendant.',
                ]);
            }

            if ($dragged->parent_id !== $parentId) {
                $dragged->fill([
                    'parent_id' => $parentId,
                ])->save();
            }

            $siblingIds = Page::query()
                ->where('parent_id', $parentId)
                ->whereKeyNot($dragged->id)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->pluck('id')
                ->all();

            $ordered = [];
            $inserted = false;

            foreach ($siblingIds as $id) {
                if ($id === $target->id && $placement === 'before') {
                    $ordered[] = $dragged->id;
                    $inserted = true;
                }

                $ordered[] = $id;

                if ($id === $target->id && $placement === 'after') {
                    $ordered[] = $dragged->id;
                    $inserted = true;
                }
            }

            if (! $inserted) {
                $ordered[] = $dragged->id;
            }

            $this->reorderSiblings($parentId, $ordered);
        });
    }

    /**
     * Parent options for selects, excluding self + descendants (circular guard).
     *
     * @return array<int, string>
     */
    public function parentOptions(?int $excludePageId = null): array
    {
        $query = Page::query()
            ->orderBy('sort_order')
            ->orderBy('title');

        $excludeIds = [];
        if ($excludePageId !== null) {
            $excludeIds[] = $excludePageId;
            $page = Page::query()->find($excludePageId);
            if ($page) {
                $excludeIds = [...$excludeIds, ...$page->descendantIds()];
            }
        }

        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query
            ->with('parent')
            ->get()
            ->mapWithKeys(fn (Page $page): array => [
                $page->id => $page->hierarchicalLabel(),
            ])
            ->all();
    }

    private function resolveParentId(mixed $parentId, ?Page $page = null): ?int
    {
        if ($parentId === null || $parentId === '') {
            return null;
        }

        $parentId = (int) $parentId;

        $parent = Page::query()->find($parentId);
        if ($parent === null) {
            throw ValidationException::withMessages([
                'parent_id' => 'Selected parent page does not exist.',
            ]);
        }

        if ($page !== null && $page->wouldCreateCycle($parentId)) {
            throw ValidationException::withMessages([
                'parent_id' => 'A page cannot be its own parent or descendant.',
            ]);
        }

        return $parentId;
    }

    private function nextSortOrder(?int $parentId): int
    {
        $max = Page::query()
            ->where('parent_id', $parentId)
            ->max('sort_order');

        return $max === null ? 0 : ((int) $max) + 1;
    }

    private function applyStatusChange(Page $page, mixed $status, User $actor): void
    {
        $target = $status instanceof ContentStatus
            ? $status
            : ContentStatus::tryFrom((string) $status);

        if ($target === null || $target === $page->contentStatus()) {
            return;
        }

        match ($target) {
            ContentStatus::Draft => $page->contentStatus() === ContentStatus::Published
                ? $this->lifecycle->unpublish($page, ContentStatus::Draft)
                : ($page->contentStatus() === ContentStatus::Archived
                    ? $this->lifecycle->restore($page, $actor, ContentStatus::Draft)
                    : $this->lifecycle->saveAsDraft($page)),
            ContentStatus::PendingReview => $this->lifecycle->submitForReview($page),
            ContentStatus::Published => $page->contentStatus() === ContentStatus::Archived
                ? $this->lifecycle->restore($page, $actor, ContentStatus::Published)
                : $this->lifecycle->publish($page, $actor),
            ContentStatus::Archived => $this->lifecycle->archive($page),
        };
    }

    private function resolveInitialStatus(mixed $status, User $actor): ContentStatus
    {
        $resolved = $status instanceof ContentStatus
            ? $status
            : (ContentStatus::tryFrom((string) $status) ?? ContentStatus::Draft);

        if ($resolved === ContentStatus::Published && ! $actor->can(Permission::PagesPublish->value)) {
            return ContentStatus::PendingReview;
        }

        return $resolved;
    }

    private function resolveAuthorId(mixed $authorId, User $actor, bool $isCreate, ?Page $page = null): int
    {
        $canReassign = $actor->can(Permission::PagesEditOthers->value);

        if ($isCreate) {
            if ($authorId === null || $authorId === '' || ! $canReassign) {
                return (int) $actor->getKey();
            }

            return $this->assertActiveAuthor((int) $authorId);
        }

        /** @var Page $page */
        if (! $canReassign) {
            return (int) $page->author_id;
        }

        if ($authorId === null || $authorId === '') {
            throw ValidationException::withMessages([
                'author_id' => 'An author is required.',
            ]);
        }

        return $this->assertActiveAuthor((int) $authorId);
    }

    private function assertActiveAuthor(int $authorId): int
    {
        $user = User::query()->find($authorId);

        if ($user === null || ! $user->isActive()) {
            throw ValidationException::withMessages([
                'author_id' => 'Author must be an active user.',
            ]);
        }

        return $authorId;
    }

    private function resolvePublishedAt(mixed $value, bool $isCreate, mixed $fallback = null): ?Carbon
    {
        if ($value === null || $value === '') {
            if ($isCreate) {
                return now();
            }

            return $fallback instanceof Carbon
                ? $fallback
                : ($fallback !== null ? Carbon::parse($fallback) : null);
        }

        return Carbon::parse($value);
    }

    private function resolveTemplate(mixed $template): ?string
    {
        if ($template === null || $template === '') {
            return null;
        }

        $template = (string) $template;

        if (! PageTemplateRegistry::isValid($template)) {
            throw ValidationException::withMessages([
                'template' => 'Unknown page template.',
            ]);
        }

        // Persist null for Default so inheritance stays explicit in the DB.
        if ($template === PageTemplateRegistry::defaultKey()) {
            return null;
        }

        return $template;
    }

    private function resolveShowInNavigation(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
