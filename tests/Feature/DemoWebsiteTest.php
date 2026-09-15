<?php

namespace Tests\Feature;

use App\Support\CustomFields\CustomFieldRegistry;
use Database\Seeders\DemoWebsiteSeeder;
use Database\Seeders\GeneralSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoWebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(GeneralSettingsSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        $this->seed(DemoWebsiteSeeder::class);
    }

    public function test_type_archives_use_specialized_cards(): void
    {
        $this->get(route('frontend.types.index', CustomFieldRegistry::TEAM_MEMBERS))
            ->assertOk()
            ->assertSee('View profile')
            ->assertSee('Director of Content');

        $this->get(route('frontend.types.index', CustomFieldRegistry::SERVICES))
            ->assertOk()
            ->assertSee('Book a discovery call')
            ->assertSee('Shared content model');

        $this->get(route('frontend.types.index', CustomFieldRegistry::PRODUCTS))
            ->assertOk()
            ->assertSee('CMS-START-01')
            ->assertSee('Brochure');

        $this->get(route('frontend.types.index', CustomFieldRegistry::TESTIMONIALS))
            ->assertOk()
            ->assertSee('North Ridge Media')
            ->assertSee('Read testimonial');
    }

    public function test_blog_route_lists_standard_posts_only(): void
    {
        $response = $this->get(route('frontend.blog'));
        $response->assertOk();
        $response->assertSee('Blog');
        $response->assertDontSee('Amina Bekele');
        $response->assertDontSee('Content Strategy Workshop');
    }

    public function test_website_pages_link_to_cpt_archives(): void
    {
        $this->get(route('frontend.pages.show', 'about-us'))
            ->assertOk()
            ->assertSee('Custom Post Types');

        $this->get(route('frontend.pages.show', 'our-team'))
            ->assertOk()
            ->assertSee('team directory');

        $this->get(route('frontend.pages.show', 'services'))
            ->assertOk()
            ->assertSee('services archive');

        $this->get(route('frontend.pages.show', 'contact'))
            ->assertOk()
            ->assertSee('hello@example.com');
    }
}
