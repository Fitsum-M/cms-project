<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\TaxonomyStructure;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\CustomTaxonomyService;
use App\Services\CustomTaxonomyTermService;
use App\Services\TagService;
use App\Services\TaxonomyAssignmentService;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TaxonomyDeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_category_with_assigned_content_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create();
        app(TaxonomyAssignmentService::class)->assignCategory($category, postId: $post->id);

        $this->assertTrue($category->hasAssignedContent());
        $this->assertSame(1, $category->assignedContentCount());

        try {
            app(CategoryService::class)->delete($category);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('assigned to 1 content item', $exception->getMessage());
        }

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_category_can_be_deleted_after_content_is_unassigned(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create();
        $assignments = app(TaxonomyAssignmentService::class);
        $assignments->assignCategory($category, $post->id);
        $assignments->unassignCategory($category, $post->id);

        app(CategoryService::class)->delete($category);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_tag_with_assigned_content_cannot_be_deleted(): void
    {
        $tag = Tag::factory()->create();
        $post = Post::factory()->create();
        app(TaxonomyAssignmentService::class)->assignTag($tag, postId: $post->id);

        $this->expectException(ValidationException::class);
        app(TagService::class)->delete($tag);
    }

    public function test_custom_term_with_assigned_content_cannot_be_deleted(): void
    {
        $taxonomy = app(CustomTaxonomyService::class)->create([
            'name' => 'Topics',
            'slug' => 'topics-guard',
            'structure_type' => TaxonomyStructure::Flat->value,
            'post_type_keys' => ['post'],
        ]);

        $term = app(CustomTaxonomyTermService::class)->create($taxonomy, [
            'name' => 'Alpha',
            'slug' => 'alpha',
        ]);

        $post = Post::factory()->create();
        app(TaxonomyAssignmentService::class)->assignCustomTerm($term, postId: $post->id);

        $this->expectException(ValidationException::class);
        app(CustomTaxonomyTermService::class)->delete($term);
    }

    public function test_custom_taxonomy_cannot_be_deleted_while_term_has_content(): void
    {
        $taxonomy = app(CustomTaxonomyService::class)->create([
            'name' => 'Series',
            'slug' => 'series-guard',
            'structure_type' => TaxonomyStructure::Flat->value,
            'post_type_keys' => ['post'],
        ]);

        $term = app(CustomTaxonomyTermService::class)->create($taxonomy, [
            'name' => 'S1',
            'slug' => 's1',
        ]);

        $post = Post::factory()->create();
        app(TaxonomyAssignmentService::class)->assignCustomTerm($term, postId: $post->id);

        $this->expectException(ValidationException::class);
        app(CustomTaxonomyService::class)->delete($taxonomy);
    }

    public function test_empty_category_delete_still_promotes_children(): void
    {
        $parent = Category::factory()->create(['name' => 'Parent', 'slug' => 'parent-guard']);
        $child = Category::factory()->childOf($parent)->create(['name' => 'Child', 'slug' => 'child-guard']);

        app(CategoryService::class)->delete($parent);

        $this->assertDatabaseMissing('categories', ['id' => $parent->id]);
        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => null,
        ]);
    }

    public function test_policy_denies_delete_when_content_assigned(): void
    {
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        $admin = User::factory()->create([
            'status' => UserStatus::Active,
            'activated_at' => now(),
        ]);
        $admin->assignSingleRole(UserRole::Administrator);

        $category = Category::factory()->create();
        $post = Post::factory()->create();
        app(TaxonomyAssignmentService::class)->assignCategory($category, $post->id);

        $this->assertFalse($admin->can('delete', $category));
    }

    public function test_sync_replaces_assignment_set(): void
    {
        $tag = Tag::factory()->create();
        $posts = Post::factory()->count(3)->create();
        $assignments = app(TaxonomyAssignmentService::class);

        $assignments->syncTagPosts($tag, $posts->pluck('id')->all());
        $this->assertSame(3, $tag->assignedContentCount());

        $assignments->syncTagPosts($tag, [$posts[1]->id]);
        $this->assertSame(1, $tag->fresh()->assignedContentCount());
        $this->assertDatabaseHas('post_tag', ['tag_id' => $tag->id, 'post_id' => $posts[1]->id]);
        $this->assertDatabaseMissing('post_tag', ['tag_id' => $tag->id, 'post_id' => $posts[0]->id]);
    }
}
