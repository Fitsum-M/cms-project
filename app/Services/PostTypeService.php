<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostType;
use App\Support\ReservedPostTypeSlugs;
use App\Support\Settings\PermalinkSettings;
use App\Support\SlugGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Custom post type registration + taxonomy associations (SRS 12.4.1–12.4.4).
 */
class PostTypeService
{
    public function __construct(
        private readonly PermalinkSettings $permalinks,
    ) {}

    /**
     * @param  array{
     *     plural_name: string,
     *     singular_name: string,
     *     slug?: string|null,
     *     icon?: string|null,
     *     supports_categories?: bool,
     *     supports_tags?: bool,
     *     custom_taxonomy_ids?: list<int|string>,
     *     sort_order?: int|null
     * }  $data
     */
    public function create(array $data): PostType
    {
        $plural = $this->requiredName($data['plural_name'] ?? null, 'plural_name');
        $singular = $this->requiredName($data['singular_name'] ?? null, 'singular_name');
        $slug = $this->resolveSlug($plural, $data['slug'] ?? null);
        $this->assertSlugAllowed($slug);
        $icon = $this->normalizeIcon($data['icon'] ?? null);
        $sortOrder = max(0, (int) ($data['sort_order'] ?? $this->nextSortOrder()));
        $supportsCategories = array_key_exists('supports_categories', $data)
            ? (bool) $data['supports_categories']
            : true;
        $supportsTags = array_key_exists('supports_tags', $data)
            ? (bool) $data['supports_tags']
            : true;
        $customTaxonomyIds = $this->normalizeCustomTaxonomyIds($data['custom_taxonomy_ids'] ?? []);

        return DB::transaction(function () use (
            $plural,
            $singular,
            $slug,
            $icon,
            $sortOrder,
            $supportsCategories,
            $supportsTags,
            $customTaxonomyIds,
        ): PostType {
            $postType = PostType::query()->create([
                'plural_name' => $plural,
                'singular_name' => $singular,
                'slug' => $slug,
                'icon' => $icon,
                'supports_categories' => $supportsCategories,
                'supports_tags' => $supportsTags,
                'sort_order' => $sortOrder,
            ]);

            $this->syncCustomTaxonomies($postType->slug, $customTaxonomyIds);

            return $postType->fresh() ?? $postType;
        });
    }

    /**
     * @param  array{
     *     plural_name?: string,
     *     singular_name?: string,
     *     slug?: string|null,
     *     icon?: string|null,
     *     supports_categories?: bool,
     *     supports_tags?: bool,
     *     custom_taxonomy_ids?: list<int|string>,
     *     sort_order?: int|null
     * }  $data
     */
    public function update(PostType $postType, array $data): PostType
    {
        $plural = array_key_exists('plural_name', $data)
            ? $this->requiredName($data['plural_name'], 'plural_name')
            : $postType->plural_name;

        $singular = array_key_exists('singular_name', $data)
            ? $this->requiredName($data['singular_name'], 'singular_name')
            : $postType->singular_name;

        $slugInput = array_key_exists('slug', $data) ? $data['slug'] : $postType->slug;
        $slug = $this->resolveSlug($plural, $slugInput, $postType->id);
        $this->assertSlugAllowed($slug, $postType->id);

        $icon = array_key_exists('icon', $data)
            ? $this->normalizeIcon($data['icon'])
            : $postType->icon;

        $sortOrder = array_key_exists('sort_order', $data)
            ? max(0, (int) $data['sort_order'])
            : $postType->sort_order;

        $supportsCategories = array_key_exists('supports_categories', $data)
            ? (bool) $data['supports_categories']
            : (bool) $postType->supports_categories;

        $supportsTags = array_key_exists('supports_tags', $data)
            ? (bool) $data['supports_tags']
            : (bool) $postType->supports_tags;

        $syncCustomTaxonomies = array_key_exists('custom_taxonomy_ids', $data);
        $customTaxonomyIds = $syncCustomTaxonomies
            ? $this->normalizeCustomTaxonomyIds($data['custom_taxonomy_ids'] ?? [])
            : null;

        return DB::transaction(function () use (
            $postType,
            $plural,
            $singular,
            $slug,
            $icon,
            $sortOrder,
            $supportsCategories,
            $supportsTags,
            $syncCustomTaxonomies,
            $customTaxonomyIds,
        ): PostType {
            $previousSlug = $postType->slug;

            $postType->fill([
                'plural_name' => $plural,
                'singular_name' => $singular,
                'slug' => $slug,
                'icon' => $icon,
                'supports_categories' => $supportsCategories,
                'supports_tags' => $supportsTags,
                'sort_order' => $sortOrder,
            ])->save();

            if ($previousSlug !== $slug && Schema::hasTable('posts')) {
                Post::query()
                    ->withTrashed()
                    ->where('post_type', $previousSlug)
                    ->update(['post_type' => $slug]);

                if (Schema::hasTable('custom_taxonomy_post_type')) {
                    DB::table('custom_taxonomy_post_type')
                        ->where('post_type_key', $previousSlug)
                        ->update(['post_type_key' => $slug]);
                }
            }

            if ($syncCustomTaxonomies && $customTaxonomyIds !== null) {
                $this->syncCustomTaxonomies($postType->slug, $customTaxonomyIds);
            }

            return $postType->fresh() ?? $postType;
        });
    }

