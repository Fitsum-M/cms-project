<?php

namespace App\Services;

use App\Enums\Permission;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Support\Dashboard\RecentContentItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Last-edited posts & pages for the Dashboard Recent Content widget (SRS 10.3 / D.02).
 */
class DashboardRecentContentService
{
    public const LIMIT = 10;

    /**
     * @return Collection<int, RecentContentItem>
     */
    public function forUser(User $user, int $limit = self::LIMIT): Collection
    {
        $seeAll = $user->can(Permission::DashboardViewRecentAll->value);

        $candidates = collect();

        if (Schema::hasTable('posts')) {
            $postsQuery = Post::query()
                ->select(['id', 'title', 'status', 'author_id', 'updated_at'])
                ->with(['author:id,name'])
                ->orderByDesc('updated_at')
                ->limit($limit);

            if (! $seeAll) {
                $postsQuery->where('author_id', $user->id);
            }

            $candidates = $candidates->concat(
                $postsQuery->get()->map(fn (Post $post): RecentContentItem => RecentContentItem::fromPost($post))
            );
        }

        if ($this->canIncludePages($user) && Schema::hasTable('pages')) {
            $pagesQuery = Page::query()
                ->select(['id', 'title', 'status', 'author_id', 'updated_at'])
                ->with(['author:id,name'])
                ->orderByDesc('updated_at')
                ->limit($limit);

            if (! $seeAll) {
                $pagesQuery->where('author_id', $user->id);
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
