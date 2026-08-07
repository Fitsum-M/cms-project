<?php

namespace App\Services;

use App\Enums\TaxonomyStructure;
use App\Models\CustomTaxonomy;
use App\Models\CustomTaxonomyPostType;
use App\Support\PostTypeRegistry;
use App\Support\ReservedTaxonomySlugs;
use App\Support\Settings\PermalinkSettings;
use App\Support\SlugGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CustomTaxonomyService
{
    public function __construct(
        private readonly PermalinkSettings $permalinks,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     slug?: string|null,
     *     structure_type: string,
     *     post_type_keys: list<string>
     * }  $data
     */
    public function create(array $data): CustomTaxonomy
    {
        $structure = TaxonomyStructure::tryFrom((string) $data['structure_type'])
            ?? throw ValidationException::withMessages([
                'structure_type' => 'Structure type must be hierarchical or flat.',
            ]);

        $postTypeKeys = $this->normalizePostTypeKeys($data['post_type_keys'] ?? []);
        $slug = $this->resolveSlug($data['name'], $data['slug'] ?? null);
        $this->assertSlugAllowed($slug);

        return DB::transaction(function () use ($data, $structure, $postTypeKeys, $slug): CustomTaxonomy {
            $taxonomy = CustomTaxonomy::query()->create([
                'name' => trim($data['name']),
                'slug' => $slug,
                'structure_type' => $structure,
            ]);

            $this->syncPostTypes($taxonomy, $postTypeKeys);

            return $taxonomy->fresh(['postTypeAssociations']);
        });
    }

    /**
     * Structure type is immutable after create (SRS 13.3.1).
     *
     * @param  array{
     *     name?: string,
     *     slug?: string|null,
     *     post_type_keys?: list<string>
     * }  $data
     */
    public function update(CustomTaxonomy $taxonomy, array $data): CustomTaxonomy
    {
        $name = array_key_exists('name', $data) ? trim((string) $data['name']) : $taxonomy->name;
        $slugInput = array_key_exists('slug', $data) ? $data['slug'] : $taxonomy->slug;
        $slug = $this->resolveSlug($name, $slugInput, $taxonomy->id);
        $this->assertSlugAllowed($slug, $taxonomy->id);

        $postTypeKeys = array_key_exists('post_type_keys', $data)
            ? $this->normalizePostTypeKeys($data['post_type_keys'])
            : $taxonomy->postTypeKeys();

        return DB::transaction(function () use ($taxonomy, $name, $slug, $postTypeKeys): CustomTaxonomy {
            $taxonomy->fill([
                'name' => $name,
                'slug' => $slug,
            ])->save();

            $this->syncPostTypes($taxonomy, $postTypeKeys);

            return $taxonomy->fresh(['postTypeAssociations']);
        });
    }

    public function delete(CustomTaxonomy $taxonomy): void
    {
        app(TaxonomyDeletionGuard::class)->assertTaxonomyDeletable($taxonomy);

        if (! $taxonomy->delete()) {
            throw new RuntimeException('Failed to delete custom taxonomy.');
        }
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    private function normalizePostTypeKeys(array $keys): array
    {
        $keys = array_values(array_unique(array_filter(array_map('strval', $keys))));

        if ($keys === []) {
            throw ValidationException::withMessages([
                'post_type_keys' => 'Associate at least one post type.',
            ]);
        }

        $allowed = PostTypeRegistry::keys();
        foreach ($keys as $key) {
            if (! in_array($key, $allowed, true)) {
                throw ValidationException::withMessages([
                    'post_type_keys' => "Unknown post type [{$key}].",
                ]);
            }
        }

        return $keys;
    }

    /**
     * @param  list<string>  $keys
     */
    private function syncPostTypes(CustomTaxonomy $taxonomy, array $keys): void
    {
        $taxonomy->postTypeAssociations()->delete();

        foreach ($keys as $key) {
            CustomTaxonomyPostType::query()->create([
                'custom_taxonomy_id' => $taxonomy->id,
                'post_type_key' => $key,
            ]);
        }
    }

    private function resolveSlug(string $name, ?string $slugInput, ?int $ignoreId = null): string
    {
        $source = filled($slugInput)
            ? (string) $slugInput
            : ($this->permalinks->autoGenerateSlugs() ? $name : '');

        if ($source === '') {
            throw ValidationException::withMessages([
                'slug' => 'A slug is required when auto-generation is disabled.',
            ]);
        }

        return SlugGenerator::unique($source, 'custom_taxonomies', 'slug', $ignoreId);
    }

    private function assertSlugAllowed(string $slug, ?int $ignoreId = null): void
    {
        if (ReservedTaxonomySlugs::isReserved($slug)) {
            throw ValidationException::withMessages([
                'slug' => "The slug [{$slug}] is reserved by the system.",
            ]);
        }

        // Uniqueness already enforced by SlugGenerator against custom_taxonomies.
        unset($ignoreId);
    }
}
