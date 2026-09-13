<?php

namespace App\Filament\Forms\Components;

use App\Filament\Forms\Tables\MediaImagePickerTable;
use App\Models\MediaAsset;
use App\Support\Media\MediaImageOptions;
use Filament\Actions\Action;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Browse-media modal picker + preview/remove for featured / OG image fields (SRS 12.2.3).
 */
final class MediaLibraryImageSelect
{
    /**
     * @return array{0: ModalTableSelect, 1: Placeholder}
     */
    public static function make(
        string $name = 'featured_image_id',
        string $label = 'Featured Image',
        ?string $helperText = null,
    ): array {
        $actionKey = 'clear_'.str_replace(['.', '-'], '_', $name);

        $select = ModalTableSelect::make($name)
            ->label($label)
            ->helperText($helperText ?? 'Choose a single image from the media library.')
            ->placeholder('No image selected')
            ->nullable()
            ->live()
            ->columnSpanFull()
            ->extraFieldWrapperAttributes([
                'class' => 'fi-fo-media-library-picker',
            ])
            ->tableConfiguration(MediaImagePickerTable::class)
            ->getOptionLabelUsing(fn ($value): ?string => MediaImageOptions::label(
                filled($value) ? (int) $value : null,
            ))
            ->selectAction(fn (Action $action): Action => $action
                ->label('Browse Media Library')
                ->icon('heroicon-o-photo')
                ->link()
                ->modalHeading('Select '.$label)
                ->modalWidth(Width::FiveExtraLarge)
                ->slideOver(false)
                ->modalSubmitActionLabel('Use selected image'))
            ->hintAction(
                Action::make($actionKey)
                    ->label('Remove')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->link()
                    ->visible(fn (ModalTableSelect $component): bool => filled($component->getState()))
                    ->action(function (ModalTableSelect $component): void {
                        $component->state(null);
                        $component->callAfterStateUpdated();
                    }),
            );

        $preview = Placeholder::make("{$name}_preview")
            ->hiddenLabel()
            ->columnSpanFull()
            ->content(fn (Get $get): HtmlString => self::previewHtml($get($name)));

        return [$select, $preview];
    }

    private static function previewHtml(mixed $id): HtmlString
    {
        if ($id === null || $id === '') {
            return new HtmlString(
                '<div class="flex h-36 items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 text-sm text-gray-500 dark:border-white/20 dark:bg-white/5 dark:text-gray-400">'
                .'No image selected'
                .'</div>',
            );
        }

        $asset = MediaAsset::query()->find((int) $id);

        if ($asset === null) {
            return new HtmlString(
                '<div class="rounded-xl border border-danger-300 bg-danger-50 px-4 py-3 text-sm text-danger-700 dark:border-danger-500/40 dark:bg-danger-500/10 dark:text-danger-400">'
                .'Broken reference — selected media is missing. Reassign or clear.'
                .'</div>',
            );
        }

        if (! $asset->isImage()) {
            return new HtmlString(
                '<div class="rounded-xl border border-danger-300 bg-danger-50 px-4 py-3 text-sm text-danger-700 dark:border-danger-500/40 dark:bg-danger-500/10 dark:text-danger-400">'
                .'Selected media is not an image.'
                .'</div>',
            );
        }

        $url = e($asset->previewUrl() ?? '');
        $title = e(Str::limit($asset->title, 72));
        $filename = e(Str::limit($asset->original_file_name, 56));
        $alt = e($asset->alt_text ?: $asset->title);

        if ($url === '') {
            return new HtmlString(
                '<div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-500 dark:border-white/20 dark:bg-white/5 dark:text-gray-400">'
                .'Image file is not available yet.'
                .'</div>',
            );
        }

        return new HtmlString(
            '<div class="overflow-hidden rounded-xl ring-1 ring-gray-950/10 dark:ring-white/10">'
            .'<img src="'.$url.'" alt="'.$alt.'" class="h-48 w-full max-w-md object-cover" />'
            .'<div class="space-y-0.5 border-t border-gray-100 bg-white px-3 py-2 dark:border-white/10 dark:bg-gray-900">'
            .'<p class="truncate text-sm font-medium text-gray-950 dark:text-white">'.$title.'</p>'
            .'<p class="truncate text-xs text-gray-500 dark:text-gray-400">'.$filename.'</p>'
            .'</div>'
            .'</div>',
        );
    }
}
