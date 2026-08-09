<?php

namespace App\Support\Seo;

/**
 * Fully resolved SEO values after inheritance (content → defaults → dynamic fallback).
 */
final class ResolvedSeoMetadata
{
    /**
     * @param  list<string>  $robots
     * @param  array{
     *     meta_title: string,
     *     meta_description: string,
     *     canonical_url: string,
     *     robots: string,
     *     og_title: string,
     *     og_description: string,
     *     og_image: string,
     *     schema_type: string
     * }  $sources
     */
    public function __construct(
        public readonly string $metaTitle,
        public readonly string $metaDescription,
        public readonly ?string $focusKeyword,
        public readonly string $canonicalUrl,
        public readonly array $robots,
        public readonly string $ogTitle,
        public readonly string $ogDescription,
        public readonly ?int $ogImageId,
        public readonly ?string $ogImageUrl,
        public readonly string $schemaType,
        public readonly string $publicUrl,
        public readonly array $sources,
    ) {}

    public function robotsDirective(): string
    {
        return implode(', ', $this->robots);
    }
}
