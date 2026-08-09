<?php

namespace App\Filament\Forms\Components;

use App\Enums\Permission;
use App\Services\ContentSeoService;
use App\Support\Settings\SeoDefaultsSettings;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * Embedded SEO panel for Post / Page editors (SRS 12.5.3).
 */
final class SeoPanel
{
    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    public static function make(string $contentType = 'post'): array
    {
        return [
            Section::make('SEO & Metadata')
                ->description('Search and social metadata. Empty fields inherit from SEO Defaults, then dynamic fallbacks.')
                ->collapsible()
                ->collapsed(false)
                ->visible(fn (): bool => auth()->user()?->can(Permission::SeoConfigureContent->value) ?? false)
                ->schema([
                    Placeholder::make('seo_serp_preview')
                        ->label('Search result preview')
                        ->content(fn (Get $get, ?Model $record): HtmlString => self::serpPreviewHtml($contentType, $get, $record))
                        ->columnSpanFull(),
                    TextInput::make('seo.meta_title')
                        ->label('Meta Title')
                        ->maxLength(255)
                        ->live(debounce: 300)
                        ->helperText(fn (Get $get): string => self::charHint($get('seo.meta_title'), 60, 255))
                        ->autocomplete(false),
                    Textarea::make('seo.meta_description')
                        ->label('Meta Description')
                        ->rows(3)
                        ->maxLength(500)
                        ->live(debounce: 300)
                        ->helperText(fn (Get $get): string => self::charHint($get('seo.meta_description'), 160, 500))
                        ->columnSpanFull(),
                    TextInput::make('seo.focus_keyword')
                        ->label('Focus Keyword')
                        ->maxLength(100)
                        ->helperText('Editorial guidance only — not rendered in HTML.'),
                    TextInput::make('seo.canonical_url')
                        ->label('Canonical URL')
                        ->url()
                        ->maxLength(255)
                        ->helperText('Absolute URL override. Leave empty to use the content permalink.')
                        ->rule('nullable')
                        ->rule('url')
                        ->rule(fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                            if ($value === null || $value === '') {
                                return;
                            }

                            if (! preg_match('#^https?://#i', (string) $value)) {
                                $fail('Canonical URL must be an absolute http(s) URL.');
                            }
                        }),
                    CheckboxList::make('seo.robots')
                        ->label('Robots Meta')
                        ->options(SeoDefaultsSettings::robotsOptions())
                        ->columns(2)
                        ->helperText('Leave empty to inherit SEO Defaults robots.'),
                    TextInput::make('seo.og_title')
                        ->label('Open Graph Title')
                        ->maxLength(255)
                        ->live(debounce: 300)
                        ->helperText('Falls back to Meta Title, then Content Title.'),
                    Textarea::make('seo.og_description')
                        ->label('Open Graph Description')
                        ->rows(2)
                        ->maxLength(500)
                        ->live(debounce: 300)
                        ->helperText('Falls back to Meta Description, then Excerpt.')
                        ->columnSpanFull(),
                    ...MediaLibraryImageSelect::make(
                        name: 'seo.og_image_id',
                        label: 'Open Graph Image',
                        helperText: 'Falls back to Featured Image, then SEO Defaults OG Image.',
                    ),
                    Select::make('seo.schema_type')
                        ->label('Schema Type')
                        ->options(SeoDefaultsSettings::schemaTypeOptions())
                        ->nullable()
                        ->placeholder('— Inherit from SEO Defaults —')
                        ->live()
                        ->helperText('Structured data type for this content item.'),
                    TextInput::make('seo.custom_schema_type')
                        ->label('Custom Schema Type')
                        ->maxLength(100)
                        ->visible(fn (Get $get): bool => $get('seo.schema_type') === 'Custom')
                        ->required(fn (Get $get): bool => $get('seo.schema_type') === 'Custom')
                        ->helperText('Schema.org type name for advanced use.'),
                    Placeholder::make('seo_og_preview')
                        ->label('Open Graph preview')
                        ->content(fn (Get $get, ?Model $record): HtmlString => self::ogPreviewHtml($contentType, $get, $record))
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    private static function charHint(mixed $state, int $recommended, int $max): string
    {
        $length = mb_strlen(trim((string) ($state ?? '')));
        $base = "{$length} / {$recommended} recommended (max {$max}).";

        if ($length === 0) {
            return $base.' Empty inherits from SEO Defaults / dynamic fallback.';
        }

        if ($length > $recommended) {
            return $base.' Longer than recommended for search snippets.';
        }

        return $base;
    }

    private static function serpPreviewHtml(string $contentType, Get $get, ?Model $record): HtmlString
    {
        $resolved = self::preview($contentType, $get, $record);
        $title = e($resolved->metaTitle !== '' ? $resolved->metaTitle : 'Page title');
        $url = e($resolved->canonicalUrl !== '' ? $resolved->canonicalUrl : $resolved->publicUrl);
        $description = e($resolved->metaDescription !== '' ? $resolved->metaDescription : 'Meta description will appear here.');
        $titleClass = mb_strlen($resolved->metaTitle) > 60
            ? 'text-amber-700 dark:text-amber-400'
            : 'text-[#1a0dab] dark:text-blue-400';
        $descClass = mb_strlen($resolved->metaDescription) > 160
            ? 'text-amber-700 dark:text-amber-400'
            : 'text-[#4d5156] dark:text-gray-400';

        return new HtmlString(<<<HTML
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                <div class="truncate text-sm {$titleClass}">{$title}</div>
                <div class="mt-0.5 truncate text-xs text-[#006621] dark:text-green-400">{$url}</div>
                <div class="mt-1 line-clamp-2 text-sm {$descClass}">{$description}</div>
            </div>
        HTML);
    }

    private static function ogPreviewHtml(string $contentType, Get $get, ?Model $record): HtmlString
    {
        $resolved = self::preview($contentType, $get, $record);
        $title = e($resolved->ogTitle !== '' ? $resolved->ogTitle : 'OG title');
        $description = e($resolved->ogDescription !== '' ? $resolved->ogDescription : 'OG description');
        $urlHost = e(parse_url($resolved->publicUrl, PHP_URL_HOST) ?: 'example.com');
        $image = $resolved->ogImageUrl;

        $imageHtml = $image
            ? '<img src="'.e($image).'" alt="" class="h-36 w-full object-cover" />'
            : '<div class="flex h-36 items-center justify-center bg-gray-100 text-xs text-gray-500 dark:bg-white/10 dark:text-gray-400">No image</div>';

        return new HtmlString(<<<HTML
            <div class="max-w-md overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">
                {$imageHtml}
                <div class="space-y-1 p-3">
                    <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">{$urlHost}</div>
                    <div class="text-sm font-semibold text-gray-950 dark:text-white">{$title}</div>
                    <div class="line-clamp-2 text-xs text-gray-600 dark:text-gray-400">{$description}</div>
                </div>
            </div>
        HTML);
    }

    private static function preview(string $contentType, Get $get, ?Model $record): \App\Support\Seo\ResolvedSeoMetadata
    {
        return app(ContentSeoService::class)->previewFromForm(
            $contentType,
            [
                'meta_title' => $get('seo.meta_title'),
                'meta_description' => $get('seo.meta_description'),
                'focus_keyword' => $get('seo.focus_keyword'),
                'canonical_url' => $get('seo.canonical_url'),
                'robots' => $get('seo.robots') ?? [],
                'og_title' => $get('seo.og_title'),
                'og_description' => $get('seo.og_description'),
                'og_image_id' => $get('seo.og_image_id'),
                'schema_type' => $get('seo.schema_type'),
                'custom_schema_type' => $get('seo.custom_schema_type'),
            ],
            [
                'title' => $get('title'),
                'excerpt' => $get('excerpt'),
                'body' => $get('body'),
                'slug' => $get('slug'),
                'featured_image_id' => $get('featured_image_id'),
                'parent_id' => $get('parent_id'),
                'post_type' => $get('post_type'),
            ],
            $record,
        );
    }
}
