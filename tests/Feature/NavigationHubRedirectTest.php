<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\Content\PagesGroup;
use App\Filament\Pages\Content\PostsGroup;
use App\Filament\Pages\Content\TaxonomiesGroup;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Posts\PostResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
