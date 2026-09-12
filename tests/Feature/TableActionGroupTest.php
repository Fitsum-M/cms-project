<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\CustomTaxonomies\Pages\ListCustomTaxonomies;
use App\Filament\Resources\MediaAssets\Pages\ListMediaAssets;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\PostTypes\Pages\ListPostTypes;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Tags\Pages\ListTags;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Category;
use App\Models\CustomTaxonomy;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostType;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\ActionGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TableActionGroupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_all_resource_tables_wrap_record_actions_in_action_group(): void
    {
        $admin = $this->makeUser('Administrator');

        $post = Post::factory()->create(['author_id' => $admin->id]);
        $page = Page::factory()->create(['author_id' => $admin->id]);
        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'activated_at' => now(),
        ]);
        $role = Role::query()->where('name', 'Editor')->firstOrFail();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $taxonomy = CustomTaxonomy::factory()->create();
        $postType = PostType::factory()->create();
        $media = MediaAsset::factory()->create(['uploaded_by' => $admin->id]);

        $cases = [
            [ListPosts::class, 'edit', $post],
            [ListPages::class, 'edit', $page],
            [ListUsers::class, 'edit', $user],
            [ListRoles::class, 'edit', $role],
            [ListCategories::class, 'edit', $category],
            [ListTags::class, 'edit', $tag],
            [ListCustomTaxonomies::class, 'edit', $taxonomy],
            [ListPostTypes::class, 'edit', $postType],
            [ListMediaAssets::class, 'edit', $media],
        ];

        foreach ($cases as [$pageClass, $action, $record]) {
            $component = Livewire::actingAs($admin)
                ->test($pageClass)
                ->assertSuccessful()
                ->assertTableActionExists($action);

            $groups = collect($component->instance()->getTable()->getRecordActions())
                ->filter(fn ($item): bool => $item instanceof ActionGroup);

            $this->assertCount(
                1,
                $groups,
                "Expected exactly one ActionGroup on {$pageClass}",
            );

            $group = $groups->first();
            $this->assertSame('Actions', $group->getTooltip());
            $this->assertSame('heroicon-m-ellipsis-vertical', $group->getIcon());
        }
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
