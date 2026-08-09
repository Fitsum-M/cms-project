<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\User;
use App\Support\Settings\MediaSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaUploadService
{
    public function __construct(
        private readonly MediaSettings $mediaSettings,
    ) {}

    /**
     * @param  list<UploadedFile|TemporaryUploadedFile|string>  $files
     * @return Collection<int, MediaAsset>
     */
    public function uploadMany(array $files, User $uploader, ?int $folderId = null, bool $applyDefaultFolder = true): Collection
    {
        $assets = collect();

        foreach ($files as $file) {
            $uploaded = $this->normalizeFile($file);

            if ($uploaded === null) {
                continue;
            }

            $assets->push($this->upload($uploaded, $uploader, $folderId, $applyDefaultFolder));
        }

        if ($assets->isEmpty()) {
            throw ValidationException::withMessages([
                'files' => 'Select at least one file to upload.',
            ]);
        }

        return $assets;
    }

    public function upload(UploadedFile $file, User $uploader, ?int $folderId = null, bool $applyDefaultFolder = true): MediaAsset
    {
        $this->assertAllowed($file);

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();
        $size = (int) $file->getSize();
        [$width, $height] = $this->imageDimensions($file, is_string($mimeType) ? $mimeType : null);

        if ($applyDefaultFolder) {
            $folderId ??= $this->mediaSettings->defaultUploadFolderId();
        }

        $asset = MediaAsset::query()->create([
            'title' => $this->defaultTitle($originalName),
            'alt_text' => null,
            'caption' => null,
            'description' => null,
            'folder_id' => $folderId,
            'uploaded_by' => $uploader->getKey(),
            'original_file_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => $size,
            'width' => $width,
            'height' => $height,
        ]);

        $asset
            ->addMedia($file)
            ->usingFileName($this->safeStoredFileName($originalName, $extension))
            ->usingName($asset->title)
            ->toMediaCollection(MediaAsset::LIBRARY_COLLECTION);

        return $asset->fresh(['uploader']) ?? $asset;
    }

    public function defaultTitle(string $originalFileName): string
    {
        $base = pathinfo($originalFileName, PATHINFO_FILENAME);

        $base = trim(str_replace(['_', '-'], ' ', $base));

        return $base !== '' ? $base : 'Untitled';
    }

    private function assertAllowed(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        if ($extension === '' || ! $this->mediaSettings->allowsExtension($extension)) {
            throw ValidationException::withMessages([
                'files' => "File type .{$extension} is not allowed.",
            ]);
        }

        $maxBytes = $this->mediaSettings->uploadMaxFileSizeBytes();
        $size = (int) $file->getSize();

        if ($size <= 0 || $size > $maxBytes) {
            $maxMb = $this->mediaSettings->uploadMaxFileSizeMb();

            throw ValidationException::withMessages([
                'files' => "Each file must be between 1 byte and {$maxMb} MB.",
            ]);
        }
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function imageDimensions(UploadedFile $file, ?string $mimeType): array
    {
        if ($mimeType === null || ! str_starts_with($mimeType, 'image/') || $mimeType === 'image/svg+xml') {
            return [null, null];
        }

        $path = $file->getRealPath();

        if ($path === false || $path === '') {
            return [null, null];
        }

        $info = @getimagesize($path);

        if ($info === false) {
            return [null, null];
        }

        return [(int) $info[0], (int) $info[1]];
    }

    private function safeStoredFileName(string $originalName, string $extension): string
    {
        $base = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));

        if ($base === '') {
            $base = 'file';
        }

        $extension = ltrim($extension, '.');

        return $extension !== '' ? "{$base}.{$extension}" : $base;
    }

    private function normalizeFile(mixed $file): ?UploadedFile
    {
        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            return $file;
        }

        if (is_string($file) && $file !== '') {
            return TemporaryUploadedFile::createFromLivewire($file);
        }

        return null;
    }
}
