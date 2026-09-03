<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\TaxonomyStructure;
use App\Enums\UserStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\CustomTaxonomyService;
use App\Services\CustomTaxonomyTermService;
use App\Services\PostService;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PostTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        $this->seed(MediaSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_create_post_assigns_categories_tags_and_custom_terms(): void
    {
        $admin = $this->makeUser('Administrator');
        $category = Category::factory()->create(['name' => 'News', 'slug' => 'news']);
        $tag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

        $taxonomy = app(CustomTaxonomyService::class)->create([
            'name' => 'Topics',
            'slug' => 'topics',
            'structure_type' => TaxonomyStructure::Flat->value,
            'post_type_keys' => ['post'],
        ]);
        $term = app(CustomTaxonomyTermService::class)->create($taxonomy, [
            'name' => 'PHP',
            'slug' => 'php',
        ]);

        $post = app(PostService::class)->create([
            'title' => 'Taxonomy Post',
            'category_ids' => [$category->id],
            'tag_ids' => [$tag->id],
            'custom_term_ids' => [$term->id],
        ], $admin);

        $this->assertTrue($post->categories->contains('id', $category->id));
        $this->assertTrue($post->tags->contains('id', $tag->id));
        $this->assertTrue($post->customTaxonomyTerms->contains('id', $term->id));
        $this->assertDatabaseHas('category_post', [
            'category_id' => $category->id,
            'post_id' => $post->id,
        ]);
        $this->assertDatabaseHas('post_tag', [
            'tag_id' => $tag->id,
            'post_id' => $post->id,
        ]);
        $this->assertDatabaseHas('custom_taxonomy_term_post', [
            'custom_taxonomy_term_id' => $term->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_tag_names_are_auto_created_on_assign(): void
    {
        $admin = $this->makeUser('Administrator');

        $post = app(PostService::class)->create([
            'title' => 'Auto tag',
            'tag_ids' => ['Brand New Tag'],
        ], $admin);

        $this->assertDatabaseHas('tags', ['name' => 'Brand New Tag']);
        $this->assertCount(1, $post->tags);
        $this->assertSame('Brand New Tag', $post->tags->first()->name);
    }

    public function test_update_replaces_taxonomy_assignments(): void
    {
        $admin = $this->makeUser('Administrator');
        $first = Category::factory()->create(['name' => 'First', 'slug' => 'first']);
        $second = Category::factory()->create(['name' => 'Second', 'slug' => 'second']);
        $tag = Tag::factory()->create(['name' => 'Keep', 'slug' => 'keep']);

        $post = app(PostService::class)->create([
            'title' => 'Sync me',
            'category_ids' => [$first->id],
            'tag_ids' => [$tag->id],
        ], $admin);

        $updated = app(PostService::class)->update($post, [
            'category_ids' => [$second->id],
            'tag_ids' => [],
        ], $admin);

        $this->assertFalse($updated->categories->contains('id', $first->id));
        $this->assertTrue($updated->categories->contains('id', $second->id));
        $this->assertCount(0, $updated->tags);
    }

    public function test_custom_term_rejected_when_taxonomy_not_associated_with_post_type(): void
    {
        $admin = $this->makeUser('Administrator');

        $taxonomy = app(CustomTaxonomyService::class)->create([
            'name' => 'Events Only',
            'slug' => 'events-only',
            'structure_type' => TaxonomyStructure::Flat->value,
            'post_type_keys' => ['post'],
        ]);
        $term = app(CustomTaxonomyTermService::class)->create($taxonomy, [
            'name' => 'Blocked',
            'slug' => 'blocked',
        ]);

        $taxonomy->postTypeAssociations()->delete();
        $taxonomy->postTypeAssociations()->create(['post_type_key' => 'events']);

        $this->expectException(ValidationException::class);

        app(PostService::class)->create([
            'title' => 'Wrong type',
            'post_type' => 'post',
            'custom_term_ids' => [$term->id],
        ], $admin);
    }

    public function test_deleting_post_cascades_taxonomy_pivots(): void
    {
        $admin = $this->makeUser('Administrator');
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $post = app(PostService::class)->create([
            'title' => 'Delete me',
            'category_ids' => [$category->id],
            'tag_ids' => [$tag->id],
        ], $admin);

        $postId = $post->id;
        $post->forceDelete();

        $this->assertDatabaseMissing('category_post', ['post_id' => $postId]);
        $this->assertDatabaseMissing('post_tag', ['post_id' => $postId]);
    }

    public function test_filament_create_assigns_taxonomies(): void
    {
        $admin = $this->makeUser('Administrator');
        $category = Category::factory()->create(['name' => 'Filament Cat', 'slug' => 'filament-cat']);
        $tag = Tag::factory()->create(['name' => 'Filament Tag', 'slug' => 'filament-tag']);

        Livewire::actingAs($admin)
            ->test(CreatePost::class)
            ->fillForm([
                'title' => 'With taxonomies',
                'body' => '<p>Body</p>',
                'post_type' => 'post',
                'author_id' => $admin->id,
                'published_at' => now(),
                'category_ids' => [$category->id],
                'tag_ids' => [$tag->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('title', 'With taxonomies')->first();
        $this->assertNotNull($post);
        $this->assertTrue($post->categories->contains('id', $category->id));
        $this->assertTrue($post->tags->contains('id', $tag->id));
    }

    public function test_filament_edit_loads_and_saves_taxonomies(): void
    {
        $admin = $this->makeUser('Administrator');
        $category = Category::factory()->create();
        $next = Category::factory()->create();

        $post = app(PostService::class)->create([
            'title' => 'Edit taxonomies',
            'category_ids' => [$category->id],
        ], $admin);

        Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertFormSet([
                'category_ids' => [$category->id],
            ])
            ->fillForm([
                'category_ids' => [$next->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($post->fresh()->categories->contains('id', $next->id));
        $this->assertFalse($post->fresh()->categories->contains('id', $category->id));
    }

    public function test_list_can_filter_by_category(): void
    {
        $admin = $this->makeUser('Administrator');
        $category = Category::factory()->create();

        $matched = app(PostService::class)->create([
            'title' => 'In category',
            'category_ids' => [$category->id],
        ], $admin);

        $other = app(PostService::class)->create([
            'title' => 'Outside',
        ], $admin);

        Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->filterTable('categories', $category->id)
            ->assertCanSeeTableRecords([$matched])
            ->assertCanNotSeeTableRecords([$other]);
    }

    private function makeUser(string $role): User
    {
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'activated_at' => now(),
        ]);

        $user->assignSingleRole($role);

        return $user->fresh();
    }
}
