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
                        TextEntry::make('url')
                            ->label('URL')
                            ->state(fn (MediaAsset $record): ?string => $record->originalUrl())
                            ->url(fn (MediaAsset $record): ?string => $record->originalUrl())
                            ->openUrlInNewTab()
                            ->copyable()
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('generated_sizes')
                            ->label('Generated sizes')
                            ->visible(fn (MediaAsset $record): bool => MediaAsset::supportsRasterConversions($record->mime_type))
                            ->state(function (MediaAsset $record): string {
                                $ready = [];

                                foreach (MediaAsset::imageConversions() as $conversion) {
                                    if ($record->hasGeneratedConversion($conversion)) {
                                        $ready[] = $conversion;
                                    }
                                }

                                if ($ready === []) {
                                    return 'Original preserved · conversions pending';
                                }

                                return 'Original preserved · '.implode(', ', $ready);
                            }),
                        TextEntry::make('uploader.name')
                            ->label('Uploaded By'),
                        TextEntry::make('folder.name')
                            ->label('Folder')
                            ->placeholder('Unfiled'),
                        TextEntry::make('created_at')
                            ->label('Upload Date')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Title'),
                        TextEntry::make('alt_text')
                            ->label('Alt Text')
                            ->placeholder('—'),
                        TextEntry::make('caption')
                            ->label('Caption')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('References')
                    ->description('Media is referenced by ID. Delete is blocked while any reference remains; Administrators may force-delete.')
                    ->schema([
                        TextEntry::make('usage')
                            ->label('Used by')
                            ->state(function (MediaAsset $record): string {
                                $refs = app(\App\Services\MediaReferenceService::class)->references($record);

                                if ($refs->isEmpty()) {
                                    return 'Not referenced — safe to delete.';
                                }

                                return $refs
                                    ->map(fn ($ref): string => "{$ref->label}: {$ref->detail}")
                                    ->implode("\n");
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
