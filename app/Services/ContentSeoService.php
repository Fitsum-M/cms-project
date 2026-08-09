<?php

namespace App\Services;

use App\Contracts\HasSeoMetadata;
use App\Enums\Permission;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Post;
use App\Models\SeoMetadata;
use App\Models\User;
use App\Support\Media\MediaImageOptions;
use App\Support\Seo\ResolvedSeoMetadata;
use App\Support\Settings\SeoDefaultsSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Content-level SEO sync + inheritance (SRS 12.5.3).
 *
 * Priority: content value → post-type default (schema) → SEO Defaults → dynamic fallback.
 */
class ContentSeoService
{
    public function __construct(
        private readonly SeoDefaultsSettings $defaults,
        private readonly ContentUrlGenerator $urls,
    ) {}

    /**
     * @return array{
     *     meta_title: string|null,
     *     meta_description: string|null,
     *     focus_keyword: string|null,
     *     canonical_url: string|null,
     *     robots: list<string>,
     *     og_title: string|null,
     *     og_description: string|null,
     *     og_image_id: int|null,
     *     schema_type: string|null,
     *     custom_schema_type: string|null
     * }
     */
    public function formState(Model&HasSeoMetadata $content): array
    {
        $seo = $content->seoRecord();

        if ($seo === null) {
            return $this->emptyFormState();
        }

        $schemaType = $seo->schema_type;
        $known = array_keys(SeoDefaultsSettings::schemaTypeOptions());
        $isCustom = filled($schemaType) && ! in_array($schemaType, $known, true);

        return [
            'meta_title' => $seo->title,
            'meta_description' => $seo->description,
            'focus_keyword' => $seo->focus_keyword,
            'canonical_url' => $seo->canonical_url,
            'robots' => $seo->robotsList(),
            'og_title' => $seo->og_title,
            'og_description' => $seo->og_description,
            'og_image_id' => $seo->og_image_id,
            'schema_type' => $isCustom ? 'Custom' : $schemaType,
            'custom_schema_type' => $isCustom ? $schemaType : null,
        ];
    }

    /**
     * Persist SEO fields. Empty strings become null (inheritance).
     * No-ops when the actor lacks SeoConfigureContent.
     *
     * @param  array<string, mixed>|null  $seoData
     */
    public function sync(Model&HasSeoMetadata $content, ?array $seoData, User $actor): void
    {
        if ($seoData === null) {
            return;
        }

        if (! $actor->can(Permission::SeoConfigureContent->value)) {
            return;
        }

        if (! Schema::hasTable('seo')) {
            return;
        }

        $normalized = $this->normalize($seoData);

        /** @var SeoMetadata $seo */
        $seo = $content->seo()->firstOrNew([]);
        $seo->fill([
            'title' => $normalized['meta_title'],
            'description' => $normalized['meta_description'],
            'focus_keyword' => $normalized['focus_keyword'],
            'canonical_url' => $normalized['canonical_url'],
            'robots' => $normalized['robots'],
            'og_title' => $normalized['og_title'],
            'og_description' => $normalized['og_description'],
            'og_image_id' => $normalized['og_image_id'],
            'schema_type' => $normalized['schema_type'],
            'image' => null,
            'author' => null,
        ]);
        $seo->save();
    }

    /**
     * Copy SEO metadata when duplicating content (SRS 12.2.15).
     */
    public function copy(Model&HasSeoMetadata $source, Model&HasSeoMetadata $target): void
    {
        $sourceSeo = $source->seoRecord();

        if ($sourceSeo === null) {
            return;
        }

        /** @var SeoMetadata $seo */
        $seo = $target->seo()->firstOrNew([]);
        $seo->fill([
            'title' => $sourceSeo->title,
            'description' => $sourceSeo->description,
            'focus_keyword' => $sourceSeo->focus_keyword,
            'canonical_url' => $sourceSeo->canonical_url,
            'robots' => $sourceSeo->getAttributes()['robots'] ?? null,
            'og_title' => $sourceSeo->og_title,
            'og_description' => $sourceSeo->og_description,
            'og_image_id' => $sourceSeo->og_image_id,
            'schema_type' => $sourceSeo->schema_type,
            'image' => null,
            'author' => null,
        ]);
        $seo->save();
    }

