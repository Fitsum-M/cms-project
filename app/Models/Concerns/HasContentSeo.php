<?php

namespace App\Models\Concerns;

use App\Models\SeoMetadata;
use App\Services\ContentSeoService;
use App\Services\ContentUrlGenerator;
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
        if ($this instanceof \App\Models\Page) {
            return app(ContentUrlGenerator::class)->pagePath($this);
        }

        if ($this instanceof \App\Models\Post) {
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

        return new SEOData(
            title: $resolved->metaTitle !== '' ? $resolved->metaTitle : null,
            description: $resolved->metaDescription !== '' ? $resolved->metaDescription : null,
            image: $resolved->ogImageUrl,
            url: $resolved->publicUrl,
            enableTitleSuffix: false,
            robots: $resolved->robotsDirective() !== '' ? $resolved->robotsDirective() : null,
            canonical_url: $resolved->canonicalUrl !== '' ? $resolved->canonicalUrl : null,
            openGraphTitle: $resolved->ogTitle !== '' ? $resolved->ogTitle : null,
            type: $this->openGraphTypeForSchema($resolved->schemaType),
        );
    }

    private function openGraphTypeForSchema(string $schemaType): string
    {
        return match ($schemaType) {
            'Article', 'BlogPosting', 'NewsArticle' => 'article',
            default => 'website',
        };
    }
}
