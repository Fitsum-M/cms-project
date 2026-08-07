<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Tags\Pages\EditTag;
use App\Filament\Resources\Tags\Pages\ListTags;
use App\Models\Tag;
use App\Models\User;
use App\Services\TagService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_administrator_can_create_tag(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(CreateTag::class)
            ->fillForm([
                'name' => 'Laravel',
                'slug' => 'laravel',
                'description' => 'PHP framework',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tags', [
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);
    }

    public function test_case_insensitive_name_duplicates_are_rejected(): void
    {
        Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

        $this->expectException(ValidationException::class);

        app(TagService::class)->create([
            'name' => 'laravel',
        ]);
    }

    public function test_find_or_create_returns_existing_case_insensitive_match(): void
    {
        $existing = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

        $found = app(TagService::class)->findOrCreateByName('LARAVEL');

        $this->assertTrue($found->is($existing));
        $this->assertSame(1, Tag::query()->count());
    }

    public function test_find_or_create_creates_new_tag_inline(): void
    {
        $created = app(TagService::class)->findOrCreateByName('Filament');

        $this->assertSame('Filament', $created->name);
        $this->assertSame('filament', $created->slug);
        $this->assertDatabaseCount('tags', 1);
    }

    public function test_slug_conflicts_get_numeric_suffix(): void
    {
        Tag::factory()->create(['name' => 'News', 'slug' => 'news']);

        $created = app(TagService::class)->create([
            'name' => 'News Room',
            'slug' => 'news',
        ]);

        $this->assertSame('news-2', $created->slug);
    }

    public function test_contributor_cannot_create_tags(): void
    {
        $contributor = $this->makeUser(UserRole::Contributor);

        $this->assertTrue($contributor->can(Permission::TaxonomiesView->value));
        $this->assertFalse($contributor->can(Permission::TaxonomiesCreate->value));

        Livewire::actingAs($contributor)
            ->test(ListTags::class)
            ->assertOk();

        Livewire::actingAs($contributor)
            ->test(CreateTag::class)
            ->assertForbidden();
    }

    public function test_editor_can_edit_tag(): void
    {
        $editor = $this->makeUser(UserRole::Editor);
        $tag = Tag::factory()->create(['name' => 'Old', 'slug' => 'old']);

        Livewire::actingAs($editor)
            ->test(EditTag::class, ['record' => $tag->getRouteKey()])
            ->fillForm([
                'name' => 'Updated',
                'slug' => 'updated',
                'description' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
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
