<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Support\Content\ContentSearch;

/**
 * Listing load budget probes for posts/pages (SRS §6, §19.1, §20.1 — GAP.NFR.03).
 */
class ContentListingPerformanceService
{
    /** Same soft ceiling as dashboard (SRS Section 6). */
    public const MAX_LOAD_MS = 2000;

    /**
     * @return array{elapsed_ms: float, within_budget: bool}
     */
    public function measurePostsListing(): array
    {
        $started = hrtime(true);

        Post::query()
            ->with(['author', 'categories', 'tags', 'featuredImage'])
            ->latest('published_at')
            ->paginate(25);

        ContentSearch::applyPostsSearch(Post::query(), 'performance')
            ->paginate(25);

        $elapsedMs = (hrtime(true) - $started) / 1_000_000;

        return [
            'elapsed_ms' => $elapsedMs,
            'within_budget' => $elapsedMs < self::MAX_LOAD_MS,
        ];
    }

    /**
     * @return array{elapsed_ms: float, within_budget: bool}
     */
    public function measurePagesListing(): array
    {
        $started = hrtime(true);

        Page::query()
            ->with(['author', 'parent'])
            ->orderBy('sort_order')
            ->paginate(25);

        ContentSearch::applyPagesSearch(Page::query(), 'overview')
            ->paginate(25);

        $elapsedMs = (hrtime(true) - $started) / 1_000_000;

        return [
            'elapsed_ms' => $elapsedMs,
            'within_budget' => $elapsedMs < self::MAX_LOAD_MS,
        ];
    }
}
