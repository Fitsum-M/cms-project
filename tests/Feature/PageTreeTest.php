<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\Content\PageHierarchy;
use App\Models\Page;
use App\Models\User;
use App\Services\PageService;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PageTreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_tree_builds_nested_nodes_ordered_by_sort_order(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $service = app(PageService::class);

        $parent = $service->create(['title' => 'Parent', 'slug' => 'parent'], $admin);
        $second = $service->create([
            'title' => 'Second',
            'slug' => 'second',
            'parent_id' => $parent->id,
        ], $admin);
        $first = $service->create([
            'title' => 'First',
            'slug' => 'first',
            'parent_id' => $parent->id,
        ], $admin);

        $service->reorderSiblings($parent->id, [$first->id, $second->id]);

        $tree = $service->tree($admin);

        $this->assertCount(1, $tree);
        $this->assertSame('Parent', $tree[0]['title']);
        $this->assertSame(ContentStatus::Draft->value, $tree[0]['status']);
        $this->assertSame(['First', 'Second'], array_column($tree[0]['children'], 'title'));
        $this->assertSame(0, $first->fresh()->sort_order);
        $this->assertSame(1, $second->fresh()->sort_order);
    }

    public function test_reorder_relative_before_and_after(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $service = app(PageService::class);

        $a = $service->create(['title' => 'A', 'slug' => 'a'], $admin);
        $b = $service->create(['title' => 'B', 'slug' => 'b'], $admin);
        $c = $service->create(['title' => 'C', 'slug' => 'c'], $admin);

        $service->reorderRelative($c->fresh(), $a->fresh(), 'before');

        $roots = Page::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->pluck('title')
            ->all();

        $this->assertSame(['C', 'A', 'B'], $roots);

        $service->reorderRelative($c->fresh(), $b->fresh(), 'after');

        $roots = Page::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->pluck('title')
            ->all();

        $this->assertSame(['A', 'B', 'C'], $roots);
    }

    public function test_move_reparents_and_blocks_cycles(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $service = app(PageService::class);

        $parent = $service->create(['title' => 'Parent'], $admin);
        $child = $service->create(['title' => 'Child', 'parent_id' => $parent->id], $admin);

        $service->move($child->fresh(), null);
        $this->assertNull($child->fresh()->parent_id);

        $service->move($child->fresh(), $parent->id);
        $this->assertSame($parent->id, $child->fresh()->parent_id);

        $this->expectException(ValidationException::class);
        $service->move($parent->fresh(), $child->id);
    }

    public function test_tree_includes_status_color_and_edit_url(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $page = app(PageService::class)->create([
            'title' => 'Published Page',
            'status' => ContentStatus::Published->value,
        ], $admin);

        $tree = app(PageService::class)->tree($admin);
        $node = collect($tree)->firstWhere('id', $page->id);

        $this->assertNotNull($node);
        $this->assertSame('success', $node['status_color']);
        $this->assertSame('Published', $node['status_label']);
        $this->assertStringContainsString('/content/pages/', $node['edit_url']);
        $this->assertSame('heroicon-o-document', $node['template_icon']);
    }

    public function test_author_tree_only_includes_own_pages(): void
    {
        $author = $this->makeUser(UserRole::Author);
        $editor = $this->makeUser(UserRole::Editor);

        $own = app(PageService::class)->create(['title' => 'Mine'], $author);
        app(PageService::class)->create(['title' => 'Theirs'], $editor);

        $tree = app(PageService::class)->tree($author);

        $this->assertCount(1, $tree);
        $this->assertSame($own->id, $tree[0]['id']);
    }

    public function test_filament_hierarchy_page_loads_and_reorders(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $service = app(PageService::class);

        $a = $service->create(['title' => 'Alpha', 'slug' => 'alpha'], $admin);
        $b = $service->create(['title' => 'Beta', 'slug' => 'beta'], $admin);

        Livewire::actingAs($admin)
            ->test(PageHierarchy::class)
            ->assertSuccessful()
            ->assertSee('Alpha')
            ->assertSee('Beta')
            ->call('reorderRelative', $b->id, $a->id, 'before')
            ->assertSuccessful();

        $roots = Page::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->pluck('title')
            ->all();

        $this->assertSame(['Beta', 'Alpha'], $roots);
    }

    public function test_filament_move_page_nests_under_parent(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $service = app(PageService::class);

        $parent = $service->create(['title' => 'Parent Nest'], $admin);
        $child = $service->create(['title' => 'Child Nest'], $admin);

        Livewire::actingAs($admin)
            ->test(PageHierarchy::class)
            ->call('movePage', $child->id, $parent->id)
            ->assertSuccessful();

        $this->assertSame($parent->id, $child->fresh()->parent_id);
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
