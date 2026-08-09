<?php

namespace App\Models;

use App\Contracts\Ownable;
use App\Support\Settings\MediaSettings;
use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'title',
    'alt_text',
    'caption',
    'description',
    'folder_id',
    'uploaded_by',
    'original_file_name',
    'mime_type',
    'size',
    'width',
    'height',
])]
class MediaAsset extends Model implements HasMedia, Ownable
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory, InteractsWithMedia;

    public const LIBRARY_COLLECTION = 'library';

    public const CONVERSION_THUMBNAIL = 'thumbnail';

    public const CONVERSION_MEDIUM = 'medium';

    public const CONVERSION_LARGE = 'large';

    /**
     * @return list<string>
     */
    public static function imageConversions(): array
    {
        return [
            self::CONVERSION_THUMBNAIL,
            self::CONVERSION_MEDIUM,
            self::CONVERSION_LARGE,
        ];
    }

    /**
     * MIME types treated as documents for library filtering (SRS 14.12).
     *
     * @return list<string>
     */
    public static function documentMimeTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
        ];
    }

    /**
     * MIME types treated as archives for library filtering (SRS 14.12).
     *
     * @return list<string>
     */
    public static function archiveMimeTypes(): array
    {
        return [
            'application/zip',
            'application/x-zip-compressed',
        ];
    }

    public function fileTypeGroup(): ?string
    {
        if ($this->isImage()) {
            return 'image';
        }

        if (is_string($this->mime_type) && in_array($this->mime_type, self::documentMimeTypes(), true)) {
            return 'document';
        }

        if (is_string($this->mime_type) && in_array($this->mime_type, self::archiveMimeTypes(), true)) {
            return 'archive';
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'folder_id' => 'integer',
            'uploaded_by' => 'integer',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function ownerKey(): ?int
    {
        return $this->uploaded_by;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::LIBRARY_COLLECTION)
            ->useDisk(config('media-library.disk_name', 'public'))
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        if ($media !== null && ! self::supportsRasterConversions($media->mime_type)) {
            return;
        }

        $settings = app(MediaSettings::class);

        $this->addMediaConversion(self::CONVERSION_THUMBNAIL)
            ->fit(Fit::Max, $settings->thumbnailWidth(), $settings->thumbnailHeight())
            ->keepOriginalImageFormat()
            ->nonOptimized()
            ->queued()
            ->performOnCollections(self::LIBRARY_COLLECTION);

        $this->addMediaConversion(self::CONVERSION_MEDIUM)
            ->fit(Fit::Max, $settings->mediumWidth(), $settings->mediumHeight())
            ->keepOriginalImageFormat()
            ->nonOptimized()
            ->queued()
            ->performOnCollections(self::LIBRARY_COLLECTION);

        $this->addMediaConversion(self::CONVERSION_LARGE)
            ->fit(Fit::Max, $settings->largeWidth(), $settings->largeHeight())
            ->keepOriginalImageFormat()
            ->nonOptimized()
            ->queued()
            ->performOnCollections(self::LIBRARY_COLLECTION);
    }

    public static function supportsRasterConversions(?string $mimeType): bool
    {
        if ($mimeType === null || ! str_starts_with($mimeType, 'image/')) {
            return false;
        }

        return $mimeType !== 'image/svg+xml';
    }

    public function isImage(): bool
    {
        return is_string($this->mime_type) && str_starts_with($this->mime_type, 'image/');
    }

    public function originalUrl(): ?string
    {
        $url = $this->getFirstMediaUrl(self::LIBRARY_COLLECTION);

        return $url !== '' ? $url : null;
    }

    public function conversionUrl(string $conversion): ?string
    {
        if (! $this->hasGeneratedConversion($conversion)) {
            return null;
        }

        $url = $this->getFirstMediaUrl(self::LIBRARY_COLLECTION, $conversion);

        return $url !== '' ? $url : null;
    }

    public function hasGeneratedConversion(string $conversion): bool
    {
        $media = $this->getFirstMedia(self::LIBRARY_COLLECTION);

        return $media?->hasGeneratedConversion($conversion) ?? false;
    }

    public function previewUrl(): ?string
    {
        return $this->conversionUrl(self::CONVERSION_THUMBNAIL) ?? $this->originalUrl();
    }

    public function humanSize(): string
    {
        $bytes = max(0, (int) $this->size);

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return ($i === 0 ? (string) $bytes : number_format($size, $size >= 10 ? 0 : 1)).' '.$units[$i];
    }

    public function isReferenced(): bool
    {
        return app(\App\Services\MediaReferenceService::class)->isReferenced($this);
    }
}