    public function resolve(Model&HasSeoMetadata $content): ResolvedSeoMetadata
    {
        $seo = $content->seoRecord();
        $title = $content->contentTitle();
        $excerpt = $content->contentExcerptForSeo();
        $publicUrl = $this->absoluteUrl($content->contentPublicPath());

        [$metaTitle, $metaTitleSource] = $this->resolveMetaTitle($seo, $title);
        [$metaDescription, $metaDescriptionSource] = $this->resolveMetaDescription($seo, $excerpt);

        $focusKeyword = $seo !== null && filled($seo->focus_keyword)
            ? (string) $seo->focus_keyword
            : null;

        [$canonicalUrl, $canonicalSource] = $this->resolveCanonical($seo, $publicUrl);
        [$robots, $robotsSource] = $this->resolveRobots($seo);
        [$ogTitle, $ogTitleSource] = $this->resolveOgTitle($seo, $metaTitle, $title);
        [$ogDescription, $ogDescriptionSource] = $this->resolveOgDescription($seo, $metaDescription, $excerpt);
        [$ogImageId, $ogImageUrl, $ogImageSource] = $this->resolveOgImage($seo, $content);

        $postTypeKey = $content instanceof Post ? (string) ($content->post_type ?: 'post') : null;
        [$schemaType, $schemaSource] = $this->resolveSchemaType($seo, $postTypeKey);

        return new ResolvedSeoMetadata(
            metaTitle: $metaTitle,
            metaDescription: $metaDescription,
            focusKeyword: $focusKeyword,
            canonicalUrl: $canonicalUrl,
            robots: $robots,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImageId: $ogImageId,
            ogImageUrl: $ogImageUrl,
            schemaType: $schemaType,
            publicUrl: $publicUrl,
            sources: [
                'meta_title' => $metaTitleSource,
                'meta_description' => $metaDescriptionSource,
                'canonical_url' => $canonicalSource,
                'robots' => $robotsSource,
                'og_title' => $ogTitleSource,
                'og_description' => $ogDescriptionSource,
                'og_image' => $ogImageSource,
                'schema_type' => $schemaSource,
            ],
        );
    }

    /**
     * Live preview from unsaved form state + content fields.
     *
     * @param  array<string, mixed>  $seoForm
     * @param  array{title?: string|null, excerpt?: string|null, body?: string|null, slug?: string|null, featured_image_id?: int|null, parent_id?: int|null}  $contentForm
     */
    public function previewFromForm(string $contentType, array $seoForm, array $contentForm, ?Model $record = null): ResolvedSeoMetadata
    {
        $title = trim((string) ($contentForm['title'] ?? $record?->title ?? ''));
        $excerpt = $this->previewExcerpt($contentType, $contentForm, $record);
        $path = $this->previewPath($contentType, $contentForm, $record);
        $publicUrl = $this->absoluteUrl($path);
        $featuredImageId = isset($contentForm['featured_image_id']) && $contentForm['featured_image_id'] !== ''
            ? (int) $contentForm['featured_image_id']
            : ($record instanceof Post ? $record->featured_image_id : null);

        $metaTitleValue = $this->blankToNull($seoForm['meta_title'] ?? null);
        $metaDescriptionValue = $this->blankToNull($seoForm['meta_description'] ?? null);
        $focusKeyword = $this->blankToNull($seoForm['focus_keyword'] ?? null);
        $canonicalValue = $this->blankToNull($seoForm['canonical_url'] ?? null);
        $ogTitleValue = $this->blankToNull($seoForm['og_title'] ?? null);
        $ogDescriptionValue = $this->blankToNull($seoForm['og_description'] ?? null);

        $robotsRaw = $seoForm['robots'] ?? [];
        if (! is_array($robotsRaw)) {
            $robotsRaw = [];
        }
        $robotsString = $robotsRaw === []
            ? null
            : implode(', ', array_map(static fn (mixed $item): string => (string) $item, $robotsRaw));

        $ogImageRaw = $seoForm['og_image_id'] ?? null;
        $ogImageValue = ($ogImageRaw === null || $ogImageRaw === '') ? null : (int) $ogImageRaw;

        $schemaRaw = $this->blankToNull($seoForm['schema_type'] ?? null);
        if ($schemaRaw === 'Custom') {
            $schemaRaw = $this->blankToNull($seoForm['custom_schema_type'] ?? null);
        }

        [$metaTitle] = $this->resolveMetaTitleFromValues($metaTitleValue, $title);
        [$metaDescription] = $this->resolveMetaDescriptionFromValues($metaDescriptionValue, $excerpt);
        [$canonicalUrl] = $this->resolveCanonicalFromValues($canonicalValue, $publicUrl);
        [$robots] = $this->resolveRobotsFromValues($robotsString);
        [$ogTitle] = $this->resolveOgTitleFromValues($ogTitleValue, $metaTitle, $title);
        [$ogDescription] = $this->resolveOgDescriptionFromValues($ogDescriptionValue, $metaDescription, $excerpt);
        [$ogImageId, $ogImageUrl] = $this->resolveOgImageFromValues($ogImageValue, $featuredImageId);
        [$schemaType] = $this->resolveSchemaTypeFromValues(
            $schemaRaw,
            is_string($contentForm['post_type'] ?? null) ? (string) $contentForm['post_type'] : null,
        );

        return new ResolvedSeoMetadata(
            metaTitle: $metaTitle,
            metaDescription: $metaDescription,
            focusKeyword: $focusKeyword,
            canonicalUrl: $canonicalUrl,
            robots: $robots,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImageId: $ogImageId,
            ogImageUrl: $ogImageUrl,
            schemaType: $schemaType,
            publicUrl: $publicUrl,
            sources: [
                'meta_title' => 'preview',
                'meta_description' => 'preview',
                'canonical_url' => 'preview',
                'robots' => 'preview',
                'og_title' => 'preview',
                'og_description' => 'preview',
                'og_image' => 'preview',
                'schema_type' => 'preview',
            ],
        );
    }

