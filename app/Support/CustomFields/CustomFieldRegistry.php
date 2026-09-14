<?php

namespace App\Support\CustomFields;

use App\Filament\Forms\Components\MediaLibraryImageSelect;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Code-defined custom-field schemas per CPT slug (Review Comment #3 §9 / Step 8).
 */
final class CustomFieldRegistry
{
    public const TEAM_MEMBERS = 'team-members';

    public const SERVICES = 'services';

    public const PRODUCTS = 'products';

    public const TESTIMONIALS = 'testimonials';

    /**
     * @return list<string>
     */
    public static function demoTypeSlugs(): array
    {
        return [
            self::TEAM_MEMBERS,
            self::SERVICES,
            self::PRODUCTS,
            self::TESTIMONIALS,
        ];
    }

    public static function hasSchema(string $postType): bool
    {
        return in_array($postType, self::demoTypeSlugs(), true);
    }

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    public static function formSections(): array
    {
        return [
            Section::make('Team member fields')
                ->description('Structured metadata for Team Members.')
                ->visible(fn (Get $get): bool => (string) ($get('post_type') ?: 'post') === self::TEAM_MEMBERS)
                ->schema(self::teamMemberComponents())
                ->columns(2),
            Section::make('Service fields')
                ->description('Structured metadata for Services.')
                ->visible(fn (Get $get): bool => (string) ($get('post_type') ?: 'post') === self::SERVICES)
                ->schema(self::serviceComponents())
                ->columns(2),
            Section::make('Product fields')
                ->description('Structured metadata for Products.')
                ->visible(fn (Get $get): bool => (string) ($get('post_type') ?: 'post') === self::PRODUCTS)
                ->schema(self::productComponents())
                ->columns(2),
            Section::make('Testimonial fields')
                ->description('Structured metadata for Testimonials.')
                ->visible(fn (Get $get): bool => (string) ($get('post_type') ?: 'post') === self::TESTIMONIALS)
                ->schema(self::testimonialComponents())
                ->columns(2),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $input
     * @return array<string, mixed>|null
     */
    public static function sanitize(string $postType, ?array $input): ?array
    {
        if (! self::hasSchema($postType)) {
            return null;
        }

        $input ??= [];

        return match ($postType) {
            self::TEAM_MEMBERS => self::sanitizeTeam($input),
            self::SERVICES => self::sanitizeService($input),
            self::PRODUCTS => self::sanitizeProduct($input),
            self::TESTIMONIALS => self::sanitizeTestimonial($input),
            default => null,
        };
    }

    /**
     * @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component>
     */
    private static function teamMemberComponents(): array
    {
        return [
            TextInput::make('custom_fields.job_title')
                ->label('Job Title / Position')
                ->maxLength(255)
                ->required(),
            TextInput::make('custom_fields.department')
                ->label('Committee / Department')
                ->maxLength(255),
            Textarea::make('custom_fields.bio')
                ->label('Bio')
                ->rows(4)
                ->columnSpanFull(),
            Repeater::make('custom_fields.social_links')
                ->label('Social Profile Links')
                ->schema([
                    TextInput::make('label')->label('Network')->required()->maxLength(100),
                    TextInput::make('url')->label('URL')->url()->required()->maxLength(500),
                ])
                ->default([])
                ->columnSpanFull()
                ->addActionLabel('Add social link'),
            ...array_map(
                fn ($component) => $component,
                MediaLibraryImageSelect::make(
                    name: 'custom_fields.headshot_id',
                    label: 'Headshot',
                    helperText: 'Portrait image from the media library.',
                ),
            ),
        ];
    }

    /**
     * @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component>
     */
    private static function serviceComponents(): array
    {
        return [
            ...array_map(
                fn ($component) => $component,
                MediaLibraryImageSelect::make(
                    name: 'custom_fields.icon_id',
                    label: 'Service Icon',
                    helperText: 'Icon or illustrative image for this service.',
                ),
            ),
            TagsInput::make('custom_fields.key_benefits')
                ->label('Key Benefits')
                ->placeholder('Add a benefit')
                ->columnSpanFull(),
            TagsInput::make('custom_fields.feature_highlights')
                ->label('Feature Highlights')
                ->placeholder('Add a feature')
                ->columnSpanFull(),
            Textarea::make('custom_fields.eligibility')
                ->label('Eligibility / Requirements')
                ->rows(3)
                ->columnSpanFull(),
            TextInput::make('custom_fields.cta_text')
                ->label('CTA button text')
                ->maxLength(100),
            TextInput::make('custom_fields.cta_url')
                ->label('CTA button link')
                ->url()
                ->maxLength(500),
        ];
    }

    /**
     * @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component>
     */
    private static function productComponents(): array
    {
        return [
            TextInput::make('custom_fields.sku')
                ->label('SKU')
                ->maxLength(100),
            TextInput::make('custom_fields.price')
                ->label('Pricing')
                ->maxLength(100)
                ->helperText('Display price, e.g. $49 / year or Contact for quote.'),
            Repeater::make('custom_fields.specifications')
                ->label('Specifications')
                ->schema([
                    TextInput::make('label')->label('Name')->required()->maxLength(100),
                    TextInput::make('value')->label('Value')->required()->maxLength(255),
                ])
                ->default([])
                ->columnSpanFull()
                ->addActionLabel('Add specification'),
            Select::make('custom_fields.gallery_ids')
                ->label('Image Gallery')
                ->multiple()
                ->searchable()
                ->options(fn (): array => self::imageMediaOptions())
                ->helperText('Select one or more images from the media library.')
                ->columnSpanFull(),
            TextInput::make('custom_fields.brochure_url')
                ->label('Downloadable Brochure / Datasheet URL')
                ->url()
                ->maxLength(500)
                ->helperText('Public URL to a PDF or datasheet.'),
            Select::make('custom_fields.availability')
                ->label('Availability status')
                ->options([
                    'in_stock' => 'In stock',
                    'limited' => 'Limited',
                    'out_of_stock' => 'Out of stock',
                    'preorder' => 'Pre-order',
                ])
                ->native(false),
        ];
    }

    /**
     * @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component>
     */
    private static function testimonialComponents(): array
    {
        return [
            Textarea::make('custom_fields.quote')
                ->label('Quote')
                ->rows(4)
                ->required()
                ->columnSpanFull(),
            TextInput::make('custom_fields.author_name')
                ->label('Author name')
                ->maxLength(255)
                ->required(),
            TextInput::make('custom_fields.author_role')
                ->label('Author role / title')
                ->maxLength(255),
            TextInput::make('custom_fields.company')
                ->label('Company / Organization')
                ->maxLength(255),
            Select::make('custom_fields.rating')
                ->label('Rating')
                ->options([
                    5 => '5 stars',
                    4 => '4 stars',
                    3 => '3 stars',
                    2 => '2 stars',
                    1 => '1 star',
                ])
                ->native(false),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function imageMediaOptions(): array
    {
        return \App\Models\MediaAsset::query()
            ->where('mime_type', 'like', 'image/%')
            ->where('mime_type', '!=', 'image/svg+xml')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->mapWithKeys(fn ($asset): array => [
                $asset->id => trim(($asset->title ?: 'Untitled').' (#'.$asset->id.')'),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private static function sanitizeTeam(array $input): array
    {
        $social = [];
        foreach ((array) ($input['social_links'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $url = trim((string) ($row['url'] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }
            $social[] = ['label' => mb_substr($label, 0, 100), 'url' => mb_substr($url, 0, 500)];
        }

        return [
            'job_title' => self::nullableTrim($input['job_title'] ?? null, 255),
            'department' => self::nullableTrim($input['department'] ?? null, 255),
            'bio' => self::nullableTrim($input['bio'] ?? null),
            'social_links' => $social,
            'headshot_id' => self::nullableInt($input['headshot_id'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private static function sanitizeService(array $input): array
    {
        return [
            'icon_id' => self::nullableInt($input['icon_id'] ?? null),
            'key_benefits' => self::stringList($input['key_benefits'] ?? []),
            'feature_highlights' => self::stringList($input['feature_highlights'] ?? []),
            'eligibility' => self::nullableTrim($input['eligibility'] ?? null),
            'cta_text' => self::nullableTrim($input['cta_text'] ?? null, 100),
            'cta_url' => self::nullableTrim($input['cta_url'] ?? null, 500),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private static function sanitizeProduct(array $input): array
    {
        $specs = [];
        foreach ((array) ($input['specifications'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }
            $specs[] = ['label' => mb_substr($label, 0, 100), 'value' => mb_substr($value, 0, 255)];
        }

        $gallery = [];
        foreach ((array) ($input['gallery_ids'] ?? []) as $id) {
            $int = self::nullableInt($id);
            if ($int !== null) {
                $gallery[] = $int;
            }
        }

        $availability = (string) ($input['availability'] ?? '');
        if (! in_array($availability, ['in_stock', 'limited', 'out_of_stock', 'preorder'], true)) {
            $availability = null;
        }

        return [
            'sku' => self::nullableTrim($input['sku'] ?? null, 100),
            'price' => self::nullableTrim($input['price'] ?? null, 100),
            'specifications' => $specs,
            'gallery_ids' => array_values(array_unique($gallery)),
            'brochure_url' => self::nullableTrim($input['brochure_url'] ?? null, 500),
            'availability' => $availability,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private static function sanitizeTestimonial(array $input): array
    {
        $rating = self::nullableInt($input['rating'] ?? null);
        if ($rating !== null) {
            $rating = max(1, min(5, $rating));
        }

        return [
            'quote' => self::nullableTrim($input['quote'] ?? null),
            'author_name' => self::nullableTrim($input['author_name'] ?? null, 255),
            'author_role' => self::nullableTrim($input['author_role'] ?? null, 255),
            'company' => self::nullableTrim($input['company'] ?? null, 255),
            'rating' => $rating,
        ];
    }

    private static function nullableTrim(mixed $value, ?int $max = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        return $max !== null ? mb_substr($text, 0, $max) : $text;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        $items = [];
        foreach ((array) $value as $item) {
            $text = trim((string) $item);
            if ($text === '') {
                continue;
            }
            $items[] = mb_substr($text, 0, 255);
        }

        return array_values(array_unique($items));
    }
}
