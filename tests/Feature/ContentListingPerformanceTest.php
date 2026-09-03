<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\User;
use App\Services\ContentListingPerformanceService;
use App\Services\DashboardPerformanceService;
use App\Services\PageService;
use App\Services\PostService;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Acceptance evidence for SRS §6 / §19.1 / §20.1 load budgets (GAP.NFR.03).
 */
class ContentListingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        $this->seed(MediaSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_posts_listing_queries_stay_under_two_seconds_with_sample_dataset(): void
    {
        $admin = $this->makeUser('Administrator');

        for ($i = 1; $i <= 250; $i++) {
            app(PostService::class)->create([
                'title' => "Listing Perf Post {$i}",
                'body' => "<p>Performance body content {$i}</p>",
            ], $admin);
        }

        $result = app(ContentListingPerformanceService::class)->measurePostsListing();

        $this->assertTrue(
            $result['within_budget'],
            sprintf('Posts listing queries took %.1fms (budget %dms)', $result['elapsed_ms'], ContentListingPerformanceService::MAX_LOAD_MS),
        );
    }

    public function test_posts_list_livewire_render_stays_under_two_seconds(): void
    {
        $admin = $this->makeUser('Administrator');
        $this->actingAs($admin);

        for ($i = 1; $i <= 25; $i++) {
            app(PostService::class)->create(['title' => "Livewire Post {$i}"], $admin);
        }

        $started = hrtime(true);

        Livewire::test(ListPosts::class)
            ->assertSuccessful();

        $elapsedMs = (hrtime(true) - $started) / 1_000_000;

        $this->assertLessThan(
            DashboardPerformanceService::MAX_LOAD_MS,
            $elapsedMs,
            sprintf('Posts list Livewire render took %.1fms (budget %dms)', $elapsedMs, DashboardPerformanceService::MAX_LOAD_MS),
        );
    }

    public function test_pages_listing_queries_stay_under_two_seconds_with_sample_dataset(): void
    {
        $admin = $this->makeUser('Administrator');

        for ($i = 1; $i <= 100; $i++) {
            app(PageService::class)->create([
                'title' => "Listing Perf Page {$i}",
                'body' => "<p>Page performance {$i}</p>",
            ], $admin);
        }

        $result = app(ContentListingPerformanceService::class)->measurePagesListing();

        $this->assertTrue(
            $result['within_budget'],
            sprintf('Pages listing queries took %.1fms (budget %dms)', $result['elapsed_ms'], ContentListingPerformanceService::MAX_LOAD_MS),
        );
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
