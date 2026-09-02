<?php

namespace App\Filament\Pages\System\Schemas;

use App\Support\Settings\GeneralSettings;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class GeneralSettingsForm
{
    /**
     * @return array<int, Section>
     */
    public static function components(): array
    {
        return [
            Section::make('Site identity')
                ->description('Primary site name and short description used across the CMS.')
                ->schema([
                    TextInput::make(GeneralSettings::SITE_TITLE)
                        ->label('Site Title')
                        ->required()
                        ->maxLength(255)
                        ->autocomplete(false),
                    TextInput::make(GeneralSettings::TAGLINE)
                        ->label('Tagline')
                        ->maxLength(255)
                        ->autocomplete(false),
                ])
                ->columns(1),
            Section::make('Date & time')
                ->description('System-wide timezone and display formats for dates and times.')
                ->schema([
                    Select::make(GeneralSettings::TIMEZONE)
                        ->label('Timezone')
                        ->options(GeneralSettings::timezoneOptions())
                        ->searchable()
                        ->required(),
                    Select::make(GeneralSettings::DATE_FORMAT)
                        ->label('Date Format')
                        ->options(GeneralSettings::dateFormatOptions())
                        ->required(),
                    Select::make(GeneralSettings::TIME_FORMAT)
                        ->label('Time Format')
                        ->options(GeneralSettings::timeFormatOptions())
                        ->required(),
                ])
                ->columns(1),
        ];
    }
}
