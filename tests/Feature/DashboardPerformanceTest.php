<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\DraftSummaryWidget;
use App\Filament\Widgets\OverviewStatsWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentContentWidget;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\ContentLifecycleService;
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

class DashboardPerformanceTest extends TestCase
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

    public function test_dashboard_aggregate_queries_stay_under_two_seconds(): void
    {
        $admin = $this->makeUser('Administrator');
        $author = $this->makeUser('Author');

        for ($i = 1; $i <= 40; $i++) {
            $post = app(PostService::class)->create(['title' => "Perf Post {$i}"], $i % 2 === 0 ? $admin : $author);
            if ($i % 5 === 0) {
                app(ContentLifecycleService::class)->submitForReview($post);
            }
        }

        for ($i = 1; $i <= 20; $i++) {
            app(PageService::class)->create(['title' => "Perf Page {$i}"], $admin);
        }

        MediaAsset::factory()->count(15)->create(['uploaded_by' => $admin->id]);

        $result = app(DashboardPerformanceService::class)->measureWarmQueries($admin);

        $this->assertTrue(
            $result['within_budget'],
            sprintf('Dashboard queries took %.1fms (budget %dms)', $result['elapsed_ms'], DashboardPerformanceService::MAX_LOAD_MS),
        );
        $this->assertLessThan(DashboardPerformanceService::MAX_LOAD_MS, $result['elapsed_ms']);
    }

    public function test_dashboard_page_renders_all_widgets_under_two_seconds(): void
    {
        $admin = $this->makeUser('Administrator');
        $this->actingAs($admin);

        for ($i = 1; $i <= 25; $i++) {
            app(PostService::class)->create(['title' => "Dash Post {$i}"], $admin);
        }

        for ($i = 1; $i <= 10; $i++) {
            app(PageService::class)->create(['title' => "Dash Page {$i}"], $admin);
        }

        $started = hrtime(true);

        Livewire::test(Dashboard::class)
            ->assertSuccessful()
            ->assertSeeLivewire(OverviewStatsWidget::class)
            ->assertSeeLivewire(RecentContentWidget::class)
            ->assertSeeLivewire(DraftSummaryWidget::class)
            ->assertSeeLivewire(QuickActionsWidget::class);

        $elapsedMs = (hrtime(true) - $started) / 1_000_000;

        $this->assertLessThan(
            DashboardPerformanceService::MAX_LOAD_MS,
            $elapsedMs,
            sprintf('Dashboard Livewire render took %.1fms (budget %dms)', $elapsedMs, DashboardPerformanceService::MAX_LOAD_MS),
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
