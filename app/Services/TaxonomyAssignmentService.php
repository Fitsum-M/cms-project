<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CustomTaxonomyTerm;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Attach/detach taxonomy terms to content IDs.
 * Posts module (Phase 5) will call these when assigning taxonomies.
 */
class TaxonomyAssignmentService
{
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
