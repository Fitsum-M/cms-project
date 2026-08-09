<?php

namespace App\Support\Content;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Post/page listing search with MySQL FULLTEXT when available (GAP.NFR.01).
 */
class ContentSearch
{
    public const MIN_FULLTEXT_LENGTH = 3;

    /**
     * @param  Builder<\App\Models\Post>  $query
     * @return Builder<\App\Models\Post>
     */
    public static function applyPostsSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        if (static::canUseFullText($search)) {
            return $query
                ->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->whereFullText(['title', 'excerpt', 'body'], $search)
                        ->orWhere('slug', 'like', '%'.$search.'%');
                })
                ->reorder()
                ->orderByRaw(
                    'MATCH(title, excerpt, body) AGAINST(? IN NATURAL LANGUAGE MODE) DESC',
                    [$search],
                )
                ->orderByDesc('published_at');
        }

        $term = '%'.$search.'%';

        return $query
            ->where(function (Builder $inner) use ($term): void {
                $inner
                    ->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('excerpt', 'like', $term)
                    ->orWhere('body', 'like', $term);
            })
            ->reorder()
            ->orderByRaw(
                'CASE
                    WHEN title LIKE ? THEN 0
                    WHEN slug LIKE ? THEN 1
                    WHEN excerpt LIKE ? THEN 2
                    ELSE 3
                END',
                [$term, $term, $term],
            )
            ->orderByDesc('published_at');
    }

    /**
     * @param  Builder<\App\Models\Page>  $query
     * @return Builder<\App\Models\Page>
     */
    public static function applyPagesSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        if (static::canUseFullText($search)) {
            return $query
                ->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->whereFullText(['title', 'body'], $search)
                        ->orWhere('slug', 'like', '%'.$search.'%');
                })
                ->reorder()
                ->orderByRaw(
                    'MATCH(title, body) AGAINST(? IN NATURAL LANGUAGE MODE) DESC',
                    [$search],
                )
                ->orderByDesc('updated_at');
        }

        $term = '%'.$search.'%';

        return $query
            ->where(function (Builder $inner) use ($term): void {
                $inner
                    ->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('body', 'like', $term);
            })
            ->reorder()
            ->orderByRaw(
                'CASE
                    WHEN title LIKE ? THEN 0
                    WHEN slug LIKE ? THEN 1
                    ELSE 2
                END',
                [$term, $term],
            )
            ->orderByDesc('updated_at');
    }

    public static function canUseFullText(string $search): bool
    {
        return DB::connection()->getDriverName() === 'mysql'
            && mb_strlen(trim($search)) >= static::MIN_FULLTEXT_LENGTH;
    }

    public static function booleanModeTerm(string $search): string
    {
        $parts = preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return collect($parts)
            ->map(static function (string $word): string {
                $sanitized = preg_replace('/[+\-><()~*"@]+/u', '', $word) ?? '';

                return $sanitized === '' ? '' : '+'.$sanitized.'*';
            })
            ->filter()
            ->implode(' ');
    }
}
