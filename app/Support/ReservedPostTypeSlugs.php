<?php

namespace App\Support;

/**
 * Slugs reserved for system routes and the built-in post type (SRS 12.4.3).
 */
final class ReservedPostTypeSlugs
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
            'types',
            'user',
            'users',
        ];
    }

    public static function isReserved(string $slug): bool
    {
        return in_array(mb_strtolower($slug), self::all(), true);
    }
}
