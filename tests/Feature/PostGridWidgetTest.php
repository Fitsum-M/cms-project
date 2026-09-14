<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\PostGridWidget;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PostGridWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_dashboard_post_grid_shows_published_posts_with_badge_and_views(): void
    {
        $admin = $this->makeUser('Administrator');
        $this->actingAs($admin);

        Post::factory()->published()->create([
            'title' => 'Grid Feature Story',
            'author_id' => $admin->id,
            'view_count' => 12,
            'excerpt' => 'A short excerpt for the grid card.',
        ]);

        Post::factory()->create([
            'title' => 'Hidden Draft Story',
            'author_id' => $admin->id,
        ]);

        Livewire::test(PostGridWidget::class)
            ->assertSuccessful()
            ->assertSee('Published posts')
            ->assertSee('Grid Feature Story')
            ->assertSee('Published')
            ->assertSee('12 views')
            ->assertSee('A short excerpt for the grid card.')
            ->assertDontSee('Hidden Draft Story');

        Livewire::test(Dashboard::class)
            ->assertSuccessful()
            ->assertSeeLivewire(PostGridWidget::class);
    }

    public function test_public_post_view_increments_view_count(): void
    {
        $author = User::factory()->create();

        $post = Post::factory()->published()->create([
            'title' => 'Counted Post',
            'slug' => 'counted-post',
            'author_id' => $author->id,
            'view_count' => 0,
        ]);

        $this->get(route('frontend.posts.show', 'counted-post'))->assertOk();

        $this->assertSame(1, $post->fresh()->view_count);
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
