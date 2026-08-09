<?php

namespace App\Support\Media;

use App\Models\MediaAsset;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Shared helpers for selecting library images (featured / OG) — SRS 12.2.3 / 14.
 */
final class MediaImageOptions
{
    /**
     * @return array<int, string>
     */
    public static function options(?int $limit = 200): array
    {
        return self::imageQuery()
            ->orderByDesc('id')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get()
            ->mapWithKeys(fn (MediaAsset $asset): array => [
                $asset->id => self::formatLabel($asset),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function search(string $search, int $limit = 50): array
    {
        $search = trim($search);

        $query = self::imageQuery()->orderByDesc('id');

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($inner) use ($term): void {
                $inner
                    ->where('title', 'like', $term)
                    ->orWhere('original_file_name', 'like', $term)
                    ->orWhere('alt_text', 'like', $term);
            });
        }

        return $query
            ->limit($limit)
            ->get()
            ->mapWithKeys(fn (MediaAsset $asset): array => [
                $asset->id => self::formatLabel($asset),
            ])
            ->all();
    }

    public static function label(?int $id): ?string
    {
        if ($id === null || $id < 1) {
            return null;
        }

        $asset = MediaAsset::query()->find($id);

        return $asset ? self::formatLabel($asset) : "Missing media #{$id}";
    }

    public static function formatLabel(MediaAsset $asset): string
    {
        return $asset->title.' ('.$asset->original_file_name.')';
    }

    /**
     * @throws ValidationException
     */
    public static function assertAssignableImage(?int $id): ?int
    {
        if ($id === null || $id < 1) {
            return null;
        }

        $asset = MediaAsset::query()->find($id);

        if ($asset === null) {
            throw ValidationException::withMessages([
                'featured_image_id' => 'Selected media does not exist.',
            ]);
        }

        if (! $asset->isImage()) {
            throw ValidationException::withMessages([
                'featured_image_id' => 'Featured image must be an image from the media library.',
            ]);
        }

        return $asset->id;
    }

    /**
     * @return Collection<int, MediaAsset>
     */
    public static function images(?int $limit = null): Collection
    {
        return self::imageQuery()
            ->orderByDesc('id')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get();
    }

    private static function imageQuery()
    {
        return MediaAsset::query()->where('mime_type', 'like', 'image/%');
    }
}