    public function delete(PostType $postType): void
    {
        if ($postType->hasAssignedContent()) {
            throw ValidationException::withMessages([
                'slug' => 'This post type cannot be deleted while content is assigned to it.',
            ]);
        }

        DB::transaction(function () use ($postType): void {
            if (Schema::hasTable('custom_taxonomy_post_type')) {
                DB::table('custom_taxonomy_post_type')
                    ->where('post_type_key', $postType->slug)
                    ->delete();
            }

            if (! $postType->delete()) {
                throw new RuntimeException('Failed to delete post type.');
            }
        });
    }

    /**
     * Replace custom taxonomy associations for a post type key (SRS 12.4.4).
     *
     * @param  list<int>  $taxonomyIds
     */
    public function syncCustomTaxonomies(string $postTypeKey, array $taxonomyIds): void
    {
        if (! Schema::hasTable('custom_taxonomy_post_type')) {
            return;
        }

        DB::table('custom_taxonomy_post_type')
            ->where('post_type_key', $postTypeKey)
            ->delete();

        $now = now();
        $rows = array_map(fn (int $taxonomyId): array => [
            'custom_taxonomy_id' => $taxonomyId,
            'post_type_key' => $postTypeKey,
            'created_at' => $now,
            'updated_at' => $now,
        ], $taxonomyIds);

        if ($rows !== []) {
            DB::table('custom_taxonomy_post_type')->insert($rows);
        }
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    private function normalizeCustomTaxonomyIds(array $ids): array
    {
        $normalized = [];

        foreach ($ids as $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $id = (int) $value;
            if ($id > 0) {
                $normalized[] = $id;
            }
        }

        $normalized = array_values(array_unique($normalized));

        if ($normalized === []) {
            return [];
        }

        if (! Schema::hasTable('custom_taxonomies')) {
            return [];
        }

        $existing = DB::table('custom_taxonomies')
            ->whereIn('id', $normalized)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $missing = array_values(array_diff($normalized, $existing));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'custom_taxonomy_ids' => 'One or more selected custom taxonomies do not exist.',
            ]);
        }

        return $existing;
    }

    private function requiredName(mixed $value, string $field): string
    {
        $name = trim((string) $value);

        if ($name === '') {
            throw ValidationException::withMessages([
                $field => 'A name is required.',
            ]);
        }

        return mb_substr($name, 0, 255);
    }

    private function resolveSlug(string $pluralName, ?string $slugInput, ?int $ignoreId = null): string
    {
        $raw = filled($slugInput) ? (string) $slugInput : $pluralName;

        if (! filled($slugInput) && ! $this->permalinks->autoGenerateSlugs()) {
            throw ValidationException::withMessages([
                'slug' => 'A slug is required when auto-generation is disabled.',
            ]);
        }

        $base = SlugGenerator::sanitize($raw);

        if (ReservedPostTypeSlugs::isReserved($base)) {
            throw ValidationException::withMessages([
                'slug' => "The slug [{$base}] is reserved by the system.",
            ]);
        }

        return SlugGenerator::unique($raw, 'post_types', 'slug', $ignoreId);
    }

    private function assertSlugAllowed(string $slug, ?int $ignoreId = null): void
    {
        if (ReservedPostTypeSlugs::isReserved($slug)) {
            throw ValidationException::withMessages([
                'slug' => "The slug [{$slug}] is reserved by the system.",
            ]);
        }

        $query = PostType::query()->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'slug' => "The slug [{$slug}] is already in use.",
            ]);
        }
    }

    private function normalizeIcon(mixed $value): ?string
    {
        $icon = $this->blankToNull($value);

        if ($icon === null) {
            return null;
        }

        $allowed = array_keys(self::iconOptions());

        if (! in_array($icon, $allowed, true)) {
            throw ValidationException::withMessages([
                'icon' => 'Select a valid menu icon.',
            ]);
        }

        return $icon;
    }

    private function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function nextSortOrder(): int
    {
        return ((int) PostType::query()->max('sort_order')) + 1;
    }

    /**
     * Curated Heroicon options for CPT navigation (SRS 12.4.2).
     *
     * @return array<string, string>
     */
    public static function iconOptions(): array
    {
        return [
            'heroicon-o-document-text' => 'Document',
            'heroicon-o-newspaper' => 'Newspaper',
            'heroicon-o-briefcase' => 'Briefcase',
            'heroicon-o-building-office-2' => 'Building',
            'heroicon-o-calendar-days' => 'Calendar',
            'heroicon-o-camera' => 'Camera',
            'heroicon-o-chat-bubble-left-right' => 'Chat',
            'heroicon-o-book-open' => 'Book',
            'heroicon-o-academic-cap' => 'Academic',
            'heroicon-o-megaphone' => 'Megaphone',
            'heroicon-o-map' => 'Map',
            'heroicon-o-musical-note' => 'Music',
            'heroicon-o-photo' => 'Photo',
            'heroicon-o-puzzle-piece' => 'Puzzle',
            'heroicon-o-rectangle-stack' => 'Stack',
            'heroicon-o-shopping-bag' => 'Shopping bag',
            'heroicon-o-sparkles' => 'Sparkles',
            'heroicon-o-trophy' => 'Trophy',
            'heroicon-o-users' => 'Users',
            'heroicon-o-video-camera' => 'Video',
        ];
    }
}
