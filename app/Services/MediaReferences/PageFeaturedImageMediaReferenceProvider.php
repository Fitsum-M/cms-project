<?php

namespace App\Services\MediaReferences;

use App\Contracts\MediaReferenceProvider;
use App\Filament\Resources\Pages\PageResource;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Support\Media\MediaReference;
use Illuminate\Support\Facades\Schema;

class PageFeaturedImageMediaReferenceProvider implements MediaReferenceProvider
{
    public function referencesFor(MediaAsset $asset): array
    {
        if (! Schema::hasTable('pages')) {
            return [];
        }

        return Page::query()
            ->withTrashed()
            ->where('featured_image_id', $asset->getKey())
            ->get()
            ->map(fn (Page $page): MediaReference => new MediaReference(
                type: 'page',
                label: 'Page',
                detail: "Featured image for “{$page->title}”",
                url: PageResource::getUrl('edit', ['record' => $page]),
            ))
            ->all();
    }

    public function clearReferences(MediaAsset $asset): void
    {
        if (! Schema::hasTable('pages')) {
            return;
        }

        Page::query()
            ->withTrashed()
            ->where('featured_image_id', $asset->getKey())
            ->update(['featured_image_id' => null]);
    }
}
