<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\PostVisibility;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestPostFeaturedImageSeeder extends Seeder
{
    public function run(): Post
    {
        $image = MediaAsset::query()
            ->where('mime_type', 'like', 'image/%')
            ->first();

        $author = User::query()->where('email', 'admin@cms.local')->first()
            ?? User::query()->first()
            ?? User::factory()->create();

        // Remove previous test post if slug exists to make seeder idempotent
        Post::query()->where('slug', 'exploring-modern-cms-media-library')->delete();

        return Post::factory()
            ->published()
            ->withFeaturedImage($image)
            ->create([
                'title' => 'Exploring the Modern CMS Media Library and Conversions',
                'slug' => 'exploring-modern-cms-media-library',
                'excerpt' => 'A showcase article featuring high-resolution images, responsive conversions, and seamless media asset management.',
                'body' => '<p>Welcome to our media showcase post! This article is seeded with a linked featured image from the Media Library.</p><p>With responsive conversions enabled, the CMS automatically provides thumbnail, medium, and large renditions optimized for every device screen size.</p>',
                'author_id' => $author->id,
                'visibility' => PostVisibility::Public,
                'status' => ContentStatus::Published,
                'published_at' => now(),
            ]);
    }
}
