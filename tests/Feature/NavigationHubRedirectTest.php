<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\Content\PageHierarchy;
use App\Filament\Pages\Content\PageTemplates;
use App\Filament\Pages\Dashboard;
use App\Filament\Navigation\CustomPostTypesNavigation;
use App\Filament\Navigation\PagesNavigation;
use App\Filament\Navigation\PostsNavigation;
use App\Filament\Navigation\TaxonomiesNavigation;
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
use App\Services\PostTypeService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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

    public function test_all_content_and_iam_navigation_hub_routes_are_removed(): void
    {
        $this->assertFileDoesNotExist(app_path('Filament/Pages/NavigationHubPage.php'));
        $this->assertFileDoesNotExist(resource_path('views/filament/pages/navigation-hub.blade.php'));

        foreach ([
            'filament.admin.pages.content.posts-hub',
            'filament.admin.pages.content.pages-hub',
            'filament.admin.pages.content.taxonomies-hub',
            'filament.admin.pages.content.posts.custom-types',
            'filament.admin.pages.iam.users',
            'filament.admin.pages.iam.users-hub',
            'filament.admin.pages.iam.users.create-hub',
        ] as $routeName) {
            $this->assertFalse(Route::has($routeName), "Hub route still registered: {$routeName}");
        }
    }

    public function test_posts_parent_nav_owned_without_hub(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $this->actingAs($admin);

        $this->assertFileDoesNotExist(app_path('Filament/Pages/Content/PostsGroup.php'));
        $this->assertFalse(Route::has('filament.admin.pages.content.posts-hub'));

        $parent = collect(PostsNavigation::items())->keyBy(fn ($item) => $item->getLabel());
        $this->assertTrue($parent['Posts']->isVisible());
        $this->assertSame(PostResource::getUrl('index'), $parent['Posts']->getUrl());

        $labels = collect(PostResource::getNavigationItems())
            ->map(fn ($item) => $item->getLabel())
            ->all();
        $this->assertContains('All Posts', $labels);
        $this->assertContains('Add New Post', $labels);

        $this->get(PostResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Add New Post', false);

        $createItems = collect(PostResource::getNavigationItems())->keyBy(fn ($item) => $item->getLabel());
        $this->assertTrue($createItems['Add New Post']->isActive());
        $this->assertFalse($createItems['All Posts']->isActive());
    }

    public function test_pages_parent_nav_owned_without_hub(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $this->actingAs($admin);

        $this->assertFileDoesNotExist(app_path('Filament/Pages/Content/PagesGroup.php'));
        $this->assertFalse(Route::has('filament.admin.pages.content.pages-hub'));

        $parent = collect(PagesNavigation::items())->keyBy(fn ($item) => $item->getLabel());
        $this->assertTrue($parent['Pages']->isVisible());
        $this->assertSame(PageResource::getUrl('index'), $parent['Pages']->getUrl());

        $labels = collect(PageResource::getNavigationItems())
            ->map(fn ($item) => $item->getLabel())
            ->all();
        $this->assertContains('All Pages', $labels);
        $this->assertContains('Add New Page', $labels);

        $this->get(PageResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Add New Page', false);

        $createItems = collect(PageResource::getNavigationItems())->keyBy(fn ($item) => $item->getLabel());
        $this->assertTrue($createItems['Add New Page']->isActive());
        $this->assertFalse($createItems['All Pages']->isActive());
    }

    public function test_taxonomies_parent_nav_owned_without_hub(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $this->actingAs($admin);

        $this->assertFileDoesNotExist(app_path('Filament/Pages/Content/TaxonomiesGroup.php'));
        $this->assertFalse(Route::has('filament.admin.pages.content.taxonomies-hub'));

        $items = collect(TaxonomiesNavigation::items())->keyBy(fn ($item) => $item->getLabel());
        $this->assertTrue($items['Taxonomies']->isVisible());
        $this->assertSame(CategoryResource::getUrl('index'), $items['Taxonomies']->getUrl());

        $this->get(CategoryResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Categories', false);
    }

    public function test_custom_post_types_nav_owned_without_hub(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $this->actingAs($admin);

        app(PostTypeService::class)->create([
            'plural_name' => 'Case Studies',
            'singular_name' => 'Case Study',
            'slug' => 'case-studies',
            'icon' => 'heroicon-o-briefcase',
        ]);

        $this->assertFileDoesNotExist(app_path('Filament/Pages/Content/CustomPostTypes.php'));
        $this->assertFalse(Route::has('filament.admin.pages.content.posts.custom-types'));

        $parent = collect(CustomPostTypesNavigation::items())->keyBy(fn ($item) => $item->getLabel());
        $this->assertTrue($parent['Custom Post Types']->isVisible());
        $this->assertSame(PostTypeResource::getUrl('index'), $parent['Custom Post Types']->getUrl());
        $this->assertSame('Posts', $parent['Custom Post Types']->getParentItem());

        $labels = collect(PostResource::getNavigationItems())
            ->map(fn ($item) => $item->getLabel())
            ->all();
        $this->assertContains('Case Studies', $labels);

        $this->get(PostTypeResource::getUrl('index'))
            ->assertOk();
    }

    public function test_user_resource_owns_all_users_and_add_new_user_navigation(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $this->actingAs($admin);
        $this->assertTrue(UserResource::shouldRegisterNavigation());
        $this->assertSame('All Users', UserResource::getNavigationLabel());
        $this->assertSame('iam/users', UserResource::getSlug());

        $labels = collect(UserResource::getNavigationItems())
            ->map(fn ($item) => $item->getLabel())
            ->all();

        $this->assertContains('All Users', $labels);
        $this->assertContains('Add New User', $labels);
    }

    public function test_iam_user_hub_pages_and_routes_are_removed(): void
    {
        $this->assertFileDoesNotExist(app_path('Filament/Pages/Iam/AllUsers.php'));
        $this->assertFileDoesNotExist(app_path('Filament/Pages/Iam/AddNewUser.php'));

        $this->assertFalse(Route::has('filament.admin.pages.iam.users'));
        $this->assertFalse(Route::has('filament.admin.pages.iam.users.create-hub'));
        $this->assertFalse(Route::has('filament.admin.pages.iam.users-hub'));

        $this->assertTrue(Route::has('filament.admin.resources.iam.users.index'));
        $this->assertTrue(Route::has('filament.admin.resources.iam.users.create'));
    }

    public function test_user_resource_navigation_urls_and_active_patterns(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $this->actingAs($admin);

        $items = collect(UserResource::getNavigationItems())->keyBy(fn ($item) => $item->getLabel());

        $this->assertSame(UserResource::getUrl('index'), $items['All Users']->getUrl());
        $this->assertSame(UserResource::getUrl('create'), $items['Add New User']->getUrl());
        $this->assertNull(UserResource::getNavigationBadge());

        $patterns = UserResource::getNavigationItemActiveRoutePattern();
        $this->assertIsArray($patterns);
        $this->assertContains('filament.admin.resources.iam.users.index', $patterns);
        $this->assertContains('filament.admin.resources.iam.users.view', $patterns);
        $this->assertContains('filament.admin.resources.iam.users.edit', $patterns);
        $this->assertNotContains('filament.admin.resources.iam.users.create', $patterns);

        $this->get(UserResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Add New User', false);

        $createItems = collect(UserResource::getNavigationItems())->keyBy(fn ($item) => $item->getLabel());
        $this->assertTrue($createItems['Add New User']->isActive());
        $this->assertFalse($createItems['All Users']->isActive());
    }

    public function test_content_and_iam_navigation_labels_match_srs_10_1(): void
    {
        $this->assertSame('Posts', PostsNavigation::items()[0]->getLabel());
        $this->assertSame('All Posts', PostResource::getNavigationLabel());
        $this->assertSame('Custom Post Types', CustomPostTypesNavigation::items()[0]->getLabel());
        $this->assertSame('Manage Types', PostTypeResource::getNavigationLabel());

        $this->assertSame('Pages', PagesNavigation::items()[0]->getLabel());
        $this->assertSame('All Pages', PageResource::getNavigationLabel());
        $this->assertSame('Page Hierarchy', PageHierarchy::getNavigationLabel());
        $this->assertSame('Page Templates', PageTemplates::getNavigationLabel());

        $this->assertSame('Taxonomies', TaxonomiesNavigation::items()[0]->getLabel());
        $this->assertSame('Categories', CategoryResource::getNavigationLabel());
        $this->assertSame('Tags', TagResource::getNavigationLabel());
        $this->assertSame('Custom Taxonomies', CustomTaxonomyResource::getNavigationLabel());

        $this->assertSame('All Users', UserResource::getNavigationLabel());
        $this->assertSame('Roles & Permissions', RoleResource::getNavigationLabel());

        $this->actingAs($this->makeUser(UserRole::Administrator));
        $iamLabels = collect(UserResource::getNavigationItems())
            ->map(fn ($item) => $item->getLabel())
            ->all();
        $this->assertContains('Add New User', $iamLabels);
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
        $this->assertSame('Posts', CustomPostTypesNavigation::items()[0]->getParentItem());
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
            __('cms.dashboard.overview.heading'),
            (new OverviewStatsWidget)->getHeading(),
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
