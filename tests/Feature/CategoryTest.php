<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use App\Services\CategoryService;
use App\Support\SlugGenerator;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_administrator_can_create_hierarchical_category(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $parent = Category::factory()->create(['name' => 'Parent', 'slug' => 'parent']);

        Livewire::actingAs($admin)
            ->test(CreateCategory::class)
            ->fillForm([
                'name' => 'Child Category',
                'slug' => 'child-category',
                'parent_id' => $parent->id,
                'description' => 'A nested category',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Child Category',
            'slug' => 'child-category',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_slug_conflicts_get_numeric_suffix(): void
    {
        Category::factory()->create(['name' => 'News', 'slug' => 'news']);

        $created = app(CategoryService::class)->create([
            'name' => 'News',
            'slug' => 'news',
        ]);

        $this->assertSame('news-2', $created->slug);
    }

    public function test_circular_parent_assignment_is_rejected(): void
    {
        $root = Category::factory()->create(['name' => 'Root', 'slug' => 'root']);
        $child = Category::factory()->childOf($root)->create(['name' => 'Child', 'slug' => 'child']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(CategoryService::class)->update($root, [
            'parent_id' => $child->id,
        ]);
    }

    public function test_deleting_parent_makes_children_root(): void
    {
        $parent = Category::factory()->create(['name' => 'Parent', 'slug' => 'parent']);
        $child = Category::factory()->childOf($parent)->create(['name' => 'Child', 'slug' => 'child']);

        app(CategoryService::class)->delete($parent);

        $this->assertDatabaseMissing('categories', ['id' => $parent->id]);
        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => null,
        ]);
    }

    public function test_slug_generator_sanitizes_per_srs(): void
    {
        $this->assertSame('hello-world', SlugGenerator::sanitize('Hello World!'));
        $this->assertSame('item', SlugGenerator::sanitize('***'));
    }

    public function test_contributor_cannot_manage_categories(): void
    {
        $contributor = $this->makeUser(UserRole::Contributor);

        $this->assertTrue($contributor->can(Permission::TaxonomiesView->value));
        $this->assertFalse($contributor->can(Permission::TaxonomiesCreate->value));

        Livewire::actingAs($contributor)
            ->test(ListCategories::class)
            ->assertOk();

        Livewire::actingAs($contributor)
            ->test(CreateCategory::class)
            ->assertForbidden();
    }

    public function test_author_can_list_but_not_create_categories(): void
    {
        $author = $this->makeUser(UserRole::Author);

        $this->assertTrue($author->can(Permission::TaxonomiesView->value));
        $this->assertFalse($author->can(Permission::TaxonomiesCreate->value));

        Livewire::actingAs($author)
            ->test(CreateCategory::class)
            ->assertForbidden();
    }

    public function test_editor_can_edit_category(): void
    {
        $editor = $this->makeUser(UserRole::Editor);
        $category = Category::factory()->create(['name' => 'Old', 'slug' => 'old']);

        Livewire::actingAs($editor)
            ->test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name' => 'Updated',
                'slug' => 'updated',
                'parent_id' => null,
                'description' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated',
            'slug' => 'updated',
        ]);
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
