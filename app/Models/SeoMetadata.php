<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RalphJSmit\Laravel\SEO\Models\SEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;

/**
 * Content-level SEO record (SRS 12.5.3 / 17.1 SEO Metadata).
 * Extends ralphjsmit/laravel-seo with focus keyword, OG fields, schema type, and media OG image.
 */
class SeoMetadata extends SEO
{
    public $table = 'seo';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'og_image_id' => 'integer',
        ];
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'og_image_id');
    }

    /**
     * @return list<string>
     */
    public function robotsList(): array
    {
        $raw = $this->getAttributes()['robots'] ?? null;

        if ($raw === null || $raw === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            explode(',', (string) $raw),
        )));
    }

    public function prepareForUsage(): SEOData
    {
        $data = parent::prepareForUsage();

        if ($data->openGraphTitle === null && filled($this->og_title)) {
            $data->openGraphTitle = (string) $this->og_title;
        }

        if ($data->description === null && filled($this->og_description)) {
            // Parent already mapped description; OG description is resolved via ContentSeoService.
        }

        if ($data->image === null && $this->og_image_id !== null) {
            $url = $this->ogImage?->originalUrl();
            if (filled($url)) {
                $data->image = $url;
            }
        }

        return $data;
    }
}
