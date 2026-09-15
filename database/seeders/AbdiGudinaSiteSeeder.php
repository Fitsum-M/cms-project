<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\PostVisibility;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostType;
use App\Models\User;
use App\Services\PostService;
use App\Services\PostTypeService;
use App\Support\CustomFields\CustomFieldRegistry;
use App\Support\Settings\GeneralSettings;
use Illuminate\Database\Seeder;

/**
 * Step 9 capstone: CMS-driven Abdi Gudina Financial Cooperatives Union replica.
 * Idempotent by post/page/type slug.
 */
class AbdiGudinaSiteSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);
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

        app(GeneralSettings::class)->save([
            'site_title' => 'Abdi Gudina Financial Cooperatives Union Ltd',
            'tagline' => 'Empowering communities through tailored cooperative finance.',
        ]);

        $types = app(PostTypeService::class);
        $posts = app(PostService::class);

        $this->ensureType($types, 'Stats', 'Stat', CustomFieldRegistry::STATS, 'heroicon-o-sparkles', false, false, false, false);
        $this->ensureType($types, 'Impact Stories', 'Impact Story', CustomFieldRegistry::IMPACT_STORIES, 'heroicon-o-trophy', false, true, true, true);
        $this->ensureType($types, 'Resources', 'Resource', CustomFieldRegistry::RESOURCES, 'heroicon-o-book-open', true, true, true, true);
        $this->ensureType($types, 'FAQs', 'FAQ', CustomFieldRegistry::FAQS, 'heroicon-o-chat-bubble-left-right', false, false, false, false);
        $this->ensureType($types, 'Member SACCOs', 'Member SACCO', CustomFieldRegistry::MEMBER_SACCOS, 'heroicon-o-building-office-2', false, false, true, false);
        $this->ensureType($types, 'Join Steps', 'Join Step', CustomFieldRegistry::JOIN_STEPS, 'heroicon-o-rectangle-stack', false, false, false, false);

        $this->seedStats($posts, $author);
        $this->seedAgServices($posts, $author);
        $this->seedLeadership($posts, $author);
        $this->seedJoinSteps($posts, $author);
        $this->seedFaqs($posts, $author);
        $this->seedImpact($posts, $author);
        $this->seedResources($posts, $author);
        $this->seedMemberSaccos($posts, $author);
        $this->seedNews($posts, $author);
        $this->seedPages($author);

        Page::query()->whereIn('slug', ['our-team'])->update(['show_in_navigation' => false]);

        $this->command?->info('Abdi Gudina CMS site content is ready.');
    }

    private function ensureType(
        PostTypeService $types,
        string $plural,
        string $singular,
        string $slug,
        string $icon,
        bool $categories,
        bool $tags,
        bool $excerpt,
        bool $featured,
    ): void {
        if (PostType::query()->where('slug', $slug)->exists()) {
            return;
        }

        $types->create([
            'plural_name' => $plural,
            'singular_name' => $singular,
            'slug' => $slug,
            'icon' => $icon,
            'supports_categories' => $categories,
            'supports_tags' => $tags,
            'supports_excerpt' => $excerpt,
            'supports_featured_image' => $featured,
        ]);
    }

    private function seedStats(PostService $posts, User $author): void
    {
        $items = [
            ['slug' => 'stat-members', 'title' => 'Individual members', 'value' => '21,920+', 'label' => 'Individual members across Oromia', 'order' => 1],
            ['slug' => 'stat-saccos', 'title' => 'Primary SACCOs', 'value' => '66', 'label' => 'Primary cooperatives served', 'order' => 2],
            ['slug' => 'stat-capital', 'title' => 'Member-owned capital', 'value' => '97.9M Birr', 'label' => 'Member-owned capital', 'order' => 3],
            ['slug' => 'stat-assets', 'title' => 'Assets managed', 'value' => '528,599,367.19', 'label' => 'Assets managed by the Union (Birr)', 'order' => 4],
        ];

        foreach ($items as $item) {
            $this->ensurePost($posts, $author, [
                'title' => $item['title'],
                'slug' => $item['slug'],
                'post_type' => CustomFieldRegistry::STATS,
                'published_at' => now()->subDays(30 - $item['order']),
                'custom_fields' => [
                    'value' => $item['value'],
                    'label' => $item['label'],
                    'display_order' => $item['order'],
                    'helper_text' => null,
                ],
            ]);
        }
    }

    private function seedAgServices(PostService $posts, User $author): void
    {
        $services = [
            [
                'title' => 'Savings',
                'slug' => 'ag-savings',
                'order' => 1,
                'excerpt' => 'Secure savings solutions that mobilize capital for member SACCOs.',
                'body' => '<p>Secure savings solutions, including mandatory and fixed deposits, that strengthen member SACCOs, mobilize capital, and support long-term financial stability.</p>',
                'benefits' => ['Mandatory deposits', 'Fixed deposits', 'Capital mobilization'],
                'features' => ['Regular savings', 'Voluntary savings', 'Sharia-compliant options'],
            ],
            [
                'title' => 'Loans',
                'slug' => 'ag-loans',
                'order' => 2,
                'excerpt' => 'Affordable financing for SACCOs and salary-backed borrowers.',
                'body' => '<p>Affordable financing for SACCOs and salary-backed borrowers, including housing, vehicle, machinery, and other specialized loan products.</p>',
                'benefits' => ['Below commercial rates', 'Short to long-term credit', 'Specialized products'],
                'features' => ['Housing loans', 'Vehicle loans', 'Agricultural credit'],
            ],
            [
                'title' => 'Credit Life Insurance',
                'slug' => 'ag-credit-life-insurance',
                'order' => 3,
                'excerpt' => 'Loan protection for borrowers, families, and cooperatives.',
                'body' => '<p>Reliable loan protection that safeguards borrowers, their families, and member cooperatives against unexpected financial risks.</p>',
                'benefits' => ['Borrower protection', 'Family security', 'Portfolio resilience'],
                'features' => ['Mandatory credit-life cover', 'Claim support', 'Transparent terms'],
            ],
            [
                'title' => 'Capacity Building',
                'slug' => 'ag-capacity-building',
                'order' => 4,
                'excerpt' => 'Training and advisory that strengthen cooperative performance.',
                'body' => '<p>Professional training, advisory, and institutional support that strengthen governance, leadership, financial management, and cooperative performance.</p>',
                'benefits' => ['Governance training', 'Leadership development', 'Financial literacy'],
                'features' => ['Workshops', 'Advisory', 'Institutional coaching'],
            ],
        ];

        foreach ($services as $service) {
            $this->ensurePost($posts, $author, [
                'title' => $service['title'],
                'slug' => $service['slug'],
                'body' => $service['body'],
                'excerpt' => $service['excerpt'],
                'post_type' => CustomFieldRegistry::SERVICES,
                'published_at' => now()->subDays(20 - $service['order']),
                'custom_fields' => [
                    'icon_id' => null,
                    'display_order' => $service['order'],
                    'key_benefits' => $service['benefits'],
                    'feature_highlights' => $service['features'],
                    'eligibility' => 'Available to member SACCOs of Abdi Gudina Financial Cooperatives Union.',
                    'cta_text' => 'Learn more',
                    'cta_url' => url('/posts/'.$service['slug']),
                ],
            ]);
        }
    }

    private function seedLeadership(PostService $posts, User $author): void
    {
        $this->ensurePost($posts, $author, [
            'title' => 'Wegayehu Tsige',
            'slug' => 'wegayehu-tsige',
            'body' => '<p>General Manager of Abdi Gudina Financial Cooperatives Union Ltd.</p>',
            'excerpt' => 'General Manager',
            'post_type' => CustomFieldRegistry::TEAM_MEMBERS,
            'published_at' => now()->subDays(15),
            'custom_fields' => [
                'job_title' => 'General Manager',
                'department' => 'Executive Leadership',
                'bio' => 'Every Birr we hold belongs to a member. We improve economic and social well-being through innovative financial and non-financial services.',
                'social_links' => [],
                'headshot_id' => null,
            ],
        ]);
    }

    private function seedJoinSteps(PostService $posts, User $author): void
    {
        $steps = [
            [1, 'verify-eligibility', 'Verify Eligibility', 'Ensure your primary SACCO is legally registered, financially sound, and actively operating within the Oromia region according to cooperative principles.'],
            [2, 'prepare-documentation', 'Prepare Documentation', 'Gather essential organizational documents, including approved bylaws, recent audit reports, and a general assembly resolution approving membership.'],
            [3, 'submit-application', 'Submit Application', 'Complete the official membership application. The Union board will review and verify your information against membership criteria.'],
            [4, 'fulfill-financials', 'Fulfill Financials', 'Upon approval, finalize membership by paying the ETB 3,000 registration fee and purchasing the minimum required shares (ETB 50,000).'],
        ];

        foreach ($steps as [$num, $slug, $title, $summary]) {
            $this->ensurePost($posts, $author, [
                'title' => $title,
                'slug' => $slug,
                'post_type' => CustomFieldRegistry::JOIN_STEPS,
                'published_at' => now()->subDays(14 - $num),
                'custom_fields' => [
                    'step_number' => $num,
                    'summary' => $summary,
                ],
            ]);
        }
    }

    private function seedFaqs(PostService $posts, User $author): void
    {
        $faqs = [
            ['what-is-abdi-gudina-union', 'What is Abdi Gudina Union?', 'We are a secondary-level financial cooperative union that serves as the apex body for primary SACCOs across the Oromia Region, providing wholesale lending, capacity building, and shared services.', 1],
            ['how-can-my-sacco-join', 'How can my SACCO join the Union?', 'Your SACCO must be legally registered, have a minimum of 200 members, and demonstrate sound governance. Apply at our Adama office or contact us online.', 2],
            ['what-loan-products', 'What loan products are available?', 'We offer short-term (up to 1 year), medium-term (1–3 years), and long-term (3–5 years) credit at rates significantly below commercial banks and microfinance institutions.', 3],
            ['is-savings-insured', 'Is my savings insured?', 'Member savings are protected through mandatory credit-life insurance and diversified investment portfolios. The Union undergoes annual external audits for full transparency.', 4],
        ];

        foreach ($faqs as [$slug, $title, $answer, $order]) {
            $this->ensurePost($posts, $author, [
                'title' => $title,
                'slug' => $slug,
                'post_type' => CustomFieldRegistry::FAQS,
                'published_at' => now()->subDays(10 - $order),
                'custom_fields' => [
                    'answer' => $answer,
                    'display_order' => $order,
                ],
            ]);
        }
    }

    private function seedImpact(PostService $posts, User $author): void
    {
        $items = [
            ['cbhi-2010', 'Outstanding Performance in Community-Based Health Insurance', 'Adama City Administration', '2010 E.C'],
            ['fca-2018', 'Certificate of Recognition for Contribution to the Cooperative Movement', 'Federal Cooperative Agency (FCA), Ethiopia', '2018 E.C'],
            ['cbhi-2016', 'Outstanding Performance in Community-Based Health Insurance', 'Adama City Administration', '2016 E.C'],
            ['ximir-2010', 'Certificate of Recognition', 'Ximir SACCOS', '2010 E.C'],
        ];

        foreach ($items as $i => [$slug, $title, $issuer, $year]) {
            $this->ensurePost($posts, $author, [
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $issuer.' · '.$year,
                'post_type' => CustomFieldRegistry::IMPACT_STORIES,
                'published_at' => now()->subDays(8 - $i),
                'custom_fields' => [
                    'issuer' => $issuer,
                    'year' => $year,
                    'location' => 'Oromia, Ethiopia',
                    'summary' => $title.' — recognized by '.$issuer.' ('.$year.').',
                ],
            ]);
        }
    }

    private function seedResources(PostService $posts, User $author): void
    {
        $items = [
            ['membership-application-guide', 'Membership Application Guide', 'guide', 'Membership', 'https://example.com/resources/membership-guide.pdf'],
            ['loan-product-brochure', 'Loan Product Brochure', 'pdf', 'Products', 'https://example.com/resources/loan-brochure.pdf'],
            ['annual-report-summary', 'Annual Report Summary', 'pdf', 'Reports', 'https://example.com/resources/annual-report.pdf'],
        ];

        foreach ($items as $i => [$slug, $title, $type, $category, $url]) {
            $this->ensurePost($posts, $author, [
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $category.' resource for member SACCOs and partners.',
                'post_type' => CustomFieldRegistry::RESOURCES,
                'published_at' => now()->subDays(6 - $i),
                'custom_fields' => [
                    'resource_type' => $type,
                    'category' => $category,
                    'file_url' => $url,
                    'cta_text' => 'Download',
                ],
            ]);
        }
    }

    private function seedMemberSaccos(PostService $posts, User $author): void
    {
        $saccos = [
            ['aba-geda-saccos', 'Aba Geda SACCOS', 'Adama City Administration'],
            ['adama-garment-emp-saccos', 'Adama Garment Emp SACCOS', 'Adama City Administration'],
            ['adama-no-3-school-saccos', 'Adama No 3 School SACCOS', 'Adama City Administration'],
            ['batu-saccos', 'Batu SACCOS', 'Adama City Administration'],
            ['biftu-geregna-saccos', 'Biftu Geregna SACCOS', 'Fentale Woreda'],
            ['boset-wereda-emp-saccos', 'Boset Wereda Emp. SACCOS', 'Boset Woreda'],
            ['gemechu-efa-saccos', 'Gemechu Efa SACCOS', 'Fentale Woreda'],
            ['wonji-fana-saccos', 'Wonji Fana SACCOS', 'Adama City Administration'],
        ];

        foreach ($saccos as $i => [$slug, $title, $location]) {
            $this->ensurePost($posts, $author, [
                'title' => $title,
                'slug' => $slug,
                'post_type' => CustomFieldRegistry::MEMBER_SACCOS,
                'published_at' => now()->subDays(5)->addMinutes($i),
                'custom_fields' => [
                    'location' => $location,
                    'initials' => mb_strtoupper(mb_substr(preg_replace('/[^A-Za-z]/', '', $title) ?? 'S', 0, 2)),
                    'membership_label' => 'Member SACCO',
                ],
            ]);
        }
    }

    private function seedNews(PostService $posts, User $author): void
    {
        $news = [
            ['ag-union-expands-capacity-building', 'AG Union expands capacity-building for member SACCOs', '<p>Training programs on governance and financial management continue across Oromia member cooperatives.</p>'],
            ['new-loan-window-for-housing', 'New loan window supports housing for SACCO members', '<p>Salary-backed housing finance is helping members invest in stable homes while keeping cooperative capital productive.</p>'],
            ['annual-assembly-highlights', 'Annual assembly highlights shared prosperity gains', '<p>Delegates reviewed capital growth, insurance coverage, and plans to deepen financial inclusion.</p>'],
        ];

        foreach ($news as $i => [$slug, $title, $body]) {
            $this->ensurePost($posts, $author, [
                'title' => $title,
                'slug' => $slug,
                'body' => $body,
                'excerpt' => strip_tags($body),
                'post_type' => 'post',
                'published_at' => now()->subDays(3 - $i),
            ]);
        }
    }

    private function seedPages(User $author): void
    {
        $this->ensurePage([
            'title' => 'Home',
            'slug' => \App\Support\CustomFields\HomepagePageFields::HOME_NAV_SLUG,
            'body' => '<p>Public homepage. Linked from navigation to the site root.</p>',
            'author_id' => $author->id,
            'show_in_navigation' => true,
            'sort_order' => 0,
        ]);

        $this->ensurePage([
            'title' => 'Empowering communities through Tailored cooperative finance.',
            'slug' => \App\Support\CustomFields\HomepagePageFields::HOME_HERO_SLUG,
            'body' => '<p>For over two decades, Abdi Gudina Financial Cooperatives Union Ltd. has helped member SACCOs grow through trusted financial services, strong governance, and shared prosperity. Serving 66 Primary SACCOs and 21,920+ members, we continue to build a more inclusive and resilient cooperative financial ecosystem in Oromia.</p>',
            'author_id' => $author->id,
            'show_in_navigation' => false,
            'sort_order' => 0,
            'seo' => [
                'meta_title' => 'Home - Abdi Gudina Financial Cooperatives Union Ltd',
                'meta_description' => 'EST. 1999 E.C · ADAMA, ETHIOPIA',
            ],
            'custom_fields' => [
                'primary_cta_label' => 'Explore services',
                'primary_cta_url' => '/types/services',
                'secondary_cta_label' => 'How to join',
                'secondary_cta_url' => '/pages/membership',
                'about_heading' => 'Growing together.',
                'about_link_label' => 'Read full about page →',
                'foundation_facts' => [
                    ['label' => 'Founded', 'value' => '1999 E.C'],
                    ['label' => 'Governance', 'value' => 'Member-Owned'],
                    ['label' => 'Headquarters', 'value' => 'Adama, Ethiopia'],
                ],
                'services_eyebrow' => 'Core Services',
                'services_heading' => 'Sustainable financial solutions',
                'services_intro' => 'Providing sustainable financial solutions that strengthen member SACCOs, promote financial inclusion, and support long-term cooperative development.',
                'services_all_label' => 'All services →',
                'saccos_eyebrow' => 'Member network',
                'saccos_heading' => ':count SACCOs. One Union.',
                'saccos_intro' => 'Primary cooperatives federated into the Union — managed as Member SACCOs in the CMS.',
                'saccos_all_label' => 'Full SACCO directory →',
                'join_eyebrow' => 'Membership',
                'join_heading' => 'How to join the Union',
                'leadership_eyebrow' => 'Leadership',
                'leadership_link_label' => 'Meet the team →',
                'faqs_eyebrow' => 'FAQs',
                'faqs_heading' => 'Questions members ask',
                'impact_eyebrow' => 'Impact',
                'impact_heading' => 'Recognition & stories',
                'impact_all_label' => 'All impact stories →',
                'resources_eyebrow' => 'Resources',
                'resources_heading' => 'Guides & downloads',
                'news_eyebrow' => 'News',
                'news_heading' => 'Latest updates',
                'news_all_label' => 'Visit news blog →',
            ],
        ]);

        $about = $this->ensurePage([
            'title' => 'About Us',
            'slug' => 'about-us',
            'body' => '<p>Since 1999 E.C., Abdi Gudina Financial Cooperatives Union Ltd. has been strengthening primary SACCOs through member-owned financial services, institutional capacity building, and responsible governance.</p><h3>Vision</h3><p>To be the leading and most trusted financial cooperative union in Ethiopia — ensuring financial inclusiveness, robust investment growth, and sustainable socio-economic development for every member.</p><h3>Mission</h3><p>To build strong financial institutions through demand-based savings, credit, interest-free financing, and micro-insurance — while cultivating financial literacy and confidence in every community we serve.</p>',
            'author_id' => $author->id,
            'show_in_navigation' => true,
            'sort_order' => 10,
        ]);

        $this->ensurePage([
            'title' => 'Our Story',
            'slug' => 'our-story',
            'body' => '<p>Abdi Gudina Financial Cooperatives Union Ltd. was founded on 16 November 1999 E.C by twelve visionary primary cooperatives. Today the Union brings together 66 Primary SACCOs and more than 21,920 members.</p>',
            'author_id' => $author->id,
            'parent_id' => $about->id,
            'show_in_navigation' => true,
            'sort_order' => 1,
        ]);

        $this->ensurePage([
            'title' => 'Leadership',
            'slug' => 'leadership',
            'body' => '<p>Leadership profiles are managed as Team Members in the CMS.</p><p><a href="/types/team-members">View leadership</a></p>',
            'author_id' => $author->id,
            'parent_id' => $about->id,
            'show_in_navigation' => true,
            'sort_order' => 2,
        ]);

        $this->ensurePage([
            'title' => 'Services',
            'slug' => 'services',
            'body' => '<p>Core services are powered by the Services custom post type.</p><p><a href="/types/services">Browse core services</a></p>',
            'author_id' => $author->id,
            'show_in_navigation' => true,
            'sort_order' => 20,
        ]);

        $this->ensurePage([
            'title' => 'Membership',
            'slug' => 'membership',
            'body' => '<p>Learn how primary SACCOs join the Union.</p><p><a href="/types/join-steps">How to join</a> · <a href="/types/member-saccos">Member SACCOs</a> · <a href="/types/faqs">FAQs</a></p>',
            'author_id' => $author->id,
            'show_in_navigation' => true,
            'sort_order' => 30,
        ]);

        $this->ensurePage([
            'title' => 'Impact',
            'slug' => 'impact',
            'body' => '<p>Recognition and community impact stories from across Oromia.</p><p><a href="/types/impact-stories">View impact stories</a></p>',
            'author_id' => $author->id,
            'show_in_navigation' => true,
            'sort_order' => 40,
        ]);

        $this->ensurePage([
            'title' => 'Resources',
            'slug' => 'resources',
            'body' => '<p>Guides, brochures, and reports for members and partners.</p><p><a href="/types/resources">Browse resources</a></p>',
            'author_id' => $author->id,
            'show_in_navigation' => true,
            'sort_order' => 50,
        ]);

        $this->ensurePage([
            'title' => 'News',
            'slug' => 'news',
            'body' => '<p>Updates from the Union.</p><p><a href="/blog">Read the news blog</a></p>',
            'author_id' => $author->id,
            'show_in_navigation' => true,
            'sort_order' => 60,
        ]);

        $this->ensurePage([
            'title' => 'Contact',
            'slug' => 'contact',
            'body' => '<p><strong>Adama City, Oda Wereda</strong><br>Oromia Region, Ethiopia</p><p>Phone: <a href="tel:+251222114181">+251-222-114-181</a><br>Email: <a href="mailto:contact@abdigudina.com">contact@abdigudina.com</a></p><p>Hours: Mon – Sat · 8:00 AM – 5:00 PM<br>Sunday closed</p>',
            'author_id' => $author->id,
            'show_in_navigation' => true,
            'sort_order' => 70,
        ]);
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
        $customFields = isset($data['custom_fields']) && is_array($data['custom_fields'])
            ? (
                $data['slug'] === \App\Support\CustomFields\HomepagePageFields::HOME_HERO_SLUG
                    ? \App\Support\CustomFields\HomepagePageFields::sanitize($data['custom_fields'])
                    : $data['custom_fields']
            )
            : null;

        if ($existing !== null) {
            $payload = [
                'title' => $data['title'],
                'body' => $data['body'],
                'show_in_navigation' => $data['show_in_navigation'] ?? $existing->show_in_navigation,
                'parent_id' => array_key_exists('parent_id', $data) ? $data['parent_id'] : $existing->parent_id,
                'sort_order' => $data['sort_order'] ?? $existing->sort_order,
                'status' => ContentStatus::Published,
                'published_at' => $existing->published_at ?? now()->subDay(),
            ];

            if ($customFields !== null) {
                $payload['custom_fields'] = $customFields;
            }

            $existing->fill($payload)->save();

            if (isset($data['seo']) && is_array($data['seo'])) {
                app(\App\Services\ContentSeoService::class)->sync(
                    $existing->fresh() ?? $existing,
                    $data['seo'],
                    User::query()->findOrFail($data['author_id'] ?? $existing->author_id),
                );
            }

            return $existing->fresh(['seo']) ?? $existing;
        }

        $page = Page::factory()->published()->create([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'body' => $data['body'],
            'author_id' => $data['author_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'show_in_navigation' => $data['show_in_navigation'] ?? true,
            'custom_fields' => $customFields,
        ]);

        if (isset($data['seo']) && is_array($data['seo'])) {
            app(\App\Services\ContentSeoService::class)->sync(
                $page,
                $data['seo'],
                User::query()->findOrFail($data['author_id']),
            );
        }

        return $page->fresh(['seo']) ?? $page;
    }
}
