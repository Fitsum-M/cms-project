<?php

namespace App\Filament\Resources\Folders\Tables;

use App\Filament\Resources\Folders\FolderActions;
use App\Filament\Resources\Folders\FolderResource;
use App\Models\Folder;
use App\Services\FolderService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Hierarchical table config kept for record actions / future use.
 * List UX is the custom tree on ListFolders (Step 2.3 decision).
 */
class FoldersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Folder $record): ?string => filled($record->parent_id)
                        ? app(FolderService::class)->hierarchicalLabel($record)
                        : null),
                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('— Root —')
                    ->sortable(),
                TextColumn::make('children_count')
                    ->label('Subfolders')
                    ->counts('children')
                    ->sortable(),
                TextColumn::make('media_assets_count')
                    ->label('Files')
                    ->counts('mediaAssets')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                FolderActions::moveAction(),
                FolderActions::configureDeleteAction(DeleteAction::make()),
            ])
            ->recordUrl(fn (Folder $record): string => FolderResource::getUrl(
                auth()->user()?->can('update', $record) ? 'edit' : 'view',
                ['record' => $record],
            ));
    }
}
