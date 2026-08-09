<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Support\Dashboard\RecentContentItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Draft Summary data for the Dashboard (SRS 10.3 / D.03).
 *
 * - Every dashboard user sees their own Draft items.
 * - Editors/Admins with DashboardViewAllDrafts also see all Pending Review items.
 */
class DashboardDraftSummaryService
{
    public const LIMIT = 10;

    /**
     * @return array{own_drafts: Collection<int, RecentContentItem>, pending_review: Collection<int, RecentContentItem>}
     */
    public function forUser(User $user, int $limit = self::LIMIT): array
    {
        return [
            'own_drafts' => $this->ownDrafts($user, $limit),
            'pending_review' => $user->can(Permission::DashboardViewAllDrafts->value)
                ? $this->pendingReview($user, $limit)
                : collect(),
        ];
    }

    /**
     * @return Collection<int, RecentContentItem>
     */
    public function ownDrafts(User $user, int $limit = self::LIMIT): Collection
    {
        return $this->collectByStatus(
            status: ContentStatus::Draft,
            authorId: (int) $user->id,
            includePages: $this->canIncludePages($user),
            limit: $limit,
        );
    }

    /**
     * @return Collection<int, RecentContentItem>
     */
    public function pendingReview(User $user, int $limit = self::LIMIT): Collection
    {
        return $this->collectByStatus(
            status: ContentStatus::PendingReview,
            authorId: null,
            includePages: $this->canIncludePages($user),
            limit: $limit,
        );
    }

    /**
     * @return Collection<int, RecentContentItem>
     */
    private function collectByStatus(
        ContentStatus $status,
        ?int $authorId,
        bool $includePages,
        int $limit,
    ): Collection {
        $candidates = collect();

        if (Schema::hasTable('posts')) {
            $postsQuery = Post::query()
                ->select(['id', 'title', 'status', 'author_id', 'updated_at'])
                ->with(['author:id,name'])
                ->where('status', $status->value)
                ->orderByDesc('updated_at')
                ->limit($limit);

            if ($authorId !== null) {
                $postsQuery->where('author_id', $authorId);
            }

            $candidates = $candidates->concat(
                $postsQuery->get()->map(fn (Post $post): RecentContentItem => RecentContentItem::fromPost($post))
            );
        }

        if ($includePages && Schema::hasTable('pages')) {
            $pagesQuery = Page::query()
                ->select(['id', 'title', 'status', 'author_id', 'updated_at'])
                ->with(['author:id,name'])
                ->where('status', $status->value)
                ->orderByDesc('updated_at')
                ->limit($limit);

            if ($authorId !== null) {
                $pagesQuery->where('author_id', $authorId);
            }

            $candidates = $candidates->concat(
                $pagesQuery->get()->map(fn (Page $page): RecentContentItem => RecentContentItem::fromPage($page))
            );
        }

        return $candidates
            ->sortByDesc(fn (RecentContentItem $item): int => $item->updatedAt->getTimestamp())
            ->take($limit)
            ->values();
    }

    private function canIncludePages(User $user): bool
    {
        return $user->can(Permission::PagesViewAll->value)
            || $user->can(Permission::PagesViewOwn->value);
    }
}