    public function absoluteUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     meta_title: string|null,
     *     meta_description: string|null,
     *     focus_keyword: string|null,
     *     canonical_url: string|null,
     *     robots: string|null,
     *     og_title: string|null,
     *     og_description: string|null,
     *     og_image_id: int|null,
     *     schema_type: string|null
     * }
     */
    public function normalize(array $data): array
    {
        $metaTitle = $this->blankToNull($data['meta_title'] ?? null);
        if ($metaTitle !== null) {
            $metaTitle = mb_substr($metaTitle, 0, 255);
        }

        $metaDescription = $this->blankToNull($data['meta_description'] ?? null);
        if ($metaDescription !== null) {
            $metaDescription = mb_substr($metaDescription, 0, 500);
        }

        $focusKeyword = $this->blankToNull($data['focus_keyword'] ?? null);
        if ($focusKeyword !== null) {
            $focusKeyword = mb_substr($focusKeyword, 0, 100);
        }

        $canonical = $this->blankToNull($data['canonical_url'] ?? null);
        if ($canonical !== null) {
            if (! filter_var($canonical, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $canonical)) {
                throw ValidationException::withMessages([
                    'seo.canonical_url' => 'Canonical URL must be a valid absolute URL (http or https).',
                ]);
            }
            $canonical = mb_substr($canonical, 0, 255);
        }

        $robots = $data['robots'] ?? [];
        if (! is_array($robots)) {
            $robots = [];
        }
        $validRobots = array_keys(SeoDefaultsSettings::robotsOptions());
        $robots = array_values(array_intersect(
            array_map(static fn (mixed $item): string => (string) $item, $robots),
            $validRobots,
        ));
        $robotsString = $robots === [] ? null : implode(', ', $robots);

        $ogTitle = $this->blankToNull($data['og_title'] ?? null);
        if ($ogTitle !== null) {
            $ogTitle = mb_substr($ogTitle, 0, 255);
        }

        $ogDescription = $this->blankToNull($data['og_description'] ?? null);
        if ($ogDescription !== null) {
            $ogDescription = mb_substr($ogDescription, 0, 500);
        }

        $ogImageId = $this->resolveOgImageId($data['og_image_id'] ?? null);

        $schemaType = $this->blankToNull($data['schema_type'] ?? null);
        if ($schemaType === 'Custom') {
            $custom = $this->blankToNull($data['custom_schema_type'] ?? null);
            $schemaType = $custom !== null ? mb_substr($custom, 0, 100) : null;
        } elseif ($schemaType !== null) {
            $known = array_keys(SeoDefaultsSettings::schemaTypeOptions());
            if (! in_array($schemaType, $known, true) || $schemaType === 'Custom') {
                $schemaType = null;
            }
        }

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'focus_keyword' => $focusKeyword,
            'canonical_url' => $canonical,
            'robots' => $robotsString,
            'og_title' => $ogTitle,
            'og_description' => $ogDescription,
            'og_image_id' => $ogImageId,
            'schema_type' => $schemaType,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveMetaTitle(?SeoMetadata $seo, string $title): array
    {
        return $this->resolveMetaTitleFromValues(
            $seo !== null && filled($seo->title) ? (string) $seo->title : null,
            $title,
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveMetaTitleFromValues(?string $contentValue, string $title): array
    {
        if (filled($contentValue)) {
            return [(string) $contentValue, 'content'];
        }

        $fromDefaults = $this->defaults->resolveMetaTitle(['title' => $title]);
        if (filled($fromDefaults)) {
            return [$fromDefaults, 'defaults'];
        }

        return [$title, 'dynamic'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveMetaDescription(?SeoMetadata $seo, string $excerpt): array
    {
        return $this->resolveMetaDescriptionFromValues(
            $seo !== null && filled($seo->description) ? (string) $seo->description : null,
            $excerpt,
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveMetaDescriptionFromValues(?string $contentValue, string $excerpt): array
    {
        if (filled($contentValue)) {
            return [(string) $contentValue, 'content'];
        }

        $defaultDescription = $this->defaults->metaDescription();
        if (filled($defaultDescription)) {
            return [$defaultDescription, 'defaults'];
        }

        return [$excerpt, 'dynamic'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveCanonical(?SeoMetadata $seo, string $publicUrl): array
    {
        return $this->resolveCanonicalFromValues(
            $seo !== null && filled($seo->canonical_url) ? (string) $seo->canonical_url : null,
            $publicUrl,
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveCanonicalFromValues(?string $contentValue, string $publicUrl): array
    {
        if (filled($contentValue)) {
            return [(string) $contentValue, 'content'];
        }

        return [$publicUrl, 'dynamic'];
    }

    /**
     * @return array{0: list<string>, 1: string}
     */
    private function resolveRobots(?SeoMetadata $seo): array
    {
        $list = $seo?->robotsList() ?? [];

        return $this->resolveRobotsFromValues($list === [] ? null : implode(', ', $list));
    }

    /**
     * @return array{0: list<string>, 1: string}
     */
    private function resolveRobotsFromValues(?string $contentValue): array
    {
        if (filled($contentValue)) {
            $list = array_values(array_filter(array_map(
                static fn (string $part): string => trim($part),
                explode(',', $contentValue),
            )));

            if ($list !== []) {
                return [$list, 'content'];
            }
        }

        $defaults = $this->defaults->robots();

        return [$defaults !== [] ? $defaults : ['index', 'follow'], 'defaults'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveOgTitle(?SeoMetadata $seo, string $metaTitle, string $title): array
    {
        return $this->resolveOgTitleFromValues(
            $seo !== null && filled($seo->og_title) ? (string) $seo->og_title : null,
            $metaTitle,
            $title,
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveOgTitleFromValues(?string $contentValue, string $metaTitle, string $title): array
    {
        if (filled($contentValue)) {
            return [(string) $contentValue, 'content'];
        }

        if (filled($metaTitle)) {
            return [$metaTitle, 'meta_title'];
        }

        return [$title, 'dynamic'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveOgDescription(?SeoMetadata $seo, string $metaDescription, string $excerpt): array
    {
        return $this->resolveOgDescriptionFromValues(
            $seo !== null && filled($seo->og_description) ? (string) $seo->og_description : null,
            $metaDescription,
            $excerpt,
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveOgDescriptionFromValues(?string $contentValue, string $metaDescription, string $excerpt): array
    {
        if (filled($contentValue)) {
            return [(string) $contentValue, 'content'];
        }

        if (filled($metaDescription)) {
            return [$metaDescription, 'meta_description'];
        }

        return [$excerpt, 'dynamic'];
    }

    /**
     * @return array{0: int|null, 1: string|null, 2: string}
     */
    private function resolveOgImage(?SeoMetadata $seo, Model&HasSeoMetadata $content): array
    {
        return $this->resolveOgImageFromValues(
            $seo?->og_image_id,
            $content->featuredImageIdForSeo(),
        );
    }

    /**
     * @return array{0: int|null, 1: string|null, 2: string}
     */
    private function resolveOgImageFromValues(?int $ogImageId, ?int $featuredImageId): array
    {
        if ($ogImageId !== null) {
            $url = $this->mediaUrl($ogImageId);
            if ($url !== null) {
                return [$ogImageId, $url, 'content'];
            }
        }

        if ($featuredImageId !== null) {
            $url = $this->mediaUrl($featuredImageId);
            if ($url !== null) {
                return [$featuredImageId, $url, 'featured'];
            }
        }

        $defaultId = $this->defaults->ogImageId();
        if ($defaultId !== null) {
            $url = $this->mediaUrl($defaultId);
            if ($url !== null) {
                return [$defaultId, $url, 'defaults'];
            }
        }

        return [null, null, 'none'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveSchemaType(?SeoMetadata $seo, ?string $postTypeKey = null): array
    {
        return $this->resolveSchemaTypeFromValues(
            $seo !== null && filled($seo->schema_type) ? (string) $seo->schema_type : null,
            $postTypeKey,
        );
    }

    /**
     * Priority: content → post-type default → SEO Defaults (SRS 12.4.7 / 12.5.3).
     *
     * @return array{0: string, 1: string}
     */
    private function resolveSchemaTypeFromValues(?string $contentValue, ?string $postTypeKey = null): array
    {
        if (filled($contentValue)) {
            return [(string) $contentValue, 'content'];
        }

        if (filled($postTypeKey)) {
            $typeDefault = \App\Support\PostTypeRegistry::defaultSchemaType($postTypeKey);
            if (filled($typeDefault)) {
                return [$typeDefault, 'post_type'];
            }
        }

        return [$this->defaults->schemaType(), 'defaults'];
    }

    private function mediaUrl(int $mediaAssetId): ?string
    {
        if (! SeoDefaultsSettings::mediaAssetsTableReady()) {
            return null;
        }

        $asset = MediaAsset::query()->find($mediaAssetId);

        if ($asset === null || ! $asset->isImage()) {
            return null;
        }

        return $asset->originalUrl() ?? $asset->previewUrl();
    }

    private function resolveOgImageId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return MediaImageOptions::assertAssignableImage((int) $value);
    }

    private function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array{
     *     meta_title: null,
     *     meta_description: null,
     *     focus_keyword: null,
     *     canonical_url: null,
     *     robots: list<string>,
     *     og_title: null,
     *     og_description: null,
     *     og_image_id: null,
     *     schema_type: null,
     *     custom_schema_type: null
     * }
     */
    private function emptyFormState(): array
    {
        return [
            'meta_title' => null,
            'meta_description' => null,
            'focus_keyword' => null,
            'canonical_url' => null,
            'robots' => [],
            'og_title' => null,
            'og_description' => null,
            'og_image_id' => null,
            'schema_type' => null,
            'custom_schema_type' => null,
        ];
    }

    /**
     * @param  array{title?: string|null, excerpt?: string|null, body?: string|null, slug?: string|null, featured_image_id?: int|null, parent_id?: int|null}  $contentForm
     */
    private function previewExcerpt(string $contentType, array $contentForm, ?Model $record): string
    {
        if ($contentType === 'post') {
            if (array_key_exists('excerpt', $contentForm) && filled($contentForm['excerpt'])) {
                return mb_substr(trim((string) $contentForm['excerpt']), 0, 160);
            }

            if ($record instanceof Post) {
                return $record->resolvedExcerpt();
            }

            $body = (string) ($contentForm['body'] ?? '');

            return mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($body)) ?? ''), 0, 160);
        }

        if ($record instanceof Page) {
            return $record->contentExcerptForSeo();
        }

        $body = (string) ($contentForm['body'] ?? $record?->body ?? '');

        return mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($body)) ?? ''), 0, 160);
    }

    /**
     * @param  array{title?: string|null, excerpt?: string|null, body?: string|null, slug?: string|null, featured_image_id?: int|null, parent_id?: int|null}  $contentForm
     */
    private function previewPath(string $contentType, array $contentForm, ?Model $record): string
    {
        $slug = trim((string) ($contentForm['slug'] ?? $record?->slug ?? 'preview'));

        if ($slug === '') {
            $slug = 'preview';
        }

        if ($contentType === 'page') {
            if ($record instanceof Page) {
                $clone = $record->replicate();
                $clone->slug = $slug;
                if (array_key_exists('parent_id', $contentForm)) {
                    $clone->parent_id = $contentForm['parent_id'] !== null && $contentForm['parent_id'] !== ''
                        ? (int) $contentForm['parent_id']
                        : null;
                }

                return $this->urls->pagePath($clone);
            }

            return $this->urls->pagePath(new Page([
                'slug' => $slug,
                'parent_id' => isset($contentForm['parent_id']) && $contentForm['parent_id'] !== ''
                    ? (int) $contentForm['parent_id']
                    : null,
            ]));
        }

        if ($record instanceof Post) {
            $clone = $record->replicate();
            $clone->slug = $slug;

            return $this->urls->postPath($clone);
        }

        return $this->urls->postPath(new Post([
            'slug' => $slug,
            'post_type' => 'post',
            'published_at' => now(),
        ]));
    }
}
