<?php

namespace Tests\Feature;

use App\Enums\ContentSlugScope;
use App\Enums\SlugConflictResolution;
use App\Models\Page;
use App\Models\Post;
use App\Services\ContentSlugService;
use App\Services\ContentUrlGenerator;
use App\Support\Settings\PermalinkSettings;
use App\Support\SlugGenerator;
use Database\Seeders\PermalinkSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContentSlugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermalinkSettingsSeeder::class);
    }

    public function test_slugs_are_sanitized_to_lowercase_hyphenated_alphanumerics(): void
    {
        $this->assertSame('hello-world', SlugGenerator::sanitize('Hello World!'));
        $this->assertSame('cafe-2026', SlugGenerator::sanitize('Café 2026'));
        $this->assertSame('item', SlugGenerator::sanitize('***'));
    }

    public function test_slug_auto_generates_from_title_on_initial_resolve(): void
    {
        $slug = app(ContentSlugService::class)->resolve([
            'title' => 'Hello World',
            'scope' => ContentSlugScope::Posts,
        ]);

        $this->assertSame('hello-world', $slug);
    }

    public function test_manual_slug_is_used_when_provided(): void
    {
        $slug = app(ContentSlugService::class)->resolve([
            'title' => 'Hello World',
            'slug' => 'Custom Slug!!',
            'scope' => ContentSlugScope::Posts,
        ]);

        $this->assertSame('custom-slug', $slug);
    }

    public function test_slug_required_when_auto_generation_disabled(): void
    {
        app(PermalinkSettings::class)->save([
            ...app(PermalinkSettings::class)->all(),
            PermalinkSettings::AUTO_GENERATE_SLUGS => false,
        ]);

        $this->expectException(ValidationException::class);

        app(ContentSlugService::class)->resolve([
            'title' => 'Hello World',
            'scope' => ContentSlugScope::Posts,
        ]);
    }

    public function test_post_slug_uniqueness_appends_numeric_suffix(): void
    {
        Post::factory()->create(['slug' => 'sample-post']);

        $slug = app(ContentSlugService::class)->resolve([
            'title' => 'Sample Post',
            'scope' => ContentSlugScope::Posts,
        ]);

        $this->assertSame('sample-post-2', $slug);

        Post::factory()->create(['slug' => 'sample-post-2']);

        $slug3 = app(ContentSlugService::class)->resolve([
            'title' => 'Sample Post',
            'scope' => ContentSlugScope::Posts,
        ]);

        $this->assertSame('sample-post-3', $slug3);
    }

    public function test_page_slugs_are_unique_across_entire_page_namespace(): void
    {
        Page::factory()->create(['slug' => 'about']);

        $slug = app(ContentSlugService::class)->resolve([
            'title' => 'About',
            'scope' => ContentSlugScope::Pages,
        ]);

        $this->assertSame('about-2', $slug);
    }

    public function test_post_and_page_namespaces_are_independent(): void
    {
        Post::factory()->create(['slug' => 'shared']);

        $pageSlug = app(ContentSlugService::class)->resolve([
            'title' => 'Shared',
            'scope' => ContentSlugScope::Pages,
        ]);

        $this->assertSame('shared', $pageSlug);
    }

    public function test_block_save_conflict_resolution_rejects_duplicate(): void
    {
        app(PermalinkSettings::class)->save([
            ...app(PermalinkSettings::class)->all(),
            PermalinkSettings::CONFLICT_RESOLUTION => SlugConflictResolution::BlockSave->value,
        ]);

        Post::factory()->create(['slug' => 'taken']);

        try {
            app(ContentSlugService::class)->resolve([
                'title' => 'Taken',
                'scope' => ContentSlugScope::Posts,
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('slug', $exception->errors());
            $this->assertStringContainsString('already used', $exception->errors()['slug'][0]);
        }
    }

    public function test_prompt_user_conflict_requires_acceptance_then_appends_suffix(): void
    {
        app(PermalinkSettings::class)->save([
            ...app(PermalinkSettings::class)->all(),
            PermalinkSettings::CONFLICT_RESOLUTION => SlugConflictResolution::PromptUser->value,
        ]);

        Post::factory()->create(['slug' => 'news']);

        try {
            app(ContentSlugService::class)->resolve([
                'title' => 'News',
                'scope' => ContentSlugScope::Posts,
            ]);
            $this->fail('Expected ValidationException for prompt');
        } catch (ValidationException $exception) {
            $this->assertSame('prompt', $exception->errors()['slug_conflict'][0] ?? null);
            $this->assertSame('news-2', $exception->errors()['slug_suggestion'][0] ?? null);
        }

        $accepted = app(ContentSlugService::class)->resolve([
            'title' => 'News',
            'scope' => ContentSlugScope::Posts,
            'accept_conflict_resolution' => true,
        ]);

        $this->assertSame('news-2', $accepted);
    }

    public function test_published_slug_change_requires_confirmation(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Live Post',
            'slug' => 'live-post',
        ]);

        try {
            app(ContentSlugService::class)->resolve([
                'title' => 'Live Post',
                'slug' => 'renamed-live',
                'scope' => ContentSlugScope::Posts,
                'ignore_id' => $post->id,
                'current_slug' => $post->slug,
                'has_been_published' => true,
                'confirm_slug_change' => false,
            ]);
            $this->fail('Expected ValidationException for slug change confirmation');
        } catch (ValidationException $exception) {
            $this->assertSame('required', $exception->errors()['slug_change_confirmation'][0] ?? null);
        }

        $confirmed = app(ContentSlugService::class)->resolve([
            'title' => 'Live Post',
            'slug' => 'renamed-live',
            'scope' => ContentSlugScope::Posts,
            'ignore_id' => $post->id,
            'current_slug' => $post->slug,
            'has_been_published' => true,
            'confirm_slug_change' => true,
        ]);

        $this->assertSame('renamed-live', $confirmed);
    }

    public function test_url_generation_follows_permalink_structures(): void
    {
        app(PermalinkSettings::class)->save([
            ...app(PermalinkSettings::class)->all(),
            PermalinkSettings::POST_URL_STRUCTURE => '/{post-type}/{slug}/',
            PermalinkSettings::PAGE_URL_STRUCTURE => '/{parent-slug}/{slug}/',
        ]);

        $post = Post::factory()->published()->create([
            'slug' => 'hello-world',
            'post_type' => 'blog',
            'published_at' => now()->setDate(2026, 8, 9)->setTime(12, 0),
        ]);

        $parent = Page::factory()->create(['slug' => 'about']);
        $child = Page::factory()->childOf($parent)->create(['slug' => 'team']);

        $urls = app(ContentUrlGenerator::class);

        $this->assertSame('/blog/hello-world/', $urls->postPath($post));
        $this->assertSame('/about/team/', $urls->pagePath($child));
        $this->assertSame('/about/', $urls->pagePath($parent));
    }

    public function test_post_url_supports_date_tokens(): void
    {
        app(PermalinkSettings::class)->save([
            ...app(PermalinkSettings::class)->all(),
            PermalinkSettings::POST_URL_STRUCTURE => '/{year}/{month}/{day}/{slug}/',
        ]);

        $post = Post::factory()->create([
            'slug' => 'launch',
            'published_at' => now()->setDate(2026, 3, 5)->setTime(10, 0),
        ]);

        $this->assertSame(
            '/2026/03/05/launch/',
            app(ContentUrlGenerator::class)->postPath($post),
        );
    }

    public function test_ignore_id_allows_keeping_existing_slug_on_update(): void
    {
        $post = Post::factory()->create(['slug' => 'keep-me']);

        $slug = app(ContentSlugService::class)->resolve([
            'title' => 'Keep Me',
            'slug' => 'keep-me',
            'scope' => ContentSlugScope::Posts,
            'ignore_id' => $post->id,
            'current_slug' => 'keep-me',
        ]);

        $this->assertSame('keep-me', $slug);
    }
}
