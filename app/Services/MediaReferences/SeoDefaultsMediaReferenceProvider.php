<?php

namespace App\Services\MediaReferences;

use App\Contracts\MediaReferenceProvider;
use App\Filament\Pages\System\SettingsPage;
use App\Models\MediaAsset;
use App\Support\Media\MediaReference;
use App\Support\Settings\SeoDefaultsSettings;

/**
 * SEO Defaults default OG image stores a MediaAsset ID (SRS 14.13).
 */
class SeoDefaultsMediaReferenceProvider implements MediaReferenceProvider
{
    public function __construct(
        private readonly SeoDefaultsSettings $seoDefaults,
    ) {}

    public function referencesFor(MediaAsset $asset): array
    {
        if ($this->seoDefaults->ogImageId() !== $asset->getKey()) {
            return [];
        }

        return [
            new MediaReference(
                type: 'settings',
                label: 'SEO Defaults',
                detail: 'Default Open Graph image',
                url: SettingsPage::getUrl().'?tab=seo',
            ),
        ];
    }

    public function clearReferences(MediaAsset $asset): void
    {
        if ($this->seoDefaults->ogImageId() !== $asset->getKey()) {
            return;
        }

        $this->seoDefaults->save([
            ...$this->seoDefaults->all(),
            SeoDefaultsSettings::OG_IMAGE_ID => null,
        ]);
    }
}
