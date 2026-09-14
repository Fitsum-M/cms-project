<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\PostVisibility;
use App\Models\PostType;
use App\Models\User;
use App\Services\PostService;
use App\Services\PostTypeService;
use App\Support\CustomFields\CustomFieldRegistry;
use Illuminate\Database\Seeder;

class CustomPostTypesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (PostType::query()->where('slug', CustomFieldRegistry::TEAM_MEMBERS)->exists()) {
            $this->command?->info('Custom post type demo data already seeded. Skipping.');

            return;
        }

        $this->call(RoleSeeder::class);

        $author = User::query()->where('email', 'admin@cms.local')->first()
            ?? User::factory()->create([
                'name' => 'Administrator',
                'email' => 'admin@cms.local',
                'username' => 'admin',
            ]);

        if (! $author->hasRole('Administrator')) {
            $author->assignSingleRole('Administrator');
        }

        $types = app(PostTypeService::class);
        $posts = app(PostService::class);

        $types->create([
            'plural_name' => 'Team Members',
            'singular_name' => 'Team Member',
            'slug' => CustomFieldRegistry::TEAM_MEMBERS,
            'icon' => 'heroicon-o-users',
            'supports_categories' => false,
            'supports_tags' => false,
            'supports_excerpt' => true,
            'supports_featured_image' => true,
            'default_schema_type' => 'Person',
        ]);

        $types->create([
            'plural_name' => 'Services',
            'singular_name' => 'Service',
            'slug' => CustomFieldRegistry::SERVICES,
            'icon' => 'heroicon-o-briefcase',
            'supports_categories' => false,
            'supports_tags' => true,
            'supports_excerpt' => true,
            'supports_featured_image' => true,
            'default_schema_type' => 'Service',
        ]);

        $types->create([
            'plural_name' => 'Products',
            'singular_name' => 'Product',
            'slug' => CustomFieldRegistry::PRODUCTS,
            'icon' => 'heroicon-o-shopping-bag',
            'supports_categories' => true,
            'supports_tags' => true,
            'supports_excerpt' => true,
            'supports_featured_image' => true,
            'default_schema_type' => 'Product',
        ]);

        $types->create([
            'plural_name' => 'Testimonials',
            'singular_name' => 'Testimonial',
            'slug' => CustomFieldRegistry::TESTIMONIALS,
            'icon' => 'heroicon-o-chat-bubble-left-right',
            'supports_categories' => false,
            'supports_tags' => false,
            'supports_excerpt' => false,
            'supports_featured_image' => false,
            'default_schema_type' => 'Review',
        ]);

        $posts->create([
            'title' => 'Amina Bekele',
            'slug' => 'amina-bekele',
            'body' => '<p>Amina leads editorial strategy and content operations.</p>',
            'excerpt' => 'Director of Content Operations',
            'author_id' => $author->id,
            'post_type' => CustomFieldRegistry::TEAM_MEMBERS,
            'status' => ContentStatus::Published->value,
            'visibility' => PostVisibility::Public->value,
            'published_at' => now()->subDays(10),
            'custom_fields' => [
                'job_title' => 'Director of Content',
                'department' => 'Editorial Committee',
                'bio' => 'Amina has spent a decade building publishing workflows for mission-driven organizations.',
                'social_links' => [
                    ['label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/'],
                    ['label' => 'X', 'url' => 'https://x.com/'],
                ],
                'headshot_id' => null,
            ],
        ], $author);

        $posts->create([
            'title' => 'Daniel Worku',
            'slug' => 'daniel-worku',
            'body' => '<p>Daniel oversees product and platform reliability.</p>',
            'excerpt' => 'Head of Platform',
            'author_id' => $author->id,
            'post_type' => CustomFieldRegistry::TEAM_MEMBERS,
            'status' => ContentStatus::Published->value,
            'visibility' => PostVisibility::Public->value,
            'published_at' => now()->subDays(9),
            'custom_fields' => [
                'job_title' => 'Head of Platform',
                'department' => 'Technology',
                'bio' => 'Daniel focuses on media pipelines, performance, and editor experience.',
                'social_links' => [
                    ['label' => 'GitHub', 'url' => 'https://github.com/'],
                ],
                'headshot_id' => null,
            ],
        ], $author);

        $posts->create([
            'title' => 'Content Strategy Workshop',
            'slug' => 'content-strategy-workshop',
            'body' => '<p>A facilitated workshop that helps teams define information architecture and publishing cadence.</p>',
            'excerpt' => 'Plan clearer content systems with your editors.',
            'author_id' => $author->id,
            'post_type' => CustomFieldRegistry::SERVICES,
            'status' => ContentStatus::Published->value,
            'visibility' => PostVisibility::Public->value,
            'published_at' => now()->subDays(8),
            'custom_fields' => [
                'icon_id' => null,
                'key_benefits' => ['Shared content model', 'Editor-ready playbooks', 'Measurable publishing goals'],
                'feature_highlights' => ['Taxonomy mapping', 'Role workshops', '90-day roadmap'],
                'eligibility' => 'Best for organizations with at least one active editorial team.',
                'cta_text' => 'Book a discovery call',
                'cta_url' => 'https://example.com/contact',
            ],
        ], $author);

        $posts->create([
            'title' => 'Managed Publishing Support',
            'slug' => 'managed-publishing-support',
            'body' => '<p>Ongoing support for draft review, media QA, and release coordination.</p>',
            'excerpt' => 'Keep publishing moving without adding headcount.',
            'author_id' => $author->id,
            'post_type' => CustomFieldRegistry::SERVICES,
            'status' => ContentStatus::Published->value,
            'visibility' => PostVisibility::Public->value,
            'published_at' => now()->subDays(7),
            'custom_fields' => [
                'icon_id' => null,
                'key_benefits' => ['Faster reviews', 'Consistent brand voice', 'Release checklists'],
                'feature_highlights' => ['Weekly standups', 'Media QA', 'SEO pass'],
                'eligibility' => 'Requires an active CMS workspace and designated editor contact.',
                'cta_text' => 'Request support plan',
                'cta_url' => 'https://example.com/support',
            ],
        ], $author);

        $posts->create([
            'title' => 'CMS Starter Kit',
            'slug' => 'cms-starter-kit',
            'body' => '<p>A packaged set of templates, taxonomies, and publishing defaults for new sites.</p>',
            'excerpt' => 'Ship a structured content foundation faster.',
            'author_id' => $author->id,
            'post_type' => CustomFieldRegistry::PRODUCTS,
            'status' => ContentStatus::Published->value,
            'visibility' => PostVisibility::Public->value,
            'published_at' => now()->subDays(6),
            'custom_fields' => [
                'sku' => 'CMS-START-01',
                'price' => '$1,200 one-time',
                'specifications' => [
                    ['label' => 'Includes', 'value' => '4 CPT schemas + demo pages'],
                    ['label' => 'Support', 'value' => '30 days email support'],
                    ['label' => 'Delivery', 'value' => 'Configurable package'],
                ],
                'gallery_ids' => [],
                'brochure_url' => 'https://example.com/files/cms-starter-kit.pdf',
                'availability' => 'in_stock',
            ],
        ], $author);

        $posts->create([
            'title' => 'Editorial Analytics Pack',
            'slug' => 'editorial-analytics-pack',
            'body' => '<p>Dashboards and reporting views for content performance and editorial throughput.</p>',
            'excerpt' => 'See what editors ship and what audiences read.',
            'author_id' => $author->id,
            'post_type' => CustomFieldRegistry::PRODUCTS,
            'status' => ContentStatus::Published->value,
            'visibility' => PostVisibility::Public->value,
            'published_at' => now()->subDays(5),
            'custom_fields' => [
                'sku' => 'CMS-ANALYTICS-02',
                'price' => '$89 / month',
                'specifications' => [
                    ['label' => 'Metrics', 'value' => 'Views, drafts, publish lag'],
                    ['label' => 'Export', 'value' => 'CSV weekly digest'],
                ],
                'gallery_ids' => [],
                'brochure_url' => 'https://example.com/files/analytics-pack.pdf',
                'availability' => 'preorder',
            ],
        ], $author);

        $posts->create([
            'title' => 'Trusted by our editors',
            'slug' => 'trusted-by-our-editors',
            'body' => null,
            'author_id' => $author->id,
            'post_type' => CustomFieldRegistry::TESTIMONIALS,
            'status' => ContentStatus::Published->value,
            'visibility' => PostVisibility::Public->value,
            'published_at' => now()->subDays(4),
            'custom_fields' => [
                'quote' => 'The custom fields finally let us model services and team pages without hacking the blog.',
                'author_name' => 'Sara Hailu',
                'author_role' => 'Managing Editor',
                'company' => 'North Ridge Media',
                'rating' => 5,
            ],
        ], $author);

        $posts->create([
            'title' => 'Clearer product pages',
            'slug' => 'clearer-product-pages',
            'body' => null,
            'author_id' => $author->id,
            'post_type' => CustomFieldRegistry::TESTIMONIALS,
            'status' => ContentStatus::Published->value,
            'visibility' => PostVisibility::Public->value,
            'published_at' => now()->subDays(3),
            'custom_fields' => [
                'quote' => 'SKU, specs, and brochure links live on the product CPT — no more spreadsheet glue.',
                'author_name' => 'Jonas Abebe',
                'author_role' => 'Product Lead',
                'company' => 'Lakeview Labs',
                'rating' => 4,
            ],
        ], $author);

        $this->command?->info('Seeded Team Members, Services, Products, and Testimonials with structured custom fields.');
    }
}
