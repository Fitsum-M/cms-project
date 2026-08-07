<?php

namespace App\Services;

use App\Models\Category;
use App\Support\SlugGenerator;
use App\Support\Settings\PermalinkSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CategoryService
{
    public function __construct(
        private readonly PermalinkSettings $permalinks,
    ) {}

    /**
     * @param  array{name: string, slug?: string|null, parent_id?: int|null, description?: string|null}  $data
     */
    public function create(array $data): Category
    {
        $parentId = $data['parent_id'] ?? null;

        if ($parentId !== null && ! Category::query()->whereKey($parentId)->exists()) {
            throw ValidationException::withMessages([
                'parent_id' => 'Selected parent category does not exist.',
            ]);
        }

        $slug = $this->resolveSlug($data['name'], $data['slug'] ?? null);

        return Category::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'parent_id' => $parentId,
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * @param  array{name?: string, slug?: string|null, parent_id?: int|null, description?: string|null}  $data
     */
    public function update(Category $category, array $data): Category
    {
        $parentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : $category->parent_id;

        if ($parentId !== null && ! Category::query()->whereKey($parentId)->exists()) {
            throw ValidationException::withMessages([
                'parent_id' => 'Selected parent category does not exist.',
            ]);
        }

        if ($category->wouldCreateCycle($parentId !== null ? (int) $parentId : null)) {
            throw ValidationException::withMessages([
                'parent_id' => 'A category cannot be its own parent or descendant.',
            ]);
        }

        $name = $data['name'] ?? $category->name;
        $slugInput = array_key_exists('slug', $data) ? $data['slug'] : $category->slug;
        $slug = $this->resolveSlug($name, $slugInput, $category->id);

        $category->fill([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parentId,
            'description' => array_key_exists('description', $data)
                ? $data['description']
                : $category->description,
        ])->save();

        return $category->refresh();
    }

    /**
     * Delete a category: children become root-level; blocked when content is assigned (13.1.5 / T.04).
     */
    public function delete(Category $category): void
    {
        app(TaxonomyDeletionGuard::class)->assertDeletable($category);

        DB::transaction(function () use ($category): void {
            Category::query()
                ->where('parent_id', $category->id)
                ->update(['parent_id' => null]);

            if (! $category->delete()) {
                throw new RuntimeException('Failed to delete category.');
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public function parentOptions(?int $excludeCategoryId = null): array
    {
        $query = Category::query()->orderBy('name');

        $excludeIds = [];
        if ($excludeCategoryId !== null) {
            $excludeIds[] = $excludeCategoryId;
            $category = Category::query()->find($excludeCategoryId);
            if ($category) {
                $excludeIds = [...$excludeIds, ...$category->descendantIds()];
            }
        }

        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query
            ->get(['id', 'name', 'parent_id'])
            ->mapWithKeys(function (Category $category): array {
                $label = $this->hierarchicalLabel($category);

                return [$category->id => $label];
            })
            ->all();
    }

    public function hierarchicalLabel(Category $category): string
    {
        $parts = [$category->name];
        $current = $category;

        while ($current->parent_id !== null) {
            $current = $current->parent;
            if ($current === null) {
                break;
            }
            array_unshift($parts, $current->name);
        }

        return implode(' › ', $parts);
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

        return SlugGenerator::unique($source, 'categories', 'slug', $ignoreId);
    }
}
