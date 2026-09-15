<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\PostVisibility;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use App\Support\CustomFields\CustomFieldRegistry;
use Illuminate\Database\Seeder;

/**
 * Completes the multi-page CPT demo website (Review Comment #3 Step 8.2).
 * Safe to re-run: skips entries that already exist by slug.
 */
class DemoWebsiteSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CustomPostTypesDemoSeeder::class);

        $author = User::query()->where('email', 'admin@cms.local')->first()
            ?? User::factory()->create([
                'name' => 'Administrator',
                'email' => 'admin@cms.local',
                'username' => 'admin',
            ]);

        if (method_exists($author, 'hasRole') && ! $author->hasRole('Administrator')) {
            $author->assignSingleRole('Administrator');
        }

        $posts = app(PostService::class);

        $this->ensurePost($posts, $author, [
            'title' => 'Helen Desta',
            'slug' => 'helen-desta',
            'body' => '<p>Helen leads partner communications and public storytelling.</p>',
            'excerpt' => 'Communications Lead',
            'post_type' => CustomFieldRegistry::TEAM_MEMBERS,
            'published_at' => now()->subDays(2),
            'custom_fields' => [
                'job_title' => 'Communications Lead',
                'department' => 'Outreach',
                'bio' => 'Helen coordinates partner messaging and media relations across programs.',
                'social_links' => [
                    ['label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/'],
                ],
                'headshot_id' => null,
            ],
        ]);

        $this->ensurePost($posts, $author, [
            'title' => 'Editorial Training Retainer',
            'slug' => 'editorial-training-retainer',
            'body' => '<p>Monthly coaching for editors on workflow, taxonomy, and SEO hygiene.</p>',
            'excerpt' => 'Keep your editorial team sharp with recurring training.',
            'post_type' => CustomFieldRegistry::SERVICES,
            'published_at' => now()->subDays(1),
            'custom_fields' => [
                'icon_id' => null,
                'key_benefits' => ['Live coaching', 'Office hours', 'Playbook updates'],
                'feature_highlights' => ['Monthly workshops', 'Async reviews', 'KPI check-ins'],
                'eligibility' => 'Available to organizations with an active CMS workspace.',
                'cta_text' => 'Start a retainer',
                'cta_url' => 'https://example.com/retainers',
            ],
        ]);

        $this->ensurePost($posts, $author, [
            'title' => 'Governance Checklist Pack',
            'slug' => 'governance-checklist-pack',
            'body' => '<p>Ready-to-use publishing checklists for legal, brand, and accessibility review.</p>',
            'excerpt' => 'Ship safer releases with shared review gates.',
            'post_type' => CustomFieldRegistry::PRODUCTS,
            'published_at' => now()->subHours(12),
            'custom_fields' => [
                'sku' => 'CMS-GOV-03',
                'price' => '$249 one-time',
                'specifications' => [
                    ['label' => 'Formats', 'value' => 'PDF + Notion templates'],
                    ['label' => 'License', 'value' => 'Organization-wide'],
                ],
                'gallery_ids' => [],
                'brochure_url' => 'https://example.com/files/governance-pack.pdf',
                'availability' => 'in_stock',
            ],
        ]);

        $this->ensurePost($posts, $author, [
            'title' => 'Faster handoffs',
            'slug' => 'faster-handoffs',
            'body' => null,
            'post_type' => CustomFieldRegistry::TESTIMONIALS,
            'published_at' => now()->subHours(6),
            'custom_fields' => [
                'quote' => 'Our public site finally mirrors how we work: services, people, and products are first-class content.',
                'author_name' => 'Marta Tadesse',
                'author_role' => 'Operations Director',
                'company' => 'Horizon Civic Trust',
                'rating' => 5,
            ],
        ]);

        $about = $this->ensurePage([
            'title' => 'About Us',
            'slug' => 'about-us',
            'body' => '<p>We help organizations publish structured content — services, people, products, and stories — from one CMS.</p><p>This About page is a CMS Page. The Team, Services, Products, and Testimonials sections on the home page are Custom Post Types with dedicated fields.</p><p><a href="/types/team-members">Meet the team</a> · <a href="/types/services">Browse services</a></p>',
            'author_id' => $author->id,
            'sort_order' => 0,
            'show_in_navigation' => true,
        ]);

        $this->ensurePage([
            'title' => 'Our Team',
            'slug' => 'our-team',
            'body' => '<p>Leadership and staff profiles are managed as <strong>Team Members</strong> custom posts.</p><p><a href="/types/team-members">View the live team directory</a> powered by job title, department, bio, social links, and headshot fields.</p>',
            'author_id' => $author->id,
            'parent_id' => $about->id,
            'show_in_navigation' => true,
        ]);

        $this->ensurePage([
            'title' => 'Services',
            'slug' => 'services',
            'body' => '<p>Our service catalog is not a static HTML page — each offering is a Services CPT with benefits, features, eligibility, and CTA fields.</p><p><a href="/types/services">Open the services archive</a> or start from the <a href="/">website home</a>.</p>',
            'author_id' => $author->id,
            'sort_order' => 1,
            'show_in_navigation' => true,
        ]);

        $this->ensurePage([
            'title' => 'Contact',
            'slug' => 'contact',
            'body' => '<p>Questions about the demo website or CMS workflows? Reach out — we would love to hear from you.</p><p>Email: <a href="mailto:hello@example.com">hello@example.com</a></p><p>Or browse <a href="/types/products">products</a> and <a href="/blog">news</a> while you are here.</p>',
            'author_id' => $author->id,
            'sort_order' => 2,
            'show_in_navigation' => true,
        ]);

        $this->command?->info('Demo website pages and extra CPT entries are ready.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensurePost(PostService $posts, User $author, array $data): void
    {
        if (Post::query()->where('slug', $data['slug'])->exists()) {
            return;
        }

        $posts->create([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'body' => $data['body'] ?? null,
            'excerpt' => $data['excerpt'] ?? null,
            'author_id' => $author->id,
            'post_type' => $data['post_type'],
            'status' => ContentStatus::Published->value,
            'visibility' => PostVisibility::Public->value,
            'published_at' => $data['published_at'] ?? now(),
            'custom_fields' => $data['custom_fields'] ?? null,
        ], $author);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensurePage(array $data): Page
    {
        $existing = Page::query()->where('slug', $data['slug'])->first();

        if ($existing !== null) {
            $existing->fill([
                'body' => $data['body'],
                'show_in_navigation' => $data['show_in_navigation'] ?? $existing->show_in_navigation,
                'parent_id' => $data['parent_id'] ?? $existing->parent_id,
                'sort_order' => $data['sort_order'] ?? $existing->sort_order,
                'status' => ContentStatus::Published,
                'published_at' => $existing->published_at ?? now()->subDay(),
            ])->save();

            return $existing->fresh() ?? $existing;
        }

        return Page::factory()->published()->inNavigation()->create([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'body' => $data['body'],
            'author_id' => $data['author_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'show_in_navigation' => $data['show_in_navigation'] ?? true,
        ]);
    }
}
