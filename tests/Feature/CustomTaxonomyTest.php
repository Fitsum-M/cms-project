<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\TaxonomyStructure;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\CustomTaxonomies\Pages\CreateCustomTaxonomy;
use App\Filament\Resources\CustomTaxonomies\Pages\EditCustomTaxonomy;
use App\Models\CustomTaxonomy;
use App\Models\CustomTaxonomyTerm;
use App\Models\User;
use App\Services\CustomTaxonomyService;
use App\Services\CustomTaxonomyTermService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_administrator_can_create_custom_taxonomy(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(CreateCustomTaxonomy::class)
            ->fillForm([
                'name' => 'Topics',
                'slug' => 'topics',
                'structure_type' => TaxonomyStructure::Hierarchical->value,
                'post_type_keys' => ['post'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('custom_taxonomies', [
            'name' => 'Topics',
            'slug' => 'topics',
            'structure_type' => TaxonomyStructure::Hierarchical->value,
        ]);

        $taxonomy = CustomTaxonomy::query()->where('slug', 'topics')->first();
        $this->assertSame(['post'], $taxonomy->postTypeKeys());
    }

    public function test_reserved_slug_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(CustomTaxonomyService::class)->create([
            'name' => 'Category Clone',
            'slug' => 'category',
            'structure_type' => TaxonomyStructure::Flat->value,
            'post_type_keys' => ['post'],
        ]);
    }

    public function test_structure_type_is_immutable_on_update(): void
    {
        $taxonomy = app(CustomTaxonomyService::class)->create([
            'name' => 'Genres',
            'slug' => 'genres',
            'structure_type' => TaxonomyStructure::Flat->value,
            'post_type_keys' => ['post'],
        ]);

        app(CustomTaxonomyService::class)->update($taxonomy, [
            'name' => 'Genres Updated',
            'post_type_keys' => ['post'],
        ]);

        $this->assertSame(TaxonomyStructure::Flat, $taxonomy->fresh()->structure_type);
    }

    public function test_requires_at_least_one_post_type(): void
    {
        $this->expectException(ValidationException::class);

        app(CustomTaxonomyService::class)->create([
            'name' => 'Orphan',
            'slug' => 'orphan',
            'structure_type' => TaxonomyStructure::Flat->value,
            'post_type_keys' => [],
        ]);
    }

    public function test_hierarchical_terms_support_parent_and_reject_cycles(): void
    {
        $taxonomy = app(CustomTaxonomyService::class)->create([
            'name' => 'Topics',
            'slug' => 'topics',
            'structure_type' => TaxonomyStructure::Hierarchical->value,
            'post_type_keys' => ['post'],
        ]);

        $service = app(CustomTaxonomyTermService::class);
        $root = $service->create($taxonomy, ['name' => 'Root', 'slug' => 'root']);
        $child = $service->create($taxonomy, [
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $root->id,
        ]);

        $this->assertSame($root->id, $child->parent_id);

        $this->expectException(ValidationException::class);
        $service->update($root, ['parent_id' => $child->id]);
    }

    public function test_flat_taxonomy_ignores_parent_id(): void
    {
        $taxonomy = app(CustomTaxonomyService::class)->create([
            'name' => 'Labels',
            'slug' => 'labels',
            'structure_type' => TaxonomyStructure::Flat->value,
            'post_type_keys' => ['post'],
        ]);

        $term = app(CustomTaxonomyTermService::class)->create($taxonomy, [
            'name' => 'Featured',
            'slug' => 'featured',
            'parent_id' => 999,
        ]);

        $this->assertNull($term->parent_id);
    }

    public function test_deleting_parent_term_promotes_children(): void
    {
        $taxonomy = app(CustomTaxonomyService::class)->create([
            'name' => 'Topics',
            'slug' => 'topics-2',
            'structure_type' => TaxonomyStructure::Hierarchical->value,
            'post_type_keys' => ['post'],
        ]);

        $service = app(CustomTaxonomyTermService::class);
        $parent = $service->create($taxonomy, ['name' => 'Parent', 'slug' => 'parent']);
        $child = $service->create($taxonomy, [
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $parent->id,
        ]);

        $service->delete($parent);

        $this->assertDatabaseMissing('custom_taxonomy_terms', ['id' => $parent->id]);
        $this->assertDatabaseHas('custom_taxonomy_terms', [
            'id' => $child->id,
            'parent_id' => null,
        ]);
    }

    public function test_editor_can_edit_custom_taxonomy(): void
    {
        $taxonomy = app(CustomTaxonomyService::class)->create([
            'name' => 'Series',
            'slug' => 'series',
            'structure_type' => TaxonomyStructure::Flat->value,
            'post_type_keys' => ['post'],
        ]);

        $editor = $this->makeUser(UserRole::Editor);

        Livewire::actingAs($editor)
            ->test(EditCustomTaxonomy::class, ['record' => $taxonomy->getRouteKey()])
            ->fillForm([
                'name' => 'Series Updated',
                'slug' => 'series',
                'structure_type' => TaxonomyStructure::Flat->value,
                'post_type_keys' => ['post'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('custom_taxonomies', [
            'id' => $taxonomy->id,
            'name' => 'Series Updated',
        ]);
    }

    public function test_author_cannot_create_custom_taxonomy(): void
    {
        $author = $this->makeUser(UserRole::Author);

        $this->assertFalse($author->can(Permission::TaxonomiesCreate->value));

        Livewire::actingAs($author)
            ->test(CreateCustomTaxonomy::class)
            ->assertForbidden();
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
