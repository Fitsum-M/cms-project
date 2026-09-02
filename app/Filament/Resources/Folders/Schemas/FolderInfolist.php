<?php

namespace App\Filament\Resources\Folders\Schemas;

use App\Models\Folder;
use App\Services\FolderService;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FolderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Folder summary')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name'),
                        TextEntry::make('path')
                            ->label('Full path')
                            ->state(fn (Folder $record): string => app(FolderService::class)->hierarchicalLabel($record))
                            ->columnSpanFull(),
                        TextEntry::make('parent.name')
                            ->label('Parent')
                            ->placeholder('— Root level —')
                            ->url(fn (Folder $record): ?string => $record->parent_id
                                ? \App\Filament\Resources\Folders\FolderResource::getUrl('view', ['record' => $record->parent_id])
                                : null),
                        IconEntry::make('is_empty')
                            ->label('Empty')
                            ->boolean()
                            ->state(fn (Folder $record): bool => $record->isEmpty())
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-folder-open')
                            ->trueColor('success')
                            ->falseColor('gray'),
                    ])
                    ->columns(2),
                Section::make('Contents')
                    ->description('Counts for this folder only (not nested descendants).')
                    ->schema([
                        TextEntry::make('children_count')
                            ->label('Subfolders')
                            ->state(fn (Folder $record): int => (int) ($record->children_count ?? $record->children()->count()))
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('media_assets_count')
                            ->label('Media files')
                            ->state(fn (Folder $record): int => (int) ($record->media_assets_count ?? $record->mediaAssets()->count()))
                            ->badge()
                            ->color(fn (Folder $record): string => ((int) ($record->media_assets_count ?? $record->mediaAssets()->count())) > 0
                                ? 'primary'
                                : 'gray'),
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Updated')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
