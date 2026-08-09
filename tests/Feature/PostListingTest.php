<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\ContentLifecycleService;
use App\Services\PostService;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PostListingTest extends TestCase
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

    public function test_duplicate_creates_draft_copy_with_taxonomies_and_reset_publish_date(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $source = app(PostService::class)->create([
            'title' => 'Original Post',
            'body' => '<p>Body content</p>',
            'status' => ContentStatus::Published->value,
            'published_at' => now()->subDays(5),
            'category_ids' => [$category->id],
            'tag_ids' => [$tag->id],
            'featured_image_id' => null,
        ], $admin);

        $copy = app(PostService::class)->duplicate($source, $admin);

        $this->assertSame('Original Post (Copy)', $copy->title);
        $this->assertSame(ContentStatus::Draft, $copy->status);
        $this->assertNotSame($source->slug, $copy->slug);
        $this->assertTrue($copy->published_at->isAfter($source->published_at));
        $this->assertTrue($copy->categories->contains('id', $category->id));
        $this->assertTrue($copy->tags->contains('id', $tag->id));
        $this->assertDatabaseHas('posts', [
            'id' => $source->id,
            'title' => 'Original Post',
        ]);
    }

    public function test_bulk_change_status_and_author(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $editor = $this->makeUser(UserRole::Editor);
        $posts = Post::factory()->count(2)->create([
            'author_id' => $admin->id,
            'status' => ContentStatus::Draft,
        ]);

        $statusResult = app(PostService::class)->bulkChangeStatus(
            $posts,
            ContentStatus::Published,
            $admin,
        );

        $this->assertSame(2, $statusResult['success']);
        $this->assertSame(0, $statusResult['failed']);
        $this->assertSame(ContentStatus::Published, $posts[0]->fresh()->status);

        $authorResult = app(PostService::class)->bulkChangeAuthor($posts, $editor->id, $admin);
        $this->assertSame(2, $authorResult['success']);
        $this->assertSame($editor->id, $posts[0]->fresh()->author_id);
    }

    public function test_bulk_assign_categories_and_tags_merges_existing(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $existing = Category::factory()->create();
        $added = Category::factory()->create();
        $tag = Tag::factory()->create();

        $post = app(PostService::class)->create([
            'title' => 'Bulk taxonomy',
            'category_ids' => [$existing->id],
        ], $admin);

        app(PostService::class)->bulkAssignCategories([$post], [$added->id], $admin);
        app(PostService::class)->bulkAssignTags([$post], [$tag->id], $admin);

        $post->refresh()->load(['categories', 'tags']);
        $this->assertTrue($post->categories->contains('id', $existing->id));
        $this->assertTrue($post->categories->contains('id', $added->id));
        $this->assertTrue($post->tags->contains('id', $tag->id));
    }

    public function test_restore_trashed_and_archived_posts(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $lifecycle = app(ContentLifecycleService::class);
        $service = app(PostService::class);

        $trashed = Post::factory()->create(['author_id' => $admin->id]);
        $lifecycle->trash($trashed);

        $archived = Post::factory()->archived()->create(['author_id' => $admin->id]);

        $result = $service->bulkRestore([
            Post::withTrashed()->findOrFail($trashed->id),
            $archived,
        ], $admin);

        $this->assertSame(2, $result['success']);
        $this->assertSame(ContentStatus::Draft, Post::query()->findOrFail($trashed->id)->status);
        $this->assertSame(ContentStatus::Draft, $archived->fresh()->status);
        $this->assertFalse(Post::query()->findOrFail($trashed->id)->trashed());
    }

    public function test_list_excludes_trashed_by_default_and_supports_search(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $lifecycle = app(ContentLifecycleService::class);

        $visible = Post::factory()->create([
            'author_id' => $admin->id,
            'title' => 'Searchable Alpha Title',
            'slug' => 'searchable-alpha',
            'body' => '<p>UniqueBodyToken</p>',
        ]);
        $trashed = Post::factory()->create([
            'author_id' => $admin->id,
            'title' => 'Trashed Post',
        ]);
        $lifecycle->trash($trashed);

        Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([Post::withTrashed()->findOrFail($trashed->id)])
            ->searchTable('UniqueBodyToken')
            ->assertCanSeeTableRecords([$visible]);
    }

    public function test_list_filters_by_status_category_and_date_range(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $category = Category::factory()->create();

        $matched = app(PostService::class)->create([
            'title' => 'Filter Match',
            'status' => ContentStatus::Published->value,
            'published_at' => now()->subDay(),
            'category_ids' => [$category->id],
        ], $admin);

        $other = app(PostService::class)->create([
            'title' => 'Filter Miss',
            'status' => ContentStatus::Draft->value,
            'published_at' => now()->subMonths(2),
        ], $admin);

        Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->filterTable('status', [ContentStatus::Published->value])
            ->filterTable('categories', $category->id)
            ->filterTable('date_range', [
                'field' => 'published_at',
                'from' => now()->subDays(3)->toDateString(),
                'until' => now()->toDateString(),
            ])
            ->assertCanSeeTableRecords([$matched])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_author_cannot_bulk_change_author(): void
    {
        $author = $this->makeUser(UserRole::Author);
        $other = $this->makeUser(UserRole::Editor);
        $post = Post::factory()->create(['author_id' => $author->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(PostService::class)->bulkChangeAuthor([$post], $other->id, $author);
    }

    private function makeUser(UserRole $role): User
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
