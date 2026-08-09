<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CustomTaxonomyTerm;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Attach/detach taxonomy terms to content IDs (SRS 12.2.5–12.2.7, 13.1.5).
 */
class TaxonomyAssignmentService
{
    public function __construct(
        private readonly TagService $tags,
    ) {}

    public function assignCategory(Category $category, int $postId): void
    {
        DB::table('category_post')->updateOrInsert(
            [
                'category_id' => $category->id,
                'post_id' => $postId,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function unassignCategory(Category $category, int $postId): void
    {
        DB::table('category_post')
            ->where('category_id', $category->id)
            ->where('post_id', $postId)
            ->delete();
    }

    public function assignTag(Tag $tag, int $postId): void
    {
        DB::table('post_tag')->updateOrInsert(
            [
                'tag_id' => $tag->id,
                'post_id' => $postId,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function unassignTag(Tag $tag, int $postId): void
    {
        DB::table('post_tag')
            ->where('tag_id', $tag->id)
            ->where('post_id', $postId)
            ->delete();
    }

    public function assignCustomTerm(CustomTaxonomyTerm $term, int $postId): void
    {
        DB::table('custom_taxonomy_term_post')->updateOrInsert(
            [
                'custom_taxonomy_term_id' => $term->id,
                'post_id' => $postId,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function unassignCustomTerm(CustomTaxonomyTerm $term, int $postId): void
    {
        DB::table('custom_taxonomy_term_post')
            ->where('custom_taxonomy_term_id', $term->id)
            ->where('post_id', $postId)
            ->delete();
    }

    /**
     * @param  list<int|string>  $categoryIds
     */
    public function syncPostCategories(Post $post, array $categoryIds): void
    {
        $ids = $this->normalizePositiveIds($categoryIds);

        if ($ids !== []) {
            $existing = Category::query()->whereIn('id', $ids)->pluck('id')->all();
            $missing = array_values(array_diff($ids, array_map('intval', $existing)));

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'category_ids' => 'One or more selected categories do not exist.',
                ]);
            }
        }

        $post->categories()->sync($ids);
    }

    /**
     * Accepts existing tag IDs and/or new tag names (auto-create, SRS 12.2.6 / 13.2.2).
     *
     * @param  list<int|string>  $tags
     */
    public function syncPostTags(Post $post, array $tags): void
    {
        $tagIds = [];

        foreach ($tags as $value) {
            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                $id = (int) $value;
                if ($id > 0) {
                    $tagIds[] = $id;
                }

                continue;
            }

            $name = trim((string) $value);
            if ($name === '') {
                continue;
            }

            $tagIds[] = $this->tags->findOrCreateByName($name)->id;
        }

        $tagIds = array_values(array_unique($tagIds));

        if ($tagIds !== []) {
            $existing = Tag::query()->whereIn('id', $tagIds)->pluck('id')->all();
            $missing = array_values(array_diff($tagIds, array_map('intval', $existing)));

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'tag_ids' => 'One or more selected tags do not exist.',
                ]);
            }
        }

        $post->tags()->sync($tagIds);
    }

    /**
     * @param  list<int|string>  $termIds
     */
    public function syncPostCustomTerms(Post $post, array $termIds): void
    {
        $ids = $this->normalizePositiveIds($termIds);

        if ($ids === []) {
            $post->customTaxonomyTerms()->sync([]);

            return;
        }

        $terms = CustomTaxonomyTerm::query()
            ->with('taxonomy.postTypeAssociations')
            ->whereIn('id', $ids)
            ->get();

        if ($terms->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'custom_term_ids' => 'One or more selected custom taxonomy terms do not exist.',
            ]);
        }

        $postType = $post->post_type;

        foreach ($terms as $term) {
            $allowed = $term->taxonomy?->postTypeKeys() ?? [];

            if (! in_array($postType, $allowed, true)) {
                throw ValidationException::withMessages([
                    'custom_term_ids' => "Term \"{$term->name}\" is not available for post type \"{$postType}\".",
                ]);
            }
        }

        $post->customTaxonomyTerms()->sync($ids);
    }

    /**
     * @param  list<int>  $postIds
     */
    public function syncCategoryPosts(Category $category, array $postIds): void
    {
        $this->syncPivot('category_post', 'category_id', $category->id, $postIds);
    }

    /**
     * @param  list<int>  $postIds
     */
    public function syncTagPosts(Tag $tag, array $postIds): void
    {
        $this->syncPivot('post_tag', 'tag_id', $tag->id, $postIds);
    }

    /**
     * @param  list<int>  $postIds
     */
    public function syncCustomTermPosts(CustomTaxonomyTerm $term, array $postIds): void
    {
        $this->syncPivot('custom_taxonomy_term_post', 'custom_taxonomy_term_id', $term->id, $postIds);
    }

    /**
     * @param  list<int|string>  $values
     * @return list<int>
     */
    private function normalizePositiveIds(array $values): array
    {
        $ids = [];

        foreach ($values as $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<int>  $postIds
     */
    private function syncPivot(string $table, string $foreignKey, int $foreignId, array $postIds): void
    {
        $postIds = array_values(array_unique(array_map('intval', $postIds)));

        if ($foreignId < 1) {
            throw new InvalidArgumentException('Invalid taxonomy id.');
        }

        DB::table($table)->where($foreignKey, $foreignId)->delete();

        $now = now();
        $rows = array_map(fn (int $postId): array => [
            $foreignKey => $foreignId,
            'post_id' => $postId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $postIds);

        if ($rows !== []) {
            DB::table($table)->insert($rows);
        }
    }
}
