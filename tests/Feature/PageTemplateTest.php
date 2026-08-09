<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\Content\PageTemplates;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use App\Services\PageService;
use App\Support\PageTemplateRegistry;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PageTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_default_template_is_assumed_when_blank(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $page = app(PageService::class)->create([
            'title' => 'About',
        ], $admin);

        $this->assertNull($page->template);
        $this->assertSame('default', $page->resolvedTemplate());
        $this->assertSame('Default', $page->templateLabel());
        $this->assertSame('heroicon-o-document', $page->templateIcon());
        $this->assertFalse($page->isNavigationReady());
    }

    public function test_template_and_navigation_flag_persist(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $page = app(PageService::class)->create([
            'title' => 'Landing',
            'template' => 'landing',
            'show_in_navigation' => true,
        ], $admin);

        $this->assertSame('landing', $page->template);
        $this->assertTrue($page->show_in_navigation);
        $this->assertSame('Landing', $page->templateLabel());
        $this->assertSame('heroicon-o-rocket-launch', $page->templateIcon());

        $updated = app(PageService::class)->update($page, [
            'template' => 'default',
            'show_in_navigation' => false,
        ], $admin);

        $this->assertNull($updated->template);
        $this->assertFalse($updated->show_in_navigation);
        $this->assertSame('default', $updated->resolvedTemplate());
    }

    public function test_unknown_template_is_rejected(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $this->expectException(ValidationException::class);

        app(PageService::class)->create([
            'title' => 'Bad',
            'template' => 'not-a-real-template',
        ], $admin);
    }

    public function test_navigation_tree_respects_flag_and_hierarchy_order(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $service = app(PageService::class);

        $parent = $service->create([
            'title' => 'Company',
            'slug' => 'company',
            'show_in_navigation' => true,
        ], $admin);

        $child = $service->create([
            'title' => 'Team',
            'slug' => 'team',
            'parent_id' => $parent->id,
            'show_in_navigation' => true,
        ], $admin);

        $service->create([
            'title' => 'Hidden',
            'slug' => 'hidden',
            'show_in_navigation' => false,
        ], $admin);

        $orphan = $service->create([
            'title' => 'Orphan Nav',
            'slug' => 'orphan-nav',
            'parent_id' => $service->create([
                'title' => 'Hidden Parent',
                'slug' => 'hidden-parent',
                'show_in_navigation' => false,
            ], $admin)->id,
            'show_in_navigation' => true,
        ], $admin);

        $nav = $service->navigationTree();

        $this->assertCount(2, $nav);
        $this->assertSame(['Company', 'Orphan Nav'], array_column($nav, 'title'));
        $this->assertSame(['Team'], array_column($nav[0]['children'], 'title'));
        $this->assertSame($child->id, $nav[0]['children'][0]['id']);
        $this->assertSame($orphan->id, $nav[1]['id']);
    }

    public function test_tree_exposes_template_icon_and_nav_flag(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $page = app(PageService::class)->create([
            'title' => 'Contact',
            'template' => 'contact',
            'show_in_navigation' => true,
        ], $admin);

        $node = collect(app(PageService::class)->tree($admin))->firstWhere('id', $page->id);

        $this->assertSame('contact', $node['template']);
        $this->assertSame('Contact', $node['template_label']);
        $this->assertSame('heroicon-o-envelope', $node['template_icon']);
        $this->assertTrue($node['show_in_navigation']);
    }

    public function test_filament_create_and_edit_template_fields(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(CreatePage::class)
            ->fillForm([
                'title' => 'Templated',
                'template' => 'sidebar',
                'show_in_navigation' => true,
                'author_id' => $admin->id,
                'published_at' => now(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::query()->where('title', 'Templated')->first();
        $this->assertNotNull($page);
        $this->assertSame('sidebar', $page->template);
        $this->assertTrue($page->show_in_navigation);

        Livewire::actingAs($admin)
            ->test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormSet([
                'template' => 'sidebar',
                'show_in_navigation' => true,
            ])
            ->fillForm([
                'template' => 'full-width',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('full-width', $page->fresh()->template);
    }

    public function test_page_templates_catalog_page_lists_registry(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(PageTemplates::class)
            ->assertSuccessful()
            ->assertSee('Default')
            ->assertSee('Landing')
            ->assertSee(PageTemplateRegistry::defaultKey());
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
