<?php

namespace App\Support;

use App\Models\PostType;
use Illuminate\Support\Facades\Schema;

final class PostTypeRegistry
{
    public const STANDARD_KEY = 'post';

    /**
     * Built-in + registered custom post type keys (SRS 12.4 / 13.3).
     *
     * @return array<string, string> slug => plural label
     */
    public static function options(): array
    {
        $options = [
            self::STANDARD_KEY => 'Posts (standard)',
        ];

        foreach (self::customTypes() as $type) {
            $options[$type->slug] = $type->plural_name;
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::options());
    }

    public static function label(string $key): string
    {
        return self::options()[$key] ?? $key;
    }

    public static function singularLabel(string $key): string
    {
        if ($key === self::STANDARD_KEY) {
            return 'Post';
        }

        $type = self::find($key);

        return $type?->singular_name ?? $key;
    }

    public static function supportsCategories(string $key): bool
    {
        if ($key === self::STANDARD_KEY) {
            return true;
        }

        return (bool) (self::find($key)?->supports_categories ?? false);
    }

    public static function supportsTags(string $key): bool
    {
        if ($key === self::STANDARD_KEY) {
            return true;
        }

        return (bool) (self::find($key)?->supports_tags ?? false);
    }

    /**
     * @return list<int>
     */
    public static function customTaxonomyIds(string $key): array
    {
        if ($key === self::STANDARD_KEY) {
            return self::customTaxonomyIdsForKey(self::STANDARD_KEY);
        }

        $type = self::find($key);

        return $type?->customTaxonomyIds() ?? [];
    }

    public static function supportsAnyTaxonomy(string $key): bool
    {
        return self::supportsCategories($key)
            || self::supportsTags($key)
            || self::customTaxonomyIds($key) !== [];
    }

    public static function find(string $slug): ?PostType
    {
        if (! self::tableReady() || $slug === self::STANDARD_KEY) {
            return null;
        }

        return PostType::query()->where('slug', $slug)->first();
    }

    public static function isRegistered(string $slug): bool
    {
        return in_array($slug, self::keys(), true);
    }

    public static function isCustom(string $slug): bool
    {
        return $slug !== self::STANDARD_KEY && self::find($slug) !== null;
    }

    /**
     * @return list<PostType>
     */
    public static function customTypes(): array
    {
        if (! self::tableReady()) {
            return [];
        }

        return PostType::query()
            ->orderBy('sort_order')
            ->orderBy('plural_name')
            ->get()
            ->all();
    }

    public static function tableReady(): bool
    {
        try {
            return Schema::hasTable('post_types');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<int>
     */
    private static function customTaxonomyIdsForKey(string $key): array
    {
        if (! Schema::hasTable('custom_taxonomy_post_type')) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('custom_taxonomy_post_type')
            ->where('post_type_key', $key)
            ->orderBy('custom_taxonomy_id')
            ->pluck('custom_taxonomy_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
