<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\RecentContentWidget;
use App\Models\User;
use App\Services\ContentLifecycleService;
use App\Services\DashboardRecentContentService;
use App\Services\PageService;
use App\Services\PostService;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardRecentContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_recent_content_returns_last_ten_edited_posts_and_pages(): void
    {
        $admin = $this->makeUser('Administrator');
        $this->actingAs($admin);

        Carbon::setTestNow('2026-08-01 10:00:00');

        for ($i = 1; $i <= 8; $i++) {
            Carbon::setTestNow(now()->addMinutes($i));
            app(PostService::class)->create(['title' => "Post {$i}"], $admin);
        }

        for ($i = 1; $i <= 5; $i++) {
            Carbon::setTestNow(now()->addMinutes(8 + $i));
            app(PageService::class)->create(['title' => "Page {$i}"], $admin);
        }

        $items = app(DashboardRecentContentService::class)->forUser($admin);

        $this->assertCount(10, $items);
        $this->assertSame('Page 5', $items->first()->title);
        $this->assertSame('page', $items->first()->type);
        $this->assertSame('Post 4', $items->last()->title);
        $this->assertTrue($items->every(fn ($item) => filled($item->editUrl)));

        Carbon::setTestNow();
    }

    public function test_admin_sees_others_content_author_sees_only_own(): void
    {
        $admin = $this->makeUser('Administrator');
        $author = $this->makeUser('Author');

        Carbon::setTestNow('2026-08-02 12:00:00');
        app(PostService::class)->create(['title' => 'Admin Post'], $admin);

        Carbon::setTestNow('2026-08-02 12:05:00');
        app(PostService::class)->create(['title' => 'Author Post'], $author);

        Carbon::setTestNow('2026-08-02 12:10:00');
        app(PageService::class)->create(['title' => 'Admin Page'], $admin);

        $this->assertTrue($admin->can(Permission::DashboardViewRecentAll->value));
        $this->assertFalse($author->can(Permission::DashboardViewRecentAll->value));

        $adminItems = app(DashboardRecentContentService::class)->forUser($admin);
        $this->assertSame(
            ['Admin Page', 'Author Post', 'Admin Post'],
            $adminItems->pluck('title')->all(),
        );

        $authorItems = app(DashboardRecentContentService::class)->forUser($author);
        $this->assertSame(['Author Post'], $authorItems->pluck('title')->all());

        Carbon::setTestNow();
    }

    public function test_trashed_content_is_excluded(): void
    {
        $admin = $this->makeUser('Administrator');

        $kept = app(PostService::class)->create(['title' => 'Kept'], $admin);
        $trashed = app(PostService::class)->create(['title' => 'Gone'], $admin);
        app(ContentLifecycleService::class)->trash($trashed);

        $titles = app(DashboardRecentContentService::class)->forUser($admin)->pluck('title');

        $this->assertTrue($titles->contains('Kept'));
        $this->assertFalse($titles->contains('Gone'));
        $this->assertSame($kept->title, $titles->first());
    }

    public function test_contributor_does_not_see_pages_in_recent_content(): void
    {
        $admin = $this->makeUser('Administrator');
        $contributor = $this->makeUser('Contributor');

        app(PostService::class)->create(['title' => 'Contributor Post'], $contributor);
        app(PageService::class)->create(['title' => 'Someone Page'], $admin);

        $items = app(DashboardRecentContentService::class)->forUser($contributor);

        $this->assertSame(['Contributor Post'], $items->pluck('title')->all());
        $this->assertTrue($items->every(fn ($item) => $item->type === 'post'));
    }

    public function test_recent_content_widget_renders_on_dashboard(): void
    {
        $admin = $this->makeUser('Administrator');
        $this->actingAs($admin);

        app(PostService::class)->create(['title' => 'Hello Recent'], $admin);
        app(PageService::class)->create(['title' => 'About Recent'], $admin);

        Livewire::test(RecentContentWidget::class)
            ->assertSuccessful()
            ->assertSee('Recent Content')
            ->assertSee('Hello Recent')
            ->assertSee('About Recent')
            ->assertSee('Post')
            ->assertSee('Page');

        Livewire::test(Dashboard::class)
            ->assertSuccessful()
            ->assertSeeLivewire(RecentContentWidget::class);
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
