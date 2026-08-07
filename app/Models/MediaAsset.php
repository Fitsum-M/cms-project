<?php

namespace App\Models;

use App\Contracts\Ownable;
use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

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

    public function isImage(): bool
    {
        return is_string($this->mime_type) && str_starts_with($this->mime_type, 'image/');
    }

    public function previewUrl(): ?string
    {
        $url = $this->getFirstMediaUrl(self::LIBRARY_COLLECTION);

        return $url !== '' ? $url : null;
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
}
