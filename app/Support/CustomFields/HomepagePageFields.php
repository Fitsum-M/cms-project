<?php

namespace App\Support\CustomFields;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\Page;

/**
 * Homepage chrome stored on the `home-hero` page (Review Comment #3 §10 — no Blade hard-coded copy).
 */
final class HomepagePageFields
{
    public const HOME_HERO_SLUG = 'home-hero';

    public const HOME_NAV_SLUG = 'home';

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    public static function formSections(): array
    {
        return [
            Section::make('Homepage hero & CTAs')
                ->description('Used by the public homepage. Prefer CMS URLs (e.g. /types/services or /pages/membership).')
                ->visible(fn (Get $get, ?Page $record): bool => self::isHomeHero($get, $record))
                ->schema([
                    TextInput::make('custom_fields.primary_cta_label')->label('Primary CTA label')->maxLength(100),
                    TextInput::make('custom_fields.primary_cta_url')->label('Primary CTA URL')->maxLength(500),
                    TextInput::make('custom_fields.secondary_cta_label')->label('Secondary CTA label')->maxLength(100),
                    TextInput::make('custom_fields.secondary_cta_url')->label('Secondary CTA URL')->maxLength(500),
                ])
                ->columns(2),
            Section::make('Homepage section copy')
                ->description('Eyebrows, headings, intros, and “view all” labels for each homepage block.')
                ->visible(fn (Get $get, ?Page $record): bool => self::isHomeHero($get, $record))
                ->schema([
                    TextInput::make('custom_fields.about_heading')->label('About section heading')->maxLength(255),
                    TextInput::make('custom_fields.about_link_label')->label('About link label')->maxLength(100),
                    Repeater::make('custom_fields.foundation_facts')
                        ->label('Foundation facts')
                        ->schema([
                            TextInput::make('label')->label('Label')->required()->maxLength(100),
                            TextInput::make('value')->label('Value')->required()->maxLength(255),
                        ])
                        ->default([])
                        ->columnSpanFull()
                        ->addActionLabel('Add fact'),
                    TextInput::make('custom_fields.services_eyebrow')->label('Services eyebrow')->maxLength(100),
                    TextInput::make('custom_fields.services_heading')->label('Services heading')->maxLength(255),
                    Textarea::make('custom_fields.services_intro')->label('Services intro')->rows(2)->columnSpanFull(),
                    TextInput::make('custom_fields.services_all_label')->label('Services “view all” label')->maxLength(100),
                    TextInput::make('custom_fields.saccos_eyebrow')->label('SACCOs eyebrow')->maxLength(100),
                    TextInput::make('custom_fields.saccos_heading')->label('SACCOs heading')->helperText('Use :count for the live SACCO count.')->maxLength(255),
                    Textarea::make('custom_fields.saccos_intro')->label('SACCOs intro')->rows(2)->columnSpanFull(),
                    TextInput::make('custom_fields.saccos_all_label')->label('SACCOs “view all” label')->maxLength(100),
                    TextInput::make('custom_fields.join_eyebrow')->label('Join steps eyebrow')->maxLength(100),
                    TextInput::make('custom_fields.join_heading')->label('Join steps heading')->maxLength(255),
                    TextInput::make('custom_fields.leadership_eyebrow')->label('Leadership eyebrow')->maxLength(100),
                    TextInput::make('custom_fields.leadership_link_label')->label('Leadership link label')->maxLength(100),
                    TextInput::make('custom_fields.faqs_eyebrow')->label('FAQs eyebrow')->maxLength(100),
                    TextInput::make('custom_fields.faqs_heading')->label('FAQs heading')->maxLength(255),
                    TextInput::make('custom_fields.impact_eyebrow')->label('Impact eyebrow')->maxLength(100),
                    TextInput::make('custom_fields.impact_heading')->label('Impact heading')->maxLength(255),
                    TextInput::make('custom_fields.impact_all_label')->label('Impact “view all” label')->maxLength(100),
                    TextInput::make('custom_fields.resources_eyebrow')->label('Resources eyebrow')->maxLength(100),
                    TextInput::make('custom_fields.resources_heading')->label('Resources heading')->maxLength(255),
                    TextInput::make('custom_fields.news_eyebrow')->label('News eyebrow')->maxLength(100),
                    TextInput::make('custom_fields.news_heading')->label('News heading')->maxLength(255),
                    TextInput::make('custom_fields.news_all_label')->label('News “view all” label')->maxLength(100),
                ])
                ->columns(2),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $input
     * @return array<string, mixed>|null
     */
    public static function sanitize(?array $input): ?array
    {
        if ($input === null) {
            return null;
        }

        $facts = [];
        foreach ((array) ($input['foundation_facts'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }
            $facts[] = [
                'label' => mb_substr($label, 0, 100),
                'value' => mb_substr($value, 0, 255),
            ];
        }

        $string = static function (mixed $value, int $max = 255): ?string {
            if ($value === null) {
                return null;
            }
            $text = trim((string) $value);

            return $text === '' ? null : mb_substr($text, 0, $max);
        };

        return [
            'primary_cta_label' => $string($input['primary_cta_label'] ?? null, 100),
            'primary_cta_url' => $string($input['primary_cta_url'] ?? null, 500),
            'secondary_cta_label' => $string($input['secondary_cta_label'] ?? null, 100),
            'secondary_cta_url' => $string($input['secondary_cta_url'] ?? null, 500),
            'about_heading' => $string($input['about_heading'] ?? null),
            'about_link_label' => $string($input['about_link_label'] ?? null, 100),
            'foundation_facts' => $facts,
            'services_eyebrow' => $string($input['services_eyebrow'] ?? null, 100),
            'services_heading' => $string($input['services_heading'] ?? null),
            'services_intro' => $string($input['services_intro'] ?? null, 2000),
            'services_all_label' => $string($input['services_all_label'] ?? null, 100),
            'saccos_eyebrow' => $string($input['saccos_eyebrow'] ?? null, 100),
            'saccos_heading' => $string($input['saccos_heading'] ?? null),
            'saccos_intro' => $string($input['saccos_intro'] ?? null, 2000),
            'saccos_all_label' => $string($input['saccos_all_label'] ?? null, 100),
            'join_eyebrow' => $string($input['join_eyebrow'] ?? null, 100),
            'join_heading' => $string($input['join_heading'] ?? null),
            'leadership_eyebrow' => $string($input['leadership_eyebrow'] ?? null, 100),
            'leadership_link_label' => $string($input['leadership_link_label'] ?? null, 100),
            'faqs_eyebrow' => $string($input['faqs_eyebrow'] ?? null, 100),
            'faqs_heading' => $string($input['faqs_heading'] ?? null),
            'impact_eyebrow' => $string($input['impact_eyebrow'] ?? null, 100),
            'impact_heading' => $string($input['impact_heading'] ?? null),
            'impact_all_label' => $string($input['impact_all_label'] ?? null, 100),
            'resources_eyebrow' => $string($input['resources_eyebrow'] ?? null, 100),
            'resources_heading' => $string($input['resources_heading'] ?? null),
            'news_eyebrow' => $string($input['news_eyebrow'] ?? null, 100),
            'news_heading' => $string($input['news_heading'] ?? null),
            'news_all_label' => $string($input['news_all_label'] ?? null, 100),
        ];
    }

    private static function isHomeHero(Get $get, ?Page $record): bool
    {
        $slug = (string) ($get('slug') ?: ($record?->slug ?? ''));

        return $slug === self::HOME_HERO_SLUG;
    }
}
