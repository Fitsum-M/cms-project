<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostType;
use App\Support\CustomFields\CustomFieldRegistry;
use Database\Seeders\AbdiGudinaSiteSeeder;
use Database\Seeders\GeneralSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbdiGudinaSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(GeneralSettingsSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        $this->seed(AbdiGudinaSiteSeeder::class);
    }

    public function test_home_renders_cms_driven_organization_sections(): void
    {
        $response = $this->get(route('frontend.home'));

        $response->assertOk();
        $response->assertSee('Abdi Gudina');
        $response->assertSee('21,920+');
        $response->assertSee('Savings');
        $response->assertSee('Loans');
        $response->assertSee('Wegayehu Tsige');
        $response->assertSee('Verify Eligibility');
        $response->assertSee('What is Abdi Gudina Union?');
        $response->assertSee('Aba Geda SACCOS');
        $response->assertSee('Federal Cooperative Agency');
        $response->assertSee('Membership Application Guide');
        $response->assertSee('AG Union expands capacity-building');
    }

    public function test_navigation_is_cms_page_driven_with_dropdowns(): void
    {
        $response = $this->get(route('frontend.home'));

        $response->assertOk();
        $response->assertSee('About Us');
        $response->assertSee('Our Story');
        $response->assertSee('Leadership');
        $response->assertSee('Membership');
        $response->assertSee('Impact');
        $response->assertSee('Resources');
        $response->assertSee('Contact');
        $response->assertSee('aria-label="About Us submenu"', false);
        // Hard-coded CPT nav labels from Step 8 demo should not be primary nav keys
        $response->assertDontSee('>Testimonials</a>', false);
    }

    public function test_required_capstone_cpts_exist_with_content(): void
    {
        foreach ([
            CustomFieldRegistry::STATS,
            CustomFieldRegistry::SERVICES,
            CustomFieldRegistry::TEAM_MEMBERS,
            CustomFieldRegistry::IMPACT_STORIES,
            CustomFieldRegistry::RESOURCES,
            CustomFieldRegistry::FAQS,
            CustomFieldRegistry::MEMBER_SACCOS,
            CustomFieldRegistry::JOIN_STEPS,
        ] as $slug) {
            $this->assertTrue(PostType::query()->where('slug', $slug)->exists(), "Missing CPT {$slug}");
            $this->assertTrue(
                Post::query()->where('post_type', $slug)->exists(),
                "Missing content for {$slug}",
            );
        }

        $this->assertTrue(Post::query()->where('post_type', 'post')->where('slug', 'ag-union-expands-capacity-building')->exists());
    }

    public function test_type_archives_and_pages_are_public(): void
    {
        $this->get(route('frontend.types.index', CustomFieldRegistry::STATS))->assertOk()->assertSee('21,920+');
        $this->get(route('frontend.types.index', CustomFieldRegistry::SERVICES))->assertOk()->assertSee('Credit Life Insurance');
        $this->get(route('frontend.types.index', CustomFieldRegistry::IMPACT_STORIES))->assertOk()->assertSee('2018 E.C');
        $this->get(route('frontend.types.index', CustomFieldRegistry::RESOURCES))->assertOk()->assertSee('Download');
        $this->get(route('frontend.pages.show', 'about-us'))->assertOk()->assertSee('Vision');
        $this->get(route('frontend.pages.show', 'contact'))->assertOk()->assertSee('contact@abdigudina.com');
        $this->get(route('frontend.blog'))->assertOk()->assertSee('AG Union expands capacity-building');
    }
}
