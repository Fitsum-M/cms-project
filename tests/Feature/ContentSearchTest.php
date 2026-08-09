<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Services\ContentListingPerformanceService;
use App\Services\PageService;
use App\Services\PostService;
use App\Support\Content\ContentSearch;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ContentSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * InnoDB FULLTEXT is updated at COMMIT; disable per-test transactions (GAP.NFR.01).
     *
     * @var list<string>
     */
    protected array $connectionsToTransact = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_posts_fulltext_index_exists_on_mysql(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('FULLTEXT indexes require MySQL.');
        }

        $indexes = collect(DB::select('SHOW INDEX FROM posts WHERE Key_name = ?', ['posts_content_fulltext']));

        $this->assertTrue($indexes->isNotEmpty());
    }

    public function test_fulltext_search_finds_body_content(): void
    {
        if (! Schema::hasTable('posts')) {
            $this->markTestSkipped('Posts table missing.');
        }

        $admin = $this->makeUser(UserRole::Administrator);

        $match = Post::factory()->create([
            'author_id' => $admin->id,
            'title' => 'Alpha',
            'body' => '<p>UniqueFulltextNeedleXYZ content</p>',
        ]);
        Post::factory()->create([
            'author_id' => $admin->id,
            'title' => 'Beta',
            'body' => '<p>Nothing related here</p>',
        ]);

        $ids = ContentSearch::applyPostsSearch(Post::query(), 'UniqueFulltextNeedleXYZ')
            ->pluck('id')
            ->all();

        $this->assertSame([(int) $match->id], $ids);
        $this->assertTrue(ContentSearch::canUseFullText('UniqueFulltextNeedleXYZ'));
    }

    public function test_short_search_terms_fall_back_to_like(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $match = Post::factory()->create([
            'author_id' => $admin->id,
            'title' => 'XY',
        ]);
        Post::factory()->create([
            'author_id' => $admin->id,
            'title' => 'Unrelated',
        ]);

        $this->assertFalse(ContentSearch::canUseFullText('XY'));

        $ids = ContentSearch::applyPostsSearch(Post::query(), 'XY')->pluck('id')->all();

        $this->assertContains((int) $match->id, $ids);
    }

    public function test_pages_table_search_finds_body_content(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $page = app(PageService::class)->create([
            'title' => 'About',
            'body' => '<p>PageBodySearchToken</p>',
        ], $admin);

        app(PageService::class)->create([
            'title' => 'Other',
            'body' => '<p>Nothing</p>',
        ], $admin);

        Livewire::actingAs($admin)
            ->test(ListPages::class)
            ->searchTable('PageBodySearchToken')
            ->assertCanSeeTableRecords([$page]);
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
