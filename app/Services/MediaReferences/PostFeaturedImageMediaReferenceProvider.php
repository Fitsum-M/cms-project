<?php

namespace App\Services\MediaReferences;

use App\Contracts\MediaReferenceProvider;
use App\Filament\Resources\Posts\PostResource;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Support\Media\MediaReference;
use Illuminate\Support\Facades\Schema;

class PostFeaturedImageMediaReferenceProvider implements MediaReferenceProvider
{
    public function referencesFor(MediaAsset $asset): array
    {
        if (! Schema::hasTable('posts')) {
            return [];
        }

        return Post::query()
            ->withTrashed()
            ->where('featured_image_id', $asset->getKey())
            ->get()
            ->map(fn (Post $post): MediaReference => new MediaReference(
                type: 'post',
                label: 'Post',
                detail: "Featured image for “{$post->title}”",
                url: PostResource::getUrl('edit', ['record' => $post]),
            ))
            ->all();
    }

    public function clearReferences(MediaAsset $asset): void
    {
        if (! Schema::hasTable('posts')) {
            return;
        }

        Post::query()
            ->withTrashed()
            ->where('featured_image_id', $asset->getKey())
            ->update(['featured_image_id' => null]);
    }
}
