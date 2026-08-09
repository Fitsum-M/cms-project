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
use App\Models\SeoMetadata;
use App\Models\User;
use App\Services\ContentLifecycleService;
use App\Services\ContentSeoService;
use App\Services\MediaDeletionService;
use App\Services\MediaUploadService;
use App\Services\PageService;
use App\Services\PostService;
use App\Support\Settings\SeoDefaultsSettings;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SeoDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ContentSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        $this->seed(MediaSettingsSeeder::class);
        $this->seed(SeoDefaultsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_post_seo_fields_persist_and_empty_strings_become_null(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $post = app(PostService::class)->create([
            'title' => 'SEO Post',
            'excerpt' => 'Post excerpt for fallbacks.',
            'seo' => [
                'meta_title' => 'Custom Meta Title',
                'meta_description' => 'Custom meta description for search.',
                'focus_keyword' => 'laravel cms',
                'canonical_url' => 'https://example.com/custom-canonical',
                'robots' => ['noindex', 'nofollow'],
                'og_title' => 'OG Title',
                'og_description' => 'OG Description',
                'schema_type' => 'Article',
            ],
        ], $admin);

        $seo = $post->seoRecord();
        $this->assertNotNull($seo);
        $this->assertSame('Custom Meta Title', $seo->title);
        $this->assertSame('Custom meta description for search.', $seo->description);
        $this->assertSame('laravel cms', $seo->focus_keyword);
        $this->assertSame('https://example.com/custom-canonical', $seo->canonical_url);
        $this->assertSame('noindex, nofollow', $seo->robots);
        $this->assertSame('OG Title', $seo->og_title);
        $this->assertSame('OG Description', $seo->og_description);
        $this->assertSame('Article', $seo->schema_type);

        app(PostService::class)->update($post, [
            'seo' => [
                'meta_title' => '   ',
                'meta_description' => '',
                'focus_keyword' => '',
                'canonical_url' => '',
                'robots' => [],
                'og_title' => '',
                'og_description' => '',
                'schema_type' => null,
            ],
        ], $admin);

        $seo = $post->fresh()->seoRecord();
        $this->assertNull($seo?->title);
        $this->assertNull($seo?->description);
        $this->assertNull($seo?->focus_keyword);
        $this->assertNull($seo?->canonical_url);
        $this->assertNull($seo?->getAttributes()['robots'] ?? null);
        $this->assertNull($seo?->og_title);
        $this->assertNull($seo?->schema_type);
    }

    public function test_inheritance_uses_defaults_then_dynamic_fallback(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        app(SeoDefaultsSettings::class)->save([
            ...app(SeoDefaultsSettings::class)->all(),
            SeoDefaultsSettings::META_TITLE_PATTERN => '{title} | {site_title}',
            SeoDefaultsSettings::META_DESCRIPTION => 'Site-wide default description',
            SeoDefaultsSettings::SCHEMA_TYPE => 'WebPage',
            SeoDefaultsSettings::ROBOTS => ['index', 'follow', 'noarchive'],
        ]);

        $post = app(PostService::class)->create([
            'title' => 'Hello World',
            'excerpt' => 'Excerpt text here.',
        ], $admin);

        $resolved = app(ContentSeoService::class)->resolve($post);

        $this->assertStringContainsString('Hello World', $resolved->metaTitle);
        $this->assertSame('defaults', $resolved->sources['meta_title']);
        $this->assertSame('Site-wide default description', $resolved->metaDescription);
        $this->assertSame('defaults', $resolved->sources['meta_description']);
        $this->assertSame(['index', 'follow', 'noarchive'], $resolved->robots);
        $this->assertSame('defaults', $resolved->sources['robots']);
        $this->assertSame('WebPage', $resolved->schemaType);
        $this->assertSame($resolved->metaTitle, $resolved->ogTitle);
        $this->assertSame('meta_title', $resolved->sources['og_title']);

        app(SeoDefaultsSettings::class)->save([
            ...app(SeoDefaultsSettings::class)->all(),
            SeoDefaultsSettings::META_DESCRIPTION => '',
        ]);

        $resolved = app(ContentSeoService::class)->resolve($post->fresh());
        $this->assertSame('Excerpt text here.', $resolved->metaDescription);
        $this->assertSame('dynamic', $resolved->sources['meta_description']);
    }

    public function test_og_image_falls_back_to_featured_then_defaults(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $featured = $this->uploadImage($admin, 'featured.png');
        $defaultOg = $this->uploadImage($admin, 'default-og.png');
        $customOg = $this->uploadImage($admin, 'custom-og.png');

        app(SeoDefaultsSettings::class)->save([
            ...app(SeoDefaultsSettings::class)->all(),
            SeoDefaultsSettings::OG_IMAGE_ID => $defaultOg->id,
        ]);

        $post = app(PostService::class)->create([
            'title' => 'Images',
            'featured_image_id' => $featured->id,
        ], $admin);

        $resolved = app(ContentSeoService::class)->resolve($post);
        $this->assertSame($featured->id, $resolved->ogImageId);
        $this->assertSame('featured', $resolved->sources['og_image']);

        app(PostService::class)->update($post, [
            'seo' => ['og_image_id' => $customOg->id],
        ], $admin);

        $resolved = app(ContentSeoService::class)->resolve($post->fresh());
        $this->assertSame($customOg->id, $resolved->ogImageId);
        $this->assertSame('content', $resolved->sources['og_image']);

        $page = app(PageService::class)->create([
            'title' => 'About',
        ], $admin);

        $resolved = app(ContentSeoService::class)->resolve($page);
        $this->assertSame($defaultOg->id, $resolved->ogImageId);
        $this->assertSame('defaults', $resolved->sources['og_image']);
    }

    public function test_canonical_url_must_be_absolute(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $this->expectException(ValidationException::class);

        app(PostService::class)->create([
            'title' => 'Bad canonical',
            'seo' => [
                'canonical_url' => '/relative-path',
            ],
        ], $admin);
    }

    public function test_contributor_cannot_persist_seo_fields(): void
    {
        $contributor = $this->makeUser(UserRole::Contributor);
        $this->assertFalse($contributor->can(Permission::SeoConfigureContent->value));

        $post = app(PostService::class)->create([
            'title' => 'Contributor Post',
            'seo' => [
                'meta_title' => 'Should Not Save',
                'schema_type' => 'Article',
            ],
        ], $contributor);

        $seo = $post->seoRecord();
        $this->assertTrue($seo === null || $seo->title === null);
        $this->assertTrue($seo === null || $seo->schema_type === null);
    }

    public function test_duplicate_copies_seo_metadata(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $source = app(PostService::class)->create([
            'title' => 'Original',
            'seo' => [
                'meta_title' => 'Original Meta',
                'focus_keyword' => 'copy-me',
                'schema_type' => 'BlogPosting',
            ],
        ], $admin);

        $copy = app(PostService::class)->duplicate($source, $admin);
        $seo = $copy->seoRecord();

        $this->assertSame('Original Meta', $seo?->title);
        $this->assertSame('copy-me', $seo?->focus_keyword);
        $this->assertSame('BlogPosting', $seo?->schema_type);
    }

    public function test_force_delete_removes_seo_metadata(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $post = app(PostService::class)->create([
            'title' => 'Delete Me',
            'seo' => ['meta_title' => 'Gone Soon'],
        ], $admin);

        $seoId = $post->seoRecord()?->id;
        $this->assertNotNull($seoId);

        app(ContentLifecycleService::class)->trash($post);
        app(ContentLifecycleService::class)->forceDelete($post->fresh(), $admin);

        $this->assertDatabaseMissing('seo', ['id' => $seoId]);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_page_seo_works_identically(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $page = app(PageService::class)->create([
            'title' => 'Contact',
            'body' => '<p>Reach us anytime for support and sales.</p>',
            'seo' => [
                'meta_title' => 'Contact Us',
                'schema_type' => 'ContactPage',
            ],
        ], $admin);

        $resolved = app(ContentSeoService::class)->resolve($page);
        $this->assertSame('Contact Us', $resolved->metaTitle);
        $this->assertSame('content', $resolved->sources['meta_title']);
        $this->assertSame('ContactPage', $resolved->schemaType);
    }

    public function test_og_image_blocks_media_delete_and_force_clears(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $asset = $this->uploadImage($admin, 'seo-og.jpg');

        app(PostService::class)->create([
            'title' => 'Uses OG',
            'seo' => ['og_image_id' => $asset->id],
        ], $admin);

        $this->assertTrue($asset->fresh()->isReferenced());

        try {
            app(MediaDeletionService::class)->delete($asset);
            $this->fail('Expected delete to be blocked.');
        } catch (ValidationException) {
            // expected
        }

        app(MediaDeletionService::class)->forceDelete($asset->fresh());
        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        $this->assertNull(SeoMetadata::query()->whereNotNull('og_image_id')->where('og_image_id', $asset->id)->first());
    }

    public function test_filament_create_post_saves_seo_panel(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $this->actingAs($admin);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Livewire SEO',
                'status' => ContentStatus::Draft->value,
                'seo' => [
                    'meta_title' => 'LW Meta',
                    'meta_description' => 'LW description under 160.',
                    'focus_keyword' => 'filament',
                    'robots' => ['index', 'follow'],
                    'schema_type' => 'Article',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('title', 'Livewire SEO')->first();
        $this->assertNotNull($post);
        $this->assertSame('LW Meta', $post->seoRecord()?->title);
        $this->assertSame('filament', $post->seoRecord()?->focus_keyword);
    }

    public function test_filament_edit_loads_and_updates_seo(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $this->actingAs($admin);

        $post = app(PostService::class)->create([
            'title' => 'Edit SEO',
            'seo' => [
                'meta_title' => 'Before',
                'schema_type' => 'Article',
            ],
        ], $admin);

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertFormSet([
                'seo.meta_title' => 'Before',
                'seo.schema_type' => 'Article',
            ])
            ->fillForm([
                'seo' => [
                    'meta_title' => 'After',
                    'schema_type' => 'NewsArticle',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('After', $post->fresh()->seoRecord()?->title);
        $this->assertSame('NewsArticle', $post->fresh()->seoRecord()?->schema_type);
    }

    public function test_get_dynamic_seo_data_uses_resolved_values(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $post = app(PostService::class)->create([
            'title' => 'Dynamic',
            'seo' => [
                'meta_title' => 'Resolved Title',
                'meta_description' => 'Resolved description',
                'robots' => ['noindex'],
            ],
        ], $admin);

        $data = $post->getDynamicSEOData();
        $this->assertSame('Resolved Title', $data->title);
        $this->assertSame('Resolved description', $data->description);
        $this->assertSame('noindex', $data->robots);
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
