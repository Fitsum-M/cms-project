<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\User;
use App\Services\ContentLifecycleService;
use App\Services\ContentSeoService;
use App\Services\PostService;
use App\Services\PostTypeService;
use App\Support\PostTypeRegistry;
use App\Support\Settings\SeoDefaultsSettings;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SeoDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomPostTypeBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        $this->seed(MediaSettingsSeeder::class);
        $this->seed(SeoDefaultsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_custom_type_inherits_publishing_lifecycle(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        app(PostTypeService::class)->create([
            'plural_name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
        ]);

        $post = app(PostService::class)->create([
            'title' => 'Breaking Story',
            'post_type' => 'news',
            'status' => ContentStatus::Draft->value,
            'body' => '<p>Full story body for publishing.</p>',
        ], $admin);

        $this->assertSame('news', $post->post_type);
        $this->assertSame(ContentStatus::Draft, $post->contentStatus());

        app(ContentLifecycleService::class)->publish($post, $admin);

        $this->assertSame(ContentStatus::Published, $post->fresh()->contentStatus());
        $this->assertTrue($post->fresh()->isPubliclyAccessible());
    }

    public function test_schema_inherits_from_post_type_then_seo_defaults(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        app(SeoDefaultsSettings::class)->save([
            ...app(SeoDefaultsSettings::class)->all(),
            SeoDefaultsSettings::SCHEMA_TYPE => 'WebPage',
        ]);

        app(PostTypeService::class)->create([
            'plural_name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'default_schema_type' => 'NewsArticle',
        ]);

        $post = app(PostService::class)->create([
            'title' => 'Schema Story',
            'post_type' => 'news',
        ], $admin);

        $resolved = app(ContentSeoService::class)->resolve($post);
        $this->assertSame('NewsArticle', $resolved->schemaType);
        $this->assertSame('post_type', $resolved->sources['schema_type']);

        app(PostService::class)->update($post, [
            'seo' => ['schema_type' => 'Article'],
        ], $admin);

        $resolved = app(ContentSeoService::class)->resolve($post->fresh());
        $this->assertSame('Article', $resolved->schemaType);
        $this->assertSame('content', $resolved->sources['schema_type']);
    }

    public function test_disabled_excerpt_and_featured_image_are_not_persisted(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        app(PostTypeService::class)->create([
            'plural_name' => 'Notices',
            'singular_name' => 'Notice',
            'slug' => 'notices',
            'supports_excerpt' => false,
            'supports_featured_image' => false,
        ]);

        $this->assertFalse(PostTypeRegistry::supportsExcerpt('notices'));
        $this->assertFalse(PostTypeRegistry::supportsFeaturedImage('notices'));

        $post = app(PostService::class)->create([
            'title' => 'Plain Notice',
            'post_type' => 'notices',
            'body' => '<p>Body text that would normally generate an excerpt.</p>',
            'excerpt' => 'Should be ignored',
        ], $admin);

        $this->assertNull($post->excerpt);
        $this->assertNull($post->featured_image_id);

        $this->actingAs($admin);

        Livewire::test(CreatePost::class)
            ->fillForm(['post_type' => 'notices'])
            ->assertFormFieldIsHidden('excerpt')
            ->assertFormFieldIsHidden('featured_image_id');
    }

    public function test_seo_panel_works_on_custom_type_content(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        app(PostTypeService::class)->create([
            'plural_name' => 'Guides',
            'singular_name' => 'Guide',
            'slug' => 'guides',
            'default_schema_type' => 'Article',
        ]);

        $post = app(PostService::class)->create([
            'title' => 'Getting Started',
            'post_type' => 'guides',
            'seo' => [
                'meta_title' => 'Guide Meta',
                'meta_description' => 'Guide description for search.',
            ],
        ], $admin);

        $seo = $post->seoRecord();
        $this->assertSame('Guide Meta', $seo?->title);

        $resolved = app(ContentSeoService::class)->resolve($post);
        $this->assertSame('Guide Meta', $resolved->metaTitle);
        $this->assertSame('Article', $resolved->schemaType);
    }

    public function test_list_and_edit_use_custom_type_labels(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $this->actingAs($admin);

        app(PostTypeService::class)->create([
            'plural_name' => 'Case Studies',
            'singular_name' => 'Case Study',
            'slug' => 'case-studies',
        ]);

        $post = app(PostService::class)->create([
            'title' => 'Acme Rollout',
            'post_type' => 'case-studies',
        ], $admin);

        Livewire::withQueryParams(['post_type' => 'case-studies'])
            ->test(ListPosts::class)
            ->assertSee('Case Studies');

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertSee('Edit Case Study');
    }

    public function test_author_can_create_and_submit_custom_type_for_review(): void
    {
        $author = $this->makeUser(UserRole::Author);

        app(PostTypeService::class)->create([
            'plural_name' => 'Reports',
            'singular_name' => 'Report',
            'slug' => 'reports',
        ]);

        $post = app(PostService::class)->create([
            'title' => 'Monthly Report',
            'post_type' => 'reports',
            'status' => ContentStatus::Published->value,
        ], $author);

        // Authors cannot publish — lifecycle moves to pending.
        $this->assertSame(ContentStatus::PendingReview, $post->contentStatus());
        $this->assertSame($author->id, $post->author_id);
        $this->assertSame('reports', $post->post_type);
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
