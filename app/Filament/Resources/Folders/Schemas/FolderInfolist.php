<?php

namespace App\Filament\Resources\Folders\Schemas;

use App\Models\Folder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Scaffold stub — richer stats land in Step 2.5.
 */
class FolderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Folder')
                    ->schema([
                        TextEntry::make('name')->label('Name'),
                        TextEntry::make('parent.name')
                            ->label('Parent')
                            ->placeholder('— Root level —'),
                        TextEntry::make('children_count')
                            ->label('Subfolders')
                            ->state(fn (Folder $record): int => (int) ($record->children_count ?? $record->children()->count())),
                        TextEntry::make('media_assets_count')
                            ->label('Media files')
                            ->state(fn (Folder $record): int => (int) ($record->media_assets_count ?? $record->mediaAssets()->count())),
                    ])
                    ->columns(2),
            ]);
    }
}
