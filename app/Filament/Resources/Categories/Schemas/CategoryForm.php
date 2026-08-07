<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Services\CategoryService;
use App\Support\Settings\PermalinkSettings;
use App\Support\SlugGenerator;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
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
                    ->helperText('URL-friendly identifier. Unique across all categories; conflicts get a numeric suffix.')
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? SlugGenerator::sanitize($state) : $state),
                Select::make('parent_id')
                    ->label('Parent Category')
                    ->options(function (?Category $record): array {
                        return app(CategoryService::class)->parentOptions($record?->id);
                    })
                    ->searchable()
                    ->nullable()
                    ->placeholder('— Root level —')
                    ->helperText('A category cannot be its own parent or descendant.'),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }
}
