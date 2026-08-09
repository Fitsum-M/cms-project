<?php

namespace App\Support\Seo;

use App\Contracts\HasSeoMetadata;
use App\Services\ContentSeoService;
use App\Support\Settings\SeoDefaultsSettings;
use Illuminate\Database\Eloquent\Model;
use RalphJSmit\Laravel\SEO\Schema\ArticleSchema;
use RalphJSmit\Laravel\SEO\SchemaCollection;
use RalphJSmit\Laravel\SEO\Support\SEOData;

/**
 * Maps resolved CMS schema types to ralphjsmit/laravel-seo JSON-LD output (SRS §19.10, GAP.SEO.02).
 */
class JsonLdSchemaBuilder
{
    public function __construct(
        private readonly ContentSeoService $seoService,
    ) {}

    public function build(Model&HasSeoMetadata $content, SEOData $seoData): ?SchemaCollection
    {
        $resolved = $this->seoService->resolve($content);
        $schemaType = trim($resolved->schemaType);

        if ($schemaType === '') {
            return null;
        }

        $collection = SchemaCollection::initialize();

        if ($this->isArticleSchemaType($schemaType)) {
            $collection->addArticle(function (ArticleSchema $schema) use ($schemaType): ArticleSchema {
                $schema->type = $schemaType;

                return $schema;
            });

            return $collection;
        }

        if ($schemaType === 'FAQPage') {
            $collection->addFaqPage();

            return $collection;
        }

        $collection->push(function (SEOData $data) use ($schemaType, $resolved): array {
            return $this->webPageMarkup($schemaType, $data, $resolved);
        });

        return $collection;
    }

    /**
     * @return array<string, mixed>
     */
    public function webPageMarkup(string $schemaType, SEOData $data, ResolvedSeoMetadata $resolved): array
    {
        $markup = [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            'name' => $data->title ?? $resolved->metaTitle,
            'description' => $data->description ?? $resolved->metaDescription,
            'url' => $data->url ?? $resolved->publicUrl,
        ];

        if (filled($data->image)) {
            $markup['image'] = $data->image;
        }

        return array_filter(
            $markup,
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }

    public function isArticleSchemaType(string $schemaType): bool
    {
        return in_array($schemaType, ['Article', 'BlogPosting', 'NewsArticle'], true);
    }

    /**
     * @return list<string>
     */
    public static function supportedSchemaTypes(): array
    {
        return array_values(array_filter(
            array_keys(SeoDefaultsSettings::schemaTypeOptions()),
            static fn (string $type): bool => $type !== 'Custom',
        ));
    }
}
