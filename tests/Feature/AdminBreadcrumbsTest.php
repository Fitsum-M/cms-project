<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Pages\System\SettingsPage;
use App\Filament\Resources\Folders\FolderResource;
use App\Filament\Resources\Folders\Pages\EditFolder;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\User;
use App\Services\FolderService;
use App\Services\PageService;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminBreadcrumbsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_admin_panel_keeps_breadcrumbs_enabled(): void
    {
        $this->assertTrue(Filament::getPanel('admin')->hasBreadcrumbs());
    }

    public function test_resource_list_renders_dashboard_breadcrumb_trail(): void
    {
        $admin = $this->makeUser('Administrator');

        Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->assertSeeHtml('fi-breadcrumbs')
            ->assertSee('Dashboard')
            ->assertSee('Posts');
    }

    public function test_custom_page_shows_dashboard_and_title(): void
    {
        $admin = $this->makeUser('Administrator');

        Livewire::actingAs($admin)
            ->test(SettingsPage::class)
            ->assertSeeHtml('fi-breadcrumbs')
            ->assertSee('Dashboard')
            ->assertSee('Settings');
    }

    public function test_nested_page_and_folder_breadcrumbs_include_ancestors(): void
    {
        $admin = $this->makeUser('Administrator');

        $parent = app(PageService::class)->create(['title' => 'About Us'], $admin);
        $child = app(PageService::class)->create([
            'title' => 'Our Team',
            'parent_id' => $parent->id,
        ], $admin);

        $pageCrumbs = Livewire::actingAs($admin)
            ->test(EditPage::class, ['record' => $child->getRouteKey()])
            ->assertSeeHtml('fi-breadcrumbs')
            ->assertSee('About Us')
            ->instance()
            ->getBreadcrumbs();

        $this->assertContains('About Us', $pageCrumbs);
        $this->assertContains('Our Team', $pageCrumbs);

        $root = app(FolderService::class)->create(['name' => 'Campaigns']);
        $nested = app(FolderService::class)->create([
            'name' => 'Hero Images',
            'parent_id' => $root->id,
        ]);

        $folderCrumbs = Livewire::actingAs($admin)
            ->test(EditFolder::class, ['record' => $nested->getRouteKey()])
            ->assertSee('Campaigns')
            ->instance()
            ->getBreadcrumbs();

        $this->assertArrayHasKey(FolderResource::getUrl('edit', ['record' => $root]), $folderCrumbs);
        $this->assertContains('Hero Images', $folderCrumbs);
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
