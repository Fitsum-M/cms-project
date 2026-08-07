<?php

namespace App\Support;

/**
 * Slugs reserved for system taxonomies and routes (SRS 13.3.3).
 */
final class ReservedTaxonomySlugs
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            'admin',
            'api',
            'categories',
            'category',
            'login',
            'media',
            'page',
            'pages',
            'post',
            'posts',
            'tag',
            'tags',
            'taxonomies',
            'taxonomy',
            'user',
            'users',
        ];
    }

    public static function isReserved(string $slug): bool
    {
        return in_array(mb_strtolower($slug), self::all(), true);
    }
}
