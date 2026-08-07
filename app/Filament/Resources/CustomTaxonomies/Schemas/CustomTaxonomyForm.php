<?php

namespace App\Filament\Resources\CustomTaxonomies\Schemas;

use App\Enums\TaxonomyStructure;
use App\Support\PostTypeRegistry;
use App\Support\Settings\PermalinkSettings;
use App\Support\SlugGenerator;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class CustomTaxonomyForm
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
                    ->helperText('Must be unique and cannot use reserved system terms (category, tag, post, …).')
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? SlugGenerator::sanitize($state) : $state),
                Select::make('structure_type')
                    ->label('Structure Type')
                    ->options(TaxonomyStructure::options())
                    ->required()
                    ->rule(Rule::enum(TaxonomyStructure::class))
                    ->disabledOn('edit')
                    ->dehydrated()
                    ->helperText('Immutable after creation: hierarchical (nested terms) or flat (tag-like).'),
                CheckboxList::make('post_type_keys')
                    ->label('Associated Post Types')
                    ->options(fn (): array => PostTypeRegistry::options())
                    ->required()
                    ->columns(2)
                    ->helperText('At least one post type is required. The taxonomy appears only on associated editors.')
                    ->columnSpanFull(),
            ]);
    }
}
