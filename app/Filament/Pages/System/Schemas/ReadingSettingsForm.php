<?php

namespace App\Filament\Pages\System\Schemas;

use App\Support\Settings\ReadingSettings;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Validation\Rule;

class ReadingSettingsForm
{
    /**
     * @return array<int, Section>
     */
    public static function components(): array
    {
        $pagesReady = ReadingSettings::pagesTableReady();
        $pageHelper = $pagesReady
            ? 'Select a CMS page.'
            : 'No pages available yet — page references unlock after Pages are implemented (Phase 5).';

        return [
            Section::make('Front page display')
                ->description('Choose which pages serve as the site homepage and posts listing.')
                ->schema([
                    Select::make(ReadingSettings::HOMEPAGE_PAGE_ID)
                        ->label('Homepage')
                        ->options(fn (): array => ReadingSettings::pageOptions())
                        ->searchable()
                        ->nullable()
                        ->placeholder('— Not set —')
                        ->helperText($pageHelper)
                        ->rules(self::pageReferenceRules()),
                    Select::make(ReadingSettings::POSTS_PAGE_ID)
                        ->label('Posts Page')
                        ->options(fn (): array => ReadingSettings::pageOptions())
                        ->searchable()
                        ->nullable()
                        ->placeholder('— Not set —')
                        ->helperText($pageHelper)
                        ->rules(self::pageReferenceRules()),
                ])
                ->columns(1),
            Section::make('Pagination')
                ->schema([
                    TextInput::make(ReadingSettings::POSTS_PER_PAGE)
                        ->label('Posts Per Page')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1)
                        ->maxValue(100)
                        ->helperText('Default number of posts per listing page (1–100).'),
                ])
                ->columns(1),
        ];
    }

    /**
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    public static function pageReferenceRules(): array
    {
        if (! ReadingSettings::pagesTableReady()) {
            return ['nullable'];
        }

        return [
            'nullable',
            'integer',
            Rule::exists('pages', 'id'),
        ];
    }
}
