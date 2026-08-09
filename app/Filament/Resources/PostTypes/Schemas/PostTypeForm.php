<?php

namespace App\Filament\Resources\PostTypes\Schemas;

use App\Models\CustomTaxonomy;
use App\Models\PostType;
use App\Services\PostTypeService;
use App\Support\Settings\PermalinkSettings;
use App\Support\SlugGenerator;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as DbSchema;

class PostTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Labels')
                    ->schema([
                        TextInput::make('plural_name')
                            ->label('Plural Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Shown in navigation and listing headers (e.g. Case Studies).')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get, ?PostType $record): void {
                                if (! app(PermalinkSettings::class)->autoGenerateSlugs()) {
                                    return;
                                }

                                if ($record !== null) {
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
                        TextInput::make('singular_name')
                            ->label('Singular Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Shown in form headers and actions (e.g. Case Study).'),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required(fn (): bool => ! app(PermalinkSettings::class)->autoGenerateSlugs())
                            ->maxLength(255)
                            ->helperText('URL prefix for this type. Must be unique and cannot use reserved routes (post, page, admin, …). Changing the slug updates existing content of this type.')
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                                ? SlugGenerator::sanitize($state)
                                : $state)
                            ->disabled(fn (?PostType $record): bool => $record !== null && $record->hasAssignedContent())
                            ->dehydrated(),
                        Select::make('icon')
                            ->label('Menu Icon')
                            ->options(PostTypeService::iconOptions())
                            ->searchable()
                            ->nullable()
                            ->placeholder('— Default stack icon —')
                            ->helperText('Optional icon for the Content → Posts navigation submenu.'),
                    ])
                    ->columns(2),
                Section::make('Taxonomies')
                    ->description('Only associated taxonomies appear in the editor for this post type (SRS 12.4.4).')
                    ->schema([
                        Toggle::make('supports_categories')
                            ->label('Categories')
                            ->helperText('Allow assigning categories to content of this type.')
                            ->default(true),
                        Toggle::make('supports_tags')
                            ->label('Tags')
                            ->helperText('Allow assigning tags to content of this type.')
                            ->default(true),
                        CheckboxList::make('custom_taxonomy_ids')
                            ->label('Custom Taxonomies')
                            ->options(fn (): array => self::customTaxonomyOptions())
                            ->columns(2)
                            ->helperText('Select which custom taxonomies appear for this type. Associations are also editable from each taxonomy’s settings.')
                            ->visible(fn (): bool => self::customTaxonomyOptions() !== [])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function customTaxonomyOptions(): array
    {
        if (! DbSchema::hasTable('custom_taxonomies')) {
            return [];
        }

        return CustomTaxonomy::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
