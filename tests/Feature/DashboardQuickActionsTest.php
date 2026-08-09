<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\Dam\UploadMedia;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Widgets\QuickActionsWidget;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardQuickActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_admin_quick_actions_link_to_create_forms(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $this->actingAs($admin);

        Livewire::test(QuickActionsWidget::class)
            ->assertSuccessful()
            ->assertSee('Quick Actions')
            ->assertSee('Add New Post')
            ->assertSee('Add New Page')
            ->assertSee('Upload Media')
            ->assertSee(PostResource::getUrl('create'), false)
            ->assertSee(PageResource::getUrl('create'), false)
            ->assertSee(UploadMedia::getUrl(), false);

        Livewire::test(Dashboard::class)
            ->assertSuccessful()
            ->assertSeeLivewire(QuickActionsWidget::class);
    }

    public function test_contributor_only_sees_add_post_action(): void
    {
        $contributor = $this->makeUser(UserRole::Contributor);
        $this->assertTrue($contributor->can(Permission::PostsCreate->value));
        $this->assertFalse($contributor->can(Permission::PagesCreate->value));
        $this->assertFalse($contributor->can(Permission::MediaUpload->value));

        $this->actingAs($contributor);

        Livewire::test(QuickActionsWidget::class)
            ->assertSuccessful()
            ->assertSee('Add New Post')
            ->assertDontSee('Add New Page')
            ->assertDontSee('Upload Media');
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
