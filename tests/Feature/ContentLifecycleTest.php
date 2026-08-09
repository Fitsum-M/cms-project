<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Services\ContentLifecycleService;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ContentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private ContentLifecycleService $lifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->lifecycle = app(ContentLifecycleService::class);
    }

    public function test_new_content_starts_as_draft(): void
    {
        $post = Post::factory()->create();

        $this->assertSame(ContentStatus::Draft, $post->contentStatus());
        $this->assertSame('Draft', $post->lifecycleLabel());
        $this->assertTrue($this->lifecycle->canEdit($post));
    }

    public function test_author_without_publish_permission_is_moved_to_pending_review(): void
    {
        $author = $this->makeUser(UserRole::Author);
        $post = Post::factory()->create();

        $this->assertFalse($author->can(Permission::PostsPublish->value));

        $this->lifecycle->publish($post, $author);

        $this->assertSame(ContentStatus::PendingReview, $post->fresh()->contentStatus());
        $this->assertNotSame(ContentStatus::Published, $post->fresh()->contentStatus());
    }

    public function test_editor_can_publish_from_draft_and_pending(): void
    {
        $editor = $this->makeUser(UserRole::Editor);
        $post = Post::factory()->create();

        $this->lifecycle->publish($post, $editor);

        $post->refresh();
        $this->assertSame(ContentStatus::Published, $post->contentStatus());
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->hasBeenPublished());

        $pending = Post::factory()->pending()->create();
        $this->lifecycle->publish($pending, $editor);
        $this->assertSame(ContentStatus::Published, $pending->fresh()->contentStatus());
    }

    public function test_unpublish_to_draft_or_archived(): void
    {
        $editor = $this->makeUser(UserRole::Editor);
        $post = Post::factory()->create();
        $this->lifecycle->publish($post, $editor);

        $this->lifecycle->unpublish($post, ContentStatus::Draft);
        $this->assertSame(ContentStatus::Draft, $post->fresh()->contentStatus());
        $this->assertTrue($post->fresh()->hasBeenPublished());

        $this->lifecycle->publish($post, $editor);
        $this->lifecycle->unpublish($post, ContentStatus::Archived);
        $this->assertSame(ContentStatus::Archived, $post->fresh()->contentStatus());
    }

    public function test_archive_from_draft_pending_and_published(): void
    {
        $post = Post::factory()->create();
        $this->lifecycle->archive($post);
        $this->assertSame(ContentStatus::Archived, $post->fresh()->contentStatus());

        $pending = Post::factory()->pending()->create();
        $this->lifecycle->archive($pending);
        $this->assertSame(ContentStatus::Archived, $pending->fresh()->contentStatus());

        $published = Post::factory()->published()->create();
        $this->lifecycle->archive($published);
        $this->assertSame(ContentStatus::Archived, $published->fresh()->contentStatus());
    }

    public function test_soft_delete_trashes_and_blocks_edit(): void
    {
        $post = Post::factory()->create();
        $this->lifecycle->trash($post);

        $trashed = Post::withTrashed()->findOrFail($post->id);
        $this->assertTrue($trashed->trashed());
        $this->assertSame('Trashed', $trashed->lifecycleLabel());
        $this->assertFalse($this->lifecycle->canEdit($trashed));
        $this->assertNull(Post::query()->find($post->id));
        $this->assertNotNull(Post::withTrashed()->find($post->id));
    }

    public function test_restore_from_trash_defaults_to_draft_and_resolves_slug_conflict(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $post = Post::factory()->create(['slug' => 'shared-slug']);
        $this->lifecycle->trash($post);

        Post::factory()->create(['slug' => 'shared-slug']);

        $trashed = Post::withTrashed()->findOrFail($post->id);
        $this->lifecycle->restore($trashed, $admin);

        $restored = Post::query()->findOrFail($post->id);
        $this->assertSame(ContentStatus::Draft, $restored->contentStatus());
        $this->assertSame('shared-slug-2', $restored->slug);
        $this->assertFalse($restored->trashed());
    }

    public function test_restore_archived_to_draft_or_published(): void
    {
        $editor = $this->makeUser(UserRole::Editor);
        $archived = Post::factory()->archived()->create();

        $this->lifecycle->restore($archived, $editor, ContentStatus::Draft);
        $this->assertSame(ContentStatus::Draft, $archived->fresh()->contentStatus());

        $archivedAgain = Post::factory()->archived()->create();
        $this->lifecycle->restore($archivedAgain, $editor, ContentStatus::Published);
        $this->assertSame(ContentStatus::Published, $archivedAgain->fresh()->contentStatus());
        $this->assertNotNull($archivedAgain->fresh()->published_at);
    }

    public function test_hard_delete_only_from_trash_and_requires_force_delete_permission(): void
    {
        $editor = $this->makeUser(UserRole::Editor);
        $admin = $this->makeUser(UserRole::Administrator);
        $post = Post::factory()->create();

        try {
            $this->lifecycle->forceDelete($post, $admin);
            $this->fail('Expected ValidationException for non-trashed hard delete');
        } catch (ValidationException) {
            // expected
        }

        $this->lifecycle->trash($post);

        $trashed = Post::withTrashed()->findOrFail($post->id);

        try {
            $this->lifecycle->forceDelete($trashed, $editor);
            $this->fail('Expected ValidationException for editor force delete');
        } catch (ValidationException) {
            // expected
        }

        $this->lifecycle->forceDelete(Post::withTrashed()->findOrFail($post->id), $admin);
        $this->assertNull(Post::withTrashed()->find($post->id));
    }

    public function test_pages_share_the_same_lifecycle_transitions(): void
    {
        $editor = $this->makeUser(UserRole::Editor);
        $page = Page::factory()->create();

        $this->lifecycle->publish($page, $editor);
        $this->assertSame(ContentStatus::Published, $page->fresh()->contentStatus());

        $this->lifecycle->archive($page);
        $this->assertSame(ContentStatus::Archived, $page->fresh()->contentStatus());

        $this->lifecycle->trash($page);
        $this->assertTrue(Page::withTrashed()->findOrFail($page->id)->trashed());

        $this->lifecycle->restore(Page::withTrashed()->findOrFail($page->id), $editor);
        $this->assertSame(ContentStatus::Draft, $page->fresh()->contentStatus());
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $post = Post::factory()->create();

        $this->expectException(ValidationException::class);
        $this->lifecycle->unpublish($post);
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
