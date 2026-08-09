<?php

namespace App\Filament\Forms\Components;

use App\Models\MediaAsset;
use App\Support\Media\MediaImageOptions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

/**
 * Media-library image select + live preview for featured / OG style fields (SRS 12.2.3).
 */
final class MediaLibraryImageSelect
{
    /**
     * @return array{0: Select, 1: Placeholder}
     */
    public static function make(
        string $name = 'featured_image_id',
        string $label = 'Featured Image',
        ?string $helperText = null,
    ): array {
        $select = Select::make($name)
            ->label($label)
            ->searchable()
            ->nullable()
            ->placeholder('— None —')
            ->helperText($helperText ?? 'Select a single image from the media library.')
            ->getSearchResultsUsing(fn (string $search): array => MediaImageOptions::search($search))
            ->getOptionLabelUsing(fn ($value): ?string => MediaImageOptions::label(
                filled($value) ? (int) $value : null,
            ))
            ->options(fn (): array => MediaImageOptions::options(limit: 100))
            ->live();

        $preview = Placeholder::make("{$name}_preview")
            ->label('Preview')
            ->content(function (Get $get) use ($name): HtmlString {
                $id = $get($name);

                if ($id === null || $id === '') {
                    return new HtmlString(
                        '<p class="text-sm text-gray-500 dark:text-gray-400">No image selected.</p>',
                    );
                }

                $asset = MediaAsset::query()->find((int) $id);

                if ($asset === null) {
                    return new HtmlString(
                        '<p class="text-sm text-danger-600 dark:text-danger-400">Broken reference — selected media is missing. Reassign or clear.</p>',
                    );
                }

                if (! $asset->isImage()) {
                    return new HtmlString(
                        '<p class="text-sm text-danger-600 dark:text-danger-400">Selected media is not an image.</p>',
                    );
                }

                $url = e($asset->previewUrl() ?? '');
                $alt = e($asset->alt_text ?: $asset->title);

                if ($url === '') {
                    return new HtmlString(
                        '<p class="text-sm text-gray-500 dark:text-gray-400">Image file is not available yet.</p>',
                    );
                }

                return new HtmlString(
                    '<img src="'.$url.'" alt="'.$alt.'" class="h-36 w-auto max-w-full rounded-lg object-cover ring-1 ring-gray-950/10 dark:ring-white/10" />',
                );
            });

        return [$select, $preview];
    }
}
