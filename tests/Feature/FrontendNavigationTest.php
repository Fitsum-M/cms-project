<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Page;
use App\Models\User;
use App\Services\FrontendContentService;
use Database\Seeders\GeneralSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(GeneralSettingsSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
    }

    public function test_navigation_pages_eager_load_published_nav_children(): void
    {
        $author = User::factory()->create();

        $about = Page::factory()->published()->inNavigation()->create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'author_id' => $author->id,
            'sort_order' => 1,
        ]);

        $team = Page::factory()->published()->inNavigation()->childOf($about)->create([
            'title' => 'Our Team',
            'slug' => 'our-team',
            'author_id' => $author->id,
            'sort_order' => 1,
        ]);

        Page::factory()->published()->childOf($about)->create([
            'title' => 'Hidden Child',
            'slug' => 'hidden-child',
            'author_id' => $author->id,
            'show_in_navigation' => false,
        ]);

        Page::factory()->inNavigation()->childOf($about)->create([
            'title' => 'Draft Child',
            'slug' => 'draft-child',
            'author_id' => $author->id,
            'status' => ContentStatus::Draft,
        ]);

        Page::factory()->published()->inNavigation()->childOf($about)->create([
            'title' => 'Future Child',
            'slug' => 'future-child',
            'author_id' => $author->id,
            'published_at' => now()->addDay(),
        ]);

        $navPages = app(FrontendContentService::class)->navigationPages();

        $this->assertCount(1, $navPages);
        $this->assertTrue($navPages->first()->relationLoaded('children'));
        $this->assertSame(['Our Team'], $navPages->first()->children->pluck('title')->all());
        $this->assertTrue($navPages->first()->children->contains('id', $team->id));
    }

    public function test_frontend_header_renders_nested_page_dropdown(): void
    {
        $author = User::factory()->create();

        $about = Page::factory()->published()->inNavigation()->create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'author_id' => $author->id,
        ]);

        Page::factory()->published()->inNavigation()->childOf($about)->create([
            'title' => 'Our Team',
            'slug' => 'our-team',
            'author_id' => $author->id,
        ]);

        Page::factory()->published()->inNavigation()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'author_id' => $author->id,
        ]);

        $response = $this->get(route('frontend.home'));

        $response->assertOk();
        $response->assertSee('About Us');
        $response->assertSee('Our Team');
        $response->assertSee(route('frontend.pages.show', 'our-team'), false);
        $response->assertSee('Contact');
        $response->assertSee('x-data="{ open: false }"', false);
        $response->assertSee('aria-label="About Us submenu"', false);
    }
}
