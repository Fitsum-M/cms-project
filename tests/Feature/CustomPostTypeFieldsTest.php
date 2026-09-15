<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\PostVisibility;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use App\Support\CustomFields\CustomFieldRegistry;
use Database\Seeders\CustomPostTypesDemoSeeder;
use Database\Seeders\GeneralSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomPostTypeFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(GeneralSettingsSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
    }

    public function test_demo_seeder_creates_required_cpts_with_structured_fields(): void
    {
        $this->seed(CustomPostTypesDemoSeeder::class);

        foreach (CustomFieldRegistry::demoTypeSlugs() as $slug) {
            $this->assertDatabaseHas('post_types', ['slug' => $slug]);
            $this->assertTrue(
                Post::query()->where('post_type', $slug)->where('status', ContentStatus::Published)->exists(),
                "Expected published posts for {$slug}",
            );
        }

        $team = Post::query()->where('slug', 'amina-bekele')->first();
        $this->assertNotNull($team);
        $this->assertSame('Director of Content', $team->customField('job_title'));
        $this->assertSame('Editorial Committee', $team->customField('department'));
        $this->assertNotEmpty($team->customField('bio'));
        $this->assertIsArray($team->customField('social_links'));

        $service = Post::query()->where('slug', 'content-strategy-workshop')->first();
        $this->assertNotNull($service);
        $this->assertContains('Shared content model', $service->customField('key_benefits'));
        $this->assertSame('Book a discovery call', $service->customField('cta_text'));

        $product = Post::query()->where('slug', 'cms-starter-kit')->first();
        $this->assertNotNull($product);
        $this->assertSame('CMS-START-01', $product->customField('sku'));
        $this->assertSame('in_stock', $product->customField('availability'));
        $this->assertNotEmpty($product->customField('brochure_url'));

        $testimonial = Post::query()->where('slug', 'trusted-by-our-editors')->first();
        $this->assertNotNull($testimonial);
        $this->assertSame('Sara Hailu', $testimonial->customField('author_name'));
        $this->assertSame(5, $testimonial->customField('rating'));
    }

    public function test_post_service_persists_custom_fields_for_team_members(): void
    {
        $this->seed(CustomPostTypesDemoSeeder::class);

        $author = User::query()->where('email', 'admin@cms.local')->firstOrFail();

        $post = app(PostService::class)->create([
            'title' => 'Helen Desta',
            'slug' => 'helen-desta',
            'body' => '<p>Bio body</p>',
            'author_id' => $author->id,
            'post_type' => CustomFieldRegistry::TEAM_MEMBERS,
            'status' => ContentStatus::Published->value,
            'visibility' => PostVisibility::Public->value,
            'custom_fields' => [
                'job_title' => 'Communications Lead',
                'department' => 'Outreach',
                'bio' => 'Helen coordinates partner messaging.',
                'social_links' => [
                    ['label' => 'LinkedIn', 'url' => 'https://linkedin.com/in/helen'],
                ],
                'headshot_id' => null,
            ],
        ], $author);

        $this->assertSame('Communications Lead', $post->fresh()->customField('job_title'));
        $this->assertSame('Outreach', $post->customField('department'));
        $this->assertSame('Helen coordinates partner messaging.', $post->customField('bio'));
        $this->assertSame('https://linkedin.com/in/helen', $post->customField('social_links.0.url'));
    }

    public function test_frontend_type_archives_and_detail_render_custom_fields(): void
    {
        $this->seed(CustomPostTypesDemoSeeder::class);

        $this->get(route('frontend.types.index', CustomFieldRegistry::TEAM_MEMBERS))
            ->assertOk()
            ->assertSee('Team Members')
            ->assertSee('Amina Bekele');

        $this->get(route('frontend.types.index', CustomFieldRegistry::SERVICES))
            ->assertOk()
            ->assertSee('Content Strategy Workshop');

        $this->get(route('frontend.types.index', CustomFieldRegistry::PRODUCTS))
            ->assertOk()
            ->assertSee('CMS Starter Kit');

        $this->get(route('frontend.types.index', CustomFieldRegistry::TESTIMONIALS))
            ->assertOk()
            ->assertSee('Sara Hailu')
            ->assertSee('The custom fields finally let us model services');

        $this->get(route('frontend.posts.show', 'amina-bekele'))
            ->assertOk()
            ->assertSee('Job Title / Position')
            ->assertSee('Director of Content')
            ->assertSee('Editorial Committee')
            ->assertSee('LinkedIn');

        $this->get(route('frontend.posts.show', 'cms-starter-kit'))
            ->assertOk()
            ->assertSee('CMS-START-01')
            ->assertSee('Download brochure / datasheet');

        $this->get(route('frontend.posts.show', 'trusted-by-our-editors'))
            ->assertOk()
            ->assertSee('Sara Hailu')
            ->assertSee('Rating: 5/5');
    }

    public function test_unknown_type_archive_returns_not_found(): void
    {
        $this->get(route('frontend.types.index', 'not-a-real-type'))->assertNotFound();
    }
}
