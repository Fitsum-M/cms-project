<?php

namespace App\Filament\Resources\PostTypes\Schemas;

use App\Models\CustomTaxonomy;
use App\Models\PostType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostTypeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Labels')
                    ->schema([
                        TextEntry::make('plural_name')
                            ->label('Plural Name'),
                        TextEntry::make('singular_name')
                            ->label('Singular Name'),
                        TextEntry::make('slug')
                            ->label('Slug')
                            ->copyable(),
                        TextEntry::make('icon')
                            ->label('Menu Icon')
                            ->placeholder('Default'),
                        TextEntry::make('posts_count')
                            ->label('Content items')
                            ->state(fn (PostType $record): int => $record->postsCount()),
                    ])
                    ->columns(2),
                Section::make('Taxonomies')
                    ->schema([
                        IconEntry::make('supports_categories')
                            ->label('Categories')
                            ->boolean(),
                        IconEntry::make('supports_tags')
                            ->label('Tags')
                            ->boolean(),
                        TextEntry::make('custom_taxonomies')
                            ->label('Custom Taxonomies')
                            ->state(function (PostType $record): string {
                                $ids = $record->customTaxonomyIds();

                                if ($ids === []) {
                                    return '— None —';
                                }

                                return CustomTaxonomy::query()
                                    ->whereIn('id', $ids)
                                    ->orderBy('name')
                                    ->pluck('name')
                                    ->implode(', ');
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
