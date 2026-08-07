<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SlugGenerator
{
    /**
     * Sanitize to lowercase alphanumeric characters with hyphens (SRS 12.5.1).
     */
    public static function sanitize(string $value): string
    {
        $slug = Str::slug($value);

        return $slug !== '' ? $slug : 'item';
    }

    /**
     * Ensure uniqueness by appending numeric suffixes (e.g. sample-2).
     */
    public static function unique(
        string $value,
        string $table,
        string $column = 'slug',
        ?int $ignoreId = null,
    ): string {
        $base = self::sanitize($value);
        $candidate = $base;
        $suffix = 2;

        while (self::exists($table, $column, $candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private static function exists(string $table, string $column, string $slug, ?int $ignoreId): bool
    {
        $query = DB::table($table)->where($column, $slug);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
