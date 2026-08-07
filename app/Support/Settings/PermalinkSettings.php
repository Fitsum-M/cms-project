<?php

namespace App\Support\Settings;

use App\Enums\SettingGroup;
use App\Enums\SlugConflictResolution;
use App\Services\SettingsStore;
use InvalidArgumentException;

class PermalinkSettings
{
    public const POST_URL_STRUCTURE = 'post_url_structure';

    public const PAGE_URL_STRUCTURE = 'page_url_structure';

    public const AUTO_GENERATE_SLUGS = 'auto_generate_slugs';

    public const CONFLICT_RESOLUTION = 'conflict_resolution';

    public function __construct(
        private readonly SettingsStore $store,
    ) {}

    public function postUrlStructure(): string
    {
        return (string) $this->store->get(
            SettingGroup::Permalinks,
            self::POST_URL_STRUCTURE,
            self::defaults()[self::POST_URL_STRUCTURE],
        );
    }

    public function pageUrlStructure(): string
    {
        return (string) $this->store->get(
            SettingGroup::Permalinks,
            self::PAGE_URL_STRUCTURE,
            self::defaults()[self::PAGE_URL_STRUCTURE],
        );
    }

    public function autoGenerateSlugs(): bool
    {
        return (bool) $this->store->get(
            SettingGroup::Permalinks,
            self::AUTO_GENERATE_SLUGS,
            self::defaults()[self::AUTO_GENERATE_SLUGS],
        );
    }

    public function conflictResolution(): SlugConflictResolution
    {
        $value = $this->store->get(
            SettingGroup::Permalinks,
            self::CONFLICT_RESOLUTION,
            self::defaults()[self::CONFLICT_RESOLUTION],
        );

        return SlugConflictResolution::tryFrom((string) $value)
            ?? SlugConflictResolution::AppendNumber;
    }

    /**
     * @return array{
     *     post_url_structure: string,
     *     page_url_structure: string,
     *     auto_generate_slugs: bool,
     *     conflict_resolution: string
     * }
     */
    public function all(): array
    {
        return [
            self::POST_URL_STRUCTURE => $this->postUrlStructure(),
            self::PAGE_URL_STRUCTURE => $this->pageUrlStructure(),
            self::AUTO_GENERATE_SLUGS => $this->autoGenerateSlugs(),
            self::CONFLICT_RESOLUTION => $this->conflictResolution()->value,
        ];
    }

    /**
     * @param  array{
     *     post_url_structure?: string,
     *     page_url_structure?: string,
     *     auto_generate_slugs?: bool|int|string,
     *     conflict_resolution?: string
     * }  $data
     */
    public function save(array $data): void
    {
        $merged = [
            ...$this->all(),
            ...$data,
        ];

        $postStructure = self::normalizeStructure((string) $merged[self::POST_URL_STRUCTURE]);
        $pageStructure = self::normalizeStructure((string) $merged[self::PAGE_URL_STRUCTURE]);

        self::assertContainsSlugToken($postStructure, 'Post URL structure');
        self::assertContainsSlugToken($pageStructure, 'Page URL structure');

        $conflict = SlugConflictResolution::tryFrom((string) $merged[self::CONFLICT_RESOLUTION])
            ?? SlugConflictResolution::AppendNumber;

        $this->store->putMany(SettingGroup::Permalinks, [
            self::POST_URL_STRUCTURE => ['value' => $postStructure, 'type' => 'string'],
            self::PAGE_URL_STRUCTURE => ['value' => $pageStructure, 'type' => 'string'],
            self::AUTO_GENERATE_SLUGS => [
                'value' => filter_var($merged[self::AUTO_GENERATE_SLUGS], FILTER_VALIDATE_BOOLEAN),
                'type' => 'boolean',
            ],
            self::CONFLICT_RESOLUTION => ['value' => $conflict->value, 'type' => 'string'],
        ]);
    }

    /**
     * @return array{
     *     post_url_structure: string,
     *     page_url_structure: string,
     *     auto_generate_slugs: bool,
     *     conflict_resolution: string
     * }
     */
    public static function defaults(): array
    {
        return [
            self::POST_URL_STRUCTURE => '/{post-type}/{slug}/',
            self::PAGE_URL_STRUCTURE => '/{parent-slug}/{slug}/',
            self::AUTO_GENERATE_SLUGS => true,
            self::CONFLICT_RESOLUTION => SlugConflictResolution::AppendNumber->value,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function postUrlStructureOptions(): array
    {
        return [
            '/{post-type}/{slug}/' => '/{post-type}/{slug}/',
            '/{year}/{month}/{slug}/' => '/{year}/{month}/{slug}/',
            '/{year}/{month}/{day}/{slug}/' => '/{year}/{month}/{day}/{slug}/',
            '/{slug}/' => '/{slug}/',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function pageUrlStructureOptions(): array
    {
        return [
            '/{parent-slug}/{slug}/' => '/{parent-slug}/{slug}/ (nested under parent)',
            '/{slug}/' => '/{slug}/ (flat)',
        ];
    }

    /**
     * Build a post path from the configured structure and token values.
     *
     * @param  array{
     *     slug: string,
     *     post_type?: string,
     *     year?: string|int,
     *     month?: string|int,
     *     day?: string|int
     * }  $tokens
     */
    public function buildPostPath(array $tokens): string
    {
        return $this->replaceTokens($this->postUrlStructure(), [
            'post-type' => $tokens['post_type'] ?? 'post',
            'slug' => $tokens['slug'],
            'year' => (string) ($tokens['year'] ?? ''),
            'month' => (string) ($tokens['month'] ?? ''),
            'day' => (string) ($tokens['day'] ?? ''),
        ]);
    }

    /**
     * Build a page path from the configured structure and token values.
     *
     * @param  array{slug: string, parent_slug?: string|null}  $tokens
     */
    public function buildPagePath(array $tokens): string
    {
        $parentSlug = $tokens['parent_slug'] ?? null;

        $path = $this->replaceTokens($this->pageUrlStructure(), [
            'parent-slug' => (string) ($parentSlug ?? ''),
            'slug' => $tokens['slug'],
        ]);

        // Collapse empty parent segments: "//about/" → "/about/"
        return (string) preg_replace('#/+#', '/', $path);
    }

    public static function normalizeStructure(string $structure): string
    {
        $structure = trim($structure);

        if ($structure === '') {
            throw new InvalidArgumentException('URL structure cannot be empty.');
        }

        if (! str_starts_with($structure, '/')) {
            $structure = '/'.$structure;
        }

        if (! str_ends_with($structure, '/')) {
            $structure .= '/';
        }

        return $structure;
    }

    public static function assertContainsSlugToken(string $structure, string $label = 'URL structure'): void
    {
        if (! str_contains($structure, '{slug}')) {
            throw new InvalidArgumentException("{$label} must include the {slug} token.");
        }
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private function replaceTokens(string $structure, array $tokens): string
    {
        $replacements = [];

        foreach ($tokens as $key => $value) {
            $replacements['{'.$key.'}'] = $value;
        }

        return strtr($structure, $replacements);
    }
}
