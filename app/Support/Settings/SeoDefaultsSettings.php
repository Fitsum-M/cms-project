<?php

namespace App\Support\Settings;

use App\Enums\SettingGroup;
use App\Services\SettingsStore;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SeoDefaultsSettings
{
    public const META_TITLE_PATTERN = 'default_meta_title_pattern';

    public const META_DESCRIPTION = 'default_meta_description';

    public const OG_IMAGE_ID = 'default_og_image_id';

    public const SCHEMA_TYPE = 'default_schema_type';

    public const ROBOTS = 'default_robots';

    public function __construct(
        private readonly SettingsStore $store,
    ) {}

    public function metaTitlePattern(): string
    {
        return (string) $this->store->get(
            SettingGroup::SeoDefaults,
            self::META_TITLE_PATTERN,
            self::defaults()[self::META_TITLE_PATTERN],
        );
    }

    public function metaDescription(): string
    {
        return (string) $this->store->get(
            SettingGroup::SeoDefaults,
            self::META_DESCRIPTION,
            self::defaults()[self::META_DESCRIPTION],
        );
    }

    public function ogImageId(): ?int
    {
        $value = $this->store->get(
            SettingGroup::SeoDefaults,
            self::OG_IMAGE_ID,
            self::defaults()[self::OG_IMAGE_ID],
        );

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function schemaType(): string
    {
        return (string) $this->store->get(
            SettingGroup::SeoDefaults,
            self::SCHEMA_TYPE,
            self::defaults()[self::SCHEMA_TYPE],
        );
    }

    /**
     * @return list<string>
     */
    public function robots(): array
    {
        $value = $this->store->get(
            SettingGroup::SeoDefaults,
            self::ROBOTS,
            self::defaults()[self::ROBOTS],
        );

        if (! is_array($value)) {
            return self::defaults()[self::ROBOTS];
        }

        return array_values(array_filter($value, fn (mixed $item): bool => is_string($item) && $item !== ''));
    }

    public function robotsDirective(): string
    {
        return implode(', ', $this->robots());
    }

    /**
     * Resolve the default meta title pattern with token replacements.
     *
     * @param  array{title?: string, site_title?: string}  $tokens
     */
    public function resolveMetaTitle(array $tokens = []): string
    {
        $siteTitle = $tokens['site_title'] ?? app(GeneralSettings::class)->siteTitle();
        $title = $tokens['title'] ?? '';

        return strtr($this->metaTitlePattern(), [
            '{title}' => $title,
            '{site_title}' => $siteTitle,
        ]);
    }

    /**
     * @return array{
     *     default_meta_title_pattern: string,
     *     default_meta_description: string,
     *     default_og_image_id: int|null,
     *     default_schema_type: string,
     *     default_robots: list<string>
     * }
     */
    public function all(): array
    {
        return [
            self::META_TITLE_PATTERN => $this->metaTitlePattern(),
            self::META_DESCRIPTION => $this->metaDescription(),
            self::OG_IMAGE_ID => $this->ogImageId(),
            self::SCHEMA_TYPE => $this->schemaType(),
            self::ROBOTS => $this->robots(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        $merged = [
            ...$this->all(),
            ...$data,
        ];

        $robots = $merged[self::ROBOTS] ?? [];
        if (! is_array($robots)) {
            $robots = [];
        }

        $validRobots = array_keys(self::robotsOptions());
        $robots = array_values(array_intersect(
            array_map(fn (mixed $item): string => (string) $item, $robots),
            $validRobots,
        ));

        if ($robots === []) {
            $robots = self::defaults()[self::ROBOTS];
        }

        $schemaType = trim((string) ($merged[self::SCHEMA_TYPE] ?? self::defaults()[self::SCHEMA_TYPE]));

        if ($schemaType === 'Custom' && filled($merged['custom_schema_type'] ?? null)) {
            $schemaType = trim((string) $merged['custom_schema_type']);
        }

        if ($schemaType === '' || $schemaType === 'Custom') {
            $schemaType = self::defaults()[self::SCHEMA_TYPE];
        }

        $schemaType = mb_substr($schemaType, 0, 100);

        $this->store->putMany(SettingGroup::SeoDefaults, [
            self::META_TITLE_PATTERN => [
                'value' => mb_substr((string) $merged[self::META_TITLE_PATTERN], 0, 255),
                'type' => 'string',
            ],
            self::META_DESCRIPTION => [
                'value' => mb_substr((string) ($merged[self::META_DESCRIPTION] ?? ''), 0, 500),
                'type' => 'string',
            ],
            self::OG_IMAGE_ID => [
                'value' => $this->normalizeNullableId($merged[self::OG_IMAGE_ID] ?? null),
                'type' => 'integer',
            ],
            self::SCHEMA_TYPE => ['value' => $schemaType, 'type' => 'string'],
            self::ROBOTS => ['value' => $robots, 'type' => 'json'],
        ]);
    }

    /**
     * @return array{
     *     default_meta_title_pattern: string,
     *     default_meta_description: string,
     *     default_og_image_id: null,
     *     default_schema_type: string,
     *     default_robots: list<string>
     * }
     */
    public static function defaults(): array
    {
        return [
            self::META_TITLE_PATTERN => '{title} | {site_title}',
            self::META_DESCRIPTION => '',
            self::OG_IMAGE_ID => null,
            self::SCHEMA_TYPE => 'WebPage',
            self::ROBOTS => ['index', 'follow'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function schemaTypeOptions(): array
    {
        $types = [
            'WebPage',
            'Article',
            'BlogPosting',
            'NewsArticle',
            'AboutPage',
            'ContactPage',
            'FAQPage',
            'Custom',
        ];

        return array_combine($types, $types);
    }

    /**
     * @return array<string, string>
     */
    public static function robotsOptions(): array
    {
        return [
            'index' => 'index',
            'noindex' => 'noindex',
            'follow' => 'follow',
            'nofollow' => 'nofollow',
            'noarchive' => 'noarchive',
            'nosnippet' => 'nosnippet',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function ogImageOptions(): array
    {
        if (! self::mediaTableReady()) {
            return [];
        }

        return Media::query()
            ->where('mime_type', 'like', 'image/%')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'name', 'file_name'])
            ->mapWithKeys(fn (Media $media): array => [
                (int) $media->id => trim($media->name.' ('.$media->file_name.')'),
            ])
            ->all();
    }

    public static function mediaTableReady(): bool
    {
        try {
            return Schema::hasTable('media');
        } catch (\Throwable) {
            return false;
        }
    }

    public static function mediaImageExists(int $mediaId): bool
    {
        if (! self::mediaTableReady()) {
            return false;
        }

        return Media::query()
            ->whereKey($mediaId)
            ->where('mime_type', 'like', 'image/%')
            ->exists();
    }

    private function normalizeNullableId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
