<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\ContentSeoService;
use App\Services\PageService;
use App\Services\PostService;
use App\Support\Seo\JsonLdSchemaBuilder;
use App\Support\Settings\SeoDefaultsSettings;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SeoDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RalphJSmit\Laravel\SEO\TagManager;
use Tests\TestCase;

class SeoJsonLdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        $this->seed(SeoDefaultsSeeder::class);
    }

    public function test_post_renders_valid_article_json_ld_for_configured_schema_type(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $post = app(PostService::class)->create([
            'title' => 'JSON-LD Article Post',
            'body' => '<p>Article body for structured data.</p>',
            'status' => ContentStatus::Published->value,
            'published_at' => now(),
            'seo' => [
                'meta_title' => 'Article Meta Title',
                'meta_description' => 'Article meta description.',
                'schema_type' => 'Article',
            ],
        ], $admin);

        $json = $this->extractJsonLd((string) (new TagManager)->for($post)->render());

        $this->assertIsArray($json);
        $this->assertSame('https://schema.org', $json['@context']);
        $this->assertSame('Article', $json['@type']);
        $this->assertSame('Article Meta Title', $json['headline']);
        $this->assertSame('Article meta description.', $json['description']);
        $this->assertArrayHasKey('datePublished', $json);
        $this->assertArrayHasKey('author', $json);
    }

    public function test_news_article_schema_type_is_emitted_in_json_ld(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $post = app(PostService::class)->create([
            'title' => 'Breaking News',
            'seo' => ['schema_type' => 'NewsArticle'],
        ], $admin);

        $json = $this->extractJsonLd((string) (new TagManager)->for($post)->render());

        $this->assertSame('NewsArticle', $json['@type']);
    }

    public function test_page_renders_web_page_json_ld_from_inherited_defaults(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        app(SeoDefaultsSettings::class)->save([
            ...app(SeoDefaultsSettings::class)->all(),
            SeoDefaultsSettings::SCHEMA_TYPE => 'WebPage',
        ]);

        $page = app(PageService::class)->create([
            'title' => 'About Us',
            'body' => '<p>Company overview.</p>',
        ], $admin);

        $json = $this->extractJsonLd((string) (new TagManager)->for($page)->render());

        $this->assertSame('WebPage', $json['@type']);
        $this->assertStringContainsString('About Us', (string) ($json['name'] ?? ''));
    }

    public function test_contact_page_schema_type_renders_matching_json_ld_type(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $page = app(PageService::class)->create([
            'title' => 'Contact',
            'seo' => ['schema_type' => 'ContactPage'],
        ], $admin);

        $json = $this->extractJsonLd((string) (new TagManager)->for($page)->render());

        $this->assertSame('ContactPage', $json['@type']);
        $this->assertStringContainsString('Contact', (string) ($json['name'] ?? ''));
    }

    public function test_custom_schema_type_renders_as_json_ld_type(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $post = app(PostService::class)->create([
            'title' => 'Product Landing',
            'seo' => [
                'schema_type' => 'Custom',
                'custom_schema_type' => 'Product',
            ],
        ], $admin);

        $resolved = app(ContentSeoService::class)->resolve($post);
        $this->assertSame('Product', $resolved->schemaType);

        $json = $this->extractJsonLd((string) (new TagManager)->for($post)->render());

        $this->assertSame('Product', $json['@type']);
    }

    public function test_json_ld_schema_builder_maps_all_supported_types(): void
    {
        $builder = app(JsonLdSchemaBuilder::class);

        $this->assertTrue($builder->isArticleSchemaType('Article'));
        $this->assertTrue($builder->isArticleSchemaType('BlogPosting'));
        $this->assertTrue($builder->isArticleSchemaType('NewsArticle'));
        $this->assertFalse($builder->isArticleSchemaType('WebPage'));

        $this->assertContains('FAQPage', JsonLdSchemaBuilder::supportedSchemaTypes());
        $this->assertContains('AboutPage', JsonLdSchemaBuilder::supportedSchemaTypes());
    }

    /**
     * @return array<string, mixed>
     */
    private function extractJsonLd(string $html): array
    {
        $this->assertStringContainsString('application/ld+json', $html);

        preg_match('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $matches);
        $this->assertNotEmpty($matches[1] ?? null, 'Expected JSON-LD script tag in SEO output.');

        $decoded = json_decode(html_entity_decode(trim($matches[1])), true);

        $this->assertIsArray($decoded, 'JSON-LD payload must be valid JSON.');

        return $decoded;
    }

    private function makeUser(UserRole $role): User
    {
        foreach (Permission::cases() as $permission) {
            \Spatie\Permission\Models\Permission::findOrCreate($permission->value, 'web');
        }

        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'activated_at' => now(),
        ]);

        $user->assignSingleRole($role);

        return $user->fresh();
    }
}
