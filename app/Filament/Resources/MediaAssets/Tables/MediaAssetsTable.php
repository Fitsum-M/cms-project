<?php

namespace App\Filament\Resources\MediaAssets\Tables;

use App\Models\MediaAsset;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MediaAssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview')
                    ->label('Preview')
                    ->getStateUsing(fn (MediaAsset $record): ?string => $record->isImage() ? $record->previewUrl() : null)
                    ->square()
                    ->extraImgAttributes(['alt' => '']),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->description(fn (MediaAsset $record): string => $record->original_file_name),
                TextColumn::make('mime_type')
                    ->label('Type')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn (MediaAsset $record): string => $record->humanSize())
                    ->sortable(),
                TextColumn::make('uploader.name')
                    ->label('Uploader')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
