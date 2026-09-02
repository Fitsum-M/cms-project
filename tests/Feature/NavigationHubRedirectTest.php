<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\Content\CustomPostTypes;
use App\Filament\Pages\Content\PageHierarchy;
use App\Filament\Pages\Content\PagesGroup;
use App\Filament\Pages\Content\PageTemplates;
use App\Filament\Pages\Content\PostsGroup;
use App\Filament\Pages\Content\TaxonomiesGroup;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Iam\AddNewUser;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\CustomTaxonomies\CustomTaxonomyResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\PostTypes\PostTypeResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Tags\TagResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\DraftSummaryWidget;
use App\Filament\Widgets\OverviewStatsWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentContentWidget;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionClass;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NavigationHubRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_posts_hub_redirects_to_all_posts(): void
    {
        $this->actingAs($this->makeUser(UserRole::Administrator));

        Livewire::test(PostsGroup::class)
            ->assertRedirect(PostResource::getUrl('index'));
    }

    public function test_pages_hub_redirects_to_all_pages(): void
    {
        $this->actingAs($this->makeUser(UserRole::Administrator));

        Livewire::test(PagesGroup::class)
            ->assertRedirect(PageResource::getUrl('index'));
    }

    public function test_taxonomies_hub_redirects_to_categories(): void
    {
        $this->actingAs($this->makeUser(UserRole::Administrator));

        Livewire::test(TaxonomiesGroup::class)
            ->assertRedirect(CategoryResource::getUrl('index'));
    }

    public function test_custom_post_types_hub_redirects_to_manage_types_for_administrators(): void
    {
        $this->actingAs($this->makeUser(UserRole::Administrator));

        Livewire::test(CustomPostTypes::class)
            ->assertRedirect(PostTypeResource::getUrl('index'));
    }

    public function test_add_new_user_hub_redirects_to_user_resource_create(): void
    {
        $this->actingAs($this->makeUser(UserRole::Administrator));

        Livewire::test(AddNewUser::class)
            ->assertRedirect(UserResource::getUrl('create'));
    }

    public function test_user_resource_owns_all_users_navigation(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $this->actingAs($admin);
        $this->assertTrue(UserResource::shouldRegisterNavigation());
        $this->assertSame('All Users', UserResource::getNavigationLabel());
        $this->assertSame('iam/users', UserResource::getSlug());
    }

    public function test_content_and_iam_navigation_labels_match_srs_10_1(): void
    {
        $this->assertSame('Posts', PostsGroup::getNavigationLabel());
        $this->assertSame('All Posts', PostResource::getNavigationLabel());
        $this->assertSame('Custom Post Types', CustomPostTypes::getNavigationLabel());
        $this->assertSame('Manage Types', PostTypeResource::getNavigationLabel());

        $this->assertSame('Pages', PagesGroup::getNavigationLabel());
        $this->assertSame('All Pages', PageResource::getNavigationLabel());
        $this->assertSame('Page Hierarchy', PageHierarchy::getNavigationLabel());
        $this->assertSame('Page Templates', PageTemplates::getNavigationLabel());

        $this->assertSame('Taxonomies', TaxonomiesGroup::getNavigationLabel());
        $this->assertSame('Categories', CategoryResource::getNavigationLabel());
        $this->assertSame('Tags', TagResource::getNavigationLabel());
        $this->assertSame('Custom Taxonomies', CustomTaxonomyResource::getNavigationLabel());

        $this->assertSame('All Users', UserResource::getNavigationLabel());
        $this->assertSame('Add New User', AddNewUser::getNavigationLabel());
        $this->assertSame('Roles & Permissions', RoleResource::getNavigationLabel());
    }

    public function test_role_resource_owns_iam_roles_navigation_without_per_role_items(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $this->actingAs($admin);
        $this->assertTrue(RoleResource::shouldRegisterNavigation());
        $this->assertSame('iam/roles', RoleResource::getSlug());
    }

    public function test_content_navigation_parent_nesting_matches_srs_10_1(): void
    {
        $this->assertSame('Posts', $this->navigationParentItem(PostResource::class));
        $this->assertSame('Posts', $this->navigationParentItem(CustomPostTypes::class));
        $this->assertSame('Custom Post Types', $this->navigationParentItem(PostTypeResource::class));

        $this->assertSame('Pages', $this->navigationParentItem(PageResource::class));
        $this->assertSame('Pages', $this->navigationParentItem(PageHierarchy::class));
        $this->assertSame('Pages', $this->navigationParentItem(PageTemplates::class));

        $this->assertSame('Taxonomies', $this->navigationParentItem(CategoryResource::class));
        $this->assertSame('Taxonomies', $this->navigationParentItem(TagResource::class));
        $this->assertSame('Taxonomies', $this->navigationParentItem(CustomTaxonomyResource::class));
    }

    public function test_dashboard_exposes_srs_10_1_sections_as_widgets(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $widgets = Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->instance()
            ->getWidgets();

        $this->assertContains(OverviewStatsWidget::class, $widgets);
        $this->assertContains(RecentContentWidget::class, $widgets);
        $this->assertContains(DraftSummaryWidget::class, $widgets);
        $this->assertContains(QuickActionsWidget::class, $widgets);

        $this->assertSame(
            'Overview',
            (new ReflectionClass(OverviewStatsWidget::class))->getProperty('heading')->getDefaultValue(),
        );
        $this->assertSame('Dashboard', Dashboard::getNavigationLabel());
    }

    /**
     * @param  class-string  $class
     */
    private function navigationParentItem(string $class): ?string
    {
        return (new ReflectionClass($class))->getStaticPropertyValue('navigationParentItem');
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
