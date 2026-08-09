<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Page;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Content')
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('slug')
                            ->copyable(),
                        TextEntry::make('public_path')
                            ->label('Public path')
                            ->state(fn (Page $record): string => $record->publicPath())
                            ->copyable(),
                        TextEntry::make('body')
                            ->label('Content')
                            ->html()
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Hierarchy')
                    ->schema([
                        TextEntry::make('parent.title')
                            ->label('Parent')
                            ->placeholder('— Top level —'),
                        TextEntry::make('hierarchy')
                            ->label('Path')
                            ->state(fn (Page $record): string => $record->hierarchicalLabel()),
                        TextEntry::make('sort_order')
                            ->label('Order'),
                        TextEntry::make('children_count')
                            ->label('Children')
                            ->state(fn (Page $record): int => $record->children()->count()),
                    ])
                    ->columns(2),
                Section::make('Presentation')
                    ->schema([
                        TextEntry::make('template')
                            ->label('Template')
                            ->state(fn (Page $record): string => $record->templateLabel()),
                        TextEntry::make('show_in_navigation')
                            ->label('Show in Navigation')
                            ->state(fn (Page $record): string => $record->isNavigationReady() ? 'Yes' : 'No'),
                    ])
                    ->columns(2),
                Section::make('Settings')
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (Page $record): string => $record->lifecycleLabel()),
                        TextEntry::make('author.name')
                            ->label('Author'),
                        TextEntry::make('published_at')
                            ->label('Publish Date')
                            ->dateTime(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
