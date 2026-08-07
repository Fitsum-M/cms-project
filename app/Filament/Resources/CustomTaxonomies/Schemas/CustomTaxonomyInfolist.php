<?php

namespace App\Filament\Resources\CustomTaxonomies\Schemas;

use App\Support\PostTypeRegistry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomTaxonomyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label('Name'),
                TextEntry::make('slug')->label('Slug'),
                TextEntry::make('structure_type')
                    ->label('Structure')
                    ->formatStateUsing(fn ($state): string => $state?->label() ?? (string) $state),
                TextEntry::make('post_type_keys')
                    ->label('Post Types')
                    ->state(function ($record): string {
                        $options = PostTypeRegistry::options();
                        $labels = collect($record->postTypeKeys())
                            ->map(fn (string $key): string => $options[$key] ?? $key)
                            ->all();

                        return implode(', ', $labels) ?: '—';
                    })
                    ->columnSpanFull(),
                TextEntry::make('terms_count')
                    ->label('Terms')
                    ->state(fn ($record): int => $record->terms()->count()),
                TextEntry::make('created_at')->dateTime()->label('Created'),
                TextEntry::make('updated_at')->dateTime()->label('Updated'),
            ]);
    }
}
