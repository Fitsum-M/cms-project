<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\DraftSummaryWidget;
use App\Models\User;
use App\Services\ContentLifecycleService;
use App\Services\DashboardDraftSummaryService;
use App\Services\PageService;
use App\Services\PostService;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardDraftSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_author_sees_only_own_drafts_not_pending_section(): void
    {
        $admin = $this->makeUser('Administrator');
        $author = $this->makeUser('Author');

        app(PostService::class)->create(['title' => 'Author Draft'], $author);
        app(PostService::class)->create(['title' => 'Admin Draft'], $admin);

        $pending = app(PostService::class)->create(['title' => 'Pending Post'], $author);
        app(ContentLifecycleService::class)->submitForReview($pending);

        $this->assertFalse($author->can(Permission::DashboardViewAllDrafts->value));

        $summary = app(DashboardDraftSummaryService::class)->forUser($author);

        $this->assertSame(['Author Draft'], $summary['own_drafts']->pluck('title')->all());
        $this->assertTrue($summary['pending_review']->isEmpty());

        $this->actingAs($author);

        Livewire::test(DraftSummaryWidget::class)
            ->assertSuccessful()
            ->assertSee('My drafts')
            ->assertSee('Author Draft')
            ->assertDontSee('Awaiting review')
            ->assertDontSee('Admin Draft')
            ->assertDontSee('Pending Post');
    }

    public function test_editor_sees_own_drafts_and_all_pending_review(): void
    {
        $editor = $this->makeUser('Editor');
        $author = $this->makeUser('Author');

        app(PostService::class)->create(['title' => 'Editor Draft'], $editor);
        app(PageService::class)->create(['title' => 'Editor Page Draft'], $editor);

        $pendingPost = app(PostService::class)->create(['title' => 'Author Pending'], $author);
        app(ContentLifecycleService::class)->submitForReview($pendingPost);

        $pendingPage = app(PageService::class)->create(['title' => 'Author Page Pending'], $author);
        app(ContentLifecycleService::class)->submitForReview($pendingPage);

        $this->assertTrue($editor->can(Permission::DashboardViewAllDrafts->value));

        $summary = app(DashboardDraftSummaryService::class)->forUser($editor);

        $this->assertEqualsCanonicalizing(
            ['Editor Draft', 'Editor Page Draft'],
            $summary['own_drafts']->pluck('title')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['Author Pending', 'Author Page Pending'],
            $summary['pending_review']->pluck('title')->all(),
        );

        $this->actingAs($editor);

        Livewire::test(DraftSummaryWidget::class)
            ->assertSuccessful()
            ->assertSee('Awaiting review')
            ->assertSee('Author Pending')
            ->assertSee('Editor Draft');

        Livewire::test(Dashboard::class)
            ->assertSuccessful()
            ->assertSeeLivewire(DraftSummaryWidget::class);
    }

    public function test_published_content_is_excluded_from_draft_summary(): void
    {
        $admin = $this->makeUser('Administrator');
        $draft = app(PostService::class)->create(['title' => 'Still Draft'], $admin);
        $published = app(PostService::class)->create(['title' => 'Live Post'], $admin);
        app(ContentLifecycleService::class)->publish($published, $admin);

        $summary = app(DashboardDraftSummaryService::class)->forUser($admin);

        $this->assertTrue($summary['own_drafts']->pluck('title')->contains('Still Draft'));
        $this->assertFalse($summary['own_drafts']->pluck('title')->contains('Live Post'));
        $this->assertSame($draft->title, $summary['own_drafts']->first()->title);
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
