<?php

namespace App\Services;

use App\Models\CustomTaxonomy;
use App\Models\CustomTaxonomyTerm;
use App\Support\Settings\PermalinkSettings;
use App\Support\SlugGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CustomTaxonomyTermService
{
    public function __construct(
        private readonly PermalinkSettings $permalinks,
    ) {}

    /**
     * @param  array{name: string, slug?: string|null, parent_id?: int|null, description?: string|null}  $data
     */
    public function create(CustomTaxonomy $taxonomy, array $data): CustomTaxonomyTerm
    {
        $parentId = $this->resolveParentId($taxonomy, $data['parent_id'] ?? null);
        $slug = $this->resolveSlug($taxonomy, $data['name'], $data['slug'] ?? null);

        return CustomTaxonomyTerm::query()->create([
            'custom_taxonomy_id' => $taxonomy->id,
            'name' => trim($data['name']),
            'slug' => $slug,
            'parent_id' => $parentId,
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * @param  array{name?: string, slug?: string|null, parent_id?: int|null, description?: string|null}  $data
     */
    public function update(CustomTaxonomyTerm $term, array $data): CustomTaxonomyTerm
    {
        $taxonomy = $term->taxonomy;
        $parentId = array_key_exists('parent_id', $data)
            ? $this->resolveParentId($taxonomy, $data['parent_id'], $term)
            : $term->parent_id;

        if ($term->wouldCreateCycle($parentId !== null ? (int) $parentId : null)) {
            throw ValidationException::withMessages([
                'parent_id' => 'A term cannot be its own parent or descendant.',
            ]);
        }

        $name = array_key_exists('name', $data) ? trim((string) $data['name']) : $term->name;
        $slugInput = array_key_exists('slug', $data) ? $data['slug'] : $term->slug;
        $slug = $this->resolveSlug($taxonomy, $name, $slugInput, $term->id);

        $term->fill([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parentId,
            'description' => array_key_exists('description', $data)
                ? $data['description']
                : $term->description,
        ])->save();

        return $term->refresh();
    }

    public function delete(CustomTaxonomyTerm $term): void
    {
        app(TaxonomyDeletionGuard::class)->assertDeletable($term);

        DB::transaction(function () use ($term): void {
            CustomTaxonomyTerm::query()
                ->where('parent_id', $term->id)
                ->update(['parent_id' => null]);

            if (! $term->delete()) {
                throw new RuntimeException('Failed to delete taxonomy term.');
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public function parentOptions(CustomTaxonomy $taxonomy, ?int $excludeTermId = null): array
    {
        if (! $taxonomy->isHierarchical()) {
            return [];
        }

        $query = CustomTaxonomyTerm::query()
            ->where('custom_taxonomy_id', $taxonomy->id)
            ->orderBy('name');

        $excludeIds = [];
        if ($excludeTermId !== null) {
            $excludeIds[] = $excludeTermId;
            $term = CustomTaxonomyTerm::query()->find($excludeTermId);
            if ($term) {
                $excludeIds = [...$excludeIds, ...$term->descendantIds()];
            }
        }

        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query
            ->get(['id', 'name', 'parent_id'])
            ->mapWithKeys(fn (CustomTaxonomyTerm $term): array => [
                $term->id => $this->hierarchicalLabel($term),
            ])
            ->all();
    }

    public function hierarchicalLabel(CustomTaxonomyTerm $term): string
    {
        $parts = [$term->name];
        $current = $term;

        while ($current->parent_id !== null) {
            $current = $current->parent;
            if ($current === null) {
                break;
            }
            array_unshift($parts, $current->name);
        }

        return implode(' › ', $parts);
    }

    private function resolveParentId(
        CustomTaxonomy $taxonomy,
        mixed $parentId,
        ?CustomTaxonomyTerm $term = null,
    ): ?int {
        if ($taxonomy->isFlat()) {
            return null;
        }

        if ($parentId === null || $parentId === '') {
            return null;
        }

        $parentId = (int) $parentId;

        $exists = CustomTaxonomyTerm::query()
            ->where('custom_taxonomy_id', $taxonomy->id)
            ->whereKey($parentId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'parent_id' => 'Selected parent term does not exist in this taxonomy.',
            ]);
        }

        if ($term !== null && $term->wouldCreateCycle($parentId)) {
            throw ValidationException::withMessages([
                'parent_id' => 'A term cannot be its own parent or descendant.',
            ]);
        }

        return $parentId;
    }

    private function resolveSlug(
        CustomTaxonomy $taxonomy,
        string $name,
        ?string $slugInput,
        ?int $ignoreId = null,
    ): string {
        $source = filled($slugInput)
            ? (string) $slugInput
            : ($this->permalinks->autoGenerateSlugs() ? $name : '');

        if ($source === '') {
            throw ValidationException::withMessages([
                'slug' => 'A slug is required when auto-generation is disabled.',
            ]);
        }

        $base = SlugGenerator::sanitize($source);
        $candidate = $base;
        $suffix = 2;

        while (
            CustomTaxonomyTerm::query()
                ->where('custom_taxonomy_id', $taxonomy->id)
                ->where('slug', $candidate)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
