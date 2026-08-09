<?php

namespace App\Models\Concerns;

use App\Models\Page;
use App\Models\Post;
use App\Models\SeoMetadata;
use App\Services\ContentSeoService;
use App\Services\ContentUrlGenerator;
use App\Support\Seo\JsonLdSchemaBuilder;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;

trait HasContentSeo
{
    use HasSEO;

    protected static function bootHasContentSeo(): void
    {
        static::forceDeleting(function (self $model): void {
            $model->seo()->delete();
        });
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(config('seo.model'), 'model')->withDefault();
    }

    public function seoRecord(): ?SeoMetadata
    {
        $seo = $this->seo;

        if (! $seo instanceof SeoMetadata || ! $seo->exists) {
            return null;
        }

        return $seo;
    }

    public function contentExcerptForSeo(): string
    {
        if (method_exists($this, 'resolvedExcerpt')) {
            return (string) $this->resolvedExcerpt();
        }

        $body = (string) ($this->body ?? '');

        if ($body === '') {
            return '';
        }

        return mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($body)) ?? ''), 0, 160);
    }

    public function contentPublicPath(): string
    {
        if ($this instanceof Page) {
            return app(ContentUrlGenerator::class)->pagePath($this);
        }

        if ($this instanceof Post) {
            return app(ContentUrlGenerator::class)->postPath($this);
        }

        return '/';
    }

    public function featuredImageIdForSeo(): ?int
    {
        if (! property_exists($this, 'featured_image_id') && ! isset($this->featured_image_id)) {
            return null;
        }

        $id = $this->featured_image_id ?? null;

        return $id === null ? null : (int) $id;
    }

    public function getDynamicSEOData(): SEOData
    {
        $resolved = app(ContentSeoService::class)->resolve($this);

        $body = property_exists($this, 'body') ? (string) ($this->body ?? '') : '';
        $articleBody = $body !== '' ? trim(preg_replace('/\s+/u', ' ', strip_tags($body)) ?? '') : null;

        $seoData = new SEOData(
            title: $resolved->metaTitle !== '' ? $resolved->metaTitle : null,
            description: $resolved->metaDescription !== '' ? $resolved->metaDescription : null,
            author: $this->author?->name,
            image: $resolved->ogImageUrl,
            url: $resolved->publicUrl,
            enableTitleSuffix: false,
            published_time: $this->published_at ?? $this->created_at,
            modified_time: $this->updated_at,
            articleBody: filled($articleBody) ? $articleBody : null,
            robots: $resolved->robotsDirective() !== '' ? $resolved->robotsDirective() : null,
            canonical_url: $resolved->canonicalUrl !== '' ? $resolved->canonicalUrl : null,
            openGraphTitle: $resolved->ogTitle !== '' ? $resolved->ogTitle : null,
            type: $this->openGraphTypeForSchema($resolved->schemaType),
        );

        $schema = app(JsonLdSchemaBuilder::class)->build($this, $seoData);

        if ($schema !== null) {
            $seoData->schema = $schema;
        }

        return $seoData;
    }

    private function openGraphTypeForSchema(string $schemaType): string
    {
        return match ($schemaType) {
            'Article', 'BlogPosting', 'NewsArticle' => 'article',
            default => 'website',
        };
    }
}
