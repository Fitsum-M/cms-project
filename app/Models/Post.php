<?php

namespace App\Models;

use App\Contracts\HasContentLifecycle;
use App\Contracts\HasSeoMetadata;
use App\Contracts\Ownable;
use App\Enums\ContentSlugScope;
use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\PostVisibility;
use App\Models\Concerns\HasContentLifecycle as HasContentLifecycleTrait;
use App\Models\Concerns\HasContentSeo;
use App\Models\Concerns\HasSafeHtmlBody;
use App\Services\ContentUrlGenerator;
use App\Services\PostService;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'title',
    'slug',
    'body',
    'excerpt',
    'author_id',
    'featured_image_id',
    'post_type',
    'custom_fields',
    'status',
    'visibility',
    'password',
    'published_at',
])]
#[Hidden(['password'])]
class Post extends Model implements HasContentLifecycle, HasSeoMetadata, Ownable
{
    /** @use HasFactory<PostFactory> */
    use HasContentLifecycleTrait;
    use HasContentSeo;
    use HasFactory;
    use HasSafeHtmlBody;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'visibility' => PostVisibility::class,
            'published_at' => 'datetime',
            'view_count' => 'integer',
            'author_id' => 'integer',
            'featured_image_id' => 'integer',
            'custom_fields' => 'array',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function customFields(): array
    {
        return is_array($this->custom_fields) ? $this->custom_fields : [];
    }

    public function customField(string $key, mixed $default = null): mixed
    {
        return data_get($this->customFields(), $key, $default);
    }

    public function customFieldMedia(string $key): ?MediaAsset
    {
        $id = $this->customField($key);

        if (! is_numeric($id)) {
            return null;
        }

        return MediaAsset::query()->find((int) $id);
    }

    /**
     * @return list<MediaAsset>
     */
    public function customFieldMediaMany(string $key): array
    {
        $ids = $this->customField($key, []);

        if (! is_array($ids) || $ids === []) {
            return [];
        }

        $normalized = array_values(array_filter(array_map(
            static fn ($id): ?int => is_numeric($id) ? (int) $id : null,
            $ids,
        )));

        if ($normalized === []) {
            return [];
        }

        $assets = MediaAsset::query()
            ->whereIn('id', $normalized)
            ->get()
            ->keyBy('id');

        $ordered = [];
        foreach ($normalized as $id) {
            $asset = $assets->get($id);
            if ($asset !== null) {
                $ordered[] = $asset;
            }
        }

        return $ordered;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'featured_image_id');
    }

    public function hasFeaturedImage(): bool
    {
        return $this->featured_image_id !== null && $this->featuredImage !== null;
    }

    /**
     * True when an ID is stored but the media row is gone (broken reference, SRS 12.2.3).
     */
    public function hasBrokenFeaturedImage(): bool
    {
        return $this->featured_image_id !== null && $this->featuredImage === null;
    }

    /**
     * Public URL for listings / OG fallback (SRS 12.2.3).
     */
    public function featuredImageUrl(?string $conversion = null): ?string
    {
        $image = $this->featuredImage;

        if ($image === null || ! $image->isImage()) {
            return null;
        }

        if ($conversion !== null) {
            return $image->conversionUrl($conversion) ?? $image->originalUrl();
        }

        return $image->previewUrl() ?? $image->originalUrl();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_post')->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag')->withTimestamps();
    }

    public function customTaxonomyTerms(): BelongsToMany
    {
        return $this->belongsToMany(
            CustomTaxonomyTerm::class,
            'custom_taxonomy_term_post',
            'post_id',
            'custom_taxonomy_term_id',
        )->withTimestamps();
    }

    public function ownerKey(): ?int
    {
        return $this->author_id;
    }

    public function contentSlugScope(): ContentSlugScope
    {
        return ContentSlugScope::Posts;
    }

    public function publishPermission(): Permission
    {
        return Permission::PostsPublish;
    }

    public function restorePermission(): Permission
    {
        return Permission::PostsRestore;
    }

    public function forceDeletePermission(): Permission
    {
        return Permission::PostsForceDelete;
    }

    public function resolvedExcerpt(): string
    {
        return app(PostService::class)->effectiveExcerpt($this);
    }

    public function recordPublicView(): void
    {
        $this->increment('view_count');
    }

    public function isPubliclyAccessible(): bool
    {
        return app(PostService::class)->isPubliclyAccessible($this);
    }

    public function contentPublicPath(): string
    {
        return app(ContentUrlGenerator::class)->postPath($this);
    }
}
