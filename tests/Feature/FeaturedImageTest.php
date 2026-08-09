<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\User;
use App\Services\MediaDeletionService;
use App\Services\MediaUploadService;
use App\Services\PostService;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FeaturedImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        $this->seed(MediaSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_post_can_assign_featured_image_from_media_library(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $asset = $this->uploadImage($admin, 'hero.jpg');

        $post = app(PostService::class)->create([
            'title' => 'With Image',
            'featured_image_id' => $asset->id,
            'status' => ContentStatus::Draft->value,
        ], $admin);

        $this->assertSame($asset->id, $post->featured_image_id);
        $this->assertTrue($post->hasFeaturedImage());
        $this->assertFalse($post->hasBrokenFeaturedImage());
        $this->assertNotNull($post->featuredImageUrl());
        $this->assertTrue($asset->fresh()->isReferenced());
    }

    public function test_non_image_media_cannot_be_featured(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $doc = app(MediaUploadService::class)->upload(
            UploadedFile::fake()->create('notes.pdf', 40, 'application/pdf'),
            $admin,
        );

        $this->expectException(ValidationException::class);

        app(PostService::class)->create([
            'title' => 'Bad featured',
            'featured_image_id' => $doc->id,
        ], $admin);
    }

    public function test_missing_media_id_is_rejected(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $this->expectException(ValidationException::class);

        app(PostService::class)->create([
            'title' => 'Missing media',
            'featured_image_id' => 999999,
        ], $admin);
    }

    public function test_delete_media_blocked_while_used_as_featured_image(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $asset = $this->uploadImage($admin, 'used.jpg');

        app(PostService::class)->create([
            'title' => 'Uses media',
            'featured_image_id' => $asset->id,
        ], $admin);

        try {
            app(MediaDeletionService::class)->delete($asset);
            $this->fail('Expected ValidationException for referenced featured image.');
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->implode(' ');
            $this->assertStringContainsString('Featured image', $message);
            $this->assertStringContainsString('Uses media', $message);
        }

        $this->assertDatabaseHas('media_assets', ['id' => $asset->id]);
        $this->assertDatabaseHas('posts', [
            'title' => 'Uses media',
            'featured_image_id' => $asset->id,
        ]);
    }

    public function test_force_delete_clears_featured_image_reference(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $asset = $this->uploadImage($admin, 'force-feat.jpg');

        $post = app(PostService::class)->create([
            'title' => 'Force clear',
            'featured_image_id' => $asset->id,
        ], $admin);

        app(MediaDeletionService::class)->forceDelete($asset);

        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        $this->assertNull($post->fresh()->featured_image_id);
        $this->assertFalse($post->fresh()->hasFeaturedImage());
    }

    public function test_clearing_featured_image_on_update(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $asset = $this->uploadImage($admin, 'clear.jpg');

        $post = app(PostService::class)->create([
            'title' => 'Clear me',
            'featured_image_id' => $asset->id,
        ], $admin);

        $updated = app(PostService::class)->update($post, [
            'featured_image_id' => null,
        ], $admin);

        $this->assertNull($updated->featured_image_id);
        $this->assertFalse($asset->fresh()->isReferenced());
    }

    public function test_filament_create_and_edit_featured_image(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $asset = $this->uploadImage($admin, 'filament.jpg');

        Livewire::actingAs($admin)
            ->test(CreatePost::class)
            ->fillForm([
                'title' => 'Filament Featured',
                'featured_image_id' => $asset->id,
                'author_id' => $admin->id,
                'post_type' => 'post',
                'published_at' => now(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('title', 'Filament Featured')->first();
        $this->assertNotNull($post);
        $this->assertSame($asset->id, $post->featured_image_id);

        $other = $this->uploadImage($admin, 'other.jpg');

        Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertFormSet([
                'featured_image_id' => $asset->id,
            ])
            ->fillForm([
                'featured_image_id' => $other->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($other->id, $post->fresh()->featured_image_id);
    }

    public function test_duplicate_copies_featured_image(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $asset = $this->uploadImage($admin, 'dup.jpg');

        $source = app(PostService::class)->create([
            'title' => 'Source',
            'featured_image_id' => $asset->id,
        ], $admin);

        $copy = app(PostService::class)->duplicate($source, $admin);

        $this->assertSame($asset->id, $copy->featured_image_id);
    }

    private function uploadImage(User $user, string $name): MediaAsset
    {
        return app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image($name, 120, 90),
            $user,
        );
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
