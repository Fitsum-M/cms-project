<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\PostTypes\Pages\EditPostType;
use App\Models\Category;
use App\Models\CustomTaxonomy;
use App\Models\CustomTaxonomyTerm;
use App\Models\Tag;
use App\Models\User;
use App\Services\CustomTaxonomyService;
use App\Services\PostService;
use App\Services\PostTypeService;
use App\Support\PostTypeRegistry;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PostTypeTaxonomyAssociationTest extends TestCase
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

    public function test_post_type_can_associate_categories_tags_and_custom_taxonomies(): void
    {
        $admin = $this->makeUser('Administrator');

        $taxonomy = app(CustomTaxonomyService::class)->create([
            'name' => 'Industries',
            'structure_type' => 'flat',
            'post_type_keys' => ['post'],
        ]);

        $type = app(PostTypeService::class)->create([
            'plural_name' => 'Case Studies',
            'singular_name' => 'Case Study',
            'slug' => 'case-studies',
            'supports_categories' => true,
            'supports_tags' => false,
            'custom_taxonomy_ids' => [$taxonomy->id],
        ]);

        $this->assertTrue($type->supports_categories);
        $this->assertFalse($type->supports_tags);
        $this->assertSame([$taxonomy->id], $type->customTaxonomyIds());
        $this->assertTrue(PostTypeRegistry::supportsCategories('case-studies'));
        $this->assertFalse(PostTypeRegistry::supportsTags('case-studies'));
        $this->assertSame([$taxonomy->id], PostTypeRegistry::customTaxonomyIds('case-studies'));
        $this->assertContains('case-studies', $taxonomy->fresh()->postTypeKeys());
    }

    public function test_updating_associations_from_post_type_syncs_pivot(): void
    {
        $admin = $this->makeUser('Administrator');

        $alpha = app(CustomTaxonomyService::class)->create([
            'name' => 'Alpha',
            'structure_type' => 'flat',
            'post_type_keys' => ['post'],
        ]);
        $beta = app(CustomTaxonomyService::class)->create([
            'name' => 'Beta',
            'structure_type' => 'flat',
            'post_type_keys' => ['post'],
        ]);

        $type = app(PostTypeService::class)->create([
            'plural_name' => 'News',
            'singular_name' => 'News Item',
            'slug' => 'news',
            'custom_taxonomy_ids' => [$alpha->id],
        ]);

        app(PostTypeService::class)->update($type, [
            'custom_taxonomy_ids' => [$beta->id],
        ]);

        $this->assertSame([$beta->id], $type->fresh()->customTaxonomyIds());
        $this->assertNotContains('news', $alpha->fresh()->postTypeKeys());
        $this->assertContains('news', $beta->fresh()->postTypeKeys());
        $this->assertContains('post', $alpha->fresh()->postTypeKeys());
    }

    public function test_post_editor_hides_unsupported_taxonomies(): void
    {
        $admin = $this->makeUser('Administrator');
        $this->actingAs($admin);

        app(PostTypeService::class)->create([
            'plural_name' => 'Notices',
            'singular_name' => 'Notice',
            'slug' => 'notices',
            'supports_categories' => false,
            'supports_tags' => false,
            'custom_taxonomy_ids' => [],
        ]);

        Livewire::test(CreatePost::class)
            ->fillForm(['post_type' => 'notices'])
            ->assertFormSet(['post_type' => 'notices'])
            ->assertFormFieldIsHidden('category_ids')
            ->assertFormFieldIsHidden('tag_ids')
            ->assertFormFieldIsHidden('custom_term_ids');
    }

    public function test_post_editor_shows_associated_custom_taxonomy_terms_only(): void
    {
        $admin = $this->makeUser('Administrator');

        $associated = app(CustomTaxonomyService::class)->create([
            'name' => 'Sectors',
            'structure_type' => 'flat',
            'post_type_keys' => ['post'],
        ]);
        $other = app(CustomTaxonomyService::class)->create([
            'name' => 'Topics',
            'structure_type' => 'flat',
            'post_type_keys' => ['post'],
        ]);

        $termA = CustomTaxonomyTerm::query()->create([
            'custom_taxonomy_id' => $associated->id,
            'name' => 'Healthcare',
            'slug' => 'healthcare',
        ]);
        CustomTaxonomyTerm::query()->create([
            'custom_taxonomy_id' => $other->id,
            'name' => 'Politics',
            'slug' => 'politics',
        ]);

        app(PostTypeService::class)->create([
            'plural_name' => 'Reports',
            'singular_name' => 'Report',
            'slug' => 'reports',
            'supports_categories' => true,
            'supports_tags' => true,
            'custom_taxonomy_ids' => [$associated->id],
        ]);

        $post = app(PostService::class)->create([
            'title' => 'Annual Report',
            'post_type' => 'reports',
            'custom_term_ids' => [$termA->id],
        ], $admin);

        $this->assertTrue($post->customTaxonomyTerms->contains('id', $termA->id));

        $this->expectException(ValidationException::class);

        app(PostService::class)->create([
            'title' => 'Bad Terms',
            'post_type' => 'reports',
            'custom_term_ids' => [CustomTaxonomyTerm::query()->where('slug', 'politics')->value('id')],
        ], $admin);
    }

    public function test_unsupported_categories_and_tags_are_cleared_on_save(): void
    {
        $admin = $this->makeUser('Administrator');

        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        app(PostTypeService::class)->create([
            'plural_name' => 'Alerts',
            'singular_name' => 'Alert',
            'slug' => 'alerts',
            'supports_categories' => false,
            'supports_tags' => false,
        ]);

        $post = app(PostService::class)->create([
            'title' => 'System Alert',
            'post_type' => 'alerts',
            'category_ids' => [$category->id],
            'tag_ids' => [$tag->id],
        ], $admin);

        $this->assertCount(0, $post->categories);
        $this->assertCount(0, $post->tags);
    }

    public function test_standard_post_still_supports_categories_and_tags(): void
    {
        $this->assertTrue(PostTypeRegistry::supportsCategories('post'));
        $this->assertTrue(PostTypeRegistry::supportsTags('post'));
    }

    public function test_filament_edit_persists_taxonomy_toggles(): void
    {
        $admin = $this->makeUser('Administrator');
        $this->actingAs($admin);

        $taxonomy = app(CustomTaxonomyService::class)->create([
            'name' => 'Regions',
            'structure_type' => 'hierarchical',
            'post_type_keys' => ['post'],
        ]);

        $type = app(PostTypeService::class)->create([
            'plural_name' => 'Guides',
            'singular_name' => 'Guide',
            'slug' => 'guides',
            'supports_categories' => true,
            'supports_tags' => true,
        ]);

        Livewire::test(EditPostType::class, ['record' => $type->getRouteKey()])
            ->assertFormSet([
                'supports_categories' => true,
                'supports_tags' => true,
                'custom_taxonomy_ids' => [],
            ])
            ->fillForm([
                'supports_categories' => false,
                'supports_tags' => true,
                'custom_taxonomy_ids' => [$taxonomy->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $type->fresh();
        $this->assertFalse($fresh->supports_categories);
        $this->assertTrue($fresh->supports_tags);
        $this->assertSame([$taxonomy->id], $fresh->customTaxonomyIds());
    }

    public function test_deleting_post_type_clears_taxonomy_associations(): void
    {
        $taxonomy = app(CustomTaxonomyService::class)->create([
            'name' => 'Formats',
            'structure_type' => 'flat',
            'post_type_keys' => ['post'],
        ]);

        $type = app(PostTypeService::class)->create([
            'plural_name' => 'Ephemera',
            'singular_name' => 'Item',
            'slug' => 'ephemera',
            'custom_taxonomy_ids' => [$taxonomy->id],
        ]);

        app(PostTypeService::class)->delete($type);

        $this->assertDatabaseMissing('post_types', ['slug' => 'ephemera']);
        $this->assertDatabaseMissing('custom_taxonomy_post_type', [
            'post_type_key' => 'ephemera',
            'custom_taxonomy_id' => $taxonomy->id,
        ]);
        $this->assertContains('post', $taxonomy->fresh()->postTypeKeys());
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
