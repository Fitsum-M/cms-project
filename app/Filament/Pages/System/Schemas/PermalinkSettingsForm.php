<?php

namespace App\Filament\Pages\System\Schemas;

use App\Enums\SlugConflictResolution;
use App\Support\Settings\PermalinkSettings;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Illuminate\Validation\Rule;

class PermalinkSettingsForm
{
    /**
     * @return array<int, Section>
     */
    public static function components(): array
    {
        return [
            Section::make('URL structures')
                ->description('Patterns used when generating public URLs for posts and pages. Every pattern must include {slug}.')
                ->schema([
                    Select::make(PermalinkSettings::POST_URL_STRUCTURE)
                        ->label('URL Structure')
                        ->options(PermalinkSettings::postUrlStructureOptions())
                        ->required()
                        ->helperText('Default: /{post-type}/{slug}/'),
                    Select::make(PermalinkSettings::PAGE_URL_STRUCTURE)
                        ->label('Page URL Structure')
                        ->options(PermalinkSettings::pageUrlStructureOptions())
                        ->required()
                        ->helperText('Controls whether child pages nest under the parent slug.'),
                ])
                ->columns(1),
            Section::make('Slug behavior')
                ->schema([
                    Toggle::make(PermalinkSettings::AUTO_GENERATE_SLUGS)
                        ->label('Slug Generation')
                        ->helperText('Auto-generate slugs from the title on save.')
                        ->required(),
                    Select::make(PermalinkSettings::CONFLICT_RESOLUTION)
                        ->label('Conflict Resolution')
                        ->options(SlugConflictResolution::options())
                        ->required()
                        ->rule(Rule::enum(SlugConflictResolution::class))
                        ->helperText('What happens when a generated or edited slug already exists.'),
                ])
                ->columns(1),
        ];
    }
}
