<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\User;
use App\Services\MediaDeletionService;
use App\Services\MediaUploadService;
use App\Services\PageService;
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

class PageFeaturedImageTest extends TestCase
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

    public function test_page_can_assign_featured_image_from_media_library(): void
    {
        $admin = $this->makeUser('Administrator');
        $asset = $this->uploadImage($admin, 'page-hero.jpg');

        $page = app(PageService::class)->create([
            'title' => 'With Image',
            'featured_image_id' => $asset->id,
            'status' => ContentStatus::Draft->value,
        ], $admin);

        $this->assertSame($asset->id, $page->featured_image_id);
        $this->assertTrue($page->hasFeaturedImage());
        $this->assertFalse($page->hasBrokenFeaturedImage());
        $this->assertNotNull($page->featuredImageUrl());
        $this->assertTrue($asset->fresh()->isReferenced());
    }

    public function test_non_image_media_cannot_be_featured_on_page(): void
    {
        $admin = $this->makeUser('Administrator');
        $doc = app(MediaUploadService::class)->upload(
            UploadedFile::fake()->create('notes.pdf', 40, 'application/pdf'),
            $admin,
        );

        $this->expectException(ValidationException::class);

        app(PageService::class)->create([
            'title' => 'Bad featured',
            'featured_image_id' => $doc->id,
        ], $admin);
    }

    public function test_missing_media_id_is_rejected_for_page(): void
    {
        $admin = $this->makeUser('Administrator');

        $this->expectException(ValidationException::class);

        app(PageService::class)->create([
            'title' => 'Missing media',
            'featured_image_id' => 999999,
        ], $admin);
    }

    public function test_delete_media_blocked_while_used_as_page_featured_image(): void
    {
        $admin = $this->makeUser('Administrator');
        $asset = $this->uploadImage($admin, 'used-page.jpg');

        app(PageService::class)->create([
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
        $this->assertDatabaseHas('pages', [
            'title' => 'Uses media',
            'featured_image_id' => $asset->id,
        ]);
    }

    public function test_force_delete_clears_page_featured_image_reference(): void
    {
        $admin = $this->makeUser('Administrator');
        $asset = $this->uploadImage($admin, 'force-page-feat.jpg');

        $page = app(PageService::class)->create([
            'title' => 'Force clear',
            'featured_image_id' => $asset->id,
        ], $admin);

        app(MediaDeletionService::class)->forceDelete($asset);

        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        $this->assertNull($page->fresh()->featured_image_id);
        $this->assertFalse($page->fresh()->hasFeaturedImage());
    }

    public function test_clearing_page_featured_image_on_update(): void
    {
        $admin = $this->makeUser('Administrator');
        $asset = $this->uploadImage($admin, 'clear-page.jpg');

        $page = app(PageService::class)->create([
            'title' => 'Clear me',
            'featured_image_id' => $asset->id,
        ], $admin);

        $updated = app(PageService::class)->update($page, [
            'featured_image_id' => null,
        ], $admin);

        $this->assertNull($updated->featured_image_id);
        $this->assertFalse($asset->fresh()->isReferenced());
    }

    public function test_filament_create_and_edit_page_featured_image(): void
    {
        $admin = $this->makeUser('Administrator');
        $asset = $this->uploadImage($admin, 'filament-page.jpg');

        Livewire::actingAs($admin)
            ->test(CreatePage::class)
            ->fillForm([
                'title' => 'Filament Featured Page',
                'featured_image_id' => $asset->id,
                'author_id' => $admin->id,
                'published_at' => now(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::query()->where('title', 'Filament Featured Page')->first();
        $this->assertNotNull($page);
        $this->assertSame($asset->id, $page->featured_image_id);

        $other = $this->uploadImage($admin, 'other-page.jpg');

        Livewire::actingAs($admin)
            ->test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormSet([
                'featured_image_id' => $asset->id,
            ])
            ->fillForm([
                'featured_image_id' => $other->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($other->id, $page->fresh()->featured_image_id);
    }

    public function test_published_page_shows_featured_image_on_frontend(): void
    {
        $admin = $this->makeUser('Administrator');
        $asset = $this->uploadImage($admin, 'frontend-page.jpg');

        $page = app(PageService::class)->create([
            'title' => 'Public With Image',
            'slug' => 'public-with-image',
            'body' => '<p>Hello page.</p>',
            'featured_image_id' => $asset->id,
            'status' => ContentStatus::Published->value,
            'published_at' => now()->subMinute(),
        ], $admin);

        $response = $this->get(route('frontend.pages.show', $page->slug));

        $response->assertOk();
        $response->assertSee('Public With Image');
        $response->assertSee($page->featuredImageUrl('large') ?? $page->featuredImageUrl(), false);
    }

    public function test_page_resource_query_eager_loads_featured_image_to_avoid_n_plus_one(): void
    {
        $admin = $this->makeUser('Administrator');
        $this->actingAs($admin);

        $eagerLoads = \App\Filament\Resources\Pages\PageResource::getEloquentQuery()
            ->getEagerLoads();

        $this->assertArrayHasKey('author', $eagerLoads);
        $this->assertArrayHasKey('parent', $eagerLoads);
        $this->assertArrayHasKey('featuredImage', $eagerLoads);
    }

    private function uploadImage(User $user, string $name): MediaAsset
    {
        return app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image($name, 120, 90),
            $user,
        );
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
