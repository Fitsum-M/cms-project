<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\PostVisibility;
use App\Enums\UserStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PostTest extends TestCase
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

    public function test_create_post_assigns_author_slug_and_defaults(): void
    {
        $admin = $this->makeUser('Administrator');

        $post = app(PostService::class)->create([
            'title' => 'Hello World',
            'body' => '<p>First paragraph of content goes here for excerpt generation.</p>',
            'status' => ContentStatus::Draft->value,
            'visibility' => PostVisibility::Public->value,
        ], $admin);

        $this->assertSame('Hello World', $post->title);
        $this->assertSame('hello-world', $post->slug);
        $this->assertSame($admin->id, $post->author_id);
        $this->assertSame(ContentStatus::Draft, $post->status);
        $this->assertSame(PostVisibility::Public, $post->visibility);
        $this->assertNotNull($post->published_at);
        $this->assertSame('First paragraph of content goes here for excerpt generation.', $post->excerpt);
    }

    public function test_author_cannot_reassign_authorship(): void
    {
        $author = $this->makeUser('Author');
        $other = $this->makeUser('Editor');

        $post = app(PostService::class)->create([
            'title' => 'Mine',
            'author_id' => $other->id,
        ], $author);

        $this->assertSame($author->id, $post->author_id);

        $updated = app(PostService::class)->update($post, [
            'author_id' => $other->id,
        ], $author);

        $this->assertSame($author->id, $updated->author_id);
    }

    public function test_editor_can_reassign_author(): void
    {
        $editor = $this->makeUser('Editor');
        $author = $this->makeUser('Author');

        $post = app(PostService::class)->create([
            'title' => 'Reassign me',
            'author_id' => $author->id,
        ], $editor);

        $this->assertSame($author->id, $post->author_id);
    }

    public function test_password_required_for_password_protected_visibility(): void
    {
        $admin = $this->makeUser('Administrator');

        $this->expectException(ValidationException::class);

        app(PostService::class)->create([
            'title' => 'Secret',
            'visibility' => PostVisibility::PasswordProtected->value,
        ], $admin);
    }

    public function test_password_protected_post_stores_hashed_password(): void
    {
        $admin = $this->makeUser('Administrator');

        $post = app(PostService::class)->create([
            'title' => 'Secret',
            'visibility' => PostVisibility::PasswordProtected->value,
            'password' => 's3cret!',
        ], $admin);

        $this->assertSame(PostVisibility::PasswordProtected, $post->visibility);
        $this->assertTrue(Hash::check('s3cret!', $post->password));
    }

    public function test_future_dated_published_post_is_not_publicly_accessible(): void
    {
        $admin = $this->makeUser('Administrator');

        $post = app(PostService::class)->create([
            'title' => 'Scheduled',
            'status' => ContentStatus::Published->value,
            'visibility' => PostVisibility::Public->value,
            'published_at' => now()->addDays(3),
        ], $admin);

        $this->assertSame(ContentStatus::Published, $post->status);
        $this->assertFalse($post->isPubliclyAccessible());
    }

    public function test_private_and_password_posts_are_not_publicly_accessible(): void
    {
        $admin = $this->makeUser('Administrator');

        $private = app(PostService::class)->create([
            'title' => 'Private',
            'status' => ContentStatus::Published->value,
            'visibility' => PostVisibility::Private->value,
            'published_at' => now()->subHour(),
        ], $admin);

        $protected = app(PostService::class)->create([
            'title' => 'Protected',
            'status' => ContentStatus::Published->value,
            'visibility' => PostVisibility::PasswordProtected->value,
            'password' => 'x',
            'published_at' => now()->subHour(),
        ], $admin);

        $this->assertFalse($private->isPubliclyAccessible());
        $this->assertFalse($protected->isPubliclyAccessible());
    }

    public function test_filament_create_and_list_posts(): void
    {
        $admin = $this->makeUser('Administrator');

        Livewire::actingAs($admin)
            ->test(CreatePost::class)
            ->fillForm([
                'title' => 'From Filament',
                'body' => '<p>Body copy</p>',
                'status' => ContentStatus::Draft->value,
                'visibility' => PostVisibility::Public->value,
                'post_type' => 'post',
                'author_id' => $admin->id,
                'published_at' => now(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('posts', [
            'title' => 'From Filament',
            'author_id' => $admin->id,
            'slug' => 'from-filament',
        ]);

        Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(Post::query()->get());
    }

    public function test_author_sees_only_own_posts_in_list(): void
    {
        $author = $this->makeUser('Author');
        $other = $this->makeUser('Editor');

        $own = Post::factory()->create(['author_id' => $author->id, 'title' => 'Own post']);
        $foreign = Post::factory()->create(['author_id' => $other->id, 'title' => 'Other post']);

        Livewire::actingAs($author)
            ->test(ListPosts::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$foreign]);
    }

    public function test_author_cannot_edit_others_post(): void
    {
        $author = $this->makeUser('Author');
        $foreign = Post::factory()->create([
            'author_id' => $this->makeUser('Administrator')->id,
        ]);

        $this->assertFalse($author->can('update', $foreign));
        $this->assertFalse($author->can('view', $foreign));
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
