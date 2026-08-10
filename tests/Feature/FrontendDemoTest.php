<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\PostVisibility;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\GeneralSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(GeneralSettingsSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
    }

    public function test_home_page_renders_published_posts(): void
    {
        $author = User::factory()->create();

        Post::factory()->published()->create([
            'title' => 'Public Demo Post',
            'slug' => 'public-demo-post',
            'author_id' => $author->id,
        ]);

        Post::factory()->create([
            'title' => 'Hidden Draft Post',
            'slug' => 'hidden-draft-post',
            'author_id' => $author->id,
        ]);

        $response = $this->get(route('frontend.home'));

        $response->assertOk();
        $response->assertSee('Public Demo Post');
        $response->assertDontSee('Hidden Draft Post');
    }

    public function test_post_detail_page_shows_public_content(): void
    {
        $author = User::factory()->create();

        Post::factory()->published()->create([
            'title' => 'Readable Post',
            'slug' => 'readable-post',
            'body' => '<p>Frontend body content.</p>',
            'author_id' => $author->id,
        ]);

        $response = $this->get(route('frontend.posts.show', 'readable-post'));

        $response->assertOk();
        $response->assertSee('Readable Post');
        $response->assertSee('Frontend body content.', false);
    }

    public function test_private_and_draft_posts_return_not_found(): void
    {
        $author = User::factory()->create();

        Post::factory()->create([
            'title' => 'Draft Only',
            'slug' => 'draft-only',
            'author_id' => $author->id,
        ]);

        Post::factory()->published()->create([
            'title' => 'Private Post',
            'slug' => 'private-post',
            'visibility' => PostVisibility::Private,
            'author_id' => $author->id,
        ]);

        $this->get(route('frontend.posts.show', 'draft-only'))->assertNotFound();
        $this->get(route('frontend.posts.show', 'private-post'))->assertNotFound();
    }

    public function test_page_detail_renders_published_page(): void
    {
        $author = User::factory()->create();

        Page::factory()->published()->create([
            'title' => 'About Page',
            'slug' => 'about-page',
            'body' => '<p>About page body.</p>',
            'author_id' => $author->id,
        ]);

        $response = $this->get(route('frontend.pages.show', 'about-page'));

        $response->assertOk();
        $response->assertSee('About Page');
        $response->assertSee('About page body.', false);
    }

    public function test_demo_data_seeder_populates_content(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseHas('posts', ['slug' => 'welcome-to-our-cms']);
        $this->assertDatabaseHas('pages', ['slug' => 'about-us']);
        $this->assertTrue(Post::query()->where('status', ContentStatus::Published)->count() >= 10);
    }

    public function test_demo_data_seeder_is_idempotent(): void
    {
        $this->seed(DemoDataSeeder::class);
        $countAfterFirstRun = Post::query()->count();

        $this->seed(DemoDataSeeder::class);

        $this->assertSame($countAfterFirstRun, Post::query()->count());
    }
}
