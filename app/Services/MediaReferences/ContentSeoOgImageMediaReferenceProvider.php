<?php

namespace App\Services\MediaReferences;

use App\Contracts\MediaReferenceProvider;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Posts\PostResource;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Post;
use App\Models\SeoMetadata;
use App\Support\Media\MediaReference;
use Illuminate\Support\Facades\Schema;

class ContentSeoOgImageMediaReferenceProvider implements MediaReferenceProvider
{
    public function referencesFor(MediaAsset $asset): array
    {
        if (! Schema::hasTable('seo')) {
            return [];
        }

        return SeoMetadata::query()
            ->where('og_image_id', $asset->getKey())
            ->with('model')
            ->get()
            ->map(function (SeoMetadata $seo): ?MediaReference {
                $model = $seo->model;

                if ($model instanceof Post) {
                    return new MediaReference(
                        type: 'post_seo',
                        label: 'Post SEO',
                        detail: "OG image for “{$model->title}”",
                        url: PostResource::getUrl('edit', ['record' => $model]),
                    );
                }

                if ($model instanceof Page) {
                    return new MediaReference(
                        type: 'page_seo',
                        label: 'Page SEO',
                        detail: "OG image for “{$model->title}”",
                        url: PageResource::getUrl('edit', ['record' => $model]),
                    );
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    public function clearReferences(MediaAsset $asset): void
    {
        if (! Schema::hasTable('seo')) {
            return;
        }

        SeoMetadata::query()
            ->where('og_image_id', $asset->getKey())
            ->update(['og_image_id' => null]);
    }
}
