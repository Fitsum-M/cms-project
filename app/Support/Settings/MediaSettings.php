<?php

namespace App\Support\Settings;

use App\Enums\SettingGroup;
use App\Services\SettingsStore;
use Illuminate\Support\Facades\Schema;

class MediaSettings
{
    public const THUMBNAIL_WIDTH = 'thumbnail_width';

    public const THUMBNAIL_HEIGHT = 'thumbnail_height';

    public const MEDIUM_WIDTH = 'medium_width';

    public const MEDIUM_HEIGHT = 'medium_height';

    public const LARGE_WIDTH = 'large_width';

    public const LARGE_HEIGHT = 'large_height';

    public const UPLOAD_MAX_FILE_SIZE_MB = 'upload_max_file_size_mb';

    public const DEFAULT_UPLOAD_FOLDER_ID = 'default_upload_folder_id';

    public const ALLOWED_FILE_TYPES = 'allowed_file_types';

    public function __construct(
        private readonly SettingsStore $store,
    ) {}

    public function thumbnailWidth(): int
    {
        return $this->intSetting(self::THUMBNAIL_WIDTH);
    }

    public function thumbnailHeight(): int
    {
        return $this->intSetting(self::THUMBNAIL_HEIGHT);
    }

    public function mediumWidth(): int
    {
        return $this->intSetting(self::MEDIUM_WIDTH);
    }

    public function mediumHeight(): int
    {
        return $this->intSetting(self::MEDIUM_HEIGHT);
    }

    public function largeWidth(): int
    {
        return $this->intSetting(self::LARGE_WIDTH);
    }

    public function largeHeight(): int
    {
        return $this->intSetting(self::LARGE_HEIGHT);
    }

    public function uploadMaxFileSizeMb(): int
    {
        return $this->intSetting(self::UPLOAD_MAX_FILE_SIZE_MB);
    }

    public function uploadMaxFileSizeBytes(): int
    {
        return $this->uploadMaxFileSizeMb() * 1024 * 1024;
    }

    public function defaultUploadFolderId(): ?int
    {
        $value = $this->store->get(
            SettingGroup::Media,
            self::DEFAULT_UPLOAD_FOLDER_ID,
            self::defaults()[self::DEFAULT_UPLOAD_FOLDER_ID],
        );

        return $value === null || $value === '' ? null : (int) $value;
    }

    /**
     * @return list<string>
     */
    public function allowedFileTypes(): array
    {
        $value = $this->store->get(
            SettingGroup::Media,
            self::ALLOWED_FILE_TYPES,
            self::defaults()[self::ALLOWED_FILE_TYPES],
        );

        if (! is_array($value)) {
            return self::defaults()[self::ALLOWED_FILE_TYPES];
        }

        return array_values(array_filter($value, fn (mixed $item): bool => is_string($item) && $item !== ''));
    }

    public function allowsExtension(string $extension): bool
    {
        $extension = strtolower(ltrim($extension, '.'));

        return in_array($extension, $this->allowedFileTypes(), true);
    }

