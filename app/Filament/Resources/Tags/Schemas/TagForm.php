<?php

namespace App\Filament\Resources\Tags\Schemas;

use App\Support\Settings\PermalinkSettings;
use App\Support\SlugGenerator;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Tag names are unique case-insensitively (e.g. Laravel matches laravel).')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                        if (! app(PermalinkSettings::class)->autoGenerateSlugs()) {
                            return;
                        }

                        if (filled($get('slug'))) {
                            return;
                        }

                        if (blank($state)) {
                            return;
                        }

                        $set('slug', SlugGenerator::sanitize($state));
                    }),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required(fn (): bool => ! app(PermalinkSettings::class)->autoGenerateSlugs())
                    ->maxLength(255)
                    ->helperText('URL-friendly identifier. Conflicts get a numeric suffix.')
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? SlugGenerator::sanitize($state) : $state),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }
}
