<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\OverviewStatsWidget;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\User;
use App\Services\ContentLifecycleService;
use App\Services\DashboardOverviewService;
use App\Services\MediaUploadService;
use App\Services\PageService;
use App\Services\PostService;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        $this->seed(MediaSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_overview_counts_posts_pages_media_and_users(): void
    {
        $admin = $this->makeUser('Administrator');
        $this->makeUser('Editor');

        app(PostService::class)->create(['title' => 'Post A'], $admin);
        app(PostService::class)->create(['title' => 'Post B'], $admin);
        $trashed = app(PostService::class)->create(['title' => 'Trashed'], $admin);
        app(ContentLifecycleService::class)->trash($trashed);

        app(PageService::class)->create(['title' => 'About'], $admin);
        app(PageService::class)->create(['title' => 'Contact'], $admin);

        app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image('one.jpg', 40, 40),
            $admin,
        );
        app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image('two.jpg', 40, 40),
            $admin,
        );
        app(MediaUploadService::class)->upload(
            UploadedFile::fake()->create('doc.pdf', 20, 'application/pdf'),
            $admin,
        );

        $counts = app(DashboardOverviewService::class)->counts();

        $this->assertSame(2, $counts['posts']);
        $this->assertSame(2, $counts['pages']);
        $this->assertSame(3, $counts['media']);
        $this->assertSame(2, $counts['users']);
        $this->assertSame(2, Post::query()->count());
        $this->assertSame(3, Post::withTrashed()->count());
    }

    public function test_overview_widget_renders_counts_on_dashboard(): void
    {
        $admin = $this->makeUser('Administrator');
        $this->actingAs($admin);

        app(PostService::class)->create(['title' => 'Hello'], $admin);
        app(PageService::class)->create(['title' => 'Home'], $admin);
        MediaAsset::factory()->create(['uploaded_by' => $admin->id]);

        Livewire::test(OverviewStatsWidget::class)
            ->assertSuccessful()
            ->assertSee('Overview')
            ->assertSee('Posts')
            ->assertSee('Pages')
            ->assertSee('Media')
            ->assertSee('Users')
            ->assertSee('1'); // posts / pages / media each at least 1; users include admin

        Livewire::test(Dashboard::class)
            ->assertSuccessful()
            ->assertSeeLivewire(OverviewStatsWidget::class);
    }

    public function test_contributor_can_view_overview_widget(): void
    {
        $contributor = $this->makeUser('Contributor');
        $this->assertTrue($contributor->can(Permission::DashboardView->value));
        $this->actingAs($contributor);

        Livewire::test(OverviewStatsWidget::class)
            ->assertSuccessful()
            ->assertSee('Overview');
    }

    public function test_soft_deleted_users_are_excluded_from_user_count(): void
    {
        $admin = $this->makeUser('Administrator');
        $gone = $this->makeUser('Author');
        $gone->delete();

        $this->assertSame(1, app(DashboardOverviewService::class)->userCount());
        $this->assertSame(1, User::query()->count());
        $this->assertSame(2, User::withTrashed()->count());
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
