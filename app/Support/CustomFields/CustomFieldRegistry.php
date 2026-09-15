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
 * Code-defined custom-field schemas per CPT slug (Review Comment #3 §§9–10).
 */
final class CustomFieldRegistry
{
    public const TEAM_MEMBERS = 'team-members';

    public const SERVICES = 'services';

    public const PRODUCTS = 'products';

    public const TESTIMONIALS = 'testimonials';

    public const STATS = 'stats';

    public const IMPACT_STORIES = 'impact-stories';

    public const RESOURCES = 'resources';

    public const FAQS = 'faqs';

    public const MEMBER_SACCOS = 'member-saccos';

    public const JOIN_STEPS = 'join-steps';

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
            self::STATS,
            self::IMPACT_STORIES,
            self::RESOURCES,
            self::FAQS,
            self::MEMBER_SACCOS,
            self::JOIN_STEPS,
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
            Section::make('Stat / metric fields')
                ->description('Homepage and about-page metrics.')
                ->visible(fn (Get $get): bool => (string) ($get('post_type') ?: 'post') === self::STATS)
                ->schema(self::statComponents())
                ->columns(2),
            Section::make('Impact story fields')
                ->description('Awards, recognition, and impact highlights.')
                ->visible(fn (Get $get): bool => (string) ($get('post_type') ?: 'post') === self::IMPACT_STORIES)
                ->schema(self::impactStoryComponents())
                ->columns(2),
            Section::make('Resource fields')
                ->description('Downloadable or linked resources.')
                ->visible(fn (Get $get): bool => (string) ($get('post_type') ?: 'post') === self::RESOURCES)
                ->schema(self::resourceComponents())
                ->columns(2),
            Section::make('FAQ fields')
                ->description('Frequently asked questions.')
                ->visible(fn (Get $get): bool => (string) ($get('post_type') ?: 'post') === self::FAQS)
                ->schema(self::faqComponents())
                ->columns(2),
            Section::make('Member SACCO fields')
                ->description('Primary cooperatives in the union.')
                ->visible(fn (Get $get): bool => (string) ($get('post_type') ?: 'post') === self::MEMBER_SACCOS)
                ->schema(self::memberSaccoComponents())
                ->columns(2),
            Section::make('Join step fields')
                ->description('How-to-join process steps.')
                ->visible(fn (Get $get): bool => (string) ($get('post_type') ?: 'post') === self::JOIN_STEPS)
                ->schema(self::joinStepComponents())
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
            self::STATS => self::sanitizeStat($input),
            self::IMPACT_STORIES => self::sanitizeImpact($input),
            self::RESOURCES => self::sanitizeResource($input),
            self::FAQS => self::sanitizeFaq($input),
            self::MEMBER_SACCOS => self::sanitizeMemberSacco($input),
            self::JOIN_STEPS => self::sanitizeJoinStep($input),
            default => null,
        };
    }

    /** @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component> */
    private static function teamMemberComponents(): array
    {
        return [
            TextInput::make('custom_fields.job_title')->label('Job Title / Position')->maxLength(255)->required(),
            TextInput::make('custom_fields.department')->label('Committee / Department')->maxLength(255),
            Textarea::make('custom_fields.bio')->label('Bio')->rows(4)->columnSpanFull(),
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

    /** @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component> */
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
            TextInput::make('custom_fields.display_order')->label('Display order')->numeric()->minValue(1)->maxValue(99),
            TagsInput::make('custom_fields.key_benefits')->label('Key Benefits')->placeholder('Add a benefit')->columnSpanFull(),
            TagsInput::make('custom_fields.feature_highlights')->label('Feature Highlights')->placeholder('Add a feature')->columnSpanFull(),
            Textarea::make('custom_fields.eligibility')->label('Eligibility / Requirements')->rows(3)->columnSpanFull(),
            TextInput::make('custom_fields.cta_text')->label('CTA button text')->maxLength(100),
            TextInput::make('custom_fields.cta_url')->label('CTA button link')->url()->maxLength(500),
        ];
    }

    /** @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component> */
    private static function productComponents(): array
    {
        return [
            TextInput::make('custom_fields.sku')->label('SKU')->maxLength(100),
            TextInput::make('custom_fields.price')->label('Pricing')->maxLength(100),
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
                ->columnSpanFull(),
            TextInput::make('custom_fields.brochure_url')->label('Downloadable Brochure / Datasheet URL')->url()->maxLength(500),
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

    /** @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component> */
    private static function testimonialComponents(): array
    {
        return [
            Textarea::make('custom_fields.quote')->label('Quote')->rows(4)->required()->columnSpanFull(),
            TextInput::make('custom_fields.author_name')->label('Author name')->maxLength(255)->required(),
            TextInput::make('custom_fields.author_role')->label('Author role / title')->maxLength(255),
            TextInput::make('custom_fields.company')->label('Company / Organization')->maxLength(255),
            Select::make('custom_fields.rating')
                ->label('Rating')
                ->options([5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star'])
                ->native(false),
        ];
    }

    /** @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component> */
    private static function statComponents(): array
    {
        return [
            TextInput::make('custom_fields.value')->label('Metric value')->required()->maxLength(100)->helperText('e.g. 21,920+'),
            TextInput::make('custom_fields.label')->label('Metric label')->required()->maxLength(255),
            TextInput::make('custom_fields.display_order')->label('Display order')->numeric()->minValue(1)->maxValue(99),
            Textarea::make('custom_fields.helper_text')->label('Supporting text')->rows(2)->columnSpanFull(),
        ];
    }

    /** @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component> */
    private static function impactStoryComponents(): array
    {
        return [
            TextInput::make('custom_fields.issuer')->label('Issuer / Organization')->maxLength(255),
            TextInput::make('custom_fields.year')->label('Year')->maxLength(50),
            TextInput::make('custom_fields.location')->label('Location')->maxLength(255),
            Textarea::make('custom_fields.summary')->label('Summary')->rows(3)->columnSpanFull(),
        ];
    }

    /** @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component> */
    private static function resourceComponents(): array
    {
        return [
            Select::make('custom_fields.resource_type')
                ->label('Resource type')
                ->options([
                    'pdf' => 'PDF / Document',
                    'link' => 'External link',
                    'form' => 'Form',
                    'guide' => 'Guide',
                ])
                ->native(false),
            TextInput::make('custom_fields.category')->label('Category')->maxLength(100),
            TextInput::make('custom_fields.file_url')->label('File / link URL')->url()->maxLength(500)->required(),
            TextInput::make('custom_fields.cta_text')->label('CTA label')->maxLength(100)->default('Download'),
        ];
    }

    /** @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component> */
    private static function faqComponents(): array
    {
        return [
            Textarea::make('custom_fields.answer')->label('Answer')->rows(4)->required()->columnSpanFull(),
            TextInput::make('custom_fields.display_order')->label('Display order')->numeric()->minValue(1)->maxValue(99),
        ];
    }

    /** @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component> */
    private static function memberSaccoComponents(): array
    {
        return [
            TextInput::make('custom_fields.location')->label('Woreda / Location')->maxLength(255),
            TextInput::make('custom_fields.initials')->label('Initials')->maxLength(10),
            TextInput::make('custom_fields.membership_label')->label('Membership label')->maxLength(100)->default('Member SACCO'),
        ];
    }

    /** @return list<\Filament\Forms\Components\Field|\Filament\Schemas\Components\Component> */
    private static function joinStepComponents(): array
    {
        return [
            TextInput::make('custom_fields.step_number')->label('Step number')->numeric()->minValue(1)->maxValue(20)->required(),
            Textarea::make('custom_fields.summary')->label('Step summary')->rows(3)->required()->columnSpanFull(),
        ];
    }

    /** @return array<int, string> */
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

    /** @param  array<string, mixed>  $input @return array<string, mixed> */
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

    /** @param  array<string, mixed>  $input @return array<string, mixed> */
    private static function sanitizeService(array $input): array
    {
        return [
            'icon_id' => self::nullableInt($input['icon_id'] ?? null),
            'display_order' => self::nullableInt($input['display_order'] ?? null),
            'key_benefits' => self::stringList($input['key_benefits'] ?? []),
            'feature_highlights' => self::stringList($input['feature_highlights'] ?? []),
            'eligibility' => self::nullableTrim($input['eligibility'] ?? null),
            'cta_text' => self::nullableTrim($input['cta_text'] ?? null, 100),
            'cta_url' => self::nullableTrim($input['cta_url'] ?? null, 500),
        ];
    }

    /** @param  array<string, mixed>  $input @return array<string, mixed> */
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

    /** @param  array<string, mixed>  $input @return array<string, mixed> */
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

    /** @param  array<string, mixed>  $input @return array<string, mixed> */
    private static function sanitizeStat(array $input): array
    {
        return [
            'value' => self::nullableTrim($input['value'] ?? null, 100),
            'label' => self::nullableTrim($input['label'] ?? null, 255),
            'helper_text' => self::nullableTrim($input['helper_text'] ?? null),
            'display_order' => self::nullableInt($input['display_order'] ?? null),
        ];
    }

    /** @param  array<string, mixed>  $input @return array<string, mixed> */
    private static function sanitizeImpact(array $input): array
    {
        return [
            'issuer' => self::nullableTrim($input['issuer'] ?? null, 255),
            'year' => self::nullableTrim($input['year'] ?? null, 50),
            'location' => self::nullableTrim($input['location'] ?? null, 255),
            'summary' => self::nullableTrim($input['summary'] ?? null),
        ];
    }

    /** @param  array<string, mixed>  $input @return array<string, mixed> */
    private static function sanitizeResource(array $input): array
    {
        $type = (string) ($input['resource_type'] ?? 'link');
        if (! in_array($type, ['pdf', 'link', 'form', 'guide'], true)) {
            $type = 'link';
        }

        return [
            'resource_type' => $type,
            'category' => self::nullableTrim($input['category'] ?? null, 100),
            'file_url' => self::nullableTrim($input['file_url'] ?? null, 500),
            'cta_text' => self::nullableTrim($input['cta_text'] ?? null, 100) ?? 'Download',
        ];
    }

    /** @param  array<string, mixed>  $input @return array<string, mixed> */
    private static function sanitizeFaq(array $input): array
    {
        return [
            'answer' => self::nullableTrim($input['answer'] ?? null),
            'display_order' => self::nullableInt($input['display_order'] ?? null),
        ];
    }

    /** @param  array<string, mixed>  $input @return array<string, mixed> */
    private static function sanitizeMemberSacco(array $input): array
    {
        return [
            'location' => self::nullableTrim($input['location'] ?? null, 255),
            'initials' => self::nullableTrim($input['initials'] ?? null, 10),
            'membership_label' => self::nullableTrim($input['membership_label'] ?? null, 100) ?? 'Member SACCO',
        ];
    }

    /** @param  array<string, mixed>  $input @return array<string, mixed> */
    private static function sanitizeJoinStep(array $input): array
    {
        return [
            'step_number' => self::nullableInt($input['step_number'] ?? null),
            'summary' => self::nullableTrim($input['summary'] ?? null),
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

    /** @return list<string> */
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