    /**
     * @return array{
     *     thumbnail_width: int,
     *     thumbnail_height: int,
     *     medium_width: int,
     *     medium_height: int,
     *     large_width: int,
     *     large_height: int,
     *     upload_max_file_size_mb: int,
     *     default_upload_folder_id: int|null,
     *     allowed_file_types: list<string>
     * }
     */
    public function all(): array
    {
        return [
            self::THUMBNAIL_WIDTH => $this->thumbnailWidth(),
            self::THUMBNAIL_HEIGHT => $this->thumbnailHeight(),
            self::MEDIUM_WIDTH => $this->mediumWidth(),
            self::MEDIUM_HEIGHT => $this->mediumHeight(),
            self::LARGE_WIDTH => $this->largeWidth(),
            self::LARGE_HEIGHT => $this->largeHeight(),
            self::UPLOAD_MAX_FILE_SIZE_MB => $this->uploadMaxFileSizeMb(),
            self::DEFAULT_UPLOAD_FOLDER_ID => $this->defaultUploadFolderId(),
            self::ALLOWED_FILE_TYPES => $this->allowedFileTypes(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        $merged = [
            ...$this->all(),
            ...$data,
        ];

        $allowed = $merged[self::ALLOWED_FILE_TYPES] ?? [];
        if (! is_array($allowed)) {
            $allowed = [];
        }

        $allowed = array_values(array_unique(array_map(
            fn (mixed $ext): string => strtolower(ltrim((string) $ext, '.')),
            $allowed,
        )));

        $validExtensions = array_keys(self::fileTypeOptions());
        $allowed = array_values(array_intersect($allowed, $validExtensions));

        $this->store->putMany(SettingGroup::Media, [
            self::THUMBNAIL_WIDTH => ['value' => $this->clampDimension($merged[self::THUMBNAIL_WIDTH]), 'type' => 'integer'],
            self::THUMBNAIL_HEIGHT => ['value' => $this->clampDimension($merged[self::THUMBNAIL_HEIGHT]), 'type' => 'integer'],
            self::MEDIUM_WIDTH => ['value' => $this->clampDimension($merged[self::MEDIUM_WIDTH]), 'type' => 'integer'],
            self::MEDIUM_HEIGHT => ['value' => $this->clampDimension($merged[self::MEDIUM_HEIGHT]), 'type' => 'integer'],
            self::LARGE_WIDTH => ['value' => $this->clampDimension($merged[self::LARGE_WIDTH]), 'type' => 'integer'],
            self::LARGE_HEIGHT => ['value' => $this->clampDimension($merged[self::LARGE_HEIGHT]), 'type' => 'integer'],
            self::UPLOAD_MAX_FILE_SIZE_MB => ['value' => $this->clampUploadMb($merged[self::UPLOAD_MAX_FILE_SIZE_MB]), 'type' => 'integer'],
            self::DEFAULT_UPLOAD_FOLDER_ID => [
                'value' => $this->normalizeNullableId($merged[self::DEFAULT_UPLOAD_FOLDER_ID] ?? null),
                'type' => 'integer',
            ],
            self::ALLOWED_FILE_TYPES => ['value' => $allowed, 'type' => 'json'],
        ]);
    }

    /**
     * @return array{
     *     thumbnail_width: int,
     *     thumbnail_height: int,
     *     medium_width: int,
     *     medium_height: int,
     *     large_width: int,
     *     large_height: int,
     *     upload_max_file_size_mb: int,
     *     default_upload_folder_id: null,
     *     allowed_file_types: list<string>
     * }
     */
    public static function defaults(): array
    {
        return [
            self::THUMBNAIL_WIDTH => 150,
            self::THUMBNAIL_HEIGHT => 150,
            self::MEDIUM_WIDTH => 300,
            self::MEDIUM_HEIGHT => 300,
            self::LARGE_WIDTH => 1024,
            self::LARGE_HEIGHT => 1024,
            self::UPLOAD_MAX_FILE_SIZE_MB => 10,
            self::DEFAULT_UPLOAD_FOLDER_ID => null,
            self::ALLOWED_FILE_TYPES => array_keys(self::fileTypeOptions()),
        ];
    }

    /**
     * Extensions permitted by SRS 14.2 (admin may restrict via settings).
     *
     * @return array<string, string>
     */
    public static function fileTypeOptions(): array
    {
        return [
            'jpg' => 'jpg (Images)',
            'jpeg' => 'jpeg (Images)',
            'png' => 'png (Images)',
            'gif' => 'gif (Images)',
            'webp' => 'webp (Images)',
            'svg' => 'svg (Images)',
            'pdf' => 'pdf (Documents)',
            'doc' => 'doc (Documents)',
            'docx' => 'docx (Documents)',
            'txt' => 'txt (Documents)',
            'zip' => 'zip (Archives)',
        ];
    }

    /**
     * MIME types / accept hints for Filament FileUpload acceptedFileTypes.
     *
     * Includes `image/*` and `.ext` entries so OS file pickers (especially Windows)
     * filter to images instead of a vague "Custom Files" / all-files list.
     *
     * @param  list<string>|null  $extensions
     * @return list<string>
     */
    public function acceptedMimeTypes(?array $extensions = null): array
    {
        $extensions ??= $this->allowedFileTypes();
        $map = self::extensionMimeMap();
        $accepted = [];
        $hasImage = false;

        foreach ($extensions as $extension) {
            $extension = strtolower(ltrim($extension, '.'));
            $mapped = $map[$extension] ?? [];

            foreach ($mapped as $mime) {
                if (str_starts_with($mime, 'image/')) {
                    $hasImage = true;
                }

                $accepted[$mime] = true;
            }

            if ($extension !== '') {
                $accepted['.'.$extension] = true;
            }
        }

        $result = array_keys($accepted);

        if ($hasImage) {
            array_unshift($result, 'image/*');
        }

        return $result;
    }

    /**
     * Max upload size in kilobytes for Filament/Laravel file rules.
     */
    public function uploadMaxFileSizeKb(): int
    {
        return max(1, (int) ceil($this->uploadMaxFileSizeBytes() / 1024));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function extensionMimeMap(): array
    {
        return [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'txt' => ['text/plain'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function folderOptions(): array
    {
        if (! self::foldersTableReady()) {
            return [];
        }

        return app(\App\Services\FolderService::class)->options();
    }

    public static function foldersTableReady(): bool
    {
        try {
            return Schema::hasTable('folders');
        } catch (\Throwable) {
            return false;
        }
    }

    private function intSetting(string $key): int
    {
        return (int) $this->store->get(SettingGroup::Media, $key, self::defaults()[$key]);
    }

    private function clampDimension(mixed $value): int
    {
        return max(1, min(10000, (int) $value));
    }

    private function clampUploadMb(mixed $value): int
    {
        return max(1, min(1024, (int) $value));
    }

    private function normalizeNullableId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
