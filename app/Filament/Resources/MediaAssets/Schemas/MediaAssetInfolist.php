<?php

namespace App\Filament\Resources\MediaAssets\Schemas;

use App\Models\MediaAsset;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MediaAssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('File')
                    ->schema([
                        ImageEntry::make('preview')
                            ->label('Preview')
                            ->visible(fn (MediaAsset $record): bool => $record->isImage())
                            ->getStateUsing(fn (MediaAsset $record): ?string => $record->previewUrl())
                            ->columnSpanFull(),
                        TextEntry::make('title')
                            ->label('Title'),
                        TextEntry::make('original_file_name')
                            ->label('File Name'),
                        TextEntry::make('mime_type')
                            ->label('MIME Type'),
                        TextEntry::make('size')
                            ->label('Size')
                            ->formatStateUsing(fn (MediaAsset $record): string => $record->humanSize()),
                        TextEntry::make('dimensions')
                            ->label('Dimensions')
                            ->state(function (MediaAsset $record): ?string {
                                if ($record->width === null || $record->height === null) {
                                    return null;
                                }

                                return "{$record->width} × {$record->height}";
                            })
                            ->placeholder('—'),
                        TextEntry::make('uploader.name')
                            ->label('Uploader'),
                        TextEntry::make('created_at')
                            ->label('Uploaded')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
