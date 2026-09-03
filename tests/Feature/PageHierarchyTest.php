<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use App\Models\User;
use App\Services\ContentUrlGenerator;
use App\Services\PageService;
use App\Support\Settings\PermalinkSettings;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PageHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_create_page_with_core_fields_and_defaults(): void
    {
        $admin = $this->makeUser('Administrator');

        $page = app(PageService::class)->create([
            'title' => 'About Us',
            'body' => '<p>Company story</p>',
            'status' => ContentStatus::Draft->value,
        ], $admin);

        $this->assertSame('About Us', $page->title);
        $this->assertSame('about-us', $page->slug);
        $this->assertSame($admin->id, $page->author_id);
        $this->assertNull($page->parent_id);
        $this->assertSame(0, $page->sort_order);
        $this->assertSame(ContentStatus::Draft, $page->status);
        $this->assertSame('<p>Company story</p>', $page->body);
    }

    public function test_nested_pages_and_public_path_with_parent_slug(): void
    {
        $admin = $this->makeUser('Administrator');
        app(PermalinkSettings::class)->save([
            ...app(PermalinkSettings::class)->all(),
            PermalinkSettings::PAGE_URL_STRUCTURE => '/{parent-slug}/{slug}/',
        ]);

        $parent = app(PageService::class)->create([
            'title' => 'About',
            'slug' => 'about',
        ], $admin);

        $child = app(PageService::class)->create([
            'title' => 'Team',
            'slug' => 'team',
            'parent_id' => $parent->id,
        ], $admin);

        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame(0, $child->sort_order);
        $this->assertSame('/about/team/', app(ContentUrlGenerator::class)->pagePath($child));
        $this->assertSame('About › Team', $child->hierarchicalLabel());
    }

    public function test_circular_parent_assignment_is_rejected(): void
    {
        $admin = $this->makeUser('Administrator');

        $root = app(PageService::class)->create(['title' => 'Root'], $admin);
        $child = app(PageService::class)->create([
            'title' => 'Child',
            'parent_id' => $root->id,
        ], $admin);
        $grandchild = app(PageService::class)->create([
            'title' => 'Grandchild',
            'parent_id' => $child->id,
        ], $admin);

        try {
            app(PageService::class)->update($root, [
                'parent_id' => $grandchild->id,
            ], $admin);
            $this->fail('Expected ValidationException for circular reference.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('cannot be its own parent or descendant', $exception->getMessage());
        }

        try {
            app(PageService::class)->update($root, [
                'parent_id' => $root->id,
            ], $admin);
            $this->fail('Expected ValidationException for self parent.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('cannot be its own parent or descendant', $exception->getMessage());
        }

        $this->assertNull($root->fresh()->parent_id);
    }

    public function test_parent_options_exclude_self_and_descendants(): void
    {
        $admin = $this->makeUser('Administrator');
        $root = app(PageService::class)->create(['title' => 'Root'], $admin);
        $child = app(PageService::class)->create([
            'title' => 'Child',
            'parent_id' => $root->id,
        ], $admin);
        $other = app(PageService::class)->create(['title' => 'Other'], $admin);

        $options = app(PageService::class)->parentOptions($root->id);

        $this->assertArrayNotHasKey($root->id, $options);
        $this->assertArrayNotHasKey($child->id, $options);
        $this->assertArrayHasKey($other->id, $options);
    }

    public function test_sibling_sort_order_auto_increments(): void
    {
        $admin = $this->makeUser('Administrator');
        $parent = app(PageService::class)->create(['title' => 'Parent'], $admin);

        $first = app(PageService::class)->create([
            'title' => 'First',
            'parent_id' => $parent->id,
        ], $admin);
        $second = app(PageService::class)->create([
            'title' => 'Second',
            'parent_id' => $parent->id,
        ], $admin);

        $this->assertSame(0, $first->sort_order);
        $this->assertSame(1, $second->sort_order);
    }

    public function test_author_cannot_reassign_page_authorship(): void
    {
        $author = $this->makeUser('Author');
        $editor = $this->makeUser('Editor');

        $page = app(PageService::class)->create([
            'title' => 'Mine',
            'author_id' => $editor->id,
        ], $author);

        $this->assertSame($author->id, $page->author_id);

        $updated = app(PageService::class)->update($page, [
            'author_id' => $editor->id,
        ], $author);

        $this->assertSame($author->id, $updated->author_id);
    }

    public function test_contributor_cannot_create_pages(): void
    {
        $contributor = $this->makeUser('Contributor');

        $this->assertFalse($contributor->can('create', Page::class));
    }

    public function test_filament_create_and_list_pages(): void
    {
        $admin = $this->makeUser('Administrator');
        $parent = app(PageService::class)->create(['title' => 'Company'], $admin);

        Livewire::actingAs($admin)
            ->test(CreatePage::class)
            ->fillForm([
                'title' => 'Contact',
                'body' => '<p>Hello</p>',
                'parent_id' => $parent->id,
                'status' => ContentStatus::Draft->value,
                'author_id' => $admin->id,
                'published_at' => now(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pages', [
            'title' => 'Contact',
            'parent_id' => $parent->id,
            'slug' => 'contact',
        ]);

        Livewire::actingAs($admin)
            ->test(ListPages::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(Page::query()->get());
    }

    public function test_author_sees_only_own_pages(): void
    {
        $author = $this->makeUser('Author');
        $other = $this->makeUser('Editor');

        $own = Page::factory()->create(['author_id' => $author->id, 'title' => 'Own page']);
        $foreign = Page::factory()->create(['author_id' => $other->id, 'title' => 'Other page']);

        Livewire::actingAs($author)
            ->test(ListPages::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$foreign]);
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
