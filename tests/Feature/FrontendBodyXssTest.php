<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Support\ContentHtml;
use Database\Seeders\GeneralSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendBodyXssTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(GeneralSettingsSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
    }

    public function test_content_html_strips_scripts_and_event_handlers(): void
    {
        $dirty = '<p>Hello</p><script>alert("xss")</script><img src=x onerror="alert(1)"><a href="javascript:alert(1)">bad</a><p><strong>Safe</strong></p>';

        $cleaned = ContentHtml::clean($dirty);

        $this->assertStringContainsString('<p>Hello</p>', $cleaned);
        $this->assertStringContainsString('<strong>Safe</strong>', $cleaned);
        $this->assertStringNotContainsString('<script', $cleaned);
        $this->assertStringNotContainsString('onerror', $cleaned);
        $this->assertStringNotContainsString('javascript:', $cleaned);
    }

    public function test_frontend_post_and_page_bodies_are_purified(): void
    {
        $author = User::factory()->create();

        $payload = '<p>Visible copy</p><script>alert("xss")</script><img src=x onerror="alert(1)">';

        $post = Post::factory()->published()->create([
            'title' => 'XSS Post',
            'slug' => 'xss-post',
            'author_id' => $author->id,
            'body' => $payload,
        ]);

        $page = Page::query()->create([
            'title' => 'XSS Page',
            'slug' => 'xss-page',
            'author_id' => $author->id,
            'body' => $payload,
            'status' => ContentStatus::Published,
            'published_at' => now()->subMinute(),
            'show_in_navigation' => false,
        ]);

        $postResponse = $this->get(route('frontend.posts.show', $post->slug));
        $postResponse->assertOk();
        $postResponse->assertSee('Visible copy', false);
        $postResponse->assertDontSee('alert("xss")', false);
        $postResponse->assertDontSee('onerror=', false);
        $this->assertStringNotContainsString('<script>alert', $postResponse->getContent());

        $pageResponse = $this->get(route('frontend.pages.show', $page->slug));
        $pageResponse->assertOk();
        $pageResponse->assertSee('Visible copy', false);
        $pageResponse->assertDontSee('alert("xss")', false);
        $pageResponse->assertDontSee('onerror=', false);
        $this->assertStringNotContainsString('<script>alert', $pageResponse->getContent());
    }
}
