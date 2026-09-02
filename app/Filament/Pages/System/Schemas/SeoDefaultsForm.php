<?php

namespace App\Filament\Pages\System\Schemas;

use App\Support\Settings\SeoDefaultsSettings;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\Rule;

class SeoDefaultsForm
{
    /**
     * @return array<int, Section>
     */
    public static function components(): array
    {
        $ogHelper = SeoDefaultsSettings::ogImageOptions() === []
            ? 'No images in the media library yet. Upload images via DAM (Phase 4) to set a fallback OG image.'
            : 'Fallback og:image when content has no OG image or featured image.';

        return [
            Section::make('Meta defaults')
                ->description('Fallback values when content-level SEO fields are empty (SRS 12.5.3 inheritance).')
                ->schema([
                    TextInput::make(SeoDefaultsSettings::META_TITLE_PATTERN)
                        ->label('Default Meta Title Pattern')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Tokens: {title}, {site_title}. Example: {title} | {site_title}')
                        ->autocomplete(false),
                    Textarea::make(SeoDefaultsSettings::META_DESCRIPTION)
                        ->label('Default Meta Description')
                        ->rows(3)
                        ->maxLength(500)
                        ->helperText('Used when a content item has no meta description. Max 500 characters (160 recommended).'),
                ])
                ->columns(1),
            Section::make('Open Graph & schema')
                ->schema([
                    Select::make(SeoDefaultsSettings::OG_IMAGE_ID)
                        ->label('Default OG Image')
                        ->options(fn (): array => SeoDefaultsSettings::ogImageOptions())
                        ->searchable()
                        ->nullable()
                        ->placeholder('— Not set —')
                        ->helperText($ogHelper)
                        ->rules(self::ogImageRules()),
                    Select::make(SeoDefaultsSettings::SCHEMA_TYPE)
                        ->label('Default Schema Type')
                        ->options(SeoDefaultsSettings::schemaTypeOptions())
                        ->required()
                        ->live()
                        ->helperText('WebPage is recommended for general pages.'),
                    TextInput::make('custom_schema_type')
                        ->label('Custom Schema Type')
                        ->maxLength(100)
                        ->visible(fn (Get $get): bool => $get(SeoDefaultsSettings::SCHEMA_TYPE) === 'Custom')
                        ->required(fn (Get $get): bool => $get(SeoDefaultsSettings::SCHEMA_TYPE) === 'Custom')
                        ->helperText('Schema.org type name for advanced use.'),
                ])
                ->columns(1),
            Section::make('Robots')
                ->schema([
                    CheckboxList::make(SeoDefaultsSettings::ROBOTS)
                        ->label('Default Robots')
                        ->options(SeoDefaultsSettings::robotsOptions())
                        ->required()
                        ->columns(2)
                        ->helperText('Default robots directives when content does not override them (e.g. index, follow).'),
                ])
                ->columns(1),
        ];
    }

    /**
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    public static function ogImageRules(): array
    {
        if (! SeoDefaultsSettings::mediaAssetsTableReady()) {
            return ['nullable'];
        }

        return [
            'nullable',
            'integer',
            Rule::exists('media_assets', 'id'),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public static function prepareFillState(array $settings): array
    {
        $known = array_keys(SeoDefaultsSettings::schemaTypeOptions());

        if (! in_array($settings[SeoDefaultsSettings::SCHEMA_TYPE] ?? null, $known, true)) {
            $settings['custom_schema_type'] = $settings[SeoDefaultsSettings::SCHEMA_TYPE];
            $settings[SeoDefaultsSettings::SCHEMA_TYPE] = 'Custom';
        } else {
            $settings['custom_schema_type'] = null;
        }

        return $settings;
    }
}
