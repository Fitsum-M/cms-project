<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DraftAutosaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_draft_post_autosaves_after_form_changes(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $post = Post::factory()->create([
            'title' => 'Original Title',
            'slug' => 'original-title',
            'body' => 'Original body',
            'status' => ContentStatus::Draft,
            'author_id' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertSeeHtml('wire:poll.'.EditPost::DRAFT_AUTOSAVE_INTERVAL_SECONDS.'s')
            ->fillForm([
                'title' => 'Autosaved Title',
                'body' => 'Autosaved body',
            ])
            ->call('autosaveDraft')
            ->assertNotified();

        $fresh = $post->fresh();
        $this->assertSame('Autosaved Title', $fresh?->title);
        $this->assertSame(ContentStatus::Draft, $fresh?->status);
        $this->assertStringContainsString('Autosaved body', (string) $fresh?->body);
    }

    public function test_draft_page_autosaves_after_form_changes(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $page = Page::factory()->create([
            'title' => 'Page Original',
            'slug' => 'page-original',
            'body' => 'Page body',
            'status' => ContentStatus::Draft,
            'author_id' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm([
                'title' => 'Page Autosaved',
                'body' => 'Updated page body',
            ])
            ->call('autosaveDraft')
            ->assertNotified();

        $fresh = $page->fresh();
        $this->assertSame('Page Autosaved', $fresh?->title);
        $this->assertSame(ContentStatus::Draft, $fresh?->status);
        $this->assertStringContainsString('Updated page body', (string) $fresh?->body);
    }

    public function test_published_post_does_not_autosave(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $post = Post::factory()->create([
            'title' => 'Published Title',
            'slug' => 'published-title',
            'body' => 'Published body',
            'status' => ContentStatus::Published,
            'author_id' => $admin->id,
            'published_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertDontSeeHtml('data-cms-draft-autosave')
            ->fillForm([
                'title' => 'Should Not Save',
            ])
            ->call('autosaveDraft');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Published Title',
            'status' => ContentStatus::Published->value,
        ]);
    }

    public function test_autosave_skips_when_form_is_unchanged(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $post = Post::factory()->create([
            'title' => 'Unchanged',
            'slug' => 'unchanged',
            'status' => ContentStatus::Draft,
            'author_id' => $admin->id,
            'updated_at' => now()->subMinute(),
        ]);

        $updatedAt = $post->fresh()->updated_at?->toDateTimeString();

        Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->call('autosaveDraft')
            ->assertNotNotified();

        $this->assertSame($updatedAt, $post->fresh()->updated_at?->toDateTimeString());
    }

    public function test_autosave_skips_blank_title(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $post = Post::factory()->create([
            'title' => 'Keep Me',
            'slug' => 'keep-me',
            'status' => ContentStatus::Draft,
            'author_id' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'title' => '',
            ])
            ->call('autosaveDraft');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Keep Me',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeUser(UserRole $role, array $attributes = []): User
    {
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'activated_at' => now(),
            ...$attributes,
        ]);

        $user->assignSingleRole($role);

        return $user->fresh();
    }
}
