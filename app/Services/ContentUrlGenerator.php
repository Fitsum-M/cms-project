<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
use App\Support\Settings\PermalinkSettings;
use Carbon\CarbonInterface;

/**
 * Builds public content paths from Permalink settings (SRS 12.5.1 / 12.2.18 / 12.3).
 */
class ContentUrlGenerator
{
    public function __construct(
        private readonly PermalinkSettings $permalinks,
    ) {}

    public function postPath(Post $post): string
    {
        $publishedAt = $post->published_at;

        return $this->permalinks->buildPostPath([
            'slug' => $post->slug,
            'post_type' => $post->post_type ?: 'post',
            'year' => $this->datePart($publishedAt, 'Y'),
            'month' => $this->datePart($publishedAt, 'm'),
            'day' => $this->datePart($publishedAt, 'd'),
        ]);
    }

    public function pagePath(Page $page): string
    {
        return $this->permalinks->buildPagePath([
            'slug' => $page->slug,
            'parent_slug' => $this->parentSlugPrefix($page),
        ]);
    }

    /**
     * Parent path segment(s) for nested page URLs when structure uses {parent-slug}.
     */
    public function parentSlugPrefix(Page $page): ?string
    {
        if ($page->parent_id === null) {
            return null;
        }

        $segments = [];
        $current = $page->parent;

        while ($current !== null) {
            array_unshift($segments, $current->slug);
            $current = $current->parent;
        }

        if ($segments === []) {
            return null;
        }

        return implode('/', $segments);
    }

    private function datePart(?CarbonInterface $date, string $format): string
    {
        if ($date === null) {
            return match ($format) {
                'Y' => date('Y'),
                'm' => date('m'),
                'd' => date('d'),
                default => '',
            };
        }

        return $date->format($format);
    }
}
